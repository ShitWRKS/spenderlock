<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    /**
     * GET /api/comments/{type}/{id}
     * Lista commenti per una risorsa (contract, supplier, contact)
     */
    public function index(Request $request, string $type, int $id)
    {
        $subjectTypeMap = [
            'contract' => 'App\\Models\\Contract',
            'supplier' => 'App\\Models\\Supplier',
            'contact' => 'App\\Models\\Contact',
        ];

        if (!isset($subjectTypeMap[$type])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipo risorsa non valido'
            ], 400);
        }

        $comments = DB::table('filament_comments')
            ->where('subject_type', $subjectTypeMap[$type])
            ->where('subject_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($comment) {
                $user = User::find($comment->user_id);
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'user_name' => $user ? $user->name : 'Apps Script Bot',
                    'user_email' => $user ? $user->email : null,
                    'created_at' => $comment->created_at,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $comments,
            'count' => $comments->count()
        ]);
    }

    /**
     * POST /api/comments/{type}/{id}
     * Crea nuovo commento
     */
    public function store(Request $request, string $type, int $id)
    {
        $subjectTypeMap = [
            'contract' => 'App\\Models\\Contract',
            'supplier' => 'App\\Models\\Supplier',
            'contact' => 'App\\Models\\Contact',
        ];

        if (!isset($subjectTypeMap[$type])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipo risorsa non valido'
            ], 400);
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:5000',
            'user_name' => 'nullable|string|max:255',
        ]);

        // OAuth client_credentials non ha user, usa sistema o nome fornito
        $user = $request->user();

        // Se non c'è user autenticato, cerca il primo utente del tenant
        if (!$user) {
            $user = User::first();
        }

        $userId = $user ? $user->id : null;
        $userName = $validated['user_name'] ?? ($user ? $user->name : 'Apps Script Bot');

        $commentId = DB::table('filament_comments')->insertGetId([
            'user_id' => $userId,
            'subject_type' => $subjectTypeMap[$type],
            'subject_id' => $id,
            'comment' => $validated['comment'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $comment = DB::table('filament_comments')->find($commentId);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'user_name' => $userName,
                'user_email' => $user ? $user->email : null,
                'created_at' => $comment->created_at,
            ]
        ], 201);
    }
}
