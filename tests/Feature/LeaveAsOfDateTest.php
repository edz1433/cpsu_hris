<?php

namespace Tests\Feature;

use App\Models\LeaveApplication;
use App\Models\Office;
use Tests\TestCase;

/**
 * The form's "As of" date is the last day of the month before the leave starts.
 */
class LeaveAsOfDateTest extends TestCase
{
    /** @dataProvider leaveDates */
    public function test_as_of_is_the_last_day_of_the_previous_month(string $dateRange, string $expected): void
    {
        $leave = new LeaveApplication(['date_range' => $dateRange, 'as_of' => '2020-01-15 08:00:00']);

        $this->assertSame($expected, $leave->certificationAsOf()->toDateString());
    }

    public static function leaveDates(): array
    {
        return [
            'November 23, 2026' => ['2026-11-23', '2026-10-31'],
            'December 3, 2026' => ['2026-12-03', '2026-11-30'],
            'January 15, 2027 crosses the year' => ['2027-01-15', '2026-12-31'],
            'February 10-12, 2027 uses the start' => ['2027-02-10 to 2027-02-12', '2027-01-31'],
            'March 5, 2027' => ['2027-03-05', '2027-02-28'],
            'March 10, 2028 leap year' => ['2028-03-10', '2028-02-29'],
            'March 31 does not overflow' => ['2027-03-31', '2027-02-28'],
            'range across months uses the start' => ['2026-11-30 to 2026-12-02', '2026-10-31'],
        ];
    }

    public function test_unreadable_date_range_keeps_the_stored_date(): void
    {
        $leave = new LeaveApplication(['date_range' => '2026-19,20-11', 'as_of' => '2026-11-05 08:00:00']);
        $this->assertSame('2026-11-05', $leave->certificationAsOf()->toDateString());

        $this->assertNull((new LeaveApplication(['date_range' => 'bad']))->certificationAsOf());
    }

    public function test_leave_form_prints_the_as_of_date(): void
    {
        $leave = new LeaveApplication([
            'transnum' => '000001', 'empid' => 'E-1', 'leave_type' => 1, 'leave_purpose' => 1, 'days' => 1,
            'date_range' => '2026-11-23', 'date_filing' => '2026-11-20 08:00:00', 'as_of' => '2026-11-21 08:00:00',
            'emp_esign' => 0, 'remarks_stat' => 0,
        ]);
        $leave->setRelation('office', new Office(['office_name' => 'Test Office', 'office_abbr' => 'TO']));

        $html = view('leaves.generate-leave', ['leaveApplication' => $leave])->render();

        $this->assertStringContainsString('OCT 31, 2026', $html);
        $this->assertStringNotContainsString('NOV 21, 2026', $html);
    }
}
