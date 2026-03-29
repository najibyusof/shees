<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentComment extends IncidentRelatedRecord
{
    protected $fillable = [
        'incident_id',
        'user_id',
        'comment',
        'comment_type',
        'tagged_users',
        'is_critical',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tagged_users' => 'array',
            'is_critical' => 'boolean',
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
        ]);
    }

    public static function criticalTypes(): array
    {
        return (array) config('incident_workflow.unresolved_critical_comments.critical_comment_types', ['action_required']);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(IncidentCommentReply::class, 'incident_comment_id')->oldest();
    }
}

