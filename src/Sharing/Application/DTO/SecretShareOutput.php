<?php
declare(strict_types=1);
namespace App\Sharing\Application\DTO;

final readonly class SecretShareOutput
{
    public function __construct(
        public string $id,
        public string $secretId,
        public string $sharedWithId,
        public string $sharedWithEmail,
        public string $sharedById,
        public string $permission,
        public string $createdAt,
        public ?string $expiresAt,
    ) {}

    public static function fromEntity(\App\Sharing\Domain\Entity\SecretShare $share): self
    {
        return new self(
            id: $share->getId()->toRfc4122(),
            secretId: $share->getSecret()->getId()->toRfc4122(),
            sharedWithId: $share->getSharedWith()->getId()->toRfc4122(),
            sharedWithEmail: $share->getSharedWith()->getEmail(),
            sharedById: $share->getSharedBy()->getId()->toRfc4122(),
            permission: $share->getPermission()->value,
            createdAt: $share->getCreatedAt()->format(\DateTimeInterface::ATOM),
            expiresAt: $share->getExpiresAt()?->format(\DateTimeInterface::ATOM),
        );
    }
}
