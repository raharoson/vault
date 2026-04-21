<?php
declare(strict_types=1);
namespace App\Organization\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateOrganizationInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 255)]
        public readonly string $name = '',

        #[Assert\Length(max: 500)]
        public readonly ?string $description = null,
    ) {}
}
