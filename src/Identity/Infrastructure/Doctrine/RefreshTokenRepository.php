<?php
declare(strict_types=1);
namespace App\Identity\Infrastructure\Doctrine;

use App\Identity\Domain\Entity\RefreshToken;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\RefreshTokenRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RefreshTokenRepository extends ServiceEntityRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findByTokenHash(string $tokenHash): ?RefreshToken
    {
        return $this->findOneBy(['tokenHash' => $tokenHash, 'revoked' => false]);
    }

    public function findValidTokensByUser(User $user): array
    {
        return $this->createQueryBuilder('rt')
            ->where('rt.user = :user')
            ->andWhere('rt.revoked = false')
            ->andWhere('rt.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function save(RefreshToken $token): void
    {
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
    }

    public function revokeAllForUser(User $user): void
    {
        $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.revoked', true)
            ->where('rt.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function removeExpired(): int
    {
        return $this->createQueryBuilder('rt')
            ->delete()
            ->where('rt.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
