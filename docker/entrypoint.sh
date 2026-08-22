#!/bin/sh
set -eu

# Desenvolvimento: evita que a aplicação falhe sem .env.
# Produção: sempre forneça APP_KEY via secret/ambiente persistente.
key_file="/var/www/html/storage/app/.app_key"
jwt_file="/var/www/html/storage/app/.jwt_secret"

if [ -n "${APP_KEY:-}" ]; then
  # Em produção, a variável do provedor deve ser a fonte de verdade.
  # O arquivo só é usado quando APP_KEY não foi configurada.
  :
elif [ -f "$key_file" ] && [ -s "$key_file" ]; then
  APP_KEY="$(cat "$key_file")"
else
  APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
  printf '%s' "$APP_KEY" > "$key_file"
  chmod 600 "$key_file"
fi

export APP_KEY

if [ "$(printf '%s' "${JWT_SECRET:-}" | wc -c)" -ge 32 ]; then
  # Em produção, o segredo configurado no Render tem prioridade.
  :
elif [ -f "$jwt_file" ] && [ -s "$jwt_file" ]; then
  JWT_SECRET="$(cat "$jwt_file")"
else
  JWT_SECRET="$(php -r 'echo bin2hex(random_bytes(32));')"
  printf '%s' "$JWT_SECRET" > "$jwt_file"
  chmod 600 "$jwt_file"
fi
export JWT_SECRET

# O Laravel carrega o .env durante o bootstrap. Mantenha as configurações
# persistentes e do banco alinhadas com o ambiente do container.
touch .env
set_env() {
  name="$1"
  value="$2"
  if grep -q "^${name}=" .env; then
    sed -i "s|^${name}=.*|${name}=${value}|" .env
  else
    printf '%s=%s\n' "$name" "$value" >> .env
  fi
}

set_env APP_KEY "$APP_KEY"
set_env DB_CONNECTION "${DB_CONNECTION:-pgsql}"
set_env DB_HOST "${DB_HOST:-db}"
set_env DB_PORT "${DB_PORT:-5432}"
set_env DB_DATABASE "${DB_DATABASE:-nfe}"
set_env DB_USERNAME "${DB_USERNAME:-nfe}"
set_env DB_PASSWORD "${DB_PASSWORD:-}"
set_env CACHE_STORE "${CACHE_STORE:-file}"
set_env JWT_SECRET "$JWT_SECRET"

php artisan config:clear
php artisan migrate --force
exec php artisan serve --host=0.0.0.0 --port=8000
