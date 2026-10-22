<?php

namespace App\Services\Audit;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditDatabaseWrites
{
    public function listen(array $connections = ['mysql', 'payroll']): void
    {
        foreach ($connections as $connection) {
            DB::connection($connection)->listen(function (QueryExecuted $query) use ($connection) {
                if ($query->connectionName === $connection) {
                    $this->observe($query);
                }
            });
        }
    }

    public function observe(QueryExecuted $query): void
    {
        if ((app()->runningInConsole() && !config('audit.capture_in_console')) || !app()->bound('request')) {
            return;
        }

        $request = request();
        if (AuditScope::excludes($request) || AuditScope::isDtr($request)) {
            return;
        }

        if (!preg_match('/^\s*(insert(?:\s+ignore)?\s+into|replace\s+into|update|delete\s+from)\s+([`"\w.]+)/i', $query->sql, $matches)) {
            return;
        }

        $operation = str_starts_with(strtolower($matches[1]), 'update') ? 'update'
            : (str_starts_with(strtolower($matches[1]), 'delete') ? 'delete' : 'insert');
        $tableParts = explode('.', str_replace(['`', '"'], '', $matches[2]));
        $table = strtolower((string) end($tableParts));
        if ($table === 'audit_logs' || in_array($table, ['dtrs', 'dtrs_test', 'f_devices', 'logzones', 'official_times', 'official_time_schedules'], true)) {
            return;
        }

        $writes = $request->attributes->get('audit_sql_writes', []);
        $key = $table.'|'.$operation;
        $writes[$key] = ($writes[$key] ?? 0) + 1;
        $request->attributes->set('audit_sql_writes', $writes);
    }

    public function unmatched(Request $request): array
    {
        $writes = $request->attributes->get('audit_sql_writes', []);
        $models = $request->attributes->get('audit_model_writes', []);
        $snapshots = $request->attributes->get('audit_snapshot_writes', []);
        $remaining = [];
        foreach ($writes as $key => $count) {
            $other = $count - ($models[$key] ?? 0) - ($snapshots[$key] ?? 0);
            if ($other > 0) {
                [$table, $operation] = explode('|', $key, 2);
                $remaining[] = ['table' => $table, 'operation' => $operation, 'statement_count' => $other];
            }
        }
        return $remaining;
    }
}
