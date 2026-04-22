<?php

declare(strict_types=1);

namespace App\Vault\Presentation\Controller;

use App\Identity\Domain\Entity\User;
use App\Shared\Infrastructure\OrganizationContext\OrganizationContext;
use App\Vault\Application\DTO\CreateFolderInput;
use App\Vault\Application\DTO\FolderOutput;
use App\Vault\Domain\Entity\SecretFolder;
use App\Vault\Domain\Repository\SecretFolderRepositoryInterface;
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


class FolderController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly SecretFolderRepositoryInterface $folderRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {}
    
    #[Route('/api/folders', name: 'api_folders_list', methods: ['GET'])]
    public function getFolder(#[CurrentUser] User $user): JsonResponse
    {
        $organization = $this->organizationContext->requireCurrent();
        $secretFolders = $this->folderRepository->findByOrganization($organization);

        return new JsonResponse(
            array_map(fn (SecretFolder $secretFolder) => FolderOutput::fromEntity($secretFolder), $secretFolders)
        );
    }

    #[Route('/api/folders', name: 'api_folders_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $organization = $this->organizationContext->requireCurrent();

        $input = $this->serializer->deserialize($request->getContent(), CreateFolderInput::class, 'json');
        $errors = $this->validator->validate($input);
        if (count($errors) > 0) {
            return $this->violationResponse($errors);
        }

        $folder = new SecretFolder(
            organization: $organization,
            name: $input->name
        );

        if ($input->parentId !== null && Uuid::isValid($input->parentId)) {
            $parentFolder = $this->folderRepository->findById(Uuid::fromString($input->parentId));

            if (!$parentFolder || !$parentFolder->getOrganization()->getId()->equals($organization->getId())) {
                return new JsonResponse(['error' => 'Invalid parent folder'], Response::HTTP_BAD_REQUEST);
            }

            $folder->setParent($parentFolder);
        }

        $this->folderRepository->save($folder);

        return new JsonResponse(FolderOutput::fromEntity($folder), Response::HTTP_CREATED);
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
