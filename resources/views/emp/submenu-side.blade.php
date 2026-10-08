<style>
    .employee-card {
        background-color: #ffffff;
        background-image: url('{{ asset('images/qr-bg.png') }}');
        background-size: cover;
        background-position: center;
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        width: 270px;
        height: 360px;
        padding: 20px;
        text-align: center;
        font-family: 'Arial', sans-serif;
        font-size: 14px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 2px solid #e0e0e0;
    }

    .qr-code {
        padding: 9px 5px 2px 4px; /* top right bottom left */
        margin-left: 8px;
    }
</style>
@php
    $imageUrl = asset('Profile/Employee/' . $employee->profile);
    $imagePath = public_path('Profile/Employee/' . $employee->profile);

    $interval = (new DateTime($employee->date_hired))->diff(new DateTime(date('Y-m-d')));
    $serviceText = $employee->date_hired ? $interval->y . ' years ' . $interval->m . ' months' : '—';

    // Employees open their own PDS; Personal Information has its own route for them.
    $pdsLink = fn ($name) => ($guard == 'web') ? route($name, $employee->id) : route($name === 'PDS' ? 'empPDS' : $name);
    $onPage = fn (...$paths) => collect($paths)->contains(fn ($path) => request()->is($path));
    $hasRows = fn ($key) => isset($columnstatus[$key]) && count($columnstatus[$key]) > 0;
    $isSet = fn ($key) => isset($columnstatus) && $columnstatus[$key] == 1;

    // [route name, icon, label, is current page, is filled in]
    $pdsSections = [
        ['PDS', 'fa-user', 'Personal Information', $onPage('pds/personal-info/*', 'pds'), true],
        ['familybg', 'fa-users', 'Family Background', $onPage('pds/family-bg', 'pds/family-bg/*'), $isSet('colfamstat')],
        ['educbg', 'fa-graduation-cap', 'Educational Background', $onPage('pds/educ-bg', 'pds/educ-bg/*'), $isSet('coleducstat')],
        ['eligibility', 'fa-certificate', 'Eligibility', $onPage('pds/eligibility', 'pds/eligibility/*') || isset($eligibilityedit), $hasRows('eligibility')],
        ['work-experience', 'fa-briefcase', 'Work Experience', $onPage('pds/work-experience', 'pds/work-experience/*') || isset($workexperienceedit), $hasRows('workexperience')],
        ['voluntary-work', 'fa-hand-holding-heart', 'Voluntary Work', $onPage('pds/voluntary-work', 'pds/voluntary-work/*') || isset($voluntaryworksedit), $hasRows('voluntaryworks')],
        ['learning-dev', 'fa-book', 'Learning and Development', $onPage('pds/learning-dev', 'pds/learning-dev/*') || isset($learningdevedit), $hasRows('learningdev')],
        ['otherInfo', 'fa-info-circle', 'Other Information', $onPage('pds/other-info', 'pds/other-info/*'), $isSet('colotherinfo')],
        ['infoQuestion', 'fa-question-circle', 'Other Information Questions', $onPage('pds/info-question', 'pds/info-question/*'), $isSet('colinfoquestion')],
        ['references', 'fa-address-book', 'References', $onPage('pds/references', 'pds/references/*'), $isSet('colreferences')],
        ['govids', 'fa-id-card', 'Government Issued ID', $onPage('pds/government-id', 'pds/government-id/*'), $isSet('colgovids')],
    ];
    $pdsDone = collect($pdsSections)->filter(fn ($section) => $section[4])->count();
    $pdsTotal = count($pdsSections);
@endphp

<div class="col-lg-3">
    <div class="dash-card lv-profile">
        <div class="lv-profile-head">
            {{-- Clicks on the photo are handled by the #changeProfilePicture listener; this covers keyboard use. --}}
            <button type="button" class="pds-avatar" title="Change profile picture" aria-label="Change profile picture" onclick="if (event.target === this) document.getElementById('profilePictureInput').click()">
                <img src="{{ file_exists($imagePath) ? $imageUrl : asset('Profile/Employee/default.png') }}" alt="" class="lv-avatar" id="changeProfilePicture">
                <span class="pds-avatar-edit" aria-hidden="true"><i class="fas fa-camera"></i></span>
            </button>
            <input type="file" id="profilePictureInput" style="display: none;" accept="image/*">
            <div style="min-width: 0; flex: 1;">
                <h3 class="lv-name">{{ ucwords(strtolower(str_replace('Ñ', 'ñ', $employee->fname))) }} {{ ucwords(strtolower(str_replace('Ñ', 'ñ', $employee->lname))) }}</h3>
                <p class="lv-position">{{ $employee->position ?: '—' }}</p>
                <span class="lv-pill {{ $employee->stat_1 == 1 ? 'is-added' : 'is-deducted' }} mt-1">{{ $employee->stat_1 == 1 ? 'Active' : 'Suspended' }}</span>
            </div>
            <button type="button" class="lv-icon-btn align-self-start" onclick="openQRModal()" data-toggle="modal" data-target="#qrModal" title="Show QR code" aria-label="Show QR code">
                <i class="fas fa-qrcode"></i>
            </button>
        </div>
        <ul class="lv-balances pds-facts">
            <li><span>Employee ID</span> <span>{{ $employee->emp_ID }}</span></li>
            <li><span>Item No.</span> <span>{{ $employee->item_no ?: '—' }}</span></li>
            <li><span>Service</span> <span>{{ $serviceText }}</span></li>
        </ul>
    </div>

    <div class="dash-card">
        <div class="dash-card-header">
            <h5><i class="fas fa-id-card" style="color: var(--cpsu-green-600);"></i>Personal data sheet</h5>
            <span class="dash-card-hint">{{ $pdsDone }} of {{ $pdsTotal }}</span>
        </div>
        <div class="pds-progress" role="progressbar" aria-label="Sections filled in" aria-valuemin="0" aria-valuemax="{{ $pdsTotal }}" aria-valuenow="{{ $pdsDone }}">
            <span style="width: {{ round($pdsDone / $pdsTotal * 100) }}%;"></span>
        </div>
        <nav class="pds-nav" aria-label="Personal data sheet sections">
            @foreach($pdsSections as [$routeName, $icon, $label, $isCurrent, $isDone])
                <a href="{{ $pdsLink($routeName) }}" class="{{ $isCurrent ? 'is-active' : '' }}" @if($isCurrent) aria-current="page" @endif>
                    <i class="fas {{ $icon }} pds-nav-icon"></i>
                    <span class="pds-nav-label">{{ $label }}</span>
                    @if($isDone)
                        <i class="fas fa-check-circle pds-nav-state is-done" title="Filled in"></i>
                    @else
                        <i class="far fa-circle pds-nav-state" title="Not filled in yet"></i>
                    @endif
                </a>
            @endforeach
        </nav>
        <div class="pds-nav-group">Documents</div>
        <nav class="pds-nav pb-2" aria-label="Personal data sheet documents">
            <a href="{{ $pdsLink('generatepds') }}" target="_blank" rel="noopener">
                <i class="fas fa-eye pds-nav-icon"></i>
                <span class="pds-nav-label">Preview Personal Data Sheet</span>
                <i class="fas fa-external-link-alt pds-nav-state"></i>
            </a>
            <a href="{{ $pdsLink('genpdsAtthachment') }}" target="_blank" rel="noopener">
                <i class="fas fa-paperclip pds-nav-icon"></i>
                <span class="pds-nav-label">Attachment to CS Form No. 212</span>
                <i class="fas fa-external-link-alt pds-nav-state"></i>
            </a>
            @php $onSignature = $onPage('pds/signature', 'pds/signature/*'); @endphp
            <a href="{{ $pdsLink('signature') }}" class="{{ $onSignature ? 'is-active' : '' }}" @if($onSignature) aria-current="page" @endif>
                <i class="fas fa-signature pds-nav-icon"></i>
                <span class="pds-nav-label">E-Signature</span>
            </a>
        </nav>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" role="dialog" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <!-- Download Button -->
                <a href="#" id="downloadBtn"
                    class="btn btn-danger rounded-circle d-flex align-items-center justify-content-center"
                    style="width: 30px; height: 30px; position: absolute; top: 10px; right: 10px; z-index: 999;">
                    <i class="fas fa-download"></i>
                </a>

                <!-- Employee Card with QR Code -->
                <div class="employee-card-content">
                    <div class="employee-card" id="employeeCard">
                        <div class="qr-code" id="qrcode">
                            <!-- QR Code will be generated here -->
                        </div>

                        <div class="details mt-3" style="text-align: center; border-radius: 5px; background-color: rgba(97, 91, 91, 0.342); color: #fff; padding-top: 5px; padding-bottom: 5px;">
                            <h5 style="margin: 0; font-weight: bold; font-size: 1.2rem;">
                                {{ ucwords(strtoupper(str_replace('Ñ', 'ñ', $employee->fname))) }}
                                {{ ucwords(strtoupper(str_replace('Ñ', 'ñ', $employee->lname))) }}
                                {{ ucwords(strtoupper(str_replace('Ñ', 'ñ', $employee->suffix))) }}
                            </h5>
                            <p style="margin: 4px 0; font-style: italic;">{{ ($employee->emp_status == 1) ? $employee->position : 'OFFICE STAFF'  }}</p>
                            {{-- <p style="margin: 0; font-weight: bold;">MAIN CAMPUS</p> --}}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@php
    $shortEncrypted = shortEncrypt($employee->emp_ID);
@endphp
<script>
    function openQRModal() {
        const qrElements = ['qrcode', 'qrcode1'];
        const token = "{{ $shortEncrypted }}";

        qrElements.forEach(elementId => {
            const qrElement = document.getElementById(elementId);
            if (qrElement) {
                qrElement.innerHTML = "";
                new QRCode(qrElement, {
                    text: token,
                    width: 205,
                    height: 205,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        });
    }
</script>

<script>
    document.getElementById('downloadBtn').addEventListener('click', function() {
        const target = document.querySelector('.employee-card-content');
        html2canvas(target, {
            backgroundColor: null,
            useCORS: true
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = '{{ $employee->emp_ID }}.png';
            link.href = canvas.toDataURL();
            link.click();
        });
    });
</script>
