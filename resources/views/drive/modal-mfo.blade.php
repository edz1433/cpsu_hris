<style>
    #createOpcrMfoModal .modal-dialog {
        max-width: 40%;
        height: 40%;
        margin: 30px auto;
    }
</style>
<div class="modal fade" id="createOpcrMfoModal" tabindex="-1" role="dialog" aria-labelledby="createOpcrMfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success1" id="createOpcrMfoModalLabel"><b id="functions">PERFORMANCE REVIEW</b></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" data-id="1">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <form id="uploadForm" method="POST" action="{{ request()->is('spms/*') ? route('create-opcr-mfo') : '' }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="opcr-id" id="opcr-id">
                            <div class="form-row" id="newrow">
                            </div>
                            <div id="added-rows">

                            </div>
                            <div class="form-row">
                                <div class="col-md-12 mt-2 text-right">
                                    <button class="btn btn-info btn-sm" type="button" id="addRowMfoRows"><i class="fas fa-plus fa-xs"></i> ADD ROWS</button>
                                    <button class="btn btn-success btn-sm" type="submit"><i class="fas fa-save"></i> Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="opcrMfoData" tabindex="-1" role="dialog" aria-labelledby="opcrMfoDataLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success1" id="opcrMfoDataLabel"><b id="functions">PERFORMANCE REVIEW</b></h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" data-id="1">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <form id="uploadForm" method="POST" action="{{ request()->is('spms/*') ? route('create-opcr-mfo-data') : '' }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="opcr_mfo_id" id="opcr-mfo-id">
                            <div class="form-row align-items-center">
                                <div class="form-group col-md-3">
                                    <label class="text-success1">MFO / PAP's</label>
                                    <textarea name="mfo" rows="3" class="form-control form-control-sm"></textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">TARGETS + MEASURES</label>
                                    <textarea name="target" rows="3" class="form-control form-control-sm"></textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">INDIVIDUAL SUPPORT DOCUMENTS</label>
                                    <textarea name="in_support" rows="3" class="form-control form-control-sm"></textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">REPORT OF SUPERVISOR / OTHER OFFICES</label>
                                    <textarea name="report_sup" rows="3" class="form-control form-control-sm"></textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">DIVISION / INDIVIDUALS ACCOUNTABLE</label>
                                    <textarea name="div_account" rows="3" class="form-control form-control-sm"></textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">QUALITY</label>
                                    <textarea name="quality" rows="3" class="form-control form-control-sm"></textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">EFFICIENCY</label>
                                    <textarea name="efficiency" rows="3" class="form-control form-control-sm"></textarea>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="text-success1">TIMELINESS</label>
                                    <textarea name="timeliness" rows="3" class="form-control form-control-sm"></textarea>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-12 mt-2 text-right">
                                    <button class="btn btn-success btn-sm" type="submit"><i class="fas fa-save"></i> Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const saveButton = document.querySelector('button[type="submit"]');
        const addedRowsContainer = document.getElementById('added-rows');
        const formRowContainer = document.getElementById('newrow');

        function checkTotalPercentage() {
            let totalPercent = 0;
            const percentInputs = document.querySelectorAll('input[name="percent[]"]');
            percentInputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                totalPercent += value;
            });

            const requiredPercent = parseFloat($('#functions').text().match(/\((\d+)%\)/)?.[1] || 0);

            if (totalPercent.toFixed(2) === requiredPercent.toFixed(2)) {
                saveButton.disabled = false;
            } else {
                saveButton.disabled = true;
            }
        }

        // Add event listener for "Add Rows" button
        document.getElementById('addRowMfoRows').addEventListener('click', function () {
            const newRow = `
                <div class="form-row align-items-center">
                    <div class="form-group col-md-10">
                        <textarea name="mfo[]" rows="2" class="form-control form-control-sm"></textarea>
                    </div>
                    <div class="form-group col-md-2">
                        <input type="number" name="percent[]" class="form-control form-control-sm text-center" style="height: 52px; font-size: 25px;" value="">
                    </div>
                </div>
            `;
            addedRowsContainer.insertAdjacentHTML('beforeend', newRow);
            attachInputListeners();
        });

        $('#createOpcrMfoModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const dataId = button.data('cat');
            const opcrid = button.data('id');
            const mfo = button.data('mfo');
            const percent = button.data('percent');

            $('#opcr-id').val(opcrid);
            $('#functions').html(`${mfo} (${percent}%)`);

            const defaultData = {
                1: [
                    @if ($cores->isNotEmpty())
                        @foreach ($cores as $core)
                            { mfo: "{{ $core->mfo }}", percent: {{ $core->percent }} },
                        @endforeach
                    @else
                        { mfo: "MFO 1: Provision of Accessible, Equitable, Quality, and Relevant Curricular Programs", percent: 25 },
                        { mfo: "MFO 2: Excellence in Research and Creative Works", percent: 25 },
                        { mfo: "MFO 3: Delivery of Extension and Community Services", percent: 10 }
                    @endif
                ],
                2: [
                    @if ($strats->isNotEmpty())
                        @foreach ($strats as $strat)
                            { mfo: "{{ $strat->mfo }}", percent: {{ $strat->percent }} },
                        @endforeach
                    @else
                        { mfo: "MFO 4: Production Activities", percent: 10 },
                        { mfo: "MFO 5: Attainment of Good Governance", percent: 10 }
                    @endif
                ],
                3: [
                    @if ($supports->isNotEmpty())
                        @foreach ($supports as $support)
                            { mfo: "{{ $support->mfo }}", percent: {{ $support->percent }} },
                        @endforeach
                    @else
                        { mfo: "MFO 4: Production Activities", percent: 10 },
                        { mfo: "MFO 5: Attainment of Good Governance", percent: 10 }
                    @endif
                ]
            };

            formRowContainer.innerHTML = '';
            addedRowsContainer.innerHTML = '';

            const labels = `
                <div class="form-group col-md-10 row0 d-flex justify-content-between align-items-center" style="margin-bottom: -5px;">
                    <label for="mfo" class="text-success1">MFO's</label>
                </div>
                <div class="form-group col-md-2 row0 d-flex justify-content-between align-items-center" style="margin-bottom: -5px;">
                    <label for="mfo" class="text-success1">PERCENT</label>
                </div>
            `;
            formRowContainer.insertAdjacentHTML('beforeend', labels);

            if (defaultData[dataId]) {
                defaultData[dataId].forEach(item => {
                    const newRow = `
                        <div class="form-group col-md-10 row0">
                            <textarea name="mfo[]" rows="2" class="form-control form-control-sm">${item.mfo}</textarea>
                        </div>
                        <div class="form-group col-md-2 row0">
                            <input type="text" name="percent[]" class="form-control form-control-sm text-center" style="height: 52px; font-size: 25px;" value="${item.percent}" oninput="this.value = Math.min(100, Math.max(0, this.value.replace(/[^0-9]/g, '')));">
                        </div>
                    `;
                    formRowContainer.insertAdjacentHTML('beforeend', newRow);
                });
            }
            attachInputListeners();
        });

        function attachInputListeners() {
            const percentInputs = document.querySelectorAll('input[name="percent[]"]');
            percentInputs.forEach(input => {
                input.addEventListener('input', checkTotalPercentage);
            });
        }

        attachInputListeners();

        $('#opcrMfoData').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const mfoid = button.data('mfoid');
            $('#opcr-mfo-id').val(mfoid);
        });
    });


</script>

