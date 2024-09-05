<script>
    const equivalences = [
        0.042, 0.083, 0.125, 0.167, 0.208, 0.250, 0.292, 0.333, 0.375,
        0.417, 0.458, 0.500, 0.542, 0.583, 0.625, 0.667, 0.708, 0.750,
        0.792, 0.833, 0.875, 0.917, 0.958, 1.000, 1.042, 1.083, 1.125,
        1.167, 1.208, 1.250
    ];

    function updateEquivalent() {
        let daysInput = parseInt(document.getElementById('days').value, 10);
        const sl = document.getElementById('sl');
        const vl = document.getElementById('vl'); 

        if (isNaN(daysInput) || daysInput < 1) {
            sl.value = '';
            vl.value = '';
            return;
        }

        if (daysInput > 30) {
            daysInput = 30;
            document.getElementById('days').value = 30;
        }

        if (daysInput >= 1 && daysInput <= 30) {
            const equivalentValue = equivalences[daysInput - 1];
            sl.value = equivalentValue.toFixed(3);
            vl.value = equivalentValue.toFixed(3);
        } else {
            sl.value = '';
            vl.value = ''; 
        }
    }
</script>
<script>
    function updateEquivalent1() {
        let daysInput = parseInt(document.getElementById('days1').value, 10);
        const sl = document.getElementById('sl1');
        const vl = document.getElementById('vl1'); 

        if (isNaN(daysInput) || daysInput < 1) {
            sl.value = '';
            vl.value = '';
            return;
        }

        if (daysInput > 30) {
            daysInput = 30;
            document.getElementById('days1').value = 30;
        }

        if (daysInput >= 1 && daysInput <= 30) {
            const equivalentValue = equivalences[daysInput - 1];
            sl.value = equivalentValue.toFixed(3);
            vl.value = equivalentValue.toFixed(3);
        } else {
            sl.value = '';
            vl.value = ''; 
        }
    }
</script>
<script>
    function redirectToLeaveRead(select) {
        var empId = select.value;
        if (empId) {
            window.location.href = '{{ route("leavesRead", ":id") }}'.replace(':id', empId);
        }
    }
</script>
<script>
    function handleCheckboxClick(clickedCheckbox) {
        const checkboxes = document.querySelectorAll('input[type="checkbox"][name="leave_type"]');
        
        checkboxes.forEach(checkbox => {
            if (checkbox !== clickedCheckbox) {
                checkbox.checked = false;
            }
        });
    }
</script>
<script>
    function calculateDays() {
        const inc_date1 = document.getElementById('inc_date1').value;
        const inc_date2 = document.getElementById('inc_date2').value;
        const dayField = document.getElementById('day');
        
        if (inc_date1) {
            if (inc_date2) {
                const date1 = new Date(inc_date1);
                const date2 = new Date(inc_date2);
                const timeDifference = date2.getTime() - date1.getTime();
                const dayDifference = Math.ceil(timeDifference / (1000 * 3600 * 24)) + 1;
                
                dayField.value = dayDifference > 0 ? dayDifference : 1;
            } else {
                dayField.value = 1;
            }
        } else {
            dayField.value = '';
        }
    }
</script>
<script>   
    $(document).on('click', '.leaves_delete', function(e){
        var id = $(this).val();
        var url = "{{ route('leavesDelete', ['id' => ':id', 'empid' => ':empid']) }}";
        url = url.replace(':id', id).replace(':empid', '{{ $employee->id }}');

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

<script>   
    $(document).on('click', '.leaves_edit', function(e){
        var id = $(this).data('id');
        var url = "{{ route('leavesEdit', ['id' => ':id']) }}";
        url = url.replace(':id', id);
        $('#lcid').val(id);
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
        });

        $.ajax({
            type: "POST",
            url: url,
            success: function (response) {  
                if(response.data){
                    $('#days1').val(response.data.days);
                    $('#sl1').val(response.data.earn_sl);
                    $('#vl1').val(response.data.earn_vl);
                    $('#remarks1').val(response.data.remarks);
                    $('#date1').val(response.data.date);
                } else {
                    console.log("No leave credit found for this employee.");
                }
            },
        });
    });  
</script>