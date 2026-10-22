<?php

namespace App\Services\Audit;

use App\Models\Dtr;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditDtrRecorder
{
    public function record(Request $request, ?Response $response = null, ?Throwable $exception = null, bool $received = false): bool
    {
        // HEAD 405 is the kiosk's normal reachability probe, not a failed sync.
        if ($request->is('api/dtrs') && $request->isMethod('HEAD') && !$exception
            && in_array($response?->getStatusCode(), [200, 204, 405], true)) return true;
        if ($request->is('api/dtrs') && !$request->isMethod('HEAD')) {
            return app(AuditDtrSyncRecorder::class)->failed($request, $response, $exception);
        }
        $data = $this->responseData($response);
        $action = $this->action($request);
        [$outcome, $reason] = $received ? ['received', null] : $this->result($request, $action, $response, $data, $exception);
        // Keep routine successful reads quiet, but retain every non-success result.
        if (AuditScope::isDtrUtility($request) && $outcome === 'success') return true;
        $employee = $this->employee($request, $data);
        $name = $employee ? trim(implode(' ', array_filter([$employee->fname, $employee->mname, $employee->lname]))) : null;
        $seconds = $data['wait_seconds'] ?? null;

        return app(AuditWriter::class)->record([
            'module' => AuditScope::isTardiness($request) ? 'tardiness' : 'dtr',
            'action' => $action,
            'outcome' => $outcome,
            'reason_code' => $reason,
            'target_type' => $employee ? 'employee' : 'dtr_action',
            'target_id' => $employee->id ?? null,
            'target_name' => $name ?: 'DTR '.Str::headline(preg_replace('/^dtr_/', '', $action)),
            'employee' => $employee,
            'redact_user_agent' => true,
            'details' => array_filter([
                // Source identifies the endpoint family, not a verified client/device.
                'source' => $request->is('api/app/*', 'api/app-dtr/*') ? 'time_entry_api'
                    : ($request->is('api/*') ? 'hris_api' : 'hris_web'),
                // Use the route template; never copy URL parameters or the query string.
                'endpoint' => optional($request->route())->uri(),
                'route' => optional($request->route())->getName(),
                'http_status' => $response?->getStatusCode() ?? ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                    ? $exception->getStatusCode() : ($exception instanceof ValidationException ? 422
                        : ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : ($exception ? 500 : null)))),
                'reason_code' => $reason,
                'attendance_action' => in_array($action, ['time_in', 'time_out', 'overtime'], true) ? $action : null,
                'seconds_remaining' => is_numeric($seconds) && $reason === 'time_in_after_time_out_60_seconds' ? max(0, (int) $seconds) : null,
                'employee_identifier_supplied' => $this->hasEmployeeIdentifier($request),
                'identity_basis' => $employee && ($request->attributes->has('audit_subject_employee_code')
                    || $request->attributes->has('audit_face_claim_employee_code')) && ($data['match'] ?? null) !== true
                    ? $request->attributes->get('audit_subject_identity_basis', 'qr_claim_not_face_verified') : null,
                'admin_authorized' => $request->is('api/app/admin-face-claim', 'api/app/admin-face-verify')
                    && is_bool($data['is_admin'] ?? null) ? $data['is_admin'] : null,
                'zone_id' => is_scalar($request->input('zone_id')) && preg_match('/^-?\d{1,10}$/', (string) $request->input('zone_id'))
                    ? (int) $request->input('zone_id') : null,
                'exception_type' => $exception ? class_basename($exception) : null,
                'dtr_id' => $received ? null : $request->attributes->get('audit_dtr_id'),
                'attendance_at' => $received ? null : $request->attributes->get('audit_attendance_at'),
            ], fn ($value) => $value !== null),
        ]);
    }

    // Capture the actual saved row/time, including a request crossing midnight.
    // The middleware writes the outcome before committing the outer transaction.
    public function captureAttendance(Dtr $record): void
    {
        if (!app()->bound('request') || !request()->attributes->get('audit_attendance_active')) return;
        $field = match ((string) request()->input('action')) {
            '1' => 'time_in', '2' => 'time_out', '3' => 'time_over', default => null,
        };
        if (!$field || (!$record->wasRecentlyCreated && !$record->wasChanged($field))) return;
        $times = explode(',', (string) $record->getAttribute($field));
        request()->attributes->set('audit_dtr_id', $record->getKey());
        request()->attributes->set('audit_attendance_at', $record->date.' '.end($times));
    }

    private function action(Request $request): string
    {
        if ($request->is('api/app/log-attendance', 'api/app/authlogin')) {
            $number = $request->input($request->is('api/app/log-attendance') ? 'action' : 'cat');
            return match ((string) $number) {
                '1' => 'time_in', '2' => 'time_out', '3' => 'overtime',
                default => 'attendance_attempt',
            };
        }

        $route = optional($request->route())->getName();
        if ($route && !str_starts_with($route, 'generated::')) {
            return 'dtr_'.preg_replace('/[^a-z0-9_]+/', '_', strtolower($route));
        }

        // Before routing, the viewer's third segment is an encrypted employee reference.
        if ($request->is('api/app-dtr/*')) {
            return $request->is('api/app-dtr/search') ? 'dtr_app_dtr_search'
                : ($request->is('api/app-dtr/pdf/*') ? 'dtr_app_dtr_pdf' : 'dtr_app_dtr_read');
        }
        $segment = (string) $request->segment($request->is('api/*') ? 3 : 2);
        return 'dtr_'.preg_replace('/[^a-z0-9_]+/', '_', strtolower($segment ?: 'request'));
    }

    private function result(Request $request, string $action, ?Response $response, array $data, ?Throwable $exception): array
    {
        if ($exception) {
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                return [$status >= 500 ? 'error' : 'rejected', $this->reason($status, [])];
            }
            if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return ['rejected', 'not_found'];
            }
            return ['error', $exception instanceof ValidationException ? 'validation_failed' : 'unhandled_exception'];
        }

        $status = $response?->getStatusCode() ?? 500;
        if ($action === 'time_in' && ($data['allowed'] ?? null) === false && is_numeric($data['wait_seconds'] ?? null)) {
            return ['rejected', 'time_in_after_time_out_60_seconds'];
        }
        if (in_array($action, ['time_in', 'time_out', 'overtime'], true)
            && ($status === 202 || (($data['updated'] ?? null) === false && ($data['allowed'] ?? null) === true))) {
            return ['unchanged', 'duplicate_or_unchanged'];
        }
        if ($this->isValidationMessage($data)) {
            return ['rejected', 'validation_failed'];
        }
        if (in_array($action, ['time_in', 'time_out', 'overtime'], true) && !array_key_exists('updated', $data)
            && isset($data['message']) && str_contains(strtolower((string) $data['message']), 'dtr updated')) {
            return ['success', null];
        }
        if ($status >= 500) {
            return ['error', $this->reason($status, $data)];
        }
        if ($status === 429) {
            return ['rejected', 'rate_limited'];
        }

        if ($request->is('api/app/admin-face-claim', 'api/app/admin-face-verify')
            && ($data['match'] ?? null) === true && ($data['is_admin'] ?? null) !== true) {
            return ['rejected', 'access_denied'];
        }
        if ($request->is('api/app/check-restriction-level') && ($data['allowed'] ?? null) === false) {
            return ['rejected', (int) ($data['level'] ?? 0) === 2 ? 'attendance_disabled' : 'outside_attendance_window'];
        }
        if ($request->is('api/app/fetch-employees') && $data === [] && $status < 400) {
            return ['unchanged', 'no_employees_available'];
        }

        $failure = $status >= 400
            || in_array(false, array_intersect_key($data, array_flip(['success', 'valid', 'match', 'ok', 'allowed'])), true)
            || isset($data['error']) || !empty($data['errors']);
        if ($failure) {
            return ['rejected', $this->reason($status, $data)];
        }

        $body = trim((string) ($response?->getContent() ?? ''));
        if ($body === '0') return ['rejected', 'device_already_linked'];
        if ($body === '2') return ['unchanged', 'device_already_linked'];

        return ['success', null];
    }

    private function reason(int $status, array $data): string
    {
        $message = strtolower((string) ($data['error'] ?? $data['message'] ?? ''));
        if (str_contains($message, 'employee not found or inactive')) return 'identity_not_found_or_inactive';
        if (str_contains($message, 'employee not found') || str_contains($message, 'unknown emp_id')) return 'identity_not_found';
        if (str_contains($message, 'password not set')) return 'password_not_configured';
        if (str_contains($message, 'license not configured')) return 'license_not_configured';
        if (str_contains($message, 'invalid mode')) return 'invalid_mode';
        if (str_contains($message, 'invalid enroller')) return 'invalid_enroller';
        if (str_contains($message, 'encrypted parameter') || str_contains($message, 'corrupted employee data')) return 'invalid_employee_reference';
        if (str_contains($message, 'inactive')) return 'employee_inactive';
        if (str_contains($message, 'face did not match')) return 'face_mismatch';
        if (str_contains($message, 'no face registered')) return 'face_not_registered';
        if (str_contains($message, 'embedding')) return 'biometric_validation_failed';
        if (str_contains($message, 'qr')) return 'invalid_qr';
        if (str_contains($message, 'device') || str_contains($message, 'android')) return 'device_rejected';
        if (str_contains($message, 'not authorized')) return 'access_denied';
        if (str_contains($message, 'password')) return 'invalid_credentials';
        if (str_contains($message, 'missing') || str_contains($message, 'invalid')) return 'validation_failed';
        return match ($status) {
            400, 422 => 'validation_failed', 401 => 'unauthenticated', 403 => 'access_denied',
            404 => 'not_found', 413 => 'request_too_large', 429 => 'rate_limited',
            503 => 'service_unavailable',
            default => $status >= 500 ? 'server_error' : 'request_rejected',
        };
    }

    private function isValidationMessage(array $data): bool
    {
        $message = strtolower((string) ($data['message'] ?? ''));
        return str_contains($message, 'the given data was invalid')
            || (str_starts_with($message, 'the ') && (str_contains($message, ' field is required')
                || str_contains($message, ' field must be') || str_contains($message, ' field has invalid')))
            || str_starts_with($message, 'the selected ');
    }

    private function responseData(?Response $response): array
    {
        if ($response && str_contains((string) $response->headers->get('Content-Type'), 'application/json')) {
            $data = method_exists($response, 'getData') ? $response->getData(true) : json_decode($response->getContent(), true);
            return is_array($data) ? $data : [];
        }
        // Classify plain-text errors without persisting their possibly sensitive body.
        if ($response && $response->getStatusCode() >= 400
            && str_contains((string) $response->headers->get('Content-Type'), 'text/plain')) {
            return ['message' => trim((string) $response->getContent())];
        }
        return [];
    }

    private function hasEmployeeIdentifier(Request $request): bool
    {
        if ($request->attributes->has('audit_subject_employee_code') || $request->attributes->has('audit_face_claim_employee_code')) return true;
        foreach (['emp_id', 'empid', 'empId', 'emp_ID', 'employeeId', 'android'] as $key) {
            if ($request->filled($key) || optional($request->route())->parameter($key)) return true;
        }
        return false;
    }

    public function attendanceEmployee(Request $request): ?Employee
    {
        if (!$request->attributes->has('audit_attendance_employee')) {
            $value = $request->input('emp_id');
            $employee = is_scalar($value) && trim((string) $value) !== ''
                ? Employee::where('emp_ID', (string) $value)->first([
                    'id', 'emp_ID', 'fname', 'mname', 'lname', 'camp_id', 'emp_dept',
                ]) : null;
            $request->attributes->set('audit_attendance_employee', $employee);
        }

        return $request->attributes->get('audit_attendance_employee');
    }

    private function employee(Request $request, array $data = []): ?Employee
    {
        try {
            if ($request->is('api/app/log-attendance')) {
                return $this->attendanceEmployee($request);
            }
            if (isset($data['emp_id']) && is_scalar($data['emp_id']) && strlen((string) $data['emp_id']) <= 100) {
                $resolved = Employee::query()->where('emp_ID', (string) $data['emp_id'])->first([
                    'id', 'emp_ID', 'fname', 'mname', 'lname', 'camp_id', 'emp_dept',
                ]);
                if ($resolved) return $resolved;
            }
            // Use only a subject resolved by the controller from a QR or encrypted reference.
            // A caller-supplied employee id cannot attribute QR/face checks or encrypted views.
            $subjectCode = $request->attributes->get('audit_subject_employee_code',
                $request->attributes->get('audit_face_claim_employee_code'));
            if ($request->is('api/app/validate-qr', 'api/app/face-claim', 'api/app/admin-face-claim',
                'api/app/download-dtr', 'api/app-dtr/*') && is_string($subjectCode) && strlen($subjectCode) <= 100) {
                return Employee::where('emp_ID', $subjectCode)->first([
                    'id', 'emp_ID', 'fname', 'mname', 'lname', 'camp_id', 'emp_dept',
                ]);
            }
            // Other QR/face identity is authoritative only when returned by the controller.
            if ($request->is('api/app/validate-qr', 'api/app/face-claim', 'api/app/admin-face-claim',
                'api/app/face-verify', 'api/app/admin-face-verify', 'api/app/download-dtr')) return null;
            if ($request->is('api/app-dtr/*') && !$request->is('api/app-dtr/search', 'api/app-dtr/pdf/*')) return null;
            foreach (['emp_id', 'empid', 'empId', 'emp_ID', 'employeeId'] as $key) {
                $value = optional($request->route())->parameter($key) ?? $request->input($key);
                if (!is_scalar($value) || strlen((string) $value) > 100 || trim((string) $value) === '') continue;
                $employee = Employee::query()->where('emp_ID', (string) $value)->first();
                if (!$employee && ctype_digit((string) $value)
                    && !$request->is('api/app/log-attendance', 'api/app/face-register')) {
                    $employee = Employee::query()->find((int) $value);
                }
                if ($employee) return $employee;
            }
            if ($request->is('api/app/authlogin')) {
                $android = $request->input('android');
                if (is_string($android) && $android !== '' && strlen($android) <= 255) {
                    return Employee::query()->where('android_id', $android)->first();
                }
            }
        } catch (Throwable $e) {
            // Audit lookup must not alter the DTR response.
        }
        return null;
    }
}
