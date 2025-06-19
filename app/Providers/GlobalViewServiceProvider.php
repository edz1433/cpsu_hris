<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\SpmsPersonnel;
use App\Models\Office;

class GlobalViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('*', function ($view) {
            $guard = guard();

            // Check if a user is authenticated
            if ($guard && auth()->guard($guard)->check()) {
                $user = auth()->guard($guard)->user();
                $role = $user->role;
                $userid = $user->id;

                // Fetch needed data
                $pmtsmember = SpmsPersonnel::where('category', 1)->pluck('empid')->toArray();
                $officeHeads = Office::whereRaw('LOWER(office_name) NOT LIKE ?', ['%college%'])
                    ->pluck('office_head_id')
                    ->toArray();

                // Determine route logic
                $driveRoute = ($role == 'Administrator' || $role == 'HR Administrator')
                    ? route('drive')
                    : (
                        ($role == 'employee' && (in_array($userid, $pmtsmember) || in_array($userid, $officeHeads)))
                            ? route('drive')
                            : route('sub-folder', shortEncrypt(3))
                    );

                // Share variables to all views
                $view->with([
                    'role' => $role,
                    'guard' => $guard,
                    'userid' => $userid,
                    'driveRoute' => $driveRoute,
                    'pmtsmember' => $pmtsmember,
                    'officeHeads' => $officeHeads,
                ]);
            }
        });
    }

    public function register()
    {
        //
    }
}
