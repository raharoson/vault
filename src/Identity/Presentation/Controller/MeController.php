<?php
declare(strict_types=1);
namespace App\Identity\Presentation\Controller;

use App\Identity\Domain\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/me', name: 'api_me', methods: ['GET'])]
final class MeController extends AbstractController
{
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return new JsonResponse([
            'id' => $user->getId()->toRfc4122(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'status' => $user->getStatus()->value,
            'mfaEnabled' => $user->isMfaEnabled(),
        ]);
    }
}
