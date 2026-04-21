<?php
declare(strict_types=1);
namespace App\Shared\Infrastructure\Crypto;

use App\Vault\Domain\ValueObject\EncryptedPayload;

final class AesGcmEncryptionService implements EncryptionServiceInterface
{
    private const ALGORITHM = 'aes-256-gcm';
    private const VERSION = 1;
    private const NONCE_LENGTH = 12; // 96 bits for GCM
    private const TAG_LENGTH = 16;   // 128 bits

    private readonly string $key;

    public function __construct(string $encryptionKey)
    {
        // La clé est stockée en hex (64 chars = 32 bytes)
        $decoded = hex2bin($encryptionKey);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new \InvalidArgumentException('Encryption key must be a 64-character hex string (32 bytes).');
        }
        $this->key = $decoded;
    }

    public function encrypt(string $plaintext): EncryptedPayload
    {
        $nonce = random_bytes(self::NONCE_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::ALGORITHM,
            $this->key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_LENGTH,
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed: ' . openssl_error_string());
        }

        return new EncryptedPayload(
            ciphertext: base64_encode($ciphertext),
            nonce: base64_encode($nonce),
            tag: base64_encode($tag),
            algorithm: self::ALGORITHM,
            version: self::VERSION,
        );
    }

    public function decrypt(EncryptedPayload $payload): string
    {
        if ($payload->getAlgorithm() !== self::ALGORITHM) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported algorithm "%s". Expected "%s".',
                $payload->getAlgorithm(),
                self::ALGORITHM,
            ));
        }

        $plaintext = openssl_decrypt(
            base64_decode($payload->getCiphertext()),
            self::ALGORITHM,
            $this->key,
            OPENSSL_RAW_DATA,
            base64_decode($payload->getNonce()),
            base64_decode($payload->getTag()),
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed. Data may be corrupted or tampered with.');
        }

        return $plaintext;
    }

    public function decryptFromJson(string $json): string
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid encrypted payload JSON.');
        }
        return $this->decrypt(EncryptedPayload::fromArray($data));
    }

    public function encryptToJson(string $plaintext): string
    {
        return json_encode($this->encrypt($plaintext)->toArray(), JSON_THROW_ON_ERROR);
    }
}
