<?php

namespace App\Services\Audit;

use Illuminate\Http\Request;

class AuditModuleCatalog
{
    public function module(Request $request): string
    {
        $segment = (string) $request->segment($request->is('api/*') ? 2 : 1);
        return config('audit.modules.'.$segment, $segment ?: 'system');
    }

    public function isMapped(Request $request): bool
    {
        $segment = (string) $request->segment($request->is('api/*') ? 2 : 1);
        return array_key_exists($segment, config('audit.modules', []));
    }

    public function intent(Request $request): string
    {
        $route = strtolower((string) optional($request->route())->getName());
        if ($route === '') {
            return 'request';
        }

        foreach (['delete' => 'delete', 'remove' => 'delete', 'destroy' => 'delete',
                  'create' => 'create', 'store' => 'create', 'upload' => 'create',
                  'update' => 'update', 'edit' => 'update', 'save' => 'update',
                  'approve' => 'approve', 'sign' => 'sign', 'close' => 'close',
                  'cancel' => 'cancel', 'deduct' => 'deduct', 'assign' => 'assign',
                  'rate' => 'rate', 'cast' => 'cast', 'copy' => 'copy',
                  'undo' => 'undo', 'return' => 'return', 'lock' => 'lock'] as $word => $intent) {
            if (str_contains($route, $word)) {
                return $intent;
            }
        }

        return 'request';
    }

    public function isReadOnly(Request $request): bool
    {
        return in_array(optional($request->route())->getName(), config('audit.read_only_routes', []), true);
    }

    public function isTrackedAccess(Request $request): bool
    {
        return in_array(optional($request->route())->getName(), config('audit.access_routes', []), true);
    }

    public function isMutatingGet(Request $request): bool
    {
        return $request->method() === 'GET'
            && in_array(optional($request->route())->getName(), config('audit.mutating_get_routes', []), true);
    }
}
