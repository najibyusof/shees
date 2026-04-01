<?php

namespace Tests\Feature;

use App\Models\Permission;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class PermissionSeederConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_used_permissions_are_seeded(): void
    {
        $this->seed(PermissionSeeder::class);

        $seededPermissions = Permission::query()->pluck('name')->all();
        $usedPermissions = $this->extractUsedPermissions();

        $missing = array_values(array_diff($usedPermissions, $seededPermissions));
        sort($missing);

        $this->assertSame(
            [],
            $missing,
            'Permissions used in code but missing in PermissionSeeder: '.implode(', ', $missing)
        );
    }

    public function test_all_role_mapped_permissions_are_seeded(): void
    {
        $this->seed(PermissionSeeder::class);

        $seededPermissions = Permission::query()->pluck('name')->all();
        $mappedPermissions = $this->extractRoleMappedPermissions();

        $missing = array_values(array_diff($mappedPermissions, $seededPermissions));
        sort($missing);

        $this->assertSame(
            [],
            $missing,
            'Permissions referenced in RolePermissionSeeder but missing in PermissionSeeder: '.implode(', ', $missing)
        );
    }

    /**
     * @return array<int, string>
     */
    private function extractUsedPermissions(): array
    {
        $files = [
            ...$this->phpFiles(base_path('app')),
            ...$this->phpFiles(base_path('routes')),
            ...$this->bladeFiles(base_path('resources/views')),
        ];

        $used = [];

        foreach ($files as $filePath) {
            $content = file_get_contents($filePath);
            if ($content === false) {
                continue;
            }

            foreach ($this->extractPermissionMiddlewareValues($content) as $permission) {
                $used[$permission] = true;
            }

            foreach ($this->extractFunctionPermissionValues($content) as $permission) {
                $used[$permission] = true;
            }

            foreach ($this->extractBladePermissionValues($content) as $permission) {
                $used[$permission] = true;
            }
        }

        $permissions = array_keys($used);
        sort($permissions);

        return $permissions;
    }

    /**
     * @return array<int, string>
     */
    private function extractRoleMappedPermissions(): array
    {
        $seederPath = base_path('database/seeders/RolePermissionSeeder.php');
        $content = file_get_contents($seederPath);

        if ($content === false) {
            return [];
        }

        if (! preg_match('/\$roleMap\s*=\s*\[(.*?)\];/s', $content, $roleMapMatch)) {
            return [];
        }

        if (! preg_match_all("/['\"]([a-z0-9_.\-*\s]+)['\"]/i", $roleMapMatch[1], $stringMatches)) {
            return [];
        }

        $permissions = [];

        foreach ($stringMatches[1] as $value) {
            $value = trim($value);

            if ($value === '*' || ! $this->isPermissionName($value)) {
                continue;
            }

            $permissions[$value] = true;
        }

        $result = array_keys($permissions);
        sort($result);

        return $result;
    }

    /**
     * @return array<int, string>
     */
    private function phpFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $files = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function bladeFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        $files = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function extractPermissionMiddlewareValues(string $content): array
    {
        $permissions = [];

        if (preg_match_all("/permission:([a-z0-9_.,\-]+)/i", $content, $matches)) {
            foreach ($matches[1] as $raw) {
                foreach (explode(',', $raw) as $permission) {
                    $permission = trim($permission);
                    if ($this->isPermissionName($permission)) {
                        $permissions[] = $permission;
                    }
                }
            }
        }

        if (preg_match_all("/can:([a-z0-9_.,\-]+)/i", $content, $matches)) {
            foreach ($matches[1] as $raw) {
                foreach (explode(',', $raw) as $ability) {
                    $ability = trim($ability);
                    if ($this->isPermissionName($ability)) {
                        $permissions[] = $ability;
                    }
                }
            }
        }

        return $permissions;
    }

    /**
     * @return array<int, string>
     */
    private function extractFunctionPermissionValues(string $content): array
    {
        $permissions = [];

        $singleValuePatterns = [
            "/hasPermissionTo\\(\\s*['\"]([a-z0-9_.\-]+)['\"]\\s*\\)/i",
            "/Gate::(?:authorize|allows|denies)\\(\\s*['\"]([a-z0-9_.\-]+)['\"]\\s*\\)/i",
        ];

        foreach ($singleValuePatterns as $pattern) {
            if (! preg_match_all($pattern, $content, $matches)) {
                continue;
            }

            foreach ($matches[1] as $permission) {
                if ($this->isPermissionName($permission)) {
                    $permissions[] = $permission;
                }
            }
        }

        if (preg_match_all('/hasAnyPermission\\(\\s*\\[([^\\]]+)\\]/i', $content, $arrayMatches)) {
            foreach ($arrayMatches[1] as $arrayContent) {
                if (! preg_match_all("/['\"]([a-z0-9_.\-]+)['\"]/i", $arrayContent, $valueMatches)) {
                    continue;
                }

                foreach ($valueMatches[1] as $permission) {
                    if ($this->isPermissionName($permission)) {
                        $permissions[] = $permission;
                    }
                }
            }
        }

        return $permissions;
    }

    /**
     * @return array<int, string>
     */
    private function extractBladePermissionValues(string $content): array
    {
        $permissions = [];

        if (preg_match_all("/@can\\(\\s*['\"]([a-z0-9_.\-]+)['\"]/i", $content, $matches)) {
            foreach ($matches[1] as $ability) {
                if ($this->isPermissionName($ability)) {
                    $permissions[] = $ability;
                }
            }
        }

        if (preg_match_all('/@canany\\(\\s*\\[([^\\]]+)\\]/i', $content, $arrayMatches)) {
            foreach ($arrayMatches[1] as $arrayContent) {
                if (! preg_match_all("/['\"]([a-z0-9_.\-]+)['\"]/i", $arrayContent, $valueMatches)) {
                    continue;
                }

                foreach ($valueMatches[1] as $ability) {
                    if ($this->isPermissionName($ability)) {
                        $permissions[] = $ability;
                    }
                }
            }
        }

        return $permissions;
    }

    private function isPermissionName(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        $policyAbilities = [
            'view',
            'viewany',
            'create',
            'update',
            'delete',
            'restore',
            'forcedelete',
            'submit',
            'approve',
            'reject',
            'assignusers',
            'markcompletion',
            'uploadcertificate',
            'logattendance',
            'finalize',
        ];

        if (in_array(strtolower($value), $policyAbilities, true)) {
            return false;
        }

        return str_contains($value, '_') || str_contains($value, '.');
    }
}
