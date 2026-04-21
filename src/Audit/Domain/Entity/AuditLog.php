<?php
declare(strict_types=1);
namespace App\Audit\Domain\Entity;

use App\Audit\Domain\Enum\AuditAction;
use App\Audit\Domain\Enum\AuditTargetType;
use App\Audit\Infrastructure\Doctrine\AuditLogRepository;
use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Entity\Organization;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(columns: ['organization_id', 'created_at'], name: 'audit_org_date_idx')]
#[ORM\Index(columns: ['actor_id'], name: 'audit_actor_idx')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Organization $organization;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $actor;

    #[ORM\Column(type: 'string', enumType: AuditAction::class)]
    private AuditAction $action;

    #[ORM\Column(type: 'string', enumType: AuditTargetType::class, nullable: true)]
    private ?AuditTargetType $targetType = null;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?Uuid $targetId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $context = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        AuditAction $action,
        ?Organization $organization = null,
        ?User $actor = null,
        ?AuditTargetType $targetType = null,
        ?Uuid $targetId = null,
        ?array $context = null,
        ?string $ipAddress = null,
    ) {
        $this->id = Uuid::v7();
        $this->action = $action;
        $this->organization = $organization;
        $this->actor = $actor;
        $this->targetType = $targetType;
        $this->targetId = $targetId;
        $this->context = $context;
        $this->ipAddress = $ipAddress;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid { return $this->id; }
    public function getOrganization(): ?Organization { return $this->organization; }
    public function getActor(): ?User { return $this->actor; }
    public function getAction(): AuditAction { return $this->action; }
    public function getTargetType(): ?AuditTargetType { return $this->targetType; }
    public function getTargetId(): ?Uuid { return $this->targetId; }
    public function getContext(): ?array { return $this->context; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
