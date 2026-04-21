<?php
declare(strict_types=1);
namespace App\Organization\Application\DTO;

final readonly class MembershipOutput
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $email,
        public string $fullName,
        public string $role,
        public string $joinedAt,
    ) {}

    public static function fromEntity(\App\Organization\Domain\Entity\Membership $membership): self
    {
        return new self(
            id: $membership->getId()->toRfc4122(),
            userId: $membership->getUser()->getId()->toRfc4122(),
            email: $membership->getUser()->getEmail(),
            fullName: $membership->getUser()->getFullName(),
            role: $membership->getRole()->value,
            joinedAt: $membership->getJoinedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
