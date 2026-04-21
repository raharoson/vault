<?php
declare(strict_types=1);
namespace App\Sharing\Domain\Repository;

use App\Identity\Domain\Entity\User;
use App\Sharing\Domain\Entity\SecretShare;
use App\Vault\Domain\Entity\Secret;
use Symfony\Component\Uid\Uuid;

interface SecretShareRepositoryInterface
{
    public function findById(Uuid $id): ?SecretShare;
    public function findBySecret(Secret $secret): array;
    public function findByUserAndSecret(User $user, Secret $secret): ?SecretShare;
    public function findActiveSharesForUser(User $user): array;
    public function save(SecretShare $share): void;
    public function remove(SecretShare $share): void;
}
