<?php

declare(strict_types=1);

namespace App\Vault\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateFolderInput
{
    public function __construct(
        #[Assert\NotBlank] 
        #[Assert\Length(min: 1, max: 255)]
        public string $name,
        #[Assert\Uuid]
        public ?string $parentId
    ) {
    }
}
