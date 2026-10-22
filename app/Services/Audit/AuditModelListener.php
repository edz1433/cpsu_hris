<?php

namespace App\Services\Audit;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AuditModelListener
{
    private const SKIP_MODELS = [
        'Dtr', 'DtrTest', 'Tardiness', 'OfficialTime', 'OfficialTimeSchedule',
        'Device', 'Fdevice', 'Logzone', 'History', 'SyncFeed',
    ];

    private const SKIP_FIELDS = [
        'created_at', 'updated_at', 'deleted_at', 'verification_code',
    ];

    public function record(string $action, Model $model): void
    {
        if ((app()->runningInConsole() && !config('audit.capture_in_console')) || !app()->bound('request') || AuditScope::excludes(request()) || AuditScope::isDtr(request())) {
            return;
        }

        $type = class_basename($model);
        if (!str_starts_with(get_class($model), 'App\\Models\\') || in_array($type, self::SKIP_MODELS, true)) {
            return;
        }

        $attributes = $action === 'updated' ? $model->getChanges() : $model->getAttributes();
        $fields = array_values(array_diff(array_keys($attributes), self::SKIP_FIELDS));
        if ($action === 'updated' && !$fields) {
            return;
        }

        $safeFields = config('audit.safe_fields', []);
        $visible = array_values(array_intersect($fields, $safeFields));
        $oldData = [];
        $newData = [];
        foreach ($visible as $field) {
            if ($action !== 'created') {
                $oldData[$field] = $model->getRawOriginal($field);
            }
            if ($action !== 'deleted') {
                $newData[$field] = $model->getAttribute($field);
            }
        }

        $employee = $this->employeeFor($model);
        $targetName = $this->targetName($model, $type);
        $event = [
            'module' => app(AuditModuleCatalog::class)->module(request()),
            'action' => $action,
            'target_type' => Str::snake($type),
            'target_id' => $model->getKey(),
            'target_name' => $targetName,
            'employee' => $employee,
            'campus_id' => in_array($type, ['Campus', 'Camp'], true) ? $model->getKey() : ($model->getAttribute('campus_id') ?? $model->getAttribute('camp_id')),
            'office_id' => $type === 'Office' ? $model->getKey() : ($model->getAttribute('office_id') ?? $model->getAttribute('emp_dept') ?? $model->getAttribute('department')),
            'details' => [
                'entity_table' => $model->getTable(),
                'old_data' => $oldData,
                'new_data' => $newData,
                'redacted_field_count' => count($fields) - count($visible),
            ],
        ];

        $write = function () use ($event, $action, $model) {
            if (app(AuditWriter::class)->record($event)) {
                $request = request();
                $request->attributes->set('audit_model_events', 1 + (int) $request->attributes->get('audit_model_events', 0));
                $operation = $action === 'created' ? 'insert' : ($action === 'deleted' && !in_array(SoftDeletes::class, class_uses_recursive($model), true) ? 'delete' : 'update');
                $key = strtolower($model->getTable()).'|'.$operation;
                $modelWrites = $request->attributes->get('audit_model_writes', []);
                $modelWrites[$key] = ($modelWrites[$key] ?? 0) + 1;
                $request->attributes->set('audit_model_writes', $modelWrites);
            }
        };

        if ($model->getConnection()->transactionLevel() > 0) {
            $model->getConnection()->afterCommit($write);
        } else {
            $write();
        }
    }

    private function employeeFor(Model $model): ?Employee
    {
        if ($model instanceof Employee) {
            return $model;
        }

        $employeeId = $model->getAttribute('employee_id');
        if ($employeeId && is_numeric($employeeId)) {
            return Employee::query()->find((int) $employeeId);
        }

        $employeeCode = $model->getAttribute('emp_ID') ?? $model->getAttribute('empid');
        if ($employeeCode !== null && $employeeCode !== '') {
            $employee = Employee::query()->where('emp_ID', (string) $employeeCode)->first();
            if (!$employee && ctype_digit((string) $employeeCode)) {
                $employee = Employee::query()->find((int) $employeeCode);
            }
            return $employee;
        }

        return null;
    }

    private function targetName(Model $model, string $type): string
    {
        $name = trim(implode(' ', array_filter([
            $model->getAttribute('fname'),
            $model->getAttribute('mname'),
            $model->getAttribute('lname'),
        ])));
        if ($name !== '') {
            return $name;
        }

        foreach (['employee_name', 'office_name', 'campus_name', 'name', 'title', 'reference_no'] as $field) {
            $value = $model->getAttribute($field);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return Str::headline($type).' #'.$model->getKey();
    }
}
