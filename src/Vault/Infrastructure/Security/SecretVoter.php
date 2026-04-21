<?php
declare(strict_types=1);
namespace App\Vault\Infrastructure\Security;

use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Repository\MembershipRepositoryInterface;
use App\Sharing\Application\Service\SecretSharingService;
use App\Sharing\Domain\Enum\SharePermission;
use App\Vault\Domain\Entity\Secret;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Secret>
 */
final class SecretVoter extends Voter
{
    public const VIEW = 'SECRET_VIEW';
    public const EDIT = 'SECRET_EDIT';
    public const DELETE = 'SECRET_DELETE';
    public const SHARE = 'SECRET_SHARE';

    public function __construct(
        private readonly SecretSharingService $sharingService,
        private readonly MembershipRepositoryInterface $membershipRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::SHARE], true)
            && $subject instanceof Secret;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Secret $secret */
        $secret = $subject;

        // L'utilisateur doit être membre de l'organisation du secret
        $membership = $this->membershipRepository->findByUserAndOrganization($user, $secret->getOrganization());
        if ($membership === null) {
            return false;
        }

        return match($attribute) {
            self::VIEW => $this->canView($user, $secret),
            self::EDIT => $this->canEdit($user, $secret),
            self::DELETE => $this->canDelete($user, $secret, $membership),
            self::SHARE => $this->canShare($user, $secret),
            default => false,
        };
    }

    private function canView(User $user, Secret $secret): bool
    {
        $permission = $this->sharingService->getUserPermissionForSecret($user, $secret);
        return $permission !== null;
    }

    private function canEdit(User $user, Secret $secret): bool
    {
        $permission = $this->sharingService->getUserPermissionForSecret($user, $secret);
        return $permission !== null && $permission->includes(SharePermission::EDIT);
    }

    private function canDelete(User $user, Secret $secret, \App\Organization\Domain\Entity\Membership $membership): bool
    {
        // Seul le propriétaire ou un admin/owner de l'org peut supprimer
        return $secret->isOwnedBy($user) || $membership->getRole()->canDeleteSecrets();
    }

    private function canShare(User $user, Secret $secret): bool
    {
        $permission = $this->sharingService->getUserPermissionForSecret($user, $secret);
        return $permission !== null && $permission->includes(SharePermission::SHARE);
    }
}
