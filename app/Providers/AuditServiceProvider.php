<?php

namespace App\Providers;

use App\Models\Dtr;
use App\Services\Audit\AuditDtrRecorder;
use App\Services\Audit\AuditAuthListener;
use App\Services\Audit\AuditDatabaseWrites;
use App\Services\Audit\AuditModelListener;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        app(AuditDatabaseWrites::class)->listen();
        Event::listen('eloquent.saved: '.Dtr::class, [AuditDtrRecorder::class, 'captureAttendance']);
        Event::listen(Login::class, [AuditAuthListener::class, 'login']);
        Event::listen(Logout::class, [AuditAuthListener::class, 'logout']);

        foreach (['created', 'updated', 'deleted'] as $action) {
            Event::listen('eloquent.'.$action.': *', function (string $event, array $payload) use ($action) {
                $model = $payload[0] ?? null;
                if ($model instanceof Model) {
                    app(AuditModelListener::class)->record($action, $model);
                }
            });
        }
    }
}
