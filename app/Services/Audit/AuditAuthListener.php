<?php

namespace App\Services\Audit;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuditAuthListener
{
    public function login(Login $event): void
    {
        $this->record('login', $event->guard, $event->user);
    }

    public function logout(Logout $event): void
    {
        $this->record('logout', $event->guard, $event->user);
    }

    private function record(string $action, string $guard, $user): void
    {
        if ((app()->runningInConsole() && !config('audit.capture_in_console')) || !app()->bound('request') || AuditScope::excludes(request())) {
            return;
        }

        $name = trim(implode(' ', array_filter([$user->fname ?? null, $user->mname ?? null, $user->lname ?? null])));
        app(AuditWriter::class)->record([
            'module' => 'authentication',
            'action' => $action,
            'actor_type' => $guard,
            'actor' => $user,
            'target_type' => $guard === 'employee' ? 'employee' : 'user',
            'target_id' => $user->id ?? null,
            'target_name' => $name ?: ($user->username ?? null),
            'employee' => $guard === 'employee' ? $user : null,
            'details' => ['guard' => $guard],
        ]);
        request()->attributes->set('audit_auth_events', 1 + (int) request()->attributes->get('audit_auth_events', 0));
    }
}
