<?php

namespace App\Listeners;

use App\Models\CommentMetadata;
use Parallax\FilamentComments\Models\FilamentComment;

/**
 * Listener: Auto-populate comment_metadata when Parallax creates a comment
 * 
 * This intercepts the FilamentComment::created event from Parallax library
 * and automatically creates a metadata row with defaults.
 * 
 * Ensures ZERO breaking changes - Parallax continues to work unmodified,
 * while our extension layer captures the event and adds metadata.
 */
class PopulateCommentMetadataOnCreate
{
    /**
     * Handle the event.
     */
    public function handle(FilamentComment $comment): void
    {
        // Skip if metadata already exists (idempotent)
        if (CommentMetadata::where('comment_id', $comment->id)->exists()) {
            return;
        }

        // Determine source from request context
        $source = $this->detectSource();

        // Create metadata row with defaults
        CommentMetadata::create([
            'comment_id' => $comment->id,
            'source' => $source,
            'tags' => [],
            'is_internal' => false,
            'email_message_id' => null,
            'email_status' => null,
        ]);
    }

    /**
     * Detect source of comment creation
     * 
     * Returns: 'web' (Filament UI), 'gmail', 'api', or 'system'
     */
    private function detectSource(): string
    {
        // Check if running via console/queue (system jobs)
        if (app()->runningInConsole()) {
            return 'system';
        }

        // Check request header X-Comment-Source (for API calls)
        $header = request()->header('X-Comment-Source');
        if ($header && in_array($header, ['api', 'gmail', 'calendar'])) {
            return $header;
        }

        // Default to web (Filament UI)
        return 'web';
    }
}
