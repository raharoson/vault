<?php
declare(strict_types=1);
namespace App\Identity\Infrastructure\Security;

use App\Audit\Application\Service\AuditLogger;
use App\Audit\Domain\Enum\AuditAction;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class JwtEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthSuccess',
            Events::AUTHENTICATION_FAILURE => 'onAuthFailure',
        ];
    }

    public function onAuthSuccess(AuthenticationSuccessEvent $event): void
    {
        $userIdentifier = $event->getUser();
        if (!$userIdentifier instanceof User) {
            return;
        }

        $this->auditLogger->log(
            action: AuditAction::LOGIN_SUCCESS,
            actor: $userIdentifier,
            context: ['email' => $userIdentifier->getEmail()],
        );
    }

    public function onAuthFailure(AuthenticationFailureEvent $event): void
    {
        $this->auditLogger->log(
            action: AuditAction::LOGIN_FAILURE,
            context: ['error' => $event->getException()->getMessage()],
        );
    }
}
