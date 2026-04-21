<?php
declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $schemas = $openApi->getComponents()->getSchemas() ?? new \ArrayObject();

        $schemas['LoginRequest'] = new \ArrayObject([
            'type' => 'object', 'required' => ['email', 'password'],
            'properties' => [
                'email'    => ['type' => 'string', 'format' => 'email', 'example' => 'alice@example.com'],
                'password' => ['type' => 'string', 'format' => 'password', 'example' => 'password'],
            ],
        ]);
        $schemas['LoginResponse'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'token'         => ['type' => 'string'],
                'refresh_token' => ['type' => 'string'],
            ],
        ]);
        $schemas['RefreshRequest'] = new \ArrayObject([
            'type' => 'object', 'required' => ['refresh_token'],
            'properties' => ['refresh_token' => ['type' => 'string']],
        ]);
        $schemas['UserProfile'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'id'         => ['type' => 'string', 'format' => 'uuid'],
                'email'      => ['type' => 'string'],
                'firstName'  => ['type' => 'string'],
                'lastName'   => ['type' => 'string'],
                'fullName'   => ['type' => 'string'],
                'status'     => ['type' => 'string', 'enum' => ['active', 'inactive', 'suspended']],
                'mfaEnabled' => ['type' => 'boolean'],
            ],
        ]);
        $schemas['Organization'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'id'          => ['type' => 'string', 'format' => 'uuid'],
                'name'        => ['type' => 'string'],
                'slug'        => ['type' => 'string'],
                'description' => ['type' => 'string', 'nullable' => true],
                'createdAt'   => ['type' => 'string', 'format' => 'date-time'],
            ],
        ]);
        $schemas['CreateOrganizationRequest'] = new \ArrayObject([
            'type' => 'object', 'required' => ['name'],
            'properties' => [
                'name'        => ['type' => 'string', 'example' => 'Acme Corp'],
                'description' => ['type' => 'string', 'nullable' => true],
            ],
        ]);
        $schemas['Membership'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'id'       => ['type' => 'string', 'format' => 'uuid'],
                'userId'   => ['type' => 'string', 'format' => 'uuid'],
                'email'    => ['type' => 'string'],
                'role'     => ['type' => 'string', 'enum' => ['owner', 'admin', 'member', 'viewer']],
                'joinedAt' => ['type' => 'string', 'format' => 'date-time'],
            ],
        ]);
        $schemas['AddMemberRequest'] = new \ArrayObject([
            'type' => 'object', 'required' => ['email', 'role'],
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email', 'example' => 'bob@example.com'],
                'role'  => ['type' => 'string', 'enum' => ['owner', 'admin', 'member', 'viewer'], 'example' => 'member'],
            ],
        ]);
        $schemas['Secret'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'id'        => ['type' => 'string', 'format' => 'uuid'],
                'title'     => ['type' => 'string'],
                'type'      => ['type' => 'string', 'enum' => ['password', 'secure_note', 'api_key']],
                'folderId'  => ['type' => 'string', 'format' => 'uuid', 'nullable' => true],
                'payload'   => ['type' => 'string', 'nullable' => true, 'description' => 'Décrypté uniquement sur GET /{id}'],
                'createdAt' => ['type' => 'string', 'format' => 'date-time'],
                'updatedAt' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
            ],
        ]);
        $schemas['CreateSecretRequest'] = new \ArrayObject([
            'type' => 'object', 'required' => ['title', 'type', 'payload'],
            'properties' => [
                'title'    => ['type' => 'string', 'example' => 'GitHub PAT'],
                'type'     => ['type' => 'string', 'enum' => ['password', 'secure_note', 'api_key'], 'example' => 'password'],
                'payload'  => ['type' => 'string', 'example' => '{"username":"alice","password":"secret"}'],
                'folderId' => ['type' => 'string', 'format' => 'uuid', 'nullable' => true],
            ],
        ]);
        $schemas['UpdateSecretRequest'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'title'   => ['type' => 'string'],
                'payload' => ['type' => 'string'],
            ],
        ]);

        // Paramètres réutilisables
        $jwtHeader = new Model\Parameter('Authorization', 'header', 'Bearer {token}', true, false, false, ['type' => 'string', 'example' => 'Bearer eyJ...']);
        $orgHeader  = new Model\Parameter('X-Organization-Id', 'header', 'UUID de l\'organisation', true, false, false, ['type' => 'string', 'format' => 'uuid']);
        $idParam    = fn(string $n, string $d) => new Model\Parameter($n, 'path', $d, true, false, false, ['type' => 'string', 'format' => 'uuid']);

        // Réponses réutilisables
        $r = fn(string $desc, ?string $ref = null) => array_filter([
            'description' => $desc,
            'content'     => $ref ? ['application/json' => ['schema' => ['$ref' => '#/components/schemas/' . $ref]]] : null,
        ]);
        $rArr = fn(string $desc, string $ref) => [
            'description' => $desc,
            'content'     => ['application/json' => ['schema' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/' . $ref]]]],
        ];
        $rb = fn(string $ref) => new Model\RequestBody(
            required: true,
            content: new \ArrayObject(['application/json' => ['schema' => ['$ref' => '#/components/schemas/' . $ref]]]),
        );

        $paths = $openApi->getPaths();

        // AUTH
        $paths->addPath('/api/auth/login', new Model\PathItem(post: new Model\Operation(
            operationId: 'postAuthLogin', tags: ['Authentication'],
            summary: 'Connexion — obtenir un JWT',
            requestBody: $rb('LoginRequest'),
            responses: ['200' => $r('JWT token', 'LoginResponse'), '401' => $r('Identifiants invalides')],
        )));

        $paths->addPath('/api/auth/refresh', new Model\PathItem(post: new Model\Operation(
            operationId: 'postAuthRefresh', tags: ['Authentication'],
            summary: 'Renouveler le JWT via refresh token',
            requestBody: $rb('RefreshRequest'),
            responses: ['200' => $r('Nouveaux tokens', 'LoginResponse'), '400' => $r('refresh_token manquant'), '401' => $r('Token invalide ou expiré')],
        )));

        $paths->addPath('/api/me', new Model\PathItem(get: new Model\Operation(
            operationId: 'getMe', tags: ['Authentication'],
            summary: 'Profil de l\'utilisateur connecté',
            parameters: [$jwtHeader],
            responses: ['200' => $r('Profil', 'UserProfile'), '401' => $r('Non authentifié')],
        )));

        // ORGANIZATIONS
        $paths->addPath('/api/organizations', new Model\PathItem(
            get: new Model\Operation(
                operationId: 'getOrganizations', tags: ['Organizations'],
                summary: 'Lister mes organisations',
                parameters: [$jwtHeader],
                responses: ['200' => $rArr('Liste', 'Organization')],
            ),
            post: new Model\Operation(
                operationId: 'createOrganization', tags: ['Organizations'],
                summary: 'Créer une organisation',
                parameters: [$jwtHeader],
                requestBody: $rb('CreateOrganizationRequest'),
                responses: ['201' => $r('Créée', 'Organization'), '422' => $r('Validation')],
            ),
        ));

        $paths->addPath('/api/organizations/{id}', new Model\PathItem(
            get: new Model\Operation(
                operationId: 'getOrganization', tags: ['Organizations'],
                summary: 'Détail d\'une organisation',
                parameters: [$jwtHeader, $idParam('id', 'UUID organisation')],
                responses: ['200' => $r('Organisation', 'Organization'), '403' => $r('Accès refusé'), '404' => $r('Non trouvée')],
            ),
        ));

        $paths->addPath('/api/organizations/{id}/members', new Model\PathItem(
            get: new Model\Operation(
                operationId: 'getOrganizationMembers', tags: ['Organizations'],
                summary: 'Lister les membres',
                parameters: [$jwtHeader, $idParam('id', 'UUID organisation')],
                responses: ['200' => $rArr('Membres', 'Membership')],
            ),
            post: new Model\Operation(
                operationId: 'addOrganizationMember', tags: ['Organizations'],
                summary: 'Ajouter un membre',
                parameters: [$jwtHeader, $idParam('id', 'UUID organisation')],
                requestBody: $rb('AddMemberRequest'),
                responses: ['201' => $r('Ajouté', 'Membership'), '404' => $r('User inconnu'), '409' => $r('Déjà membre'), '422' => $r('Validation')],
            ),
        ));

        $paths->addPath('/api/organizations/{id}/members/{memberId}', new Model\PathItem(
            delete: new Model\Operation(
                operationId: 'removeOrganizationMember', tags: ['Organizations'],
                summary: 'Retirer un membre',
                parameters: [$jwtHeader, $idParam('id', 'UUID organisation'), $idParam('memberId', 'UUID membership')],
                responses: ['204' => $r('Retiré'), '404' => $r('Non trouvé'), '422' => $r('Impossible')],
            ),
        ));

        // SECRETS
        $paths->addPath('/api/secrets', new Model\PathItem(
            get: new Model\Operation(
                operationId: 'getSecrets', tags: ['Secrets'],
                summary: 'Lister les secrets de l\'organisation',
                description: 'Payload non décrypté. Nécessite X-Organization-Id.',
                parameters: [$jwtHeader, $orgHeader],
                responses: ['200' => $rArr('Secrets', 'Secret')],
            ),
            post: new Model\Operation(
                operationId: 'createSecret', tags: ['Secrets'],
                summary: 'Créer un secret (chiffré AES-256-GCM)',
                parameters: [$jwtHeader, $orgHeader],
                requestBody: $rb('CreateSecretRequest'),
                responses: ['201' => $r('Créé', 'Secret'), '422' => $r('Validation')],
            ),
        ));

        $paths->addPath('/api/secrets/{id}', new Model\PathItem(
            get: new Model\Operation(
                operationId: 'getSecret', tags: ['Secrets'],
                summary: 'Lire un secret (payload décrypté)',
                parameters: [$jwtHeader, $orgHeader, $idParam('id', 'UUID secret')],
                responses: ['200' => $r('Secret décrypté', 'Secret'), '403' => $r('Accès refusé'), '404' => $r('Non trouvé')],
            ),
            patch: new Model\Operation(
                operationId: 'updateSecret', tags: ['Secrets'],
                summary: 'Modifier un secret',
                parameters: [$jwtHeader, $orgHeader, $idParam('id', 'UUID secret')],
                requestBody: $rb('UpdateSecretRequest'),
                responses: ['200' => $r('Mis à jour', 'Secret'), '403' => $r('Accès refusé'), '422' => $r('Validation')],
            ),
            delete: new Model\Operation(
                operationId: 'deleteSecret', tags: ['Secrets'],
                summary: 'Supprimer un secret',
                parameters: [$jwtHeader, $orgHeader, $idParam('id', 'UUID secret')],
                responses: ['204' => $r('Supprimé'), '403' => $r('Accès refusé'), '404' => $r('Non trouvé')],
            ),
        ));

        return $openApi->withComponents(
            $openApi->getComponents()->withSchemas($schemas),
        );
    }
}
