<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Contract;
use App\Models\Supplier;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * API Controller: Communication Thread
 * 
 * Retrieves complete communication history for any entity (contract, supplier, contact).
 * Uses the Comment wrapper model to assemble threads from multiple sources.
 * 
 * Endpoint: GET /api/communication-thread?type={type}&id={id}&depth={full|summary}
 */
class CommunicationThreadController extends Controller
{
    /**
     * Get communication thread for an entity
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:contract,supplier,contact',
            'id' => 'required|integer|min:1',
            'depth' => 'nullable|in:full,summary',
        ]);

        $type = $validated['type'];
        $id = $validated['id'];
        $depth = $validated['depth'] ?? 'summary';

        // Verify entity exists
        $entity = $this->getEntity($type, $id);
        if (!$entity) {
            return response()->json([
                'status' => 'error',
                'message' => ucfirst($type) . ' not found',
            ], 404);
        }

        // Try cache first
        $cacheKey = "communication-thread:$type:$id:$depth";
        if ($cached = Cache::get($cacheKey)) {
            return response()->json($cached);
        }

        // Fetch thread
        $comments = Comment::with('metadata', 'links')
            ->conversationThread($type, $id)
            ->get();

        // Assemble response
        $response = [
            'status' => 'success',
            'entity' => [
                'type' => $type,
                'id' => $id,
                'name' => $entity->name ?? $entity->title ?? 'N/A',
            ],
            'thread' => $this->formatComments($comments, $depth),
            'summary' => [
                'total_comments' => $comments->count(),
                'by_source' => $comments->groupBy('source')->map->count(),
                'last_activity' => $comments->max('created_at')?->toIso8601String(),
            ],
        ];

        // Cache for 5 minutes
        Cache::put($cacheKey, $response, 5 * 60);

        return response()->json($response);
    }

    /**
     * Add comment with linking
     * 
     * POST /api/communication-thread/{type}/{id}/comment
     */
    public function addComment(Request $request, string $type, int $id)
    {
        $validated = $request->validate([
            'comment' => 'required|string|min:1',
            'tags' => 'nullable|array',
            'is_internal' => 'nullable|boolean',
        ]);

        // Verify entity exists
        $entity = $this->getEntity($type, $id);
        if (!$entity) {
            return response()->json([
                'status' => 'error',
                'message' => ucfirst($type) . ' not found',
            ], 404);
        }

        // Determine subject type for Parallax
        $typeMap = [
            'contract' => Contract::class,
            'supplier' => Supplier::class,
            'contact' => Contact::class,
        ];

        try {
            // Create comment via Parallax (will trigger our event listener)
            $filamentComment = \Parallax\FilamentComments\Models\FilamentComment::create([
                'user_id' => auth()->id() ?? 1, // Fallback for unauthenticated
                'subject_type' => $typeMap[$type],
                'subject_id' => $id,
                'comment' => $validated['comment'],
            ]);

            // Event listener auto-creates metadata
            // Now update with additional data
            $comment = Comment::find($filamentComment->id);
            if ($comment->metadata) {
                $comment->metadata->update([
                    'tags' => $validated['tags'] ?? [],
                    'is_internal' => $validated['is_internal'] ?? false,
                    'source' => $request->header('X-Comment-Source', 'web'),
                ]);
            }

            // Invalidate cache
            Cache::forget("communication-thread:$type:$id:full");
            Cache::forget("communication-thread:$type:$id:summary");

            return response()->json([
                'status' => 'success',
                'message' => 'Comment created',
                'data' => $this->formatComment($comment),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create comment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get entity object
     */
    private function getEntity(string $type, int $id)
    {
        return match ($type) {
            'contract' => Contract::find($id),
            'supplier' => Supplier::find($id),
            'contact' => Contact::find($id),
            default => null,
        };
    }

    /**
     * Format comments collection
     */
    private function formatComments($comments, string $depth = 'summary')
    {
        return $comments->map(fn($comment) => $this->formatComment($comment, $depth))->values();
    }

    /**
     * Format single comment
     */
    private function formatComment($comment, string $depth = 'summary'): array
    {
        $formatted = [
            'id' => $comment->id,
            'comment' => $comment->comment,
            'created_at' => $comment->created_at->toIso8601String(),
            'user_id' => $comment->user_id,
            'source' => $comment->source,
            'tags' => $comment->tags,
            'is_internal' => $comment->is_internal,
        ];

        if ($depth === 'full' && $comment->links) {
            $formatted['linked_to'] = $comment->links->map(function ($link) {
                return [
                    'type' => $link->target_type,
                    'id' => $link->target_id,
                    'relation_type' => $link->relation_type,
                ];
            })->values();
        }

        return $formatted;
    }
}
