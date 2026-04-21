<?php
declare(strict_types=1);
namespace App\Shared\Infrastructure\Crypto;

use App\Vault\Domain\ValueObject\EncryptedPayload;

interface EncryptionServiceInterface
{
    public function encrypt(string $plaintext): EncryptedPayload;
    public function decrypt(EncryptedPayload $payload): string;
    public function encryptToJson(string $plaintext): string;
    public function decryptFromJson(string $json): string;
}
