const CONFIG = {
  // Laravel API
  API_BASE_URL: 'http://localhost:8000/api',
  OAUTH_TOKEN_URL: 'http://localhost:8000/oauth/token',

  // OAuth2 Credentials (Laravel Passport client_credentials)
  OAUTH_CLIENT_ID: '019a252b-844c-7192-a76b-94858e700651',
  OAUTH_CLIENT_SECRET: 'rtKLxJhQd6rppdlwqIjC3WKewQgtsVPY6QQ9bpdZ',

  // Multi-tenancy
  TENANT_DOMAIN: 'localhost',

  // Cache settings
  TOKEN_CACHE_KEY: 'spenderlock_access_token',
  TOKEN_CACHE_EXPIRY_KEY: 'spenderlock_token_expiry',
  CACHE_DURATION_SECONDS: 3600,

  // API settings
  API_TIMEOUT_MS: 10000,
  MAX_RETRIES: 3,
  RETRY_DELAY_MS: 1000,

  // UI settings
  MAX_CONTRACTS_DISPLAY: 10,
  DEFAULT_UPCOMING_DAYS: 30,

  // Colors
  PRIMARY_COLOR: '#FFC107',
  SECONDARY_COLOR: '#FFECB3'
};

function getConfig(key) {
  return CONFIG[key];
}
