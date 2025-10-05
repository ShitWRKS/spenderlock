#!/bin/bash

# Script di setup automatico per SpenderLock
# Viene eseguito automaticamente durante il setup del container Docker

echo "🚀 SpenderLock - Setup Automatico"
echo "=================================="

# Aspetta che il database sia pronto
echo "⏳ Attesa connessione database..."
sleep 5

# Esegui migrazioni base
echo "📋 Esecuzione migrazioni landlord..."
php artisan migrate --force

# Esegui migrazioni del landlord
echo "📋 Esecuzione migrazioni landlord..."
php artisan migrate --database=landlord --path=database/migrations/landlord --force

# Setup tenant di default
echo "🏢 Setup tenant di default..."
php artisan tenants:setup-default

echo "✅ Setup completato!"
echo "🌐 Accedi su: http://localhost/admin"
echo "📧 Email: ${DEFAULT_ADMIN_EMAIL}"
echo "🔑 Password: ${DEFAULT_ADMIN_PASSWORD}"