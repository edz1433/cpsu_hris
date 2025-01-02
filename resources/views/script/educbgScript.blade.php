<script>
$('.update-field').on('input', function() {
    columnid = $(this).data('column-id');
    columnname = $(this).attr('name');

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
</script>

<script>
    // Add row button click event for College form
$('#add-row-college').click(function () {
    var newRowIndex = $('.form-group.mtop .form-row.lbel').length; // Count existing rows
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
                <label class="badge badge-secondary text-wrap lbel">Degree</label>
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

    // Append the new row
    $('.form-group.mtop.college-div').last().append(newRow);

    // Reattach event listeners to delete buttons
    attachDeleteEvent();
});

$('#form-container').on('input', '.update-field', function () {
    var columnId = $(this).data('column-id');
    var columnName = $(this).attr('name');
    var value = $(this).val();

    $.ajax({
        url: '{{ route("educBgUpdate") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id: columnId,
            column: columnName,
            value: value
        },
        success: function (response) {
            console.log('Field updated successfully!');
        },
        error: function (xhr, status, error) {
            if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors;
                console.error('Validation errors:', errors);
            } else {
                console.error('Error:', error);
            }
        }
    });
});

// Delete button functionality
$('#form-container').on('click', '.btn-delete', function () {
    $(this).closest('.form-row').remove();
    updateData(); // Call the updateData function after deletion
});

// Handle batch updates for array fields
$('.update-field-array').on('input', function () {
    var names = $('input[name="coll_school[]"]').map(function () { return $(this).val(); }).get();
    var courses = $('input[name="coll_course[]"]').map(function () { return $(this).val(); }).get();

    $.ajax({
        url: '{{ route("educBgUpdateArray") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            schools: names,
            courses: courses
        },
        success: function (response) {
            if (response.success) {
                console.log('Data updated successfully!');
            }
        },
        error: function (xhr, status, error) {
            if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors;
                console.error('Validation errors:', errors);
            } else {
                console.error('Error:', error);
            }
        }
    });
});

// Function to trigger data updates (customize as needed)
function updateData() {
    console.log('Update data triggered!');
    // Add logic for any additional data handling here if needed
}

// Attach delete button event to remove a row
function attachDeleteEvent() {
    $('.btn-delete').off('click').on('click', function () {
        $(this).closest('.form-row').remove();
    });
}

// Initial call to attach delete event listeners
attachDeleteEvent();

</script>