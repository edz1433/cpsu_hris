<?php

namespace App\Services;

use App\Models\LeaveApplication;
use App\Models\LeaveCredit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Rebuilds an employee's leave card (CPSU-F-HRMO-05) from what is already
 * stored: the Credit Ledger (leave_credits) plus the leave applications that
 * went through the HRIS. Read-only: it never writes to the database.
 *
 * The two sources complement each other. Ledger rows are posted by HR
 * (opening balance, monthly earnings, tardiness, monetization, paper leaves),
 * while an HRIS application deducts the employee's VL/SL directly when the
 * supervisor (or OIC) signs it, without adding a ledger row. Both are needed
 * to arrive at the balance the system holds.
 */
class LeaveCardLedger
{
    public const LEAVE_CODES = [
        1 => 'VL',
        2 => 'FL',
        3 => 'SL',
        4 => 'ML',
        5 => 'PL',
        6 => 'SpL',
        7 => 'SPL-SP',
        8 => 'StL',
        9 => 'VAWC',
        10 => 'RP',
        11 => 'SLBW',
        12 => 'SEL',
        13 => 'AL',
        14 => 'VSC',
        15 => 'WL',
    ];

    public const LEAVE_NAMES = [
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
    ];

    // Order of entries inside one month: the opening balance, then leaves and
    // deductions, then credits added back, and the month's earnings last.
    private const GROUP_OPENING = 0;
    private const GROUP_DEBIT = 1;
    private const GROUP_ADD_BACK = 2;
    private const GROUP_EARNED = 3;

    /**
     * @param  string  $empId  employees.emp_ID
     * @param  string  $from   first month, Y-m
     * @param  string  $to     last month, Y-m
     */
    public function build(string $empId, string $from, string $to): array
    {
        $events = $this->ledgerEvents($empId)
            ->merge($this->applicationEvents($empId))
            ->sortBy(fn ($event) => sprintf('%s|%d|%s|%010d', $event['month'], $event['group'], $event['sort'], $event['seq']))
            ->values();

        $opening = $events->firstWhere('group', self::GROUP_OPENING);

        // Nothing exists before the opening balance, so a card starting earlier
        // would only be empty months.
        if ($opening && $from < $opening['month'] && $opening['month'] <= $to) {
            $from = $opening['month'];
        }

        $vl = 0.0;
        $sl = 0.0;
        $forwardRemarks = '';

        foreach ($events as $event) {
            $before = $event['month'] < $from
                || ($event['group'] === self::GROUP_OPENING && $event['month'] === $from);

            if (!$before) {
                continue;
            }

            [$vl, $sl] = $this->apply($vl, $sl, $event);

            if ($event['group'] === self::GROUP_OPENING && $event['month'] === $from) {
                $forwardRemarks = $event['remarks'];
            }
        }

        $openingBalance = ['vl' => $vl, 'sl' => $sl];
        $byMonth = $events
            ->filter(fn ($event) => $event['month'] >= $from && $event['month'] <= $to
                && !($event['group'] === self::GROUP_OPENING && $event['month'] === $from))
            ->groupBy('month');

        $rows = [];
        $month = Carbon::createFromFormat('!Y-m', $from);
        $last = Carbon::createFromFormat('!Y-m', $to);

        while ($month->lte($last)) {
            $key = $month->format('Y-m');

            if ($key === $from || $month->month === 1) {
                $rows[] = $this->forwardRow($vl, $sl, $key === $from ? $forwardRemarks : '');
                $rows[] = $this->row('year', $month->format('Y'), $vl, $sl);
            }

            $monthEvents = $byMonth->get($key, collect());

            if ($monthEvents->isEmpty()) {
                $rows[] = $this->row('entry', $month->format('M'), $vl, $sl);
            }

            foreach ($monthEvents->values() as $index => $event) {
                [$vl, $sl] = $this->apply($vl, $sl, $event);
                $rows[] = $this->row('entry', $index === 0 ? $month->format('M') : '', $vl, $sl, $event);
            }

            $month->addMonth();
        }

        return [
            'from' => $from,
            'to' => $to,
            'opening' => $openingBalance,
            'closing' => ['vl' => $vl, 'sl' => $sl],
            'rows' => $rows,
        ];
    }

    private function ledgerEvents(string $empId): Collection
    {
        $credits = LeaveCredit::where('empid', $empId)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $first = $credits->first();

        return $credits->map(function (LeaveCredit $credit) use ($first) {
            $vl = round((float) $credit->earn_vl, 3);
            $sl = round((float) $credit->earn_sl, 3);
            $remarks = trim(preg_replace('/\s+/', ' ', (string) $credit->remarks));
            $event = $this->event($credit->date, (string) optional($credit->created_at)->format('Y-m-d'), $credit->id);

            // Same reading of stat/days as LeaveCreditController::leavesDelete:
            // stat 1 with no days is a deduction, everything else adds credits.
            if ((int) $credit->stat === 1 && (float) $credit->days == 0) {
                return array_merge($event, [
                    'group' => self::GROUP_DEBIT,
                    'particulars' => $remarks,
                    'vl_less' => $vl,
                    'sl_less' => $sl,
                ]);
            }

            if ((int) $credit->stat === 1) {
                $standard = stripos($remarks, 'Earned leave credits') === 0;

                return array_merge($event, [
                    'group' => self::GROUP_EARNED,
                    'remarks' => $standard ? '' : $remarks,
                    'vl_earned' => $vl,
                    'sl_earned' => $sl,
                ]);
            }

            // stat 0 is the balance HR carried over when the employee was first
            // encoded; any later one is a plain addition.
            if ((int) $credit->stat === 0 && $credit->is($first)) {
                return array_merge($event, [
                    'group' => self::GROUP_OPENING,
                    'particulars' => 'Balance forwarded',
                    'remarks' => $remarks,
                    'vl_earned' => $vl,
                    'sl_earned' => $sl,
                ]);
            }

            return array_merge($event, [
                'group' => self::GROUP_ADD_BACK,
                'particulars' => $remarks,
                'vl_earned' => $vl,
                'sl_earned' => $sl,
            ]);
        });
    }

    private function applicationEvents(string $empId): Collection
    {
        return LeaveApplication::where('empid', $empId)
            ->whereIn('status', [3, 4])
            ->orderBy('id')
            ->get()
            ->map(function (LeaveApplication $application) {
                [$start, $end] = $this->dateRange($application->date_range);

                if (!$start) {
                    return null;
                }

                $remarksStat = (int) $application->remarks_stat;
                $signed = (int) $application->sup_sign === 2 || $application->oic !== null;
                $disapproved = $remarksStat !== 0;

                // Credits come off at the supervisor/OIC signature and go back
                // when HR disapproves (4) or the supervisor never signed (2).
                $deducted = $signed && !in_array($remarksStat, [2, 4], true);

                // Still waiting for the supervisor: nothing has happened yet.
                if (!$deducted && !$disapproved) {
                    return null;
                }

                $type = (int) $application->leave_type;
                $code = self::LEAVE_CODES[$type] ?? 'Leave';

                // The card lists plain day numbers for VL and SL; every
                // other leave type carries its code, e.g. "5 (SpL)".
                $days = $this->formatDays($start, $end, (int) $application->days + (int) $application->holiday);
                $particulars = in_array($type, [1, 3], true) ? $days : "{$days} ({$code})";

                $event = array_merge($this->event($start->format('Y-m'), $start->format('Y-m-d'), $application->id), [
                    'group' => self::GROUP_DEBIT,
                    'particulars' => $particulars,
                ]);

                $remarks = [];

                if ($deducted) {
                    $lessVl = round((float) $application->less_vl, 3);
                    $lessSl = round((float) $application->less_sl, 3);
                    $withoutPay = round((float) $application->day_wpay, 3);

                    if (in_array($type, [1, 2, 3], true)) {
                        $event['vl_less'] = $lessVl;
                    }
                    if ($type === 3) {
                        $event['sl_less'] = $lessSl;
                        if ($lessVl > 0) {
                            $remarks[] = 'SL charged to VL';
                        }
                    }
                    if ($withoutPay > 0) {
                        $event[$type === 3 ? 'sl_wop' : 'vl_wop'] = $withoutPay;
                    }
                }

                if ($disapproved) {
                    $reason = [2 => $application->remarks_details, 3 => $application->remarks_details1, 4 => $application->remarks_details2][$remarksStat] ?? '';
                    $reason = trim((string) $reason);
                    // remarks_stat 4 is a cancellation, the rest are disapprovals.
                    $label = $remarksStat === 4 ? 'CANCELLED' : 'DISAPPROVED';
                    // The stored reason often opens with the label itself,
                    // e.g. "Cancelled due to exigency of service".
                    if ($remarksStat === 4) {
                        $reason = trim(preg_replace('/^cancel(?:led|ation)?\s*\.?\s*/i', '', $reason));
                    }
                    $remarks[] = trim($label . ' ' . $reason);
                }

                $event['remarks'] = implode('; ', array_filter($remarks));

                return $event;
            })
            ->filter()
            ->values();
    }

    private function event(string $month, string $sort, int $seq): array
    {
        return [
            'month' => $month,
            'sort' => $sort,
            'seq' => $seq,
            'group' => self::GROUP_DEBIT,
            'particulars' => '',
            'remarks' => '',
            'vl_earned' => 0.0,
            'vl_less' => 0.0,
            'vl_wop' => 0.0,
            'sl_earned' => 0.0,
            'sl_less' => 0.0,
            'sl_wop' => 0.0,
        ];
    }

    private function apply(float $vl, float $sl, array $event): array
    {
        return [
            round($vl + $event['vl_earned'] - $event['vl_less'], 3),
            round($sl + $event['sl_earned'] - $event['sl_less'], 3),
        ];
    }

    private function forwardRow(float $vl, float $sl, string $remarks): array
    {
        $row = $this->row('forward', 'BALANCE BROUGHT FORWARD', $vl, $sl);
        $row['remarks'] = $remarks;

        return $row;
    }

    private function row(string $kind, string $period, float $vl, float $sl, array $event = []): array
    {
        return [
            'kind' => $kind,
            'period' => $period,
            'particulars' => $event['particulars'] ?? '',
            'vl_earned' => $event['vl_earned'] ?? 0.0,
            'vl_less' => $event['vl_less'] ?? 0.0,
            'vl_balance' => $vl,
            'vl_wop' => $event['vl_wop'] ?? 0.0,
            'sl_earned' => $event['sl_earned'] ?? 0.0,
            'sl_less' => $event['sl_less'] ?? 0.0,
            'sl_balance' => $sl,
            'sl_wop' => $event['sl_wop'] ?? 0.0,
            'remarks' => $event['remarks'] ?? '',
        ];
    }

    /**
     * "2025-04-10 to 2025-04-11" or a single "2025-04-10".
     */
    private function dateRange(?string $range): array
    {
        $parts = array_map('trim', explode(' to ', (string) $range));

        try {
            $start = Carbon::parse($parts[0])->startOfDay();
            $end = isset($parts[1]) ? Carbon::parse($parts[1])->startOfDay() : $start->copy();
        } catch (\Exception $e) {
            return [null, null];
        }

        return $end->lt($start) ? [$end, $start] : [$start, $end];
    }

    /**
     * Day numbers as written on the card: "18", "19,20,21,22,23", and
     * "30,31 Feb 2" when the leave runs into the next month. Falls back to a
     * plain range when the weekdays don't match the days filed (weekend or
     * holiday work), so no date is ever listed that wasn't actually taken.
     */
    private function formatDays(Carbon $start, Carbon $end, int $daysFiled): string
    {
        if ($start->eq($end)) {
            return $start->format('j');
        }

        $weekdays = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if (!$day->isWeekend()) {
                $weekdays[] = $day->copy();
            }
        }

        if (count($weekdays) !== $daysFiled || count($weekdays) > 10 || !$weekdays) {
            return $start->format('j') . '-' . ($start->isSameMonth($end) ? $end->format('j') : $end->format('M j'));
        }

        $out = '';
        $month = $start->format('Y-m');
        foreach ($weekdays as $index => $day) {
            if ($day->format('Y-m') !== $month) {
                $month = $day->format('Y-m');
                $out .= ' ' . $day->format('M') . ' ' . $day->format('j');
                continue;
            }
            $out .= ($index === 0 ? '' : ',') . $day->format('j');
        }

        return $out;
    }
}
