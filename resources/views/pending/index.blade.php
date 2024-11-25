@extends('layouts.master')

@section('body')
<section class="content">
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header" style="padding: 6px !important; background-color: #3B8682 !important;">
                    <i class="fas fa-spinner text-light"></i><b class="text-light"> PENDING</b>
                </div>
                <div class="card-footer p-0">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a href="{{ route('readPending', 1) }}" class="nav-link">
                                <i class="{{ request()->is('pending/1') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-calendar-check" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/1') ? 'text-dark' : 'text-muted' }} text-bold">Leave Application</span>
                                <span class="float-right badge badge-secondary" class="">0</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('readPending', 2) }}" class="nav-link">
                                <i class="{{ request()->is('pending/2') ? 'text-dark' : 'text-muted' }} pr-2 fas fas fa-certificate" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/2') ? 'text-dark' : 'text-muted' }} text-bold">Eligibility</span>
                                <span class="float-right badge badge-secondary" class="">0</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('readPending', 3) }}" class="nav-link">
                                <i class="{{ request()->is('pending/3') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-briefcase" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/3') ? 'text-dark' : 'text-muted' }} text-bold">Work Experience</span>
                                <span class="float-right badge badge-secondary" class="">0</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('readPending', 4) }}" class="nav-link">
                                <i class="{{ request()->is('pending/4') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-hand-holding-heart" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/4') ? 'text-dark' : 'text-muted' }} text-bold">Voluntary Work</span>
                                <span class="float-right badge badge-secondary" class="">0</span>
                            </a>
                        </li> 
                        <li class="nav-item">
                            <a href="{{ route('readPending', 5) }}" class="nav-link">
                                <i class="{{ request()->is('pending/5') ? 'text-dark' : 'text-muted' }} pr-2 fas fas fa-book" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/5') ? 'text-dark' : 'text-muted' }} text-bold">Learning and Development</span>
                                <span class="float-right badge badge-secondary" class="">0</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header" style="background-color: #3B8682 !important;">
                    <h3 class="card-title"></h3>
                    <div class="card-tools">
                        <div class="input-group input-group-sm" style="width: 100%; margin-right:;">
                            <input type="text" name="table_search" class="form-control float-right" placeholder="Search">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>    
                    </div>
                </div>
                <div class="card-body table-responsive p-0" style="height: 500px;">
                    <table class="table table-head-fixed text-nowrap">
                        <tbody>
                            <thead>
                                <tr>
                                    <th width="80%">FULL NAME</th>
                                    <th width="20%" class="text-center">ACTION</th>
                                </tr>
                            </thead>
                            @foreach ($employees as $emp)
                            @php
                            switch ($type) {
                                case '1':
                                    $route = route('leaveStatus', $emp->id);
                                    break;
                        
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
                            }
                            @endphp
                                <tr>
                                    <td>{{ $emp->lname }}, {{ $emp->fname }} {{ $emp->suffix }} {{ isset($emp->mname) ? strtoupper(substr($emp->mname, 0, 1)).'.' : '' }}</td>
                                    <td class="text-center">
                                        <a href="{{ $route }}" target="_blank" class='btn btn-info btn-sm employee_edit mr-1' style='width: 30px;' value="{{ $emp->id }}">
                                            <i class="fas fa-exclamation-circle" style="font-size: 0.75rem;"></i>  
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="table_search"]');
        const tableRows = document.querySelectorAll('.table tbody tr');
    
        searchInput.addEventListener('input', function() {
            const searchTerm = searchInput.value.toLowerCase();
    
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const found = Array.from(cells).some(cell => cell.textContent.toLowerCase().includes(searchTerm));
                row.style.display = found ? '' : 'none';
            });
        });
    });
</script>
@endsection