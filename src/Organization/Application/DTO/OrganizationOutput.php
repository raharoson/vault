<?php
declare(strict_types=1);
namespace App\Organization\Application\DTO;

final readonly class OrganizationOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $createdAt,
    ) {}

    public static function fromEntity(\App\Organization\Domain\Entity\Organization $org): self
    {
        return new self(
            id: $org->getId()->toRfc4122(),
            name: $org->getName(),
            slug: $org->getSlug(),
            description: $org->getDescription(),
            createdAt: $org->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
