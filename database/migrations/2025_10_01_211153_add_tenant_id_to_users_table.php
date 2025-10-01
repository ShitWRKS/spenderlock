<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aggiunge il campo tenant_id alla tabella users per associare
     * ogni utente al proprio tenant.
     * 
     * Questo campo sarà utilizzato per filtrare automaticamente gli utenti
     * in base al tenant corrente quando si è nel contesto tenant.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            
            // Aggiungiamo un indice per ottimizzare le query di filtering
            $table->index('tenant_id');
            
            // Nota: Non aggiungiamo foreign key perché i database sono separati
            // Il tenant_id fa riferimento all'id nella tabella tenants del database landlord
        });
    }

    /**
     * Rimuove il campo tenant_id dalla tabella users.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
