# SpenderLock Production

Sistema di gestione contratti e fornitori con integrazione Google Workspace.

## 📋 Indice

- [Descrizione](#descrizione)
- [Architettura](#architettura)
- [Requisiti](#requisiti)
- [Installazione](#installazione)
- [Configurazione](#configurazione)
- [API Multi-tenant](#api-multi-tenant)
- [Google Workspace Integration](#google-workspace-integration)
- [Testing](#testing)
- [Deployment](#deployment)

## 🎯 Descrizione

SpenderLock è un sistema completo per la gestione di:
- Contratti e scadenze
- Fornitori e contatti
- Timeline e comunicazioni
- Documenti e allegati
- Commenti e note
- Integrazione email Gmail

Il sistema è progettato per architettura **multi-tenant** con database SQLite separati per ogni tenant.

## 🏗️ Architettura

### Stack Tecnologico

- **Backend**: Laravel 11
- **Admin Panel**: Filament 3
- **Database**: SQLite (multi-tenant)
- **Authentication**: Laravel Passport (OAuth2)
- **Multi-tenancy**: Spatie Laravel Multitenancy
- **Frontend**: Livewire + Alpine.js
- **API**: RESTful con OAuth2 client_credentials

### Struttura Multi-tenant

```
database/
├── landlord.sqlite          # Database principale (tenants, oauth_clients)
├── tenant_*.sqlite          # Database per ogni tenant (dati isolati)
└── database.sqlite          # Database di default
```

Ogni tenant ha database isolato con tabelle:
- contracts, suppliers, contacts
- filament_comments, comment_metadata, comment_links
- reminders, contract_categories
- users (specifici del tenant)

## 📦 Requisiti

- PHP 8.2+
- Composer 2.x
- SQLite 3
- Node.js 18+ (per asset build)
- Git

## 🚀 Installazione

### 1. Clone e dipendenze

```bash
cd spenderlock-prod
composer install
npm install && npm run build
```

### 2. Configurazione ambiente

```bash
cp .env.example .env
php artisan key:generate
```

Configura `.env`:

```env
APP_NAME="SpenderLock"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Europe/Rome
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=/path/to/spenderlock-prod/database/database.sqlite
```

### 3. Database setup

```bash
# Crea file database
touch database/database.sqlite
touch database/landlord.sqlite

# Esegui migrations
php artisan migrate

# Seed (opzionale)
php artisan db:seed
```

### 4. Laravel Passport setup

```bash
# Installa Passport
php artisan passport:install

# Output conterrà:
# Client ID: 019a252b-844c-7192-a76b-94858e700651
# Client Secret: rtKLxJhQd6rppdlwqIjC3WKewQgtsVPY6QQ9bpdZ
```

Salva `Client ID` e `Client Secret` per configurare Apps Script.

### 5. Crea tenant

```bash
php artisan tinker
```

```php
use App\Models\Tenant;

$tenant = Tenant::create([
    'name' => 'SpenderLock Company',
    'domain' => 'localhost',
    'database' => database_path('tenant_spenderlock_company.sqlite')
]);

// Crea database tenant
touch database/tenant_spenderlock_company.sqlite
```

### 6. Avvia server

```bash
php artisan serve
```

Accedi a: http://localhost:8000

## ⚙️ Configurazione

### Multi-tenancy

Il sistema identifica il tenant da:
1. **Richieste Web**: Dominio (es. `localhost`, `app.example.com`)
2. **Richieste API**: Header `X-Tenant-Domain` o `X-Tenant-ID`

Configurazione in `config/multitenancy.php`:

```php
'tenant_finder' => \App\TenantFinder\ApiTenantFinder::class,
'tenant_database_connection_name' => 'tenant',
'landlord_database_connection_name' => 'landlord',
```

### OAuth2 API

Le API usano **client_credentials grant** (machine-to-machine):

```bash
# Get access token
curl -X POST http://localhost:8000/oauth/token \
  -H "Content-Type: application/json" \
  -d '{
    "grant_type": "client_credentials",
    "client_id": "YOUR_CLIENT_ID",
    "client_secret": "YOUR_CLIENT_SECRET"
  }'
```

Response:
```json
{
  "token_type": "Bearer",
  "expires_in": 31536000,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### Timezone

Configurato in `.env`:

```env
APP_TIMEZONE=Europe/Rome
```

Orari salvati in UTC, mostrati in timezone configurato.

## 🔌 API Multi-tenant

### Autenticazione

Tutte le API richiedono:
1. **Bearer Token** (OAuth2)
2. **Header X-Tenant-Domain** (per identificare tenant)

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "X-Tenant-Domain: localhost" \
     http://localhost:8000/api/contracts/upcoming
```

### Endpoint Disponibili

#### Contratti
- `GET /api/contracts/upcoming?days=30` - Contratti in scadenza
- `GET /api/contracts/search?q=term` - Cerca contratti
- `GET /api/contracts/{id}` - Dettaglio contratto
- `POST /api/contracts/{id}/reminders` - Crea reminder

#### Fornitori
- `GET /api/suppliers` - Lista fornitori
- `GET /api/suppliers?q=term` - Cerca fornitori
- `GET /api/suppliers/{id}` - Dettaglio fornitore
- `POST /api/suppliers` - Crea fornitore
- `GET /api/suppliers/{id}/contacts` - Contatti fornitore

#### Contatti
- `GET /api/contacts/{id}` - Dettaglio contatto
- `POST /api/contacts` - Crea contatto
- `PUT /api/contacts/{id}` - Aggiorna contatto
- `GET /api/contacts/search/email?email=` - Cerca per email

#### Commenti
- `GET /api/comments/{type}/{id}` - Lista commenti (type: contract|supplier|contact)
- `POST /api/comments/{type}/{id}` - Crea commento

#### Thread Comunicazioni
- `GET /api/communication-threads/{id}` - Dettaglio thread

#### Import Email
- `POST /api/comments/import-email` - Importa email da Gmail

### Script di Test

Usa `test-api.sh` per testare tutti gli endpoint:

```bash
chmod +x test-api.sh
./test-api.sh
```

Configura prima:
```bash
CLIENT_ID="your-client-id"
CLIENT_SECRET="your-client-secret"
TENANT_DOMAIN="localhost"
```

## 📧 Google Workspace Integration

### Apps Script Addon

File in `appscript/`:
- `appsscript.json` - Manifest addon
- `Code.gs` - Entry points
- `Config.gs` - Configurazione OAuth2 e API
- `Auth.gs` - Gestione token
- `API.gs` - Chiamate API
- `UI-v1.3.gs` - Interfaccia CardService

### Configurazione Apps Script

1. **Crea progetto**: https://script.google.com
2. **Copia file** da `appscript/` al progetto
3. **Configura `Config.gs`**:

```javascript
const CONFIG = {
  API_BASE_URL: 'https://your-domain.com/api',
  OAUTH_TOKEN_URL: 'https://your-domain.com/oauth/token',
  OAUTH_CLIENT_ID: 'YOUR_CLIENT_ID',
  OAUTH_CLIENT_SECRET: 'YOUR_CLIENT_SECRET',
  TENANT_DOMAIN: 'localhost',  // Match con tenant Laravel
  // ...
};
```

4. **Deploy addon**:
   - Deploy → Test deployments → Install
   - Testa in Gmail

### Funzionalità Gmail Addon

- 📋 Visualizza contratti in scadenza
- 🔍 Cerca contratti e fornitori
- 📨 Importa email come commenti
- 📆 Collega email a contratti/fornitori
- 📝 Aggiungi note rapide

## 🧪 Testing

### Test API

```bash
# Test singolo endpoint
curl -X GET http://localhost:8000/api/contracts/upcoming \
  -H "Authorization: Bearer TOKEN" \
  -H "X-Tenant-Domain: localhost"

# Test completo
./test-api.sh
```

### Test Database

```bash
php artisan tinker
```

```php
// Verifica tenant
use App\Models\Tenant;
Tenant::all();

// Verifica connessione tenant
use App\Models\Contract;
Contract::count();
```

## 🚀 Deployment

### Opzione 1: Server Locale con Cloudflare Tunnel

```bash
# Installa cloudflared
brew install cloudflare/cloudflare/cloudflared

# Crea tunnel
cloudflared tunnel --url http://localhost:8000
```

Output: `https://random-name.trycloudflare.com`

Aggiorna Apps Script `Config.gs` con l'URL.

### Opzione 2: Hosting Condiviso

1. Upload files via FTP/SFTP
2. Configura `.env` per produzione
3. `composer install --no-dev --optimize-autoloader`
4. `php artisan config:cache`
5. `php artisan route:cache`
6. `php artisan view:cache`

### Opzione 3: VPS/Cloud

Vedi `docs/DEPLOY-OPTIONS.md` per guide dettagliate.

## 📁 Struttura Progetto

```
spenderlock-prod/
├── app/
│   ├── Filament/           # Filament resources
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/        # API Controllers
│   │   └── Middleware/
│   ├── Models/             # Eloquent models
│   │   ├── Tenant.php
│   │   ├── Contract.php
│   │   ├── Supplier.php
│   │   └── Contact.php
│   └── TenantFinder/       # Custom tenant finder
├── appscript/              # Google Apps Script files
│   ├── appsscript.json
│   ├── Code.gs
│   ├── Config.gs
│   ├── Auth.gs
│   ├── API.gs
│   └── UI-v1.3.gs
├── config/
│   ├── multitenancy.php    # Multi-tenancy config
│   └── database.php        # Database connections
├── database/
│   ├── migrations/
│   ├── landlord.sqlite     # Main database
│   └── tenant_*.sqlite     # Tenant databases
├── routes/
│   ├── web.php
│   └── api.php             # API routes
├── test-api.sh             # API test script
└── README.md
```

## 🔒 Sicurezza

### Credenziali da NON committare

- ❌ `.env` file
- ❌ `database/*.sqlite`
- ❌ `storage/oauth-*.key`
- ❌ OAuth client secrets

### Best Practices

- ✅ Usa `.gitignore` per files sensibili
- ✅ Ruota secrets regolarmente
- ✅ Usa HTTPS in produzione
- ✅ Abilita rate limiting API
- ✅ Monitora accessi OAuth

## 📝 Changelog

### v1.0.0 (27 Ottobre 2024)

**Features**:
- ✅ Multi-tenancy con Spatie Laravel Multitenancy
- ✅ OAuth2 API con Laravel Passport
- ✅ Custom TenantFinder per API (header-based)
- ✅ 14+ endpoint API REST
- ✅ Google Apps Script addon per Gmail
- ✅ Sistema commenti con metadata
- ✅ Import email da Gmail
- ✅ Timeline attività
- ✅ Gestione allegati

**Fixes**:
- ✅ Timezone corretto (Europe/Rome)
- ✅ Date cast in Contract model
- ✅ Validazione supplier_id con multitenancy
- ✅ User lookup per commenti da API

## 🤝 Contributi

Progetto interno per gestione contratti e fornitori.

## 📄 Licenza

Internal Use Only © 2024

---

**Ultima modifica**: 27 Ottobre 2024
