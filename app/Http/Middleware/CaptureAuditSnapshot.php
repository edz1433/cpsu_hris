<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use App\Services\Audit\AuditModuleCatalog;
use App\Services\Audit\AuditScope;
use App\Services\Audit\AuditWriter;
use App\Services\Audit\AuditFlash;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CaptureAuditSnapshot
{
    public function handle(Request $request, Closure $next)
    {
        $spec = config('audit.snapshots.'.optional($request->route())->getName());
        $key = $spec['key'] ?? 'id';
        $id = $spec ? $request->input($spec['id_input']) : null;
        // Row ids must be numeric; other keys (official_times.empid) are short employee codes.
        $validId = is_scalar($id) && ($key === 'id' ? ctype_digit((string) $id)
            : trim((string) $id) !== '' && strlen((string) $id) <= 100);
        if (!$spec || AuditScope::excludes($request) || AuditScope::isDtr($request) || !$validId) {
            return $next($request);
        }

        $query = fn () => DB::connection($spec['connection'])->table($spec['table'])
            ->where($key, $key === 'id' ? (int) $id : (string) $id)->first(array_merge(['id'], $spec['fields']));
        $before = $query();
        $response = $next($request);
        if ($response->getStatusCode() >= 400 || AuditFlash::hasFailure($request)) {
            return $response;
        }

        $after = $query();
        if (!$before && !$after) {
            return $response;
        }

        $oldData = [];
        $newData = [];
        foreach ($spec['fields'] as $field) {
            $old = $before->$field ?? null;
            $new = $after->$field ?? null;
            if ($old != $new) {
                $oldData[$field] = $old;
                $newData[$field] = $new;
            }
        }
        $unchanged = !$oldData && !$newData && (bool) $before === (bool) $after;
        // A save that changed nothing is still evidence when the spec asks for it.
        if ($unchanged && !($spec['record_unchanged'] ?? false)) {
            return $response;
        }

        $action = $unchanged ? 'no_change' : (!$before ? 'created' : (!$after ? 'deleted' : 'updated'));
        $employee = match ($spec['target_type']) {
            'employee' => Employee::query()->find((int) $id),
            'official_time' => Employee::query()->where('emp_ID', (string) $id)->first(),
            default => null,
        };
        $name = match ($spec['target_type']) {
            'office' => $after->office_name ?? $before->office_name ?? null,
            'employee' => trim(($after->fname ?? $before->fname ?? '').' '.($after->lname ?? $before->lname ?? '')),
            default => $employee ? trim(implode(' ', array_filter([$employee->fname, $employee->mname, $employee->lname]))) : (string) $id,
        };
        $written = app(AuditWriter::class)->record([
            'module' => app(AuditModuleCatalog::class)->module($request),
            'action' => $action,
            'outcome' => $unchanged ? 'unchanged' : 'success',
            'reason_code' => $unchanged ? 'no_changes' : null,
            'target_type' => $spec['target_type'],
            'target_id' => $id,
            'target_name' => $name,
            'employee' => $employee,
            'employee_id' => $spec['target_type'] === 'employee' ? (int) $id : null,
            'office_id' => $spec['target_type'] === 'office' ? (int) $id : null,
            'details' => ['old_data' => $oldData, 'new_data' => $newData],
        ]);
        if ($written) {
            // This entry describes the request; RecordAuditRequest must not add a generic "submitted" row.
            $request->attributes->set('audit_model_events', 1 + (int) $request->attributes->get('audit_model_events', 0));
        }
        if ($written && !$unchanged) {
            $key = $spec['table'].'|'.($action === 'created' ? 'insert' : ($action === 'deleted' ? 'delete' : 'update'));
            $snapshots = $request->attributes->get('audit_snapshot_writes', []);
            $snapshots[$key] = ($snapshots[$key] ?? 0) + 1;
            $request->attributes->set('audit_snapshot_writes', $snapshots);
        }

        return $response;
    }
}
