<?php
declare(strict_types=1);
namespace App\Vault\Domain\Repository;

use App\Organization\Domain\Entity\Organization;
use App\Vault\Domain\Entity\SecretFolder;
use Symfony\Component\Uid\Uuid;

interface SecretFolderRepositoryInterface
{
    public function findById(Uuid $id): ?SecretFolder;
    public function findByOrganization(Organization $organization): array;
    public function save(SecretFolder $folder): void;
    public function remove(SecretFolder $folder): void;
}
