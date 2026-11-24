<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\TimeEntryController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Time Entry punches and face registrations need server-side proof (face-claim / admin-face-claim), not just a POST.
class TimeEntryVerificationGateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('cache.default', 'array');
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        DB::purge('mysql');
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID');
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->text('face_embeddings')->nullable();
        });
        Schema::create('dtrs', function (Blueprint $table) {
            $table->id();
            $table->string('emp_ID')->nullable();
            $table->date('date')->nullable();
            foreach (['time_in', 'time_out', 'time_over', 'device_id_in', 'device_id_out', 'device_id_over'] as $field) $table->text($field)->nullable();
        });
        DB::table('employees')->insert([['emp_ID' => 'E-8'], ['emp_ID' => 'A-1']]);
        Cache::flush();
    }

    private function controller(): TimeEntryController
    {
        return (new \ReflectionClass(TimeEntryController::class))->newInstanceWithoutConstructor();
    }

    public function test_log_attendance_is_refused_without_a_recent_face_match(): void
    {
        $request = Request::create('/api/app/log-attendance', 'POST', ['action' => 1, 'emp_id' => 'E-8', 'zone_id' => '-5']);
        $response = $this->controller()->logAttendance($request);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(0, DB::table('dtrs')->count());
    }

    public function test_log_attendance_keeps_unknown_employee_response(): void
    {
        $request = Request::create('/api/app/log-attendance', 'POST', ['action' => 1, 'emp_id' => 'NOPE', 'zone_id' => '-5']);
        $this->assertSame(404, $this->controller()->logAttendance($request)->getStatusCode());
    }

    public function test_face_register_is_refused_without_a_recent_admin_face_check(): void
    {
        $request = Request::create('/api/app/face-register', 'POST', [
            'emp_ID' => 'E-8', 'enroller_ID' => 'A-1', 'embedding' => array_fill(0, 128, 0.1),
        ]);
        $response = $this->controller()->faceRegister($request);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertNull(DB::table('employees')->where('emp_ID', 'E-8')->value('face_embeddings'));
    }

    public function test_face_register_keeps_existing_rejections_before_the_admin_check(): void
    {
        $c = $this->controller();
        $this->assertSame(404, $c->faceRegister(Request::create('/', 'POST', ['emp_ID' => 'NOPE', 'enroller_ID' => 'A-1']))->getStatusCode());
        $this->assertSame(400, $c->faceRegister(Request::create('/', 'POST', ['emp_ID' => 'E-8', 'enroller_ID' => 'NOPE']))->getStatusCode());
        $this->assertSame(400, $c->faceRegister(Request::create('/', 'POST', ['emp_ID' => 'E-8', 'enroller_ID' => 'A-1', 'embedding' => [1]]))->getStatusCode());
    }
}
