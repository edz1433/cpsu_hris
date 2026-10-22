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
        $id = $spec ? $request->input($spec['id_input']) : null;
        if (!$spec || AuditScope::excludes($request) || AuditScope::isDtr($request) || !is_scalar($id) || !ctype_digit((string) $id)) {
            return $next($request);
        }

        $query = fn () => DB::connection($spec['connection'])->table($spec['table'])
            ->where('id', (int) $id)->first(array_merge(['id'], $spec['fields']));
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
        if (!$oldData && !$newData && (bool) $before === (bool) $after) {
            return $response;
        }

        $action = !$before ? 'created' : (!$after ? 'deleted' : 'updated');
        $name = $spec['target_type'] === 'office'
            ? ($after->office_name ?? $before->office_name ?? null)
            : trim(($after->fname ?? $before->fname ?? '').' '.($after->lname ?? $before->lname ?? ''));
        $written = app(AuditWriter::class)->record([
            'module' => app(AuditModuleCatalog::class)->module($request),
            'action' => $action,
            'target_type' => $spec['target_type'],
            'target_id' => $id,
            'target_name' => $name,
            'employee' => $spec['target_type'] === 'employee' ? Employee::query()->find((int) $id) : null,
            'employee_id' => $spec['target_type'] === 'employee' ? (int) $id : null,
            'office_id' => $spec['target_type'] === 'office' ? (int) $id : null,
            'details' => ['old_data' => $oldData, 'new_data' => $newData],
        ]);
        if ($written) {
            $key = $spec['table'].'|'.($action === 'created' ? 'insert' : ($action === 'deleted' ? 'delete' : 'update'));
            $snapshots = $request->attributes->get('audit_snapshot_writes', []);
            $snapshots[$key] = ($snapshots[$key] ?? 0) + 1;
            $request->attributes->set('audit_snapshot_writes', $snapshots);
        }

        return $response;
    }
}
