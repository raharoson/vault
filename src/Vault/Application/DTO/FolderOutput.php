<?php

declare(strict_types=1);

namespace App\Vault\Application\DTO;

class FolderOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $parentId,
        public string $organizationId,
        public ?string $createdAt,
    ) {
    }

    public static function fromEntity(\App\Vault\Domain\Entity\SecretFolder $folder): self
    {
        return new self(
            id: $folder->getId()->toRfc4122(),
            name: $folder->getName(),
            parentId: $folder->getParent()?->getId()->toRfc4122(),
            organizationId: $folder->getOrganization()->getId()->toRfc4122(),
            createdAt: $folder->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
