<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

/**
 * Comment Wrapper Model
 * 
 * This is a READ-ONLY wrapper around Parallax\FilamentComments.
 * It provides extended functionality via satellite tables without modifying
 * the original filament_comments table.
 * 
 * Architecture:
 * - Reads from filament_comments (Parallax)
 * - Extends with comment_metadata (1:1)
 * - Links entities via comment_links (N:N)
 */
class Comment extends Model
{
    use SoftDeletes;
    
    protected $table = 'filament_comments';
    
    // Read-only wrapper - no mass assignment
    protected $guarded = ['id'];
    
    /**
     * Polymorphic relationship to the commented entity (Supplier, Contact, etc.)
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject');
    }
    
    /**
     * 1:1 Relationship with CommentMetadata
     */
    public function metadata(): HasOne
    {
        return $this->hasOne(CommentMetadata::class, 'comment_id');
    }

    /**
     * 1:N Relationship with CommentLinks (this comment links to other entities)
     */
    public function links(): HasMany
    {
        return $this->hasMany(CommentLink::class, 'source_comment_id');
    }

    /**
     * Accessor: Get tags from metadata
     */
    public function getTagsAttribute(): array
    {
        return $this->metadata?->tags ?? [];
    }

    /**
     * Accessor: Get source from metadata
     */
    public function getSourceAttribute(): string
    {
        return $this->metadata?->source ?? 'web';
    }

    /**
     * Accessor: Get is_internal from metadata
     */
    public function getIsInternalAttribute(): bool
    {
        return $this->metadata?->is_internal ?? false;
    }

    /**
     * Accessor: Get email_status from metadata
     */
    public function getEmailStatusAttribute(): ?string
    {
        return $this->metadata?->email_status;
    }

    /**
     * Scope: Filter by source
     */
    public function scopeBySource($query, string $source)
    {
        return $query->whereHas('metadata', function ($q) use ($source) {
            $q->where('source', $source);
        });
    }

    /**
     * Scope: Filter by tag
     */
    public function scopeByTag($query, string $tag)
    {
        return $query->whereHas('metadata', function ($q) use ($tag) {
            $q->whereJsonContains('tags', $tag);
        });
    }

    /**
     * Scope: Get only external comments
     */
    public function scopeExternalOnly($query)
    {
        return $query->whereHas('metadata', function ($q) {
            $q->where('is_internal', false);
        });
    }

    /**
     * Scope: Get full conversation thread for an entity
     * Includes:
     * - Direct comments on entity (via subject_type/subject_id)
     * - Linked comments (via comment_links)
     */
    public function scopeConversationThread($query, string $entityType, int $entityId)
    {
        return $query->where(function ($q) use ($entityType, $entityId) {
            // Direct comments
            $q->where('subject_type', $entityType)
              ->where('subject_id', $entityId);
        })->orWhereHas('links', function ($q) use ($entityType, $entityId) {
            // Linked comments
            $q->where('target_type', $entityType)
              ->where('target_id', $entityId);
        })->orderBy('created_at', 'desc');
    }

    /**
     * Get comment count by source for an entity
     */
    public static function getSourceStatsForEntity(string $entityType, int $entityId): array
    {
        $comments = self::conversationThread($entityType, $entityId)
            ->with('metadata')
            ->get();

        $stats = [
            'total' => $comments->count(),
            'by_source' => [],
            'external' => 0,
            'internal' => 0,
        ];

        foreach ($comments as $comment) {
            $source = $comment->source;
            $stats['by_source'][$source] = ($stats['by_source'][$source] ?? 0) + 1;
            
            if ($comment->is_internal) {
                $stats['internal']++;
            } else {
                $stats['external']++;
            }
        }

        return $stats;
    }
}
