@extends('layouts.master')

@section('body')
@php
    $current_route = request()->route()->getName();
    $isEdit = $current_route == 'uEdit';
    $pages = \App\Models\User::PAGE_ACCESS;
    $pageHints = [
        'employees' => 'Employee list, records and PDS',
        'offices' => 'Offices and their heads',
        'payslip' => 'Payslip page',
        'events' => 'Events calendar and attendance reports',
        'dtr' => 'DTR, time logs and tardiness',
        'spms' => 'Performance ratings and documents',
        'settings' => 'System settings and maintenance mode',
        'leave' => 'Leave credits, approvals and history',
        'kiosk' => 'HR kiosk app',
        'contracts' => 'Contract of service periods',
    ];
    $editAccess = $isEdit ? array_map('trim', explode(',', (string) $uEdit->access)) : [];
    $editUser = $isEdit ? $uEdit : null;
    $oldAccess = old('access');
    $isChecked = function ($index) use ($oldAccess, $editAccess, $editUser, $pages) {
        if (is_array($oldAccess)) {
            return array_key_exists($index, $oldAccess);
        }
        // Not saved yet for this account (e.g. Contracts on older accounts): show
        // what it can open today, so saving the form doesn't quietly remove it.
        if ($editUser && !array_key_exists($index, $editAccess)) {
            $key = array_search($index, array_map(fn ($page) => $page[0], $pages), true);
            return $key !== false && $editUser->canAccessPage($key);
        }
        return ($editAccess[$index] ?? '0') === '1';
    };
    $field = fn ($name) => old($name, $isEdit ? $uEdit->$name : '');
    $roles = ['Administrator', 'HR Administrator', 'Payroll Administrator'];
    $roleTone = ['Administrator' => 'is-start', 'HR Administrator' => 'is-added', 'Payroll Administrator' => 'is-info'];
@endphp
<div class="container-fluid dash">
    <div class="dtr-head">
        <div>
            <h1>User Management</h1>
            <p>Admin-side accounts and the pages each one can open.</p>
        </div>
        @if($isEdit)
            <a href="{{ route('ulist') }}" class="lv-btn"><i class="fas fa-plus"></i> New user</a>
        @endif
    </div>

    <div class="row">
        <div class="col-xl-4 col-lg-5">
            <div class="dash-card {{ $isEdit ? 'um-editing' : '' }}">
                <div class="dash-card-header">
                    <h5>
                        <i class="fas {{ $isEdit ? 'fa-user-edit' : 'fa-user-plus' }}" style="color: var(--cpsu-green-600);"></i>
                        {{ $isEdit ? 'Edit user' : 'New user' }}
                    </h5>
                    @if($isEdit)
                        <a href="{{ route('ulist') }}" class="dash-card-hint">Cancel</a>
                    @endif
                </div>
                <div class="dash-card-body">
                    <form class="dtr-form" id="userForm" action="{{ $isEdit ? route('uUpdate') : route('uCreate') }}" method="POST">
                        @csrf
                        <input type="hidden" name="uid" value="{{ $isEdit ? $uEdit->id : '' }}">

                        <div class="form-row">
                            <div class="col-sm-6 dtr-field">
                                <label class="dtr-label" for="u_lname">Last name</label>
                                <input type="text" name="lname" id="u_lname" value="{{ $field('lname') }}" oninput="this.value = this.value.toUpperCase()" class="form-control" autocomplete="off" required>
                            </div>
                            <div class="col-sm-6 dtr-field">
                                <label class="dtr-label" for="u_fname">First name</label>
                                <input type="text" name="fname" id="u_fname" value="{{ $field('fname') }}" class="form-control" autocomplete="off" required>
                            </div>
                            <div class="col-sm-6 dtr-field">
                                <label class="dtr-label" for="u_mname">Middle name</label>
                                <input type="text" name="mname" id="u_mname" value="{{ $field('mname') }}" oninput="this.value = this.value.toUpperCase()" class="form-control" autocomplete="off" required>
                            </div>
                            <div class="col-sm-6 dtr-field">
                                <label class="dtr-label" for="u_gender">Gender</label>
                                <select name="gender" id="u_gender" class="form-control" required>
                                    <option value="">Select</option>
                                    @foreach(['Male', 'Female'] as $gender)
                                        <option value="{{ $gender }}" @if($field('gender') == $gender) selected @endif>{{ $gender }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="dtr-field">
                            <label class="dtr-label" for="u_campus">Campus</label>
                            <select class="form-control select2" name="campus_id" id="u_campus" style="width: 100%;" required>
                                <option value="">Select campus</option>
                                @foreach ($camp as $cp)
                                    <option value="{{ $cp->id }}" @if($field('campus_id') == $cp->id) selected @endif>{{ $cp->campus_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="dtr-field">
                            <label class="dtr-label" for="roleSelect">Role</label>
                            <select class="form-control" name="role" id="roleSelect" onchange="updateCheckboxes()" required>
                                <option value="">Select role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role }}" @if($field('role') == $role) selected @endif>{{ $role }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="dtr-field">
                            <label class="dtr-label" for="u_username">Username (Google email)</label>
                            <input type="email" name="username" id="u_username" value="{{ $field('username') }}" placeholder="name@cpsu.edu.ph" class="form-control" autocomplete="off" required>
                        </div>

                        <div class="dtr-field">
                            <div class="d-flex align-items-baseline justify-content-between">
                                <span class="dtr-label">Pages this user can open</span>
                                <button type="button" class="um-link" id="toggleAllAccess">Select all</button>
                            </div>
                            <p class="um-admin-note" id="adminNote">Administrators can open every page, whatever is ticked here.</p>
                            <div class="um-access">
                                @foreach($pages as $key => [$index, $label])
                                    @if($key === 'settings')
                                        {{-- Administrators only; keep any saved flag as it is. --}}
                                        <label class="um-access-item is-locked" title="Only Administrators can open System Settings">
                                            <input type="checkbox" disabled>
                                            @if($isChecked($index))<input type="hidden" name="access[{{ $index }}]" value="1">@endif
                                            <span>
                                                <b>{{ $label }}</b>
                                                <small>Administrators only</small>
                                            </span>
                                        </label>
                                        @continue
                                    @endif
                                    <label class="um-access-item">
                                        <input type="checkbox" name="access[{{ $index }}]" value="1" id="access{{ $index }}" {{ $isChecked($index) ? 'checked' : '' }}>
                                        <span>
                                            <b>{{ $label }}</b>
                                            <small>{{ $pageHints[$key] ?? '' }}</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" name="btn-submit" class="dtr-generate w-100">
                            <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Save changes' : 'Create user' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5><i class="fas fa-users-cog" style="color: var(--cpsu-green-600);"></i>Accounts</h5>
                    <span class="dash-card-hint">{{ count($users) }} {{ count($users) == 1 ? 'user' : 'users' }}</span>
                </div>
                <div class="dash-card-body">
                    <div class="table-responsive">
                        <table id="example1" class="table table-hover lv-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Campus</th>
                                    <th>Role</th>
                                    <th>Pages</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tbody">
                                @foreach($users as $user)
                                @php
                                    // What the account can actually open, same rule the menu and routes use.
                                    $granted = collect($pages)->filter(fn ($page, $key) => $user->canAccessPage($key))->map(fn ($page) => $page[1]);
                                    $isRowEdited = $isEdit && $uEdit->id == $user->uid;
                                @endphp
                                <tr id="tr-{{ $user->uid }}" class="{{ $isRowEdited ? 'um-row-active' : '' }}">
                                    <td>
                                        <div class="um-name">{{ strtoupper($user->lname) }}, {{ strtoupper($user->fname) }} {{ strtoupper($user->mname) }}</div>
                                        <div class="um-username">{{ $user->username }}</div>
                                    </td>
                                    <td class="{{ $user->campus_name ? '' : 'lv-muted' }}">{{ $user->campus_name ?: '—' }}</td>
                                    <td><span class="lv-pill {{ $roleTone[$user->role] ?? 'is-info' }}">{{ $user->role }}</span></td>
                                    <td>
                                        @if($user->role == 'Administrator')
                                            <span class="um-page is-all">All pages</span>
                                        @elseif($granted->isEmpty())
                                            <span class="lv-muted" style="font-size: 12px;">Dashboard only</span>
                                        @else
                                            @foreach($granted as $label)
                                                <span class="um-page">{{ $label }}</span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="text-center" width="96">
                                        <span class="lv-row-actions">
                                            <a href="{{ route('uEdit', $user->uid) }}" class="lv-icon-btn" title="Edit" aria-label="Edit">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <button type="button" value="{{ $user->uid }}" class="lv-icon-btn is-danger users-delete" title="Delete" aria-label="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Called by the Role dropdown: Administrators get every page.
    function updateCheckboxes() {
        var isAdmin = document.getElementById('roleSelect').value === 'Administrator';
        document.getElementById('adminNote').style.display = isAdmin ? 'block' : 'none';
        if (isAdmin) {
            document.querySelectorAll('.um-access input[type="checkbox"]:not(:disabled)').forEach(function (box) { box.checked = true; });
        }
        syncToggleLabel();
    }

    function syncToggleLabel() {
        var boxes = document.querySelectorAll('.um-access input[type="checkbox"]:not(:disabled)');
        var allOn = Array.prototype.every.call(boxes, function (box) { return box.checked; });
        document.getElementById('toggleAllAccess').textContent = allOn ? 'Clear all' : 'Select all';
    }

    document.getElementById('toggleAllAccess').addEventListener('click', function () {
        var boxes = document.querySelectorAll('.um-access input[type="checkbox"]:not(:disabled)');
        var allOn = Array.prototype.every.call(boxes, function (box) { return box.checked; });
        boxes.forEach(function (box) { box.checked = !allOn; });
        syncToggleLabel();
    });
    document.querySelectorAll('.um-access input[type="checkbox"]:not(:disabled)').forEach(function (box) {
        box.addEventListener('change', syncToggleLabel);
    });

    document.getElementById('adminNote').style.display = document.getElementById('roleSelect').value === 'Administrator' ? 'block' : 'none';
    syncToggleLabel();
</script>
@endsection
