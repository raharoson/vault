<?php
declare(strict_types=1);
namespace App\Vault\Domain\Enum;

enum SecretType: string
{
    case PASSWORD = 'password';
    case SECURE_NOTE = 'secure_note';
    case API_KEY = 'api_key';
}
