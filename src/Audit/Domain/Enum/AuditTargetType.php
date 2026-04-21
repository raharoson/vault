<?php
declare(strict_types=1);
namespace App\Audit\Domain\Enum;

enum AuditTargetType: string
{
    case SECRET = 'secret';
    case ORGANIZATION = 'organization';
    case USER = 'user';
    case SHARE = 'share';
}
