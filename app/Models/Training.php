<?php

namespace App\Models;

use App\Traits\HasResourceScoping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Training extends Model
{
    use HasFactory, SoftDeletes, HasResourceScoping;

    protected $fillable = [
        'title',
        'description',
        'title_translations',
        'description_translations',
        'starts_at',
        'ends_at',
        'certificate_validity_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'title_translations' => 'array',
            'description_translations' => 'array',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot([
                'assigned_by',
                'assigned_at',
                'completed_at',
                'completion_status',
                'expiry_notified_at',
            ])
            ->withTimestamps();
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function titleForLocale(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $translations = $this->title_translations ?? [];

        return $translations[$locale] ?? $this->title;
    }

    public function descriptionForLocale(?string $locale = null): ?string
    {
        $locale = $locale ?: app()->getLocale();
        $translations = $this->description_translations ?? [];

        return $translations[$locale] ?? $this->description;
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $nestedQuery) use ($search) {
            $nestedQuery->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            default => $query,
        };
    }

    public function scopeDateBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when(
                filled($from),
                fn (Builder $builder) => $builder->whereDate('starts_at', '>=', $from)
            )
            ->when(
                filled($to),
                fn (Builder $builder) => $builder->whereDate('ends_at', '<=', $to)
            );
    }

    public function scopeSortByField(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $allowedSorts = [
            'title',
            'is_active',
            'starts_at',
            'ends_at',
            'created_at',
        ];

        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'created_at';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('id', 'desc');
    }

    /**
     * Scope query to trainings accessible by the given user.
     *
     * Access rules:
     * - Admin: all trainings
     * - Manager, Safety Officer, HOD HSSE, APSB PD: all trainings
     * - Others: trainings they are assigned to
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('Admin')) {
            return $query;
        }

        // Supervisory roles can view all trainings
        if ($user->hasAnyRole(['Manager', 'Safety Officer', 'HOD HSSE', 'APSB PD', 'MRTS', 'Auditor'])) {
            return $query;
        }

        // Workers see only trainings assigned to them
        return $query->whereHas('users', fn (Builder $q) => $q->where('users.id', $user->id));
    }
}
