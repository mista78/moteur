<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseFormatter;
use App\Services\SinistreServiceInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Sinistre Controller
 * Handles sinistre (claim) related endpoints with date-effet calculations
 */
class SinistreController
{
    private SinistreServiceInterface $sinistreService;
    private LoggerInterface $logger;

    /**
     * Constructor with dependency injection
     *
     * @param SinistreServiceInterface $sinistreService Sinistre service
     * @param LoggerInterface $logger Logger
     */
    public function __construct(
        SinistreServiceInterface $sinistreService,
        LoggerInterface $logger
    ) {
        $this->sinistreService = $sinistreService;
        $this->logger = $logger;
    }

    /**
     * GET /api/sinistres/{id}/date-effet
     * Get sinistre with calculated date-effet for all arrets
     */
    #[OA\Get(
        path: "/api/sinistres/{id}/date-effet",
        summary: "Obtenir un sinistre avec date-effet calculée",
        description: "Récupère un sinistre avec la date d'effet (date-effet) calculée pour tous ses arrêts de travail selon les règles CARMF",
        tags: ["sinistres"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du sinistre",
                schema: new OA\Schema(type: "integer", example: 123)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Sinistre avec date-effet calculée",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(
                                    property: "sinistre",
                                    type: "object",
                                    description: "Données du sinistre"
                                ),
                                new OA\Property(
                                    property: "arrets_with_date_effet",
                                    type: "array",
                                    description: "Arrêts avec date-effet calculée",
                                    items: new OA\Items(type: "object")
                                ),
                                new OA\Property(
                                    property: "recap_indems",
                                    type: "array",
                                    description: "Récapitulatifs des indemnisations (recap_idem) pour ce sinistre",
                                    items: new OA\Items(type: "object")
                                )
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Sinistre non trouvé"),
            new OA\Response(response: 500, description: "Erreur serveur")
        ]
    )]
    public function getSinistreWithDateEffet(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        try {
            $sinistreId = (int) $args['id'];

            // Use the service to get sinistre with calculated date-effet
            $data = $this->sinistreService->getSinistreWithDateEffet($sinistreId);

            return ResponseFormatter::success($response, $data);
        } catch (Exception $e) {
            $this->logger->error('Error getting sinistre with date-effet', [
                'sinistre_id' => $args['id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return ResponseFormatter::error($response, $e->getMessage(), 404);
        }
    }

    /**
     * GET /api/adherents/{adherent_number}/sinistres/{id}/date-effet
     * Get sinistre with date-effet for specific adherent
     */
    #[OA\Get(
        path: "/api/adherents/{adherent_number}/sinistres/{id}/date-effet",
        summary: "Obtenir un sinistre d'un adhérent avec date-effet",
        description: "Récupère un sinistre spécifique d'un adhérent avec la date d'effet calculée",
        tags: ["sinistres"],
        parameters: [
            new OA\Parameter(
                name: "adherent_number",
                in: "path",
                required: true,
                description: "Numéro d'adhérent",
                schema: new OA\Schema(type: "string", example: "123456")
            ),
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du sinistre",
                schema: new OA\Schema(type: "integer", example: 123)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Sinistre avec date-effet calculée",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Sinistre non trouvé ou n'appartient pas à l'adhérent"),
            new OA\Response(response: 500, description: "Erreur serveur")
        ]
    )]
    public function getSinistreForAdherent(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        try {
            $adherentNumber = (string) $args['adherent_number'];
            $sinistreId = (int) $args['id'];

            $data = $this->sinistreService->getSinistreWithDateEffetForAdherent(
                $adherentNumber,
                $sinistreId
            );

            return ResponseFormatter::success($response, $data);
        } catch (Exception $e) {
            $this->logger->error('Error getting sinistre for adherent', [
                'adherent_number' => $args['adherent_number'] ?? null,
                'sinistre_id' => $args['id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return ResponseFormatter::error($response, $e->getMessage(), 404);
        }
    }

    /**
     * GET /api/adherents/{adherent_number}/sinistres/date-effet
     * Get all sinistres for adherent with date-effet
     */
    #[OA\Get(
        path: "/api/adherents/{adherent_number}/sinistres/date-effet",
        summary: "Obtenir tous les sinistres d'un adhérent avec date-effet",
        description: "Récupère tous les sinistres d'un adhérent avec la date d'effet calculée pour chaque arrêt",
        tags: ["sinistres"],
        parameters: [
            new OA\Parameter(
                name: "adherent_number",
                in: "path",
                required: true,
                description: "Numéro d'adhérent",
                schema: new OA\Schema(type: "string", example: "123456")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des sinistres avec date-effet calculée",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(type: "object")
                        )
                    ]
                )
            ),
            new OA\Response(response: 500, description: "Erreur serveur")
        ]
    )]
    public function getAllSinistresForAdherent(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        try {
            $adherentNumber = (string) $args['adherent_number'];

            $data = $this->sinistreService->getAllSinistresWithDateEffet($adherentNumber);

            return ResponseFormatter::success($response, $data);
        } catch (Exception $e) {
            $this->logger->error('Error getting all sinistres for adherent', [
                'adherent_number' => $args['adherent_number'] ?? null,
                'error' => $e->getMessage()
            ]);

            return ResponseFormatter::error($response, $e->getMessage(), 500);
        }
    }
}
