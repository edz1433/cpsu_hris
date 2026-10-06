<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CPSU HRIS {{ isset($title) ? ' | '.$title : '' }}</title>
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free-v6/css/all.min.css') }}">
    <!-- fullCalendar -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fullcalendar/main.css') }}">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="{{ asset('template/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('template/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('template/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <!-- Custom style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/style.css') }}">
    <!-- CPSU theme layer -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/theme.css') }}">
    <!-- QR -->
    <script src="{{ asset('template/dist/js/html2canvas.min.js') }}"></script>
    <script src="{{ asset('template/dist/js/qrcode.min.js') }}"></script>
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('template/img/CPSU_L.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
    .profile-image {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        margin-top: -7px;
        margin-right: 10px;
    }
    .img-circle1 {
        width: 40px !important;
        height: 40px !important;
        border-radius: 50% !important;
        object-fit: cover !important;
        border: 2px solid #ddd !important;
        display: block !important;
    }

    .nav-item.dropdown .dropdown-menu.notifications{
        width: 500px !important; /* Or whatever width you prefer */
        max-width: none !important; /* Ensure it doesn't get constrained by max-width */
    }
    .btn-success1 {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
    }
    body.modal-open {
        overflow: hidden;   
    }
    .privacy-container h3 {
        margin-top: 1.5rem;
    }
    .privacy-container ul {
        padding-left: 20px;
    }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed sidebar-collapse layout-navbar-fixed text-sm sb-layout">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-warning">
            
            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                @if($guard == "web")
                    @include('layouts.notif-admin')
                @else
                    @include('layouts.notif-employee')
                @endif

                @if(!empty($guard))
                    <li class="nav-item" id="interviewRatingNavItem" style="{{ ($activeInterviewRatingCount ?? 0) > 0 ? '' : 'display:none;' }}">
                        <a class="nav-link text-success1"
                           href="{{ route('interviewAssignments') }}"
                           id="interviewRatingNavLink"
                           title="Interview Ratings">
                            <i class="fas fa-comments"></i>
                            <span class="badge badge-danger navbar-badge" id="interviewRatingBadge">{{ $activeInterviewRatingCount ?? 0 }}</span>
                        </a>
                    </li>
                @endif
                
                <!-- User Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-success1" href="#" role="button" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        @php
                            $profileUrl = asset('Profile/Employee/' . auth()->guard($guard)->user()->profile);
                            $profilePath = public_path('Profile/Employee/' . auth()->guard($guard)->user()->profile);
                        @endphp
                        <img src="{{ file_exists($profilePath) && isset(auth()->guard($guard)->user()->profile) ? $profileUrl : asset('Profile/Employee/default.png') }}" alt="User Image" class="profile-image">
                    </a>                    
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdownMenuLink">
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-power-off fa-xs"></i> Sign Out
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </nav>
        
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dim-green sb" id="mainSidebar">
            <div class="sb-bg" aria-hidden="true"></div>

            <!-- Edge handle: collapse / expand -->
            <button type="button" class="sb-collapse" data-widget="pushmenu" title="Toggle sidebar" aria-label="Toggle sidebar" aria-controls="mainSidebar" aria-expanded="false">
                <i class="fas fa-chevron-left"></i>
            </button>

            <!-- Sidebar header -->
            <div class="sb-header">
                <a href="{{ route('dashboard') }}" class="brand-link sb-brand">
                    <img src="{{ asset('template/img/CPSU_L.png') }}" alt="CPSU Logo" class="brand-image">
                    <span class="brand-text sb-brand-text">
                        <span class="sb-brand-title">CPSU HRIS</span>
                        <span class="sb-brand-sub">HR Information System</span>
                    </span>
                </a>
            </div>

            <!-- Menu search -->
            <div class="sb-search">
                <i class="fas fa-magnifying-glass sb-search-icon" aria-hidden="true"></i>
                <input type="search" id="sbSearch" class="sb-search-input" placeholder="Search menu..." autocomplete="off" aria-label="Search menu">
                <kbd class="sb-kbd">Ctrl K</kbd>
            </div>

            <!-- Sidebar content (scrolls) -->
            <div class="sidebar">
                @include('partials.control')
                <p class="sb-search-empty" id="sbSearchEmpty">No menu items found</p>
            </div>

            <!-- Sidebar footer (pinned) -->
            <div class="sb-footer">
                <div class="sb-user">
                    @php
                        $profileUrl = asset('Profile/Employee/' . auth()->guard($guard)->user()->profile);
                        $profilePath = public_path('Profile/Employee/' . auth()->guard($guard)->user()->profile);
                    @endphp
                    <img src="{{ file_exists($profilePath) && auth()->guard($guard)->check() && auth()->guard($guard)->user()->profile ? $profileUrl : asset('Profile/Employee/default.png') }}"
                         class="sb-avatar"
                         alt="User Image">
                    <div class="sb-user-info">
                        <span class="sb-user-name">
                            {{ ucwords(strtolower(auth()->guard($guard)->user()->fname)) }} {{ ucwords(strtolower(auth()->guard($guard)->user()->lname)) }}
                        </span>
                        <span class="sb-user-role">
                            @if($guard == "employee")
                                {{ auth()->guard($guard)->user()->emp_status == 1 ? auth()->guard($guard)->user()->position : 'Employee' }}
                            @else
                                {{ ucfirst(auth()->guard($guard)->user()->role) }}
                            @endif
                        </span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="sb-logout-form">
                        @csrf
                        <button type="submit" class="sb-logout" title="Sign out" aria-label="Sign out">
                            <i class="fas fa-arrow-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper" style="padding-top: 20px;">
            <!-- Main content -->
            <div class="content">
                @yield('body')
            </div>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
        
        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->

        @if($guard == "employee" && auth()->guard($guard)->user()->dpn == 0)
            <div class="modal fade show" id="dpnModal" tabindex="-1" aria-modal="true" role="dialog" style="display: block; background: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-dialog-scrollable modal-lg">
                    <div class="modal-content shadow">
                        <div class="modal-body px-4 py-3">
                            @include('data-privacy') 
                        </div>

                        <div class="modal-footer justify-content-between px-4 py-3">
                            <small class="text-muted">Central Philippines State University &copy; {{ now()->year }}</small>

                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('dataPrivacyNotice') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success px-4 mr-1">
                                        I Accept
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger px-4">
                                        Decline
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.body.classList.add('modal-open');
            </script>
        @endif

        <div id="dataPrivacyModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="dataPrivacyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content shadow">
                    <div class="modal-body px-4 py-3">
                        @include('data-privacy') 
                    </div>

                    <div class="modal-footer justify-content-between px-4 py-3">
                        <small class="text-muted">Central Philippines State University &copy; {{ now()->year }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Footer -->
        <footer class="main-footer cpsu-footer">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    &copy; {{ now()->year }} Central Philippines State University. All rights reserved.
                    &nbsp;&middot;&nbsp;
                    <a href="#" data-toggle="modal" data-target="#dataPrivacyModal">Data Privacy Policy</a>
                </div>
                <div class="d-none d-sm-inline">
                    Maintained and managed by
                    <a href="https://www.facebook.com/cpsumiso.main" target="_blank" rel="noopener">MIS</a>
                </div>
            </div>
        </footer>


    </div>

@include('script.masterScript')
@include('script.driveScript')
@include('script.officeScript')
@if(auth()->guard('employee')->check())
<script>
    (function () {
        const maintenanceStatusUrl = @json(route('maintenance.status'));
        const maintenancePageUrl = @json(route('getLogin'));

        window.setInterval(function () {
            fetch(maintenanceStatusUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store'
            }).then(function (response) {
                if (response.status === 503) {
                    window.location.replace(maintenancePageUrl);
                }
            }).catch(function () {
                // A temporary network failure should not sign the employee out.
            });
        }, 3000);
    })();
</script>
@endif
@if(request()->is('pds/family-bg/*') || request()->is('pds/family-bg'))
    @include('script.familybgScript')
@endif
@if(request()->is('employees') || request()->is('employees/*'))
    @include('script.employeeScript')
@endif
@if(request()->is('user') || request()->is('user/*'))
    @include('script.userScript')
@endif
@if(request()->is('pds') || request()->is('pds/personal-info') || request()->is('pds/personal-info/*'))
    @include('script.personInfoScript')
@endif
@if(request()->is('pds/educ-bg/*') || request()->is('pds/educ-bg'))
    @include('script.educbgScript')
@endif
@if(request()->is('pds/eligibility/*') || request()->is('pds/eligibility') || isset($eligibilityedit))
    @include('script.eligibilityScript')
@endif
@if(request()->is('pds/work-experience/*') || request()->is('pds/work-experience') || isset($workexperienceedit))
    @include('script.WorkExperienceScript')
@endif
@if(request()->is('pds/voluntary-work/*') || request()->is('pds/voluntary-work-edit/*') || request()->is('pds/voluntary-work') || isset($workexperienceedit))
    @include('script.voluntaryWorksScript')
@endif
@if(request()->is('pds/learning-dev/*') || request()->is('pds/learning-dev-edit/*') || request()->is('pds/learning-dev') || isset($learningdevedit))
    @include('script.learningDevScript')
@endif
@if(request()->is('pds/other-info/*') || request()->is('pds/other-info-edit/*') || request()->is('pds/other-info'))
    @include('script.otherInfoScript')
@endif
@if(request()->is('pds/info-question/*') || request()->is('pds/info-question-edit/*') || request()->is('pds/info-question'))
    @include('script.infoquestionScript')
@endif
@if(request()->is('pds/references*'))
    @include('script.referenceScript')
@endif
@if(request()->is('pds/government-id*'))
    @include('script.govidScript')
@endif
@if(request()->is('leaves/*') || request()->is('leaves') || request()->is('leave*') || request()->is('leave/history') || request()->is('leave/history*'))
    @include('script.leaveCreditScript')
@endif
@if(request()->is('pending/*'))
    @include('script.pendingScript')
@endif
@if(request()->is('pds/signature/*') || request()->is('pds/signature'))
    @include('script.signatureScript')
@endif
@if(!empty($guard))
<script>
document.addEventListener('DOMContentLoaded', function () {
    let interviewRatingNavRunning = false;

    function refreshInterviewRatingNav() {
        if (interviewRatingNavRunning) {
            return;
        }

        interviewRatingNavRunning = true;
        fetch("{{ route('interviewAssignmentsStatus') }}", {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store'
        })
            .then(response => response.json())
            .then(function (data) {
                const count = parseInt(data.count || 0, 10);
                const item = document.getElementById('interviewRatingNavItem');
                const badge = document.getElementById('interviewRatingBadge');
                const link = document.getElementById('interviewRatingNavLink');

                if (!item || !badge || !link) {
                    return;
                }

                badge.textContent = count;
                link.setAttribute('href', "{{ route('interviewAssignments') }}");
                if (count > 0) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            })
            .catch(function () {})
            .finally(function () {
                interviewRatingNavRunning = false;
            });
    }

    refreshInterviewRatingNav();
    window.addEventListener('focus', refreshInterviewRatingNav);
    window.addEventListener('pageshow', refreshInterviewRatingNav);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            refreshInterviewRatingNav();
        }
    });
    setInterval(refreshInterviewRatingNav, 1000);
});
</script>
@endif
<script>
// Sidebar: menu search (Ctrl/Cmd + K), and keep the mini sidebar expanded while anything in it has focus.
(function () {
    var sidebar = document.querySelector('.main-sidebar.sb');
    var input = document.getElementById('sbSearch');
    if (!sidebar || !input) {
        return;
    }
    var menu = sidebar.querySelector('.nav-sidebar');
    var empty = document.getElementById('sbSearchEmpty');

    function matches(el, term) {
        return el.textContent.toLowerCase().indexOf(term) !== -1;
    }

    function filterMenu() {
        var term = input.value.trim().toLowerCase();
        var anyVisible = false;
        var header = null;
        var headerHasItems = false;

        Array.prototype.forEach.call(menu.children, function (li) {
            if (li.classList.contains('nav-header')) {
                if (header) {
                    header.classList.toggle('sb-hidden', !headerHasItems);
                }
                header = li;
                headerHasItems = false;
                return;
            }

            var match = term === '' || matches(li, term);
            li.classList.toggle('sb-hidden', !match);

            if (li.classList.contains('has-treeview')) {
                var parentMatch = term === '' || matches(li.querySelector('.nav-link'), term);
                li.classList.toggle('sb-search-open', match && term !== '');
                Array.prototype.forEach.call(li.querySelectorAll('.nav-treeview > .nav-item'), function (sub) {
                    sub.classList.toggle('sb-hidden', !(parentMatch || matches(sub, term)));
                });
            }

            if (match) {
                anyVisible = true;
                headerHasItems = true;
            }
        });

        if (header) {
            header.classList.toggle('sb-hidden', !headerHasItems);
        }
        empty.classList.toggle('is-visible', !anyVisible);
    }

    function firstResult() {
        var links = menu.querySelectorAll('.nav-item:not(.sb-hidden) > .nav-link');
        for (var i = 0; i < links.length; i++) {
            var href = links[i].getAttribute('href');
            if (href && href !== '#' && links[i].offsetParent !== null) {
                return links[i];
            }
        }
        return null;
    }

    input.addEventListener('input', filterMenu);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            input.value = '';
            filterMenu();
            input.blur();
        } else if (e.key === 'Enter') {
            var link = firstResult();
            if (link) {
                window.location.href = link.href;
            }
        }
    });

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            openOnSmallScreen();
            input.focus();
            input.select();
        }
    });

    // Collapse button: AdminLTE toggles the body class; we stop hover/focus from
    // immediately re-expanding the rail while the pointer is still over it.
    var collapseBtn = sidebar.querySelector('.sb-collapse');
    if (collapseBtn) {
        collapseBtn.addEventListener('click', function () {
            var willCollapse = !document.body.classList.contains('sidebar-collapse');
            collapseBtn.blur();
            sidebar.classList.remove('sidebar-focused');
            if (willCollapse && window.innerWidth >= 992) {
                sidebar.classList.add('sidebar-no-expand');
            }
        });
    }
    // Phones/tablets have no hover: tapping the search icon in the rail opens the drawer.
    function openOnSmallScreen() {
        if (window.innerWidth < 992 && document.body.classList.contains('sidebar-collapse') && collapseBtn) {
            collapseBtn.click();
        }
    }
    sidebar.querySelector('.sb-search').addEventListener('click', function () {
        openOnSmallScreen();
        input.focus();
    });

    // Keep aria-expanded in sync with AdminLTE's body classes
    function syncExpanded() {
        if (collapseBtn) {
            collapseBtn.setAttribute('aria-expanded', document.body.classList.contains('sidebar-collapse') ? 'false' : 'true');
        }
    }
    new MutationObserver(syncExpanded).observe(document.body, { attributes: true, attributeFilter: ['class'] });
    syncExpanded();

    sidebar.addEventListener('mouseleave', function () {
        sidebar.classList.remove('sidebar-no-expand');
    });

    sidebar.addEventListener('focusin', function (e) {
        if (e.target === collapseBtn) {
            return;
        }
        sidebar.classList.add('sidebar-focused');
    });
    sidebar.addEventListener('focusout', function (e) {
        if (!sidebar.contains(e.relatedTarget)) {
            sidebar.classList.remove('sidebar-focused');
        }
    });

    if (/Mac|iPhone|iPad/.test(navigator.platform)) {
        sidebar.querySelector('.sb-kbd').textContent = '⌘ K';
    }
})();
</script>
</body>
</html>
