<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CommentMetadata Model
 * 
 * 1:1 relationship with filament_comments
 * Stores extended metadata without touching Parallax table
 */
class CommentMetadata extends Model
{
    protected $table = 'comment_metadata';

    protected $fillable = [
        'comment_id',
        'source',
        'tags',
        'is_internal',
        'email_message_id',
        'email_status',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_internal' => 'boolean',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'comment_id');
    }
}
