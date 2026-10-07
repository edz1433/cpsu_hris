<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddEmployeesToPeriodRequest;
use App\Models\ContractPeriod;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Services\Contracts\ContractGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeContractController extends Controller
{
    protected $generator;

    public function __construct(ContractGenerator $generator)
    {
        // Shares the navbar notification data the layout needs.
        parent::__construct();
        $this->generator = $generator;

        $this->middleware(function ($request, $next) {
            $user = auth()->guard('web')->user();
            // Granted per account in User Management (Administrators always have it).
            abort_unless($this->getGuard() === 'web' && $user && $user->canAccessPage('contracts'), 403);

            return $next($request);
        });
    }

    public function getGuard()
    {
        if (\Auth::guard('web')->check()) {
            return 'web';
        } elseif (\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    /** Add one contract per selected employee (AJAX). */
    public function store(AddEmployeesToPeriodRequest $request, ContractPeriod $period)
    {
        if (!$period->isOpen()) {
            return response()->json(['message' => 'This period is closed.'], 422);
        }

        $data = $request->validated();
        $ids = array_map('intval', $data['employee_ids']);

        if (!$request->boolean('confirm_overlap')) {
            $conflicts = EmployeeContract::with('period')
                ->whereIn('employee_id', $ids)
                ->where('contract_period_id', '!=', $period->id)
                ->where('status', '!=', EmployeeContract::STATUS_CANCELLED)
                ->whereHas('period', fn ($q) => $q
                    ->whereDate('start_date', '<=', $period->end_date)
                    ->whereDate('end_date', '>=', $period->start_date))
                ->get();

            if ($conflicts->isNotEmpty()) {
                return response()->json([
                    'needs_confirmation' => true,
                    'conflicts' => $conflicts->map(fn ($c) => $c->employee_name . ' — ' . $c->period->title)->values(),
                ], 409);
            }
        }

        // Read-only select from employees; nothing is written back.
        $employees = Employee::query()
            ->select(['id', 'fname', 'mname', 'lname', 'suffix', 'camp_id'])
            ->whereIn('id', $ids)
            ->orderBy('lname')
            ->orderBy('fname')
            ->get();
        $campuses = EmployeeContract::campuses();
        $userId = auth()->guard('web')->id();

        try {
            DB::connection('mysql')->transaction(function () use ($employees, $campuses, $period, $data, $userId) {
                foreach ($employees as $employee) {
                    $attributes = [
                        'campus_id'       => $employee->camp_id,
                        'employee_name'   => EmployeeContract::formatEmployeeName($employee),
                        'position'        => trim($data['position']),
                        'monthly_rate'    => round($data['monthly_rate'], 2),
                        'daily_deduction' => round($data['daily_deduction'], 2),
                        'status'          => EmployeeContract::STATUS_ACTIVE,
                        'updated_by'      => $userId,
                    ];

                    // A row removed earlier is restored so its reference number is never reissued.
                    $removed = EmployeeContract::onlyTrashed()
                        ->where('contract_period_id', $period->id)
                        ->where('employee_id', $employee->id)
                        ->lockForUpdate()
                        ->first();

                    if ($removed) {
                        $removed->fill($attributes);
                        $removed->restore();
                        continue;
                    }

                    EmployeeContract::create($attributes + [
                        'contract_period_id' => $period->id,
                        'employee_id'        => $employee->id,
                        'reference_no'       => $this->generator->nextReferenceNo($period, $campuses->get($employee->camp_id)),
                        'created_by'         => $userId,
                    ]);
                }
            });
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23000') {
                return response()->json(['message' => 'One of the selected employees was just added by someone else. Reload and try again.'], 422);
            }
            throw $e;
        }

        $count = $employees->count();
        session()->flash('success', $count . ' ' . Str::plural('employee', $count) . ' added.');

        return response()->json(['added' => $count]);
    }

    public function update(Request $request, EmployeeContract $contract)
    {
        if ($error = $this->lockedReason($contract, [EmployeeContract::STATUS_ACTIVE])) {
            return back()->with('error', $error);
        }

        $data = $request->validate([
            'position'        => ['required', 'string', 'max:255'],
            'monthly_rate'    => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999'],
            'daily_deduction' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999'],
        ]);

        $contract->update([
            'position'        => trim($data['position']),
            'monthly_rate'    => round($data['monthly_rate'], 2),
            'daily_deduction' => round($data['daily_deduction'], 2),
            'updated_by'      => auth()->guard('web')->id(),
        ]);

        return back()->with('success', 'Contract updated.');
    }

    public function sign(EmployeeContract $contract)
    {
        return $this->setStatus($contract, EmployeeContract::STATUS_SIGNED, [EmployeeContract::STATUS_ACTIVE], 'Contract marked as signed.');
    }

    public function cancel(EmployeeContract $contract)
    {
        return $this->setStatus($contract, EmployeeContract::STATUS_CANCELLED, [EmployeeContract::STATUS_ACTIVE, EmployeeContract::STATUS_SIGNED], 'Contract cancelled.');
    }

    public function destroy(EmployeeContract $contract)
    {
        if ($error = $this->lockedReason($contract, [EmployeeContract::STATUS_ACTIVE, EmployeeContract::STATUS_CANCELLED])) {
            return back()->with('error', $error);
        }

        $contract->update(['updated_by' => auth()->guard('web')->id()]);
        $contract->delete();

        return back()->with('success', 'Employee removed from this period.');
    }

    public function download(EmployeeContract $contract)
    {
        $dir = $this->generator->makeWorkDir();
        $path = $this->generator->generate($contract, $dir);

        return response()->download($path, basename($path))->deleteFileAfterSend(true);
    }

    /** ZIP of every active and signed contract in the period. */
    public function downloadAll(ContractPeriod $period)
    {
        $contracts = $period->contracts()
            ->whereIn('status', [EmployeeContract::STATUS_ACTIVE, EmployeeContract::STATUS_SIGNED])
            ->orderBy('employee_name')
            ->get();

        return $this->zipResponse($period, $contracts, '');
    }

    public function downloadSelected(Request $request, ContractPeriod $period)
    {
        $ids = $request->validate([
            'contract_ids'   => ['required', 'array', 'min:1'],
            'contract_ids.*' => ['integer'],
        ], ['contract_ids.required' => 'Select at least one contract.'])['contract_ids'];

        $contracts = $period->contracts()->whereIn('id', $ids)->orderBy('employee_name')->get();

        return $this->zipResponse($period, $contracts, '-selected');
    }

    protected function zipResponse(ContractPeriod $period, $contracts, string $suffix)
    {
        if ($contracts->isEmpty()) {
            return back()->with('error', 'There are no contracts to download.');
        }

        $contracts->each->setRelation('period', $period);
        $dir = $this->generator->makeWorkDir();
        $zipName = (Str::slug($period->title) ?: 'contracts') . $suffix . '.zip';
        $path = $this->generator->zip($contracts, $dir, $zipName);

        return response()->download($path, $zipName)->deleteFileAfterSend(true);
    }

    protected function setStatus(EmployeeContract $contract, string $status, array $from, string $message)
    {
        if ($error = $this->lockedReason($contract, $from)) {
            return back()->with('error', $error);
        }

        $contract->update(['status' => $status, 'updated_by' => auth()->guard('web')->id()]);

        return back()->with('success', $message);
    }

    /** Why this contract can't be changed, or null if it can. */
    protected function lockedReason(EmployeeContract $contract, array $allowedStatuses): ?string
    {
        if (!$contract->period || !$contract->period->isOpen()) {
            return 'This period is closed. Contracts can still be downloaded but not changed.';
        }
        if (!in_array($contract->status, $allowedStatuses, true)) {
            return 'This action is not allowed for a ' . $contract->status . ' contract.';
        }

        return null;
    }
}
