@auth
    @php
        $notificationsReady = \Illuminate\Support\Facades\Schema::hasTable('notifications');
        $notificationItems = collect();
        $unreadNotificationsCount = 0;

        if ($notificationsReady) {
            $unreadNotificationsCount = auth()->user()->unreadNotifications()->count();
            $notificationItems = auth()->user()->notifications()->latest()->limit(5)->get();
        }
    @endphp

    @if($notificationsReady)
        <div class="dropdown notification-dropdown">
            <button class="btn notification-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
                <i class="fa-solid fa-bell"></i>
                @if($unreadNotificationsCount > 0)
                    <span class="notification-count">{{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}</span>
                @endif
            </button>

            <div class="dropdown-menu dropdown-menu-end notification-menu">
                <div class="notification-menu-head">
                    <div>
                        <strong>Notifikasi</strong>
                        <span>{{ $unreadNotificationsCount }} belum dibaca</span>
                    </div>
                    @if($unreadNotificationsCount > 0)
                        <form action="{{ route('notifications.read') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-link notification-read-btn">Tandai dibaca</button>
                        </form>
                    @endif
                </div>

                @forelse($notificationItems as $notification)
                    @php
                        $data = $notification->data;
                        $url = $data['url'] ?? '#';
                        $icon = $data['icon'] ?? 'fa-bell';
                    @endphp
                    <a href="{{ $url }}" class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
                        <span class="notification-icon"><i class="fa-solid {{ $icon }}"></i></span>
                        <span class="notification-copy">
                            <strong>{{ $data['title'] ?? 'Notifikasi' }}</strong>
                            <small>{{ $data['message'] ?? '' }}</small>
                        </span>
                    </a>
                @empty
                    <div class="notification-empty">Belum ada notifikasi.</div>
                @endforelse
            </div>
        </div>
    @endif
@endauth
