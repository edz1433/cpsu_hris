<?php

namespace App\Services\Audit;

use App\Models\Employee;
use Illuminate\Http\Request;

class AuditAffectedPerson
{
    public function resolve(Request $request, string $module): ?Employee
    {
        $keys = match ($module) {
            'employees' => ['employee_id', 'empid', 'id'],
            'pds' => ['employee_id', 'empid', 'eid'],
            'leave', 'leave_credits' => ['employee_id', 'empid'],
            'spms' => ['employee_id', 'empid', 'userid'],
            'contracts' => ['employee_id'],
            default => [],
        };

        foreach ($keys as $key) {
            $value = optional($request->route())->parameter($key) ?? $request->input($key);
            if (!is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            $value = trim((string) $value);
            if (strlen($value) > 100) {
                continue;
            }
            $employee = Employee::query()->where('emp_ID', $value)->first();
            if (!$employee && ctype_digit($value)) {
                $employee = Employee::query()->find((int) $value);
            }
            if ($employee) {
                return $employee;
            }
        }

        return null;
    }
}
