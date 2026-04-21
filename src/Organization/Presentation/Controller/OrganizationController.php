<?php
declare(strict_types=1);
namespace App\Organization\Presentation\Controller;

use App\Audit\Application\Service\AuditLogger;
use App\Audit\Domain\Enum\AuditAction;
use App\Audit\Domain\Enum\AuditTargetType;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Organization\Application\DTO\AddMemberInput;
use App\Organization\Application\DTO\CreateOrganizationInput;
use App\Organization\Application\DTO\MembershipOutput;
use App\Organization\Application\DTO\OrganizationOutput;
use App\Organization\Domain\Entity\Organization;
use App\Organization\Domain\Enum\MembershipRole;
use App\Organization\Domain\Repository\MembershipRepositoryInterface;
use App\Organization\Domain\Repository\OrganizationRepositoryInterface;
use App\Organization\Domain\Service\OrganizationMembershipService;
use App\Organization\Infrastructure\Security\OrganizationVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/organizations')]
final class OrganizationController extends AbstractController
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly MembershipRepositoryInterface $membershipRepository,
        private readonly OrganizationMembershipService $membershipService,
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuditLogger $auditLogger,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'api_organizations_list', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $organizations = $this->organizationRepository->findForUser($user);

        return new JsonResponse(
            array_map(fn(Organization $org) => OrganizationOutput::fromEntity($org), $organizations)
        );
    }

    #[Route('', name: 'api_organizations_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        /** @var CreateOrganizationInput $input */
        $input = $this->serializer->deserialize($request->getContent(), CreateOrganizationInput::class, 'json');
        $errors = $this->validator->validate($input);
        if (count($errors) > 0) {
            return $this->violationResponse($errors);
        }

        $slug = $this->generateSlug($input->name);
        if ($this->organizationRepository->slugExists($slug)) {
            $slug .= '-' . substr(uniqid('', true), -6);
        }

        $organization = new Organization($input->name, $slug, $input->description);
        $this->organizationRepository->save($organization);
        $this->membershipService->addMember($user, $organization, MembershipRole::OWNER);

        $this->auditLogger->log(
            action: AuditAction::MEMBER_ADDED,
            organization: $organization,
            actor: $user,
            targetType: AuditTargetType::ORGANIZATION,
            targetId: $organization->getId(),
        );

        return new JsonResponse(OrganizationOutput::fromEntity($organization), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_organizations_show', methods: ['GET'])]
    public function show(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $organization = $this->resolveOrganization($id);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $organization);

        return new JsonResponse(OrganizationOutput::fromEntity($organization));
    }

    #[Route('/{id}/members', name: 'api_organization_members_list', methods: ['GET'])]
    public function listMembers(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $organization = $this->resolveOrganization($id);
        $this->denyAccessUnlessGranted(OrganizationVoter::VIEW, $organization);

        $memberships = $this->membershipRepository->findByOrganization($organization);

        return new JsonResponse(
            array_map(
                fn(\App\Organization\Domain\Entity\Membership $m) => MembershipOutput::fromEntity($m),
                $memberships
            )
        );
    }

    #[Route('/{id}/members', name: 'api_organization_members_add', methods: ['POST'])]
    public function addMember(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $organization = $this->resolveOrganization($id);
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_MEMBERS, $organization);

        /** @var AddMemberInput $input */
        $input = $this->serializer->deserialize($request->getContent(), AddMemberInput::class, 'json');
        $errors = $this->validator->validate($input);
        if (count($errors) > 0) {
            return $this->violationResponse($errors);
        }

        $targetUser = $this->userRepository->findByEmail($input->email);
        if ($targetUser === null) {
            return new JsonResponse(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $membership = $this->membershipService->addMember($targetUser, $organization, $input->role);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        $this->auditLogger->log(
            action: AuditAction::MEMBER_ADDED,
            organization: $organization,
            actor: $user,
            targetType: AuditTargetType::USER,
            targetId: $targetUser->getId(),
            context: ['role' => $input->role->value],
        );

        return new JsonResponse(MembershipOutput::fromEntity($membership), Response::HTTP_CREATED);
    }

    #[Route('/{id}/members/{memberId}', name: 'api_organization_members_remove', methods: ['DELETE'])]
    public function removeMember(string $id, string $memberId, #[CurrentUser] User $user): JsonResponse
    {
        $organization = $this->resolveOrganization($id);
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_MEMBERS, $organization);

        if (!Uuid::isValid($memberId)) {
            throw $this->createNotFoundException('Membership not found.');
        }

        $membership = $this->membershipRepository->findById(Uuid::fromString($memberId));
        if ($membership === null || !$membership->getOrganization()->getId()->equals($organization->getId())) {
            throw $this->createNotFoundException('Membership not found.');
        }

        $targetUser = $membership->getUser();

        try {
            $this->membershipService->removeMember($targetUser, $organization);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->auditLogger->log(
            action: AuditAction::MEMBER_REMOVED,
            organization: $organization,
            actor: $user,
            targetType: AuditTargetType::USER,
            targetId: $targetUser->getId(),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function resolveOrganization(string $id): Organization
    {
        if (!Uuid::isValid($id)) {
            throw $this->createNotFoundException('Organization not found.');
        }
        $org = $this->organizationRepository->findById(Uuid::fromString($id));
        if ($org === null) {
            throw $this->createNotFoundException('Organization not found.');
        }
        return $org;
    }

    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
    }

    private function violationResponse(ConstraintViolationListInterface $errors): JsonResponse
    {
        $violations = [];
        foreach ($errors as $error) {
            $violations[$error->getPropertyPath()][] = $error->getMessage();
        }
        return new JsonResponse(['errors' => $violations], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
