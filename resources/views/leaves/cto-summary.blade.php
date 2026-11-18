{{-- Dates and hours of a CTO application, shown in place of the leave days on the Status page. --}}
@php $fmtHours = fn ($h) => \App\Models\LeaveApplication::formatHours($h); @endphp
<span><b>EARNED COCs WHEN FILED :</b> {{ $fmtHours($leaves->coc_balance) }} hrs</span><br>
<span><b>DATES OF CTO :</b></span>
<ul class="mb-1 pl-4">
    @foreach($leaves->ctoDates as $ctoDate)
        <li>{{ $ctoDate->cto_date->format('F j, Y') }} &mdash; {{ $fmtHours($ctoDate->hours) }} hrs</li>
    @endforeach
</ul>
<span><b>TOTAL REQUESTED CTO :</b> {{ $fmtHours($leaves->cto_hours) }} hrs</span><br>
<span><b>REMAINING COCs :</b> {{ $fmtHours((float) $leaves->coc_balance - (float) $leaves->cto_hours) }} hrs</span><br>
