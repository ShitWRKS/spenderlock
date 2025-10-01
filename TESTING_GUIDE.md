# 🧪 Guida per Testare il Sistema Multi-Tenant

## 🎯 Setup Completato

✅ **Server in esecuzione**: http://localhost:8000  
✅ **Tenant 1 creato**: tenant1.local  
✅ **Tenant 2 creato**: tenant2.local  
✅ **Utenti admin configurati**

## 🌐 Configurazione Domini Locali

Prima di testare, aggiungi questi domini al tuo file `/etc/hosts` (macOS/Linux) o `C:\Windows\System32\drivers\etc\hosts` (Windows):

```
127.0.0.1 tenant1.local
127.0.0.1 tenant2.local
```

### Come modificare /etc/hosts su macOS:

```bash
sudo nano /etc/hosts
```

Aggiungi le righe sopra, salva e esci.

## 👥 Credenziali di Login

### Tenant 1 (tenant1.local)

-   **URL**: http://tenant1.local:8000/admin
-   **Email**: admin.t1@example.com
-   **Password**: password
-   **Ruolo**: Super Admin

### Tenant 2 (tenant2.local)

-   **URL**: http://tenant2.local:8000/admin
-   **Email**: admin.t2@example.com
-   **Password**: password
-   **Ruolo**: Super Admin

## 🧪 Test di Isolamento Dati

### Test 1: Login e Creazione Dati

1. **Accedi al Tenant 1**:

    - Vai su: http://tenant1.local:8000/admin
    - Login con: admin.t1@example.com / password
    - Crea alcuni **Fornitori** (Suppliers)
    - Crea alcuni **Contratti** (Contracts)
    - Crea alcuni **Budget**

2. **Accedi al Tenant 2**:
    - Vai su: http://tenant2.local:8000/admin
    - Login con: admin.t2@example.com / password
    - Verifica che **NON vedi** i dati del Tenant 1
    - Crea dati diversi per il Tenant 2

### Test 2: Verifica Isolamento Database

Puoi verificare l'isolamento a livello database:

```bash
# Verifica tenant nel database landlord
sqlite3 database/landlord.sqlite "SELECT * FROM tenants;"

# Verifica dati Tenant 1
sqlite3 database/tenant_tenant_1.sqlite "SELECT * FROM users;"
sqlite3 database/tenant_tenant_1.sqlite "SELECT * FROM suppliers;"

# Verifica dati Tenant 2
sqlite3 database/tenant_tenant_2.sqlite "SELECT * FROM users;"
sqlite3 database/tenant_tenant_2.sqlite "SELECT * FROM suppliers;"
```

### Test 3: Cross-Tenant Security

1. Mentre sei loggato sul Tenant 1, prova ad accedere all'URL del Tenant 2
2. Il sistema dovrebbe:
    - Riconoscere il cambio di tenant
    - Invalidare la sessione
    - Richiedere nuovo login

## 🎛️ Comandi Utili per Testing

### Creare nuovi tenant

```bash
php artisan tenants:create "Nome Azienda" "dominio.local" --seed
```

### Creare utenti in tenant specifici

```bash
php artisan tenants:create-user "tenant1.local" "Nome Utente" "email@example.com" "password" --admin
```

### Elencare utenti in un tenant

```bash
php artisan tenants:list-users "tenant1.local"
```

### Eseguire migrazioni per tutti i tenant

```bash
php artisan tenants:artisan "migrate --database=tenant"
```

### Verificare tenant esistenti

```bash
php artisan tinker --execute="App\Models\Tenant::all()->each(fn(\$t) => print(\$t->name . ' - ' . \$t->domain . PHP_EOL));"
```

## 🔍 Verifiche di Funzionamento

### ✅ Cosa Deve Funzionare:

1. **Isolamento Dati**: Ogni tenant vede solo i suoi dati
2. **Switch Automatico**: Cambiando dominio, cambia automaticamente il database
3. **Autenticazione Separata**: Utenti isolati per tenant
4. **Permissions**: Ruoli e permessi separati per tenant
5. **Cache Isolata**: Cache prefissata per tenant
6. **Sessioni Sicure**: Protezione cross-tenant

### ❌ Cosa NON Deve Succedere:

1. Vedere dati di altri tenant
2. Accesso cross-tenant con stessa sessione
3. Condivisione di cache tra tenant
4. Errori di connessione database

## 🐛 Troubleshooting

### Server non raggiungibile

```bash
# Verifica che il server sia attivo
curl http://tenant1.local:8000
```

### Domini non risolti

-   Verifica il file `/etc/hosts`
-   Riavvia il browser
-   Prova con `http://127.0.0.1:8000` direttamente

### Errori di login

```bash
# Verifica utenti nel tenant
php artisan tenants:list-users "tenant1.local"

# Reset password utente
php artisan tenants:create-user "tenant1.local" "New User" "new@example.com" "newpassword" --admin
```

### Database errors

```bash
# Verifica migrazioni tenant
php artisan tenants:artisan "migrate:status --database=tenant" --tenant=4

# Re-migra se necessario
php artisan tenants:artisan "migrate:fresh --database=tenant --seed" --tenant=4
```

## 🎉 Test di Successo

Se tutto funziona correttamente, dovresti vedere:

1. ✅ Login su tenant1.local con dati isolati
2. ✅ Login su tenant2.local con dati completamente separati
3. ✅ Creazione di record che rimangono isolati per tenant
4. ✅ Sicurezza cross-tenant che impedisce accesso non autorizzato
5. ✅ Performance buone con switching automatico database

**Il sistema multi-tenant è completamente funzionale! 🚀**

---

## 📞 Supporto

Per problemi o domande:

-   Consulta `MULTITENANCY.md` per la documentazione completa
-   Verifica i log Laravel: `tail -f storage/logs/laravel.log`
-   Usa i comandi di debug sopra elencati
