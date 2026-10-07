<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractPeriodRequest;
use App\Models\ContractPeriod;
use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Http\Request;

class ContractPeriodController extends Controller
{
    public function __construct()
    {
        // Shares the navbar notification data the layout needs.
        parent::__construct();

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

    public function index(Request $request)
    {
        $guard = $this->getGuard();
        $title = 'Contracts';
        $types = config('contracts.types');
        $filters = $request->only(['type', 'status', 'year']);

        $periods = ContractPeriod::query()
            ->withCount([
                'contracts as employees_count' => fn ($q) => $q->where('status', '!=', EmployeeContract::STATUS_CANCELLED),
                'contracts as signed_count' => fn ($q) => $q->where('status', EmployeeContract::STATUS_SIGNED),
            ])
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('contract_type', $type))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['year'] ?? null, fn ($q, $year) => $q->whereYear('start_date', $year))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        $years = ContractPeriod::selectRaw('DISTINCT YEAR(start_date) as y')->orderByDesc('y')->pluck('y');

        return view('contracts.index', compact('guard', 'title', 'types', 'filters', 'periods', 'years'));
    }

    public function store(StoreContractPeriodRequest $request)
    {
        $data = $request->validated();
        $title = trim($data['title'] ?? '') ?: ContractPeriod::suggestTitle($data['contract_type'], $data['start_date'], $data['end_date']);
        $overlaps = ContractPeriod::overlapping($data['contract_type'], $data['start_date'], $data['end_date']);

        $period = ContractPeriod::create([
            'contract_type' => $data['contract_type'],
            'title'         => $title,
            'start_date'    => $data['start_date'],
            'end_date'      => $data['end_date'],
            'status'        => ContractPeriod::STATUS_OPEN,
            'created_by'    => auth()->guard('web')->id(),
            'updated_by'    => auth()->guard('web')->id(),
        ]);

        return $this->withOverlapWarning(
            redirect()->route('contracts.show', $period)->with('success', 'Contract period created.'),
            $overlaps
        );
    }

    public function show(ContractPeriod $period)
    {
        $guard = $this->getGuard();
        $title = $period->title;
        $contracts = $period->contracts()->orderBy('employee_name')->get();
        $campuses = EmployeeContract::campuses();

        $employees = collect();
        if ($period->isOpen()) {
            $employees = Employee::query()
                ->select(['id', 'fname', 'mname', 'lname', 'suffix', 'position', 'emp_ID', 'camp_id'])
                ->where('stat_1', 1)
                ->whereIn('emp_status', $period->typeConfig()['employee_statuses'] ?? [])
                ->whereNotIn('id', $contracts->pluck('employee_id'))
                ->orderBy('lname')
                ->orderBy('fname')
                ->get();
        }

        $daysPerMonth = config('contracts.working_days_per_month', 22);

        return view('contracts.show', compact('guard', 'title', 'period', 'contracts', 'campuses', 'employees', 'daysPerMonth'));
    }

    /** Edit title and dates. Allowed while open and nothing is signed yet. */
    public function update(StoreContractPeriodRequest $request, ContractPeriod $period)
    {
        if (!$period->isOpen()) {
            return back()->with('error', 'This period is closed.');
        }
        if ($period->contracts()->where('status', EmployeeContract::STATUS_SIGNED)->exists()) {
            return back()->with('error', 'Dates cannot be changed once a contract in this period is signed.');
        }

        $data = $request->validated();
        $newYear = date('Y', strtotime($data['start_date']));
        if ($newYear !== $period->start_date->format('Y') && $period->contracts()->withTrashed()->exists()) {
            return back()->with('error', 'The start year cannot change after employees are added, because it is part of their reference numbers.');
        }

        $period->update([
            'title'      => trim($data['title'] ?? '') ?: ContractPeriod::suggestTitle($period->contract_type, $data['start_date'], $data['end_date']),
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'updated_by' => auth()->guard('web')->id(),
        ]);

        return $this->withOverlapWarning(
            back()->with('success', 'Contract period updated.'),
            ContractPeriod::overlapping($period->contract_type, $data['start_date'], $data['end_date'], $period->id)
        );
    }

    /** Closing locks every edit in the period; downloads keep working. */
    public function close(ContractPeriod $period)
    {
        if ($period->isOpen()) {
            $period->update(['status' => ContractPeriod::STATUS_CLOSED, 'updated_by' => auth()->guard('web')->id()]);
        }

        return back()->with('success', 'Contract period closed.');
    }

    protected function withOverlapWarning($redirect, $overlaps)
    {
        if ($overlaps->isNotEmpty()) {
            $redirect->with('warning', 'This period overlaps with another open period of the same type: ' . $overlaps->pluck('title')->implode('; ') . '.');
        }

        return $redirect;
    }
}
