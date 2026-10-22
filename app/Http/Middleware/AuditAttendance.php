<?php

namespace App\Http\Middleware;

use App\Services\Audit\AuditDtrRecorder;
use App\Services\Audit\AuditDtrSyncRecorder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class AuditAttendance
{
    public function handle(Request $request, Closure $next)
    {
        $desktop = $request->is('api/dtrs');
        $audit = app(AuditDtrRecorder::class);
        $sync = app(AuditDtrSyncRecorder::class);
        $data = $desktop ? json_decode($request->getContent(), true) : null;
        // Preserve the controller's invalid-JSON response without requiring a receipt.
        if ($desktop && !is_array($data)) {
            try {
                $response = $next($request);
            } catch (Throwable $e) {
                $audit->record($request, null, $e);
                throw $e;
            }
            $audit->record($request, $response);
            return $response;
        }

        $response = null;
        $downstreamException = null;
        $audited = false;
        $desktopLockConnection = null;
        $desktopLockName = null;
        try {
            // Receipts survive rollback; completion evidence shares the DTR transaction.
            if ($desktop) {
                $sync->receipt($request, $data);
            } elseif (!$audit->record($request, null, null, true)) {
                throw new RuntimeException('Attendance receipt could not be stored.');
            }
            if ($desktop && DB::connection()->getDriverName() === 'mysql') {
                $connection = DB::connection();
                $desktopLockName = 'hris:dtr-sync:'.substr(hash('sha256', $connection->getDatabaseName()), 0, 40);
                // ponytail: one desktop sync per database. The legacy controller merges whole dates;
                // use date-scoped locks if upload throughput later requires parallelism.
                // Acquire before starting the transaction so a waiting upload gets a fresh snapshot.
                $lock = $connection->selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$desktopLockName], false);
                if ((int) ($lock->acquired ?? 0) !== 1) {
                    throw new RuntimeException('Desktop sync is busy.');
                }
                $desktopLockConnection = $connection;
            }
            DB::transaction(function () use ($request, $next, $desktop, $audit, $sync, $data,
                &$response, &$downstreamException, &$audited) {
                $punches = $desktop ? $sync->punches($data) : [];
                $before = $desktop ? $sync->snapshot($punches) : [];
                if (!$desktop) {
                    $employee = $audit->attendanceEmployee($request);
                    // Retain serialization of first-row attendance without controller edits.
                    if ($employee && !DB::table('employees')->where('id', $employee->id)->lockForUpdate()->first(['id'])) {
                        throw new RuntimeException('Employee no longer exists.');
                    }
                    $request->attributes->set('audit_attendance_active', true);
                }
                try {
                    $response = $next($request);
                } catch (Throwable $e) {
                    $downstreamException = $e;
                    throw $e;
                }
                // Controllers catch database errors and return 500; roll back those too.
                if ($response->getStatusCode() >= 400) {
                    throw new RuntimeException('Attendance request failed.');
                }
                if ($desktop) {
                    $sync->committed($request, $punches, $before, count($data));
                    $audited = true;
                } elseif (method_exists($response, 'getData') && ($response->getData(true)['updated'] ?? false) === true) {
                    if (!$audit->record($request, $response)) {
                        throw new RuntimeException('Attendance audit could not be stored.');
                    }
                    $audited = true;
                }
            });
        } catch (Throwable $e) {
            $request->attributes->remove('audit_dtr_id');
            $request->attributes->remove('audit_attendance_at');
            if ($downstreamException) {
                $audit->record($request, null, $downstreamException);
                throw $downstreamException;
            }
            if (!$response || $response->getStatusCode() < 400) {
                $response = $desktop
                    ? response()->json(['error' => 'DTR sync failed'], 500)
                    : response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
            }
            $audit->record($request, $response);
            return $response;
        } finally {
            $request->attributes->remove('audit_attendance_active');
            if ($desktopLockConnection) {
                try {
                    // Named locks survive transaction commit/rollback; release on every exit.
                    $desktopLockConnection->selectOne('SELECT RELEASE_LOCK(?)', [$desktopLockName], false);
                } catch (Throwable $e) {
                    // A lost connection must not turn a committed sync into a failed response.
                    $desktopLockConnection->disconnect();
                }
            }
        }

        if (!$audited) $audit->record($request, $response);
        return $response;
    }
}
