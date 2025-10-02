# SpenderLock

SpenderLock è un sistema di gestione contratti e budget multi-tenant sviluppato con Laravel e Filament Admin Panel. Il sistema permette di gestire fornitori, contratti, budget annuali e contatti con completo isolamento dei dati per ogni organizzazione.

## 🏗️ Caratteristiche Principali

- **Sistema Multi-Tenant**: Isolamento completo dei dati per ogni organizzazione
- **Gestione Contratti**: Contratti con fornitori, categorie, date di scadenza, importi e allegati
- **Gestione Budget**: Budget annuali suddivisi per categoria contratto
- **Gestione Fornitori**: Anagrafica fornitori con contatti associati
- **Dashboard Avanzata**: Widget per visualizzazione contratti in scadenza, calendario eventi, totali spesa
- **Sistema di Autorizzazioni**: Gestione ruoli e permessi tramite Filament Shield
- **Interfaccia Moderna**: Admin panel completo con Filament v3
- **Deploy Docker**: Setup automatico con Docker Compose

## 🔧 Requisiti di Sistema

- PHP 8.4+
- Composer
- Node.js e npm
- SQLite (per sviluppo) o PostgreSQL (per produzione)
- Docker e Docker Compose (opzionale)

## 🚀 Installazione

### Opzione 1: Setup con Docker (Raccomandato)

**🚀 Quick Start (5 minuti):**
```bash
git clone <repository-url>
cd spenderlock
docker-compose up -d --build
```
Poi vai su http://localhost/admin e usa:
- **Email**: admin@spenderlock.com
- **Password**: spenderlock123

**🔧 Setup Personalizzato:**

1. **Clona il repository**
   ```bash
   git clone <repository-url>
   cd spenderlock
   ```

2. **Configura le credenziali admin (opzionale)**
   
   Modifica le variabili di ambiente nel `docker-compose.yml`:
   ```yaml
   environment:
     - DEFAULT_TENANT_NAME=La Tua Azienda
     - DEFAULT_TENANT_DOMAIN=localhost
     - DEFAULT_ADMIN_NAME=Administrator
     - DEFAULT_ADMIN_EMAIL=admin@tuaazienda.com
     - DEFAULT_ADMIN_PASSWORD=password-sicura
   ```

3. **Avvia il sistema**
   ```bash
   docker-compose up -d --build
   ```

4. **Accedi al pannello admin**
   - URL: http://localhost/admin
   - Email: admin@spenderlock.com (o quello configurato)
   - Password: spenderlock123 (o quella configurata)

### Opzione 2: Setup Locale

1. **Clona il repository**
   ```bash
   git clone <repository-url>
   cd spenderlock
   ```

2. **Installa le dipendenze**
   ```bash
   composer install
   npm install
   ```

3. **Configura l'ambiente**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Crea il database landlord**
   ```bash
   touch database/landlord.sqlite
   php artisan migrate --database=landlord --path=database/migrations/landlord
   ```

5. **Crea tenant e utente admin**
   ```bash
   php artisan tenants:create "La Tua Azienda" "localhost"
   php artisan tenants:create-user 1 "Administrator" "admin@localhost" "password123" --admin
   ```

6. **Compila gli asset e avvia il server**
   ```bash
   npm run dev
   php artisan serve --port=8000
   ```

7. **Accedi al pannello admin**
   - URL: http://localhost:8000/admin
   - Email: admin@localhost
   - Password: password123

## 🏢 Gestione Multi-Tenant

### Comandi Principali

```bash
# Crea un nuovo tenant
php artisan tenants:create "Nome Azienda" "dominio.local"

# Crea utente admin per un tenant
php artisan tenants:create-user 1 "Admin" "admin@domain.com" "password" --admin

# Crea utente normale per un tenant
php artisan tenants:create-user 1 "User" "user@domain.com" "password"

# Setup tenant di default (usato da Docker)
php artisan tenants:setup-default

# Esegui comandi per tutti i tenant
php artisan tenants:artisan "migrate"

# Esegui comando per tenant specifico
php artisan tenants:artisan "db:seed" --tenant=1
```

### Configurazione Domini Locali

Per testare più tenant in locale, aggiungi al tuo `/etc/hosts`:
```
127.0.0.1 azienda1.local
127.0.0.1 azienda2.local
127.0.0.1 test.local
```

## 🔧 Sviluppo

### Avvio Rapido
```bash
# Avvia tutti i servizi necessari
composer run dev
```
Questo comando avvia simultaneamente:
- Server Laravel (porta 8000)
- Queue worker per job in background
- Log monitoring con Pail
- Vite dev server per asset

### Comandi Individuali
```bash
# Server Laravel
php artisan serve

# Compilazione asset
npm run dev          # Sviluppo con hot reload
npm run build        # Build produzione

# Database
php artisan migrate --database=landlord --path=database/migrations/landlord
php artisan tenants:artisan "migrate"

# Test
composer run test
php artisan test

# Code formatting
php artisan pint

# Filament
php artisan filament:upgrade
php artisan tenants:artisan "shield:generate --all --panel=admin"
```

## 📊 Struttura del Progetto

### Architettura Multi-Tenant
- **Database Landlord**: Contiene la tabella `tenants` e configurazioni globali
- **Database Tenant**: Ogni tenant ha il proprio database SQLite isolato
- **Switching Automatico**: Il sistema cambia automaticamente database in base al dominio

### Modelli Principali
- **Tenant**: Modello principale per la gestione multi-tenant (usa Landlord DB)
- **Contract**: Contratto con fornitore, categoria, date e importi (usa Tenant DB)
- **Budget**: Budget annuale per categoria contratto (usa Tenant DB)
- **Supplier**: Anagrafica fornitore (usa Tenant DB)
- **Contact**: Contatto associato a fornitore (usa Tenant DB)
- **ContractCategory**: Categoria per classificazione contratti (usa Tenant DB)

### Filament Resources
- Admin panel completo per tutti i modelli
- Dashboard con widget personalizzati
- Gestione ruoli e permessi integrata
- Export/Import dati
- Calendario contratti interattivo

## 🚀 Deploy in Produzione

### Docker (Raccomandato)

1. **Modifica docker-compose.yml** con le tue configurazioni
2. **Configura le variabili di ambiente** per il tenant di default
3. **Deploy:**
   ```bash
   docker-compose up -d --build
   ```

### Server Tradizionale

1. **Setup server** con PHP 8.4+, PostgreSQL, Redis
2. **Deploy codice** e installa dipendenze
3. **Configura database** e esegui migrazioni
4. **Setup tenant di default**:
   ```bash
   php artisan tenants:setup-default \
     --tenant-name="Tua Azienda" \
     --tenant-domain="tuodominio.com" \
     --admin-email="admin@tuodominio.com" \
     --admin-password="password-sicura"
   ```

## 🔒 Sicurezza

- **Isolamento Database**: Ogni tenant ha database completamente separato
- **Domain-based Access**: Accesso basato su dominio verificato
- **Role-based Permissions**: Sistema di ruoli e permessi granulare
- **Session Validation**: Validazione sessioni per prevenire cross-tenant access

## 📝 Documentazione

- [Guida Multi-Tenancy](MULTITENANCY.md) - Documentazione completa del sistema multi-tenant
- [Testing Guide](TESTING_GUIDE.md) - Guida per testing e sviluppo

## 🤝 Contributi

1. Fork del repository
2. Crea feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit delle modifiche (`git commit -m 'Add some AmazingFeature'`)
4. Push al branch (`git push origin feature/AmazingFeature`)
5. Apri una Pull Request

## 📄 Licenza

Questo progetto è distribuito sotto licenza MIT. Vedi il file `LICENSE` per dettagli.

## 🆘 Supporto

Per supporto e bug report:
- Apri una [issue su GitHub](../../issues)
- Consulta la [documentazione](MULTITENANCY.md)
- Verifica la [guida di testing](TESTING_GUIDE.md)

## Tecnologie Utilizzate

- **Backend**: Laravel 12.x, Filament 3.x
- **Frontend**: Tailwind CSS 4.x, Vite 6.x
- **Database**: SQLite (dev), MySQL/PostgreSQL (prod)
- **Autorizzazioni**: Spatie Laravel Permission + Filament Shield
- **File Upload**: Gestito tramite Filament con storage locale

## Configurazione Produzione

1. **Variabili ambiente produzione**
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com
   ```

2. **Database produzione**
   - Configura MySQL/PostgreSQL nel file `.env`
   - Esegui migration: `php artisan migrate --force`

3. **Ottimizzazione**
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   npm run build
   ```

## Licenza

Questo progetto è distribuito sotto licenza MIT. Vedi il file `LICENSE` per maggiori dettagli.
