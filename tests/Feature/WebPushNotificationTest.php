<?php

namespace Tests\Feature;

use App\Http\Controllers\PushSubscriptionController;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPush\NotificationPush;
use App\Services\WebPush\PushSender;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Web push mirroring of in-app notifications (PWA).
class WebPushNotificationTest extends TestCase
{
    /** @var array<int, array{endpoints: array, message: array}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['mysql', 'payroll'] as $connection) {
            config()->set("database.connections.$connection", [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ]);
            DB::purge($connection);
        }
        config()->set('webpush.public_key', 'test-public');
        config()->set('webpush.private_key', 'test-private');

        (require database_path('migrations/2026_11_17_000001_create_push_subscriptions_table.php'))->up();

        $schema = Schema::connection('mysql');
        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('username')->nullable();
            $table->string('role')->nullable();
            $table->string('access')->nullable();
            $table->timestamps();
        });
        $schema->create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID');
            $table->string('fname');
            $table->string('lname');
            $table->unsignedBigInteger('supervisor')->nullable();
            $table->unsignedBigInteger('emp_dept')->nullable();
            $table->timestamps();
        });
        Schema::connection('payroll')->create('offices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('oic_id')->nullable();
            $table->timestamps();
        });
        $schema->create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->string('empid');
            $table->string('transnum')->nullable();
            $table->integer('leave_type');
            $table->unsignedBigInteger('supervisor')->nullable();
            $table->unsignedBigInteger('department')->nullable();
            $table->unsignedBigInteger('president')->nullable();
            $table->timestamps();
        });
        $schema->create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('empid')->nullable();
            $table->unsignedBigInteger('lapp_id')->nullable();
            $table->unsignedBigInteger('esign_id')->nullable();
            $table->decimal('category', 4, 1)->nullable();
            $table->string('utype')->nullable();
            $table->string('module')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });

        $db = DB::connection('mysql');
        $db->table('users')->insert([
            ['id' => 1, 'role' => 'Administrator', 'access' => ''],
            ['id' => 2, 'role' => 'HR', 'access' => '0,0,0,0,0,0,0,1'], // leave only
            ['id' => 3, 'role' => 'HR', 'access' => '1,0,0,0,0,0,0,0'], // employees only
        ]);
        $db->table('employees')->insert([
            ['id' => 10, 'emp_ID' => 'E-10', 'fname' => 'ANA', 'lname' => 'SANTOS', 'supervisor' => 11, 'emp_dept' => 1],
            ['id' => 11, 'emp_ID' => 'E-11', 'fname' => 'Sup', 'lname' => 'Ervisor', 'supervisor' => null, 'emp_dept' => 1],
            ['id' => 12, 'emp_ID' => 'E-12', 'fname' => 'Pres', 'lname' => 'Ident', 'supervisor' => null, 'emp_dept' => null],
            ['id' => 13, 'emp_ID' => 'E-13', 'fname' => 'Oic', 'lname' => 'Person', 'supervisor' => null, 'emp_dept' => 1],
        ]);
        DB::connection('payroll')->table('offices')->insert(['id' => 1, 'oic_id' => null]);
        $db->table('leave_applications')->insert([
            'id' => 5, 'empid' => 'E-10', 'transnum' => '2026-0005', 'leave_type' => 1, 'supervisor' => 11, 'department' => 1, 'president' => 12,
        ]);

        foreach ([['user', 1], ['user', 2], ['user', 3], ['employee', 10], ['employee', 11], ['employee', 12], ['employee', 13]] as [$type, $id]) {
            $this->subscription($type, $id);
        }

        $sent = &$this->sent;
        $this->app->instance(PushSender::class, new class($sent) extends PushSender {
            public function __construct(private array &$sent)
            {
            }

            public function send(Collection $subscriptions, array $message): int
            {
                $this->sent[] = ['endpoints' => $subscriptions->pluck('endpoint')->sort()->values()->all(), 'message' => $message];

                return $subscriptions->count();
            }
        });
    }

    private function subscription(string $type, int $id): PushSubscription
    {
        $endpoint = "https://push.example/{$type}-{$id}";

        return PushSubscription::create([
            'subscriber_type' => $type, 'subscriber_id' => $id, 'endpoint' => $endpoint,
            'endpoint_hash' => PushSubscription::hashEndpoint($endpoint), 'public_key' => 'p', 'auth_token' => 'a',
        ]);
    }

    private function notify(array $attributes): Notification
    {
        $callbacks = new \ReflectionProperty($this->app, 'terminatingCallbacks');
        $callbacks->setAccessible(true);
        $before = count($callbacks->getValue($this->app));

        $notification = Notification::create($attributes + ['empid' => 'E-10', 'lapp_id' => 5]);

        // Run just the after-response push this notification queued.
        $all = $callbacks->getValue($this->app);
        foreach (array_slice($all, $before) as $callback) {
            $this->app->call($callback);
        }
        $callbacks->setValue($this->app, array_slice($all, 0, $before));

        return $notification;
    }

    public function test_new_leave_application_reaches_only_hr_accounts_with_leave_access(): void
    {
        $this->notify(['category' => 1, 'utype' => 'hr', 'module' => 'leave']);

        $this->assertCount(1, $this->sent);
        $this->assertSame(['https://push.example/user-1', 'https://push.example/user-2'], $this->sent[0]['endpoints']);
        $this->assertSame('New leave application', $this->sent[0]['message']['title']);
        $this->assertStringContainsString('Ana Santos is applying for Vacation Leave (Application No. #2026-0005)', $this->sent[0]['message']['body']);
        $this->assertStringEndsWith('/leave/status/10', $this->sent[0]['message']['url']);
    }

    public function test_approval_reaches_only_the_applicant(): void
    {
        $this->notify(['category' => 2, 'utype' => 'employee', 'module' => 'leave']);

        $this->assertSame(['https://push.example/employee-10'], $this->sent[0]['endpoints']);
        $this->assertSame('Leave application approved', $this->sent[0]['message']['title']);
    }

    public function test_supervisor_step_goes_to_the_oic_when_one_is_assigned(): void
    {
        $this->notify(['category' => 3, 'utype' => 'supervisor', 'module' => 'leave', 'esign_id' => 11]);
        $this->assertSame(['https://push.example/employee-11'], $this->sent[0]['endpoints']);

        DB::connection('payroll')->table('offices')->where('id', 1)->update(['oic_id' => 13]);
        $this->notify(['category' => 3, 'utype' => 'supervisor', 'module' => 'leave', 'esign_id' => 11]);
        $this->assertSame(['https://push.example/employee-13'], $this->sent[1]['endpoints']);
    }

    public function test_president_step_goes_to_the_assigned_president(): void
    {
        $this->notify(['category' => 4, 'utype' => 'president', 'module' => 'leave']);

        $this->assertSame(['https://push.example/employee-12'], $this->sent[0]['endpoints']);
        $this->assertSame('Leave application awaiting your approval', $this->sent[0]['message']['title']);
    }

    public function test_pds_submission_reaches_hr_accounts_with_employee_access(): void
    {
        $this->notify(['category' => 1, 'utype' => 'hr', 'module' => 'pds', 'lapp_id' => 99]);

        $this->assertSame(['https://push.example/user-1', 'https://push.example/user-3'], $this->sent[0]['endpoints']);
        $this->assertSame('Ana Santos has submitted a new eligibility.', $this->sent[0]['message']['body']);
    }

    public function test_rows_created_already_read_and_unknown_modules_are_not_pushed(): void
    {
        $this->notify(['category' => 1, 'utype' => 'employee', 'module' => 'leavecreditadd', 'status' => 1]);
        $this->notify(['category' => 1, 'utype' => 'employee', 'module' => 'dtr']);

        $this->assertSame([], $this->sent);
    }

    public function test_nothing_is_pushed_without_vapid_keys(): void
    {
        config()->set('webpush.private_key', null);

        $this->notify(['category' => 2, 'utype' => 'employee', 'module' => 'leave']);

        $this->assertSame([], $this->sent);
    }

    public function test_subscribing_links_the_device_to_the_signed_in_account(): void
    {
        $body = ['endpoint' => 'https://push.example/shared-pc', 'keys' => ['p256dh' => 'pk', 'auth' => 'ak'], 'contentEncoding' => 'aes128gcm'];

        $this->be(User::find(2), 'web');
        $this->subscribe($body);
        auth()->guard('web')->logout();

        // Same browser, next person signs in: the device moves to them.
        $this->be(Employee::find(10), 'employee');
        $this->subscribe($body);

        $rows = PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($body['endpoint']))->get();
        $this->assertCount(1, $rows);
        $this->assertSame(['employee', 10], [$rows[0]->subscriber_type, (int) $rows[0]->subscriber_id]);
    }

    public function test_unsubscribe_only_removes_the_callers_own_device(): void
    {
        $this->be(Employee::find(10), 'employee');

        $request = $this->ajax(Request::create('/notification/push/unsubscribe', 'POST', ['endpoint' => 'https://push.example/employee-11']));
        app()->instance('request', $request);
        (new PushSubscriptionController())->destroy($request);
        $this->assertSame(1, PushSubscription::where('subscriber_id', 11)->where('subscriber_type', 'employee')->count());

        $request = $this->ajax(Request::create('/notification/push/unsubscribe', 'POST', ['endpoint' => 'https://push.example/employee-10']));
        app()->instance('request', $request);
        (new PushSubscriptionController())->destroy($request);
        $this->assertSame(0, PushSubscription::where('subscriber_id', 10)->where('subscriber_type', 'employee')->count());
    }

    public function test_message_uses_a_per_item_tag_so_updates_replace_older_alerts(): void
    {
        $push = new NotificationPush(new Notification(['empid' => 'E-10', 'lapp_id' => 5, 'category' => 2, 'utype' => 'employee', 'module' => 'leave']));

        $this->assertSame('hris-leave-5', $push->message()['tag']);
    }

    private function ajax(Request $request): Request
    {
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        return $request;
    }

    private function subscribe(array $body): void
    {
        $request = $this->ajax(Request::create('/notification/push/subscribe', 'POST', $body));
        app()->instance('request', $request);
        $response = (new PushSubscriptionController())->store($request);
        $this->assertTrue($response->getData(true)['success']);
    }
}
