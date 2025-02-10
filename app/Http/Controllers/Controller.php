<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {   
        $notificationsCount = Notification::where('notifications.utype', '=', 'hr')
        ->where('notifications.status', 0)
        ->count();
        
        $notificationsCount1 = Notification::where('notifications.utype', 'employee')
        ->whereNotIn('notifications.module', ['leavecredit', 'leavecreditadd'])
        ->where('notifications.status', 0)
        ->get();    

        $notifications = Notification::query()
        ->select(
            'notifications.*',
            'notifications.status as notifstat',
            'notifications.created_at as notif_created_at'
        )
        ->where('notifications.utype', '=', 'hr')
        
        // Join for "leave" module
        ->leftJoin('leave_applications', function ($join) {
            $join->on('notifications.lapp_id', '=', 'leave_applications.id')
                 ->where('notifications.module', '=', 'leave');
        })
        ->leftJoin('employees as leave_emp', function ($join) {
            $join->on('leave_emp.emp_ID', '=', 'leave_applications.empid')
                 ->where('notifications.module', '=', 'leave');
        })
        
        // Join for "pds eligibilities" module with category 1
        ->leftJoin('eligibilities', function ($join) {
            $join->on('notifications.lapp_id', '=', 'eligibilities.id')
                 ->where('notifications.category', '=', 1)
                 ->where('notifications.module', '=', 'pds');
        })
        ->leftJoin('employees as pds_emp_eligi', function ($join) {
            $join->on('pds_emp_eligi.emp_ID', '=', 'eligibilities.empid')
                 ->where('notifications.category', '=', 1)
                 ->where('notifications.module', '=', 'pds');
        })

        // Join for "pds work_experiences" module with category 2
        ->leftJoin('work_experiences', function ($join) {
            $join->on('notifications.lapp_id', '=', 'work_experiences.id')
                    ->where('notifications.category', '=', 2)
                    ->where('notifications.module', '=', 'pds');
        })
        ->leftJoin('employees as pds_emp_workexp', function ($join) {
            $join->on('pds_emp_workexp.emp_ID', '=', 'work_experiences.empid')
                    ->where('notifications.category', '=', 2)
                    ->where('notifications.module', '=', 'pds');
        })  

        // Join for "pds voluntary_works" module with category 3
        ->leftJoin('voluntary_works', function ($join) {
            $join->on('notifications.lapp_id', '=', 'voluntary_works.id')
                    ->where('notifications.category', '=', 3)
                    ->where('notifications.module', '=', 'pds');
        })
        ->leftJoin('employees as pds_emp_volworks', function ($join) {
            $join->on('pds_emp_volworks.emp_ID', '=', 'voluntary_works.empid')
                    ->where('notifications.category', '=', 3)
                    ->where('notifications.module', '=', 'pds');
        })  

        // Join for "pds learning_devs" module with category 4
        ->leftJoin('learning_devs', function ($join) {
            $join->on('notifications.lapp_id', '=', 'learning_devs.id')
                    ->where('notifications.category', '=', 4)
                    ->where('notifications.module', '=', 'pds');
        })
        ->leftJoin('employees as pds_emp_learndev', function ($join) {
            $join->on('pds_emp_learndev.emp_ID', '=', 'learning_devs.empid')
                    ->where('notifications.category', '=', 4)
                    ->where('notifications.module', '=', 'pds');
        })  
        
        ->addSelect(
            'leave_applications.*', 'leave_emp.*', 'leave_emp.id as leave_emp_id', 'leave_emp.profile as leave_emp_profile', \DB::raw("CONCAT(leave_emp.fname, ' ', leave_emp.lname) as leave_emp_fullname"),
            'pds_emp_eligi.*', 'pds_emp_eligi.id as pds_emp_eligi_id', 'pds_emp_eligi.profile as pds_emp_eligi_profile', \DB::raw("CONCAT(pds_emp_eligi.fname, ' ', pds_emp_eligi.lname) as pds_emp_eligi_fullname"),
            'pds_emp_workexp.*', 'pds_emp_workexp.id as pds_emp_workexp_id', 'pds_emp_workexp.profile as pds_emp_workexp_profile', \DB::raw("CONCAT(pds_emp_workexp.fname, ' ', pds_emp_workexp.lname) as pds_emp_workexp_fullname"),
            'pds_emp_volworks.*', 'pds_emp_volworks.id as pds_emp_volworks_id', 'pds_emp_volworks.profile as pds_emp_volworks_profile', \DB::raw("CONCAT(pds_emp_volworks.fname, ' ', pds_emp_volworks.lname) as pds_emp_volworks_fullname"),
            'pds_emp_learndev.*', 'pds_emp_learndev.id as pds_emp_learndev_id', 'pds_emp_learndev.profile as pds_emp_learndev_profile', \DB::raw("CONCAT(pds_emp_learndev.fname, ' ', pds_emp_learndev.lname) as pds_emp_learndev_fullname")
        )
            
        ->orderBy('notifications.created_at', 'desc')
        ->paginate(10);
        
        $notifications1 = Notification::query()
            ->select(
                'notifications.*',
                'notifications.empid as notifempid',
                'notifications.status as notifstat',
                'notifications.created_at as notif_created_at'
            )
            ->where('notifications.utype', '=', 'employee')
        
            // Join for "leave" module
            ->leftJoin('leave_applications', function ($join) {
                $join->on('notifications.lapp_id', '=', 'leave_applications.id')
                     ->where('notifications.module', '=', 'leave');
            })
            ->leftJoin('employees as leave_emp', function ($join) {
                $join->on('leave_emp.emp_ID', '=', 'leave_applications.empid')
                     ->where('notifications.module', '=', 'leave');
            })
        
            // Join for "pds eligibilities" module with category 1
            ->leftJoin('eligibilities', function ($join) {
                $join->on('notifications.lapp_id', '=', 'eligibilities.id')
                     ->where('notifications.category', '=', 1)
                     ->where('notifications.module', '=', 'pds');
            })
            ->leftJoin('employees as pds_emp_eligi', function ($join) {
                $join->on('pds_emp_eligi.emp_ID', '=', 'eligibilities.empid')
                     ->where('notifications.category', '=', 1)
                     ->where('notifications.module', '=', 'pds');
            })
        
            // Join for "pds work_experiences" module with category 2
            ->leftJoin('work_experiences', function ($join) {
                $join->on('notifications.lapp_id', '=', 'work_experiences.id')
                        ->where('notifications.category', '=', 2)
                        ->where('notifications.module', '=', 'pds');
            })
            ->leftJoin('employees as pds_emp_workexp', function ($join) {
                $join->on('pds_emp_workexp.emp_ID', '=', 'work_experiences.empid')
                        ->where('notifications.category', '=', 2)
                        ->where('notifications.module', '=', 'pds');
            })  
        
            // Join for "pds voluntary_works" module with category 3
            ->leftJoin('voluntary_works', function ($join) {
                $join->on('notifications.lapp_id', '=', 'voluntary_works.id')
                        ->where('notifications.category', '=', 3)
                        ->where('notifications.module', '=', 'pds');
            })
            ->leftJoin('employees as pds_emp_volworks', function ($join) {
                $join->on('pds_emp_volworks.emp_ID', '=', 'voluntary_works.empid')
                        ->where('notifications.category', '=', 3)
                        ->where('notifications.module', '=', 'pds');
            })  
        
            // Join for "pds learning_devs" module with category 4
            ->leftJoin('learning_devs', function ($join) {
                $join->on('notifications.lapp_id', '=', 'learning_devs.id')
                        ->where('notifications.category', '=', 4)
                        ->where('notifications.module', '=', 'pds');
            })
            ->leftJoin('employees as pds_emp_learndev', function ($join) {
                $join->on('pds_emp_learndev.emp_ID', '=', 'learning_devs.empid')
                        ->where('notifications.category', '=', 4)
                        ->where('notifications.module', '=', 'pds');
            }) 
        
            ->addSelect(
                'leave_applications.*', 'leave_emp.*', 'leave_emp.id as leave_emp_id', 'leave_emp.profile as leave_emp_profile', DB::raw("CONCAT(leave_emp.fname, ' ', leave_emp.lname) as leave_emp_fullname"),
                'pds_emp_eligi.*', 'pds_emp_eligi.id as pds_emp_eligi_id', 'eligibilities.careereligible as eligibilities_careereligible', 'pds_emp_eligi.profile as pds_emp_eligi_profile', DB::raw("CONCAT(pds_emp_eligi.fname, ' ', pds_emp_eligi.lname) as pds_emp_eligi_fullname"),
                'pds_emp_workexp.*', 'pds_emp_workexp.id as pds_emp_workexp_id', 'work_experiences.department as work_experiences_department', 'pds_emp_workexp.profile as pds_emp_workexp_profile', DB::raw("CONCAT(pds_emp_workexp.fname, ' ', pds_emp_workexp.lname) as pds_emp_workexp_fullname"),
                'pds_emp_volworks.*', 'pds_emp_volworks.id as pds_emp_volworks_id', 'voluntary_works.org_name as voluntary_works_org_name', 'pds_emp_volworks.profile as pds_emp_volworks_profile', DB::raw("CONCAT(pds_emp_volworks.fname, ' ', pds_emp_volworks.lname) as pds_emp_volworks_fullname"),
                'pds_emp_learndev.*', 'pds_emp_learndev.id as pds_emp_learndev_id', 'learning_devs.learning_dev as learning_devs_learning_dev', 'pds_emp_learndev.profile as pds_emp_learndev_profile', DB::raw("CONCAT(pds_emp_learndev.fname, ' ', pds_emp_learndev.lname) as pds_emp_learndev_fullname")
            )
                
            ->orderBy('notifications.created_at', 'desc')
            ->get(); // Use get() for sharing with View, unless pagination is required
        
        View::share([
            'notifications' => $notifications,
            'notifications1' => $notifications1,
            'notificationsCount' => $notificationsCount,
            'notificationsCount1' => $notificationsCount1
        ]);
    
    }
}