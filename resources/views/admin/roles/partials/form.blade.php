@php
    $currentRole = $role ?? null;
    $initialName = old('name', $currentRole?->name ?? '');
    $initialSlug = old('slug', $currentRole?->slug ?? '');
    $initialSelectedPermissionIds = collect(old('permission_ids', $selectedPermissionIds ?? []))
        ->map(fn($id) => (string) $id)
        ->values()
        ->all();
    $modulePayload = $permissionGroups
        ->map(
            fn($group) => [
                'key' => $group['key'],
                'permissionIds' => collect($group['permissions'])
                    ->pluck('id')
                    ->map(fn($id) => (string) $id)
                    ->values()
                    ->all(),
            ],
        )
        ->values()
        ->all();
    $allPermissionIds = $permissionGroups
        ->flatMap(fn($group) => collect($group['permissions'])->pluck('id'))
        ->map(fn($id) => (string) $id)
        ->values()
        ->all();
    $slugWasCustomized = $initialSlug !== '' && $initialSlug !== \Illuminate\Support\Str::slug($initialName);
@endphp

<div x-data="{
    name: @js($initialName),
    slug: @js($initialSlug),
    selectedPermissionIds: @js($initialSelectedPermissionIds),
    allPermissionIds: @js($allPermissionIds),
    modules: @js($modulePayload),
    openModules: {},
    slugLocked: @js($slugWasCustomized),
    init() {
        if (!this.slug && this.name) {
            this.slug = this.slugify(this.name);
        }

        this.modules.forEach((module) => {
            this.openModules[module.key] = true;
        });
    },
    slugify(value) {
        return String(value ?? '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    },
    onNameInput(event) {
        this.name = event.target.value;

        if (!this.slugLocked) {
            this.slug = this.slugify(this.name);
        }
    },
    onSlugInput(event) {
        this.slug = this.slugify(event.target.value);
        this.slugLocked = this.slug !== '' && this.slug !== this.slugify(this.name);
    },
    unique(ids) {
        return [...new Set(ids.map((id) => String(id)))];
    },
    toggleAllPermissions(checked) {
        this.selectedPermissionIds = checked ? [...this.allPermissionIds] : [];
    },
    toggleModulePermissions(moduleKey, permissionIds, checked) {
        const normalizedIds = permissionIds.map((id) => String(id));

        if (checked) {
            this.selectedPermissionIds = this.unique([...this.selectedPermissionIds, ...normalizedIds]);
            return;
        }

        this.selectedPermissionIds = this.selectedPermissionIds.filter((id) => !normalizedIds.includes(String(id)));
    },
    isAllSelected() {
        return this.allPermissionIds.length > 0 &&
            this.allPermissionIds.every((id) => this.selectedPermissionIds.includes(String(id)));
    },
    isModuleFullySelected(moduleKey) {
        const module = this.modules.find((item) => item.key === moduleKey);

        return !!module &&
            module.permissionIds.length > 0 &&
            module.permissionIds.every((id) => this.selectedPermissionIds.includes(String(id)));
    },
    isModulePartiallySelected(moduleKey) {
        const module = this.modules.find((item) => item.key === moduleKey);

        if (!module || module.permissionIds.length === 0) {
            return false;
        }

        const selectedCount = module.permissionIds.filter((id) => this.selectedPermissionIds.includes(String(id))).length;

        return selectedCount > 0 && selectedCount < module.permissionIds.length;
    },
    toggleModuleOpen(moduleKey) {
        this.openModules[moduleKey] = !this.isModuleOpen(moduleKey);
    },
    isModuleOpen(moduleKey) {
        return this.openModules[moduleKey] ?? true;
    },
}" class="space-y-6">
    <x-ui.card title="Role Details" subtitle="Define the role identity and slug used by the admin panel.">
        <div class="grid gap-4 md:grid-cols-2">
            <x-ui.form-input name="name" label="Role Name" placeholder="Enter role name" :value="$initialName" required
                x-model="name" @input="onNameInput($event)" />

            <div class="space-y-1.5">
                <x-ui.form-input name="slug" label="Slug" placeholder="auto-generated-role-slug" :value="$initialSlug"
                    required x-model="slug" @input="onSlugInput($event)" />
                <p class="text-xs ui-text-muted">The slug auto-generates from the role name until you customize it.</p>
            </div>
        </div>

        @if ($currentRole?->isProtectedRole())
            <div
                class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-200">
                This is a protected system role. You can update its permissions, but deletion is blocked.
            </div>
        @endif
    </x-ui.card>

    <x-ui.card title="Permission Assignment"
        subtitle="Assign permissions by module. Use the global toggle for full-access roles or module toggles for faster setup.">
        <div
            class="mb-5 flex flex-col gap-3 rounded-2xl border ui-border ui-surface-soft p-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-semibold ui-text">Permission Coverage</p>
                <p class="text-xs ui-text-muted">
                    <span x-text="selectedPermissionIds.length"></span> of {{ count($allPermissionIds) }} permissions
                    selected
                </p>
            </div>

            <label
                class="inline-flex items-center gap-3 rounded-xl border ui-border bg-white px-4 py-2 text-sm font-medium ui-text shadow-sm dark:bg-gray-800">
                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500"
                    @change="toggleAllPermissions($event.target.checked)" :checked="isAllSelected()">
                <span>Select all permissions</span>
            </label>
        </div>

        <div class="space-y-4">
            @foreach ($permissionGroups as $group)
                <x-ui.permission-group :module-key="$group['key']" :module-label="$group['label']" :permissions="$group['permissions']" />
            @endforeach
        </div>

        @error('permission_ids')
            <p class="mt-3 text-sm text-rose-600">{{ $message }}</p>
        @enderror
        @error('permission_ids.*')
            <p class="mt-3 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </x-ui.card>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <x-ui.button :href="route('admin.roles')" variant="secondary" size="md">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary" size="md">{{ $submitLabel }}</x-ui.button>
    </div>
</div>
