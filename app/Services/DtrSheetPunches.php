<?php

namespace App\Services;

use DateTime;

/**
 * Places one day's punches into the AM/PM columns the same way the printed DTR
 * (resources/views/dtr/dtr-pdf.blade.php) does, so the employee dashboard
 * shows what the DTR prints. The PDF keeps its own copy of these rules; if
 * they change there, change them here too.
 *
 * The form uses fixed clock windows, not the employee's schedule:
 *   AM in   earliest time-in, if before 10:00
 *   AM out  earliest time-out, if after 10:00 and before 13:30
 *   PM in   latest time-in before 14:00, if after 11:00 and before 13:31
 *   PM out  latest time-out, if after 13:31
 *
 * Late/undertime is computed separately (CalculatesTardiness) against the
 * official schedule; this class only decides what the form prints.
 */
class DtrSheetPunches
{
    /**
     * @return array{am_in: ?DateTime, am_out: ?DateTime, pm_in: ?DateTime, pm_out: ?DateTime}
     */
    public static function forDay($timeIn, $timeOut)
    {
        $ins = $timeIn ? explode(',', $timeIn) : [];
        $outs = $timeOut ? explode(',', $timeOut) : [];

        sort($ins);
        sort($outs);

        // Time-ins from 14:00 onwards are never printed.
        $ins = array_values(array_filter($ins, function ($time) {
            return strtotime($time) < strtotime('14:00:00');
        }));

        $ins = array_map(fn ($time) => date('g:i:s A', strtotime($time)), $ins);
        $outs = array_map(fn ($time) => date('g:i:s A', strtotime($time)), $outs);

        $punches = ['am_in' => null, 'am_out' => null, 'pm_in' => null, 'pm_out' => null];

        if ($ins) {
            $firstIn = new DateTime(reset($ins));
            $lastIn = new DateTime(end($ins));

            if ($firstIn < new DateTime('10:00')) {
                $punches['am_in'] = $firstIn;
            }
            if ($lastIn > new DateTime('11:00') && $lastIn < new DateTime('13:31')) {
                $punches['pm_in'] = $lastIn;
            }
        }

        if ($outs) {
            $firstOut = new DateTime(reset($outs));
            $lastOut = new DateTime(end($outs));

            if ($firstOut > new DateTime('10:00') && $firstOut < new DateTime('13:30')) {
                $punches['am_out'] = $firstOut;
            }
            if ($lastOut > new DateTime('13:31')) {
                $punches['pm_out'] = $lastOut;
            }
        }

        return $punches;
    }
}
