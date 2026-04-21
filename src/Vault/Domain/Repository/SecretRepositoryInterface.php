<?php
declare(strict_types=1);
namespace App\Vault\Domain\Repository;

use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Entity\Organization;
use App\Vault\Domain\Entity\Secret;
use Symfony\Component\Uid\Uuid;

interface SecretRepositoryInterface
{
    public function findById(Uuid $id): ?Secret;
    public function findByOrganizationForUser(Organization $organization, User $user): array;
    public function save(Secret $secret): void;
    public function remove(Secret $secret): void;
}
