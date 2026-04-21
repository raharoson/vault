<?php
declare(strict_types=1);
namespace App\Organization\Infrastructure\Doctrine;

use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class OrganizationRepository extends ServiceEntityRepository implements OrganizationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    public function findById(Uuid $id): ?Organization
    {
        return $this->find($id);
    }

    public function findBySlug(string $slug): ?Organization
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.memberships', 'm')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Organization $organization): void
    {
        $this->getEntityManager()->persist($organization);
        $this->getEntityManager()->flush();
    }

    public function remove(Organization $organization): void
    {
        $this->getEntityManager()->remove($organization);
        $this->getEntityManager()->flush();
    }

    public function slugExists(string $slug): bool
    {
        return (bool) $this->count(['slug' => $slug]);
    }
}
