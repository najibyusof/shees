<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PermissionGroupHelper
{
    private const ACTION_ORDER = [
        'view' => 10,
        'create' => 20,
        'edit' => 30,
        'update' => 40,
        'delete' => 50,
        'approve' => 60,
        'manage' => 70,
        'restore' => 80,
        'force-delete' => 90,
        'force_delete' => 90,
    ];

    public static function group(Collection $permissions): Collection
    {
        return $permissions
            ->map(fn ($permission) => self::transformPermission($permission))
            ->groupBy('module_key')
            ->map(function (Collection $items, string $moduleKey) {
                $sortedItems = $items
                    ->sort(function (array $left, array $right) {
                        $leftOrder = self::ACTION_ORDER[$left['action_key']] ?? 999;
                        $rightOrder = self::ACTION_ORDER[$right['action_key']] ?? 999;

                        return [$leftOrder, $left['action_label'], $left['name']] <=> [$rightOrder, $right['action_label'], $right['name']];
                    })
                    ->values();

                return [
                    'key' => $moduleKey,
                    'label' => $sortedItems->first()['module_label'],
                    'permissions' => $sortedItems,
                ];
            })
            ->sortBy('label')
            ->values();
    }

    private static function transformPermission(object $permission): array
    {
        [$moduleKey, $actionKey] = self::extractModuleAndAction((string) $permission->name);

        return [
            'id' => (int) $permission->id,
            'name' => (string) $permission->name,
            'description' => $permission->description ? (string) $permission->description : null,
            'module_key' => $moduleKey,
            'module_label' => self::humanize($moduleKey),
            'action_key' => $actionKey,
            'action_label' => self::humanize($actionKey),
        ];
    }

    private static function extractModuleAndAction(string $permissionName): array
    {
        if (str_contains($permissionName, '.')) {
            $segments = array_values(array_filter(explode('.', $permissionName)));
            $action = array_pop($segments) ?? $permissionName;
            $module = implode('.', $segments);

            return [$module !== '' ? $module : 'general', $action];
        }

        if (preg_match('/^(view|create|edit|update|delete|approve|manage|restore|force-delete|force_delete)_(.+)$/', $permissionName, $matches) === 1) {
            return [$matches[2], $matches[1]];
        }

        return ['general', $permissionName];
    }

    private static function humanize(string $value): string
    {
        return Str::of($value)
            ->replace(['.', '_', '-'], ' ')
            ->title()
            ->toString();
    }
}
