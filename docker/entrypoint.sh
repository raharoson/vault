#!/bin/sh
set -e

# Rendre var/ accessible en écriture
mkdir -p var/cache var/log
chmod -R 777 var/

# En dev : installer/synchroniser les dépendances (sans déclencher les scripts Flex)
if [ "${APP_ENV}" != "prod" ]; then
    composer install --no-interaction --no-scripts
fi

# Attendre que PostgreSQL soit prêt (avant la warmup qui touche Doctrine)
POSTGRES_HOST="${POSTGRES_HOST:-postgres}"
POSTGRES_USER="${POSTGRES_USER:-app}"
POSTGRES_DB="${POSTGRES_DB:-lockself_vault}"

echo "Waiting for PostgreSQL at ${POSTGRES_HOST}…"
until pg_isready -h "${POSTGRES_HOST}" -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" > /dev/null 2>&1; do
    sleep 1
done
echo "PostgreSQL is ready."

# Générer les clés JWT RSA si elles sont absentes
if [ ! -f /app/config/jwt/private.pem ]; then
    echo "Generating JWT RSA key pair…"
    mkdir -p /app/config/jwt
    openssl genpkey \
        -out /app/config/jwt/private.pem \
        -aes256 \
        -algorithm rsa \
        -pkeyopt rsa_keygen_bits:4096 \
        -pass "pass:${JWT_PASSPHRASE}"
    openssl pkey \
        -in /app/config/jwt/private.pem \
        -out /app/config/jwt/public.pem \
        -pubout \
        -passin "pass:${JWT_PASSPHRASE}"
    echo "JWT keys generated."
fi

# Régénérer le cache Symfony (après postgres pour ne pas bloquer la warmup)
rm -rf var/cache/*
php bin/console cache:warmup
php bin/console assets:install public --symlink --relative 2>/dev/null || true

# Appliquer les migrations Doctrine (ne bloque pas si aucune migration n'existe)
php bin/console doctrine:migrations:migrate --no-interaction 2>&1 || true

exec "$@"
