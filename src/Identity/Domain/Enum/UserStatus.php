<?php
declare(strict_types=1);
namespace App\Identity\Domain\Enum;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}
