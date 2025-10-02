# 🎭 Sistema Demo SpenderLock - Documentazione Completa

## 📋 Panoramica
Sistema demo completamente automatizzato per SpenderLock con reset automatico giornaliero per mantenere un ambiente demo sempre pulito e funzionante.

## 🚀 Comandi Demo Disponibili

### 1. Setup Demo Completo
```bash
php artisan demo:simple-setup [domain] [--reset]
```

**Funzioni:**
- ✅ Crea nuovo tenant demo
- ✅ Esegue migrazioni database
- ✅ Genera 42 permessi Filament Shield
- ✅ Crea ruolo `super_admin` con tutti i permessi
- ✅ Crea utente demo con accesso completo
- ✅ Popola database con dati demo realistici

**Esempio:**
```bash
php artisan demo:simple-setup demo-final.local --reset
```

### 2. Reset Demo
```bash
php artisan demo:reset [domain]
```

**Funzioni:**
- 🗑️ Cancella tutti i dati demo esistenti
- 👤 Preserva l'utente demo (demo@demo.local)
- 🎨 Ricrea dati demo freschi
- ⚡ Veloce e sicuro

**Esempio:**
```bash
php artisan demo:reset demo-final.local
```

## 📊 Dati Demo Inclusi

### 🏢 Categorie Contratti (5)
- Software e Licenze
- Servizi IT  
- Marketing e Pubblicità
- Consulenze
- Manutenzioni

### 🏪 Fornitori (4)
- **TechSoft Solutions SRL** - Software enterprise
- **Digital Marketing Pro** - Agenzia marketing digitale
- **IT Consulting Group** - Consulenza IT strategica
- **CloudHost Services** - Cloud hosting provider

### 👥 Contatti (5)
- Contatti di riferimento per ogni fornitore
- Ruoli: Account Manager, Technical Support, Creative Director, etc.

### 📋 Contratti (6)
- **Licenza Software CRM** - €15.000 annuale
- **Hosting Cloud** - €700/mese ricorrente  
- **Campagna Marketing Q1-Q2** - €12.000 milestone
- **Consulenza Trasformazione Digitale** - €25.000 milestone
- **Licenze Microsoft Office 365** - €6.000 annuale
- **Manutenzione Sistema IT** - €800 trimestrale

### 💰 Budget (5)
- Budget allocati per ogni categoria di contratto
- Totale: €210.000 budget annuale allocato

## 🔄 Reset Automatico

### ⏰ Scheduling
Il sistema è configurato per il reset automatico:

```php
// routes/console.php
Schedule::command('demo:reset demo-final.local')
    ->daily()
    ->at('03:00')
    ->timezone('Europe/Rome');
```

**Configurazione:**
- 🕒 **Orario**: Ogni giorno alle 03:00 (Europa/Roma)
- 📝 **Logging**: Success/failure automatico nei log
- 🔄 **Frequenza**: Giornaliera
- 📱 **Monitoraggio**: Log eventi per debug

### 🚀 Per Produzione
Per attivare lo scheduler in produzione:

```bash
# Crontab entry
* * * * * cd /path/to/spenderlock && php artisan schedule:run >> /dev/null 2>&1
```

## 👤 Credenziali Demo

### 🔐 Utente Demo
- **Email**: `demo@demo.local`
- **Password**: `demo123`
- **Ruolo**: `super_admin`
- **Permessi**: Accesso completo a tutto il sistema (42 permessi)

### 🌐 Accesso
- **URL**: `http://demo-final.local` (o il dominio configurato)
- **Panel**: Filament Admin Panel
- **Lingua**: Italiano

## 📁 File Creati/Modificati

### ✨ Nuovi Comandi
```
app/Console/Commands/
├── SimpleDemoSetupCommand.php      # Setup completo demo
└── SimpleDemoResetCommand.php      # Reset veloce demo
```

### 🌱 Seeder Demo
```
database/seeders/
└── DemoDataSeeder.php              # Dati demo completi
```

### ⚙️ Configurazione
```
routes/
└── console.php                     # Scheduling reset automatico
```

## 🎯 Utilizzo per App Store

### 📱 Demo Pubblico
Il sistema è progettato per pubblicazione su app store con:

1. **🔄 Reset Automatico**: Mantiene demo sempre pulito
2. **📊 Dati Realistici**: Scenario aziendale credibile  
3. **👤 Accesso Immediato**: Credenziali semplici da ricordare
4. **🛡️ Sicurezza**: Isolamento completo tra tenant
5. **⚡ Performance**: Utilizzo SQLite per velocità

### 🚀 Deploy Demo
Per il deploy di produzione:

```bash
# 1. Setup tenant demo
php artisan demo:simple-setup demo.yourstore.com

# 2. Configura dominio nel DNS
# 3. Configura web server (Nginx/Apache)
# 4. Attiva scheduler per reset automatico
```

## 🔧 Troubleshooting

### ❓ Problemi Comuni

**Tenant già esistente:**
```bash
php artisan demo:simple-setup domain --reset
```

**Permessi mancanti:**
```bash
# Il comando ricrea automaticamente tutti i 42 permessi
```

**Database corrotto:**
```bash
rm -f database/tenant_*.sqlite
php artisan demo:simple-setup domain --reset
```

### 📝 Debug
```bash
# Verifica tenant esistenti
php artisan tenants:list

# Check log scheduler
tail -f storage/logs/laravel.log | grep "Demo tenant"
```

## ✅ Checklist Pre-Pubblicazione

- [ ] Test setup demo funziona
- [ ] Test reset demo funziona  
- [ ] Credenziali demo corrette
- [ ] Dati demo realistici e completi
- [ ] Scheduler configurato per produzione
- [ ] DNS configurato per dominio demo
- [ ] Web server configurato
- [ ] HTTPS abilitato (se necessario)
- [ ] Monitoring log attivo

---

🎉 **Il sistema demo è ora completamente funzionale e pronto per la pubblicazione!**