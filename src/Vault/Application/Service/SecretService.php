<?php
declare(strict_types=1);
namespace App\Vault\Application\Service;

use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Entity\Organization;
use App\Shared\Infrastructure\Crypto\EncryptionServiceInterface;
use App\Vault\Domain\Entity\Secret;
use App\Vault\Domain\Entity\SecretFolder;
use App\Vault\Domain\Enum\SecretType;
use App\Vault\Domain\Repository\SecretRepositoryInterface;

final class SecretService
{
    public function __construct(
        private readonly SecretRepositoryInterface $secretRepository,
        private readonly EncryptionServiceInterface $encryptionService,
    ) {}

    public function createSecret(
        Organization $organization,
        User $owner,
        string $title,
        SecretType $type,
        string $plaintextPayload,
        ?SecretFolder $folder = null,
    ): Secret {
        $encryptedPayload = $this->encryptionService->encryptToJson($plaintextPayload);

        $secret = new Secret(
            organization: $organization,
            owner: $owner,
            title: $title,
            type: $type,
            encryptedPayload: $encryptedPayload,
            folder: $folder,
        );

        $this->secretRepository->save($secret);
        return $secret;
    }

    public function updateSecret(Secret $secret, ?string $title = null, ?string $plaintextPayload = null): void
    {
        if ($title !== null) {
            $secret->setTitle($title);
        }

        if ($plaintextPayload !== null) {
            $encryptedPayload = $this->encryptionService->encryptToJson($plaintextPayload);
            $secret->setEncryptedPayload($encryptedPayload);
        }

        $this->secretRepository->save($secret);
    }

    public function decryptSecret(Secret $secret): string
    {
        return $this->encryptionService->decryptFromJson($secret->getEncryptedPayload());
    }

    public function deleteSecret(Secret $secret): void
    {
        $this->secretRepository->remove($secret);
    }
}
