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

<!-- jQuery UI -->
<script src="{{ asset('template/plugins/jquery-ui/jquery-ui.min.js') }}"></script>

<!-- fullCalendar 2.2.5 -->
<script src="{{ asset('template/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('template/plugins/fullcalendar/main.js') }}"></script>

{{-- @include('script.dashboardChart') --}}
{{-- Notification --}}
<script>
  function showColor(element) {
    var color = element.getAttribute('data-color');

    document.getElementById('bg_color').value = color;

    document.getElementById('submit-bg').className = 'btn ' + color + ' btn-sm';
  }
</script>
<script>
  $(function () {
    function ini_events(ele) {
      ele.each(function () {
        var eventObject = {
          title: $.trim($(this).text())
        }

        $(this).data('eventObject', eventObject)

        $(this).draggable({
          zIndex: 1070,
          revert: true,
          revertDuration: 0
        })
      })
    }

    ini_events($('#external-events div.external-event'))

    var Calendar = FullCalendar.Calendar;
    var Draggable = FullCalendar.Draggable;

    var containerEl = document.getElementById('external-events');
    var checkbox = document.getElementById('drop-remove');
    var calendarEl = document.getElementById('calendar');

    new Draggable(containerEl, {
      itemSelector: '.external-event',
      eventData: function (eventEl) {
        return {
          title: eventEl.innerText,
          backgroundColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
          borderColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
          textColor: window.getComputedStyle(eventEl, null).getPropertyValue('color'),
        };
      }
    });

    var calendar = new Calendar(calendarEl, {
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      themeSystem: 'bootstrap',

      events: function (fetchInfo, successCallback, failureCallback) {
        fetch("{{ route('eventShow') }}")
          .then(response => response.json())
          .then(data => {
            const events = data.map(event => {
              const startDate = new Date(event.start);
              const hours = startDate.getHours();
              const minutes = startDate.getMinutes().toString().padStart(2, '0');
              const ampm = hours >= 12 ? 'PM' : 'AM';
              const hour12 = hours % 12 || 12;
              const timeFormatted = `${hour12}:${minutes} ${ampm} `;
              
              const isSingle = !event.end;

              return {
                title: event.title,
                timeLabel: isSingle ? timeFormatted : null,
                start: event.start,
                end: event.end || null,
                allDay: false,
                backgroundColor: '#0073b7',
                borderColor: '#0073b7',
              };
            });
            successCallback(events);
          })
          .catch(error => {
            console.error('Error loading events:', error);
            failureCallback(error);
          });
      },

      eventContent: function (arg) {
        const event = arg.event;
        const time = event.extendedProps.timeLabel;
        const title = event.title;
        const eventColor = event.backgroundColor || '#0073b7';  // Default to blue if no color is set

        let html = '';

        // Add the blue dot at the start of the title
        html += `<span class="fc-event-dot" style="width: 10px; height: 10px; background-color: ${eventColor}; border-radius: 50%; margin-right: 5px;"></span>`;

        if (time) {
          html += `<div class="fc-time-label">${time}</div>`;
        }
        html += `<div class="fc-event-title">${title}</div>`;

        return { html };
      },

      editable: true,
      droppable: true,
      drop: function (info) {
        if (checkbox.checked) {
          info.draggedEl.parentNode.removeChild(info.draggedEl);
        }

        // Handle the drop event
        const eventObject = $(info.draggedEl).data('eventObject');
        
        // Add the dropped event to the calendar
        calendar.addEvent({
          title: eventObject.title,
          start: info.date,
          backgroundColor: eventObject.backgroundColor,
          borderColor: eventObject.borderColor,
          textColor: eventObject.textColor,
        });
      }
    });

    calendar.render();

    var currColor = '#3c8dbc';

    $('#color-chooser > li > a').click(function (e) {
      e.preventDefault()
      currColor = $(this).css('color')
      $('#add-new-event').css({
        'background-color': currColor,
        'border-color': currColor
      })
    })

    $('#add-new-event').click(function (e) {
      e.preventDefault()
      var val = $('#new-event').val()
      if (val.length == 0) {
        return
      }

      var event = $('<div />')
      event.css({
        'background-color': currColor,
        'border-color': currColor,
        'color': '#fff'
      }).addClass('external-event')
      event.text(val)
      $('#external-events').prepend(event)

      ini_events(event)
      $('#new-event').val('')
    })
  })
</script>
  
  
<script>
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
    });
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
            "lengthChange": false, 
            "autoWidth": true,
            //"buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]

        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

        // $("#example1").DataTable({
        //     "responsive": false,
        //     "lengthChange": false, // Removes the "Show Entries" dropdown
        //     "autoWidth": true,
        //     "searching": true, // Hides the search input
        //     "paging": true, // Enables pagination
        //     "dom": '<"top">rt<"bottom"p><"clear">', // Pagination only at the bottom
        //     "pageLength": 10, // Sets the number of rows per page to 9
        //     //"buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        // }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

        $("#example2").DataTable({
            "responsive": false,
            "lengthChange": true, 
            "autoWidth": true,
            //"buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]

        }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

        $("#example3").DataTable({
            "responsive": false,
            "lengthChange": false, 
            "autoWidth": false,
            // "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]

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
@if(request()->is('pds/work-experience*') || request()->is('pds/voluntary-work*') || request()->is('pds/learning-dev*') || request()->is('dtr/dtr-logs*'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const incDate1 = document.getElementById('inc_date1');
        const incDate2 = document.getElementById('inc_date2');

        function updateDate2MinDate() {
            const date1Value = incDate1.value;
            if (date1Value) {
                const minDate = new Date(date1Value).toISOString().split('T')[0];
                incDate2.setAttribute('min', minDate);
                incDate2.value = '';
                if (incDate2.value && new Date(incDate2.value) < new Date(minDate)) {
                    incDate2.value = '';
                } else {

                }
            } else {
                incDate2.removeAttribute('min');
            }
        }

        function validateDateRange() {
            const date1Value = incDate1.value;
            const date2Value = incDate2.value;

            if (date1Value && date2Value) {
                if (new Date(date1Value) > new Date(date2Value)) {
                    return false;
                } else {
                    return true;
                }
            }
            return true;
        }

        incDate1.addEventListener('change', function() {
            updateDate2MinDate();
            validateDateRange();
        });

        incDate2.addEventListener('change', validateDateRange);
    });
</script>
@endif
<script>
$(document).ready(function() {
    var page = 1;
    var loading = false;
    var maxPages = {{ $notifications->lastPage() }};

    function loadMoreNotifications(page) {
        $.ajax({
            url: '{{ route('notificationload', ':page') }}'.replace(':page', page),
            type: "get",
            beforeSend: function() {
                loading = true;
            }
        })
        .done(function(data) {
            if (data.html === "") {
                loading = false;
                return;
            }

            $('#notifications-container').append(data.html);  
            loading = false;

            if (page >= maxPages) {
                $('.dropdown-menu').off('scroll');
            }
        })
        .fail(function(jqXHR, ajaxOptions, thrownError) {
            console.log('Server error occurred');
            loading = false;
        });
    }

    $('.dropdown-menu').scroll(function() {
        var dropdownMenu = $(this);

        if (dropdownMenu.scrollTop() + dropdownMenu.innerHeight() >= dropdownMenu[0].scrollHeight && !loading) {
            if (page < maxPages) {
                page++;
                loadMoreNotifications(page); 
            }
        }
    });
});
</script>
