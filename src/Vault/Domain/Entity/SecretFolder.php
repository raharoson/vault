<?php
declare(strict_types=1);
namespace App\Vault\Domain\Entity;

use App\Organization\Domain\Entity\Organization;
use App\Vault\Infrastructure\Doctrine\SecretFolderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SecretFolderRepository::class)]
#[ORM\Table(name: 'secret_folders')]
#[ORM\HasLifecycleCallbacks]
class SecretFolder
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Organization $organization;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SecretFolder $parent = null;

    #[ORM\OneToMany(targetEntity: Secret::class, mappedBy: 'folder')]
    private Collection $secrets;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Organization $organization, string $name, ?SecretFolder $parent = null)
    {
        $this->id = Uuid::v7();
        $this->organization = $organization;
        $this->name = $name;
        $this->parent = $parent;
        $this->createdAt = new \DateTimeImmutable();
        $this->secrets = new ArrayCollection();
    }

    public function getId(): Uuid { return $this->id; }
    public function getOrganization(): Organization { return $this->organization; }
    public function getName(): string { return $this->name; }
    public function getParent(): ?SecretFolder { return $this->parent; }
    public function getSecrets(): Collection { return $this->secrets; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setName(string $name): void { $this->name = $name; }
}
