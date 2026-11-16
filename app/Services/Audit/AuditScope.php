<?php

namespace App\Services\Audit;

use Illuminate\Http\Request;

class AuditScope
{
    public const DTR_UTILITY_ACTIONS = [
        'dtr_health', 'dtr_check_restriction_level', 'dtr_fetch_license',
        'dtr_fetch_logzones_with_campuses', 'dtr_fetch_logzones', 'dtr_fetch_latest_logs',
        'dtr_appdtrlogs',
    ];

    public static function isDtrUtility(Request $request): bool
    {
        return ($request->is('api/dtrs') && $request->isMethod('HEAD')) || $request->is(
            'api/app/health', 'api/app/check-restriction-level', 'api/app/fetch-license',
            'api/app/fetch-logzones-with-campuses', 'api/app/fetch-logzones',
            'api/app/fetch-latest-logs', 'api/app/dtrlogs'
        );
    }

    // Screens, searches and employee lookups that only read DTR data.
    // Admin kiosk sign-ins and device linking are not reads and stay recorded.
    public static function isDtrRead(Request $request): bool
    {
        return self::isDtrUtility($request)
            || in_array(optional($request->route())->getName(), [
                'dtr-read', 'dtrSearch', 'dtrLogs', 'dtrLogspost', 'readTiredness', 'tirednessSearch',
            ], true)
            || $request->is('dtr', 'dtr/dtr-logs', 'tardiness/data')
            || ($request->is('api/app-dtr/*') && !$request->is('api/app-dtr/pdf/*'))
            || $request->is(
                'api/app/fetch-employees', 'api/app/validate-qr', 'api/app/face-claim', 'api/app/face-verify',
                'api/app/check-coordinates'
            );
    }

    public static function isTardiness(Request $request): bool
    {
        return $request->is('tardiness', 'tardiness/*', 'tirdeness*')
            || str_contains((string) optional($request->route())->getActionName(), 'TirednessController');
    }

    public static function excludes(Request $request): bool
    {
        return $request->is('audit-logs', 'audit-logs/*');
    }

    public static function isDtr(Request $request): bool
    {
        $action = (string) optional($request->route())->getActionName();

        return $request->is('api/app/*', 'api/app-dtr/*', 'api/dtrs*')
            || str_contains($action, 'DtrController')
            || str_contains($action, 'TimeEntryController')
            || str_contains($action, 'TimeEntryDtrController')
            || str_contains($action, 'TirednessController')
            || str_contains($action, 'FdtController')
            || $request->is('dtr', 'dtr/*', 'tardiness', 'tardiness/*', 'tirdeness*');
    }
}
