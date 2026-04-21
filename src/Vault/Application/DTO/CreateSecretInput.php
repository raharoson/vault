<?php
declare(strict_types=1);
namespace App\Vault\Application\DTO;

use App\Vault\Domain\Enum\SecretType;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateSecretInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 255)]
        public readonly string $title = '',

        #[Assert\NotNull]
        public readonly SecretType $type = SecretType::PASSWORD,

        #[Assert\NotBlank]
        public readonly string $payload = '',

        public readonly ?string $folderId = null,
    ) {}
}
