<?php
declare(strict_types=1);
namespace App\Sharing\Domain\Enum;

enum SharePermission: string
{
    case READ = 'read';
    case EDIT = 'edit';
    case SHARE = 'share';

    public function includes(self $permission): bool
    {
        return match($this) {
            self::READ => $permission === self::READ,
            self::EDIT => in_array($permission, [self::READ, self::EDIT], true),
            self::SHARE => true,
        };
    }
}
