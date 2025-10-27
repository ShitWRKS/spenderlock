<?php

namespace App\Models\Traits;

use App\Models\Comment;

/**
 * Trait: HasCommunicationHistory
 * 
 * Add to Contract, Supplier, Contact to provide unified communication thread methods.
 * Uses Comment wrapper model to query all related comments across entity relationships.
 */
trait HasCommunicationHistory
{
    /**
     * Get the full communication thread for this entity
     * 
     * Includes:
     * - Direct comments on this entity
     * - Comments linked via comment_links
     * - Optionally: Comments on related entities (supplier, contacts, contracts)
     * 
     * @param bool $includeRelated - Include comments from related entities
     * @return \Illuminate\Support\Collection
     */
    public function getFullCommunicationThread(bool $includeRelated = true): \Illuminate\Support\Collection
    {
        $modelType = $this->getMorphClass();
        $modelId = $this->id;

        $comments = Comment::with('metadata', 'links')
            ->conversationThread($modelType, $modelId)
            ->orderBy('created_at', 'desc')
            ->get();

        // If includeRelated, also fetch comments from related entities
        if ($includeRelated) {
            $comments = $this->_enrichWithRelatedComments($comments, $modelType, $modelId);
        }

        return $comments;
    }

    /**
     * Get communication statistics for dashboard
     */
    public function getCommunicationStats(): array
    {
        $modelType = $this->getMorphClass();
        $modelId = $this->id;

        $allComments = Comment::conversationThread($modelType, $modelId)->get();
        $externalComments = Comment::conversationThread($modelType, $modelId)
            ->externalOnly()
            ->get();

        return [
            'total_comments' => $allComments->count(),
            'external_communications' => $externalComments->count(),
            'internal_notes' => $allComments->count() - $externalComments->count(),
            'last_activity' => $allComments->max('created_at'),
            'by_source' => $allComments->groupBy('source')->map->count(),
            'by_tag' => $allComments->pluck('tags')->flatten()->countBy(fn($t) => $t),
        ];
    }

    /**
     * Get last comment timestamp
     */
    public function getLastCommentAt(): ?\DateTime
    {
        $modelType = $this->getMorphClass();
        $modelId = $this->id;

        return Comment::conversationThread($modelType, $modelId)
            ->orderBy('created_at', 'desc')
            ->value('created_at');
    }

    /**
     * Check if entity has recent activity
     */
    public function hasRecentActivity(int $minutesAgo = 30): bool
    {
        $lastComment = $this->getLastCommentAt();
        if (!$lastComment) {
            return false;
        }

        return $lastComment->diffInMinutes(now()) <= $minutesAgo;
    }

    /**
     * Add metadata to comments from related entities (helper method)
     */
    private function _enrichWithRelatedComments(\Illuminate\Support\Collection $comments, string $modelType, int $modelId): \Illuminate\Support\Collection
    {
        // For now, just return direct comments
        // Future: Can expand to include supplier/contact/contract comments
        return $comments;
    }
}
