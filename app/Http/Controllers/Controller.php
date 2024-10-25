<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
        // dd(\Auth::guard('web')->check());
        $notificationsCount = Notification::leftjoin('leave_applications', 'notifications.lapp_id', '=', 'leave_applications.id')
        ->join('employees', 'employees.emp_ID', '=', 'leave_applications.empid')
        ->where('notifications.status', 0)
        ->orderBy('notifications.created_at', 'desc')
        ->count();
        
        $notifications = Notification::leftjoin('leave_applications', 'notifications.lapp_id', '=', 'leave_applications.id')
        ->join('employees', 'employees.emp_ID', '=', 'leave_applications.empid')
        ->select('notifications.*', 'leave_applications.*', 'employees.*', 'employees.id as eid', 'notifications.status as notifstat', 'notifications.created_at as notif_created_at')
        ->orderBy('notifications.created_at', 'desc')
        ->paginate(10);
        
        View::share([
            'notifications' => $notifications,
            'notificationsCount' => $notificationsCount
        ]);
    }
}
