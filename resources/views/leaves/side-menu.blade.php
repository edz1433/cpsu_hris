@php
    $profileImagePath = 'Profile/Employee/' . $employee->profile;
    $imagePath = \Illuminate\Support\Facades\File::exists(public_path($profileImagePath)) ? $profileImagePath : 'Profile/Employee/default.png';

    // [element id kept for the live balance refresh, label, value]
    $otherCredits = [
        ['special-pl', 'Special Privilege Leave', $employee->special_pl],
        ['solo-pl', 'Solo Parent Leave', $employee->solo_pl],
        ['study-leave', 'Study Leave', $employee->study_leave],
        ['vawc-leave', '10-Day VAWC Leave', $employee->vawc_leave],
        ['rehab-leave', 'Rehabilitation Privilege', $employee->rehab_leave],
        ['benefits-leave', 'Special Leave Benefits for Women', $employee->benefits_leave],
        ['calamity-leave', 'Special Emergency (Calamity) Leave', $employee->calamity_leave],
        ['adopt-leave', 'Adoption Leave', $employee->adopt_leave],
        ['servcred-leave', 'Vacation Service Credit', $employee->servcred_leave],
        ['wellness-leave', 'Wellness Leave', $employee->well_leave],
    ];
@endphp
<div class="col-lg-3">
    @if($guard == "web")
        <div class="dash-card">
            <div class="dash-card-body dtr-form">
                <label class="dtr-label" for="employee">Employee</label>
                <select class="form-control select2" id="employee" style="width: 100%;" onchange="redirectToLeaveRead(this)">
                    @foreach ($emplalls as $emp)
                        <option value="{{ $emp->id }}" {{ ($employee->id == $emp->id) ? 'selected' : '' }}>
                            {{ strtoupper($emp->fname) }} {{ strtoupper($emp->lname) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    <div class="dash-card lv-profile">
        <div class="lv-profile-head">
            <img src="{{ asset($imagePath) }}" alt="" class="lv-avatar" id="changeProfilePicture">
            <input type="file" id="profilePictureInput" style="display: none;" accept="image/*">
            <div style="min-width: 0;">
                <h3 class="lv-name">{{ ucwords(strtolower($employee->fname)) }} {{ ucwords(strtolower($employee->lname)) }}</h3>
                <p class="lv-position">{{ $employee->position }}</p>
            </div>
        </div>

        <div class="lv-tiles">
            <div class="lv-tile">
                <span class="lv-tile-label">Vacation Leave</span>
                <span class="lv-tile-value" id="b-vl">{{ $employee->vl }}</span>
                <span class="lv-tile-unit">days left</span>
            </div>
            <div class="lv-tile is-sick">
                <span class="lv-tile-label">Sick Leave</span>
                <span class="lv-tile-value" id="b-sl">{{ $employee->sl }}</span>
                <span class="lv-tile-unit">days left</span>
            </div>
        </div>

        <div class="lv-others-head">
            Other leave credits
            @if($guard == "web")
                <button type="button" class="lv-icon-btn" data-toggle="modal" data-target="#modalSettingLeave" title="Edit other leave credits" aria-label="Edit other leave credits">
                    <i class="fas fa-cog"></i>
                </button>
            @endif
        </div>
        <ul class="lv-balances">
            @foreach($otherCredits as [$id, $label, $value])
                <li><span>{{ $label }}</span> <span id="{{ $id }}">{{ $value ?? 0 }}</span></li>
            @endforeach
        </ul>
    </div>
</div>
@if($guard == "web")
    @include('leaves.settings-modal')
@endif
