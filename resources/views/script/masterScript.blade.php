<!-- jQuery -->
<script src="{{ asset('app.js') }}"></script>
<!-- jQuery -->
<script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('template/dist/js/adminlte.min.js') }}"></script>

<!-- Toastr -->
<script src="{{ asset('template/plugins/toastr/toastr.min.js') }}"></script>
<!-- SweetAlert2 -->
<script src="{{ asset('template/plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<!-- DataTables  & Plugins -->
<script src="{{ asset('template/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script> 
<script src="{{ asset('template/plugins/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('template/plugins/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ asset('template/plugins/pdfmake/vfs_fonts.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>

<!-- Select2 -->
<script src="{{ asset('template/plugins/select2/js/select2.full.min.js') }}"></script>

<!-- ChartJS -->
<script src="{{ asset('template/plugins/chart.js/Chart.min.js') }}"></script>

<!-- fullCalendar 2.2.5 -->
<script src="{{ asset('template/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('template/plugins/fullcalendar/fullcalendar.js') }}"></script>

{{-- @include('script.dashboardChart') --}}
{{-- Notification --}}
<script>
    @if(Session::has('error'))
        toastr.options = {
            "closeButton":true,
            "progressBar":true,
            'positionClass': 'toast-bottom-right'
        }
        toastr.error("{{ session('error') }}")
    @endif
    
    @if(Session::has('error1'))
        toastr.options = {
            "closeButton":true,
            "progressBar":true,
            'positionClass': 'toast-bottom-center'
        }
        toastr.error("{{ session('error1') }}")
    @endif

    @if(Session::has('success'))
        toastr.options = {
            "closeButton":true,
            "progressBar":true,
            'positionClass': 'toast-bottom-right'
        }
        toastr.success("{{ session('success') }}")
    @endif

    @if($errors->any())
            var errorMessage = "";
            @foreach($errors->all() as $error)
                errorMessage += "{{ $error }}" + "<br>";
            @endforeach
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-bottom-right"
            };
            toastr.error(errorMessage);
    @endif



    $(function () {
        $("#example1").DataTable({
            "responsive": false,
            "lengthChange": true, 
            "autoWidth": true,
            //"buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]

        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

        $("#example2").DataTable({
            "responsive": false,
            "lengthChange": true, 
            "autoWidth": true,
            //"buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]

        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

        $("#example3").DataTable({
            "responsive": true,
            "lengthChange": true, 
            "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]

        }).buttons().container().appendTo('#example3_wrapper .col-md-6:eq(0)');

        $('.select2').select2()
    });
   
</script>
<script>
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>

<script>
    $(document).ready(function() {

        var calendar = $('#calendar').fullCalendar({

            header: {

                left: 'prev,next today',

                center: 'title',

                right: 'month,agendaWeek,agendaDay'

            },

            selectable: true,

            selectHelper: true,

            select: function(start, end, allDay) {

                var defaultStartTime = moment('08:00:00', 'HH:mm:ss');

                var defaultEndTime = moment('17:00:00', 'HH:mm:ss');

                start.set({

                    'hour': defaultStartTime.hour(),

                    'minute': defaultStartTime.minute(),

                    'second': defaultStartTime.second()

                });

                end.set({

                    'hour': defaultEndTime.hour(),

                    'minute': defaultEndTime.minute(),

                    'second': defaultEndTime.second()

                });

                var adjustedEndDate = moment(end).subtract(1, 'day');

                $('#eventTitle').val('');

                $('#eventStartTime').val(start.format('YYYY-MM-DD HH:mm:ss'));

                $('#eventEndTime').val(adjustedEndDate.format('YYYY-MM-DD HH:mm:ss'));

                $('#eventModal').modal('show');

            },

            events: function(start, end, timezone, callback) {

                $.ajax({

                    url: '{{ route('eventShow') }}',

                    method: 'GET',

                    dataType: 'json',

                    success: function(events) {

                        callback(events);

                    },

                    error: function(xhr, status, error) {

                        console.error("Error fetching events: " + error);

                    }

                });

            },

            themeSystem: 'bootstrap',

            selectable: true,

            selectHelper: true,

            navLinks: false,

            displayEventTime: true,

            editable: false,

            eventClick: function(calEvent, jsEvent, view) {

                var startTime = calEvent.start.format('h:mm A');

                var endTime = calEvent.end.format('h:mm A');

                Swal.fire({

                    title: calEvent.title,

                    html: `

                        Start from: ${moment(calEvent.start).format("MMM. D, YYYY, h:mm a")}<br>

                        Ends on: ${moment(calEvent.end).format("MMM. D, YYYY, h:mm a")}`,

                    icon: "success",

                    confirmButtonText: "OK",

                });

            },

        });

        setInterval(function() {

            calendar.fullCalendar('refetchEvents');

        }, 5000);

    });

</script>
<script>
    function calculateAge() {
        var birthday = document.getElementById('bday').value;
        var today = new Date();
        var birthDate = new Date(birthday);
        var age = today.getFullYear() - birthDate.getFullYear();

        if (today.getMonth() < birthDate.getMonth() || (today.getMonth() === birthDate.getMonth() && today.getDate() < birthDate.getDate())) {
            age--;
        }
        document.getElementById('age').value = age;
    }
</script>
<script>
    function handleImageUpload() {
        var fileInput = document.getElementById('profile-image-input');
        var file = fileInput.files[0];

        if (file && file.type.startsWith('image/')) {
            var button = document.getElementById('capture-toggle1');
            button.classList.remove('btn-secondary');
            button.classList.add('btn-success');
            button.innerHTML = '<i class="fas fa-check"></i> Uploaded';
            button.disabled = true; // Disable the button to prevent re-uploading the same image
        } else {
            // Reset the file input if an invalid file is selected
            fileInput.value = '';
            alert('Please select a valid image file.');
        }
    }
</script>
<script>
    $(document).ready(function() {
        // Set up CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    
        // AJAX request to get provinces based on selected region
        $('#region').change(function() {
            var regionId = $(this).val();
            if (regionId) {
                $.ajax({
                    url: "{{ route('getProvinces', ['regionId' => ':regionId']) }}".replace(':regionId', regionId),
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#province').empty();
                        $('#province').append('<option value="">Select Province</option>');
                        $.each(data, function(key, value) {
                            let employeeId = "{{ request()->is('employees/profile/*') ? $empid : '' }}";
                            $('#province').append(
                                '<option value="' + value.province_id + 
                                '" data-column-id="' + employeeId + 
                                '" data-column-name="add_prov">' + 
                                value.name + '</option>'
                            );
                        });
                        $('#province').prop('disabled', false);
                    },
                    error: function() {
                        $('#province').empty();
                        $('#province').append('<option value="">Select Province</option>');
                        $('#province').prop('disabled', true);
                    }
                });
            } else {
                $('#province').empty();
                $('#province').append('<option value="">Select Province</option>');
                $('#province').prop('disabled', true);
            }
        });
    
        // AJAX request to get cities based on selected province
        $('#province').change(function() {
            var provinceId = $(this).val();
            if (provinceId) {
                $.ajax({
                    url: "{{ route('getCities', ['provinceId' => ':provinceId']) }}".replace(':provinceId', provinceId),
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#city').empty();
                        $('#city').append('<option value="">Select City</option>');
                        $.each(data, function(key, value) {
                            let employeeId = "{{ request()->is('employees/profile/*') ? $empid : '' }}";
                            $('#city').append(
                                '<option value="' + value.city_id + 
                                '" data-column-id="' + employeeId + 
                                '" data-column-name="add_city">' + 
                                value.name + '</option>'
                            );
                        });
                        $('#city').prop('disabled', false);
                    },
                    error: function() {
                        $('#city').empty();
                        $('#city').append('<option value="">Select City</option>');
                        $('#city').prop('disabled', true);
                    }
                });
            } else {
                $('#city').empty();
                $('#city').append('<option value="">Select City</option>');
                $('#city').prop('disabled', true);
            }
        });
    
        // AJAX request to get barangays based on selected city
        $('#city').change(function() {
            var cityId = $(this).val();
            if (cityId) {
                $.ajax({
                    url: "{{ route('getBarangays', ['cityId' => ':cityId']) }}".replace(':cityId', cityId),
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#barangay').empty();
                        $('#barangay').append('<option value="">Select Barangay</option>');
                        $.each(data, function(key, value) {
                            let employeeId = "{{ request()->is('employees/profile/*') ? $empid : '' }}";
                            $('#barangay').append(
                                '<option value="' + value.id + 
                                '" data-column-id="' + employeeId + 
                                '" data-column-name="add_brgy">' + 
                                value.name + '</option>'
                            );
                        });
                        $('#barangay').prop('disabled', false);
                    },
                    error: function() {
                        $('#barangay').empty();
                        $('#barangay').append('<option value="">Select Barangay</option>');
                        $('#barangay').prop('disabled', true);
                    }
                });
            } else {
                $('#barangay').empty();
                $('#barangay').append('<option value="">Select Barangay</option>');
                $('#barangay').prop('disabled', true);
            }
        });
    
    
        // AJAX request to get provinces based on selected region
        $('#region1').change(function() {
            var regionId = $(this).val();
            if (regionId) {
                $.ajax({
                    url: "{{ route('getProvinces', ['regionId' => ':regionId']) }}".replace(':regionId', regionId),
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#province1').empty();
                        $('#province1').append('<option value="">Select Province</option>');
                        $.each(data, function(key, value) {
                            let employeeId = "{{ request()->is('employees/profile/*') ? $empid : '' }}";
                            $('#province1').append(
                                '<option value="' + value.province_id + 
                                '" data-column-id="' + employeeId + 
                                '" data-column-name="padd_prov">' + 
                                value.name + '</option>'
                            );
                        });
                        $('#province1').prop('disabled', false);
                    },
                    error: function() {
                        $('#province1').empty();
                        $('#province1').append('<option value="">Select Province</option>');
                        $('#province1').prop('disabled', true);
                    }
                });
            } else {
                $('#province1').empty();
                $('#province1').append('<option value="">Select Province</option>');
                $('#province1').prop('disabled', true);
            }
        });
    
        // AJAX request to get cities based on selected province
        $('#province1').change(function() {
            var provinceId = $(this).val();
            if (provinceId) {
                $.ajax({
                    url: "{{ route('getCities', ['provinceId' => ':provinceId']) }}".replace(':provinceId', provinceId),
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#city1').empty();
                        $('#city1').append('<option value="">Select City</option>');
                        $.each(data, function(key, value) {
                            let employeeId = "{{ request()->is('employees/profile/*') ? $empid : '' }}";
                            $('#city1').append(
                                '<option value="' + value.city_id + 
                                '" data-column-id="' + employeeId + 
                                '" data-column-name="padd_city">' + 
                                value.name + '</option>'
                            );
                        });
                        $('#city1').prop('disabled', false);
                    },
                    error: function() {
                        $('#city1').empty();
                        $('#city1').append('<option value="">Select City</option>');
                        $('#city1').prop('disabled', true);
                    }
                });
            } else {
                $('#city1').empty();
                $('#city1').append('<option value="">Select City</option>');
                $('#city1').prop('disabled', true);
            }
        });
    
        // AJAX request to get barangays based on selected city
        $('#city1').change(function() {
            var cityId = $(this).val();
            if (cityId) {
                $.ajax({
                    url: "{{ route('getBarangays', ['cityId' => ':cityId']) }}".replace(':cityId', cityId),
                    type: "GET",
                    dataType: "json",
                    success: function(data) {
                        $('#barangay1').empty();
                        $('#barangay1').append('<option value="">Select Barangay</option>');
                        $.each(data, function(key, value) {
                            let employeeId = "{{ request()->is('employees/profile/*') ? $empid : '' }}";
                            $('#barangay1').append(
                                '<option value="' + value.id + 
                                '" data-column-id="' + employeeId + 
                                '" data-column-name="padd_brgy">' + 
                                value.name + '</option>'
                            );
                        });
                        $('#barangay1').prop('disabled', false);
                    },
                    error: function() {
                        $('#barangay1').empty();
                        $('#barangay1').append('<option value="">Select Barangay</option>');
                        $('#barangay1').prop('disabled', true);
                    }
                });
            } else {
                $('#barangay1').empty();
                $('#barangay1').append('<option value="">Select Barangay</option>');
                $('#barangay1').prop('disabled', true);
            }
        });
    });
</script>
@if(request()->is('employees') || request()->is('employees/*'))
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
@endif

@if(request()->is('employees/pds/*') || request()->is('pds') || request()->is('pds/*'))
<script>
    $(document).ready(function(){
        $('#changeProfilePicture').on('click', function(){
            $('#profilePictureInput').click();
        });
    
        $('#profilePictureInput').on('change', function(){
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    $('#changeProfilePicture').attr('src', event.target.result);
                }
                reader.readAsDataURL(file);
    
                const formData = new FormData();
                formData.append('profileImage', file);
    
                $.ajax({
                    url: '{{ route("updateProfilePicture", $empid) }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Profile Updated!',
                            text: 'Your profile picture has been updated successfully.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                });
            }
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('.update-field').on('change', function() {
            var elementType = $(this).prop('tagName').toLowerCase();
            if (elementType === 'input' || elementType === 'textarea') {
                columnid = $(this).data('column-id');
                columnname = $(this).data('column-name');
            } else if (elementType === 'select') {
                columnid = $(this).find('option:selected').data('column-id');
                columnname = $(this).find('option:selected').data('column-name');
            }

            var value = $(this).val();

            $.ajax({
                url: '{{ route("employeeUpdate") }}',
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
    });
</script>
@endif
<script>
    // Convert height from centimeters to feet and inches
    function convertHeight() {
        var cm = parseFloat(document.getElementById('height_cm').value);
        if (!isNaN(cm)) {
            var totalInches = cm / 2.54;
            var feet = Math.floor(totalInches / 12);
            var inches = Math.round(totalInches % 12); // Round inches to nearest whole number
            var formattedHeight = feet + "'" + inches + '"';
            document.getElementById('height_ft').value = formattedHeight;
        } else {
            document.getElementById('height_ft').value = '';
        }
    }

    // Convert height from feet and inches to centimeters
    function convertHeightToFtIn() {
        var heightFt = document.getElementById('height_ft').value;
        if (heightFt) {
            var feet = parseFloat(heightFt.split("'")[0]);
            var inches = parseFloat(heightFt.split("'")[1].replace('"', ''));
            var totalInches = feet * 12 + inches;
            var cm = totalInches * 2.54;
            document.getElementById('height_cm').value = Math.round(cm); // Round cm to nearest whole number
        } else {
            document.getElementById('height_cm').value = '';
        }
    }

    // Convert weight from kilograms to pounds
    function convertWeightKgToLb() {
        var weightKg = parseFloat(document.getElementById('weight_kg').value);
        if (!isNaN(weightKg)) {
            var weightLb = weightKg * 2.20462;
            document.getElementById('weight_lb').value = Math.round(weightLb); // Round weight in pounds
        } else {
            document.getElementById('weight_lb').value = '';
        }
    }

    // Convert weight from pounds to kilograms
    function convertWeightLbToKg() {
        var weightLb = parseFloat(document.getElementById('weight_lb').value);
        if (!isNaN(weightLb)) {
            var weightKg = weightLb / 2.20462;
            document.getElementById('weight_kg').value = Math.round(weightKg); // Round weight in kilograms
        } else {
            document.getElementById('weight_kg').value = '';
        }
    }

    // Event listener for height in centimeters
    document.getElementById('height_cm').addEventListener('input', function() {
        convertHeight();
    });

    // Event listener for height in feet and inches
    document.getElementById('height_ft').addEventListener('input', function() {
        convertHeightToFtIn();
    });

    // Event listener for weight in kilograms
    document.getElementById('weight_kg').addEventListener('input', function() {
        convertWeightKgToLb();
    });

    // Event listener for weight in pounds
    document.getElementById('weight_lb').addEventListener('input', function() {
        convertWeightLbToKg();
    });

    // Initial conversion on page load
    convertHeight();
</script>
<script>
    document.getElementById('mobile').addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, '').substring(0, 11);
        let formattedValue = '';

        if (value.length > 0) {
            formattedValue = value.substring(0, 4);
        }
        if (value.length > 4) {
            formattedValue += '-' + value.substring(4, 7);
        }
        if (value.length > 7) {
            formattedValue += '-' + value.substring(7, 11);
        }

        e.target.value = formattedValue;
    });
</script>
<script>
    $(document).ready(function() {
        let rowCount = 3;

        $('#addRow').click(function() {
            let newRow = `
                <div class="form-group col-md-8 row${rowCount}">
                    <input type="text" name="mfo[]" class="form-control form-control-sm" placeholder="Enter MFO" required>
                </div>
                <div class="form-group col-md-3 row${rowCount}">
                    <input type="number" name="percent[]" class="form-control form-control-sm" placeholder="Percent" required>
                </div>
                <div class="form-group col-md-1 row${rowCount}">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteRow('row${rowCount}')"><i class="fas fa-times"></i></button>
                </div>
            `;
            $('#newrow').append(newRow);
            rowCount++;
        });
    });

    function deleteRow(rowClass) {
        $('.' + rowClass).remove();
    }
</script>