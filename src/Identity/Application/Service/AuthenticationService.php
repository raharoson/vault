<?php
declare(strict_types=1);
namespace App\Identity\Application\Service;

use App\Identity\Domain\Entity\RefreshToken;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\RefreshTokenRepositoryInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class AuthenticationService
{
    private const REFRESH_TOKEN_TTL_DAYS = 30;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function registerUser(string $email, string $plainPassword, string $firstName, string $lastName): User
    {
        if ($this->userRepository->findByEmail($email) !== null) {
            throw new \DomainException(sprintf('Email "%s" is already registered.', $email));
        }

        $user = new User($email, $firstName, $lastName);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
        return $user;
    }

    public function createRefreshToken(User $user, ?string $ipAddress = null): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $expiresAt = new \DateTimeImmutable(sprintf('+%d days', self::REFRESH_TOKEN_TTL_DAYS));
        $refreshToken = new RefreshToken($user, $tokenHash, $expiresAt, $ipAddress);

        $this->refreshTokenRepository->save($refreshToken);
        return $rawToken;
    }

    public function refreshAccessToken(string $rawToken): User
    {
        $tokenHash = hash('sha256', $rawToken);
        $refreshToken = $this->refreshTokenRepository->findByTokenHash($tokenHash);

        if ($refreshToken === null) {
            throw new \DomainException('Refresh token not found or already revoked.');
        }

        if (!$refreshToken->isValid()) {
            throw new \DomainException('Refresh token is expired or revoked.');
        }

        $refreshToken->revoke();
        $this->refreshTokenRepository->save($refreshToken);

        return $refreshToken->getUser();
    }

    public function revokeAllTokens(User $user): void
    {
        $this->refreshTokenRepository->revokeAllForUser($user);
    }
}
