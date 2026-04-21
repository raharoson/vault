<?php
declare(strict_types=1);
namespace App\Shared\Infrastructure\OrganizationContext;

use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

final class OrganizationContext
{
    private ?Organization $current = null;
    private bool $resolved = false;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly OrganizationRepositoryInterface $organizationRepository,
    ) {}

    public function getCurrent(): ?Organization
    {
        if ($this->resolved) {
            return $this->current;
        }

        $this->resolved = true;
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return null;
        }

        $orgId = $request->headers->get('X-Organization-Id');

        if ($orgId === null || $orgId === '') {
            return null;
        }

        if (!Uuid::isValid($orgId)) {
            return null;
        }

        $this->current = $this->organizationRepository->findById(Uuid::fromString($orgId));
        return $this->current;
    }

    public function requireCurrent(): Organization
    {
        $org = $this->getCurrent();
        if ($org === null) {
            throw new \RuntimeException('No valid organization context found. Provide a valid X-Organization-Id header.');
        }
        return $org;
    }
}
