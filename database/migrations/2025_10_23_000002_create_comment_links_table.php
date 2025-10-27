<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates linking table for N:N relationships between comments and entities.
     * Allows a comment to be linked to multiple contracts, suppliers, contacts, etc.
     */
    public function up(): void
    {
        Schema::create('comment_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_comment_id');
            $table->string('target_type'); // 'contract', 'supplier', 'contact', 'category'
            $table->unsignedBigInteger('target_id');
            $table->enum('relation_type', ['related_to', 'reply_to', 'forward_to'])->default('related_to');
            $table->timestamps();

            // Foreign key to filament_comments
            $table->foreign('source_comment_id')
                ->references('id')
                ->on('filament_comments')
                ->onDelete('cascade');

            // Prevent duplicate links
            $table->unique(['source_comment_id', 'target_type', 'target_id']);

            // Indexes for common queries
            $table->index(['target_type', 'target_id']);
            $table->index(['source_comment_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_links');
    }
};
