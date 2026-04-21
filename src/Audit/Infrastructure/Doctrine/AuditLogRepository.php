<?php
declare(strict_types=1);
namespace App\Audit\Infrastructure\Doctrine;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Organization\Domain\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuditLogRepository extends ServiceEntityRepository implements AuditLogRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function findByOrganization(Organization $organization, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('al')
            ->leftJoin('al.actor', 'u')
            ->where('al.organization = :org')
            ->setParameter('org', $organization)
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countByOrganization(Organization $organization): int
    {
        return (int) $this->createQueryBuilder('al')
            ->select('COUNT(al.id)')
            ->where('al.organization = :org')
            ->setParameter('org', $organization)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(AuditLog $log): void
    {
        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();
    }
}
