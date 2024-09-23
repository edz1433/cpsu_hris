<script>
$(document).ready(function () {
    $('#signature-preview').on('click', function () {
        $('#signature-file').click();
    });

    $('#signature-file').on('change', function () {
        let formData = new FormData();
        formData.append('signature', $('#signature-file')[0].files[0]);

        $.ajax({
            url: "{{ route('uploadSignature', $empid) }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
            },
            success: function (response) {
                if (response.success) {
                    $('#signature-preview').attr('src', response.image_url);
                    toastr.options = {
                        "closeButton":true,
                        "progressBar":true,
                        'positionClass': 'toast-bottom-right'
                    }
                    toastr.success("Signature Updated Successfully.")
                }
            },
            error: function (xhr) {
                alert('Error: ' + xhr.statusText);
            }
        });
    });
});
</script>