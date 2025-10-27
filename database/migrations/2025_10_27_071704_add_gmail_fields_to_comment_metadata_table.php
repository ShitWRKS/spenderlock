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
        Schema::table('comment_metadata', function (Blueprint $table) {
            $table->string('gmail_message_id')->nullable()->unique()->after('is_internal');
            $table->string('email_from_address')->nullable()->after('gmail_message_id');
            $table->json('email_to_addresses')->nullable()->after('email_from_address');
            $table->timestamp('email_received_date')->nullable()->after('email_to_addresses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comment_metadata', function (Blueprint $table) {
            $table->dropColumn(['gmail_message_id', 'email_from_address', 'email_to_addresses', 'email_received_date']);
        });
    }
};
