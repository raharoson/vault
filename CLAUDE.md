# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
make test               # Run PHPUnit test suite
make phpstan            # PHPStan static analysis (level 6)
make migrate            # Apply Doctrine migrations
make fixtures           # Load Alice/Faker fixtures (dev only)
make start              # Start Symfony local server (daemon)
make cache-clear        # Clear Symfony cache
make routes             # List registered routes

# Single test file or filter
php bin/phpunit tests/path/to/Test.php
php bin/phpunit --filter testMethodName

# Useful console commands
php bin/console debug:container [ServiceClass]
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:diff   # Generate migration from entity changes
```

> PHPStan requires a warmed cache: run `php bin/console cache:warmup` before the first `make phpstan` in a fresh environment.

## Architecture

Six DDD modules under `src/`, each with four layers:

```
src/
  Shared/       # EncryptionService (AES-256-GCM), OrganizationContext, Email VO
  Identity/     # User entity, JWT refresh tokens, login audit subscriber
  Organization/ # Multi-tenant orgs, memberships, MembershipRole enum
  Vault/        # Secrets + folders — encrypted at rest
  Sharing/      # SecretShare grants (VIEW/EDIT/SHARE permissions)
  Audit/        # Immutable AuditLog entity, AuditLogger service
```

Each module follows: `Domain/` → `Application/` → `Infrastructure/` → `Presentation/`.

### Key cross-cutting patterns

**Multi-tenancy via header** — Every authenticated request scoped to an organization must include `X-Organization-Id: <uuid>`. `OrganizationContext` (`Shared/Infrastructure`) resolves it from the request header and is injected into controllers. Call `requireCurrent()` to throw on missing context.

**Encryption** — Secrets are encrypted before persistence. `EncryptionServiceInterface` (AES-256-GCM, 32-byte key from `ENCRYPTION_KEY` env hex-encoded) provides `encryptToJson()`/`decryptFromJson()`. Never store plaintext in the `Secret` entity.

**Authorization via Voters** — `SecretVoter` and `OrganizationVoter` gate all resource access. `SecretVoter` checks both organization membership AND `SecretShare` permissions (from `Sharing` module) to determine VIEW/EDIT/DELETE/SHARE. Always call `denyAccessUnlessGranted(SecretVoter::VIEW, $secret)` before decrypting.

**Repository pattern** — Domain layer defines interfaces (`Domain/Repository/*RepositoryInterface`), Infrastructure provides Doctrine implementations. All interface-to-implementation aliases are in `config/services.yaml`. Never inject concrete repository classes; use the interface.

**Audit logging** — All sensitive operations (secret CRUD, login events, share actions) must be logged via `AuditLogger::log()`. Failures are swallowed internally — the logger never throws. Called directly from controllers, not via Messenger.

### Request flow (Vault example)
```
JWT auth → SecretController → OrganizationContext::requireCurrent()
         → SecretRepository::findByOrganizationForUser()
         → denyAccessUnlessGranted(SecretVoter) → EncryptionService::decrypt()
         → AuditLogger::log()
```

## Conventions

- `declare(strict_types=1)` on every PHP file
- UUID primary keys (Ramsey `Uuid`, stored as `uuid` Doctrine type)
- All entities excluded from DI autowire — they are `new`ed manually in services/controllers
- Validation uses Symfony Validator constraints on DTOs (`Application/DTO/`), not entities
- `SecretOutput::fromEntity($secret, $decryptedPayload)` is the canonical way to build API responses; the decrypted payload is optional (omitted on list endpoints)
