<?php
declare(strict_types=1);
namespace App\Audit\Application\DTO;

final readonly class AuditLogOutput
{
    public function __construct(
        public string $id,
        public string $action,
        public ?string $actorId,
        public ?string $actorEmail,
        public ?string $targetType,
        public ?string $targetId,
        public ?array $context,
        public ?string $ipAddress,
        public string $createdAt,
    ) {}

    public static function fromEntity(\App\Audit\Domain\Entity\AuditLog $log): self
    {
        return new self(
            id: $log->getId()->toRfc4122(),
            action: $log->getAction()->value,
            actorId: $log->getActor()?->getId()->toRfc4122(),
            actorEmail: $log->getActor()?->getEmail(),
            targetType: $log->getTargetType()?->value,
            targetId: $log->getTargetId()?->toRfc4122(),
            context: $log->getContext(),
            ipAddress: $log->getIpAddress(),
            createdAt: $log->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
