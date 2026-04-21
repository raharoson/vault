<?php
declare(strict_types=1);
namespace App\Vault\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateSecretInput
{
    public function __construct(
        #[Assert\Length(min: 1, max: 255)]
        public readonly ?string $title = null,

        public readonly ?string $payload = null,
    ) {}
}
