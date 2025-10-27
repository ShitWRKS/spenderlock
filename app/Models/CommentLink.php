<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for comment_links table
 *
 * Allows linking comments to multiple entities (contracts, suppliers, contacts, categories).
 * Enables N:N relationship modeling without touching filament_comments.
 */
class CommentLink extends Model
{
    public $timestamps = true;
    protected $table = 'comment_links';
    protected $fillable = [
        'source_comment_id',
        'target_type',
        'target_id',
        'relation_type',
        'link_type',
        'url',
        'metadata',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relationship: Belongs to source Comment
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'source_comment_id');
    }

    /**
     * Scope: Filter by target entity type
     */
    public function scopeForEntity($query, string $type, int $id)
    {
        return $query->where('target_type', $type)->where('target_id', $id);
    }

    /**
     * Scope: Filter by relation type
     */
    public function scopeByRelationType($query, string $relationType)
    {
        return $query->where('relation_type', $relationType);
    }
}
