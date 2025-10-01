<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

/**
 * Modello Tenant che gestisce i tenant dell'applicazione multi-tenant.
 * 
 * Ogni tenant rappresenta un'organizzazione separata con il proprio database.
 * Il pacchetto Spatie gestisce automaticamente il cambio di database in base
 * al dominio della richiesta.
 * 
 * Il trait UsesLandlordConnection fa sì che questo modello utilizzi sempre
 * il database landlord, indipendentemente dal tenant corrente.
 * 
 * @property int $id
 * @property string $name Nome del tenant/organizzazione
 * @property string $domain Dominio univoco del tenant (es: azienda1.app.com)
 * @property string $database Nome del database dedicato al tenant
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Tenant extends BaseTenant
{
    use HasFactory, UsesLandlordConnection;

    /**
     * I campi che possono essere assegnati in massa.
     * 
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'domain', 
        'database',
    ];

    /**
     * I campi che devono essere nascosti dalla serializzazione.
     * 
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * Gli attributi che devono essere convertiti.
     * 
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Ottieni tutti gli utenti associati a questo tenant.
     * 
     * Nota: questa relazione funziona solo quando si è nel contesto
     * del database del tenant specifico.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    /**
     * Genera automaticamente il nome del database basato sul nome del tenant.
     * Rimuove caratteri speciali e converte in minuscolo.
     * 
     * @param string $name Nome del tenant
     * @return string Nome del database sanitizzato
     */
    public static function generateDatabaseName(string $name): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($name));
        return 'tenant_' . $sanitized;
    }

    /**
     * Genera automaticamente il dominio basato sul nome del tenant.
     * 
     * @param string $name Nome del tenant
     * @return string Dominio sanitizzato
     */
    public static function generateDomain(string $name): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($name));
        return $sanitized . '.local'; // Cambia .local con il tuo dominio di produzione
    }
}