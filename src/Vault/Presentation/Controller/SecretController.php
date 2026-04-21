<?php
declare(strict_types=1);
namespace App\Vault\Presentation\Controller;

use App\Audit\Application\Service\AuditLogger;
use App\Audit\Domain\Enum\AuditAction;
use App\Audit\Domain\Enum\AuditTargetType;
use App\Identity\Domain\Entity\User;
use App\Shared\Infrastructure\OrganizationContext\OrganizationContext;
use App\Vault\Application\DTO\CreateSecretInput;
use App\Vault\Application\DTO\SecretOutput;
use App\Vault\Application\DTO\UpdateSecretInput;
use App\Vault\Application\Service\SecretService;
use App\Vault\Domain\Entity\Secret;
use App\Vault\Domain\Repository\SecretFolderRepositoryInterface;
use App\Vault\Domain\Repository\SecretRepositoryInterface;
use App\Vault\Infrastructure\Security\SecretVoter;
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

#[Route('/api/secrets')]
final class SecretController extends AbstractController
{
    public function __construct(
        private readonly SecretRepositoryInterface $secretRepository,
        private readonly SecretFolderRepositoryInterface $folderRepository,
        private readonly SecretService $secretService,
        private readonly OrganizationContext $organizationContext,
        private readonly AuditLogger $auditLogger,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'api_secrets_list', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $organization = $this->organizationContext->requireCurrent();
        $secrets = $this->secretRepository->findByOrganizationForUser($organization, $user);

        return new JsonResponse(
            array_map(fn(Secret $s) => SecretOutput::fromEntity($s), $secrets)
        );
    }

    #[Route('', name: 'api_secrets_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $organization = $this->organizationContext->requireCurrent();

        /** @var CreateSecretInput $input */
        $input = $this->serializer->deserialize($request->getContent(), CreateSecretInput::class, 'json');
        $errors = $this->validator->validate($input);
        if (count($errors) > 0) {
            return $this->violationResponse($errors);
        }

        $folder = null;
        if ($input->folderId !== null && Uuid::isValid($input->folderId)) {
            $folder = $this->folderRepository->findById(Uuid::fromString($input->folderId));
        }

        $secret = $this->secretService->createSecret(
            organization: $organization,
            owner: $user,
            title: $input->title,
            type: $input->type,
            plaintextPayload: $input->payload,
            folder: $folder,
        );

        $this->auditLogger->log(
            action: AuditAction::SECRET_CREATED,
            organization: $organization,
            actor: $user,
            targetType: AuditTargetType::SECRET,
            targetId: $secret->getId(),
            context: ['title' => $secret->getTitle(), 'type' => $secret->getType()->value],
        );

        return new JsonResponse(SecretOutput::fromEntity($secret), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_secrets_show', methods: ['GET'])]
    public function show(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $secret = $this->resolveSecret($id);
        $this->denyAccessUnlessGranted(SecretVoter::VIEW, $secret);

        $decryptedPayload = $this->secretService->decryptSecret($secret);

        $this->auditLogger->log(
            action: AuditAction::SECRET_READ,
            organization: $secret->getOrganization(),
            actor: $user,
            targetType: AuditTargetType::SECRET,
            targetId: $secret->getId(),
        );

        return new JsonResponse(SecretOutput::fromEntity($secret, $decryptedPayload));
    }

    #[Route('/{id}', name: 'api_secrets_update', methods: ['PATCH'])]
    public function update(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $secret = $this->resolveSecret($id);
        $this->denyAccessUnlessGranted(SecretVoter::EDIT, $secret);

        /** @var UpdateSecretInput $input */
        $input = $this->serializer->deserialize($request->getContent(), UpdateSecretInput::class, 'json');
        $errors = $this->validator->validate($input);
        if (count($errors) > 0) {
            return $this->violationResponse($errors);
        }

        $this->secretService->updateSecret($secret, $input->title, $input->payload);

        $this->auditLogger->log(
            action: AuditAction::SECRET_UPDATED,
            organization: $secret->getOrganization(),
            actor: $user,
            targetType: AuditTargetType::SECRET,
            targetId: $secret->getId(),
        );

        return new JsonResponse(SecretOutput::fromEntity($secret));
    }

    #[Route('/{id}', name: 'api_secrets_delete', methods: ['DELETE'])]
    public function delete(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $secret = $this->resolveSecret($id);
        $this->denyAccessUnlessGranted(SecretVoter::DELETE, $secret);

        $secretId = $secret->getId();
        $organization = $secret->getOrganization();

        $this->secretService->deleteSecret($secret);

        $this->auditLogger->log(
            action: AuditAction::SECRET_DELETED,
            organization: $organization,
            actor: $user,
            targetType: AuditTargetType::SECRET,
            targetId: $secretId,
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function resolveSecret(string $id): Secret
    {
        if (!Uuid::isValid($id)) {
            throw $this->createNotFoundException('Secret not found.');
        }
        $secret = $this->secretRepository->findById(Uuid::fromString($id));
        if ($secret === null) {
            throw $this->createNotFoundException('Secret not found.');
        }
        return $secret;
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
