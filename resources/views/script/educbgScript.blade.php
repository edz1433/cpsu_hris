<script>
    $(document).ready(function() {
        let empid = "{{ $empid }}"; 

        function updateData() {
            let schools = [];
            let degrees = [];
            let periods = [];
            let levels = [];
            let years = [];
            let honors = [];

            // Extract data from the fields in the #college-container
            $('#college-container .form-row').each(function() {
                schools.push($(this).find('input[name="coll_school[]"]').val());
                degrees.push($(this).find('input[name="coll_course[]"]').val());
                periods.push($(this).find('input[name="coll_period[]"]').val());
                levels.push($(this).find('input[name="coll_level[]"]').val());
                years.push($(this).find('input[name="coll_grad[]"]').val());
                honors.push($(this).find('input[name="coll_honor[]"]').val());
            });

            // Ensure all fields have the same number of entries
            if (schools.length !== degrees.length || schools.length !== periods.length || schools.length !== levels.length || schools.length !== years.length || schools.length !== honors.length) {
                console.error('Mismatch between array lengths.');
                return;
            }

            $.ajax({
                url: "{{ route('educBgUpdateArray') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    empid: empid,
                    schools: schools,
                    degrees: degrees,
                    periods: periods,
                    levels: levels,
                    years: years,
                    honors: honors
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Data updated successfully!');
                    } else {
                        console.error('Failed to update data:', response.message);
                    }
                },
                error: function(xhr) {
                    console.error('An error occurred:', xhr.responseText);
                }
            });
        }

        // Add row button click event
        $('#add-row-college').click(function() {
            var newRowIndex = $('#college-container .form-row').length;
            var newRow = `
                <div class="form-row mt-3 lbel" data-index="${newRowIndex}">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-delete" style="float: right;"><i class="fas fa-trash fa-sm"></i></button>
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Name of School (Write in full)</label>
                        <input type="text" name="coll_school[]" class="form-control form-control-sm update-field" placeholder="N/A">
                    </div>

                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Basic Education/Degree/Course</label>
                        <input type="text" name="coll_course[]" class="form-control form-control-sm update-field" placeholder="N/A">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Period of attendance</label>
                        <input type="text" name="coll_period[]" class="form-control form-control-sm update-field" placeholder="ex: 2021 - 2024" oninput="validateDateRange(this)" onkeyup="restrictInput(this)">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Highest Level / Units Earned (if not graduated)</label>
                        <input type="text" name="coll_level[]" class="form-control form-control-sm update-field" placeholder="N/A">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                        <input type="month" name="coll_grad[]" class="form-control form-control-sm update-field" placeholder="N/A">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Scholarship / Academic Honors Received</label>
                        <input type="text" name="coll_honor[]" class="form-control form-control-sm update-field" placeholder="N/A">
                    </div>
                </div>
            `;
            $('#college-container').append(newRow);
            updateData();
        });

        // Update data when input fields are modified
        $('#college-container').on('input', '.update-field', function() {
            updateData();
        });

        // Delete row click event
        $('#college-container').on('click', '.btn-delete', function() {
            $(this).closest('.form-row').remove();
            updateData();
        });

        // Field update for individual fields (if you need it separately)
        $('.update-field').on('input', function() {
            var columnid = $(this).data('column-id');
            var columnname = $(this).attr('name');
            var value = $(this).val();

            $.ajax({
                url: '{{ route("educBgUpdate") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: columnid,
                    column: columnname,
                    value: value
                },
                success: function(response) {
                    console.log('Field updated successfully!');
                },
                error: function(xhr, status, error) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.errors;
                        console.error('Validation errors:', errors);
                    } else {
                        console.error('Error:', error);
                    }
                }
            });
        });

        // Batch update for array fields
        $('.update-field-array').on('input', function() {
            updateData();
        });
    });
</script>
