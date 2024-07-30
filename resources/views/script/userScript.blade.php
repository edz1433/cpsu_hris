<script>
    $(".add-form-user").submit(function(e){
        e.preventDefault();
        var formData = new FormData(this);

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
        });
        
        $.ajax({
             type:"POST",
             url:"{{ route('uCreate') }}",
             data: formData,
             cache: false,
             contentType: false,
             processData: false,
             success: function(response){
                $('span#error').text('');
                 // console.log(response);
                if(response.status == 200){
                    $('.add-form-user')[0].reset();
                    $('#modal-users').modal('hide');
                    toastr.options = {
                        "closeButton":true,
                        "progressBar":true,
                        'positionClass': 'toast-bottom-right'
                    }
                    toastr.success("User Successfully Added");
                    setInterval(function () {
                     location.reload();
                     }, 1000);
                }

                if(response.status == 400){
                    $.each(response.error, function (prefix, val) { 
                        $('span.'+prefix+'_error').text(val[0]);
                    });
                }
             }
        });
    });

    $(".update-form-user").submit(function(e){
        e.preventDefault();
        var formData = new FormData(this);

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
        });
        
        $.ajax({
             type:"POST",
             url:"{{ route('uUpdate') }}",
             data: formData,
             cache: false,
             contentType: false,
             processData: false,
             success: function(response){
                $('span#error').text('');
                 // console.log(response);
                if(response.status == 200){
                    $('.update-form-user')[0].reset();
                    $('#modal-users-edit').modal('hide');
                    toastr.options = {
                        "closeButton":true,
                        "progressBar":true,
                        'positionClass': 'toast-bottom-right'
                    }
                    toastr.success("User Successfully Added");
                    setInterval(function () {
                     location.reload();
                     }, 1000);
                }

                if(response.status == 404){
                    $('.update-form-user')[0].reset();
                    $('#modal-users-edit').modal('hide');
                    toastr.options = {
                        "closeButton":true,
                        "progressBar":true,
                        'positionClass': 'toast-bottom-right'
                    }
                    toastr.danger("Username has already been taken");
                     setInterval(function () {
                         location.reload();
                     }, 1000);
                }

                if(response.status == 400){
                    $.each(response.error, function (prefix, val) { 
                        $('span.'+prefix+'_error').text(val[0]);
                    });
                }
             }
        });
    });

     //Delete
    $(document).on('click', '.users-delete', function(e){
        var id = $(this).val();
        var url = "{{ route('uDelete', ['id' => ':id']) }}";
        url = url.replace(':id', id);
        
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
        });
        Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed){
                $.ajax({
                    type: "GET",
                    url: url,
                    success: function (response) {  
                        $("#tr-"+id).fadeOut(2000);
                        Swal.fire({
                        title:'Deleted!',
                        text:'Your file has been deleted.',
                        type:'success',
                        icon: 'warning',
                        showConfirmButton: false,
                        timer: 1000
                        })
                    }
                });
            }
        })
    });

    
    
</script>