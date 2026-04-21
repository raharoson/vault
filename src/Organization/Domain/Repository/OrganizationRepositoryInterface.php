<?php
declare(strict_types=1);
namespace App\Organization\Domain\Repository;

use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Entity\Organization;
use Symfony\Component\Uid\Uuid;

interface OrganizationRepositoryInterface
{
    public function findById(Uuid $id): ?Organization;
    public function findBySlug(string $slug): ?Organization;
    public function findForUser(User $user): array;
    public function save(Organization $organization): void;
    public function remove(Organization $organization): void;
    public function slugExists(string $slug): bool;
}
