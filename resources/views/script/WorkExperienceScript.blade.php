<script>
    function formatNumber(input) {
        let value = input.value.replace(/,/g, '');
        if (!isNaN(value) && value !== '') {
            input.value = Number(value).toLocaleString();
        }
    }

    function isNumberKey(evt) {
        let charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode != 46 && (charCode < 48 || charCode > 57)) {
            evt.preventDefault();
        }
    }
</script>
<script>
    // Filter the work experience cards by any text in them.
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="table_search"]');
        if (!searchInput) return;
        const items = document.querySelectorAll('.eli-item');
        const noMatch = document.getElementById('workNoMatch');

        searchInput.addEventListener('input', function() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let shown = 0;

            items.forEach(item => {
                const found = item.textContent.toLowerCase().includes(searchTerm);
                item.style.display = found ? '' : 'none';
                if (found) shown++;
            });
            noMatch.classList.toggle('d-none', shown > 0);
        });
    });
</script>
<script>
    $(document).on('click', '.workexperience_delete', function(e){
        var id = $(this).val();
        var url = "{{ route('workDelete', ['id' => ':id']) }}";
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
                    type: "POST",
                    url: url,
                    success: function (response) {  
                        $(".workexperience-row.row-" + id).fadeOut(2000);
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

    $(document).on('click', '.workexperience_approve', function(e) {
        var id = $(this).val();
        var url = "{{ route('expApprove', ['id' => ':id']) }}";
        url = url.replace(':id', id);
        
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        Swal.fire({
            title: 'Are you sure?',
            text: "You want to approve this work experience!",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, approve!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: url,
                    success: function(response) {
                        Swal.fire({
                            title: 'Approved!',
                            text: 'The work experience has been approved.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1000
                        });
                        
                        $("#status-" + id)
                        .text("Reviewed") 
                        .removeClass("is-start")
                        .addClass("is-added");
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while approving.',
                            icon: 'error',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                });
            }
        });
    });
</script>