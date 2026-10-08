<style>
    /* Employee QR card. Plain colors (no CSS variables) because html2canvas turns it into the downloaded PNG. */
    .qr-modal .modal-dialog { max-width: 380px; }
    .employee-card-content {
        display: inline-block;
        padding: 6px; /* room for the card's shadow in the downloaded image */
    }
    .employee-card {
        position: relative;
        width: 300px;
        overflow: hidden;
        border-radius: 20px;
        background: #ffffff;
        box-shadow: 0 10px 28px -10px rgba(11, 61, 36, .35), 0 0 0 1px rgba(11, 61, 36, .08);
        text-align: center;
        font-family: 'Source Sans Pro', 'Segoe UI', Arial, sans-serif;
    }
    .employee-card .qr-card-top {
        position: relative;
        height: 138px;
        padding: 18px 18px 0;
        background: linear-gradient(135deg, #0b3d24 0%, #146a3b 55%, #23955a 100%);
        color: #ffffff;
        text-align: left;
    }
    .employee-card .qr-card-top::after {
        content: "";
        position: absolute;
        top: -60px;
        right: -50px;
        width: 170px;
        height: 170px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .07);
    }
    .employee-card .qr-card-brand {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center; /* centred over the QR panel below */
        gap: 10px;
    }
    .employee-card .qr-card-brand img {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #ffffff;
        padding: 2px;
    }
    .employee-card .qr-card-brand b {
        display: block;
        font-size: 11.5px;
        letter-spacing: .04em;
        line-height: 1.25;
    }
    .employee-card .qr-card-brand span {
        display: block;
        margin-top: 2px;
        font-size: 10.5px;
        color: #f2c811;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .employee-card .qr-code {
        position: relative;
        z-index: 1;
        display: inline-block;
        margin-top: -44px; /* overlaps only the bottom of the header, clear of the text */
        padding: 12px;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 8px 20px -8px rgba(11, 61, 36, .45);
    }
    .employee-card .qr-code img,
    .employee-card .qr-code canvas {
        display: block;
        width: 196px;
        height: 196px;
    }
    .employee-card .qr-card-name {
        margin: 14px 16px 0;
        font-size: 18px;
        font-weight: 700;
        line-height: 1.2;
        color: #1c2b24;
    }
    .employee-card .qr-card-pos {
        margin: 3px 16px 0;
        font-size: 12.5px;
        color: #56655d;
    }
    .employee-card .qr-card-id {
        display: inline-block;
        margin-top: 10px;
        padding: 4px 12px;
        border-radius: 999px;
        background: #e3f2e9;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .06em;
        color: #146a3b;
    }
    .employee-card .qr-card-foot {
        margin-top: 14px;
        padding: 9px 16px;
        border-top: 1px dashed #dfe7e2;
        font-size: 10.5px;
        color: #8a978f;
    }
    /* Large faded seal behind the name area, like the watermark on a printed ID. */
    .employee-card .qr-card-watermark {
        position: absolute;
        left: 50%;
        bottom: -46px;
        width: 250px;
        height: 250px;
        margin-left: -125px;
        opacity: .08;
        pointer-events: none;
    }
    .employee-card .qr-card-name,
    .employee-card .qr-card-pos,
    .employee-card .qr-card-id,
    .employee-card .qr-card-foot {
        position: relative;
        z-index: 1;
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
    // Some pages (e.g. E-Signature) don't load the section status; don't show it as all unfilled there.
    $hasStatus = isset($columnstatus);
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
            @if($hasStatus)
                <span class="dash-card-hint">{{ $pdsDone }} of {{ $pdsTotal }}</span>
            @endif
        </div>
        @if($hasStatus)
        <div class="pds-progress" role="progressbar" aria-label="Sections filled in" aria-valuemin="0" aria-valuemax="{{ $pdsTotal }}" aria-valuenow="{{ $pdsDone }}">
            <span style="width: {{ round($pdsDone / $pdsTotal * 100) }}%;"></span>
        </div>
        @endif
        <nav class="pds-nav" aria-label="Personal data sheet sections">
            @foreach($pdsSections as [$routeName, $icon, $label, $isCurrent, $isDone])
                <a href="{{ $pdsLink($routeName) }}" class="{{ $isCurrent ? 'is-active' : '' }}" @if($isCurrent) aria-current="page" @endif>
                    <i class="fas {{ $icon }} pds-nav-icon"></i>
                    <span class="pds-nav-label">{{ $label }}</span>
                    @if(!$hasStatus)
                    @elseif($isDone)
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
<div class="modal fade ev-modal qr-modal" id="qrModal" tabindex="-1" role="dialog" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrModalLabel"><i class="fas fa-qrcode"></i>Employee QR code</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body text-center pb-3">
                {{-- This element is what gets saved as the PNG. --}}
                <div class="employee-card-content">
                    <div class="employee-card" id="employeeCard">
                        <img class="qr-card-watermark" src="{{ asset('template/img/CPSU_L.png') }}" alt="">
                        <div class="qr-card-top">
                            <div class="qr-card-brand">
                                <img src="{{ asset('template/img/CPSU_L.png') }}" alt="">
                                <div>
                                    <b>CENTRAL PHILIPPINES<br>STATE UNIVERSITY</b>
                                    <span>Employee QR</span>
                                </div>
                            </div>
                        </div>
                        <div class="qr-code" id="qrcode">
                            <!-- QR Code will be generated here -->
                        </div>
                        <div class="qr-card-name">
                            {{ trim(strtoupper(str_replace('Ñ', 'ñ', $employee->fname)) . ' ' . strtoupper(str_replace('Ñ', 'ñ', $employee->lname)) . ' ' . strtoupper(str_replace('Ñ', 'ñ', $employee->suffix))) }}
                        </div>
                        <div class="qr-card-pos">{{ ($employee->emp_status == 1) ? $employee->position : 'OFFICE STAFF' }}</div>
                        <div class="qr-card-id">{{ $employee->emp_ID }}</div>
                        <div class="qr-card-foot">Scan for time entries and event attendance</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="lv-btn" data-dismiss="modal">Close</button>
                <button type="button" class="lv-btn is-primary" id="downloadBtn"><i class="fas fa-download"></i> Download PNG</button>
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
        const btn = this;
        const label = btn.innerHTML;
        const target = document.querySelector('.employee-card-content');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing...';
        html2canvas(target, {
            backgroundColor: null,
            useCORS: true,
            scale: 3 // sharp enough to print
        }).then(canvas => {
            const link = document.createElement('a');
            link.download = '{{ $employee->emp_ID }}.png';
            link.href = canvas.toDataURL();
            link.click();
        }).finally(() => {
            btn.disabled = false;
            btn.innerHTML = label;
        });
    });
</script>
