<?php

namespace App\Services\Audit;

use Illuminate\Http\Request;

class AuditFlash
{
    public static function hasFailure(Request $request): bool
    {
        if (!$request->hasSession()) {
            return false;
        }

        return (bool) array_intersect(['error', 'error1', 'errors'], $request->session()->get('_flash.new', []));
    }
}
