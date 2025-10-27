# Google Apps Script - SpenderLock Gmail Addon

Addon Gmail per integrazione SpenderLock con Google Workspace.

## 📁 File del Progetto

- **appsscript.json** - Manifest dell'addon (configurazione Gmail + Calendar)
- **Code.gs** - Entry points principali (buildAddOn, buildHomepage, buildCalendarAddOn)
- **Config.gs** - Configurazione OAuth2 e API endpoints
- **Auth.gs** - Gestione token OAuth2 (client_credentials flow)
- **API.gs** - Wrapper per chiamate API Laravel
- **UI-v1.3.gs** - Interfaccia CardService completa

## 🚀 Setup Rapido

### 1. Crea Progetto Apps Script

1. Vai su https://script.google.com
2. Nuovo progetto → "SpenderLock Gmail Addon"
3. Abilita `appsscript.json`: Project Settings → Show "appsscript.json" in editor

### 2. Copia File

Copia manualmente il contenuto di ogni file `.gs` in un nuovo file script con lo stesso nome.

Per `appsscript.json`: sostituisci il contenuto nel file manifest del progetto.

### 3. Configura Credenziali

Modifica `Config.gs`:

```javascript
const CONFIG = {
  // URL Backend Laravel
  API_BASE_URL: 'https://your-domain.com/api',
  OAUTH_TOKEN_URL: 'https://your-domain.com/oauth/token',

  // OAuth2 Credentials (da Laravel Passport)
  OAUTH_CLIENT_ID: 'YOUR_CLIENT_ID',
  OAUTH_CLIENT_SECRET: 'YOUR_CLIENT_SECRET',

  // Multi-tenancy
  TENANT_DOMAIN: 'localhost',  // Deve matchare con tenant Laravel
};
```

### 4. Deploy e Test

1. **Deploy** → **Test deployments** → **Install**
2. Autorizza i permessi richiesti
3. Apri Gmail e verifica che appaia l'addon nella sidebar

## 📝 Funzionalità

### Card Disponibili

1. **Homepage** - Panoramica contratti in scadenza
2. **Contract Details** - Dettagli contratto selezionato
3. **Supplier Details** - Dettagli fornitore
4. **Search** - Ricerca contratti e fornitori

### Azioni

- 📋 Visualizza contratti in scadenza nei prossimi 30 giorni
- 🔍 Cerca contratti per nome
- 🏢 Cerca fornitori per nome
- 📨 Visualizza dettagli completi
- 📝 Aggiungi commenti (future)
- 📧 Importa email come commenti (future)

## 🔌 API Endpoints Utilizzati

| Endpoint | Metodo | Descrizione |
|----------|--------|-------------|
| `/contracts/upcoming?days=30` | GET | Contratti in scadenza |
| `/contracts/search?q=term` | GET | Ricerca contratti |
| `/contracts/{id}` | GET | Dettaglio contratto |
| `/suppliers` | GET | Lista fornitori |
| `/suppliers?q=term` | GET | Ricerca fornitori |
| `/suppliers/{id}` | GET | Dettaglio fornitore |
| `/comments/{type}/{id}` | GET | Lista commenti |
| `/comments/{type}/{id}` | POST | Crea commento |

Tutte le richieste richiedono:
- Header `Authorization: Bearer {token}`
- Header `X-Tenant-Domain: {tenant}`

## 🔒 Sicurezza

### ⚠️ IMPORTANTE

**NON committare MAI** `Config.gs` con credenziali reali su repository pubblici!

### Best Practices

1. **Usa Properties Service per credenziali**:

```javascript
const props = PropertiesService.getScriptProperties();
const CONFIG = {
  OAUTH_CLIENT_ID: props.getProperty('CLIENT_ID'),
  OAUTH_CLIENT_SECRET: props.getProperty('CLIENT_SECRET'),
  // ...
};
```

Imposta le properties:
```javascript
function setupCredentials() {
  const props = PropertiesService.getScriptProperties();
  props.setProperty('CLIENT_ID', 'your-client-id');
  props.setProperty('CLIENT_SECRET', 'your-client-secret');
}
```

2. **Limita scope OAuth2** al minimo necessario
3. **Ruota credenziali** regolarmente
4. **Monitora accessi** API dal backend Laravel

## 🐛 Troubleshooting

### Addon non appare in Gmail

- Verifica deployment attivo: Deploy → Manage deployments
- Controlla autorizzazioni: Apps Script → Executions
- Reinstalla: Deploy → Test deployments → Uninstall → Install

### Errore "Unauthorized" (401)

- Verifica `OAUTH_CLIENT_ID` e `OAUTH_CLIENT_SECRET` in `Config.gs`
- Controlla che le credenziali siano corrette sul backend Laravel
- Cancella token cached: `clearToken()` in `Auth.gs`

### Errore "Tenant not found"

- Verifica che `TENANT_DOMAIN` in `Config.gs` matchi con un tenant esistente
- Controlla che l'header `X-Tenant-Domain` venga inviato correttamente
- Verifica log Laravel per errori di multitenancy

### API timeout

- Aumenta `API_TIMEOUT_MS` in `Config.gs`
- Verifica connessione di rete
- Controlla che il backend Laravel sia raggiungibile

### Token expired

Il token OAuth2 viene cached per 1 ora. Se scade:
- Viene automaticamente richiesto un nuovo token
- Se persiste, cancella cache: `clearToken()` da `Auth.gs`

## 📊 Logging e Debug

Per abilitare logging dettagliato, apri **Executions** nel progetto Apps Script:

- View → Executions
- Seleziona l'esecuzione recente
- Visualizza log console

Debug manuale:
```javascript
// In Code.gs o altro file
function testAPI() {
  const token = getAccessToken();
  Logger.log('Token: ' + token);

  const contracts = getUpcomingContracts(30);
  Logger.log('Contracts: ' + JSON.stringify(contracts));
}
```

## 📚 Documentazione

- [Google Apps Script](https://developers.google.com/apps-script)
- [Gmail Add-ons](https://developers.google.com/gmail/add-ons)
- [Card Service](https://developers.google.com/apps-script/reference/card-service)
- [OAuth2 Client Credentials](https://oauth.net/2/grant-types/client-credentials/)

## 🔄 Versioning

### v1.0.0 (27 Ottobre 2024)

- ✅ OAuth2 client_credentials flow
- ✅ Multi-tenancy support (header-based)
- ✅ 8 API endpoints integrati
- ✅ 4 card UI (Homepage, Contract, Supplier, Search)
- ✅ Token caching (1h expiry)
- ✅ Error handling e retry logic

---

**Note**: Questo addon è progettato per uso interno e richiede backend Laravel SpenderLock configurato correttamente.
