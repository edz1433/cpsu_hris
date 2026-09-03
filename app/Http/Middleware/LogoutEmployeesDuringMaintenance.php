<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutEmployeesDuringMaintenance
{
    /**
     * End existing employee sessions as soon as they make another request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Setting::maintenanceModeEnabled() && Auth::guard('employee')->check()) {
            Auth::guard('employee')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

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
