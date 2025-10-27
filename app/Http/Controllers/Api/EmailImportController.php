<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentMetadata;
use App\Models\CommentLink;
use App\Models\Supplier;
use App\Models\Contact;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmailImportController extends Controller
{
    public function importEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sender_email' => 'required|email',
            'subject' => 'required|string',
            'body' => 'required|string',
            'gmail_message_id' => 'required|string',
            'entity_type' => 'required|in:supplier,contact,contract',
            'entity_id' => 'required|integer',
            'is_internal' => 'boolean',
            'include_attachments' => 'boolean',
            'attachments' => 'array',
            'attachments.*.filename' => 'required_with:attachments|string',
            'attachments.*.mimeType' => 'required_with:attachments|string',
            'attachments.*.data' => 'required_with:attachments|string',
            'attachments.*.is_eml' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'code' => 'VALIDATION_ERROR',
                'details' => $validator->errors()
            ], 422);
        }

        $existingMetadata = CommentMetadata::where('gmail_message_id', $request->gmail_message_id)->first();
        if ($existingMetadata) {
            return response()->json([
                'success' => false,
                'error' => 'Email already imported',
                'code' => 'DUPLICATE_EMAIL',
                'comment_id' => $existingMetadata->comment_id
            ], 409);
        }

        $entity = $this->validateEntity($request->entity_type, $request->entity_id);
        if (!$entity) {
            return response()->json([
                'success' => false,
                'error' => "Entity not found: {$request->entity_type} #{$request->entity_id}",
                'code' => 'ENTITY_NOT_FOUND'
            ], 404);
        }

        $subjectType = match($request->entity_type) {
            'supplier' => 'App\Models\Supplier',
            'contact' => 'App\Models\Contact',
            'contract' => 'App\Models\Contract',
        };

        $comment = Comment::create([
            'subject_type' => $subjectType,
            'subject_id' => $request->entity_id,
            'user_id' => 1,
            'comment' => "<strong>Email da: {$request->sender_email}</strong><br>"
                       . "<strong>Oggetto:</strong> {$request->subject}<br><hr>"
                       . $request->body,
        ]);

        $metadata = CommentMetadata::create([
            'comment_id' => $comment->id,
            'source' => 'gmail',
            'is_internal' => $request->is_internal ?? false,
            'gmail_message_id' => $request->gmail_message_id,
            'email_from_address' => $request->sender_email,
            'email_to_addresses' => $request->to_addresses ?? [],
            'email_received_date' => $request->received_at ?? now(),
            'author' => $request->sender_name ?? $request->sender_email,
            'subject_line' => $request->subject,
        ]);


        $existingEntityLink = \App\Models\CommentLink::where([
            'source_comment_id' => $comment->id,
            'target_type' => $subjectType,
            'target_id' => $request->entity_id,
            'relation_type' => 'entity',
        ])->first();
        if (!$existingEntityLink) {
            CommentLink::create([
                'source_comment_id' => $comment->id,
                'target_type' => $subjectType,
                'target_id' => $request->entity_id,
                'relation_type' => 'entity',
                'link_type' => 'entity',
                'url' => null,
                'metadata' => null,
            ]);
        }

        $attachmentsCount = 0;
        $emlPath = null;

        if ($request->include_attachments && $request->has('attachments')) {
            foreach ($request->attachments as $attachment) {
                $isEml = $attachment['is_eml'] ?? false;
                if ($isEml) {
                    $emlPath = $this->saveEmlFile($comment->id, $attachment['data']);
                    $exists = \App\Models\CommentLink::where([
                        'source_comment_id' => $comment->id,
                        'relation_type' => 'email_archive',
                        'link_type' => 'email_archive',
                        'url' => $emlPath,
                    ])->first();
                    if (!$exists) {
                        CommentLink::create([
                            'source_comment_id' => $comment->id,
                            'relation_type' => 'email_archive',
                            'link_type' => 'email_archive',
                            'url' => $emlPath,
                            'metadata' => json_encode([
                                'format' => 'rfc822',
                                'imported_from' => 'gmail',
                                'filename' => $attachment['filename'],
                                'size' => strlen(base64_decode($attachment['data']))
                            ])
                        ]);
                    }
                } else {
                    $path = $this->saveAttachment($comment->id, $attachment);
                    $exists = \App\Models\CommentLink::where([
                        'source_comment_id' => $comment->id,
                        'relation_type' => 'attachment',
                        'link_type' => 'attachment',
                        'url' => $path,
                    ])->first();
                    if (!$exists) {
                        CommentLink::create([
                            'source_comment_id' => $comment->id,
                            'relation_type' => 'attachment',
                            'link_type' => 'attachment',
                            'url' => $path,
                            'metadata' => json_encode([
                                'filename' => $attachment['filename'],
                                'mime_type' => $attachment['mimeType'],
                                'size' => $attachment['size'] ?? null
                            ])
                        ]);
                    }
                }
                $attachmentsCount++;
            }
        }

        return response()->json([
            'success' => true,
            'comment_id' => $comment->id,
            'entity_type' => $request->entity_type,
            'entity_id' => $request->entity_id,
            'attachments_uploaded' => $attachmentsCount,
            'eml_saved' => $emlPath ? true : false,
            'message' => 'Email imported successfully'
        ], 200);
    }

    private function validateEntity(string $type, int $id)
    {
        return match($type) {
            'supplier' => Supplier::find($id),
            'contact' => Contact::find($id),
            'contract' => Contract::find($id),
            default => null,
        };
    }

    private function saveEmlFile(int $commentId, string $base64Data): string
    {
        $emlData = base64_decode($base64Data);
        $date = now();
        $path = "emails/{$date->format('Y')}/{$date->format('m')}/{$date->format('d')}";
        $filename = "{$commentId}.eml";

        Storage::disk('local')->put("{$path}/{$filename}", $emlData);

        return "storage/{$path}/{$filename}";
    }

    private function saveAttachment(int $commentId, array $attachment): string
    {
        $fileData = base64_decode($attachment['data']);
        $date = now();
        $path = "email_attachments/{$date->format('Y')}/{$date->format('m')}";

        $filename = Str::slug(pathinfo($attachment['filename'], PATHINFO_FILENAME));
        $extension = pathinfo($attachment['filename'], PATHINFO_EXTENSION);
        $fullFilename = "{$commentId}_{$filename}.{$extension}";

        Storage::disk('local')->put("{$path}/{$fullFilename}", $fileData);

        return "storage/{$path}/{$fullFilename}";
    }
}
