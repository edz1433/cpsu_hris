<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeavePresidentAssignmentController extends Controller
{
    public function options(Request $request)
    {
        $this->authorizeAdministrator();
        $search = trim((string) $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
        ])['q'] ?? '');

        if (mb_strlen($search) < 2) {
            return response()->json(['results' => []]);
        }

        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
        $employees = Employee::query();
        foreach (array_slice($terms, 0, 4) as $term) {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
            $employees->where(function ($query) use ($like) {
                foreach (['fname', 'mname', 'lname', 'emp_ID'] as $field) {
                    $query->orWhereRaw("{$field} LIKE ? ESCAPE '!'", [$like]);
                }
            });
        }
        $employees = $employees->orderBy('lname')->limit(20)->get(['id', 'emp_ID', 'fname', 'mname', 'lname']);

        return response()->json(['results' => $employees->map(fn ($employee) => [
            'id' => $employee->id,
            'text' => trim(implode(' ', array_filter([$employee->fname, $employee->mname, $employee->lname]))).' ('.$employee->emp_ID.')',
        ])]);
    }

    public function update(Request $request, LeaveApplication $leaveApplication)
    {
        $this->authorizeAdministrator();
        $validated = $request->validate([
            'president' => ['required', 'integer', 'exists:employees,id'],
        ]);

        DB::transaction(function () use ($leaveApplication, $validated) {
            $application = LeaveApplication::query()->lockForUpdate()->findOrFail($leaveApplication->id);
            if (!in_array((int) $application->history, [0, 1], true)
                || !in_array((int) $application->status, [1, 2, 3], true)
                || (int) $application->pres_sign === 2) {
                throw ValidationException::withMessages([
                    'president' => 'The SUC President can only be changed before this application is signed or completed.',
                ]);
            }
            if ((int) $application->president === (int) $validated['president']) {
                throw ValidationException::withMessages(['president' => 'Select a different employee.']);
            }

            $president = Employee::query()->findOrFail($validated['president']);
            $application->president = $president->id;
            $application->pres_prefix = $president->prefix;
            $application->save();
        });

        return response()->json(['success' => true, 'message' => 'SUC President updated for this leave application.']);
    }

    private function authorizeAdministrator(): void
    {
        abort_unless(Auth::guard('web')->check() && Auth::guard('web')->user()->role === 'Administrator', 403);
    }
}
