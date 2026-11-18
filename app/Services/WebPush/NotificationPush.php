<?php

namespace App\Services\WebPush;

use App\Models\Eligibility;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LearningDev;
use App\Models\Notification;
use App\Models\PushSubscription;
use App\Models\User;
use App\Models\VoluntaryWork;
use App\Models\WorkExperience;
use App\Services\LeaveNotice;
use Illuminate\Support\Collection;

/**
 * Turns an in-app notification row into a push message and works out which
 * browser subscriptions should receive it. Wording and links follow the
 * navbar notification dropdowns (layouts/notif-admin, layouts/notif-employee).
 */
class NotificationPush
{
    public const LEAVE_TYPES = [
        1 => 'Vacation Leave',
        2 => 'Mandatory/Forced Leave',
        3 => 'Sick Leave',
        4 => 'Maternity Leave',
        5 => 'Paternity Leave',
        6 => 'Special Privilege Leave',
        7 => 'Solo Parent Leave',
        8 => 'Study Leave',
        9 => '10-Day VAWC Leave',
        10 => 'Rehabilitation Privilege',
        11 => 'Special Leave Benefits for Women',
        12 => 'Special Emergency (Calamity) Leave',
        13 => 'Adoption Leave',
        14 => 'Vacation Service Credit',
        15 => 'Wellness Leave',
        16 => 'Compensatory Time-Off (CTO)',
    ];

    // PDS category => [model, label shown to HR, field naming the entry, employee page route]
    private const PDS = [
        1 => [Eligibility::class, 'eligibility', 'careereligible', 'eligibility'],
        2 => [WorkExperience::class, 'work experience', 'department', 'work-experience'],
        3 => [VoluntaryWork::class, 'voluntary work', 'org_name', 'voluntary-work'],
        4 => [LearningDev::class, 'learning and development', 'learning_dev', 'learning-dev'],
    ];

    public function __construct(private Notification $notification)
    {
    }

    /**
     * Rows created already read (status 1) are bookkeeping, not alerts.
     */
    public function shouldSend(): bool
    {
        return (int) $this->notification->status === 0 && $this->message() !== null;
    }

    /**
     * @return array{title:string, body:string, url:string, tag:string}|null
     */
    public function message(): ?array
    {
        $n = $this->notification;

        $message = match ($n->module) {
            'leave' => $this->leaveMessage(),
            'pds' => $this->pdsMessage(),
            default => null,
        };

        if ($message) {
            // Same tag = a newer push for the same item replaces the older one on the device.
            $message['tag'] = "hris-{$n->module}-{$n->lapp_id}";
        }

        return $message;
    }

    /**
     * @return Collection<int, PushSubscription>
     */
    public function subscriptions(): Collection
    {
        $n = $this->notification;

        switch ($n->utype) {
            case 'hr':
                $page = $n->module === 'pds' ? 'employees' : 'leave';
                $userIds = User::all()->filter(fn (User $u) => $u->canAccessPage($page))->pluck('id');

                return PushSubscription::for(PushSubscription::TYPE_USER, $userIds)->get();

            case 'employee':
                $employeeIds = Employee::where('emp_ID', $n->empid)->pluck('id');

                return PushSubscription::for(PushSubscription::TYPE_EMPLOYEE, $employeeIds)->get();

            case 'supervisor':
                $leave = $this->leave();
                $id = $leave ? $leave->supervisorSignatory()['id'] : (int) $n->esign_id;

                return PushSubscription::for(PushSubscription::TYPE_EMPLOYEE, [$id])->get();

            case 'president':
                $leave = $this->leave();

                return PushSubscription::for(PushSubscription::TYPE_EMPLOYEE, [optional($leave)->president])->get();
        }

        return collect();
    }

    private ?LeaveApplication $leaveCache = null;

    private function leave(): ?LeaveApplication
    {
        return $this->leaveCache ??= LeaveApplication::find($this->notification->lapp_id);
    }

    private function applicant(): ?Employee
    {
        return Employee::where('emp_ID', $this->notification->empid)->first();
    }

    private static function fullName(?Employee $e): string
    {
        return $e ? ucwords(strtolower(trim("{$e->fname} {$e->lname}"))) : 'An employee';
    }

    private function leaveMessage(): ?array
    {
        $n = $this->notification;
        $leave = $this->leave();
        $category = (int) $n->category;

        // The employee's own application, every step (shared with the bell dropdown).
        // A removed application no longer exists, so this runs before the check below.
        if ($n->utype === 'employee') {
            $transnum = optional($leave)->transnum ?? ($category === LeaveNotice::REMOVED ? $n->esign_id : null);
            $notice = LeaveNotice::employee($category, optional($leave)->leave_type, $transnum, optional($leave)->remarks_stat);

            return $notice ? ['title' => $notice['title'], 'body' => $notice['body'], 'url' => route($notice['route'])] : null;
        }

        if (!$leave) {
            return null;
        }

        $type = self::LEAVE_TYPES[$leave->leave_type] ?? 'leave';
        $ref = "Application No. #{$leave->transnum}";
        $noun = $leave->isCto() ? 'CTO' : 'Leave';

        $applicant = $this->applicant();
        $name = self::fullName($applicant);

        if ($n->utype === 'hr' && $leave->isCto()) {
            return [
                'title' => 'CTO awaiting HR approval',
                'body' => "{$name}'s CTO ({$ref}) was approved by the immediate supervisor and needs HR's final approval.",
                'url' => route('leaveStatus', optional($applicant)->id),
            ];
        }

        if ($n->utype === 'hr') {
            $action = $category === 1 ? 'is applying for' : 'is awaiting approval for';

            return [
                'title' => $category === 1 ? 'New leave application' : 'Leave application signed by employee',
                'body' => "{$name} {$action} {$type} ({$ref}).",
                'url' => route('leaveStatus', optional($applicant)->id),
            ];
        }

        if (in_array($n->utype, ['supervisor', 'president'], true)) {
            return [
                'title' => "{$noun} application awaiting your approval",
                'body' => "{$name}'s {$type} application ({$ref}) is ready for your signature.",
                // A supervisor acts on a CTO from their own Status page.
                'url' => $leave->isCto() ? route('leaveStatus') : route('readPending', 1),
            ];
        }

        return null;
    }

    private function pdsMessage(): ?array
    {
        $n = $this->notification;
        $category = (float) $n->category;
        $base = (int) floor($category);
        if (!isset(self::PDS[$base])) {
            return null;
        }

        [$model, $label, $field, $route] = self::PDS[$base];

        if ($n->utype === 'employee') {
            $entry = $model::find($n->lapp_id);
            $action = $category == $base ? 'approved' : 'declined';
            $what = $entry && filled($entry->{$field}) ? " ({$entry->{$field}})" : '';

            return [
                'title' => 'PDS update ' . $action,
                'body' => "Your submitted {$label}{$what} has been {$action} by HR.",
                'url' => route($route),
            ];
        }

        if ($n->utype === 'hr') {
            $applicant = $this->applicant();

            return [
                'title' => 'New PDS submission',
                'body' => self::fullName($applicant) . " has submitted a new {$label}.",
                'url' => $applicant
                    ? route('updateNotif', ['menid' => $applicant->id, 'lappid' => $n->lapp_id, 'menu' => $route])
                    : route('dashboard'),
            ];
        }

        return null;
    }
}
