<?php
declare(strict_types=1);
namespace App\Organization\Domain\Repository;

use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Entity\Membership;
use App\Organization\Domain\Entity\Organization;
use Symfony\Component\Uid\Uuid;

interface MembershipRepositoryInterface
{
    public function findById(Uuid $id): ?Membership;
    public function findByUserAndOrganization(User $user, Organization $organization): ?Membership;
    public function findByOrganization(Organization $organization): array;
    public function save(Membership $membership): void;
    public function remove(Membership $membership): void;
}
