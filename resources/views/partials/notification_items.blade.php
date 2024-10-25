@foreach ($notifications as $notif)
    @php 
        $timeDifference = $notif->notif_created_at ? \Carbon\Carbon::parse($notif->notif_created_at)->timezone('Asia/Manila')->diffForHumans() : ''; 
    @endphp
    <a href="{{ route('leaveStatus', $notif->eid) }}" class="dropdown-item d-flex align-items-center">
        <div class="mr-3">
            <img src="{{ asset('Profile/Employee/'.$notif->profile) }}" class="img-circle" alt="User Profile" width="40" height="40">
        </div>
        <div>
            <p class="mb-0"><strong>{{ ucwords(strtolower($notif->fname . ' ' . $notif->lname)) }}</strong> {{ strtolower($notif->remarks) }}</p>
            <span class="{{ ($notif->notifstat == 0) ? 'text-primary font-weight-bold' : "text-muted" }} text-sm">{{ $timeDifference }}</span>
        </div>
    </a>
    <div class="dropdown-divider"></div>
@endforeach
