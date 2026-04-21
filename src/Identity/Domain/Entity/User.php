<?php
declare(strict_types=1);
namespace App\Identity\Domain\Entity;

use App\Identity\Domain\Enum\UserStatus;
use App\Identity\Infrastructure\Doctrine\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', length: 100)]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100)]
    private string $lastName;

    #[ORM\Column(type: 'string', enumType: UserStatus::class)]
    private UserStatus $status;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $mfaEnabled = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: \App\Organization\Domain\Entity\Membership::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $memberships;

    #[ORM\OneToMany(targetEntity: RefreshToken::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $refreshTokens;

    public function __construct(
        string $email,
        string $firstName,
        string $lastName,
        UserStatus $status = UserStatus::ACTIVE,
    ) {
        $this->id = Uuid::v7();
        $this->email = strtolower(trim($email));
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->status = $status;
        $this->createdAt = new \DateTimeImmutable();
        $this->memberships = new ArrayCollection();
        $this->refreshTokens = new ArrayCollection();
    }

    public function getId(): Uuid { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getFullName(): string { return $this->firstName . ' ' . $this->lastName; }
    public function getStatus(): UserStatus { return $this->status; }
    public function isMfaEnabled(): bool { return $this->mfaEnabled; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function getMemberships(): Collection { return $this->memberships; }

    public function setEmail(string $email): void { $this->email = strtolower(trim($email)); }
    public function setFirstName(string $firstName): void { $this->firstName = $firstName; }
    public function setLastName(string $lastName): void { $this->lastName = $lastName; }
    public function activate(): void { $this->status = UserStatus::ACTIVE; }
    public function deactivate(): void { $this->status = UserStatus::INACTIVE; }

    public function getUserIdentifier(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $hashedPassword): void { $this->password = $hashedPassword; }
    public function getRoles(): array { return ['ROLE_USER']; }
    public function eraseCredentials(): void {}
    public function isActive(): bool { return $this->status === UserStatus::ACTIVE; }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedAt = new \DateTimeImmutable(); }
}
