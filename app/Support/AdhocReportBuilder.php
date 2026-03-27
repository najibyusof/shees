<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class AdhocReportBuilder
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function moduleDefinitions(): array
    {
        $modules = config('report_builder.modules', []);

        return is_array($modules) ? $modules : [];
    }

    /**
     * @return array<string, string>
     */
    public function allowedModulesForUser(User $user): array
    {
        $allowed = [];

        foreach ($this->moduleDefinitions() as $key => $module) {
            foreach ($module['permissions'] as $permission) {
                if ($user->hasPermissionTo($permission)) {
                    $allowed[$key] = (string) $module['label'];
                    break;
                }
            }
        }

        return $allowed;
    }

    /**
     * @return array<string, mixed>
     */
    public function moduleDefinition(string $module): array
    {
        $definition = $this->moduleDefinitions()[$module] ?? null;
        if (! is_array($definition)) {
            throw new InvalidArgumentException('Unsupported report module.');
        }

        return $definition;
    }

    /**
     * @param  array<int, string>  $selectedFields
     * @param  array<int, array<string, mixed>>  $filters
     */
    public function buildQuery(string $module, array $selectedFields, array $filters, ?string $sortField, ?string $sortDirection): Builder
    {
        $definition = $this->moduleDefinition($module);
        /** @var class-string<Model> $modelClass */
        $modelClass = $definition['model'];

        $query = $modelClass::query();

        foreach ($definition['joins'] as $join) {
            $query->leftJoin($join['table'], $join['first'], $join['operator'], $join['second']);
        }

        $fieldMap = $definition['fields'];
        $safeFields = array_values(array_filter(array_unique($selectedFields), fn ($field) => isset($fieldMap[$field])));
        if ($safeFields === []) {
            $safeFields = ['id'];
        }

        $selects = [];
        foreach ($safeFields as $field) {
            $selects[] = $fieldMap[$field]['column'].' as '.$field;
        }

        $query->select($selects);

        foreach ($this->sanitizeFilters($filters, $fieldMap) as $filter) {
            $boolean = $filter['boolean'] === 'or' ? 'orWhere' : 'where';
            $fieldConfig = $fieldMap[$filter['field']];
            $column = $fieldConfig['column'];
            $operator = $filter['operator'];
            $value = $this->castValue($filter['value'], $fieldConfig);

            if ($operator === 'between') {
                $valueTo = $this->castValue($filter['value_to'], $fieldConfig);
                if ($valueTo === null || $value === null) {
                    continue;
                }

                $query->{$boolean.'Between'}($column, [$value, $valueTo]);
                continue;
            }

            if ($operator === 'like') {
                if ($value === null || $value === '') {
                    continue;
                }
                $query->{$boolean}($column, 'like', '%'.$value.'%');
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $query->{$boolean}($column, $operator, $value);
        }

        if ($sortField && isset($fieldMap[$sortField])) {
            $query->orderBy($fieldMap[$sortField]['column'], strtolower((string) $sortDirection) === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy($definition['table'].'.id', 'desc');
        }

        return $query;
    }

    /**
     * @param  array<int, array<string, mixed>>  $filters
     * @param  array<string, array<string, mixed>>  $fieldMap
     * @return array<int, array<string, string>>
     */
    public function sanitizeFilters(array $filters, array $fieldMap): array
    {
        $safe = [];
        $allowedOperators = ['=', '!=', 'like', '>', '<', 'between'];

        foreach ($filters as $filter) {
            $field = (string) ($filter['field'] ?? '');
            $operator = (string) ($filter['operator'] ?? '=');

            if (! isset($fieldMap[$field]) || ! in_array($operator, $allowedOperators, true)) {
                continue;
            }

            $fieldType = (string) ($fieldMap[$field]['type'] ?? 'string');
            if (! in_array($operator, $this->allowedOperatorsForType($fieldType), true)) {
                continue;
            }

            $value = (string) ($filter['value'] ?? '');
            $valueTo = (string) ($filter['value_to'] ?? '');
            $boolean = strtolower((string) ($filter['boolean'] ?? 'and')) === 'or' ? 'or' : 'and';

            if ($operator === 'between' && ($value === '' || $valueTo === '')) {
                continue;
            }

            if ($operator !== 'between' && $value === '') {
                continue;
            }

            $safe[] = [
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
                'value_to' => $valueTo,
                'boolean' => $boolean,
            ];
        }

        return $safe;
    }

    public function fieldLabel(string $module, string $field): string
    {
        $fields = $this->moduleDefinition($module)['fields'];

        return (string) ($fields[$field]['label'] ?? $field);
    }

    /**
     * @param  array<int, string>  $selectedFields
     * @return array<int, string>
     */
    public function columnLabels(string $module, array $selectedFields): array
    {
        $definition = $this->moduleDefinition($module);
        $labels = [];

        foreach ($selectedFields as $field) {
            if (isset($definition['fields'][$field])) {
                $labels[] = (string) $definition['fields'][$field]['label'];
            }
        }

        return $labels;
    }

    /**
     * @param  iterable<int, mixed>  $rows
     * @param  array<int, string>  $selectedFields
     * @return array<int, array<int, string>>
     */
    public function mapRows(string $module, iterable $rows, array $selectedFields): array
    {
        $definition = $this->moduleDefinition($module);
        $fields = $definition['fields'];
        $mapped = [];

        foreach ($rows as $row) {
            $line = [];
            foreach ($selectedFields as $field) {
                $rawValue = data_get($row, $field);
                $line[] = $this->formatValue($rawValue, $fields[$field] ?? []);
            }
            $mapped[] = $line;
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $fieldConfig
     */
    private function castValue(?string $value, array $fieldConfig): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $type = (string) ($fieldConfig['type'] ?? 'string');

        if ($type === 'enum') {
            $options = $fieldConfig['options'] ?? [];
            if (is_array($options) && $options !== [] && ! in_array($value, $options, true)) {
                return null;
            }

            $valueMap = $fieldConfig['value_map'] ?? [];
            if (is_array($valueMap) && array_key_exists($value, $valueMap)) {
                return $valueMap[$value];
            }
        }

        return match ($type) {
            'number' => is_numeric($value) ? $value + 0 : null,
            'boolean' => in_array(strtolower($value), ['1', 'true', 'yes', 'active'], true) ? 1 : 0,
            'date' => Carbon::parse($value)->toDateString(),
            'datetime' => Carbon::parse($value)->toDateTimeString(),
            default => $value,
        };
    }

    /**
     * @param  array<string, mixed>  $fieldConfig
     */
    private function formatValue(mixed $value, array $fieldConfig): string
    {
        if ($value === null) {
            return '-';
        }

        $type = (string) ($fieldConfig['type'] ?? 'string');

        if ($type === 'enum') {
            $displayMap = $fieldConfig['display_map'] ?? [];
            if (is_array($displayMap) && array_key_exists((string) $value, $displayMap)) {
                return (string) $displayMap[(string) $value];
            }
        }

        return match ($type) {
            'boolean' => ((int) $value) === 1 ? 'Active' : 'Inactive',
            'date' => Carbon::parse((string) $value)->format('Y-m-d'),
            'datetime' => Carbon::parse((string) $value)->format('Y-m-d H:i'),
            default => (string) $value,
        };
    }

    /**
     * @return array<int, string>
     */
    private function allowedOperatorsForType(string $type): array
    {
        return match ($type) {
            'enum', 'boolean' => ['=', '!='],
            'number', 'date', 'datetime' => ['=', '!=', '>', '<', 'between'],
            default => ['=', '!=', 'like'],
        };
    }
}
