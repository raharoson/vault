<?php
declare(strict_types=1);
namespace App\Sharing\Application\DTO;

use App\Sharing\Domain\Enum\SharePermission;
use Symfony\Component\Validator\Constraints as Assert;

final class ShareSecretInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email = '',

        #[Assert\NotNull]
        public readonly SharePermission $permission = SharePermission::READ,

        public readonly ?string $expiresAt = null,
    ) {}
}
