<?php
declare(strict_types=1);
namespace App\Audit\Domain\Enum;

enum AuditAction: string
{
    case LOGIN_SUCCESS = 'login_success';
    case LOGIN_FAILURE = 'login_failure';
    case SECRET_CREATED = 'secret_created';
    case SECRET_READ = 'secret_read';
    case SECRET_UPDATED = 'secret_updated';
    case SECRET_DELETED = 'secret_deleted';
    case SECRET_SHARED = 'secret_shared';
    case SHARE_REVOKED = 'share_revoked';
    case MEMBER_ADDED = 'member_added';
    case MEMBER_REMOVED = 'member_removed';
}
