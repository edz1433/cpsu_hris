@forelse ($employee as $emp)
    @php
        $hireDate = $emp->date_hired ?? null;
        $serviceDuration = calculateServiceDuration($hireDate);
        $formattedHireDate = !empty($hireDate) ? date('F d, Y', strtotime($hireDate)) : '';
        $mnameInitial = !empty($emp->mname) ? strtoupper(substr($emp->mname, 0, 1)) . '.' : '';
        $suffixStr = !empty($emp->suffix) ? ' ' . $emp->suffix : '';
        $fullName = trim(($emp->lname ?? '') . ', ' . ($emp->fname ?? '') . $suffixStr . ' ' . $mnameInitial);
        $positionStr = $emp->position ?? '';
        $partimeRate = isset($emp->partime_rate) ? (float)$emp->partime_rate : 0;
        $qualStr = !empty($emp->qual) ? ' (' . $emp->qual . ')' : '';
    @endphp
    <tr id="tr-{{ $emp->id }}">
        <td>{{ $emp->ids }}</td>
        <td>
            <b>{{ $fullName }}</b><br>
            <i>{{ $positionStr }}</i>
        </td>
        <td>{{ $emp->emp_ID ?? '' }}</td>
        <td>{{ $emp->campus_abbr ?? '' }}</td>
        <td>
            @if($partimeRate > 0)
                Part-time/JO
            @elseif(($emp->emp_status ?? 0) == 2)
                {{ $emp->status_name ?? '' }}{{ $qualStr }}
            @else
                {{ $emp->status_name ?? '' }}
            @endif
        </td>
        <td>{{ $emp->org_email ?? '' }}</td>
        <td>{{ $serviceDuration }}</td>
        <td>{{ $formattedHireDate }}</td>
        <td class="text-center">
            <div class="custom-control custom-switch">
                <input type="checkbox"
                    class="custom-control-input"
                    onchange="openToggleDialog(this, '{{ addslashes($fullName) }}', {{ $emp->id }})"
                    id="switch{{ $emp->id }}"
                    {{ ($emp->stat_1 ?? 0) == 1 ? 'checked' : '' }}>
                <label class="custom-control-label" for="switch{{ $emp->id }}"></label>
            </div>
        </td>
        <td>
            <div class='d-flex align-items-center'>
                @if(($emp->emp_status ?? 0) == 1)
                <a href="{{ route('leavesRead', $emp->id) }}" title="Leave Credits" class='btn btn-success btn-xs employee_edit mr-1' style='width: 30px;' value="{{ $emp->id }}">
                    <i class="fas fa-calendar-check"></i>
                </a>
                @else
                <a href="#" title="Leave Credits" class='btn btn-secondary btn-xs employee_edit mr-1' style='width: 30px;' value="{{ $emp->id }}">
                    <i class="fas fa-calendar-check"></i>
                </a>
                @endif
                <a href="{{ route('PDS', $emp->id) }}" title="PDS" class='btn btn-info btn-xs employee_edit mr-1' style='width: 30px;' value="{{ $emp->id }}">
                    <i class='fas fa-file-alt'></i>
                </a>
                <a title="Working Hours" data-toggle="modal" data-target="#officialTime" onclick="OfficialTime('{{ $emp->emp_ID ?? '' }}')" class='btn btn-primary btn-xs mr-1' style='width: 30px;'>
                    <i class='fas fa-clock'></i>
                </a>
            </div>
        </td>
    </tr>
@empty
    @if(isset($page) && $page == 1)
    <tr class="no-records">
        <td colspan="10" class="text-center text-muted py-4">
            <i class="fas fa-user-slash fa-2x mb-2"></i><br>No employees found.
        </td>
    </tr>
    @endif
@endforelse
