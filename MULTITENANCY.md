# SpenderLock - Sistema Multi-Tenant con Spatie Laravel Multitenancy

Questo documento spiega come funziona il sistema multi-tenant implementato in SpenderLock utilizzando il pacchetto [Spatie Laravel Multitenancy](https://github.com/spatie/laravel-multitenancy).

## 🏗️ Architettura Multi-Tenant

### Approccio: Database Separati per Tenant

SpenderLock utilizza l'approccio **"database-per-tenant"** dove:

-   Ogni tenant ha il proprio database SQLite dedicato
-   Il database "landlord" contiene la tabella `tenants` e informazioni condivise
-   L'isolamento dei dati avviene a livello di database, non tramite campi `tenant_id`

### Struttura dei Database

```
database/
├── landlord.sqlite          # Database principale (tabella tenants)
├── tenant_azienda_demo.sqlite    # Database Tenant "Azienda Demo"
├── tenant_test_company.sqlite    # Database Tenant "Test Company"
└── database.sqlite          # Database default Laravel (non utilizzato)
```

## 🔧 Configurazione

### 1. Connessioni Database

In `config/database.php` sono configurate tre connessioni:

```php
'connections' => [
    // Connessione Landlord - Database principale
    'landlord' => [
        'driver' => 'sqlite',
        'database' => database_path('landlord.sqlite'),
        // ...
    ],

    // Connessione Tenant - Database dinamico
    'tenant' => [
        'driver' => 'sqlite',
        'database' => null, // Impostato dinamicamente
        // ...
    ],

    // Connessione default Laravel
    'sqlite' => [
        'driver' => 'sqlite',
        'database' => database_path('database.sqlite'),
        // ...
    ],
]
```

### 2. Configurazione Multitenancy

In `config/multitenancy.php`:

```php
return [
    // Classe per identificare il tenant dalla richiesta
    'tenant_finder' => \Spatie\Multitenancy\TenantFinder\DomainTenantFinder::class,

    // Task eseguiti quando si cambia tenant
    'switch_tenant_tasks' => [
        \Spatie\Multitenancy\Tasks\PrefixCacheTask::class,
        \Spatie\Multitenancy\Tasks\SwitchTenantDatabaseTask::class,
    ],

    // Modello Tenant personalizzato
    'tenant_model' => \App\Models\Tenant::class,

    // Connessioni database
    'tenant_database_connection_name' => 'tenant',
    'landlord_database_connection_name' => 'landlord',
];
```

### 3. Middleware

In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web([
        \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class,
        \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
    ]);
})
```

## 📊 Modelli

### Modello Tenant (Landlord)

```php
<?php
namespace App\Models;

use Spatie\Multitenancy\Models\Tenant as BaseTenant;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Tenant extends BaseTenant
{
    use UsesLandlordConnection; // Usa sempre il database landlord

    protected $fillable = ['name', 'domain', 'database'];
}
```

### Modelli Business (Tenant)

Tutti i modelli di business utilizzano `UsesTenantConnection`:

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class User extends Model
{
    use UsesTenantConnection; // Usa il database del tenant corrente
}

class Budget extends Model
{
    use UsesTenantConnection;
}

// Tutti gli altri modelli: Contract, Supplier, Contact, ContractCategory
```

## 🚀 Gestione Tenant

### Comando per Creare Tenant

```bash
# Crea un nuovo tenant
php artisan tenants:create "Nome Azienda" "dominio.local"

# Crea un tenant ed esegui i seeder
php artisan tenants:create "Nome Azienda" "dominio.local" --seed
```

Il comando `tenants:create`:

1. Crea il record nella tabella `tenants` (database landlord)
2. Crea il file database SQLite per il tenant
3. Esegue le migrazioni nel nuovo database
4. Opzionalmente esegue i seeder

### Comandi Tenant-Aware

```bash
# Esegui un comando artisan per tutti i tenant
php artisan tenants:artisan "migrate"

# Esegui per un tenant specifico
php artisan tenants:artisan "migrate" --tenant=1

# Migra tutti i database tenant
php artisan tenants:artisan "migrate --database=tenant"

# Seeding per tutti i tenant
php artisan tenants:artisan "db:seed --database=tenant"
```

## 🔄 Come Funziona il Tenant Switching

### 1. Identificazione Tenant

Quando arriva una richiesta HTTP:

1. `DomainTenantFinder` estrae il dominio dalla richiesta
2. Cerca nella tabella `tenants` un record con quel dominio
3. Se trovato, rende quel tenant "corrente"

### 2. Switch del Database

Quando un tenant diventa corrente:

1. `SwitchTenantDatabaseTask` modifica la configurazione della connessione `tenant`
2. Imposta il `database` path dal record tenant
3. Tutti i modelli con `UsesTenantConnection` ora puntano al database corretto

### 3. Isolamento Automatico

-   **Modelli Tenant**: Automaticamente operano nel database tenant corrente
-   **Modelli Landlord**: Sempre nel database landlord
-   **Cache**: Prefissata per tenant (tramite `PrefixCacheTask`)
-   **Session**: Validata per tenant (tramite middleware)

## 🧪 Testing del Sistema

### 1. Tenant Esistenti

I seguenti tenant sono configurati per il testing:

| Nome         | Dominio            | Database                   |
| ------------ | ------------------ | -------------------------- |
| Azienda Demo | demo.local         | tenant_azienda_demo.sqlite |
| Test Company | test-company.local | tenant_test_company.sqlite |

### 2. Test di Isolamento

Per testare l'isolamento dei dati:

1. Aggiungi nel tuo `/etc/hosts`:

    ```
    127.0.0.1 demo.local
    127.0.0.1 test-company.local
    ```

2. Visita: `http://demo.local:8001/admin`
3. Crea alcuni record (fornitori, contratti, etc.)
4. Visita: `http://test-company.local:8001/admin`
5. Verifica che i dati siano completamente separati

### 3. Verifica Database

```bash
# Controlla i tenant nel database landlord
sqlite3 database/landlord.sqlite "SELECT * FROM tenants;"

# Controlla i dati in un database tenant specifico
sqlite3 database/tenant_azienda_demo.sqlite "SELECT * FROM users;"
```

## 🔧 Filament Integration

Filament funziona automaticamente con il sistema multi-tenant:

-   **Risorse**: Mostrano solo dati del tenant corrente
-   **Creazione Record**: Automaticamente nel database tenant corrente
-   **Autenticazione**: Utenti isolati per tenant
-   **Permissions**: Gestiti separatamente per ogni tenant

## 🛡️ Sicurezza

### Protezioni Implementate

1. **Domain-based Isolation**: Ogni tenant accede solo tramite il proprio dominio
2. **Database Separation**: Isolamento fisico completo dei dati
3. **Session Validation**: Middleware previene cross-tenant abuse
4. **Connection Switching**: Automatico e trasparente

### Best Practices

1. **Dominio Verification**: Sempre verificare che il dominio sia valido
2. **Database Backups**: Backup separati per ogni database tenant
3. **Monitoring**: Log separati per tenant per troubleshooting
4. **Access Control**: Implementare autenticazione robusta per ogni tenant

## 📝 Esempi di Codice

### Controller Tenant-Aware

```php
<?php
namespace App\Http\Controllers;

use App\Models\Supplier;
use Spatie\Multitenancy\Models\Tenant;

class SupplierController extends Controller
{
    public function index()
    {
        // Automaticamente filtra per il tenant corrente
        $suppliers = Supplier::all();

        // Ottieni il tenant corrente
        $currentTenant = Tenant::current();

        return view('suppliers.index', compact('suppliers', 'currentTenant'));
    }
}
```

### Policy Tenant-Aware

```php
<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Supplier;
use Spatie\Multitenancy\Models\Tenant;

class SupplierPolicy
{
    public function view(User $user, Supplier $supplier): bool
    {
        // Nota: Non serve controllare tenant_id perché l'isolamento
        // avviene a livello di database. Se l'utente può vedere il record,
        // significa che è nel database corretto.
        return true;
    }
}
```

### Seeder Tenant-Aware

```php
<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Multitenancy\Models\Tenant;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Controlla se siamo in contesto tenant o landlord
        if (Tenant::checkCurrent()) {
            // Seeding per tenant
            $this->call([
                UserSeeder::class,
                SupplierSeeder::class,
            ]);
        } else {
            // Seeding per landlord
            $this->call([
                TenantSeeder::class,
            ]);
        }
    }
}
```

## 🎯 Considerazioni di Produzione

### Performance

-   **Connection Pooling**: Configurare appropriatamente per multiple connessioni
-   **Database Optimization**: Indici appropriati per ogni database tenant
-   **Caching**: Cache separata per tenant (già implementato)

### Scalabilità

-   **Database Sharding**: Considerare sharding per molti tenant
-   **Load Balancing**: Distribuire tenant su server diversi
-   **Storage**: Backup e archiviazione per database multipli

### Monitoraggio

-   **Logging**: Log separati per tenant
-   **Metrics**: Metriche per tenant per monitoring
-   **Health Checks**: Verifiche di salute per ogni database tenant

---

## 🎉 Conclusione

Il sistema multi-tenant di SpenderLock è ora completamente configurato seguendo le best practice di Spatie Laravel Multitenancy. Il sistema fornisce:

✅ **Isolamento Completo**: Database separati per ogni tenant  
✅ **Switching Automatico**: Cambio trasparente di database  
✅ **Sicurezza**: Protezione contro cross-tenant abuse  
✅ **Scalabilità**: Architettura scalabile per molti tenant  
✅ **Facilità d'Uso**: API semplice per sviluppatori  
✅ **Filament Integration**: Pannello admin multi-tenant ready

Il sistema è pronto per l'uso in produzione! 🚀
