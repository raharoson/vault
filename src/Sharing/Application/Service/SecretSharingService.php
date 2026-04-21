<?php
declare(strict_types=1);
namespace App\Sharing\Application\Service;

use App\Identity\Domain\Entity\User;
use App\Sharing\Domain\Entity\SecretShare;
use App\Sharing\Domain\Enum\SharePermission;
use App\Sharing\Domain\Repository\SecretShareRepositoryInterface;
use App\Vault\Domain\Entity\Secret;

final class SecretSharingService
{
    public function __construct(
        private readonly SecretShareRepositoryInterface $shareRepository,
    ) {}

    public function shareSecret(
        Secret $secret,
        User $sharedWith,
        User $sharedBy,
        SharePermission $permission,
        ?\DateTimeImmutable $expiresAt = null,
    ): SecretShare {
        // Vérifier si un partage actif existe déjà
        $existing = $this->shareRepository->findByUserAndSecret($sharedWith, $secret);
        if ($existing !== null) {
            throw new \DomainException(sprintf(
                'Secret is already shared with user "%s".',
                $sharedWith->getEmail(),
            ));
        }

        // Pas de partage avec le propriétaire
        if ($secret->isOwnedBy($sharedWith)) {
            throw new \DomainException('Cannot share a secret with its owner.');
        }

        $share = new SecretShare($secret, $sharedWith, $sharedBy, $permission, $expiresAt);
        $this->shareRepository->save($share);
        return $share;
    }

    public function revokeShare(SecretShare $share): void
    {
        $this->shareRepository->remove($share);
    }

    public function userHasAccessToSecret(User $user, Secret $secret): bool
    {
        if ($secret->isOwnedBy($user)) {
            return true;
        }

        $share = $this->shareRepository->findByUserAndSecret($user, $secret);
        return $share !== null && $share->isActive();
    }

    public function getUserPermissionForSecret(User $user, Secret $secret): ?SharePermission
    {
        if ($secret->isOwnedBy($user)) {
            return SharePermission::SHARE; // Le propriétaire a tous les droits
        }

        $share = $this->shareRepository->findByUserAndSecret($user, $secret);
        if ($share === null || !$share->isActive()) {
            return null;
        }

        return $share->getPermission();
    }
}
