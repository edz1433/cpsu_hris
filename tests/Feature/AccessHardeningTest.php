<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveApplication;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Each test plays an attacker: a signed-in employee (or a visitor) going after
// someone else's account or records through the real routes and middleware.
class AccessHardeningTest extends TestCase
{
    private Employee $me;
    private Employee $other;

    protected function setUp(): void
    {
        parent::setUp();
        // Cached production view paths may not exist on the Windows test host.
        app('view')->getFinder()->setPaths([resource_path('views')]);
        // .env serves the app from a subfolder; requests here target the site root.
        config()->set('app.url', 'http://localhost');
        app('url')->forceRootUrl('http://localhost');
        foreach (['mysql', 'payroll'] as $connection) {
            config()->set("database.connections.$connection", [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ]);
            DB::purge($connection);
        }
        config()->set('database.default', 'mysql');

        (require database_path('migrations/2026_10_08_000001_create_audit_logs_table.php'))->up();
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('maintenance')->default(false);
            $table->text('dtr_acct')->nullable();
        });
        DB::table('settings')->insert(['dtr_acct' => '']);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('username');
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->string('verification_code')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID');
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->default('employee');
            $table->integer('stat_1')->default(1);
            $table->integer('emp_status')->default(1);
            $table->unsignedBigInteger('camp_id')->nullable();
            $table->unsignedBigInteger('emp_dept')->nullable();
            $table->string('mobile')->nullable();
            $table->string('org_email')->nullable();
            $table->decimal('vl')->default(0);
            $table->text('esign')->nullable();
            $table->string('verification_code')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
        Schema::create('eligibilities', function (Blueprint $table) {
            $table->id();
            $table->string('empid');
            $table->integer('status')->default(0);
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->string('empid');
            $table->unsignedBigInteger('supervisor')->nullable();
            $table->unsignedBigInteger('oic')->nullable();
            $table->unsignedBigInteger('president')->nullable();
            $table->unsignedBigInteger('hr')->nullable();
            $table->string('gen_app')->nullable();
            $table->timestamps();
        });

        // Access decisions only; AJAX skips the page-chrome queries in the base Controller.
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest']);

        $this->me = Employee::create(['emp_ID' => 'E-1', 'fname' => 'Ana', 'lname' => 'Reyes', 'username' => 'ana@example.test']);
        $this->other = Employee::create(['emp_ID' => 'E-2', 'fname' => 'Ben', 'lname' => 'Cruz', 'username' => 'ben@example.test', 'vl' => 10]);
    }

    public function test_verify_code_cannot_be_skipped_or_used_for_another_account(): void
    {
        // A used code is null; an empty code used to match it.
        $this->post('/verify', ['email' => 'ben@example.test', 'verification_code' => '']);
        $this->assertGuest('employee');

        // A right code without the Google step in this browser is refused.
        DB::table('employees')->where('id', $this->other->id)->update(['verification_code' => '123456']);
        $this->post('/verify', ['email' => 'ben@example.test', 'verification_code' => '123456']);
        $this->assertGuest('employee');

        // The posted email is ignored; only the one Google confirmed counts.
        $this->withSession(['google_verify' => ['email' => 'ana@example.test', 'expires_at' => now()->addMinutes(5)->getTimestamp()]])
            ->post('/verify', ['email' => 'ben@example.test', 'verification_code' => '123456']);
        $this->assertGuest('employee');

        $this->withSession(['google_verify' => ['email' => 'ben@example.test', 'expires_at' => now()->addMinutes(5)->getTimestamp()]])
            ->post('/verify', ['verification_code' => '123456']);
        $this->assertAuthenticatedAs($this->other, 'employee');
    }

    public function test_password_guessing_is_rate_limited(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post('/post-login', ['username' => 'ben@example.test', 'password' => 'guess'.$attempt])
                ->assertSessionHas('error', 'Invalid Credentials');
        }
        $this->post('/post-login', ['username' => 'ben@example.test', 'password' => 'guess6'])
            ->assertSessionHas('error', 'Too many sign-in attempts. Please wait a minute and try again.');
    }

    public function test_employee_accounts_cannot_reach_admin_actions(): void
    {
        $this->actingAs($this->me, 'employee');
        foreach (['/user/create', '/employees/update', '/employees/toggle-acct-stat', '/leaves/leaves-deduct',
                  '/tardiness/data', '/career/create', '/pds/eligibility-approve/1', '/pds/eligibility-cancel',
                  '/leave/leave-wpay', '/leave/cacelLeave/1', '/leave/undo'] as $uri) {
            $this->post($uri)->assertRedirect(route('dashboard'));
        }
        $this->assertSame(0, DB::table('users')->count());
    }

    public function test_deletes_no_longer_run_over_get(): void
    {
        foreach (['/employees/delete/1', '/office/delete/1', '/events/delete/1', '/spms/delete/1', '/spms/delete-file/1'] as $uri) {
            $this->get($uri)->assertStatus(405);
        }
    }

    public function test_profile_edits_are_limited_to_own_record_and_safe_fields(): void
    {
        $this->actingAs($this->me, 'employee');

        $this->postJson('/employees/employee-update', ['id' => $this->other->id, 'column' => 'mobile', 'value' => '0917'])
            ->assertStatus(403);
        $this->postJson('/employees/employee-update', ['id' => $this->me->id, 'column' => 'vl', 'value' => '999'])
            ->assertStatus(422);
        $this->postJson('/employees/employee-update', ['id' => $this->me->id, 'column' => 'mobile', 'value' => '0917'])
            ->assertOk();

        $this->assertSame('0917', $this->me->fresh()->mobile);
        $this->assertNull($this->other->fresh()->mobile);
        $this->assertEquals(0, $this->me->fresh()->vl);
    }

    public function test_employee_cannot_change_another_employees_signature_or_pds(): void
    {
        $this->actingAs($this->me, 'employee');

        $this->postJson('/pds/upload-signature/'.$this->other->id)->assertStatus(403);
        $this->postJson('/pds/familybg-update', ['id' => $this->other->id, 'column' => 'spouse_lname', 'value' => 'X'])
            ->assertStatus(403);

        $theirs = DB::table('eligibilities')->insertGetId(['empid' => 'E-2']);
        $reviewed = DB::table('eligibilities')->insertGetId(['empid' => 'E-1', 'status' => 1]);
        $mine = DB::table('eligibilities')->insertGetId(['empid' => 'E-1', 'attachment' => 'Eligibility/none.pdf']);
        $this->postJson('/pds/eligibility-delete/'.$theirs)->assertStatus(403);
        $this->postJson('/pds/eligibility-delete/'.$reviewed)->assertStatus(403);
        $this->postJson('/pds/eligibility-delete/'.$mine)->assertOk();
        $this->assertSame(2, DB::table('eligibilities')->count());
    }

    public function test_employee_sees_only_their_own_or_signed_leave(): void
    {
        Storage::fake('local');
        Storage::put('public/Leaveapplication/a.pdf', 'pdf');
        $theirs = LeaveApplication::create(['empid' => 'E-2', 'gen_app' => 'Leaveapplication/a.pdf']);
        $signedByMe = LeaveApplication::create(['empid' => 'E-2', 'supervisor' => $this->me->id, 'gen_app' => 'Leaveapplication/a.pdf']);
        $this->actingAs($this->me, 'employee');

        $this->postJson('/leave/get-pdf-path', ['id' => $theirs->id])->assertStatus(404);
        $this->postJson('/leave/get-pdf-path', ['id' => $signedByMe->id])->assertOk();
        $this->getJson('/leave/preview-leave/'.$theirs->id)->assertStatus(403);

        // Filing leave for someone else is refused.
        $this->post('/leave/create', ['empid' => 'E-2', 'date_range' => '2026-11-02'])
            ->assertSessionHas('error', 'You can only file leave for yourself.');
        $this->assertSame(2, LeaveApplication::count());
    }

    public function test_employee_cannot_read_another_employees_dtr(): void
    {
        $this->actingAs($this->me, 'employee');

        $this->postJson('/dtr', ['employee' => 'E-2', 'period' => '1', 'date' => '2026-10'])->assertStatus(403);
        $this->getJson('/dtr/pdf?employee=E-2&period=1&date=2026-10')->assertStatus(403);
        $this->postJson('/dtr/dtr-logs', ['employee' => 'E-2'])->assertStatus(403);
    }
    public function test_only_named_hr_reviewers_open_job_applications_from_employee_accounts(): void
    {
        Schema::create('job_hirings', function (Blueprint $table) { $table->id(); $table->string('title'); });
        Schema::create('applications', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('jid'); $table->string('ctrl_no')->nullable(); });

        $this->actingAs($this->me, 'employee');
        $this->get('/career/view-application/1')->assertRedirect(route('dashboard'));

        $reviewer = Employee::create(['emp_ID' => 'E-3', 'fname' => 'Cy', 'lname' => 'Lim',
            'org_email' => Employee::JOB_APPLICATION_REVIEWERS[0]])->fresh();
        $this->actingAs($reviewer, 'employee');
        // Reaches the page (the missing application sends them back), not the permission redirect.
        $this->from('/dashboard-previous')->get('/career/view-application/1')->assertRedirect('/dashboard-previous');
        $this->post('/career/create')->assertRedirect(route('dashboard'));
    }
}