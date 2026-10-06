<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Used for both creating a period and editing its dates (type is fixed after creation). */
class StoreContractPeriodRequest extends FormRequest
{
    public function authorize()
    {
        // Role check is done by the controller middleware.
        return true;
    }

    public function rules()
    {
        $enabledTypes = collect(config('contracts.types'))->filter(fn ($type) => !empty($type['enabled']))->keys()->all();
        $isUpdate = (bool) $this->route('period');

        return [
            'contract_type' => [$isUpdate ? 'nullable' : 'required', Rule::in($enabledTypes)],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'title'         => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'contract_type.in'         => 'That contract type is not available yet.',
            'end_date.after_or_equal'  => 'The end date must be on or after the start date.',
        ];
    }
}
