<?php
declare(strict_types=1);
namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Entity\RefreshToken;
use App\Identity\Domain\Entity\User;

interface RefreshTokenRepositoryInterface
{
    public function findByTokenHash(string $tokenHash): ?RefreshToken;
    public function findValidTokensByUser(User $user): array;
    public function save(RefreshToken $token): void;
    public function revokeAllForUser(User $user): void;
    public function removeExpired(): int;
}
