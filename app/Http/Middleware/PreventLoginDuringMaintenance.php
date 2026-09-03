<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class PreventLoginDuringMaintenance
{
    /**
     * Replace every web login entry point while maintenance mode is enabled.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Setting::maintenanceModeEnabled()) {
            return response()
                ->view('maintenance', [], 503)
                ->withHeaders([
                    'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                    'Retry-After' => '3600',
                ]);
        }

        return $next($request);
    }
}
