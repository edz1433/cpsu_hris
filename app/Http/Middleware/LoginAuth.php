<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LoginAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->guard('web')->check()) {
            $userRole = auth()->guard('web')->user()->role;
    
            if ($userRole !== 'Administrator') {
                if ($request->is('user') || $request->is('user/*')) {
                    return redirect()->route('dashboard')->with('error1', 'You do not have permission to access this page');
                }
            }
        } elseif (auth()->guard('employee')->check()) {
            // Admin-only areas. page.access checks admin accounts only, so employee
            // accounts are kept out here. Their own profile edits under employees/*
            // stay open and are limited to their own record by own.records.
            if ($request->is(
                'user', 'user/*', 'users', 'users/*', 'office', 'office/*', 'employees', 'employees/*',
                'leaves', 'leaves/*', 'leave/status/*', 'leave/history/*', 'leave/leave-wpay', 'leave/leaves-report',
                'leave/cacelLeave/*', 'leave/undo', 'leave/undo/*',
                'tardiness', 'tardiness/*', 'pending', 'pending/*', 'career', 'career/*',
                'pds/*-approve/*', 'pds/*-cancel',
                'pds/family-bg/*', 'pds/educ-bg/*', 'pds/eligibility/*', 'pds/work-experience/*',
                'pds/voluntary-work/*', 'pds/learning-dev/*', 'pds/other-info/*', 'pds/info-question/*',
                'pds/references/*', 'pds/government-id/*'
            ) && !$request->is('employees/employee-update', 'employees/update-profile/*')
              && !$this->isJobApplicationReview($request)) {
                return redirect()->route('dashboard')->with('error1', 'You do not have permission to access this page');
            }
            // Events are managed from the admin side only. The dashboard calendar
            // still needs the read-only feed, so that one URL stays open.
            if (($request->is('event') || $request->is('event/*') || $request->is('events') || $request->is('events/*'))
                && !$request->is('event/event-json')) {
                return redirect()->route('dashboard')->with('error1', 'You do not have permission to access this page');
            }
            if ($request->is('leave') && auth()->guard('employee')->user()->emp_status != 1) {
                return redirect()->route('dashboard')->with('error1', 'You do not have permission to access this page');
            }    

        }else {
            return redirect()->route('getLogin')->with('error1', 'You have to sign in first to access this page');
        }
        
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 1990 00:00:00 GMT');

        return $response;
    }

    // The job-application pages the HR reviewers open from their employee accounts.
    private function isJobApplicationReview(Request $request): bool
    {
        return in_array(auth()->guard('employee')->user()->org_email, \App\Models\Employee::JOB_APPLICATION_REVIEWERS, true)
            && $request->is('career/view-applications', 'career/view-application/*', 'career/mark-forwarded/*');
    }
}
