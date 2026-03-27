<nav class="border-b ui-border ui-surface/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <button @click="sidebarOpen = true"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border ui-border ui-text-muted hover:ui-surface-soft lg:hidden"
                type="button" aria-label="Open sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>

            <a href="{{ route('dashboard') }}" class="text-lg font-semibold tracking-tight ui-text">
                {{ config('app.name', 'Shees') }}
            </a>
        </div>

        <div class="flex items-center gap-3">
            <button @click="toggleTheme()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border ui-border ui-text-muted hover:ui-surface-soft"
                type="button" aria-label="Toggle theme">
                <span x-show="theme === 'light'" x-cloak>
                    <x-ui.icon name="moon" class="h-4 w-4" />
                </span>
                <span x-show="theme === 'dark'" x-cloak>
                    <x-ui.icon name="sun" class="h-4 w-4" />
                </span>
            </button>

            @auth
                @php
                    $unreadNotifications = Auth::user()->unreadNotifications()->latest()->limit(5)->get();
                    $unreadCount = Auth::user()->unreadNotifications()->count();
                @endphp

                <div x-data="{ openNotifications: false }" class="relative hidden sm:block">
                    <button @click="openNotifications = !openNotifications"
                        class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg border ui-border ui-text-muted hover:ui-surface-soft"
                        type="button" aria-label="Notifications">
                        <x-ui.icon name="bell" class="h-4 w-4" />
                        @if ($unreadCount > 0)
                            <span
                                class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-semibold text-white">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </button>

                    <div x-show="openNotifications" @click.outside="openNotifications = false" x-cloak
                        class="absolute right-0 z-20 mt-2 w-80 rounded-xl border ui-border ui-surface p-3 shadow-lg">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold ui-text">Notifications</p>
                            @if ($unreadCount > 0)
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-sky-700 hover:underline dark:text-sky-300">
                                        Mark all read
                                    </button>
                                </form>
                            @endif
                        </div>

                        @if ($unreadNotifications->count() > 0)
                            <div class="space-y-2">
                                @foreach ($unreadNotifications as $notification)
                                    @php
                                        $data = $notification->data;
                                        $title =
                                            $data['incident_title'] ?? ($data['training_title'] ?? 'Workflow update');
                                        $status =
                                            $data['status'] ??
                                            ($data['expires_at'] ?? null
                                                ? 'Expires: ' . $data['expires_at']
                                                : 'updated');
                                        $url = $data['url'] ?? route('dashboard');
                                    @endphp
                                    <a href="{{ $url }}"
                                        class="block rounded-lg border ui-border ui-surface-soft px-3 py-2 hover:ui-surface">
                                        <p class="text-sm font-medium ui-text">{{ $title }}</p>
                                        <p class="text-xs ui-text-muted">Status: {{ $status }}</p>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs ui-text-muted">No unread notifications.</p>
                        @endif
                    </div>
                </div>

                <div class="hidden items-center gap-2 rounded-lg border ui-border ui-surface-soft px-3 py-1.5 sm:flex">
                    <span
                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                    <span class="text-sm font-medium ui-text-muted">{{ Auth::user()->name }}</span>
                </div>

                <x-ui.confirm-dialog :action="route('logout')" method="POST" triggerLabel="Logout" triggerVariant="primary"
                    confirmLabel="Yes, Logout" confirmVariant="danger" title="Sign out"
                    message="You are about to end your current session.">
                </x-ui.confirm-dialog>
            @else
                <x-ui.button :href="route('login')" variant="secondary" size="md">
                    Login
                </x-ui.button>
            @endauth
        </div>
    </div>
</nav>
