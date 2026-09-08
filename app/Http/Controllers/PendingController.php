<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Employee;
use App\Models\Eligibility;
use App\Models\WorkExperience;
use App\Models\VoluntaryWork;
use App\Models\LearningDev;
use App\Models\LeaveApplication;

class PendingController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function readPending(Request $request, $type, $cat = null) {
        $guard = $this->getGuard();

        $leaveappCount = LeaveApplication::where('emp_esign', '=', 0)->where('history', 1)->where('status', 1)->count('empid');
        $eliCount = Eligibility::where('status', 0)->count();
        $workexpCount = WorkExperience::where('status', 0)->count();
        $learDevCount = LearningDev::where('status', 0)->count();
        $volWorkCount = VoluntaryWork::where('status', 0)->count();

        $search = trim($request->input('search', $request->input('table_search', '')));
        $page = (int) $request->input('page', 1);
        if ($page < 1) $page = 1;
        $limit = (int) $request->input('limit', 25);
        if ($limit < 1 || $limit > 100) $limit = 25;

        $employees = [];
        $totalCount = 0;

        switch ((string)$type) {
            case '1':
                $hasSuffix = \Illuminate\Support\Facades\Schema::hasColumn('employees', 'suffix');

                $query = LeaveApplication::join('employees as emp', 'emp.emp_ID', '=', 'leave_applications.empid')
                    ->leftJoin('employees as hr', 'hr.id', '=', 'leave_applications.hr')
                    ->leftJoin('employees as sup', 'sup.id', '=', 'leave_applications.supervisor')
                    ->leftJoin('employees as sucpres', 'sucpres.id', '=', 'leave_applications.president')
                    ->select(
                        'leave_applications.*',
                        'emp.emp_ID as empid',
                        'emp.id as employid',
                        'emp.lname as employee_lname',
                        'emp.fname as employee_fname',
                        'emp.mname as employee_mname',
                        $hasSuffix ? 'emp.suffix as employee_suffix' : \DB::raw("'' as employee_suffix"),
                        'hr.lname as hr_lname',
                        'hr.fname as hr_fname',
                        'hr.mname as hr_mname',
                        $hasSuffix ? 'hr.suffix as hr_suffix' : \DB::raw("'' as hr_suffix"),
                        'sup.lname as supervisor_lname',
                        'sup.fname as supervisor_fname',
                        'sup.mname as supervisor_mname',
                        $hasSuffix ? 'sup.suffix as supervisor_suffix' : \DB::raw("'' as supervisor_suffix"),
                        'sucpres.lname as sucpres_lname',
                        'sucpres.fname as sucpres_fname',
                        'sucpres.mname as sucpres_mname',
                        $hasSuffix ? 'sucpres.suffix as sucpres_suffix' : \DB::raw("'' as sucpres_suffix")
                    );

                // Add a filter for $cat
                if ($cat !== null && in_array((string)$cat, ['1', '0.1', '0.2'])) {
                    if ((string)$cat === '0.1') {
                        $query->where('leave_applications.emp_esign', '=', 0);
                    } elseif ((string)$cat === '0.2') {
                        $query->where('leave_applications.emp_esign', '=', 1);
                    } elseif ((string)$cat === '1') {
                        $query->where('leave_applications.emp_esign', '=', 2);
                    }
                    $query->where('leave_applications.status', '=', 1);
                    $query->whereIn('leave_applications.history', [0, 1]);
                } elseif ($cat !== null && in_array((string)$cat, ['2', '3'])) {
                    $query->where('leave_applications.status', '=', $cat);
                    $query->whereIn('leave_applications.history', [0, 1]);
                } elseif ((string)$cat === '4') {
                    $query->where('leave_applications.status', '=', 4);
                    $query->where('leave_applications.history', '=', 2);
                } elseif ((string)$cat === '5') {
                    $query->where('leave_applications.history', 2);
                    $query->where('leave_applications.remarks_stat', '!=', 0);
                } else {
                    // "All" view. Its default list is the applications still in
                    // motion, but a search from here looks through every
                    // application, finished ones included. Picking a category
                    // above narrows the search back down to that category.
                    if ($search === '') {
                        $query->where('leave_applications.history', '!=', 2);
                    }
                }

                if ($search !== '') {
                    $like = '%' . $search . '%';

                    $query->where(function($q) use ($search, $like) {
                        $q->where('emp.lname', 'like', $like)
                          ->orWhere('emp.fname', 'like', $like)
                          ->orWhere('emp.mname', 'like', $like)
                          ->orWhere('emp.emp_ID', 'like', $like)
                          ->orWhere('leave_applications.transnum', 'like', $like)
                          // so "juan cruz" and "cruz, juan" both hit
                          ->orWhereRaw("CONCAT_WS(' ', emp.fname, emp.mname, emp.lname) LIKE ?", [$like])
                          ->orWhereRaw("CONCAT_WS(', ', emp.lname, emp.fname) LIKE ?", [$like]);
                    });
                }

                $totalCount = $query->count();

                $employees = $query
                    ->orderBy('leave_applications.id', 'desc')
                    ->skip(($page - 1) * $limit)
                    ->take($limit)
                    ->get();
                       
                break;            
    
            case '2':
                $empids = Eligibility::where('status', 0)
                    ->pluck('empid')->unique()->values()->toArray();
                break;
    
            case '3':
                $empids = WorkExperience::where('status', 0)
                    ->pluck('empid')->unique()->values()->toArray();
                break;
    
            case '4':
                $empids = VoluntaryWork::where('status', 0)
                    ->pluck('empid')->unique()->values()->toArray();
                break;
    
            case '5':
                $empids = LearningDev::where('status', 0)
                    ->pluck('empid')->unique()->values()->toArray();
                break;
                
            default:
                return redirect()->route('readPending', 1);
        }

        if ((string)$type !== '1') {
            $empQuery = Employee::whereIn('emp_ID', $empids);
            if ($search !== '') {
                $like = '%' . $search . '%';

                $empQuery->where(function($q) use ($like) {
                    $q->where('lname', 'like', $like)
                      ->orWhere('fname', 'like', $like)
                      ->orWhere('mname', 'like', $like)
                      ->orWhere('emp_ID', 'like', $like)
                      ->orWhereRaw("CONCAT_WS(' ', fname, mname, lname) LIKE ?", [$like])
                      ->orWhereRaw("CONCAT_WS(', ', lname, fname) LIKE ?", [$like]);
                });
            }
            $totalCount = $empQuery->count();
            $employees = $empQuery
                ->orderBy('lname', 'asc')
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get();
        }

        $hasMore = ($page * $limit) < $totalCount;

        if ($request->ajax() || $request->has('ajax') || $request->wantsJson()) {
            $html = view('pending.partials.table_rows', compact('type', 'cat', 'employees', 'page'))->render();
            return response()->json([
                'success' => true,
                'html' => $html,
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'has_more' => $hasMore,
                'counts' => [
                    'leaveappCount' => $leaveappCount,
                    'eliCount' => $eliCount,
                    'workexpCount' => $workexpCount,
                    'learDevCount' => $learDevCount,
                    'volWorkCount' => $volWorkCount,
                ]
            ]);
        }

        return view('pending.index', compact('guard', 'cat', 'type', 'employees', 'eliCount', 'workexpCount', 'learDevCount', 'volWorkCount', 'leaveappCount', 'page', 'limit', 'totalCount', 'hasMore'));
    }

    public function leaveUndo(Request $request, $id = null)
    {
        if ($id === null) {
            $id = $request->id;
        }

        $leaveApplication = LeaveApplication::find($id);

        if (!$leaveApplication) {
            return response()->json([
                'success' => false,
                'message' => 'Leave application not found.'
            ], 404);
        }

        $leaveApplication->history = 1;
        $leaveApplication->status = 3;
        $leaveApplication->save();

        return response()->json([
            'success' => true,
            'message' => 'Leave updated successfully.'
        ]);
    }
}
