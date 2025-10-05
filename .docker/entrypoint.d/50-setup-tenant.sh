#!/bin/sh

# Hook per il setup automatico del tenant di default
# Questo script viene eseguito automaticamente dall'immagine serversideup/php

set -e

if [ "$AUTORUN_LARAVEL_SETUP_DEFAULT_TENANT" = "true" ]; then
    echo "🔧 Esecuzione setup tenant di default..."
    /var/www/html/docker-setup.sh
    echo "✅ Setup tenant completato"
else
    echo "ℹ️  AUTORUN_LARAVEL_SETUP_DEFAULT_TENANT non abilitato, saltando setup"
fi

# Non usare "exit 0" ma "return 0" per non bloccare gli altri script
return 0
