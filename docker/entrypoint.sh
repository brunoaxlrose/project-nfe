#!/bin/sh
set -eu

# Desenvolvimento: evita que a aplicação falhe sem .env.
# Produção: sempre forneça APP_KEY via secret/ambiente persistente.
if [ -z "${APP_KEY:-}" ]; then
  APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
  export APP_KEY
fi

php artisan migrate --force
exec php artisan serve --host=0.0.0.0 --port=8000
