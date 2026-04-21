<?php
declare(strict_types=1);
namespace App\Vault\Application\DTO;

final readonly class SecretOutput
{
    public function __construct(
        public string $id,
        public string $title,
        public string $type,
        public string $ownerId,
        public ?string $folderId,
        public string $createdAt,
        public ?string $updatedAt,
        public ?string $decryptedPayload = null,
    ) {}

    public static function fromEntity(\App\Vault\Domain\Entity\Secret $secret, ?string $decryptedPayload = null): self
    {
        return new self(
            id: $secret->getId()->toRfc4122(),
            title: $secret->getTitle(),
            type: $secret->getType()->value,
            ownerId: $secret->getOwner()->getId()->toRfc4122(),
            folderId: $secret->getFolder()?->getId()->toRfc4122(),
            createdAt: $secret->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $secret->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            decryptedPayload: $decryptedPayload,
        );
    }
}
