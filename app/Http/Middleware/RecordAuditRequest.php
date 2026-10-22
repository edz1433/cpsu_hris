<?php

namespace App\Http\Middleware;

use App\Services\Audit\AuditScope;
use App\Services\Audit\AuditWriter;
use App\Services\Audit\AuditModuleCatalog;
use App\Services\Audit\AuditDatabaseWrites;
use App\Services\Audit\AuditAffectedPerson;
use App\Services\Audit\AuditFlash;
use App\Services\Audit\AuditDtrRecorder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RecordAuditRequest
{
    public function handle(Request $request, Closure $next)
    {
        $request->attributes->set('audit_model_events', 0);
        $request->attributes->set('audit_auth_events', 0);
        $request->attributes->set('audit_model_writes', []);
        $request->attributes->set('audit_sql_writes', []);

        if ($request->isMethod('POST') && $request->is('api/dtrs', 'api/app/log-attendance')) {
            return app(AuditAttendance::class)->handle($request, $next);
        }

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            if (AuditScope::isDtr($request)) {
                app(AuditDtrRecorder::class)->record($request, null, $e);
                throw $e;
            }
            if (!AuditScope::excludes($request)) {
                app(AuditWriter::class)->record([
                    'module' => $this->module($request),
                    'action' => $this->isAuthentication($request) ? 'failed_login' : 'error',
                    'outcome' => 'error',
                    'reason_code' => $e instanceof ValidationException ? 'validation_failed' : 'unhandled_exception',
                    'target_type' => 'route',
                    'target_name' => $this->target($request),
                    'details' => ['exception_type' => class_basename($e)],
                ]);
            }
            throw $e;
        }

        if (AuditScope::isDtr($request)) {
            app(AuditDtrRecorder::class)->record($request, $response);
            return $response;
        }

        if (AuditScope::excludes($request)) {
            return $response;
        }

        if ($this->isAuthentication($request)) {
            if (!(int) $request->attributes->get('audit_auth_events', 0)) {
                $this->recordFailedAuthentication($request, $response);
            }
            return $response;
        }

        $catalog = app(AuditModuleCatalog::class);
        $mutating = !in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true) || $catalog->isMutatingGet($request);
        $failed = $this->failedResponse($request, $response);
        $databaseWrites = app(AuditDatabaseWrites::class)->unmatched($request);
        if ($failed || $databaseWrites || $catalog->isTrackedAccess($request) || ($mutating && !$catalog->isReadOnly($request)
            && !(int) $request->attributes->get('audit_model_events', 0)
            && !(int) $request->attributes->get('audit_auth_events', 0))) {
            $module = $this->module($request);
            $employee = app(AuditAffectedPerson::class)->resolve($request, $module);
            $employeeName = $employee ? trim(implode(' ', array_filter([$employee->fname, $employee->mname, $employee->lname]))) : null;
            app(AuditWriter::class)->record([
                'module' => $module,
                'action' => $failed ? 'request' : ($databaseWrites ? 'write' : ($catalog->isTrackedAccess($request) ? 'accessed' : 'request')),
                'outcome' => $failed ? 'failure' : 'success',
                'reason_code' => $failed ? $this->failureReason($request, $response) : null,
                'target_type' => $employee ? 'employee' : 'route',
                'target_id' => $employee->id ?? null,
                'target_name' => $employeeName ?: $this->target($request),
                'employee' => $employee,
                'details' => [
                    'http_status' => $response->getStatusCode(),
                    'route' => optional($request->route())->getName(),
                    'intent' => $catalog->intent($request),
                    'database_operations' => $databaseWrites,
                ],
            ]);
        }

        return $response;
    }

    private function recordFailedAuthentication(Request $request, Response $response): void
    {
        if (!$this->failedResponse($request, $response)) {
            return;
        }

        $identifier = $request->input('username', $request->input('email'));
        $identifier = is_string($identifier) ? mb_substr(trim($identifier), 0, 255) : null;
        app(AuditWriter::class)->record([
            'module' => 'authentication',
            'action' => 'failed_login',
            'outcome' => 'failure',
            'reason_code' => $this->failureReason($request, $response),
            'target_type' => 'account',
            'target_name' => $identifier ?: 'Account',
            'details' => ['http_status' => $response->getStatusCode()],
        ]);
    }

    private function isAuthentication(Request $request): bool
    {
        return in_array(optional($request->route())->getName(), ['postLogin', 'verify.code'], true)
            || $request->is('auth/google/callback');
    }

    private function failedResponse(Request $request, Response $response): bool
    {
        if ($response->getStatusCode() >= 400) {
            return true;
        }

        if ($response->isRedirection() && $request->hasSession()) {
            return AuditFlash::hasFailure($request);
        }

        if (method_exists($response, 'getData') && str_contains((string) $response->headers->get('Content-Type'), 'application/json')) {
            $data = $response->getData(true);
            return is_array($data) && (($data['success'] ?? null) === false
                || ($data['valid'] ?? null) === false
                || (is_numeric($data['status'] ?? null) && (int) $data['status'] >= 400)
                || isset($data['error']) || !empty($data['errors']));
        }

        return false;
    }

    private function failureReason(Request $request, Response $response): string
    {
        if (AuditFlash::hasFailure($request)) {
            $newFlash = $request->session()->get('_flash.new', []);
            if (in_array('errors', $newFlash, true)) return 'validation_failed';
            $message = strtolower((string) (in_array('error', $newFlash, true)
                ? $request->session()->get('error') : $request->session()->get('error1')));
            if (str_contains($message, 'suspended')) return 'account_suspended';
            if (str_contains($message, 'invalid verification')) return 'invalid_verification_code';
            if (str_contains($message, 'invalid credentials')) return 'invalid_credentials';
            if (str_contains($message, 'permission') || str_contains($message, 'access')) return 'access_denied';
        }

        if (method_exists($response, 'getData')) {
            $data = $response->getData(true);
            if (is_array($data) && is_numeric($data['status'] ?? null) && (int) $data['status'] >= 400) {
                return match ((int) $data['status']) {
                    401 => 'unauthenticated', 403 => 'access_denied', 404 => 'not_found',
                    422 => 'validation_failed', 429 => 'rate_limited',
                    default => (int) $data['status'] >= 500 ? 'server_error' : 'request_rejected',
                };
            }
        }

        return match ($response->getStatusCode()) {
            401 => 'unauthenticated',
            403 => 'access_denied',
            404 => 'not_found',
            422 => 'validation_failed',
            429 => 'rate_limited',
            default => $response->getStatusCode() >= 500 ? 'server_error' : 'request_rejected',
        };
    }

    private function module(Request $request): string
    {
        return app(AuditModuleCatalog::class)->module($request);
    }

    private function target(Request $request): string
    {
        $route = optional($request->route())->getName();
        return $route ? str_replace(['.', '-', '_'], ' ', $route) : 'unmatched route';
    }

}
