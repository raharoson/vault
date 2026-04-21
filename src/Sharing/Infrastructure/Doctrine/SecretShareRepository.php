<?php
declare(strict_types=1);
namespace App\Sharing\Infrastructure\Doctrine;

use App\Identity\Domain\Entity\User;
use App\Sharing\Domain\Entity\SecretShare;
use App\Sharing\Domain\Repository\SecretShareRepositoryInterface;
use App\Vault\Domain\Entity\Secret;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class SecretShareRepository extends ServiceEntityRepository implements SecretShareRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SecretShare::class);
    }

    public function findById(Uuid $id): ?SecretShare
    {
        return $this->find($id);
    }

    public function findBySecret(Secret $secret): array
    {
        return $this->findBy(['secret' => $secret]);
    }

    public function findByUserAndSecret(User $user, Secret $secret): ?SecretShare
    {
        return $this->findOneBy(['sharedWith' => $user, 'secret' => $secret]);
    }

    public function findActiveSharesForUser(User $user): array
    {
        return $this->createQueryBuilder('ss')
            ->where('ss.sharedWith = :user')
            ->andWhere('ss.expiresAt IS NULL OR ss.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function save(SecretShare $share): void
    {
        $this->getEntityManager()->persist($share);
        $this->getEntityManager()->flush();
    }

    public function remove(SecretShare $share): void
    {
        $this->getEntityManager()->remove($share);
        $this->getEntityManager()->flush();
    }
}
