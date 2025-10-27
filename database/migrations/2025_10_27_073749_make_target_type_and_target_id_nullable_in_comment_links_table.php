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
            $table->string('target_type')->nullable()->change();
            $table->unsignedBigInteger('target_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comment_links', function (Blueprint $table) {
            $table->string('target_type')->change();
            $table->unsignedBigInteger('target_id')->change();
        });
    }
};
