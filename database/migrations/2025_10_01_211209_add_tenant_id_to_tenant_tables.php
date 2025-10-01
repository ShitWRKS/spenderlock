<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aggiunge il campo tenant_id a tutte le tabelle principali del business logic.
     * 
     * Questo consente il filtering automatico dei dati per tenant quando
     * si utilizzano i trait di Spatie Multitenancy sui modelli.
     */
    public function up(): void
    {
        // Lista delle tabelle che devono essere tenant-aware
        $tables = [
            'suppliers',
            'contacts', 
            'contract_categories',
            'contracts',
            'budgets',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id');
                
                // Nota: Non aggiungiamo foreign key perché in ambiente multi-database
                // il tenant_id fa riferimento al tenant nel database landlord
            });
        }
    }

    /**
     * Rimuove il campo tenant_id da tutte le tabelle principali.
     */
    public function down(): void
    {
        $tables = [
            'suppliers',
            'contacts',
            'contract_categories', 
            'contracts',
            'budgets',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
