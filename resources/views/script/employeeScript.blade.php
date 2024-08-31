<script>
    function toggleStat(value, empId){
        $.ajax({
            url: '{{ route("toggleAcctStat") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: empId,
                stat_1: value ? 1 : 2
            },
            success: function(response) {
                if (response.success) {
                    // alert('User role updated successfully.');
                } else {
                    // alert('Failed to update user role.');
                }
            },
            error: function(xhr) {
                alert('An error occurred. Please try again.');
            }
        });
    }
</script>
<script>
    $(document).ready(function() {
        $('select[name="country"]').prop('disabled', true);
        $('.c-radio').prop('disabled', true);
    
        $('select[name="citizenship"]').change(function() {
            var selectedValue = $(this).val();
    
            if (selectedValue == "2") {
                $('select[name="country"]').prop('disabled', false);
                $('.c-radio').prop('disabled', false);
            } else {
                $('select[name="country"]').prop('disabled', true);
                $('.c-radio').prop('disabled', true);
                $('.c-radio').prop('checked', false);
                $('select[name="country"]').prop('selectedIndex', 0);
            }
        });
    });
</script>