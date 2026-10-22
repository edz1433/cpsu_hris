<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Employee;
use App\Models\Fdevice;
use App\Models\Office;
use App\Services\Audit\AuditScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::guard('web')->check() && Auth::guard('web')->user()->role === 'Administrator', 403);

        $filters = $request->validate([
            'actor' => ['nullable', 'string', 'max:255'],
            'person' => ['nullable', 'string', 'max:255'],
            'module' => ['nullable', 'string', 'max:60'],
            'kiosk_id' => ['nullable', 'string', 'regex:/^-?\d{1,10}$/'],
            'campus_id' => ['nullable', 'integer', 'min:1'],
            'office_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'date_range' => ['nullable', Rule::in(['today', 'yesterday', 'last_7_days', 'last_30_days', 'this_month', 'last_month'])],
            'per_page' => ['nullable', Rule::in(['25', '50', '100'])],
            'cursor' => ['nullable', 'string', 'max:2000'],
            'registration_source' => ['nullable', Rule::in(['desktop', 'android'])],
            'registration_enroller' => ['nullable', 'string', 'max:100'],
            'registration_person' => ['nullable', 'string', 'max:100'],
            'registration_kiosk' => ['nullable', 'string', 'regex:/^-?\d{1,10}$/'],
            'registration_from' => ['nullable', 'date_format:Y-m-d'],
            'registration_to' => ['nullable', 'date_format:Y-m-d'],
            'registration_page' => ['nullable', 'integer', 'min:1'],
        ]);

        if (!empty($filters['date_from']) && !empty($filters['date_to']) && $filters['date_to'] < $filters['date_from']) {
            throw ValidationException::withMessages(['date_to' => 'The end date must be on or after the start date.']);
        }

        if (!empty($filters['registration_from']) && !empty($filters['registration_to'])
            && $filters['registration_to'] < $filters['registration_from']) {
            throw ValidationException::withMessages(['registration_to' => 'The registration end date must be on or after the start date.']);
        }

        $auditTable = DB::table('audit_logs');
        $query = clone $auditTable;
        // Hide successful routine reads; failures must stay visible, including older entries.
        $query->where(function ($query) {
            $query->where('module', '!=', 'dtr')->orWhereNotIn('action', AuditScope::DTR_UTILITY_ACTIONS)
                ->orWhere('outcome', '!=', 'success');
        });
        // A receipt is useful only while its completion is missing.
        $query->where(function ($query) {
            $query->where('outcome', '!=', 'received')->orWhereNull('request_id')
                ->orWhereNotExists(function ($completion) {
                    $completion->selectRaw('1')->from('audit_logs as completion')
                        ->whereColumn('completion.request_id', 'audit_logs.request_id')
                        ->whereColumn('completion.module', 'audit_logs.module')
                        ->where('completion.outcome', '!=', 'received');
                });
        });

        if (!empty($filters['actor'])) {
            if ($filters['actor'] === '__system__') {
                $query->whereNull('actor_name');
            } else {
                $query->where('actor_name', $filters['actor']);
            }
        }
        if (!empty($filters['person'])) {
            $query->where('employee_code', $filters['person']);
        }
        if (isset($filters['kiosk_id']) && $filters['kiosk_id'] !== '') {
            $this->filterKiosk($query, $filters['kiosk_id']);
        }
        if (!empty($filters['module'])) {
            $tardinessRoutes = ['readTiredness', 'tirednessSearch', 'pdfTirednes'];
            if ($filters['module'] === 'tardiness') {
                $query->where(function ($query) use ($tardinessRoutes) {
                    $query->where('module', 'tardiness')
                        ->orWhere(function ($query) use ($tardinessRoutes) {
                            $query->where('module', 'dtr')->whereIn('route_name', $tardinessRoutes);
                        });
                });
            } elseif ($filters['module'] === 'dtr') {
                $query->where('module', 'dtr')->where(function ($query) use ($tardinessRoutes) {
                    $query->whereNull('route_name')->orWhereNotIn('route_name', $tardinessRoutes);
                });
            } else {
                $query->where('module', $filters['module']);
            }
        }
        foreach (['campus_id', 'office_id'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }
        if (!empty($filters['date_range'])) {
            $today = Carbon::today(config('app.timezone'));
            [$from, $to] = match ($filters['date_range']) {
                'today' => [$today->copy(), $today->copy()],
                'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()],
                'last_7_days' => [$today->copy()->subDays(6), $today->copy()],
                'last_30_days' => [$today->copy()->subDays(29), $today->copy()],
                'this_month' => [$today->copy()->startOfMonth(), $today->copy()],
                'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            };
            $this->filterDate($query, '>=', $from->startOfDay()->format('Y-m-d H:i:s'));
            $this->filterDate($query, '<', $to->addDay()->startOfDay()->format('Y-m-d H:i:s'));
        } elseif (!empty($filters['date_from'])) {
            $this->filterDate($query, '>=', $filters['date_from'].' 00:00:00');
        }
        if (empty($filters['date_range']) && !empty($filters['date_to'])) {
            $this->filterDate($query, '<', Carbon::parse($filters['date_to'])->addDay()->format('Y-m-d').' 00:00:00');
        }

        $logs = $query->orderByDesc('occurred_at')->orderByDesc('id')
            ->cursorPaginate((int) ($filters['per_page'] ?? 25))
            ->appends(collect($filters)->except('cursor')->all());

        // Only resolve IDs on this page; HR Kiosk IDs are f_devices.id, not reader serials.
        $kioskIds = collect($logs->items())->flatMap(function ($log) {
            $details = json_decode($log->details ?: '{}', true);
            if (!is_array($details) || $log->module !== 'dtr' || ($details['source'] ?? null) !== 'desktop_sync') return [];
            return array_merge(is_array($details['submitted_device_ids'] ?? null) ? $details['submitted_device_ids'] : [],
                [$details['submitted_device_id'] ?? null, $details['device_id'] ?? null]);
        })->push($filters['kiosk_id'] ?? null)
            ->filter(fn ($id) => is_scalar($id) && preg_match('/^-?\d{1,10}$/', (string) $id))
            ->unique()->values()->all();
        // Time Entry uses the same signed ID convention as fetchLatestLogs.
        $timeEntryIds = collect($logs->items())->flatMap(function ($log) {
            $details = json_decode($log->details ?: '{}', true);
            $id = is_array($details) ? ($details['zone_id'] ?? null) : null;
            if (!is_array($details) || $log->module !== 'dtr' || ($details['source'] ?? null) === 'desktop_sync'
                || !is_scalar($id) || !preg_match('/^-?\d{1,10}$/', (string) $id)) return [];
            return [(string) (int) $id];
        })->unique()->values()->all();
        $deviceIds = array_unique(array_merge($kioskIds, array_filter($timeEntryIds, fn ($id) => (int) $id > 0)));
        $deviceLabels = $deviceIds ? Fdevice::whereIn('id', $deviceIds)->pluck('label', 'id') : collect();
        $kioskLabels = $deviceLabels->map(fn ($label, $id) => (trim((string) $label) ?: 'HR Kiosk'));
        $zoneIds = array_filter($timeEntryIds, fn ($id) => (int) $id <= 0);
        $zoneLabels = $zoneIds ? DB::table('logzones')->whereIn('id', $zoneIds)->pluck('label', 'id') : collect();
        $timeEntryLabels = $deviceLabels->only($timeEntryIds)->union($zoneLabels)
            ->map(fn ($label, $id) => (trim((string) $label) ?: 'Time Entry zone'));

        // Latest upload evidence is independent of employee/date filters, not a connectivity check.
        $latestKioskUpload = null;
        if (isset($filters['kiosk_id']) && $filters['kiosk_id'] !== '') {
            $latestQuery = DB::table('audit_logs');
            $this->filterKiosk($latestQuery, $filters['kiosk_id']);
            $latestKioskUpload = $latestQuery->orderByDesc('occurred_at')->orderByDesc('id')
                ->first(['id', 'occurred_at', 'action', 'outcome', 'reason_code', 'details']);
        }

        return view('audit-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'kioskLabels' => $kioskLabels,
            'timeEntryLabels' => $timeEntryLabels,
            'latestKioskUpload' => $latestKioskUpload,
            'registrationHistory' => $this->registrationHistory($filters),
            'modules' => collect(config('audit.modules', []))->values()
                ->merge(['dtr', 'tardiness', 'system', 'authentication', $filters['module'] ?? null])
                ->filter()->unique()->sort()->values(),
            'campuses' => Campus::orderBy('name')->get(['id', 'name']),
            'offices' => Office::orderBy('office_name')->get(['id', 'office_name']),
        ]);
    }

    private function registrationHistory(array $filters): array
    {
        $query = null;
        $unavailable = [];
        foreach (['desktop' => 'emp_hrkiosk_reghist', 'android' => 'emp_timeentry_reghist'] as $source => $table) {
            if (!empty($filters['registration_source']) && $filters['registration_source'] !== $source) continue;
            if (isset($filters['registration_kiosk']) && $filters['registration_kiosk'] !== '' && $source !== 'desktop') continue;
            if (!Schema::hasTable($table)) {
                $unavailable[] = $source === 'desktop' ? 'Desktop' : 'Android';
                continue;
            }
            $history = DB::table($table)->select('id', 'date', 'enroller_ID', 'enrolled_IDs', 'enrolled_times')
                ->addSelect(DB::raw("'".$source."' as source"),
                    $source === 'desktop' ? 'enroller_device_ID as devices' : DB::raw('NULL as devices'));
            if (!empty($filters['registration_enroller'])) $history->where('enroller_ID', $filters['registration_enroller']);
            if (!empty($filters['registration_from'])) $history->where('date', '>=', $filters['registration_from']);
            if (!empty($filters['registration_to'])) $history->where('date', '<=', $filters['registration_to']);
            if (!empty($filters['registration_person'])) {
                $code = $filters['registration_person'];
                $tokens = $source === 'desktop' ? [$code, $code.'-1', $code.'-2', $code.'-3'] : [$code];
                $this->filterRegistrationTokens($history, 'enrolled_IDs', $tokens);
            }
            if (isset($filters['registration_kiosk']) && $filters['registration_kiosk'] !== '') {
                $this->filterRegistrationTokens($history, 'enroller_device_ID', [$filters['registration_kiosk']]);
            }
            $query = $query ? $query->unionAll($history) : $history;
        }
        if (!$query) return ['batches' => null, 'people' => collect(), 'kiosks' => collect(), 'unavailable' => $unavailable];

        // Paginate stored day/enroller batches; never load the entire registration history.
        $batches = DB::query()->fromSub($query, 'registration_history')
            ->orderByDesc('date')->orderByDesc('id')->orderBy('source')
            ->simplePaginate(10, ['*'], 'registration_page', (int) ($filters['registration_page'] ?? 1))
            ->appends(collect($filters)->except('registration_page')->all())->fragment('registration-history');
        $codes = [];
        $devices = [];
        foreach ($batches as $batch) {
            $codes[] = $batch->enroller_ID;
            $times = explode(',', (string) $batch->enrolled_times);
            $kiosks = explode(',', (string) $batch->devices);
            $batch->registrations = [];
            foreach (explode(',', (string) $batch->enrolled_IDs) as $index => $token) {
                $token = trim($token);
                if ($token === '') continue;
                $code = $token;
                $finger = null;
                if ($batch->source === 'desktop' && preg_match('/^(.+)-([123])$/', $token, $match)) {
                    $code = $match[1];
                    $finger = $match[2];
                }
                $device = trim($kiosks[$index] ?? '');
                $device = preg_match('/^-?\d{1,10}$/', $device) ? $device : null;
                // Keep the same list position so filters cannot expose unrelated entries in a batch.
                if (!empty($filters['registration_person']) && strcasecmp($code, $filters['registration_person']) !== 0) continue;
                if (isset($filters['registration_kiosk']) && $filters['registration_kiosk'] !== ''
                    && $device !== $filters['registration_kiosk']) continue;
                $time = trim($times[$index] ?? '');
                $batch->registrations[] = ['code' => $code, 'finger' => $finger, 'device' => $device,
                    'time' => preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $time) ? $time : null];
                $codes[] = $code;
                if ($device !== null) $devices[] = $device;
            }
        }
        $batches->setCollection($batches->getCollection()->filter(fn ($batch) => count($batch->registrations) > 0)->values());
        $people = $codes ? Employee::whereIn('emp_ID', array_unique($codes))
            ->get(['emp_ID', 'fname', 'mname', 'lname'])->mapWithKeys(function ($employee) {
                $name = trim(implode(' ', array_filter([$employee->fname, $employee->mname, $employee->lname])));
                return [mb_strtolower((string) $employee->emp_ID) => $name ?: (string) $employee->emp_ID];
            }) : collect();
        if (isset($filters['registration_kiosk']) && $filters['registration_kiosk'] !== '') $devices[] = $filters['registration_kiosk'];
        $labels = $devices ? Fdevice::whereIn('id', array_unique($devices))->pluck('label', 'id') : collect();
        return ['batches' => $batches, 'people' => $people, 'kiosks' => $labels, 'unavailable' => $unavailable];
    }

    private function filterRegistrationTokens($query, string $field, array $tokens): void
    {
        $query->where(function ($query) use ($field, $tokens) {
            foreach ($tokens as $token) {
                $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $token);
                $query->orWhere($field, $token)
                    ->orWhereRaw($field." LIKE ? ESCAPE '!'", [$escaped.',%'])
                    ->orWhereRaw($field." LIKE ? ESCAPE '!'", ['%,'.$escaped])
                    ->orWhereRaw($field." LIKE ? ESCAPE '!'", ['%,'.$escaped.',%']);
            }
        });
    }

    private function filterKiosk($query, string $id): void
    {
        $query->where('module', 'dtr')->where('details->source', 'desktop_sync')
            ->whereIn('action', ['time_in', 'time_out', 'overtime'])
            ->where(function ($query) use ($id) {
                $query->where('details->submitted_device_id', $id);
                if ($query->getConnection()->getDriverName() === 'sqlite') {
                    $query->orWhereRaw("EXISTS (SELECT 1 FROM json_each(audit_logs.details, '$.submitted_device_ids') WHERE value = ?)", [$id]);
                } else {
                    $query->orWhereJsonContains('details->submitted_device_ids', $id);
                }
            });
    }

    private function filterDate($query, string $operator, string $value): void
    {
        $query->where(function ($query) use ($operator, $value) {
            $query->where('details->attendance_at', $operator, $value)
                ->orWhere(function ($query) use ($operator, $value) {
                    $query->whereNull('details->attendance_at')->where('occurred_at', $operator, $value);
                });
        });
    }

    public function options(Request $request)
    {
        abort_unless(Auth::guard('web')->check() && Auth::guard('web')->user()->role === 'Administrator', 403);

        $input = $request->validate([
            'type' => ['required', Rule::in(['actor', 'person', 'kiosk'])],
            'q' => ['nullable', 'string', 'max:80'],
        ]);
        $search = trim((string) ($input['q'] ?? ''));
        $prefix = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
        if ($input['type'] === 'kiosk') {
            $devices = Fdevice::query()->where(function ($query) use ($prefix, $search) {
                $query->whereRaw("label LIKE ? ESCAPE '!'", [$prefix]);
                if (preg_match('/^-?\d{1,10}$/', $search)) $query->orWhere('id', $search);
            })->orderBy('label')->orderBy('id')->limit(20)->get(['id', 'label']);
            $results = $devices->map(fn ($device) => [
                'id' => (string) $device->id,
                'text' => (trim((string) $device->label) ?: 'HR Kiosk'),
            ]);
            // Allow searching a historical ID whose registry entry has been removed.
            if (preg_match('/^-?\d{1,10}$/', $search) && !$devices->contains(fn ($device) => (string) $device->id === $search)) {
                $results->prepend(['id' => $search, 'text' => 'HR Kiosk (name unavailable)']);
            }
            return response()->json(['results' => $results->take(20)->values()]);
        }
        if ($input['type'] === 'actor') {
            $names = DB::table('audit_logs')
                ->whereRaw("actor_name LIKE ? ESCAPE '!'", [$prefix])->whereNotNull('actor_name')
                ->distinct()->orderBy('actor_name')->limit(20)->pluck('actor_name');

            $results = $names->map(fn ($name) => [
                'id' => $name, 'text' => $name,
            ]);
            if ($search === '' || str_starts_with('system', mb_strtolower($search))) {
                $results->prepend(['id' => '__system__', 'text' => 'System / unknown']);
            }
            return response()->json(['results' => $results->take(20)->values()]);
        }

        if ($search === '') {
            return response()->json(['results' => []]);
        }

        $employees = Employee::query()->where(function ($query) use ($prefix) {
            $query->whereRaw("emp_ID LIKE ? ESCAPE '!'", [$prefix])
                ->orWhereRaw("fname LIKE ? ESCAPE '!'", [$prefix])
                ->orWhereRaw("lname LIKE ? ESCAPE '!'", [$prefix]);
        })->orderBy('lname')->limit(20)->get(['emp_ID', 'fname', 'mname', 'lname']);
        $results = $employees->mapWithKeys(function ($employee) {
            $name = trim(implode(' ', array_filter([$employee->fname, $employee->mname, $employee->lname])));
            return [(string) $employee->emp_ID => [
                'id' => (string) $employee->emp_ID,
                'text' => $name !== '' ? $name.' · '.$employee->emp_ID : (string) $employee->emp_ID,
            ]];
        });

        if ($results->count() < 20) {
            $codes = DB::table('audit_logs')
                ->whereRaw("employee_code LIKE ? ESCAPE '!'", [$prefix])->whereNotNull('employee_code')
                ->distinct()->orderBy('employee_code')->limit(20)->pluck('employee_code');
            foreach ($codes as $code) {
                if (!$results->has($code)) {
                    $results->put($code, ['id' => $code, 'text' => $code]);
                }
                if ($results->count() >= 20) break;
            }
        }

        return response()->json(['results' => $results->values()]);
    }
}
