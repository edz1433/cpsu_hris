<?php

namespace App\Services\Audit;

use App\Models\Dtr;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditDtrSyncRecorder
{
    // Shown with upload_counts in the Details panel; not stored per row (it is the same for every row).
    public const COUNT_BASIS = 'Audit parsing: valid rows contain at least one valid punch; valid punches have an employee code, calendar date and HH:MM:SS time. Submitted punches count CSV slots in nonempty scalar time fields. Skipped means not represented by punch audit entries, not rejected by sync. Completion counts refer to unique punches observed before/after this upload.';

    // Audit only; the controller's insertion, merge and deduplication stay unchanged.
    public function punches(array $data, ?array &$counts = null): array
    {
        // These are audit-parser counts, not controller validation or rejection counts.
        // Submitted punches count CSV slots (including empty slots) in nonempty scalar time fields.
        $counts = ['submitted_rows' => count($data), 'audit_valid_rows' => 0,
            'audit_skipped_rows' => 0, 'submitted_punches' => 0, 'audit_valid_punches' => 0];
        $punches = [];
        foreach ($data as $row) {
            if (!is_array($row)) continue;
            foreach (['time_in', 'time_out', 'time_over'] as $field) {
                if (is_scalar($row[$field] ?? null) && (string) $row[$field] !== '') {
                    $counts['submitted_punches'] += count(explode(',', (string) $row[$field]));
                }
            }
            $code = $row['emp_ID'] ?? null;
            $date = $row['date'] ?? null;
            if (!is_scalar($code) || (string) $code === '' || strlen((string) $code) > 100
                || !is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
                || !checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))) continue;
            $validRow = false;
            foreach (['in' => 'time_in', 'out' => 'time_out', 'over' => 'overtime'] as $suffix => $action) {
                $times = $row['time_'.$suffix] ?? null;
                $devices = $row['device_id_'.$suffix] ?? null;
                if (!is_scalar($times) || (string) $times === '') continue;
                $devices = is_scalar($devices) ? explode(',', (string) $devices) : [];
                foreach (explode(',', (string) $times) as $index => $time) {
                    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $time)) continue;
                    $validRow = true;
                    $counts['audit_valid_punches']++;
                    $key = json_encode([mb_strtolower((string) $code), $date, $action, $time]);
                    // Keep one event per punch, but preserve every submitted kiosk source.
                    $device = $devices[$index] ?? null;
                    $device = is_string($device) && preg_match('/^-?\d{1,10}$/', $device) ? $device : null;
                    if (isset($punches[$key])) {
                        if ($device !== null && !in_array($device, $punches[$key]['device_ids'], true)) {
                            $punches[$key]['device_ids'][] = $device;
                        }
                        continue;
                    }
                    $punches[$key] = [
                        'code' => (string) $code, 'date' => $date, 'action' => $action,
                        'field' => 'time_'.$suffix, 'device_field' => 'device_id_'.$suffix,
                        'time' => $time, 'device_id' => $device, 'device_ids' => $device === null ? [] : [$device],
                    ];
                }
            }
            if ($validRow) $counts['audit_valid_rows']++;
        }
        $counts['audit_skipped_rows'] = $counts['submitted_rows'] - $counts['audit_valid_rows'];
        $counts['audit_skipped_punches'] = $counts['submitted_punches'] - $counts['audit_valid_punches'];
        $counts['unique_punches'] = count($punches);
        $counts['duplicate_submitted_punches'] = $counts['audit_valid_punches'] - count($punches);
        return $punches;
    }

    public function snapshot(array $punches): array
    {
        if (!$punches) return [];
        $rows = Dtr::query()->whereIn('emp_ID', array_unique(array_column($punches, 'code')))
            ->whereIn('date', array_unique(array_column($punches, 'date')))
            ->get(['id', 'emp_ID', 'date', 'time_in', 'time_out', 'time_over',
                'device_id_in', 'device_id_out', 'device_id_over']);
        $stored = [];
        foreach ($rows as $row) {
            foreach (['in' => 'time_in', 'out' => 'time_out', 'over' => 'overtime'] as $suffix => $action) {
                $devices = explode(',', (string) $row->{'device_id_'.$suffix});
                foreach (explode(',', (string) $row->{'time_'.$suffix}) as $index => $time) {
                    $key = json_encode([mb_strtolower((string) $row->emp_ID), (string) $row->date, $action, $time]);
                    $stored[$key] ??= ['dtr_id' => $row->id, 'device_id' => $devices[$index] ?? null];
                }
            }
        }
        $snapshot = [];
        foreach ($punches as $key => $punch) {
            $lookup = json_encode([mb_strtolower($punch['code']), $punch['date'], $punch['action'], $punch['time']]);
            if (isset($stored[$lookup])) $snapshot[$key] = $stored[$lookup];
        }
        return $snapshot;
    }

    public function receipt(Request $request, array $data): void
    {
        $request->attributes->set('audit_sync_received_at', now()->format('Y-m-d H:i:s.u'));
        $this->punches($data, $counts);
        $request->attributes->set('audit_sync_counts', $counts);
        if (!app(AuditWriter::class)->record([
            'module' => 'dtr', 'action' => 'dtr_sync', 'outcome' => 'received',
            'target_type' => 'dtr_action', 'target_name' => 'Desktop DTR sync',
            'redact_user_agent' => true,
            'details' => $this->evidence($request),
        ])) {
            throw new \RuntimeException('DTR sync receipt could not be stored.');
        }
    }

    public function committed(Request $request, array $punches, array $before, int $submittedRows): void
    {
        $after = $this->snapshot($punches);
        $counts = $this->evidence($request)['upload_counts'];
        $counts['newly_saved_punches'] = count(array_diff_key($after, $before));
        $counts['already_stored_punches'] = count(array_intersect_key($after, $before));
        $counts['not_saved_punches'] = count(array_diff_key($punches, $after));
        $request->attributes->set('audit_sync_counts', $counts);
        foreach ($punches as $key => $punch) {
            $outcome = !isset($after[$key]) ? 'rejected' : (isset($before[$key]) ? 'unchanged' : 'success');
            $reason = match ($outcome) {
                'unchanged' => 'already_stored', 'rejected' => 'punch_not_saved', default => null,
            };
            if (!$this->write($request, $punch, $outcome, $reason, $after[$key] ?? [])) {
                throw new \RuntimeException('DTR sync audit could not be stored.');
            }
        }
        if (!$punches && !app(AuditWriter::class)->record([
            'module' => 'dtr', 'action' => 'dtr_sync', 'outcome' => 'unchanged',
            'reason_code' => 'no_valid_punches_supplied', 'target_type' => 'dtr_action',
            'target_name' => 'Desktop DTR sync', 'redact_user_agent' => true,
            'details' => $this->evidence($request),
        ])) {
            throw new \RuntimeException('DTR sync audit could not be stored.');
        }
    }

    public function failed(Request $request, ?Response $response, ?Throwable $exception): bool
    {
        $data = json_decode($request->getContent(), true);
        $punches = $this->punches(is_array($data) ? $data : [], $counts);
        // Reset completion counts after rollback; non-array payload counts are unknown.
        $request->attributes->set('audit_sync_counts', is_array($data) ? $counts : null);
        $status = $response?->getStatusCode() ?? ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
            ? $exception->getStatusCode() : ($exception instanceof \Illuminate\Validation\ValidationException ? 422 : 500));
        $outcome = $status >= 500 ? 'error' : 'rejected';
        $reason = match ($status) {
            401 => 'unauthenticated', 403 => 'access_denied', 404 => 'not_found',
            405 => 'method_not_allowed', 408 => 'request_timeout', 413 => 'request_too_large',
            422 => 'validation_failed', 429 => 'rate_limited', 503 => 'service_unavailable',
            default => $outcome === 'error' ? 'sync_failed' : 'invalid_sync_payload',
        };
        $failureDetails = array_filter(['http_status' => $status,
            'exception_type' => $exception ? class_basename($exception) : null], fn ($value) => $value !== null);
        if (!$punches) {
            return app(AuditWriter::class)->record([
                'module' => 'dtr', 'action' => 'dtr_sync', 'outcome' => $outcome,
                'reason_code' => $reason, 'target_type' => 'dtr_action',
                'target_name' => 'Desktop DTR sync', 'redact_user_agent' => true,
                'details' => $this->evidence($request) + $failureDetails,
            ]);
        }
        $stored = true;
        foreach ($punches as $punch) {
            $stored = $this->write($request, $punch, $outcome, $reason, [], $failureDetails) && $stored;
        }
        return $stored;
    }

    private function evidence(Request $request): array
    {
        if (!$request->attributes->has('audit_sync_counts')) {
            $data = json_decode($request->getContent(), true);
            $this->punches(is_array($data) ? $data : [], $counts);
            $request->attributes->set('audit_sync_counts', is_array($data) ? $counts : null);
        }
        $counts = $request->attributes->get('audit_sync_counts');
        return [
            'source' => 'desktop_sync', 'endpoint' => 'api/dtrs',
            'server_received_at' => $request->attributes->get('audit_sync_received_at'),
            'submitted_rows' => $counts['submitted_rows'] ?? null, 'upload_counts' => $counts,
        ];
    }

    private function write(Request $request, array $punch, string $outcome, ?string $reason, array $saved = [], array $result = []): bool
    {
        // Metadata lookup must not select biometric columns or confuse emp_ID with the row id.
        $employees = $request->attributes->get('audit_sync_employees');
        if ($employees === null) {
            $data = json_decode($request->getContent(), true);
            $codes = array_unique(array_column($this->punches(is_array($data) ? $data : []), 'code'));
            try {
                $employees = Employee::whereIn('emp_ID', $codes)
                    ->get(['id', 'emp_ID', 'fname', 'mname', 'lname', 'camp_id', 'emp_dept'])
                    ->keyBy(fn ($employee) => mb_strtolower((string) $employee->emp_ID));
            } catch (Throwable $e) {
                $employees = collect();
            }
            $request->attributes->set('audit_sync_employees', $employees);
        }
        $employee = $employees->get(mb_strtolower($punch['code']));
        $name = $employee ? trim(implode(' ', array_filter([$employee->fname, $employee->mname, $employee->lname]))) : null;
        $evidence = $this->evidence($request);
        $attendanceAt = $punch['date'].' '.$punch['time'];
        $received = isset($evidence['server_received_at']) ? Carbon::parse($evidence['server_received_at']) : null;
        $lag = $received ? $received->getTimestamp() - Carbon::parse($attendanceAt, $received->getTimezone())->getTimestamp() : null;
        $futureThreshold = max(0, (int) config('audit.desktop_sync.future_tolerance_seconds', 300));
        $lateThreshold = max(0, (int) config('audit.desktop_sync.late_upload_seconds', 86400));
        $flags = [];
        if ($lag !== null && $lag < -$futureThreshold) $flags[] = 'future_dated';
        if ($lag !== null && $lag > $lateThreshold) $flags[] = 'late_upload';
        return app(AuditWriter::class)->record([
            'module' => 'dtr', 'action' => $punch['action'], 'outcome' => $outcome,
            'reason_code' => $reason, 'employee' => $employee, 'employee_code' => $punch['code'],
            'target_type' => 'employee', 'target_id' => $employee->id ?? null,
            'target_name' => $name ?: 'Employee '.$punch['code'], 'redact_user_agent' => true,
            'details' => $evidence + $result + array_filter([
                'attendance_at' => $attendanceAt, 'upload_lag_seconds' => $lag,
                'timing_flags' => $flags, 'timing_flags_informational' => true,
                'future_tolerance_seconds' => $futureThreshold, 'late_upload_seconds' => $lateThreshold,
                'attendance_action' => $punch['action'], 'submitted_device_id' => $punch['device_id'],
                'submitted_device_ids' => $punch['device_ids'],
                'device_id' => isset($saved['device_id']) && preg_match('/^-?\d{1,10}$/', (string) $saved['device_id'])
                    ? (string) $saved['device_id'] : null,
                'dtr_id' => $saved['dtr_id'] ?? null, 'reason_code' => $reason,
            ], fn ($value) => $value !== null),
        ]);
    }
}
