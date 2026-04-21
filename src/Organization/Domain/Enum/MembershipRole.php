<?php
declare(strict_types=1);
namespace App\Organization\Domain\Enum;

enum MembershipRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';
    case VIEWER = 'viewer';

    public function canManageMembers(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN], true);
    }

    public function canWriteSecrets(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN, self::MEMBER], true);
    }

    public function canDeleteSecrets(): bool
    {
        return in_array($this, [self::OWNER, self::ADMIN], true);
    }
}
