<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;

class GeneratePermissionMatrixCommand extends Command
{
    protected $signature = 'rbac:matrix {--format=table} {--export=}';

    protected $description = 'Generate and display/export permission-role matrix for RBAC audit';

    public function handle(): int
    {
        $format = $this->option('format'); // 'table', 'csv', 'json'
        $export = $this->option('export');

        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        if ($format === 'table') {
            return $this->renderTable($roles, $permissions, $export);
        } elseif ($format === 'csv') {
            return $this->renderCsv($roles, $permissions, $export);
        } elseif ($format === 'json') {
            return $this->renderJson($roles, $permissions, $export);
        }

        $this->error('Invalid format. Use: table, csv, or json');

        return 1;
    }

    private function renderTable(mixed $roles, mixed $permissions, ?string $export): int
    {
        $headers = ['Permission', ...$roles->pluck('name')->toArray()];
        $rows = [];

        foreach ($permissions as $permission) {
            $row = [$permission->name];

            foreach ($roles as $role) {
                $row[] = $role->permissions->contains($permission) ? '✓' : '';
            }

            $rows[] = $row;
        }

        $this->table($headers, $rows);

        if ($export) {
            $content = $this->tableToText($headers, $rows);
            file_put_contents($export, $content);
            $this->info("Matrix exported to: $export");
        }

        return 0;
    }

    private function renderCsv(mixed $roles, mixed $permissions, ?string $export): int
    {
        $output = [];
        $output[] = 'Permission,' . $roles->pluck('name')->join(',');

        foreach ($permissions as $permission) {
            $row = [$permission->name];

            foreach ($roles as $role) {
                $row[] = $role->permissions->contains($permission) ? 'Yes' : 'No';
            }

            $output[] = implode(',', $row);
        }

        $csv = implode("\n", $output);

        if ($export) {
            file_put_contents($export, $csv);
            $this->info("Matrix exported to: $export");
        } else {
            $this->line($csv);
        }

        return 0;
    }

    private function renderJson(mixed $roles, mixed $permissions, ?string $export): int
    {
        $matrix = [];

        foreach ($permissions as $permission) {
            $matrix[$permission->name] = [];

            foreach ($roles as $role) {
                $matrix[$permission->name][$role->name] = $role->permissions->contains($permission);
            }
        }

        $json = json_encode($matrix, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($export) {
            file_put_contents($export, $json);
            $this->info("Matrix exported to: $export");
        } else {
            $this->line($json);
        }

        return 0;
    }

    private function tableToText(array $headers, array $rows): string
    {
        $lines = [];
        $lines[] = implode(' | ', $headers);
        $lines[] = str_repeat('-', 100);

        foreach ($rows as $row) {
            $lines[] = implode(' | ', $row);
        }

        return implode("\n", $lines);
    }
}
