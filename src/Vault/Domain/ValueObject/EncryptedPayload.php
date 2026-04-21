<?php
declare(strict_types=1);
namespace App\Vault\Domain\ValueObject;

final class EncryptedPayload
{
    public function __construct(
        private readonly string $ciphertext,
        private readonly string $nonce,
        private readonly string $tag,
        private readonly string $algorithm,
        private readonly int $version,
    ) {}

    public function getCiphertext(): string { return $this->ciphertext; }
    public function getNonce(): string { return $this->nonce; }
    public function getTag(): string { return $this->tag; }
    public function getAlgorithm(): string { return $this->algorithm; }
    public function getVersion(): int { return $this->version; }

    public function toArray(): array
    {
        return [
            'ciphertext' => $this->ciphertext,
            'nonce' => $this->nonce,
            'tag' => $this->tag,
            'algorithm' => $this->algorithm,
            'version' => $this->version,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            ciphertext: $data['ciphertext'],
            nonce: $data['nonce'],
            tag: $data['tag'],
            algorithm: $data['algorithm'],
            version: $data['version'],
        );
    }
}
