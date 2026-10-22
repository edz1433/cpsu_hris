<?php

return [
    'capture_in_console' => false,

    // Audit flags only; offline uploads remain accepted by the existing sync.
    'desktop_sync' => [
        'future_tolerance_seconds' => (int) env('AUDIT_DTR_FUTURE_TOLERANCE_SECONDS', 300),
        'late_upload_seconds' => (int) env('AUDIT_DTR_LATE_UPLOAD_SECONDS', 86400),
    ],

    // Edit these sentences without changing stored audit rows.
    'messages' => [
        'created' => '{actor} created {target} at {time}.',
        'updated' => '{actor} updated {target} at {time}.',
        'deleted' => '{actor} deleted {target} at {time}.',
        'login' => '{actor} signed in at {time}.',
        'logout' => '{actor} signed out at {time}.',
        'failed_login' => '{target} could not sign in at {time}: {reason}.',
        'request' => '{actor} submitted {target} at {time}.',
        'request_failed' => '{actor} could not complete {target} at {time}: {reason}.',
        'write' => '{actor} ran {operation} in {module} at {time}.',
        'accessed' => '{actor} accessed {target} at {time}.',
        'error' => '{actor} encountered an error while using {target} at {time}: {reason}.',
        'president_assignment_corrected' => '{actor} restored the SUC President assignment for {target} at {time}.',
        'time_in_received' => 'The server received a Time In request for {target} at {time}; completion is recorded separately.',
        'time_out_received' => 'The server received a Time Out request for {target} at {time}; completion is recorded separately.',
        'overtime_received' => 'The server received an Overtime request for {target} at {time}; completion is recorded separately.',
        'dtr_action_received' => 'The server received {target} at {time}; completion is recorded separately.',
        'time_in_success' => '{target} completed Time In at {attendance_time}.',
        'time_in_rejected' => '{target} was rejected for Time In at {time}: {reason}.',
        'time_in_unchanged' => '{target} had no new Time In recorded at {time}: {reason}.',
        'time_in_error' => '{target} encountered a Time In error at {time}: {reason}.',
        'time_out_success' => '{target} completed Time Out at {attendance_time}.',
        'time_out_rejected' => '{target} was rejected for Time Out at {time}: {reason}.',
        'time_out_unchanged' => '{target} had no new Time Out recorded at {time}: {reason}.',
        'time_out_error' => '{target} encountered a Time Out error at {time}: {reason}.',
        'overtime_success' => '{target} recorded Overtime at {attendance_time}.',
        'overtime_rejected' => '{target} was rejected for Overtime at {time}: {reason}.',
        'overtime_unchanged' => '{target} had no new Overtime recorded at {time}: {reason}.',
        'overtime_error' => '{target} encountered an Overtime error at {time}: {reason}.',
        'desktop_punch_success' => '{target}: kiosk-reported {attendance_action} at {attendance_time} was saved by the server at {time}.',
        'desktop_punch_unchanged' => '{target}: kiosk-reported {attendance_action} at {attendance_time} was already stored; upload received at {time}.',
        'desktop_punch_rejected' => '{target}: kiosk-reported {attendance_action} at {attendance_time} was not saved by this upload at {time}: {reason}.',
        'desktop_punch_error' => '{target}: upload of kiosk-reported {attendance_action} at {attendance_time} failed at {time}: {reason}.',
        'dtr_action_success' => '{actor} completed {target} at {time}.',
        'dtr_action_rejected' => '{actor} was rejected while using {target} at {time}: {reason}.',
        'dtr_action_unchanged' => '{actor} had no new result for {target} at {time}: {reason}.',
        'dtr_action_error' => '{actor} encountered an error with {target} at {time}: {reason}.',
    ],

    // Only these ordinary fields may have their values shown in old_data/new_data.
    // Every other changed value is omitted from the audit JSON.
    'safe_fields' => [
        'name', 'fname', 'mname', 'lname', 'employee_name', 'office_name',
        'campus_name', 'title', 'position', 'role', 'status', 'stat_1',
        'reference_no', 'camp_id', 'campus_id', 'emp_dept', 'office_id',
        'department', 'date', 'start_date', 'end_date', 'start', 'end', 'type',
        'office_abbr', 'office_head_id', 'oic_id', 'plantilla_item_no',
        'president', 'pres_prefix',
        'posted_at', 'expiration_at', 'venue', 'event_stat',
        'sl', 'vl', 'days', 'earn_sl', 'earn_vl', 'stat',
    ],

    'modules' => [
        'user' => 'users',
        'employees' => 'employees',
        'pds' => 'pds',
        'office' => 'offices',
        'career' => 'careers',
        'ete' => 'evaluations',
        'interview' => 'interviews',
        'leave' => 'leave',
        'leaves' => 'leave_credits',
        'pending' => 'leave',
        'event' => 'events',
        'events' => 'events',
        'spms' => 'spms',
        'spms-set' => 'spms',
        'spms-mfo-settings' => 'spms',
        'deans' => 'spms',
        'pr-form' => 'spms',
        'contracts' => 'contracts',
        'settings' => 'settings',
        'notification' => 'notifications',
        'myaccount' => 'accounts',
        'data-privacy-notice' => 'accounts',
        'application' => 'applications',
        'job-list' => 'careers',
        'event-list' => 'events',
        'event-login' => 'events',
        'event-logs' => 'events',
        'logout' => 'authentication',
        'post-login' => 'authentication',
        'verify' => 'authentication',
    ],

    // POST endpoints that only fetch or generate data should not report a write.
    'read_only_routes' => [
        'getFormData', 'searchReport', 'leaveReport', 'getPdfPath',
        'contracts.downloadSelected', 'OfficialTimeRead',
    ],

    'access_routes' => [
        'applicationReport', 'contracts.contract.download', 'contracts.downloadAll',
        'contracts.downloadSelected', 'genEmp', 'eteApplicantEvaluationPdf',
        'reportGenrate', 'interviewCandidateAssessmentReport', 'interviewSummaryRatingPdf',
        'getPdfPath', 'leaveReport', 'generatepds',
        'dpcrPdf', 'ipcrPdf', 'opcrPdf', 'generateDpcrPdf', 'generateIpcrPdf', 'generateOpcrPdf',
    ],

    'mutating_get_routes' => [
        'delete-folder', 'document-delete', 'empDelete', 'officeDelete',
        'eventDelete', 'updateNotif',
    ],

    // Focused before/after snapshots for writes that bypass Eloquent events.
    'snapshots' => [
        'officeUpdate' => [
            'connection' => 'payroll', 'table' => 'offices', 'id_input' => 'oid',
            'target_type' => 'office', 'fields' => ['office_name', 'office_abbr', 'office_head_id', 'oic_id'],
        ],
        'leavescreditDeduct' => [
            'connection' => 'mysql', 'table' => 'employees', 'id_input' => 'empid',
            'target_type' => 'employee', 'fields' => ['fname', 'lname', 'sl', 'vl'],
        ],
    ],
];
