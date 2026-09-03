<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FdtController
{
    private array $columnCache = [];

    public function sync(Request $request)
    {
        $token = (string) config('services.fdt_sync.token');

        if ($token === '' || ! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $updatedSince = $request->query('updated_since');
        $page = max((int) $request->query('page', 1), 1);
        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);
        $after = max((int) $request->query('after', 0), 0);

        $officeColumns = $this->columnsFor('dbcpsupms', 'offices');
        $employeeColumns = $this->columnsFor('dbcpsuhris', 'employees');
        $campusColumns = $this->columnsFor('dbcpsuhris', 'campuses');
        $settingsColumns = $this->columnsFor('dbcpsuhris', 'settings');

        $withSharedData = $page === 1;

        $signature = ($withSharedData
                ? $this->tableSignature('dbcpsupms.offices', $officeColumns, $updatedSince)
                    .'|'.$this->campusesSignature($campusColumns)
                    .'|'.$this->dtrAcctSignature($settingsColumns)
                : '')
            .'|'.$this->tableSignature('dbcpsuhris.employees', $employeeColumns, $updatedSince);

        $etag = '"'.md5($signature.'|'.$page.'|'.$perPage.'|'.$after.'|'.(string) $updatedSince).'"';

        if (hash_equals($etag, trim((string) $request->header('If-None-Match')))) {
            return response()->noContent(304)->header('ETag', $etag);
        }

        $officeSelect = [
            'id',
            'office_name',
            'office_abbr',
            $this->selectColumnOrNull($officeColumns, 'office_head_id'),
            $this->selectColumnOrNull($officeColumns, 'oic_id'),
            $this->selectColumnOrNull($officeColumns, 'created_at'),
            $this->selectColumnOrNull($officeColumns, 'updated_at'),
        ];

        $employeeSelect = collect([
            'id', 'fname', 'mname', 'lname', 'emp_ID', 'camp_id', 'emp_status',
            'emp_dept', 'supervisor', 'org_email', 'stat_1', 'esign',
            'created_at', 'updated_at',
        ])->filter(fn ($column) => in_array($column, $employeeColumns, true))->values()->all();

        $offices = $withSharedData
            ? DB::table('dbcpsupms.offices')
                ->select($officeSelect)
                ->when($updatedSince && in_array('updated_at', $officeColumns, true), function ($query) use ($updatedSince) {
                    $query->where('updated_at', '>=', $updatedSince);
                })
                ->orderBy('id')
                ->get()
            : collect();

        $campuses = $withSharedData && $this->hasColumns($campusColumns, ['id', 'campus_name', 'campus_abbr'])
            ? DB::table('dbcpsuhris.campuses')
                ->select(['id', 'campus_name', 'campus_abbr'])
                ->orderBy('id')
                ->get()
            : collect();

        $settings = $withSharedData && in_array('dtr_acct', $settingsColumns, true)
            ? ['dtr_acct' => $this->dtrAcctValue($settingsColumns)]
            : (object) [];

        $employeeQuery = DB::table('dbcpsuhris.employees')
            ->when($updatedSince && in_array('updated_at', $employeeColumns, true), function ($query) use ($updatedSince) {
                $query->where('updated_at', '>=', $updatedSince);
            });

        $employees = (clone $employeeQuery)
            ->select($employeeSelect)
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit($perPage)
            ->get();

        $hasMoreEmployees = $employees->isNotEmpty() && (clone $employeeQuery)
            ->where('id', '>', (int) $employees->last()->id)
            ->exists();

        return response()
            ->json([
                'offices' => $offices,
                'campuses' => $campuses,
                'settings' => $settings,
                'employees' => $employees,
                'next_page_url' => $hasMoreEmployees
                    ? $request->fullUrlWithQuery([
                        'page' => $page + 1,
                        'per_page' => $perPage,
                        'after' => (int) $employees->last()->id,
                    ])
                    : null,
            ])
            ->header('ETag', $etag);
    }

    private function tableSignature(string $table, array $columns, ?string $updatedSince): string
    {
        $hasUpdatedAt = in_array('updated_at', $columns, true);

        $row = DB::table($table)
            ->when($updatedSince && $hasUpdatedAt, function ($query) use ($updatedSince) {
                $query->where('updated_at', '>=', $updatedSince);
            })
            ->selectRaw($hasUpdatedAt
                ? 'COUNT(*) as row_count, MAX(updated_at) as max_updated_at'
                : 'COUNT(*) as row_count, NULL as max_updated_at')
            ->first();

        return $table.':'.($row->row_count ?? 0).':'.($row->max_updated_at ?? '');
    }

    private function campusesSignature(array $columns): string
    {
        if (! $this->hasColumns($columns, ['id', 'campus_name', 'campus_abbr'])) {
            return 'dbcpsuhris.campuses:missing';
        }

        $rows = DB::table('dbcpsuhris.campuses')
            ->select(['id', 'campus_name', 'campus_abbr'])
            ->orderBy('id')
            ->get();

        return 'dbcpsuhris.campuses:'.md5($rows->toJson());
    }

    private function dtrAcctSignature(array $columns): string
    {
        if (! in_array('dtr_acct', $columns, true)) {
            return 'dbcpsuhris.settings.dtr_acct:missing';
        }

        return 'dbcpsuhris.settings.dtr_acct:'.md5($this->dtrAcctValue($columns));
    }

    private function dtrAcctValue(array $columns): string
    {
        if (! in_array('dtr_acct', $columns, true)) {
            return '';
        }

        return (string) (DB::table('dbcpsuhris.settings')
            ->orderBy('id')
            ->value('dtr_acct') ?? '');
    }

    private function columnsFor(string $database, string $table): array
    {
        $key = $database.'.'.$table;

        return $this->columnCache[$key] ??= collect(DB::select("SHOW COLUMNS FROM `{$database}`.`{$table}`"))
            ->pluck('Field')
            ->all();
    }

    private function selectColumnOrNull(array $columns, string $column)
    {
        return in_array($column, $columns, true) ? $column : DB::raw('NULL as '.$column);
    }

    private function hasColumns(array $columns, array $required): bool
    {
        return count(array_intersect($required, $columns)) === count($required);
    }
}