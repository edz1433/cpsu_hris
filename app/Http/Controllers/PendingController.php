<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Employee;
use App\Models\Eligibility;
use App\Models\WorkExperience;
use App\Models\VoluntaryWork;
use App\Models\LearningDev;
use App\Models\LeaveApplication;
use InvalidArgumentException;

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

    public function readPending($type) {
        $guard = $this->getGuard();

        $leaveappCount = LeaveApplication::where('emp_esign', '!=', 1)->where('history', 1)->where('status', 1)->count('empid');
        $eliCount = Eligibility::where('status', 0)->count();
        $workexpCount = WorkExperience::where('status', 0)->count();
        $learDevCount = LearningDev::where('status', 0)->count();
        $volWorkCount = VoluntaryWork::where('status', 0)->count();

        switch ($type) {
            case '1':
                $empids = LeaveApplication::where('emp_esign', '!=', 1)
                    ->where('history', 1)->where('status', 1)->get()
                    ->pluck('empid')->unique()->values()
                    ->toArray();
                break;
    
            case '2':
                $empids = Eligibility::where('status', 0)
                    ->get()->pluck('empid')->unique()->values()->toArray();
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
                return redirect()->route('');
        }

        $employees = [];
        $employees = Employee::whereIn('emp_ID', $empids)->get();

        return view('pending.index', compact('guard', 'type', 'employees', 'eliCount', 'workexpCount', 'learDevCount', 'volWorkCount', 'leaveappCount'));
    }

}
