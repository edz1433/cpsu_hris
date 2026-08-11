<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FdtController
{
    /** Column lists memoised per request; the table shape cannot move inside one. */
    private array $columnCache = [];

    public function sync(Request $request)
    {
        $token = (string) config('services.fdt_sync.token');

        // Fail closed on an unset token. Comparing a null config against a null
        // bearer token passes, and this route has no auth middleware in front
        // of it, so that served the whole directory — password hashes included
        // — to anyone.
        if ($token === '' || ! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $updatedSince = $request->query('updated_since');
        $page = max((int) $request->query('page', 1), 1);
        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);
        // Keyset cursor: the highest employee id the client has already applied.
        $after = max((int) $request->query('after', 0), 0);

        $officeColumns = $this->columnsFor('dbcpsupms', 'offices');
        $employeeColumns = $this->columnsFor('dbcpsuhris', 'employees');

        // Offices ride along with the first request of a walk only.
        $withOffices = $page === 1;

        // Identity probe: COUNT(*) + MAX(updated_at) over the filtered set,
        // never a hash of the payload — the whole point is to answer an
        // unchanged set without loading the esign blobs (381 of 1371 rows,
        // ~194KB average, 7.18MB worst). Keyed on every parameter that changes
        // which rows this request would return, or it would 304 a page the
        // client never fetched. Both tables have a leading BTREE index on
        // updated_at; this probe and the filter below ride on it.
        $signature = ($withOffices ? $this->tableSignature('dbcpsupms.offices', $officeColumns, $updatedSince) : '')
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
            'emp_dept', 'supervisor', 'org_email', 'password', 'stat_1', 'esign',
            'created_at', 'updated_at',
        ])->filter(fn ($column) => in_array($column, $employeeColumns, true))->values()->all();

        $offices = $withOffices
            ? DB::table('dbcpsupms.offices')
                ->select($officeSelect)
                ->when($updatedSince && in_array('updated_at', $officeColumns, true), function ($query) use ($updatedSince) {
                    $query->where('updated_at', '>=', $updatedSince);
                })
                ->orderBy('id')
                ->get()
            : collect();

        $employeeQuery = DB::table('dbcpsuhris.employees')
            ->when($updatedSince && in_array('updated_at', $employeeColumns, true), function ($query) use ($updatedSince) {
                $query->where('updated_at', '>=', $updatedSince);
            });

        // Keyset, not offset. The set is filtered by updated_since, so any row
        // touched mid-walk joins it and shifts every later offset backward —
        // offset paging dropped rows with nothing reporting the gap. The >=
        // comparison above is what lets the next sync re-collect a row skipped
        // that way.
        $employees = (clone $employeeQuery)
            ->select($employeeSelect)
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit($perPage)
            ->get();

        // Probe for a next page on the id index alone. Fetching one extra row
        // instead would pull an esign blob just to throw it away.
        $hasMoreEmployees = $employees->isNotEmpty() && (clone $employeeQuery)
            ->where('id', '>', (int) $employees->last()->id)
            ->exists();

        return response()
            ->json([
                // Deliberately no wall-clock field: a synced_at in the body
                // changes every request and the ETag could never match. The
                // client takes its cursor from the rows' own updated_at.
                'offices' => $offices,
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
}