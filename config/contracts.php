<?php

/*
|--------------------------------------------------------------------------
| Contract of Services
|--------------------------------------------------------------------------
|
| Each contract type is one entry below plus one .docx template in
| resources/contract-templates. To enable a "Coming soon" type: add its
| template, fill in `template`, `reference_prefix` and `employee_statuses`,
| then set `enabled` to true.
|
| Template placeholders (each must sit in a single run):
|   ${EMPLOYEE_NAME} ${ARTICLE} ${POSITION} ${MONTHLY_RATE} ${DAILY_DEDUCTION}
|   ${START_DATE} ${END_DATE} ${YEAR} ${REFERENCE_NO}
|
*/

return [

    'types' => [
        'job_order' => [
            'label'             => 'Job Order',
            'enabled'           => true,
            'template'          => 'job_order.docx',
            'reference_prefix'  => 'COSJO',
            // employees.emp_status values that may receive this contract
            'employee_statuses' => ['4'],
        ],
        'cos_full_time' => [
            'label'             => 'COS Full Time',
            'enabled'           => false,
            'template'          => null,
            'reference_prefix'  => null,
            'employee_statuses' => [],
        ],
        'cos_part_time' => [
            'label'             => 'COS Part Time',
            'enabled'           => false,
            'template'          => null,
            'reference_prefix'  => null,
            'employee_statuses' => [],
        ],
        'project_based' => [
            'label'             => 'Project Based',
            'enabled'           => false,
            'template'          => null,
            'reference_prefix'  => null,
            'employee_statuses' => [],
        ],
        'support_services' => [
            'label'             => 'Support Services',
            'enabled'           => false,
            'template'          => null,
            'reference_prefix'  => null,
            'employee_statuses' => [],
        ],
        'physician' => [
            'label'             => 'Physician',
            'enabled'           => false,
            'template'          => null,
            'reference_prefix'  => null,
            'employee_statuses' => [],
        ],
    ],

    // Daily deduction suggested in the UI = monthly rate / this value.
    'working_days_per_month' => 22,

    // Campus code used in reference numbers when campuses.short is empty,
    // keyed by campus id, e.g. [3 => 'CAU']. Campuses with neither fall back
    // to their campus_abbr in uppercase letters only (e.g. "CAUAYAN").
    'campus_codes' => [],

];
