@extends('layouts.master')

@section('body')
<style>
    .circle {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background-color: #f0f0f0;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 10px;
    }
    .span-fix {
        display: inline-block;
        width: 125px;
        text-align: left;
    }
</style>
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
                            <a href="{{ route('readPending', 1) }}" data-type="1" class="nav-link pending-nav-link {{ request()->is('pending/1*') ? 'active' : '' }}">
                                <i class="{{ request()->is('pending/1*') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-calendar-check" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/1*') ? 'text-dark' : 'text-muted' }} text-bold">Leave Application</span>
                                <span class="float-right badge badge-warning" id="badge-leave">{{ number_format($leaveappCount) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('readPending', 2) }}" data-type="2" class="nav-link pending-nav-link {{ request()->is('pending/2*') ? 'active' : '' }}">
                                <i class="{{ request()->is('pending/2*') ? 'text-dark' : 'text-muted' }} pr-2 fas fas fa-certificate" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/2*') ? 'text-dark' : 'text-muted' }} text-bold">Eligibility</span>
                                <span class="float-right badge badge-warning" id="badge-eli">{{ number_format($eliCount) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('readPending', 3) }}" data-type="3" class="nav-link pending-nav-link {{ request()->is('pending/3*') ? 'active' : '' }}">
                                <i class="{{ request()->is('pending/3*') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-briefcase" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/3*') ? 'text-dark' : 'text-muted' }} text-bold">Work Experience</span>
                                <span class="float-right badge badge-warning" id="badge-workexp">{{ number_format($workexpCount) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('readPending', 5) }}" data-type="5" class="nav-link pending-nav-link {{ request()->is('pending/5*') ? 'active' : '' }}">
                                <i class="{{ request()->is('pending/5*') ? 'text-dark' : 'text-muted' }} pr-2 fas fas fa-book" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/5*') ? 'text-dark' : 'text-muted' }} text-bold">Learning and Development</span>
                                <span class="float-right badge badge-warning" id="badge-learndev">{{ number_format($learDevCount) }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('readPending', 4) }}" data-type="4" class="nav-link pending-nav-link {{ request()->is('pending/4*') ? 'active' : '' }}">
                                <i class="{{ request()->is('pending/4*') ? 'text-dark' : 'text-muted' }} pr-2 fas fa-hand-holding-heart" style="width: 20px;"></i>
                                <span class="{{ request()->is('pending/4*') ? 'text-dark' : 'text-muted' }} text-bold">Voluntary Work</span>
                                <span class="float-right badge badge-warning" id="badge-volwork">{{ number_format($volWorkCount) }}</span>
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
                <div class="card-tools d-flex justify-content-between align-items-center w-100">
                    <!-- Dropdown list on the left -->
                    <div id="leaveHeaderTools" class="{{ $type == 1 ? '' : 'd-none' }} w-100 d-flex justify-content-between align-items-center">
                        <div class="p-1" style="flex: 1; margin-left: -12px;">
                            <select class="form-control form-control-sm" style="width: 20%;" id="pendingCategorySelect" onchange="changePendingCategory(this)">
                                <option value="0" {{ ($cat == 0) ? 'selected' : '' }}>All</option>
                                <option value="0.1" {{ ($cat == 0.1) ? 'selected' : '' }}>Waiting...</option>
                                <option value="0.2" {{ ($cat == 0.2) ? 'selected' : '' }}>Employee</option>
                                <option value="1" {{ ($cat == 1) ? 'selected' : '' }}>HRMO</option>
                                <option value="2" {{ ($cat == 2) ? 'selected' : '' }}>Supervisor</option>
                                <option value="3" {{ ($cat == 3) ? 'selected' : '' }}>SUCPRES</option>
                                <option value="4" {{ ($cat == 4) ? 'selected' : '' }}>APPROVED</option>
                                <option value="5" {{ ($cat == 5) ? 'selected' : '' }}>DISAPPROVED</option>
                            </select>
                        </div>
                        <form 
                            action="{{ route('leaveReport') }}" method="POST" class="input-group w-50" 
                            target="_blank" style="float: right;">
                            @csrf
                            <input type="text" id="date_range" name="date" placeholder="SELECT DATE" class="form-control form-control-sm">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
                <div class="card-body table-responsive p-0" style="height: 500px;" id="pendingTableContainer">
                    <div class="input-group input-group-sm m-2" style="width: 20%; flex: 0 0 auto; margin-left: 1rem; float: right;">
                        <input type="text" name="table_search" id="pendingSearchInput" class="form-control float-right" placeholder="Search" autocomplete="off">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default" id="btnSearchTrigger">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div> 
                    <table class="table table-head-fixed text-nowrap">
                        <thead> 
                            <tr id="tableHeaderRow">
                                @if($type == 1)
                                <th width="70%">SIGNATORIES STATUS</th>
                                <th width="15%">REMARKS</th>
                                <th width="15%" class="text-center">ACTION</th>
                                @else
                                <th width="80%">FULL NAME</th>
                                <th width="20%" class="text-center">ACTION</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="pendingTableBody">
                            @include('pending.partials.table_rows')
                        </tbody>
                    </table>
                </div>
                <div id="batchStatusContainer" class="p-2 border-top d-flex justify-content-between align-items-center bg-light">
                    <span id="batchInfoText" class="text-muted" style="font-size: 0.875rem;">
                        Showing <strong id="loadedCount">{{ count($employees) }}</strong> of <strong id="totalCount">{{ $totalCount }}</strong> items
                    </span>
                    <div class="d-flex align-items-center">
                        <div id="batchLoadingSpinner" class="spinner-border spinner-border-sm text-primary mr-2 d-none" role="status">
                            <span class="sr-only">Loading batch...</span>
                        </div>
                        <button id="btnLoadNextBatch" class="btn btn-outline-primary btn-sm {{ $hasMore ? '' : 'd-none' }}">
                            <i class="fas fa-download mr-1"></i> Load Next Batch
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="pdfModalPending" tabindex="-1" role="dialog" aria-labelledby="pdfModalPendingLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="pdfModalPendingLabel"><i class="fas fa-file-pdf"></i> Leave Application Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdfIframe" src="" width="100%" height="600px" style="border:none;"></iframe>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-warning btn-sm undo-leave" id="pdfPendingUndoBtn" data-id="" data-to="4"><i class="fas fa-undo"></i> Undo Complete Status</button>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="pdfModalHistory" tabindex="-1" role="dialog" aria-labelledby="pdfModalHistoryLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="pdfModalHistoryLabel"><i class="fas fa-file-pdf"></i> Leave Application Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdfIframeHistory" src="" width="100%" height="600px" style="border:none;"></iframe>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-warning btn-sm undo-leave" id="pdfHistoryUndoBtn" data-id="" data-to="4"><i class="fas fa-undo"></i> Undo Complete Status</button>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</section>

<script>
    const pendingRouteBase = "{{ url('pending') }}";

    let pendingState = {
        type: "{{ $type }}",
        cat: "{{ $cat ?? 0 }}",
        page: {{ $page ?? 1 }},
        limit: {{ $limit ?? 25 }},
        total: {{ $totalCount ?? 0 }},
        hasMore: {{ isset($hasMore) && $hasMore ? 'true' : 'false' }},
        isLoading: false,
        search: ''
    };

    function loadPendingBatch(reset = false) {
        if (pendingState.isLoading) return;
        
        if (reset) {
            pendingState.page = 1;
            pendingState.hasMore = false;
            let colSpan = pendingState.type == 1 ? 3 : 2;
            $('#pendingTableBody').html('<tr><td colspan="' + colSpan + '" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Loading records...</td></tr>');
        }
        
        if (!reset && !pendingState.hasMore) return;
        
        pendingState.isLoading = true;
        $('#batchLoadingSpinner').removeClass('d-none');
        $('#btnLoadNextBatch').prop('disabled', true);
        
        let fetchUrl = pendingRouteBase + '/' + pendingState.type + '/' + (pendingState.cat || 0);
        
        $.ajax({
            url: fetchUrl,
            type: 'GET',
            data: {
                ajax: 1,
                page: pendingState.page,
                limit: pendingState.limit,
                search: pendingState.search
            },
            dataType: 'json',
            success: function(res) {
                pendingState.isLoading = false;
                $('#batchLoadingSpinner').addClass('d-none');
                
                if (res.success) {
                    if (reset) {
                        $('#pendingTableBody').html(res.html);
                    } else {
                        $('#pendingTableBody').append(res.html);
                    }
                    
                    pendingState.total = res.total;
                    pendingState.hasMore = res.has_more;
                    
                    if (res.counts) {
                        $('#badge-leave').text(Number(res.counts.leaveappCount).toLocaleString());
                        $('#badge-eli').text(Number(res.counts.eliCount).toLocaleString());
                        $('#badge-workexp').text(Number(res.counts.workexpCount).toLocaleString());
                        $('#badge-learndev').text(Number(res.counts.learDevCount).toLocaleString());
                        $('#badge-volwork').text(Number(res.counts.volWorkCount).toLocaleString());
                    }
                    
                    let loaded = $('#pendingTableBody tr:not(.no-records)').length;
                    $('#loadedCount').text(loaded);
                    $('#totalCount').text(pendingState.total);
                    
                    if (pendingState.hasMore) {
                        $('#btnLoadNextBatch').removeClass('d-none').prop('disabled', false);
                    } else {
                        $('#btnLoadNextBatch').addClass('d-none');
                    }
                }
            },
            error: function(err) {
                pendingState.isLoading = false;
                $('#batchLoadingSpinner').addClass('d-none');
                $('#btnLoadNextBatch').prop('disabled', false);
                console.error('Failed to load pending batch:', err);
            }
        });
    }

    function changePendingCategory(selectElement) {
        let cat = selectElement.value;
        pendingState.cat = cat;
        pendingState.search = '';
        $('#pendingSearchInput').val('');
        
        let newUrl = pendingRouteBase + '/1/' + cat;
        window.history.pushState({type: 1, cat: cat}, '', newUrl);
        
        loadPendingBatch(true);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Intercept sidebar navigation links
        $(document).on('click', '.pending-nav-link', function(e) {
            e.preventDefault();
            let href = $(this).attr('href');
            let type = $(this).data('type');
            
            $('.pending-nav-link i').removeClass('text-dark').addClass('text-muted');
            $('.pending-nav-link span.text-bold').removeClass('text-dark').addClass('text-muted');
            $('.pending-nav-link').removeClass('active');
            
            $(this).addClass('active');
            $(this).find('i').removeClass('text-muted').addClass('text-dark');
            $(this).find('span.text-bold').removeClass('text-muted').addClass('text-dark');
            
            pendingState.type = type;
            pendingState.cat = 0;
            pendingState.search = '';
            $('#pendingSearchInput').val('');
            
            window.history.pushState({type: type, cat: 0}, '', href);
            
            if (type == 1) {
                $('#leaveHeaderTools').removeClass('d-none');
                $('#pendingCategorySelect').val('0');
                $('#tableHeaderRow').html('<th width="70%">SIGNATORIES STATUS</th><th width="15%">REMARKS</th><th width="15%" class="text-center">ACTION</th>');
            } else {
                $('#leaveHeaderTools').addClass('d-none');
                $('#tableHeaderRow').html('<th width="80%">FULL NAME</th><th width="20%" class="text-center">ACTION</th>');
            }
            
            loadPendingBatch(true);
        });

        // Load next batch button
        $(document).on('click', '#btnLoadNextBatch', function() {
            if (pendingState.hasMore && !pendingState.isLoading) {
                pendingState.page++;
                loadPendingBatch(false);
            }
        });

        // Infinite scroll inside table container
        $('#pendingTableContainer').on('scroll', function() {
            let container = $(this);
            if (container.scrollTop() + container.innerHeight() >= container[0].scrollHeight - 60) {
                if (pendingState.hasMore && !pendingState.isLoading) {
                    pendingState.page++;
                    loadPendingBatch(false);
                }
            }
        });

        // Debounced search input handler
        let searchTimer;
        $(document).on('input', '#pendingSearchInput', function() {
            clearTimeout(searchTimer);
            let val = $(this).val();
            searchTimer = setTimeout(function() {
                pendingState.search = val;
                loadPendingBatch(true);
            }, 300);
        });

        // Browser back/forward button handling
        window.addEventListener('popstate', function(event) {
            let path = window.location.pathname;
            let match = path.match(/\/pending\/(\d+)(?:\/([\d.]+))?/);
            if (match) {
                pendingState.type = match[1] || '1';
                pendingState.cat = match[2] || '0';
                loadPendingBatch(true);
            }
        });
    });
</script>
@endsection