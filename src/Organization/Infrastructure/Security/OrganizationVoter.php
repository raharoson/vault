<?php
declare(strict_types=1);
namespace App\Organization\Infrastructure\Security;

use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Enum\MembershipRole;
use App\Organization\Domain\Service\OrganizationMembershipService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Organization>
 */
final class OrganizationVoter extends Voter
{
    public const VIEW = 'ORG_VIEW';
    public const MANAGE_MEMBERS = 'ORG_MANAGE_MEMBERS';
    public const ADMIN = 'ORG_ADMIN';

    public function __construct(
        private readonly OrganizationMembershipService $membershipService,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::MANAGE_MEMBERS, self::ADMIN], true)
            && $subject instanceof Organization;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Organization $organization */
        $organization = $subject;

        return match($attribute) {
            self::VIEW => $this->membershipService->isMember($user, $organization),
            self::MANAGE_MEMBERS => $this->membershipService->hasMinimumRole($user, $organization, MembershipRole::ADMIN),
            self::ADMIN => $this->membershipService->hasMinimumRole($user, $organization, MembershipRole::ADMIN),
            default => false,
        };
    }
}
