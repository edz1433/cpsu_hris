<?php

namespace App\Http\Middleware;

use App\Models\Eligibility;
use App\Models\LearningDev;
use App\Models\VoluntaryWork;
use App\Models\WorkExperience;
use Closure;
use Illuminate\Http\Request;

/**
 * Keeps employee accounts to their own PDS and profile. Admin accounts are not
 * affected; page.access decides what they can open.
 *
 * Usage: ->middleware('own.records')
 */
class OwnEmployeeRecords
{
    // Routes whose {id} is a PDS entry; an employee may change only their own unreviewed entries.
    private const ENTRY_ROUTES = [
        'eligibilityUpdate' => Eligibility::class, 'eliDelete' => Eligibility::class,
        'workexperienceUpdate' => WorkExperience::class, 'workDelete' => WorkExperience::class,
        'voluntaryworksUpdate' => VoluntaryWork::class, 'voluntaryworkDelete' => VoluntaryWork::class,
        'learningdevUpdate' => LearningDev::class, 'learningdevDelete' => LearningDev::class,
    ];

    // Request fields that name the employee being viewed or changed, by row id or emp_ID.
    private const EMPLOYEE_FIELDS = ['id', 'empid', 'emp_id', 'employee_id'];

    public function handle(Request $request, Closure $next)
    {
        $employee = auth()->guard('employee')->user();
        if (!$employee || auth()->guard('web')->check()) {
            return $next($request);
        }

        $own = fn ($value) => $value === null || $value === ''
            || (is_scalar($value) && in_array((string) $value, [(string) $employee->id, (string) $employee->emp_ID], true));

        $route = $request->route();
        $entryModel = self::ENTRY_ROUTES[optional($route)->getName()] ?? null;
        if ($entryModel) {
            $entry = $entryModel::find($route->parameter('id'));
            $allowed = $entry && (string) $entry->empid === (string) $employee->emp_ID && (int) $entry->status !== 1;
        } else {
            $allowed = $own(optional($route)->parameter('id'));
        }

        foreach (self::EMPLOYEE_FIELDS as $field) {
            // On entry routes the posted id is the entry itself, checked above.
            if ($entryModel && $field === 'id') continue;
            $allowed = $allowed && $own($request->input($field));
        }

        if (!$allowed) {
            $message = 'You can only change your own records.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }
            return redirect()->route('dashboard')->with('error1', $message);
        }

        return $next($request);
    }
}
