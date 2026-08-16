#!/usr/bin/env bash
set -Eeuo pipefail

DOMAIN="app.nfeblao.com.br"
COMPOSE=(docker compose -f docker-compose.yml)

if [[ ! -f .env ]]; then
  cp .env.example .env
  echo ".env criado. Edite-o com as credenciais de produção e execute novamente."
  exit 1
fi

required_vars=(DB_DATABASE DB_USERNAME DB_PASSWORD NFE_CERTIFICATE_PASSWORD ADMIN_PASSWORD OPERATOR_PASSWORD)
for var in "${required_vars[@]}"; do
  value="$(grep -E "^${var}=" .env | cut -d= -f2- || true)"
  if [[ -z "$value" || "$value" == *"troque"* || "$value" == *"example"* ]]; then
    echo "ERRO: defina um valor de produção para ${var} no .env" >&2
    exit 1
  fi
done

sed -i \
  -e 's|^APP_ENV=.*|APP_ENV=production|' \
  -e 's|^APP_DEBUG=.*|APP_DEBUG=false|' \
  -e "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" \
  -e 's|^DB_HOST=.*|DB_HOST=db|' \
  -e 's|^DB_PORT=.*|DB_PORT=5432|' .env

mkdir -p secrets/certificates nginx/conf.d nginx/snippets
chmod 700 secrets secrets/certificates

if ! grep -qE '^APP_KEY=.+$' .env; then
  app_key="$("${COMPOSE[@]}" run --rm --no-deps --entrypoint php app artisan key:generate --show)"
  sed -i "s|^APP_KEY=.*|APP_KEY=${app_key}|" .env
fi

LETSENCRYPT_EMAIL="${LETSENCRYPT_EMAIL:-$(grep -E '^LETSENCRYPT_EMAIL=' .env | cut -d= -f2- || true)}"
if [[ -z "$LETSENCRYPT_EMAIL" ]]; then
  echo "ERRO: exporte LETSENCRYPT_EMAIL com um e-mail válido antes do deploy." >&2
  exit 1
fi

echo "Construindo a imagem e iniciando banco/aplicação/Nginx..."
"${COMPOSE[@]}" build --pull app

# O Nginx precisa de arquivos de certificado para iniciar. Eles serão substituídos
# pelo certificado real do Let's Encrypt logo depois.
"${COMPOSE[@]}" run --rm --no-deps --entrypoint sh certbot -c \
  "mkdir -p /etc/letsencrypt/live/${DOMAIN} /etc/letsencrypt/archive/${DOMAIN} && \
   openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
   -keyout /etc/letsencrypt/live/${DOMAIN}/privkey.pem \
   -out /etc/letsencrypt/live/${DOMAIN}/fullchain.pem \
   -subj '/CN=${DOMAIN}'"

"${COMPOSE[@]}" up -d db app web

echo "Executando migrations, seeders e cache de produção..."
"${COMPOSE[@]}" exec app php artisan migrate --seed --force
"${COMPOSE[@]}" exec app php artisan optimize

echo "Solicitando certificado Let's Encrypt..."
"${COMPOSE[@]}" run --rm --entrypoint certbot certbot certonly --webroot -w /var/www/certbot \
  --email "$LETSENCRYPT_EMAIL" --agree-tos --no-eff-email --non-interactive \
  --keep-until-expiring -d "$DOMAIN"

"${COMPOSE[@]}" restart web
"${COMPOSE[@]}" up -d certbot
"${COMPOSE[@]}" ps
echo "FiscalFlow publicado em https://${DOMAIN}"
