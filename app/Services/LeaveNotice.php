<?php

namespace App\Services;

use App\Models\LeaveApplication;
use App\Services\WebPush\NotificationPush;

/**
 * What an employee is told about their own leave or CTO application at each
 * step. Used for both the bell dropdown (layouts/notif-employee) and the web
 * push (NotificationPush), so the two always say the same thing.
 *
 * The constants are notifications.category for rows with utype "employee"
 * and module "leave".
 */
class LeaveNotice
{
    public const NEEDS_SIGNATURE = 1;      // HR verified it, employee must e-sign
    public const APPROVED = 2;             // final approval by the President
    public const HR_APPROVED = 3;          // HR approved, sent to the supervisor
    public const SUPERVISOR_APPROVED = 4;  // supervisor/OIC approved, sent to the President
    public const CANCELED = 5;             // approved application canceled by HR
    public const DISAPPROVED = 6;
    public const RETURNED_TO_YOU = 7;      // HR sent it back for the employee to sign again
    public const RETURNED_TO_HR = 8;       // supervisor sent it back to HR
    public const RETURNED_TO_SUPERVISOR = 9; // President sent it back to the supervisor
    public const REMOVED = 10;             // pending application deleted by HR

    /**
     * @return array{title:string, body:string, route:string}|null
     */
    public static function employee(int $category, $leaveType = null, $transnum = null, $remarksStat = null): ?array
    {
        $isCto = (int) $leaveType === LeaveApplication::TYPE_CTO;
        $noun = $isCto ? 'CTO' : 'Leave';
        $kind = $isCto ? 'CTO' : strtolower(NotificationPush::LEAVE_TYPES[(int) $leaveType] ?? 'leave');
        $what = "Your {$kind} application" . ($transnum ? " (No. #{$transnum})" : '');
        $credits = $isCto ? 'Your Compensatory Overtime Credits have been restored.' : 'Your leave credits have been restored.';
        $by = [1 => 'HR', 2 => 'your immediate supervisor', 3 => 'the SUC President'][(int) $remarksStat] ?? null;

        $message = match ($category) {
            self::NEEDS_SIGNATURE => ["{$noun} application needs your signature", "{$what} was reviewed by HR and is waiting for your e-signature.", 'leaveStatus'],
            self::HR_APPROVED => ["{$noun} application approved by HR", "{$what} was approved by HR and forwarded to your immediate supervisor.", 'leaveStatus'],
            // A CTO goes supervisor -> HR (final); a leave goes on to the President.
            self::SUPERVISOR_APPROVED => ["{$noun} application approved by your supervisor", "{$what} was approved by your immediate supervisor and forwarded to " . ($isCto ? 'HR for final approval.' : 'the SUC President.'), 'leaveStatus'],
            self::APPROVED => ["{$noun} application approved", $isCto ? "{$what} has been approved by HR. The hours were deducted from your Compensatory Overtime Credits." : "{$what} has been approved.", $isCto ? 'historyRead' : 'leaveStatus'],
            self::CANCELED => ["Approved {$noun} application canceled", "{$what} was canceled by HR. {$credits}", 'historyRead'],
            self::DISAPPROVED => ["{$noun} application disapproved", "{$what} was disapproved" . ($by ? " by {$by}" : '') . '. Open History to see the reason.', 'historyRead'],
            self::RETURNED_TO_YOU => ["{$noun} application returned to you", "{$what} was returned by HR. Please review it and e-sign again.", 'leaveStatus'],
            self::RETURNED_TO_HR => ["{$noun} application returned to HR", "{$what} was returned by your immediate supervisor to HR for review.", 'leaveStatus'],
            self::RETURNED_TO_SUPERVISOR => ["{$noun} application returned to your supervisor", "{$what} was returned by the SUC President to your immediate supervisor.", 'leaveStatus'],
            self::REMOVED => ['Application removed', 'Your pending leave/CTO application' . ($transnum ? " (No. #{$transnum})" : '') . ' was removed by HR.', 'historyRead'],
            default => null,
        };

        return $message ? ['title' => $message[0], 'body' => $message[1], 'route' => $message[2]] : null;
    }

    // Creates the employee's notification; the NotificationObserver pushes it.
    public static function notify(LeaveApplication $leave, int $category): void
    {
        \App\Models\Notification::create([
            'empid' => $leave->empid,
            'lapp_id' => $leave->id,
            'category' => $category,
            'utype' => 'employee',
            'module' => 'leave',
        ]);
    }
}
