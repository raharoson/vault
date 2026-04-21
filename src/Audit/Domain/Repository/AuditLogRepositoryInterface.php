<?php
declare(strict_types=1);
namespace App\Audit\Domain\Repository;

use App\Audit\Domain\Entity\AuditLog;
use App\Organization\Domain\Entity\Organization;

interface AuditLogRepositoryInterface
{
    public function findByOrganization(Organization $organization, int $limit = 50, int $offset = 0): array;
    public function countByOrganization(Organization $organization): int;
    public function save(AuditLog $log): void;
}
