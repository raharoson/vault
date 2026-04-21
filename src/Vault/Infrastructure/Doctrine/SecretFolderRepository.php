<?php
declare(strict_types=1);
namespace App\Vault\Infrastructure\Doctrine;

use App\Organization\Domain\Entity\Organization;
use App\Vault\Domain\Entity\SecretFolder;
use App\Vault\Domain\Repository\SecretFolderRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class SecretFolderRepository extends ServiceEntityRepository implements SecretFolderRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SecretFolder::class);
    }

    public function findById(Uuid $id): ?SecretFolder
    {
        return $this->find($id);
    }

    public function findByOrganization(Organization $organization): array
    {
        return $this->findBy(['organization' => $organization], ['name' => 'ASC']);
    }

    public function save(SecretFolder $folder): void
    {
        $this->getEntityManager()->persist($folder);
        $this->getEntityManager()->flush();
    }

    public function remove(SecretFolder $folder): void
    {
        $this->getEntityManager()->remove($folder);
        $this->getEntityManager()->flush();
    }
}
