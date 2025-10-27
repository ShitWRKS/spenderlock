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
            if (!Schema::hasColumn('comment_links', 'url')) {
                $table->string('url', 255)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comment_links', function (Blueprint $table) {
            if (Schema::hasColumn('comment_links', 'url')) {
                $table->dropColumn('url');
            }
        });
    }
};
