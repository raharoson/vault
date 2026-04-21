<?php
declare(strict_types=1);
namespace App\Identity\Presentation\Controller;

use App\Audit\Application\Service\AuditLogger;
use App\Audit\Domain\Enum\AuditAction;
use App\Identity\Application\Service\AuthenticationService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/refresh', methods: ['POST'])]
final class RefreshTokenController extends AbstractController
{
    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $rawToken = $data['refresh_token'] ?? null;

        if (!is_string($rawToken) || $rawToken === '') {
            return new JsonResponse(['error' => 'refresh_token is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->authenticationService->refreshAccessToken($rawToken);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        $accessToken = $this->jwtManager->create($user);
        $newRefreshToken = $this->authenticationService->createRefreshToken(
            $user,
            $request->getClientIp(),
        );

        return new JsonResponse([
            'token' => $accessToken,
            'refresh_token' => $newRefreshToken,
        ]);
    }
}
