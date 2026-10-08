{{-- Other leave credits editor, opened by the gear in the profile column (admin only). --}}
<div class="modal fade" id="modalSettingLeave" tabindex="-1" role="dialog" aria-labelledby="modalSettingLeaveLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content" style="background: #fff;">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSettingLeaveLabel" style="font-size: 16px; font-weight: 700;">Other leave credits</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2" style="font-size: 12.5px;">Each value saves as soon as you change it.</p>
                <form class="form-horizontal" action="{{ route('leavescreditDeduct') }}" method="POST">
                    @csrf
                    <div class="row">
               
                        <div class="col-md-9 mt-1">
                            <strong>Special Privilege Leave</strong>
                        </div>
            
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="special_pl" value="{{ $employee->special_pl }}" data-column-id="{{ $empid ?? null }}" data-column-name="special_pl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>

                        <div class="col-md-9 mt-1">
                            <strong>Solo Parent Leave</strong>
                        </div>
            
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="solo_pl" value="{{ $employee->solo_pl }}" data-column-id="{{ $empid ?? null }}" data-column-name="solo_pl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>

                        <div class="col-md-9 mt-1">
                            <strong>Study Leave</strong>
                        </div>
            
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="study_leave" value="{{ $employee->study_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="study_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>10-Day VAWC Leave</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="vawc_leave" value="{{ $employee->vawc_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="vawc_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>Rehabilitation Privilege</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="rehab_leave" value="{{ $employee->rehab_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="rehab_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>Special Leave Benefits for Women</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="benefits_leave" value="{{ $employee->benefits_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="benefits_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>Special Emergency (Calamity) Leave</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="calamity_leave" value="{{ $employee->calamity_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="calamity_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>Adoption Leave</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="adopt_leave" value="{{ $employee->adopt_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="adopt_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>Vacation Service Credit</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="servcred_leave" value="{{ $employee->servcred_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="servcred_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="col-md-9 mt-1">
                            <strong>Wellness Leave</strong>
                        </div>
                        <div class="col-md-3 mt-1">                            
                            <input class="form-control form-control-sm text-center update-field" type="number" name="well_leave" value="{{ $employee->well_leave }}" data-column-id="{{ $empid ?? null }}" data-column-name="well_leave" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off">
                        </div>
                    </div>
                </form>          
            </div>
        </div>
    </div>
</div>
