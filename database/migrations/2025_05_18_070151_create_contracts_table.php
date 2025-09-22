<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->foreignId('contract_category_id')->constrained()->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('renewal_mode', ['manual', 'automatic'])->default('manual');
            $table->decimal('amount_total', 12, 2);
            $table->decimal('amount_recurring', 12, 2)->nullable();
            $table->integer('frequency_months')->nullable();
            $table->string('payment_type')->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments_contract')->nullable();
            $table->json('attachments_orders')->nullable();
            $table->json('attachments_documents')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
