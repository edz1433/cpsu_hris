<script>
    $(document).ready(function() {
        let empid = "{{ $empid }}"; 
    
        function updateData() {
            let skillsHob = [];
            let recognition = [];
            let memOrg = [];
    
            $('input[name="skills_hob[]"]').each(function() {
                skillsHob.push($(this).val().replace(/,/g, '')); // Remove commas
            });
    
            $('input[name="recognition[]"]').each(function() {
                recognition.push($(this).val().replace(/,/g, '')); // Remove commas
            });
    
            $('input[name="mem_org[]"]').each(function() {
                memOrg.push($(this).val().replace(/,/g, '')); // Remove commas
            });
    
            if (skillsHob.length !== recognition.length || skillsHob.length !== memOrg.length) {
                console.error('Mismatch between array lengths.');
                return;
            }
            
            $.ajax({
                url: "{{ route('update-child-oi') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    empid: empid,
                    skills_hob: skillsHob,
                    recognition: recognition,
                    mem_org: memOrg
                },
                success: function(response) {
                    if (response.success) {
                       // console.log('Data updated successfully!');
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
        $('#add-row-familybg').click(function() {
            var newRowIndex = $('#form-container .form-row').length;
            var newRow = `
                <div class="form-row oi-row" data-index="${newRowIndex}">
                    <input type="text" name="skills_hob[]" class="form-control update-child" placeholder="N/A" aria-label="Special skill or hobby">
                    <input type="text" name="recognition[]" class="form-control update-child" placeholder="N/A" aria-label="Non-academic distinction or recognition">
                    <input type="text" name="mem_org[]" class="form-control update-child" placeholder="N/A" aria-label="Membership in association or organization">
                    <button type="button" class="lv-icon-btn is-danger btn-delete" title="Remove row" aria-label="Remove row"><i class="fas fa-trash"></i></button>
                </div>
            `;

            $('#form-container').append(newRow);
            $('#form-container .oi-row:last input').first().trigger('focus');
            updateData();
        });
    
        $('#form-container').on('input', '.update-child', function() {
            // Remove commas from input fields
            $(this).val($(this).val().replace(/,/g, ''));
            updateData();
        });
    
        $('#form-container').on('click', '.btn-delete', function() {
            $(this).closest('.form-row').remove();
            updateData();
        });
    
        $('.update-field').on('input', function() {
            let columnid = $(this).data('column-id');
            let columnname = $(this).attr('name');
            let value = $(this).val().replace(/,/g, ''); // Remove commas
            
            $.ajax({
                url: '{{ route("otherInfoUpdate") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: columnid,
                    column: columnname,
                    value: value
                },
                success: function(response) {
                    // Handle success
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        console.error('Validation errors:', errors);
                    } else {
                        console.error('Error:', xhr.responseText);
                    }
                }
            });
        });
    
    });
    </script>
    