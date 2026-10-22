<div class="dash-card" id="registration-history">
    <div class="dash-card-header">
        <h5>Registration History</h5>
        <span class="audit-meta">Desktop HR Kiosk / Android Time Entry</span>
    </div>
    <div class="dash-card-body">
        <p class="audit-meta">Saved registration history. Enroller identities are reported by the apps. These filters apply only to registration history.</p>
        <form action="{{ route('audit-logs.index') }}#registration-history" method="GET">
            @foreach($filters as $key => $value)
                @if(!str_starts_with($key, 'registration_'))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <div class="audit-filter-grid">
                <div class="audit-field">
                    <label for="registration-source">Source</label>
                    <select id="registration-source" name="registration_source" class="form-control audit-select">
                        <option value="">Both apps</option>
                        <option value="desktop" {{ ($filters['registration_source'] ?? '') === 'desktop' ? 'selected' : '' }}>Desktop HR Kiosk</option>
                        <option value="android" {{ ($filters['registration_source'] ?? '') === 'android' ? 'selected' : '' }}>Android Time Entry</option>
                    </select>
                </div>
                <div class="audit-field">
                    <label for="registration-enroller">Enroller employee ID</label>
                    <input id="registration-enroller" name="registration_enroller" class="form-control" maxlength="100" value="{{ $filters['registration_enroller'] ?? '' }}">
                </div>
                <div class="audit-field">
                    <label for="registration-person">Enrolled employee ID</label>
                    <input id="registration-person" name="registration_person" class="form-control" maxlength="100" value="{{ $filters['registration_person'] ?? '' }}">
                </div>
                <div class="audit-field">
                    <label for="registration-kiosk">HR Kiosk (desktop)</label>
                    <select id="registration-kiosk" name="registration_kiosk" class="form-control audit-select audit-remote" data-type="kiosk">
                        <option value="">All kiosks</option>
                        @if(isset($filters['registration_kiosk']) && $filters['registration_kiosk'] !== '')
                            <option value="{{ $filters['registration_kiosk'] }}" selected>{{ $registrationHistory['kiosks']->get($filters['registration_kiosk']) ?: 'HR Kiosk (name unavailable)' }}</option>
                        @endif
                    </select>
                </div>
                <div class="audit-field">
                    <label for="registration-from">Registration from</label>
                    <input id="registration-from" name="registration_from" type="date" class="form-control" value="{{ $filters['registration_from'] ?? '' }}">
                </div>
                <div class="audit-field">
                    <label for="registration-to">Registration to</label>
                    <input id="registration-to" name="registration_to" type="date" class="form-control" value="{{ $filters['registration_to'] ?? '' }}">
                </div>
                <div class="audit-field audit-filter-actions">
                    <button type="submit" class="btn btn-success">Apply</button>
                    <a href="{{ route('audit-logs.index', collect($filters)->reject(fn ($value, $key) => str_starts_with($key, 'registration_'))->all()) }}#registration-history" class="btn btn-outline-secondary">Clear</a>
                </div>
            </div>
        </form>
        @foreach($registrationHistory['unavailable'] as $source)
            <div class="alert alert-secondary mt-3 mb-0" role="status">{{ $source }} registration history is unavailable.</div>
        @endforeach
    </div>
    <div class="table-responsive">
        <table class="table table-hover audit-table mb-0">
            <thead><tr><th>Registration Date</th><th>Source</th><th>Reported Enroller</th><th>Registrations</th></tr></thead>
            <tbody>
                @forelse($registrationHistory['batches'] ?? [] as $batch)
                    <tr>
                        <td class="text-nowrap">{{ $batch->date }}</td>
                        <td>{{ $batch->source === 'desktop' ? 'Desktop HR Kiosk' : 'Android Time Entry' }}</td>
                        <td>
                            {{ $registrationHistory['people']->get(mb_strtolower((string) $batch->enroller_ID), $batch->enroller_ID ?: 'Unknown') }}
                            <div class="audit-meta">{{ $batch->enroller_ID }}</div>
                        </td>
                        <td>
                            <details>
                                <summary>View {{ count($batch->registrations) }} {{ \Illuminate\Support\Str::plural('registration', count($batch->registrations)) }}</summary>
                                <table class="table table-sm audit-table mt-2 mb-0">
                                    <thead><tr><th>Recorded Time (Manila)</th><th>Enrolled Employee</th><th>Registration</th><th>HR Kiosk</th></tr></thead>
                                    <tbody>
                                        @foreach($batch->registrations as $registration)
                                            <tr>
                                                <td class="text-nowrap">{{ $registration['time'] ?? 'Time not recorded' }}</td>
                                                <td>
                                                    {{ $registrationHistory['people']->get(mb_strtolower($registration['code']), $registration['code']) }}
                                                    <div class="audit-meta">{{ $registration['code'] }}</div>
                                                </td>
                                                <td>{{ $batch->source === 'android' ? 'Face registration' : ($registration['finger'] ? 'Finger '.$registration['finger'] : 'Fingerprint registration') }}</td>
                                                <td>
                                                    @if($registration['device'] !== null)
                                                        {{ $registrationHistory['kiosks']->get($registration['device']) ?: 'HR Kiosk (name unavailable)' }}
                                                    @else
                                                        <span class="audit-meta">Device not recorded</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No registration records on this page match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($registrationHistory['batches'] && $registrationHistory['batches']->hasPages())
        <div class="dash-card-body d-flex justify-content-end pb-1">{{ $registrationHistory['batches']->links('pagination::simple-bootstrap-4') }}</div>
    @endif
</div>
