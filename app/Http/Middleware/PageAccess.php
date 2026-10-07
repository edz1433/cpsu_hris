<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * Blocks admin-side accounts from pages they weren't granted in User Management.
 * Employee (Google) accounts are not affected; their access is handled elsewhere.
 *
 * Usage: ->middleware('page.access:dtr')
 */
class PageAccess
{
    public function handle(Request $request, Closure $next, string $page)
    {
        $user = auth()->guard('web')->user();

        if ($user && !$user->canAccessPage($page)) {
            $label = User::PAGE_ACCESS[$page][1] ?? 'this page';
            $message = "You don't have access to {$label}. Ask an administrator to grant it in User Management.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return redirect()->route('dashboard')->with('error', $message);
        }

        return $next($request);
    }
}
