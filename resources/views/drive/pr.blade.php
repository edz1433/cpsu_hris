@extends('layouts.master')

@section('body')
<style>  
    #table-form {
        width: 100%;
        font-size: 10px;
    }

    #table-form td, th{
        border: 1px solid rgb(92, 85, 85);
        padding: 1px;
    }
    .b-none{
        border: none !important;
        width: 18px !important;
    }

    .btn-outline-secondary {
        border-radius: 50px;
        width: 30px !important;
        height: 30px !important;
    }
    .border-b-n{
        border-bottom: none;
    }
</style>
<table id="table-form">
    <thead>
        <tr>
            <th rowspan="4" class="text-center" width="135">MFO/PAPs</th>
            <th rowspan="3" class="text-center" width="150">Success Indicators</th>
            <th colspan="2" class="text-center" width="100">Evidence</th>
            <th rowspan="4" class="text-center" width="70">Allotted Budget</th>
            <th rowspan="4" class="text-center" width="70">Division/ Individuals Accountable</th>
            <th rowspan="2" colspan="4" class="text-center border-b-n"></th>
            <th rowspan="4" class="text-center">Remarks/<br>Accomplishment</th>
        </tr>
        <tr>
            <th rowspan="3" class="text-center" width="80">Individual Support Documents</th>
            <th rowspan="3" class="text-center" width="80">Report of Supervisor/ Other Offices</th>
        </tr>
        <tr>
            <th class="b-none text-center" colspan="4" width="135" height="30">Rating Guide/Accomplishment</th>
        </tr>
        <tr>
            <th>(Targets + Measures)</th>
            <th class="text-center" width="50">Q</th>
            <th class="text-center" width="50">E</th>
            <th class="text-center" width="50">T</th>
            <th class="text-center" width="50">A</th>
        </tr>
    </thead>
    <tbody id="tbody-form">
        <tr>
            <td><b>{{ $prs[0]->mfo }} ({{ $prs[0]->percent  }}%)</b></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td class="b-none text-left"> <i class="fas fa-plus pl-1"></td>
        </tr>
        @foreach($cores as $core)
        <tr>
            <td>{{ $core->mfo }}</td>
            <td class="text-center">{{ $core->target }}</td>
            <td class="text-center">{{ $core->in_support }}</td>
            <td class="text-center">{{ $core->report_sup }}</td>
            <td class="text-center">{{ $core->alloted }}</td>
            <td class="text-center">{{ $core->div_account }}</td>
            <td class="text-center">{{ $core->qrate }}</td>
            <td class="text-center">{{ $core->erate }}</td>
            <td class="text-center">{{ $core->trate }}</td>
            <td class="text-center">{{ $core->a }}</td>
            <td class="text-center">{{ $core->remarks }}</td>
            <td class="b-none text-left"> <i class="fas fa-plus pl-1"></td>
        </tr>
        @endforeach
        <tr>
            <td><b>{{ $prs[1]->mfo }} ({{ $prs[1]->percent  }}%)</b></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td class="b-none text-left"> <i class="fas fa-plus pl-1"></td>
        </tr>
        @foreach($strats as $strat)
        <tr>
            <td>{{ $strat->mfo }}</td>
            <td class="text-center">{{ $strat->target }}</td>
            <td class="text-center">{{ $strat->in_support }}</td>
            <td class="text-center">{{ $strat->report_sup }}</td>
            <td class="text-center">{{ $strat->alloted }}</td>
            <td class="text-center">{{ $strat->div_account }}</td>
            <td class="text-center">{{ $strat->qrate }}</td>
            <td class="text-center">{{ $strat->erate }}</td>
            <td class="text-center">{{ $strat->trate }}</td>
            <td class="text-center">{{ $strat->a }}</td>
            <td class="text-center">{{ $strat->remarks }}</td>
            <td class="b-none text-left"> <i class="fas fa-plus pl-1"></td>
        </tr>
        @endforeach
        <tr>
            <td><b>{{ $prs[2]->mfo }} ({{ $prs[2]->percent  }}%)</b></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td class="b-none text-left"> <i class="fas fa-plus pl-1"></td>
        </tr>
        @foreach($supports as $supp)
        <tr>
            <td>{{ $supp->mfo }}</td>
            <td class="text-center">{{ $supp->target }}</td>
            <td class="text-center">{{ $supp->in_support }}</td>
            <td class="text-center">{{ $supp->report_sup }}</td>
            <td class="text-center">{{ $supp->alloted }}</td>
            <td class="text-center">{{ $supp->div_account }}</td>
            <td class="text-center">{{ $supp->qrate }}</td>
            <td class="text-center">{{ $supp->erate }}</td>
            <td class="text-center">{{ $supp->trate }}</td>
            <td class="text-center">{{ $supp->a }}</td>
            <td class="text-center">{{ $supp->remarks }}</td>
            <td class="b-none text-left"> <i class="fas fa-plus pl-1"></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection