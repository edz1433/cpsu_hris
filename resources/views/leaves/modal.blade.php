<div class="modal fade" id="leaveModal" tabindex="-1" role="dialog" aria-labelledby="leaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveModalLabel">LEAVE CREDITS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" action="{{ route('leavesCreate') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Date</label>
                                <input class="form-control form-control-sm" type="month" id="date" name="date" required>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Days</label>
                                <input type="hidden" name="empid" value="{{ $employee->id }}">
                                <input class="form-control form-control-sm" type="number" id="days" name="days" min="1" max="30" oninput="updateEquivalent()" required>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Sick Leave</label>
                                <input type="hidden" name="empid" value="{{ $employee->id }}">
                                <input class="form-control form-control-sm" type="text" id="sl" name="sl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required readonly>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Vacation Leave</label>
                                <input class="form-control form-control-sm" type="number" id="vl" name="vl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required readonly>
                            </div>
                        </div>

                        <div class="col-md-12 col-sm-4 mb-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Remarks</label>
                                <textarea class="form-control form-control-sm" type="text" name="remarks" step="0.001" rows="2"></textarea>
                            </div>
                        </div>
            
                        <div class="col-md-12 text-right mt-3">
                            <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </div>
                </form>            
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="leaveModalDeduct" tabindex="-1" role="dialog" aria-labelledby="leaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveModalLabel">LEAVE CREDITS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" action="{{ route('leavescreditDeduct') }}" method="POST">
                    @csrf
                    <div class="row">
               
                        <div class="col-md-6">
                            <div class="form-check">
                                <label class="badge badge-secondary">Sick Leave</label>
                                <input type="hidden" name="empid" value="{{ $employee->id }}">
                                <input class="form-control form-control-sm" type="hidden" id="date" name="date" value="{{ \Carbon\Carbon::now()->format('Y-m') }}" required>
                                <input class="form-control form-control-sm" type="text" name="sl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required>
                            </div>
                        </div>
            
                        <div class="col-md-6">
                            <div class="form-check">
                                <label class="badge badge-secondary">Vacation Leave</label>
                                <input class="form-control form-control-sm" type="number" name="vl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required>
                            </div>
                        </div>

                        <div class="col-md-12 col-sm-4 mb-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Remarks</label>
                                <textarea class="form-control form-control-sm" type="text" name="remarks" step="0.001" rows="2"></textarea>
                            </div>
                        </div>
            
                        <div class="col-md-12 text-right mt-3">
                            <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </div>
                </form>            
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="leaveModalDeductEdit" tabindex="-1" role="dialog" aria-labelledby="leaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveModalLabel">LEAVE CREDITS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" action="{{ route('leavescreditDeductUpdate') }}" method="POST">
                    @csrf
                    <div class="row">
               
                        <div class="col-md-6">
                            <div class="form-check">
                                <label class="badge badge-secondary">Sick Leave</label>
                                <input type="hidden" name="empid" value="{{ $employee->id }}">
                                <input type="hidden" id="lcid" name="lcid">
                                <input class="form-control form-control-sm" type="hidden" id="date" name="date" value="{{ \Carbon\Carbon::now()->format('Y-m') }}" required>
                                <input class="form-control form-control-sm" type="text" id="sl1" name="sl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required>
                            </div>
                        </div>
            
                        <div class="col-md-6">
                            <div class="form-check">
                                <label class="badge badge-secondary">Vacation Leave</label>
                                <input class="form-control form-control-sm" type="number" id="vl1" name="vl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required>
                            </div>
                        </div>

                        <div class="col-md-12 col-sm-4 mb-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Remarks</label>
                                <textarea class="form-control form-control-sm" type="text" id="remarks1" name="remarks" step="0.001" rows="2"></textarea>
                            </div>
                        </div>
            
                        <div class="col-md-12 text-right mt-3">
                            <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </div>
                    </div>
                </form>            
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="leaveEditModal" tabindex="-1" role="dialog" aria-labelledby="leaveEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leaveEditModalLabel">LEAVE CREDITS</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" action="{{ route('leavesUpdate') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Date</label>
                                <input class="form-control form-control-sm" type="month" id="date1" name="date" required>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Days</label>
                                <input type="hidden" id="lcid" name="lcid">
                                <input class="form-control form-control-sm" type="number" id="days1" name="days" min="1" max="30" oninput="updateEquivalent1()" required>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Sick Leave</label>
                                <input type="hidden" name="empid" value="{{ $employee->id }}">
                                <input class="form-control form-control-sm" type="number" id="sl1" name="sl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required readonly>
                            </div>
                        </div>
            
                        <div class="col-md-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Vacation Leave</label>
                                <input class="form-control form-control-sm" type="number" id="vl1" name="vl" step="0.001" min="0" max="30" placeholder="0.00" autocomplete="off" required readonly>
                            </div>
                        </div>

                        <div class="col-md-12 col-sm-4 mb-3">
                            <div class="form-check">
                                <label class="badge badge-secondary">Remarks</label>
                                <textarea class="form-control form-control-sm" type="text" id="remarks1" name="remarks" step="0.001" rows="2"></textarea>
                            </div>
                        </div>
            
                        <div class="col-md-12 text-right mt-3">
                            <button type="submit" name="btn-submit" class="btn btn-success btn-sm">
                                <i class="fas fa-save"></i> Update
                            </button>
                        </div>
                    </div>
                </form>            
            </div>
        </div>
    </div>
</div>