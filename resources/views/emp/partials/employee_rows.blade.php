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
        $initials = strtoupper(substr($emp->fname ?? '', 0, 1) . substr($emp->lname ?? '', 0, 1));
        $isRegular = ($emp->emp_status ?? 0) == 1;

        if ($partimeRate > 0) {
            [$statusLabel, $statusTone] = ['Part-time/JO', 'is-start'];
        } elseif (($emp->emp_status ?? 0) == 2) {
            [$statusLabel, $statusTone] = [($emp->status_name ?? '') . $qualStr, 'is-info'];
        } else {
            [$statusLabel, $statusTone] = [$emp->status_name ?? '', $isRegular ? 'is-added' : 'is-info'];
        }
    @endphp
    <tr id="tr-{{ $emp->id }}">
        <td class="text-center lv-muted lv-num">{{ $emp->ids }}</td>
        <td>
            <div class="emp-person">
                <span class="emp-initials" aria-hidden="true">{{ $initials }}</span>
                <div style="min-width: 0;">
                    <div class="um-name">{{ $fullName }}</div>
                    <div class="um-username">{{ $positionStr ?: '—' }}</div>
                </div>
            </div>
        </td>
        <td class="emp-id">{{ $emp->emp_ID ?? '' }}</td>
        <td class="{{ !empty($emp->campus_abbr) ? '' : 'lv-muted' }}">{{ $emp->campus_abbr ?: '—' }}</td>
        <td>
            @if(trim($statusLabel) !== '')
                <span class="lv-pill {{ $statusTone }}">{{ $statusLabel }}</span>
            @else
                <span class="lv-muted">—</span>
            @endif
        </td>
        <td class="emp-email {{ !empty($emp->org_email) ? '' : 'lv-muted' }}">{{ $emp->org_email ?: '—' }}</td>
        <td style="white-space: nowrap;" class="{{ $serviceDuration ? '' : 'lv-muted' }}">{{ $serviceDuration ?: '—' }}</td>
        <td style="white-space: nowrap;" class="{{ $formattedHireDate ? '' : 'lv-muted' }}">{{ $formattedHireDate ?: '—' }}</td>
        <td class="text-center">
            <div class="custom-control custom-switch emp-switch" title="{{ ($emp->stat_1 ?? 0) == 1 ? 'Account enabled' : 'Account disabled' }}">
                <input type="checkbox"
                    class="custom-control-input"
                    onchange="openToggleDialog(this, '{{ addslashes($fullName) }}', {{ $emp->id }})"
                    id="switch{{ $emp->id }}"
                    {{ ($emp->stat_1 ?? 0) == 1 ? 'checked' : '' }}>
                <label class="custom-control-label" for="switch{{ $emp->id }}"><span class="sr-only">Account access for {{ $fullName }}</span></label>
            </div>
        </td>
        <td class="text-center" width="116">
            <span class="lv-row-actions">
                @if($isRegular)
                    <a href="{{ route('leavesRead', $emp->id) }}" class="lv-icon-btn" title="Leave credits" aria-label="Leave credits">
                        <i class="fas fa-calendar-check"></i>
                    </a>
                @else
                    <button type="button" class="lv-icon-btn" title="Leave credits are only kept for regular employees" aria-label="Leave credits" disabled>
                        <i class="fas fa-calendar-check"></i>
                    </button>
                @endif
                <a href="{{ route('PDS', $emp->id) }}" class="lv-icon-btn" title="Personal data sheet" aria-label="Personal data sheet">
                    <i class="fas fa-file-alt"></i>
                </a>
                <button type="button" class="lv-icon-btn" title="Working hours" aria-label="Working hours" data-toggle="modal" data-target="#officialTime" onclick="OfficialTime('{{ $emp->emp_ID ?? '' }}')">
                    <i class="fas fa-clock"></i>
                </button>
            </span>
        </td>
    </tr>
@empty
    @if(isset($page) && $page == 1)
    <tr class="no-records">
        <td colspan="10">
            <div class="dtr-empty">
                <div class="dtr-empty-icon"><i class="fas fa-user-slash"></i></div>
                <h6>No employees found</h6>
                <p>If you searched, try a last name, employee ID or email instead.</p>
            </div>
        </td>
    </tr>
    @endif
@endforelse
