<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates metadata table for filament_comments without touching original table.
     * This is 1:1 with filament_comments.id - allows adding tags, source, is_internal, etc
     * without modifying Parallax\FilamentComments schema.
     */
    public function up(): void
    {
        Schema::create('comment_metadata', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comment_id')->unique();
            $table->enum('source', ['web', 'gmail', 'calendar', 'api', 'system'])->default('web');
            $table->json('tags')->default(json_encode([]));
            $table->boolean('is_internal')->default(false);
            $table->string('email_message_id')->nullable()->unique();
            $table->enum('email_status', ['pending', 'sent', 'failed', 'replied'])->nullable();
            $table->timestamps();

            // Foreign key to filament_comments
            $table->foreign('comment_id')
                ->references('id')
                ->on('filament_comments')
                ->onDelete('cascade');

            // Indexes for common queries
            $table->index(['source', 'created_at']);
            $table->index(['is_internal', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_metadata');
    }
};
