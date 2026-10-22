<?php

namespace App\Services\Audit;

use Carbon\Carbon;
use Illuminate\Support\Str;

class AuditFormatter
{
    public static function actionLabel(object $entry): string
    {
        $details = json_decode((string) ($entry->details ?? '{}'), true);
        $label = ($entry->module ?? null) === 'dtr' ? ([
            'api/app/validate-qr' => 'QR Validation',
            'api/app/face-claim' => 'Face Verification',
            'api/app/face-verify' => 'Face Verification',
            'api/app/admin-face-claim' => 'Admin Face Verification',
            'api/app/admin-face-verify' => 'Admin Face Verification',
            'api/app/admin-pass-verify' => 'Admin Password Verification',
            'api/app/face-register' => 'Face Registration',
            'api/app/fetch-employees' => 'Employee Directory',
            'api/app/check-restriction-level' => 'Attendance Availability',
            'api/app/health' => 'Server Health Check',
            'api/app/fetch-license' => 'License Check',
            'api/app/fetch-logzones-with-campuses' => 'Zone Lookup',
            'api/app/fetch-logzones' => 'Zone Lookup',
            'api/app/fetch-latest-logs' => 'Latest Logs',
            'api/app/dtrlogs' => 'Latest Logs',
            'api/app/download-dtr' => 'DTR Download',
            'api/app-dtr/{empid}' => 'DTR Viewer',
            'api/app-dtr/search' => 'DTR Search',
            'api/app-dtr/pdf/{empid}/{period}/{date}/{overtime}/{filename}' => 'DTR Download',
        ][$details['endpoint'] ?? ''] ?? null) : null;

        return $label ?? str_replace('_', ' ', (string) ($entry->action ?? 'request'));
    }

    public static function describe(object $entry): string
    {
        $action = (string) ($entry->action ?? 'request');
        $messageKey = $action === 'request' && ($entry->outcome ?? 'success') !== 'success' ? 'request_failed' : $action;
        if (in_array(($entry->module ?? null), ['dtr', 'tardiness'], true)) {
            $messageKey = in_array($action, ['time_in', 'time_out', 'overtime'], true)
                ? $action.'_'.($entry->outcome ?? 'success') : 'dtr_action_'.($entry->outcome ?? 'success');
        }
        $fallback = ($entry->outcome ?? null) === 'received'
            ? 'The server received {target} at {time}; completion is recorded separately.'
            : config('audit.messages.request');
        $details = json_decode((string) ($entry->details ?? '{}'), true) ?: [];
        $label = self::actionLabel($entry);
        if (in_array($label, ['QR Validation', 'Face Verification', 'Admin Face Verification',
            'Admin Password Verification', 'Face Registration', 'Employee Directory', 'Attendance Availability',
            'Server Health Check', 'License Check', 'Zone Lookup', 'Latest Logs', 'DTR Download', 'DTR Viewer', 'DTR Search'], true)) {
            $target = ($entry->target_name ?? null) ?: 'Unresolved employee';
            $time = Carbon::parse($entry->occurred_at)->format('g:i:s A \o\n F j, Y');
            $result = match ($entry->outcome ?? '') {
                'success' => 'succeeded',
                'rejected', 'failure' => 'was rejected',
                'error' => 'encountered an error',
                'received' => 'request was received',
                'unchanged' => 'returned no new result',
                default => 'returned '.str_replace('_', ' ', (string) ($entry->outcome ?? 'unknown')),
            };
            if (($details['identity_basis'] ?? null) === 'qr_claim_not_face_verified') {
                $target .= ' (QR claim; face not verified)';
            }
            if (($entry->reason_code ?? null) === 'face_mismatch') {
                return $target.': '.$label.' '.$result.' at '.$time.'. Face did not match.';
            }
            $reason = !empty($entry->reason_code) ? ': '.str_replace('_', ' ', $entry->reason_code) : '';
            return $target.': '.$label.' '.$result.' at '.$time.$reason.'.';
        }
        if (($details['source'] ?? null) === 'desktop_sync'
            && in_array($action, ['time_in', 'time_out', 'overtime'], true)) {
            $messageKey = 'desktop_punch_'.($entry->outcome ?? 'success');
            // Remain accurate while an older Laravel config cache is still loaded.
            $fallback = match ($entry->outcome ?? 'success') {
                'success' => '{target}: kiosk-reported {attendance_action} at {attendance_time} was saved by the server at {time}.',
                'unchanged' => '{target}: kiosk-reported {attendance_action} at {attendance_time} was already stored; upload received at {time}.',
                'rejected' => '{target}: kiosk-reported {attendance_action} at {attendance_time} was not saved by this upload at {time}: {reason}.',
                default => '{target}: upload of kiosk-reported {attendance_action} at {attendance_time} failed at {time}: {reason}.',
            };
        }
        $template = config('audit.messages.'.$messageKey, $fallback);
        $reason = str_replace('_', ' ', (string) ($entry->reason_code ?? 'completed'));
        $time = Carbon::parse($entry->occurred_at)->format('g:i:s A \o\n F j, Y');

        $type = (string) ($entry->target_type ?? '');
        $name = (string) ($entry->target_name ?? '');
        $target = $name ?: Str::headline($type ?: (string) ($entry->module ?? 'this action'));
        if ($name !== '' && in_array($action, ['created', 'updated', 'deleted'], true) && $type !== '' && $type !== 'route') {
            $target = Str::headline($type).' '.$name;
        }
        if (($entry->reason_code ?? null) === 'time_in_after_time_out_60_seconds' && isset($details['seconds_remaining'])) {
            $reason = '60-second gap after Time Out; '.$details['seconds_remaining'].' seconds remaining';
        }

        $attendanceTime = isset($details['attendance_at'])
            ? Carbon::parse($details['attendance_at'])->format('g:i:s A \o\n F j, Y') : $time;
        $eventTime = ($details['source'] ?? null) !== 'desktop_sync' && ($entry->outcome ?? null) === 'success'
            && in_array($action, ['time_in', 'time_out', 'overtime'], true) ? $attendanceTime : $time;
        $description = strtr($template, [
            '{actor}' => $entry->actor_name ?: 'The system',
            '{target}' => $target,
            '{time}' => $eventTime,
            '{reason}' => $reason,
            '{operation}' => $details['intent'] ?? 'change',
            '{module}' => Str::headline((string) ($entry->module ?? 'this module')),
            '{seconds_remaining}' => (string) ($details['seconds_remaining'] ?? 0),
            '{attendance_action}' => ['time_in' => 'Time In', 'time_out' => 'Time Out', 'overtime' => 'Overtime'][$action] ?? 'attendance',
            '{attendance_time}' => $attendanceTime,
        ]);
        if (($details['source'] ?? null) === 'desktop_sync') {
            $flags = is_array($details['timing_flags'] ?? null) ? $details['timing_flags'] : [];
            if (in_array('future_dated', $flags, true)) {
                $description .= ' Informational timing flag: kiosk time is ahead of server receipt beyond the configured tolerance.';
            }
            if (in_array('late_upload', $flags, true)) {
                $description .= ' Informational timing flag: upload arrived beyond the configured delay threshold.';
            }
        }
        return $description;
    }
}
