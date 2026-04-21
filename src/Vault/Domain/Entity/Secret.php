<?php
declare(strict_types=1);
namespace App\Vault\Domain\Entity;

use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Entity\Organization;
use App\Sharing\Domain\Entity\SecretShare;
use App\Vault\Domain\Enum\SecretType;
use App\Vault\Infrastructure\Doctrine\SecretRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SecretRepository::class)]
#[ORM\Table(name: 'secrets')]
#[ORM\Index(columns: ['organization_id'], name: 'secret_org_idx')]
#[ORM\HasLifecycleCallbacks]
class Secret
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Organization $organization;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', enumType: SecretType::class)]
    private SecretType $type;

    /**
     * Serialized EncryptedPayload (JSON with ciphertext, nonce, tag, algorithm, version)
     */
    #[ORM\Column(type: 'text')]
    private string $encryptedPayload;

    #[ORM\ManyToOne(targetEntity: SecretFolder::class, inversedBy: 'secrets')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SecretFolder $folder = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: SecretShare::class, mappedBy: 'secret', cascade: ['persist', 'remove'])]
    private Collection $shares;

    public function __construct(
        Organization $organization,
        User $owner,
        string $title,
        SecretType $type,
        string $encryptedPayload,
        ?SecretFolder $folder = null,
    ) {
        $this->id = Uuid::v7();
        $this->organization = $organization;
        $this->owner = $owner;
        $this->title = $title;
        $this->type = $type;
        $this->encryptedPayload = $encryptedPayload;
        $this->folder = $folder;
        $this->createdAt = new \DateTimeImmutable();
        $this->shares = new ArrayCollection();
    }

    public function getId(): Uuid { return $this->id; }
    public function getOrganization(): Organization { return $this->organization; }
    public function getOwner(): User { return $this->owner; }
    public function getTitle(): string { return $this->title; }
    public function getType(): SecretType { return $this->type; }
    public function getEncryptedPayload(): string { return $this->encryptedPayload; }
    public function getFolder(): ?SecretFolder { return $this->folder; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function getShares(): Collection { return $this->shares; }

    public function setTitle(string $title): void { $this->title = $title; }
    public function setEncryptedPayload(string $encryptedPayload): void { $this->encryptedPayload = $encryptedPayload; }
    public function setFolder(?SecretFolder $folder): void { $this->folder = $folder; }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner->getId()->equals($user->getId());
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedAt = new \DateTimeImmutable(); }
}
