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