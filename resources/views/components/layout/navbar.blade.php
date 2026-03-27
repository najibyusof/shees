@php
    $routeName = request()->route()?->getName() ?? 'dashboard';
    $pageTitleMap = [
        'dashboard' => 'Dashboard',
        'incidents.' => 'Incident Management',
        'trainings.' => 'Training Management',
        'inspection-checklists.' => 'Inspection Checklists',
        'inspections.' => 'Inspections',
        'site-audits.' => 'Audit & NCR',
        'reports.' => 'Reports',
        'worker-tracking.' => 'Worker Tracking',
        'audit.logs' => 'Audit Logs',
        'admin.roles.' => 'Role & Permission Management',
        'admin.roles' => 'Role & Permission Management',
        'admin.users.' => 'User Management',
        'admin.users' => 'User Management',
        'profile.' => 'Profile',
    ];

    $pageTitle = 'Workspace';
    foreach ($pageTitleMap as $pattern => $label) {
        if (str_ends_with($pattern, '.') && str_starts_with($routeName, $pattern)) {
            $pageTitle = $label;
            break;
        }

        if ($routeName === $pattern) {
            $pageTitle = $label;
            break;
        }
    }
@endphp

<nav class="sticky top-0 z-40 border-b border-gray-200 bg-white/85 backdrop-blur-xl dark:border-gray-700 dark:bg-gray-800/90">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <button @click="sidebarOpen = true"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border ui-border ui-text-muted transition hover:ui-surface-soft lg:hidden"
                type="button" aria-label="Open sidebar">
                <x-ui.icon name="menu" class="h-5 w-5" />
            </button>

            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-teal-700 dark:text-teal-300">SHEES</p>
                <p class="text-sm font-semibold ui-text">{{ $pageTitle }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button @click="toggleTheme()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border ui-border ui-text-muted transition hover:ui-surface-soft"
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
                        class="relative inline-flex h-9 w-9 items-center justify-center rounded-xl border ui-border ui-text-muted transition hover:ui-surface-soft"
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
                        class="absolute right-0 z-20 mt-2 w-80 rounded-2xl border ui-border ui-surface p-3 shadow-xl">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold ui-text">Notifications</p>
                            @if ($unreadCount > 0)
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-teal-700 hover:underline">
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
                                        $title = $data['incident_title'] ?? ($data['training_title'] ?? 'Workflow update');
                                        $status =
                                            $data['status'] ??
                                            ($data['expires_at'] ?? null ? 'Expires: ' . $data['expires_at'] : 'updated');
                                        $url = $data['url'] ?? route('dashboard');
                                    @endphp
                                    <a href="{{ $url }}"
                                        class="block rounded-xl border ui-border ui-surface-soft px-3 py-2 transition hover:ui-surface">
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

                <div x-data="{ openUserMenu: false }" class="relative hidden sm:block">
                    <button @click="openUserMenu = !openUserMenu"
                        class="inline-flex items-center gap-2 rounded-xl border ui-border ui-surface-soft px-3 py-1.5">
                        <span
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-xs font-semibold text-white">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </span>
                        <span class="text-sm font-medium ui-text-muted">{{ Auth::user()->name }}</span>
                        <svg class="h-4 w-4 ui-text-muted" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.1 1.02l-4.25 4.5a.75.75 0 0 1-1.1 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-show="openUserMenu" x-cloak @click.outside="openUserMenu = false"
                        class="absolute right-0 z-20 mt-2 w-44 rounded-xl border ui-border ui-surface p-2 shadow-lg">
                        <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm ui-text-muted transition hover:ui-surface-soft hover:ui-text">
                            Profile Settings
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="mt-1 block w-full rounded-lg px-3 py-2 text-left text-sm text-rose-700 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/30">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <x-ui.button :href="route('login')" variant="secondary" size="md">
                    Login
                </x-ui.button>
            @endauth
        </div>
    </div>
</nav>
