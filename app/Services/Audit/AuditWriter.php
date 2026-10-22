<?php

namespace App\Services\Audit;

use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AuditWriter
{
    public function record(array $event): bool
    {
        try {
            $request = app()->bound('request') ? request() : null;
            if ($request && AuditScope::excludes($request)) {
                return false;
            }

            $guard = null;
            $actor = null;
            if ($request) {
                foreach (['web', 'employee'] as $candidate) {
                    if (Auth::guard($candidate)->check()) {
                        $guard = $candidate;
                        $actor = Auth::guard($candidate)->user();
                        break;
                    }
                }
            }

            $actor = $event['actor'] ?? $actor;
            $guard = $event['actor_type'] ?? $guard;
            $employee = $event['employee'] ?? null;
            $actorName = $actor ? trim(implode(' ', array_filter([$actor->fname ?? null, $actor->mname ?? null, $actor->lname ?? null]))) : null;
            $actorName = $actorName ?: ($actor->username ?? null);
            $requestId = null;
            if ($request) {
                $requestId = $request->attributes->get('audit_request_id');
                if (!$requestId) {
                    $requestId = (string) Str::uuid();
                    $request->attributes->set('audit_request_id', $requestId);
                }
            }

            $details = $event['details'] ?? [];
            if (!is_array($details)) {
                $details = [];
            }

            DB::table('audit_logs')->insert([
                'occurred_at' => now()->format('Y-m-d H:i:s.u'),
                'module' => Str::limit((string) ($event['module'] ?? 'system'), 60, ''),
                'action' => Str::limit((string) ($event['action'] ?? 'request'), 60, ''),
                'outcome' => Str::limit((string) ($event['outcome'] ?? 'success'), 20, ''),
                'reason_code' => isset($event['reason_code']) ? Str::limit((string) $event['reason_code'], 80, '') : null,
                'actor_type' => $guard,
                'actor_id' => $actor->id ?? null,
                'actor_name' => $actorName ? Str::limit($actorName, 255, '') : null,
                'target_type' => isset($event['target_type']) ? Str::limit((string) $event['target_type'], 60, '') : null,
                'target_id' => isset($event['target_id']) ? Str::limit((string) $event['target_id'], 100, '') : null,
                'target_name' => isset($event['target_name']) ? Str::limit((string) $event['target_name'], 255, '') : null,
                'employee_id' => $this->numericId($employee->id ?? ($event['employee_id'] ?? null)),
                'employee_code' => isset($employee->emp_ID) ? Str::limit((string) $employee->emp_ID, 100, '') : ($event['employee_code'] ?? null),
                'campus_id' => $this->numericId($event['campus_id'] ?? ($employee->camp_id ?? ($guard === 'web' ? ($actor->campus_id ?? null) : ($actor->camp_id ?? null)))),
                'office_id' => $this->numericId($event['office_id'] ?? ($employee->emp_dept ?? ($actor instanceof Employee ? ($actor->emp_dept ?? null) : null))),
                'ip_address' => $request ? $request->ip() : null,
                'user_agent' => $request && !($event['redact_user_agent'] ?? false)
                    ? Str::limit((string) $request->userAgent(), 2000, '') : null,
                'http_method' => $request ? $request->method() : null,
                'route_name' => $request ? Str::limit((string) optional($request->route())->getName(), 150, '') : null,
                'request_id' => $requestId,
                'summary' => null,
                'details' => json_encode($details, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            return true;
        } catch (Throwable $e) {
            try {
                Log::warning('Audit record could not be stored.', ['exception' => get_class($e)]);
            } catch (Throwable $loggingError) {
                // A failed warning sink must not change the caller's response.
            }
            return false;
        }
    }

    private function numericId($value): ?int
    {
        return is_scalar($value) && ctype_digit((string) $value) ? (int) $value : null;
    }
}
