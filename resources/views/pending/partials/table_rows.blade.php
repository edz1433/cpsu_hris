@if($type == 1)
    @forelse ($employees as $emp)
        @php
            $dateInfo = formatLeaveDateRange($emp->date_range, $emp->days);
            $formattedDateRange = $dateInfo['formatted'];
            $calculatedDays = $dateInfo['calculatedDays'];
        @endphp
        <tr>
            <td>
                <div class="w-100 mb-1">
                    <span class="badge badge-success"><b>#{{ $emp->transnum }}</b></span>
                    @if($formattedDateRange)
                        <span class="badge badge-info ml-1" title="Applied Leave Period">
                            <i class="far fa-calendar-alt mr-1"></i>{{ $formattedDateRange }} @if($calculatedDays > 0)({{ $calculatedDays }} {{ \Illuminate\Support\Str::plural('day', $calculatedDays) }})@endif
                        </span>
                    @endif
                </div>
                @if($emp->status == 1)
                    <div class="d-flex flex-wrap align-items-center">
                        <!-- Employee E-sign Status -->
                        <div class="mr-1">
                            <span class="badge bg-{{ in_array($emp->emp_esign, [0, 1]) ? 'danger' : 'success' }}">
                                <i class="fas fa-{{ in_array($emp->emp_esign, [0, 1]) ? 'times' : 'check' }}"></i> 
                            </span>
                        </div>

                        <!-- Employee Status -->
                        <div class="mr-1">
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user"></i> {{ $emp->employee_lname }}, {{ $emp->employee_fname }} {{ $emp->employee_suffix }} {{ isset($emp->employee_mname) ? strtoupper(substr($emp->employee_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>

                        <!-- HRMO Status -->
                        <span class="badge bg-danger mr-1">
                            <i class="fas fa-times"></i> 
                        </span>
                        <div class="mr-1">
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user-tie"></i> {{ $emp->hr_lname }}, {{ $emp->hr_fname }} {{ $emp->hr_suffix }} {{ isset($emp->hr_mname) ? strtoupper(substr($emp->hr_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>

                        <!-- Immediate Supervisor Status -->
                        <span class="badge bg-danger mr-1">
                            <i class="fas fa-times"></i> 
                        </span>
                        <div class="mr-1">
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user-check"></i> {{ $emp->supervisor_lname }}, {{ $emp->supervisor_fname }} {{ $emp->supervisor_suffix }} {{ isset($emp->supervisor_mname) ? strtoupper(substr($emp->supervisor_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>

                        <!-- SUC President Status -->
                        <span class="badge bg-danger mr-1">
                            <i class="fas fa-times"></i> 
                        </span>
                        <div>
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user-shield"></i> {{ $emp->sucpres_lname }}, {{ $emp->sucpres_fname }} {{ $emp->sucpres_suffix }} {{ isset($emp->sucpres_mname) ? strtoupper(substr($emp->sucpres_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>
                    </div>
                @elseif($emp->status == 2)
                    <div class="d-flex flex-wrap align-items-center">
                        <!-- Employee E-sign Status -->
                        <div class="mr-1">
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i> 
                            </span>
                        </div>

                        <!-- Employee Status -->
                        <div class="mr-1">
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user"></i> {{ $emp->employee_lname }}, {{ $emp->employee_fname }} {{ $emp->employee_suffix }} {{ isset($emp->employee_mname) ? strtoupper(substr($emp->employee_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>

                        <!-- HRMO Status -->
                        <span class="badge bg-{{ ($emp->status == 2) ? 'success' : 'danger' }} mr-1">
                            <i class="fas fa-{{ ($emp->status == 2) ? 'check' : 'times' }}"></i> 
                        </span>
                        <div class="mr-1">
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user-tie"></i> {{ $emp->hr_lname }}, {{ $emp->hr_fname }} {{ $emp->hr_suffix }} {{ isset($emp->hr_mname) ? strtoupper(substr($emp->hr_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>

                        <!-- Immediate Supervisor Status -->
                        <span class="badge bg-danger mr-1">
                            <i class="fas fa-times"></i> 
                        </span>
                        <div class="mr-1">
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user-check"></i> {{ $emp->supervisor_lname }}, {{ $emp->supervisor_fname }} {{ $emp->supervisor_suffix }} {{ isset($emp->supervisor_mname) ? strtoupper(substr($emp->supervisor_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>

                        <!-- SUC President Status -->
                        <span class="badge bg-danger mr-1">
                            <i class="fas fa-times"></i> 
                        </span>
                        <div>
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user-shield"></i> {{ $emp->sucpres_lname }}, {{ $emp->sucpres_fname }} {{ $emp->sucpres_suffix }} {{ isset($emp->sucpres_mname) ? strtoupper(substr($emp->sucpres_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>
                    </div>
                @elseif($emp->status == 3)
                    <div class="d-flex flex-wrap align-items-center">
                        <!-- Employee E-sign Status -->
                        <div class="mr-1">
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i> 
                            </span>
                        </div>

                        <!-- Employee Status -->
                        <div class="mr-1">
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user"></i> {{ $emp->employee_lname }}, {{ $emp->employee_fname }} {{ $emp->employee_suffix }} {{ isset($emp->employee_mname) ? strtoupper(substr($emp->employee_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>

                        <!-- HRMO Status -->
                        <span class="badge bg-success mr-1">
                            <i class="fas fa-check"></i> 
                        </span>
                        <div class="mr-1">
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user-tie"></i> {{ $emp->hr_lname }}, {{ $emp->hr_fname }} {{ $emp->hr_suffix }} {{ isset($emp->hr_mname) ? strtoupper(substr($emp->hr_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>

                        <!-- Immediate Supervisor Status -->
                        <span class="badge bg-{{ ($emp->status == 3) ? 'success' : 'danger' }} mr-1">
                            <i class="fas fa-{{ ($emp->status == 3) ? 'check' : 'times' }}"></i> 
                        </span>
                        <div class="mr-1">
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user-check"></i> {{ $emp->supervisor_lname }}, {{ $emp->supervisor_fname }} {{ $emp->supervisor_suffix }} {{ isset($emp->supervisor_mname) ? strtoupper(substr($emp->supervisor_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>

                        <!-- SUC President Status -->
                        <span class="badge bg-danger mr-1">
                            <i class="fas fa-times"></i> 
                        </span>
                        <div>
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user-shield"></i> {{ $emp->sucpres_lname }}, {{ $emp->sucpres_fname }} {{ $emp->sucpres_suffix }} {{ isset($emp->sucpres_mname) ? strtoupper(substr($emp->sucpres_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>
                    </div>
                @elseif($emp->status == 4)
                    <div class="d-flex flex-wrap align-items-center">
                        <!-- Employee E-sign Status -->
                        <div class="mr-1">
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i> 
                            </span>
                        </div>

                        <!-- Employee Status -->
                        <div class="mr-1">
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user"></i> {{ $emp->employee_lname }}, {{ $emp->employee_fname }} {{ $emp->employee_suffix }} {{ isset($emp->employee_mname) ? strtoupper(substr($emp->employee_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>

                        <!-- HRMO Status -->
                        <span class="badge bg-success mr-1">
                            <i class="fas fa-check"></i> 
                        </span>
                        <div class="mr-1">
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user-tie"></i> {{ $emp->hr_lname }}, {{ $emp->hr_fname }} {{ $emp->hr_suffix }} {{ isset($emp->hr_mname) ? strtoupper(substr($emp->hr_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>

                        <!-- Immediate Supervisor Status -->
                        <span class="badge bg-success mr-1">
                            <i class="fas fa-check"></i> 
                        </span>
                        <div class="mr-1">
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user-check"></i> {{ $emp->supervisor_lname }}, {{ $emp->supervisor_fname }} {{ $emp->supervisor_suffix }} {{ isset($emp->supervisor_mname) ? strtoupper(substr($emp->supervisor_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>

                        <!-- SUC President Status -->
                        <span class="badge bg-{{ ($emp->status == 4) ? 'success' : 'danger' }} mr-1">
                            <i class="fas fa-{{ ($emp->status == 4) ? 'check' : 'times' }}"></i> 
                        </span>
                        <div>
                            <span class="badge bg-secondary span-fix">
                                <i class="fas fa-user-shield"></i> {{ $emp->sucpres_lname }}, {{ $emp->sucpres_fname }} {{ $emp->sucpres_suffix }} {{ isset($emp->sucpres_mname) ? strtoupper(substr($emp->sucpres_mname, 0, 1)).'.' : '' }}
                            </span>
                        </div>
                    </div>
                @endif
            </td>

            <td>
                <!-- History Badge -->
                @if(in_array($emp->history, [0, 1]))
                    @php
                        $pendingInfo = leavePendingDays($emp->date_filing, $emp->created_at);
                    @endphp
                    <div class="w-100 mb-1">
                        <span class="badge bg-warning">
                            <i class="fas fa-spinner fa-spin"></i> Ongoing...
                        </span>
                    </div>
                    @if($pendingInfo)
                        <div class="w-100">
                            <span class="badge bg-{{ $pendingInfo['days'] >= 7 ? 'danger' : ($pendingInfo['days'] >= 3 ? 'warning' : 'info') }}"
                                title="Filed on {{ $pendingInfo['filed']->format('M d, Y') }} - pending as of {{ \Carbon\Carbon::now('Asia/Manila')->format('M d, Y') }}">
                                <i class="far fa-clock mr-1"></i>
                                @if($pendingInfo['days'] == 0)
                                    Filed today
                                @else
                                    {{ $pendingInfo['days'] }} {{ \Illuminate\Support\Str::plural('day', $pendingInfo['days']) }} pending
                                @endif
                            </span>
                        </div>
                        <small class="text-muted d-block">Filed: {{ $pendingInfo['filed']->format('M d, Y') }}</small>
                    @endif
                @else
                    @if($emp->remarks_stat == 0)
                        <span class="badge bg-success">
                            <i class="fas fa-check-circle"></i> Complete
                        </span>
                    @else
                        <span class="badge bg-danger">
                            <i class="fas fa-check-circle"></i> Disapproved
                        </span>
                    @endif
                @endif
            </td>

            <td class="text-center">
                <!-- Action Button -->
                @if($emp->status != 4)
                    <a href="#" data-id="{{ $emp->id }}" data-url-template="{{ url('leave/preview-leave/__ID__') }}" 
                        data-toggle="modal" data-target="#pdfModalPending" 
                        id="preview{{ $emp->id }}"
                        class="btn btn-danger btn-sm" 
                        style="width: 30px; padding: 0px !important;" >
                        <i class="fas fa-file-pdf" style="font-size: 0.75rem;"></i>
                    </a>
                @else
                    <a href="#" data-id="{{ $emp->id }}" data-toggle="modal" data-target="#pdfModalHistory"
                        id="preview{{ $emp->id }}"
                        class="btn btn-danger btn-sm" 
                        style="width: 30px; padding: 0px !important;" >
                        <i class="fas fa-file-pdf" style="font-size: 0.75rem;"></i>
                    </a>
                @endif
                <a href="{{ route('leaveStatus', $emp->employid) }}" 
                target="_blank" 
                class="btn btn-{{ (in_array($emp->emp_esign, [1, 2])) ? 'success' : 'info' }} btn-sm" 
                style="width: 30px; padding: 0px !important;" 
                value="{{ $emp->id }}">
                    <i class="fas fa-exclamation-circle"></i>
                </a>
                @if($cat == 4)
                    <button type="button" class="btn btn-warning btn-sm undo-leave" data-id="{{ $emp->id }}" data-to="4" title="Undo Complete Status" style="width: 30px; padding: 0px !important;"><i class="fas fa-undo"></i></button>
                @endif
            </td>
        </tr>
    @empty
        @if(isset($page) && $page == 1)
        <tr class="no-records">
            <td colspan="3" class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2"></i><br>No pending leave applications found.
            </td>
        </tr>
        @endif
    @endforelse
@else
    @forelse ($employees as $emp)
        @php
        switch ((string)$type) {
            case '2':
                $route = route('eligibility', $emp->id);
                break;
            case '3':
                $route = route('work-experience', $emp->id);
                break;
            case '4':
                $route = route('voluntary-work', $emp->id);
                break;
            case '5':
                $route = route('learning-dev', $emp->id);
                break;
            default:
                $route = '#';
                break;
        }
        @endphp
        <tr>
            <td>{{ $emp->lname }}, {{ $emp->fname }} {{ $emp->suffix }} {{ isset($emp->mname) ? strtoupper(substr($emp->mname, 0, 1)).'.' : '' }}</td>
            <td class="text-center">
                <a href="{{ $route }}" target="_blank" class='btn btn-info btn-sm mr-1' style='width: 30px;' value="{{ $emp->id }}">
                    <i class="fas fa-exclamation-circle" style="font-size: 0.75rem;"></i>  
                </a>
            </td>
        </tr>
    @empty
        @if(isset($page) && $page == 1)
        <tr class="no-records">
            <td colspan="2" class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2"></i><br>No pending records found.
            </td>
        </tr>
        @endif
    @endforelse
@endif
