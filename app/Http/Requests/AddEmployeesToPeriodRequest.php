<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Foundation\Http\FormRequest;

class AddEmployeesToPeriodRequest extends FormRequest
{
    public function authorize()
    {
        // Role check is done by the controller middleware.
        return true;
    }

    public function rules()
    {
        return [
            'employee_ids'    => ['required', 'array', 'min:1'],
            'employee_ids.*'  => ['integer', 'distinct'],
            'position'        => ['required', 'string', 'max:255'],
            'monthly_rate'    => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999'],
            'daily_deduction' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999'],
            'confirm_overlap' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'employee_ids.required' => 'Select at least one employee.',
            'employee_ids.min'      => 'Select at least one employee.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $period = $this->route('period');
            $ids = array_map('intval', $this->input('employee_ids', []));
            $statuses = $period->typeConfig()['employee_statuses'] ?? [];

            $eligible = Employee::query()
                ->whereIn('id', $ids)
                ->where('stat_1', 1)
                ->whereIn('emp_status', $statuses)
                ->pluck('id')
                ->all();
            if (count(array_diff($ids, $eligible)) > 0) {
                $validator->errors()->add('employee_ids', 'Only active ' . $period->typeLabel() . ' employees can be added.');
                return;
            }

            $duplicates = EmployeeContract::where('contract_period_id', $period->id)
                ->whereIn('employee_id', $ids)
                ->pluck('employee_name');
            if ($duplicates->isNotEmpty()) {
                $validator->errors()->add('employee_ids', 'Already in this period: ' . $duplicates->implode(', ') . '.');
            }
        });
    }
}
