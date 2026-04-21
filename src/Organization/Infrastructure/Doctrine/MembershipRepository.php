<?php
declare(strict_types=1);
namespace App\Organization\Infrastructure\Doctrine;

use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Entity\Membership;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Repository\MembershipRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class MembershipRepository extends ServiceEntityRepository implements MembershipRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membership::class);
    }

    public function findById(Uuid $id): ?Membership
    {
        return $this->find($id);
    }

    public function findByUserAndOrganization(User $user, Organization $organization): ?Membership
    {
        return $this->findOneBy(['user' => $user, 'organization' => $organization]);
    }

    public function findByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.user', 'u')
            ->where('m.organization = :org')
            ->setParameter('org', $organization)
            ->orderBy('m.joinedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Membership $membership): void
    {
        $this->getEntityManager()->persist($membership);
        $this->getEntityManager()->flush();
    }

    public function remove(Membership $membership): void
    {
        $this->getEntityManager()->remove($membership);
        $this->getEntityManager()->flush();
    }
}
