<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentCommentReply extends IncidentRelatedRecord
{
    protected $fillable = [
        'incident_comment_id',
        'user_id',
        'reply',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(IncidentComment::class, 'incident_comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
