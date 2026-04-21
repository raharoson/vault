<?php
declare(strict_types=1);
namespace App\Organization\Application\DTO;

use App\Organization\Domain\Enum\MembershipRole;
use Symfony\Component\Validator\Constraints as Assert;

final class AddMemberInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email = '',

        #[Assert\NotNull]
        public readonly MembershipRole $role = MembershipRole::MEMBER,
    ) {}
}
