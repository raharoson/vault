<?php
declare(strict_types=1);
namespace App\Organization\Domain\Service;

use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Entity\Membership;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Enum\MembershipRole;
use App\Organization\Domain\Repository\MembershipRepositoryInterface;

final class OrganizationMembershipService
{
    public function __construct(
        private readonly MembershipRepositoryInterface $membershipRepository,
    ) {}

    public function getMembership(User $user, Organization $organization): ?Membership
    {
        return $this->membershipRepository->findByUserAndOrganization($user, $organization);
    }

    public function isMember(User $user, Organization $organization): bool
    {
        return $this->getMembership($user, $organization) !== null;
    }

    public function hasRole(User $user, Organization $organization, MembershipRole $role): bool
    {
        $membership = $this->getMembership($user, $organization);
        return $membership !== null && $membership->getRole() === $role;
    }

    public function hasMinimumRole(User $user, Organization $organization, MembershipRole $minimumRole): bool
    {
        $membership = $this->getMembership($user, $organization);
        if ($membership === null) {
            return false;
        }
        $hierarchy = [
            MembershipRole::VIEWER->value => 0,
            MembershipRole::MEMBER->value => 1,
            MembershipRole::ADMIN->value => 2,
            MembershipRole::OWNER->value => 3,
        ];
        return ($hierarchy[$membership->getRole()->value] ?? -1) >= ($hierarchy[$minimumRole->value] ?? 0);
    }

    public function addMember(User $user, Organization $organization, MembershipRole $role): Membership
    {
        $existing = $this->getMembership($user, $organization);
        if ($existing !== null) {
            throw new \DomainException(sprintf(
                'User "%s" is already a member of organization "%s".',
                $user->getEmail(),
                $organization->getName(),
            ));
        }

        $membership = new Membership($user, $organization, $role);
        $this->membershipRepository->save($membership);
        return $membership;
    }
}
