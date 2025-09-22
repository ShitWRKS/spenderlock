# SpenderLock

SpenderLock è un sistema di gestione contratti e budget sviluppato con Laravel e Filament Admin Panel. Il sistema permette di gestire fornitori, contratti, budget annuali e contatti con un sistema di autorizzazioni basato sui ruoli.

## Caratteristiche Principali

- **Gestione Contratti**: Contratti con fornitori, categorie, date di scadenza, importi e allegati
- **Gestione Budget**: Budget annuali suddivisi per categoria contratto
- **Gestione Fornitori**: Anagrafica fornitori con contatti associati
- **Dashboard Avanzata**: Widget per visualizzazione contratti in scadenza, calendario eventi, totali spesa
- **Sistema di Autorizzazioni**: Gestione ruoli e permessi tramite Filament Shield
- **Interfaccia Moderna**: Admin panel completo con Filament v3

## Requisiti di Sistema

- PHP 8.2+
- Composer
- Node.js e npm
- SQLite (per sviluppo) o MySQL/PostgreSQL (per produzione)

## Installazione

1. **Clona il repository**
   ```bash
   git clone <repository-url>
   cd SpenderLock
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

4. **Configura il database**
   - Modifica il file `.env` con le tue credenziali database
   - Per sviluppo rapido, usa SQLite (già configurato)

5. **Esegui le migration**
   ```bash
   php artisan migrate --seed
   ```

6. **Crea un utente amministratore**
   ```bash
   php artisan filament:user
   ```

## Sviluppo

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
php artisan migrate
php artisan migrate:fresh --seed

# Test
composer run test
php artisan test

# Code formatting
php artisan pint

# Filament
php artisan filament:upgrade
php artisan shield:generate
```

## Struttura del Progetto

### Modelli Principali
- **Contract**: Contratto con fornitore, categoria, date e importi
- **Budget**: Budget annuale per categoria contratto
- **Supplier**: Anagrafica fornitore
- **Contact**: Contatto associato a fornitore
- **ContractCategory**: Categoria per classificazione contratti

### Filament Resources
- Admin panel completo per tutti i modelli
- Widget personalizzati per dashboard
- Sistema di autorizzazioni integrato
- Upload file per allegati contratti

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
