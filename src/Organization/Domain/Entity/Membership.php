<?php
declare(strict_types=1);
namespace App\Organization\Domain\Entity;

use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Enum\MembershipRole;
use App\Organization\Infrastructure\Doctrine\MembershipRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MembershipRepository::class)]
#[ORM\Table(name: 'memberships')]
#[ORM\UniqueConstraint(name: 'membership_unique', columns: ['user_id', 'organization_id'])]
#[ORM\HasLifecycleCallbacks]
class Membership
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Organization $organization;

    #[ORM\Column(type: 'string', enumType: MembershipRole::class)]
    private MembershipRole $role;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $joinedAt;

    public function __construct(User $user, Organization $organization, MembershipRole $role)
    {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->organization = $organization;
        $this->role = $role;
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getOrganization(): Organization { return $this->organization; }
    public function getRole(): MembershipRole { return $this->role; }
    public function getJoinedAt(): \DateTimeImmutable { return $this->joinedAt; }

    public function setRole(MembershipRole $role): void { $this->role = $role; }
    public function isOwner(): bool { return $this->role === MembershipRole::OWNER; }
    public function isAdmin(): bool { return $this->role === MembershipRole::ADMIN; }
}
