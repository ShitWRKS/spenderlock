<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comment_links', function (Blueprint $table) {
            if (!Schema::hasColumn('comment_links', 'link_type')) {
                $table->string('link_type', 32)->nullable();
            }
            if (!Schema::hasColumn('comment_links', 'relation_type')) {
                $table->string('relation_type', 32)->nullable();
            }
            if (!Schema::hasColumn('comment_links', 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comment_links', function (Blueprint $table) {
            if (Schema::hasColumn('comment_links', 'link_type')) {
                $table->dropColumn('link_type');
            }
            if (Schema::hasColumn('comment_links', 'relation_type')) {
                $table->dropColumn('relation_type');
            }
            if (Schema::hasColumn('comment_links', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};
