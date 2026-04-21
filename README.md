# LockSelf Vault

Secure secrets management API built with Symfony 8.0 and API Platform. Inspired by vault managers like LockSelf, it provides encrypted secret storage, organization-based access control, secret sharing, and full audit logging.

## Stack technique

- **PHP 8.4** with strict types throughout
- **Symfony 8.0** (Framework, Security, Messenger, Validator, Serializer, UID)
- **API Platform 4** — REST + JSON-LD API with OpenAPI docs
- **Doctrine ORM 3** — PostgreSQL 16, UUID primary keys
- **LexikJWTAuthentication** — stateless JWT authentication (RS256, argon2id passwords)
- **NelmioCORS** — configurable CORS policy
- **Symfony Messenger** — async audit log dispatch
- **PHPStan level 6** — static analysis with Symfony and Doctrine extensions
- **PHPUnit 11** — unit and integration tests with AliceBundle fixtures

## Architecture

The project follows a modular DDD structure with six modules:

```
src/
  Shared/           # Base classes, interfaces, value objects
  Identity/         # User authentication, JWT refresh tokens
  Organization/     # Multi-tenant organizations and memberships
  Vault/            # Secret and folder management (encrypted at rest)
  Sharing/          # Secret sharing between users/organizations
  Audit/            # Immutable audit log of all sensitive operations
```

Each module is structured in layers: `Domain/`, `Application/`, `Infrastructure/`, `Presentation/`.

## Installation

### 1. Clone and install dependencies

```bash
git clone <repo-url> lockself-vault
cd lockself-vault
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
# Edit .env and set your values:
# - APP_SECRET
# - DATABASE_URL
# - JWT_PASSPHRASE
# - ENCRYPTION_KEY (32-byte hex string)
```

### 3. Generate JWT RSA keys

```bash
make jwt-keys
# Or manually:
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

### 4. Create database and run migrations

```bash
make db-create
make migrate
```

### 5. Load development fixtures (optional)

```bash
make fixtures
```

### 6. Start the development server

```bash
make start
# or: symfony server:start
```

The API will be available at `https://localhost:8000/api` with interactive docs at `https://localhost:8000/api/docs`.

## Commandes utiles

| Command | Description |
|---------|-------------|
| `make install` | Full install (deps + .env copy + JWT keys) |
| `make start` | Start Symfony local server (daemon) |
| `make test` | Run PHPUnit test suite |
| `make phpstan` | Run PHPStan static analysis |
| `make migrate` | Apply Doctrine migrations |
| `make fixtures` | Load Alice/Faker fixtures |
| `make jwt-keys` | (Re)generate JWT RSA key pair |
| `make cache-clear` | Clear Symfony cache |
| `make routes` | List all registered routes |
| `php bin/console debug:container` | Inspect the DI container |
| `php bin/console doctrine:schema:validate` | Validate ORM mapping |

## Security notes

- All secrets are encrypted at rest using `ENCRYPTION_KEY` (AES-256)
- JWT tokens expire after 3600 seconds (1 hour)
- Passwords are hashed with argon2id
- All API endpoints require authentication except `/api/auth/login`, `/api/auth/refresh`, and `/api/docs`
- CORS is restricted to localhost by default; configure `CORS_ALLOW_ORIGIN` for production
- JWT private key and `.env` must never be committed to version control
