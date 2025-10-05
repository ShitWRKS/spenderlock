#!/bin/sh

# Script di setup automatico per SpenderLock
# Viene eseguito automaticamente durante il setup del container Docker

echo "🚀 SpenderLock - Setup Automatico"
echo "=================================="

# Verifica se le variabili d'ambiente per il tenant di default sono impostate
if [ -z "$DEFAULT_TENANT_NAME" ] || [ -z "$DEFAULT_TENANT_DOMAIN" ]; then
    echo "⚠️  Variabili DEFAULT_TENANT_NAME o DEFAULT_TENANT_DOMAIN non impostate"
    echo "ℹ️  Saltando setup tenant di default"
    return 0
fi

# Aspetta che il database sia pronto
echo "⏳ Attesa connessione database..."
sleep 5

# Esegui migrazioni landlord
echo "📋 Esecuzione migrazioni landlord..."
php artisan migrate --database=landlord --path=database/migrations/landlord --force

# Setup tenant di default solo se le variabili sono impostate
echo "🏢 Setup tenant di default..."
echo "   Nome: ${DEFAULT_TENANT_NAME}"
echo "   Dominio: ${DEFAULT_TENANT_DOMAIN}"
echo "   Admin: ${DEFAULT_ADMIN_NAME} (${DEFAULT_ADMIN_EMAIL})"

php artisan tenants:setup-default \
    --tenant-name="${DEFAULT_TENANT_NAME}" \
    --tenant-domain="${DEFAULT_TENANT_DOMAIN}" \
    --admin-name="${DEFAULT_ADMIN_NAME:-Administrator}" \
    --admin-email="${DEFAULT_ADMIN_EMAIL:-admin@localhost}" \
    --admin-password="${DEFAULT_ADMIN_PASSWORD:-password}"

echo ""
echo "✅ Setup completato!"
echo "🌐 Accedi su: http://${DEFAULT_TENANT_DOMAIN}/admin"
echo "📧 Email: ${DEFAULT_ADMIN_EMAIL:-admin@localhost}"
echo "🔑 Password: ${DEFAULT_ADMIN_PASSWORD:-password}"