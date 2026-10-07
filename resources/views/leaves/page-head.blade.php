{{-- Title + Leave credits / Status / History switch, identical on all three leave pages. --}}
@php
    $isWeb = $guard == "web";

    if (request()->is('leave/status*') || request()->is('leaves/status*')) {
        $pageTitle = 'Leave Status';
        $pageLead = $isWeb
            ? 'Open applications of this employee and where each one is in the approval chain.'
            : 'Your open applications, plus the ones waiting for your signature.';
    } elseif (request()->is('leave/history*') || request()->is('leaves/history*')) {
        $pageTitle = 'Leave History';
        $pageLead = $isWeb
            ? 'Completed applications of this employee. Print a report for any date range.'
            : 'Your completed leave applications.';
    } else {
        $pageTitle = $isWeb ? 'Leave Credits' : 'Apply for Leave';
        $pageLead = $isWeb
            ? 'Starting balance and every credit added or deducted for this employee.'
            : 'File a leave application, then follow it under Status.';
    }
@endphp
<div class="dtr-head">
    <div>
        <h1>{{ $pageTitle }}</h1>
        <p>{{ $pageLead }}</p>
    </div>
    @include("leaves.top-menu")
</div>
