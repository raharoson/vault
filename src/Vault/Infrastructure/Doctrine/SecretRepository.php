<?php
declare(strict_types=1);
namespace App\Vault\Infrastructure\Doctrine;

use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Entity\Organization;
use App\Vault\Domain\Entity\Secret;
use App\Vault\Domain\Repository\SecretRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class SecretRepository extends ServiceEntityRepository implements SecretRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Secret::class);
    }

    public function findById(Uuid $id): ?Secret
    {
        return $this->find($id);
    }

    public function findByOrganizationForUser(Organization $organization, User $user): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.shares', 'sh')
            ->where('s.organization = :org')
            ->andWhere('s.owner = :user OR sh.sharedWith = :user')
            ->setParameter('org', $organization)
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    public function save(Secret $secret): void
    {
        $this->getEntityManager()->persist($secret);
        $this->getEntityManager()->flush();
    }

    public function remove(Secret $secret): void
    {
        $this->getEntityManager()->remove($secret);
        $this->getEntityManager()->flush();
    }
}
