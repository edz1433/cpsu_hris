<div class="col-lg-3">
    <div class="card card-info card-outline">
        @if($guard == "web")
            <div class="p-1">
                <select class="form-control select2" id="employee" style="width: 100%;" onchange="redirectToLeaveRead(this)">
                    @foreach ($emplalls as $emp)
                        <option value="{{ $emp->id }}" {{ ($employee->id == $emp->id) ? 'selected' : '' }}>
                            {{ strtoupper($emp->fname) }} {{ strtoupper($emp->lname) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="card-body box-profile">
            <div class="text-center position-relative">
                <div class="profile-image-container">
                    <img src="{{ asset('Profile/Employee/'.$employee->profile) }}" alt="User Image" class="profile-user-img img-fluid" id="changeProfilePicture">
                </div>
                <input type="file" id="profilePictureInput" style="display: none;" accept="image/*">
            </div>
            
            <h3 class="profile-username text-center">{{ ucwords(strtolower($employee->fname)) }} {{ ucwords(strtolower($employee->lname)) }}</h3>

            <p class="text-muted text-center">{{ $employee->position }}</p>
    
            <ul class="list-group list-group-unbordered custom-gap">
                <li class="list-group-item">
                    <b>Vacation Leave</b> <span class="float-right mt-1 badge badge-info" id="b-vl">{{ $employee->vl }}</span>
                </li>
                <li class="list-group-item">
                    <b>Mandatory Leave</b> <span class="float-right mt-1 badge badge-info" id="b-ml">{{ $employee->special_pl }}</span>
                </li>
                <li class="list-group-item">
                    <b>Sick Leave</b> <span class="float-right mt-1 badge badge-info" id="b-sl">{{ $employee->sl }}</span>
                </li>
                <li class="list-group-item">
                    <b>Special Privilege Leave</b> <span class="float-right mt-1 badge badge-info">{{ $employee->special_pl }}</span>
                </li>
                <li class="list-group-item">
                    <b>Solo Parent Leave</b> <span class="float-right mt-1 badge badge-info">{{ $employee->solo_pl }}</span>
                </li>
                <li class="list-group-item">
                    <b>Study Leave</b> <span class="float-right mt-1 badge badge-info">0</span>
                </li>
                <li class="list-group-item">
                    <b>10-Day VAWC Leave</b> <span class="float-right mt-1 badge badge-info">0</span>
                </li>
                <li class="list-group-item">
                    <b>Rehabilitation Privilege</b> <span class="float-right mt-1 badge badge-info">0</span>
                </li>
                <li class="list-group-item">
                    <b>Special Leave Benefits for Women</b> <span class="float-right mt-1 badge badge-info">0</span>
                </li>
                <li class="list-group-item">
                    <b>Special Emergency (Calamity) Leave</b> <span class="float-right mt-1 badge badge-info">0</span>
                </li>
                <li class="list-group-item">
                    <b>Adoption Leave</b> <span class="float-right mt-1 badge badge-info">0</span>
                </li>
                <li class="list-group-item">
                    <b>Vacation Service Credit</b> <span class="float-right mt-1 badge badge-info">0</span>
                </li>
            </ul>
        </div>
        <!-- /.card-body -->
    </div>
</div>