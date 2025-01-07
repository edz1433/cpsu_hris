<script>
    $(document).ready(function() {
        let empid = "{{ $empid }}"; 
        
        // Function to update educational background data
        function updateData() {
            let schools = [];
            let degrees = [];
            let periods = [];
            let levels = [];
            let years = [];
            let honors = [];
    
            // Extract values from input fields in #college-container
            $('input[name="coll_school[]"]').each(function() {
                schools.push($(this).val());
            });
            $('input[name="coll_course[]"]').each(function() {
                degrees.push($(this).val());
            });
            $('input[name="coll_period[]"]').each(function() {
                periods.push($(this).val());
            });
            $('input[name="coll_level[]"]').each(function() {
                levels.push($(this).val());
            });
            $('input[name="coll_grad[]"]').each(function() {
                years.push($(this).val());
            });
            $('input[name="coll_honor[]"]').each(function() {
                honors.push($(this).val());
            });

            // Validate array lengths
            if (schools.length !== degrees.length || schools.length !== periods.length ||
                schools.length !== levels.length || schools.length !== years.length ||
                schools.length !== honors.length) {
                console.error('Mismatch between array lengths.');
                return;
            }
    
            // Send data via AJAX
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
                }
            });
        }
    
        // Add new row for educational background
        $('#add-row-college').click(function() {
            var newRowIndex = $('#college-container .form-row').length;
            var newRow = `
                <div class="form-row mt-3 lbel" data-index="${newRowIndex}">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-delete" style="float: right;">
                            <i class="fas fa-times fa-sm"></i>
                        </button>
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Name of School</label>
                        <input type="text" name="coll_school[]" class="form-control form-control-sm update-field" placeholder="N/A">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Degree/Course</label>
                        <input type="text" name="coll_course[]" class="form-control form-control-sm update-field" placeholder="N/A">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Period of Attendance</label>
                        <input type="text" name="coll_period[]" class="form-control form-control-sm update-field" placeholder="ex: 2021 - 2024">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Highest Level/Units Earned</label>
                        <input type="text" name="coll_level[]" class="form-control form-control-sm update-field" placeholder="N/A">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Year Graduated</label>
                        <input type="month" name="coll_grad[]" class="form-control form-control-sm update-field" placeholder="N/A">
                    </div>
                    <div class="col-md-4">
                        <label class="badge badge-secondary text-wrap lbel">Honors Received</label>
                        <input type="text" name="coll_honor[]" class="form-control form-control-sm update-field" placeholder="N/A">
                    </div>
                </div>
            `;
            $('#college-container').append(newRow);
            updateData();
        });

        // Detect changes in input fields and update data
        $('#college-container').on('input', '.update-field', function() {
            updateData();
        });

        // Handle row deletion
        $('#college-container').on('click', '.btn-delete', function() {
            $(this).closest('.form-row').remove();
            updateData();
        });
    });
</script>
