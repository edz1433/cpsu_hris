{{--
    Page header for the PDS tabs, with the auto-save indicator.
    $pdsTitle  - section name shown as the page title
    $saveUrls  - URLs the tab's own scripts post to when a field saves; leave empty
                for tabs saved with a submit button (no auto-save indicator)
    $pdsNote   - optional subtitle text after the employee's name
--}}
@php($autoSave = !empty($saveUrls))
<div class="dtr-head">
    <div>
        <h1>{{ $pdsTitle }}</h1>
        <p>{{ ucwords(strtolower($employee->fname)) }} {{ ucwords(strtolower($employee->lname)) }} &middot; {{ $pdsNote ?? 'each field saves as soon as you change it.' }}</p>
    </div>
    <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
        @if($autoSave)
        <span class="pds-save" id="pdsSaveState" aria-live="polite"><i class="fas fa-cloud"></i> <span>Auto-save on</span></span>
        @endif
        <a href="{{ $guard == 'web' ? route('generatepds', $employee->id) : route('generatepds') }}" target="_blank" rel="noopener" class="lv-btn">
            <i class="fas fa-file-pdf"></i> Preview PDS
        </a>
    </div>
</div>
@if($autoSave)
<script>
    // Mirrors the saves made by the tab's own scripts; it doesn't save anything itself.
    // jQuery loads at the end of the layout, so wait for it.
    document.addEventListener('DOMContentLoaded', function () {
        var saveUrls = @json(array_values($saveUrls));
        var state = document.getElementById('pdsSaveState');
        var pending = 0;
        var resetTimer;

        function show(mode, icon, text) {
            clearTimeout(resetTimer);
            state.className = 'pds-save ' + mode;
            state.innerHTML = '<i class="fas ' + icon + '"></i> <span>' + text + '</span>';
        }

        $(document).ajaxSend(function (event, xhr, settings) {
            if (saveUrls.indexOf(settings.url) === -1) return;
            pending++;
            show('is-saving', 'fa-sync-alt fa-spin', 'Saving...');
        });
        $(document).ajaxComplete(function (event, xhr, settings) {
            if (saveUrls.indexOf(settings.url) === -1) return;
            pending = Math.max(0, pending - 1);
            if (pending > 0) return;
            if (xhr.status >= 200 && xhr.status < 300) {
                show('is-saved', 'fa-check-circle', 'All changes saved');
                resetTimer = setTimeout(function () { show('', 'fa-cloud', 'Auto-save on'); }, 4000);
            } else {
                show('is-error', 'fa-exclamation-circle', 'Last change was not saved');
            }
        });
    });
</script>
@endif
