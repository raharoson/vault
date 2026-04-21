<?php
declare(strict_types=1);
namespace App\Audit\Application\Service;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Enum\AuditAction;
use App\Audit\Domain\Enum\AuditTargetType;
use App\Audit\Domain\Repository\AuditLogRepositoryInterface;
use App\Identity\Domain\Entity\User;
use App\Organization\Domain\Entity\Organization;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

final class AuditLogger
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogRepository,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $auditLogger,
    ) {}

    public function log(
        AuditAction $action,
        ?Organization $organization = null,
        ?User $actor = null,
        ?AuditTargetType $targetType = null,
        ?Uuid $targetId = null,
        ?array $context = null,
    ): void {
        $ipAddress = $this->requestStack->getCurrentRequest()?->getClientIp();

        $log = new AuditLog(
            action: $action,
            organization: $organization,
            actor: $actor,
            targetType: $targetType,
            targetId: $targetId,
            context: $context,
            ipAddress: $ipAddress,
        );

        try {
            $this->auditLogRepository->save($log);
        } catch (\Throwable $e) {
            // Ne jamais lever d'exception depuis le logger — loguer en fallback
            $this->auditLogger->error('Failed to persist audit log', [
                'action' => $action->value,
                'error' => $e->getMessage(),
            ]);
        }

        $this->auditLogger->info('Audit event', [
            'action' => $action->value,
            'organization' => $organization?->getId()?->toRfc4122(),
            'actor' => $actor?->getId()?->toRfc4122(),
            'target_type' => $targetType?->value,
            'target_id' => $targetId?->toRfc4122(),
        ]);
    }
}
