<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseFormatter;
use App\IJCalculator;
use App\Services\DateNormalizer;
use Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Calculation Controller
 * Handles all IJ calculation endpoints
 */
#[OA\Info(
    version: "1.0.0",
    title: "API Calculateur IJ CARMF",
    description: "Calculateur d'indemnités journalières pour les professionnels de santé libéraux - CARMF (Caisse Autonome de Retraite des Médecins de France)",
    contact: new OA\Contact(name: "Support API CARMF IJ")
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Serveur de développement local"
)]
#[OA\Server(
    url: "/",
    description: "Serveur de production"
)]
#[OA\Tag(
    name: "calculations",
    description: "Opérations de calcul des indemnités journalières"
)]
#[OA\Tag(
    name: "mocks",
    description: "Données de test (mocks)"
)]
class CalculationController
{
    private IJCalculator $calculator;
    private LoggerInterface $logger;

    public function __construct(IJCalculator $calculator, LoggerInterface $logger)
    {
        $this->calculator = $calculator;
        $this->logger = $logger;
    }

    /**
     * POST /api/calculations
     * Main calculation endpoint
     */
    #[OA\Post(
        path: "/api/calculations",
        summary: "Calculer les indemnités journalières",
        description: "Point d'entrée principal pour le calcul des indemnités journalières basé sur la classe de cotisation, l'âge et les arrêts de travail",
        tags: ["calculations"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["arrets"],
                properties: [
                    new OA\Property(property: "statut", type: "string", enum: ["M", "RSPM", "CCPL"], example: "M", description: "Statut professionnel (M=Médecin, RSPM=Remplaçant, CCPL=Conjoint collaborateur)"),
                    new OA\Property(property: "classe", type: "string", enum: ["A", "B", "C"], example: "A", description: "Classe de cotisation"),
                    new OA\Property(property: "option", type: "integer", example: 100, description: "Pourcentage d'option"),
                    new OA\Property(property: "birth_date", type: "string", format: "date", example: "1989-09-26", description: "Date de naissance"),
                    new OA\Property(property: "current_date", type: "string", format: "date", example: "2024-09-09", description: "Date actuelle"),
                    new OA\Property(property: "attestation_date", type: "string", format: "date", example: "2024-01-31", description: "Date d'attestation"),
                    new OA\Property(property: "affiliation_date", type: "string", format: "date", example: "2019-01-15", description: "Date d'affiliation CARMF"),
                    new OA\Property(property: "nb_trimestres", type: "integer", example: 22, description: "Nombre de trimestres validés"),
                    new OA\Property(property: "pass_value", type: "integer", example: 46368, description: "Valeur du PASS (Plafond Annuel de la Sécurité Sociale) - optionnel"),
                    new OA\Property(property: "revenu_n_moins_2", type: "number", example: 50000, description: "Revenu N-2 pour détermination automatique de la classe"),
                    new OA\Property(
                        property: "arrets",
                        type: "array",
                        description: "Liste des arrêts de travail",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "arret-from-line", type: "string", format: "date", example: "2023-10-24", description: "Date de début de l'arrêt"),
                                new OA\Property(property: "arret-to-line", type: "string", format: "date", example: "2024-01-31", description: "Date de fin de l'arrêt"),
                                new OA\Property(property: "rechute-line", type: "integer", example: 0, description: "Indicateur rechute (0=non, 1=oui)"),
                                new OA\Property(property: "dt-line", type: "integer", example: 1, description: "Délai de transmission (jours)"),
                                new OA\Property(property: "gpm-member-line", type: "integer", example: 1, description: "Membre GPM (0=non, 1=oui)")
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Calcul effectué avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "montant", type: "number", format: "float", example: 12345.67, description: "Montant total des IJ en euros"),
                                new OA\Property(property: "nb_jours", type: "integer", example: 100, description: "Nombre de jours indemnisés"),
                                new OA\Property(property: "details", type: "array", items: new OA\Items(type: "object"), description: "Détails du calcul par jour")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Requête invalide"),
            new OA\Response(response: 500, description: "Erreur de calcul")
        ]
    )]
    public function calculate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $input = $request->getParsedBody();

            // Ensure $input is an array
            if (!is_array($input)) {
                return ResponseFormatter::error($response, 'Invalid request body');
            }

            if (!isset($input['arrets']) || !is_array($input['arrets'])) {
                return ResponseFormatter::error($response, 'Missing or invalid arrets parameter');
            }

            // Normalize all dates
            $input = DateNormalizer::normalize($input);

            // Set PASS value if provided
            if (isset($input['pass_value'])) {
                $this->calculator->setPassValue($input['pass_value']);
            }

            // Auto-determine class if revenu_n_moins_2 provided but classe is not
            if (isset($input['revenu_n_moins_2']) && !isset($input['classe'])) {
                $revenuNMoins2 = (float) $input['revenu_n_moins_2'];
                $taxeOffice = isset($input['taxe_office']) ? (bool) $input['taxe_office'] : false;
                $dateOuvertureDroits = $input['date_ouverture_droits'] ?? null;

                $input['classe'] = $this->calculator->determineClasse($revenuNMoins2, $dateOuvertureDroits, $taxeOffice);
            }

            $result = $this->calculator->calculateAmount($input);

            $this->logger->info('Calculation completed', ['nb_jours' => $result['nb_jours'], 'montant' => $result['montant']]);

            return ResponseFormatter::success($response, $result);

        } catch (Exception $e) {
            $this->logger->error('Calculation error', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * POST /api/calculations/date-effet
     * Calculate date-effet for arrets (90-day rule)
     */
    #[OA\Post(
        path: "/api/calculations/date-effet",
        summary: "Calculer la date d'effet",
        description: "Calcule la date d'effet pour les arrêts de travail (règle des 90 jours pour nouvelle pathologie, 15 jours pour rechute)",
        tags: ["calculations"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["arrets"],
                properties: [
                    new OA\Property(property: "birth_date", type: "string", format: "date", example: "1989-09-26", description: "Date de naissance"),
                    new OA\Property(property: "previous_cumul_days", type: "integer", example: 0, description: "Nombre de jours cumulés précédemment"),
                    new OA\Property(
                        property: "arrets",
                        type: "array",
                        description: "Liste des arrêts de travail",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "arret-from-line", type: "string", format: "date", description: "Date de début"),
                                new OA\Property(property: "arret-to-line", type: "string", format: "date", description: "Date de fin"),
                                new OA\Property(property: "rechute-line", type: "integer", description: "Indicateur rechute"),
                                new OA\Property(property: "dt-line", type: "integer", description: "Délai de transmission"),
                                new OA\Property(property: "gpm-member-line", type: "integer", description: "Membre GPM")
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Date d'effet calculée avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"), description: "Arrêts avec dates d'effet")
                    ]
                )
            )
        ]
    )]
    public function dateEffet(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $input = $request->getParsedBody();

            // Ensure $input is an array
            if (!is_array($input)) {
                return ResponseFormatter::error($response, 'Invalid request body');
            }

            if (!isset($input['arrets']) || !is_array($input['arrets'])) {
                return ResponseFormatter::error($response, 'Missing or invalid arrets parameter');
            }

            // Normalize all dates
            $input = DateNormalizer::normalize($input);

            $arrets = $input['arrets'];
            $birthDate = $input['birth_date'] ?? null;
            $previousCumulDays = $input['previous_cumul_days'] ?? 0;

            $result = $this->calculator->calculateDateEffet($arrets, $birthDate, $previousCumulDays);

            return ResponseFormatter::success($response, $result);

        } catch (Exception $e) {
            $this->logger->error('Date-effet calculation error', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * POST /api/calculations/end-payment
     * Calculate end payment dates by period
     */
    public function endPayment(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $input = $request->getParsedBody();

            // Ensure $input is an array
            if (!is_array($input)) {
                return ResponseFormatter::error($response, 'Invalid request body');
            }

            if (!isset($input['arrets']) || !is_array($input['arrets'])) {
                return ResponseFormatter::error($response, 'Missing or invalid arrets parameter');
            }

            // Normalize all dates
            $input = DateNormalizer::normalize($input);

            $arrets = $input['arrets'];
            $previousCumulDays = $input['previous_cumul_days'] ?? 0;
            $birthDate = $input['birth_date'] ?? null;
            $currentDate = $input['current_date'] ?? date('Y-m-d');

            if (!$birthDate) {
                return ResponseFormatter::error($response, 'Missing birth_date parameter');
            }

            // First calculate date effet for each arrêt
            $arretsWithEffet = $this->calculator->calculateDateEffet($arrets, $birthDate, $previousCumulDays);

            // Then calculate end payment dates
            $result = $this->calculator->calculateEndPaymentDates(
                $arretsWithEffet,
                $previousCumulDays,
                $birthDate,
                $currentDate
            );

            return ResponseFormatter::success($response, $result);

        } catch (Exception $e) {
            $this->logger->error('End payment calculation error', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * POST /api/calculations/revenu
     * Calculate revenue from class and PASS
     */
    public function revenu(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $input = $request->getParsedBody();

            // Ensure $input is an array
            if (!is_array($input)) {
                return ResponseFormatter::error($response, 'Invalid request body');
            }

            if (!isset($input['classe'])) {
                return ResponseFormatter::error($response, 'Missing classe parameter');
            }

            $classe = $input['classe'];
            $nbPass = $input['nb_pass'] ?? null;

            // Set PASS value if provided
            if (isset($input['pass_value'])) {
                $this->calculator->setPassValue($input['pass_value']);
            }

            $result = $this->calculator->calculateRevenuAnnuel($classe, $nbPass);

            return ResponseFormatter::success($response, $result);

        } catch (Exception $e) {
            $this->logger->error('Revenue calculation error', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * POST /api/calculations/classe
     * Determine contribution class from revenue
     */
    #[OA\Post(
        path: "/api/calculations/classe",
        summary: "Déterminer la classe de cotisation",
        description: "Détermine automatiquement la classe de cotisation (A/B/C) en fonction du revenu et de la valeur du PASS",
        tags: ["calculations"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "revenu_n_moins_2", type: "number", example: 50000, description: "Revenu de l'année N-2 en euros"),
                    new OA\Property(property: "date_ouverture_droits", type: "string", format: "date", example: "2024-01-01", description: "Date d'ouverture des droits"),
                    new OA\Property(property: "taxe_office", type: "boolean", example: false, description: "Soumis à la taxe d'office"),
                    new OA\Property(property: "pass_value", type: "integer", example: 46368, description: "Valeur du PASS (optionnel)")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Classe déterminée avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "classe", type: "string", enum: ["A", "B", "C"], example: "B", description: "Classe de cotisation déterminée"),
                                new OA\Property(property: "revenu_n_moins_2", type: "number", example: 50000),
                                new OA\Property(property: "taxe_office", type: "boolean", example: false),
                                new OA\Property(property: "pass_value", type: "integer", example: 46368)
                            ],
                            type: "object"
                        )
                    ]
                )
            )
        ]
    )]
    public function determineClasse(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $input = $request->getParsedBody();

            // Normalize all dates
            $input = DateNormalizer::normalize($input);

            $revenuNMoins2 = isset($input['revenu_n_moins_2']) ? (float) $input['revenu_n_moins_2'] : null;
            $dateOuvertureDroits = $input['date_ouverture_droits'] ?? null;
            $taxeOffice = isset($input['taxe_office']) ? (bool) $input['taxe_office'] : false;

            // Set PASS value if provided
            if (isset($input['pass_value'])) {
                $this->calculator->setPassValue($input['pass_value']);
            }

            $classe = $this->calculator->determineClasse($revenuNMoins2, $dateOuvertureDroits, $taxeOffice);

            return ResponseFormatter::success($response, [
                'classe' => $classe,
                'revenu_n_moins_2' => $revenuNMoins2,
                'taxe_office' => $taxeOffice,
                'pass_value' => $input['pass_value'] ?? 47000
            ]);

        } catch (Exception $e) {
            $this->logger->error('Classe determination error', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * POST /api/calculations/arrets-date-effet
     * Calculate date-effet for multiple arrets with rechute detection
     */
    public function arretsDateEffet(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $input = $request->getParsedBody();

            // Ensure $input is an array
            if (!is_array($input)) {
                return ResponseFormatter::error($response, 'Invalid request body');
            }

            if (!isset($input['arrets']) || !is_array($input['arrets'])) {
                return ResponseFormatter::error($response, 'Missing or invalid arrets parameter');
            }

            // Normalize all dates
            $input = DateNormalizer::normalize($input);

            $arrets = $input['arrets'];
            $birthDate = $input['birth_date'] ?? null;
            $previousCumulDays = $input['previous_cumul_days'] ?? 0;

            // Calculate date-effet for all arrets
            $arretsWithDateEffet = $this->calculator->calculateDateEffet($arrets, $birthDate, $previousCumulDays);

            return ResponseFormatter::success($response, $arretsWithDateEffet);

        } catch (Exception $e) {
            $this->logger->error('Arrets date-effet calculation error', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }
}
