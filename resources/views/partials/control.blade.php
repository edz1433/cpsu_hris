<nav style="margin-right: -30px; !important">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link text-success1 {{ request()->is('dashboard') || request()->is('myaccount') ? 'active' : '' }}">
                <i class="pt-1 nav-icon fas fa-tachometer-alt"></i>
                <p>Dashboard</p>
            </a>
        </li>
        @if(auth()->guard($guard)->user()->role !== "employee")
            <li class="nav-item">
                <a href="{{ route('emp_list') }}" class="nav-link text-success1 {{ request()->is('employees') || request()->is('employees/*') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-users"></i>
                    <p>Employees</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('officeList') }}" class="nav-link text-success1 {{ request()->is('officeList') || request()->is('officeEdit/*') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-building"></i>
                    <p>Offices</p>
                </a>
            </li>
                    
            <li class="nav-item">
                <a href="#" class="nav-link text-success1 {{ request()->is('payslip') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-file-invoice"></i>
                    <p>PAYSLIP</p>
                </a>
            </li>
        @endif

        @if(auth()->guard($guard)->user()->role == "employee")
            <li class="nav-item">
                <a href="{{ route('empPDS') }}" class="nav-link text-success1 {{ request()->is('pds') || request()->is('pds/*') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-clipboard"></i>
                    <p>PDS</p>
                </a>
            </li>
        @endif

        <li class="nav-item">
            <a href="{{ route('dtr-read') }}" class="nav-link text-success1 {{ request()->is('dtr') || request()->is('dtr/*') ? 'active' : '' }}">
                <i class="pt-1 nav-icon fas fa-clock"></i>
                <p>DTR</p>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('drive') }}" class="nav-link text-success1 {{ request()->is('spms') || request()->is('spms/*') || request()->is('drive-account') ? 'active' : '' }}">
                <i class="pt-1 nav-icon fas fa-folder"></i>
                <p>SPMS</p>
            </a>
        </li>

        <li class="nav-item">
            <a href="#" class="nav-link text-success1 {{ request()->is('events') ? 'active' : '' }}">
                <i class="pt-1 nav-icon fas fa-calendar"></i>
                <p>Events</p>
            </a>
        </li>

        @if(auth()->guard($guard)->user()->role == "Administrator")

            <li class="nav-item">
                <a href="{{ route('ulist') }}" class="nav-link text-success1 {{ request()->is('ulist') || request()->is('uEdit/*') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-user-cog"></i>
                    <p>Users</p>
                </a>
            </li>

            <li class="nav-item">
                <a href="" class="nav-link text-success1 {{ request()->is('settings') ? 'active' : '' }}">
                    <i class="pt-1 nav-icon fas fa-cogs"></i>
                    <p>Settings</p>
                </a>
            </li>
        
        @endif
    </ul>
</nav>
