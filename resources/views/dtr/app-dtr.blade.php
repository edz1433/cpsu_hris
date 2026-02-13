<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <!-- Head includes -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">

    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #f5f6f8;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        .page-wrapper {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem 1rem;
        }

        .form-container {
            width: 100%;
            max-width: 480px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .form-header {
            background: #04401F;
            color: white;
            padding: 1.25rem 1.5rem;
            text-align: center;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .form-body {
            padding: 1.75rem 1.5rem;
        }

        /* Improved responsive inputs */
        .form-label {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 0.5rem;
            display: block;
            font-size: 1rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 0.85rem 1.1rem;
            font-size: 1.05rem;
            line-height: 1.5;
            height: auto;
            min-height: 48px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #04401F;
            box-shadow: 0 0 0 0.25rem rgba(4,64,31,0.15);
            outline: none;
        }

        .form-check {
            display: flex;
            align-items: center; /* vertical center */
            gap: 0.5rem;         /* optional spacing */
        }

        .form-check-input {
            width: 2.2em;          /* Keeps it large and touch-friendly */
            height: 2.2em;
            margin: 0 0.4rem 0 0;  /* replace margin-top + margin-right */
            cursor: pointer;
            flex-shrink: 0;        /* Prevents shrinking in flex layout */
            border-radius: 0.4em;  /* Optional: softer corners to match your rounded style */
            box-shadow: 0 0 0 0.2rem rgba(4,64,31,0.1);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(4,64,31,0.25);
        }

        .form-check-label {
            font-size: 1.05rem;
            color: #2d3748;
        }

        .btn-primary-company {
            background-color: #04401F;
            border: none;
            padding: 1rem;
            font-size: 1.15rem;
            font-weight: 500;
            border-radius: 8px;
            min-height: 52px;
            transition: background 0.2s;
        }

        .btn-primary-company:hover,
        .btn-primary-company:focus {
            background-color: #033518;
        }

        .pdf-section {
            padding: 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .pdf-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: all 0.15s;
        }

        .pdf-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        .pdf-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.1rem;
            color: #1e293b;
            text-decoration: none;
        }

        .pdf-link:active {
            opacity: 0.7;
            transform: scale(0.98);
            transition: all 0.1s;
        }

        .pdf-icon {
            font-size: 2.4rem; /* Slightly reduced for better fit on narrow screens */
            color: #e53e3e;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .pdf-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .pdf-info strong {
            color: #04401F;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .pdf-info small {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Flex wrapper to prevent overflow and enable ellipsis */
        .pdf-left-wrapper {
            display: flex;
            align-items: center;
            flex-grow: 1;
            min-width: 0; /* Critical: allows shrinking and ellipsis */
            overflow: hidden;
        }

        /* Extra mobile breathing room */
        @media (max-width: 576px) {
            .form-body,
            .pdf-section {
                padding: 1.5rem 1.25rem;
            }

            .form-control,
            .form-select {
                font-size: 1.1rem;
                padding: 0.9rem 1rem;
            }

            .btn-primary-company {
                font-size: 1.2rem;
                padding: 1.1rem;
            }

            .pdf-icon {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                max-width: 100%;
                border-radius: 0;
                min-height: 100vh;
            }
            .page-wrapper {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <div class="form-container">

            <div class="form-body">

                @if(isset($empdata))
                    <div class="text-center mb-4">
                        <h5 class="mb-1 fw-bold">
                            {{ $empdata->fname }}
                            @if($empdata->mname) {{ $empdata->mname }} @endif
                            {{ $empdata->lname }}
                        </h5>
                        <!-- <small class="text-muted">Employee ID: {{ $empdata->emp_ID }}</small> -->
                    </div>

                    <form action="{{ route('app-dtr-search') }}" method="POST">
                        @csrf
                        <input type="hidden" name="emp_id" value="{{ $empdata->emp_ID }}">

                        <div class="mb-4">
                            <label class="form-label" for="period">
                                <i class="fas fa-calendar-alt me-2 text-muted"></i>Select Period
                            </label>
                            <select name="period" id="period" class="form-select" required>
                                <option value="1" {{ old('period', $period ?? 1) == 1 ? 'selected' : '' }}>1st Half (1–15)</option>
                                <option value="2" {{ old('period', $period ?? 1) == 2 ? 'selected' : '' }}>2nd Half (16–end)</option>
                                <option value="3" {{ old('period', $period ?? 1) == 3 ? 'selected' : '' }}>Whole Month</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="date">
                                <i class="fas fa-calendar me-2 text-muted"></i>Select Month & Year
                            </label>
                            <input type="month" name="date" id="date" class="form-control"
                                   value="{{ old('date', $date ?? now()->format('Y-m')) }}" required>
                        </div>

                        <div class="mb-4 form-check">
                            <input class="form-check-input" type="checkbox" name="overtime" value="1" id="overtime"
                                   {{ old('overtime', $overtime ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="overtime">
                                <i class="fas fa-clock me-2 text-muted"></i>Overtime
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary-company text-light w-100">
                            <i class="fas fa-file-export me-2"></i> Generate
                        </button>
                    </form>
                @else
                    <div class="alert alert-info text-center mb-0">
                        Please select an employee first.
                    </div>
                @endif

            </div>

            @if(isset($dtrFilename))
                <div class="pdf-section">
                    <div class="d-grid gap-3">
                        <!-- Summary DTR -->
                        <div class="pdf-card">
                            <a href="{{ route('app-dtr-pdf', [
                                'empid'    => $empdata->emp_ID,
                                'period'   => $period,
                                'date'     => $date,
                                'overtime' => $overtime ? 1 : 0,
                                'filename' => $dtrFilename
                            ]) }}" class="pdf-link text-decoration-none">
                                <div class="pdf-left-wrapper">
                                    <i class="fas fa-file-pdf pdf-icon"></i>
                                    <div class="pdf-info">
                                        <strong>{{ $dtrFilename }}</strong>
                                        <small>Daily Time Record – Summary</small>
                                    </div>
                                </div>
                                <i class="fas fa-download text-success fs-4 ms-2"></i>
                            </a>
                        </div>

                        <!-- Detailed Logs (disabled until implemented) -->
                        <div class="pdf-card bg-light opacity-75">
                            <div class="pdf-link text-muted">
                                <div class="pdf-left-wrapper">
                                    <i class="fas fa-file-pdf pdf-icon"></i>
                                    <div class="pdf-info">
                                        <strong>{{ $dtrLogsFilename }}</strong>
                                        <small>Detailed Time Logs – Not available yet</small>
                                    </div>
                                </div>
                                <i class="fas fa-clock text-muted fs-4 ms-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <!-- jQuery -->
    <script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Toastr -->
    <script src="{{ asset('template/plugins/toastr/toastr.min.js') }}"></script>
    @if(isset($dtrFilename))
        <script>
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-center",
                "timeOut": "4000",
            };
            toastr.success("Records Generated Successfully");
        </script>
    @endif
</body>
</html>