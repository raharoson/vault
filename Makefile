.PHONY: install start test phpstan fixtures migrate jwt-keys \
        up down build logs shell sh-php migrate-docker fixtures-docker

## Install project dependencies
install:
	composer install
	cp -n .env.example .env || true
	$(MAKE) jwt-keys

## Start Symfony local server
start:
	symfony server:start --daemon

## Run PHPUnit test suite
test:
	php bin/phpunit

## Run PHPStan static analysis
phpstan:
	vendor/bin/phpstan analyse --memory-limit=256M

## Load Alice fixtures
fixtures:
	php bin/console doctrine:fixtures:load --no-interaction

## Run Doctrine migrations
migrate:
	php bin/console doctrine:migrations:migrate --no-interaction

## Generate JWT RSA key pair
jwt-keys:
	mkdir -p config/jwt
	openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096 -pass pass:$(shell grep JWT_PASSPHRASE .env | cut -d '=' -f2)
	openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:$(shell grep JWT_PASSPHRASE .env | cut -d '=' -f2)
	@echo "JWT keys generated in config/jwt/"

## Clear Symfony cache
cache-clear:
	php bin/console cache:clear

## Show routes
routes:
	php bin/console debug:router

## Create database
db-create:
	php bin/console doctrine:database:create --if-not-exists

## Drop database
db-drop:
	php bin/console doctrine:database:drop --force --if-exists

# ── Docker ────────────────────────────────────────────────────────────────────

## Build and start all containers
up:
	docker compose up --build -d

## Stop all containers
down:
	docker compose down

## Rebuild images without cache
build:
	docker compose build --no-cache

## Follow logs (all services, or pass s=php|nginx|postgres|messenger)
logs:
	docker compose logs -f $(s)

## Open a shell in the php container
shell:
	docker compose exec php sh

## Run PHPUnit tests inside the php container
test-docker:
	docker compose exec php php bin/phpunit

## Run PHPStan inside the php container
phpstan-docker:
	docker compose exec php vendor/bin/phpstan analyse --memory-limit=256M

## Run migrations inside the php container
migrate-docker:
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

## Load fixtures inside the php container
fixtures-docker:
	docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
