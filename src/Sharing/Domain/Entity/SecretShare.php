<?php
declare(strict_types=1);
namespace App\Sharing\Domain\Entity;

use App\Identity\Domain\Entity\User;
use App\Sharing\Domain\Enum\SharePermission;
use App\Sharing\Infrastructure\Doctrine\SecretShareRepository;
use App\Vault\Domain\Entity\Secret;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SecretShareRepository::class)]
#[ORM\Table(name: 'secret_shares')]
#[ORM\UniqueConstraint(name: 'share_unique', columns: ['secret_id', 'shared_with_id'])]
class SecretShare
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Secret::class, inversedBy: 'shares')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Secret $secret;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $sharedWith;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $sharedBy;

    #[ORM\Column(type: 'string', enumType: SharePermission::class)]
    private SharePermission $permission;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    public function __construct(
        Secret $secret,
        User $sharedWith,
        User $sharedBy,
        SharePermission $permission,
        ?\DateTimeImmutable $expiresAt = null,
    ) {
        $this->id = Uuid::v7();
        $this->secret = $secret;
        $this->sharedWith = $sharedWith;
        $this->sharedBy = $sharedBy;
        $this->permission = $permission;
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $expiresAt;
    }

    public function getId(): Uuid { return $this->id; }
    public function getSecret(): Secret { return $this->secret; }
    public function getSharedWith(): User { return $this->sharedWith; }
    public function getSharedBy(): User { return $this->sharedBy; }
    public function getPermission(): SharePermission { return $this->permission; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }

    public function setPermission(SharePermission $permission): void { $this->permission = $permission; }
    public function isExpired(): bool { return $this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable(); }
    public function isActive(): bool { return !$this->isExpired(); }
}
