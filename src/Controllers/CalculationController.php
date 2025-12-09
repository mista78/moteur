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
    title: "CARMF IJ Calculator API",
    description: "French medical professional sick leave benefits calculator (Indemnités Journalières) for CARMF",
    contact: new OA\Contact(name: "CARMF IJ API Support")
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Local development server"
)]
#[OA\Server(
    url: "/",
    description: "Production server"
)]
#[OA\Tag(
    name: "calculations",
    description: "IJ calculation operations"
)]
#[OA\Tag(
    name: "mocks",
    description: "Mock data for testing"
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
        summary: "Calculate IJ benefits",
        description: "Main calculation endpoint for daily sick leave benefits based on contribution class, age, and work stoppages",
        tags: ["calculations"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["arrets"],
                properties: [
                    new OA\Property(property: "statut", type: "string", enum: ["M", "RSPM", "CCPL"], example: "M", description: "Professional status"),
                    new OA\Property(property: "classe", type: "string", enum: ["A", "B", "C"], example: "A", description: "Contribution class"),
                    new OA\Property(property: "option", type: "integer", example: 100, description: "Option percentage"),
                    new OA\Property(property: "birth_date", type: "string", format: "date", example: "1989-09-26", description: "Birth date"),
                    new OA\Property(property: "current_date", type: "string", format: "date", example: "2024-09-09", description: "Current date"),
                    new OA\Property(property: "attestation_date", type: "string", format: "date", example: "2024-01-31", description: "Attestation date"),
                    new OA\Property(property: "affiliation_date", type: "string", format: "date", example: "2019-01-15", description: "Affiliation date"),
                    new OA\Property(property: "nb_trimestres", type: "integer", example: 22, description: "Number of quarters"),
                    new OA\Property(property: "pass_value", type: "integer", example: 46368, description: "PASS value (optional)"),
                    new OA\Property(property: "revenu_n_moins_2", type: "number", example: 50000, description: "Revenue N-2 for auto class determination"),
                    new OA\Property(
                        property: "arrets",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "arret-from-line", type: "string", format: "date", example: "2023-10-24"),
                                new OA\Property(property: "arret-to-line", type: "string", format: "date", example: "2024-01-31"),
                                new OA\Property(property: "rechute-line", type: "integer", example: 0),
                                new OA\Property(property: "dt-line", type: "integer", example: 1),
                                new OA\Property(property: "gpm-member-line", type: "integer", example: 1)
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful calculation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "montant", type: "number", format: "float", example: 12345.67),
                                new OA\Property(property: "nb_jours", type: "integer", example: 100),
                                new OA\Property(property: "details", type: "array", items: new OA\Items(type: "object"))
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Invalid request"),
            new OA\Response(response: 500, description: "Calculation error")
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
        summary: "Calculate date-effet",
        description: "Calculate effective date for work stoppages (90-day rule for new pathology, 15-day for rechute)",
        tags: ["calculations"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["arrets"],
                properties: [
                    new OA\Property(property: "birth_date", type: "string", format: "date", example: "1989-09-26"),
                    new OA\Property(property: "previous_cumul_days", type: "integer", example: 0),
                    new OA\Property(
                        property: "arrets",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "arret-from-line", type: "string", format: "date"),
                                new OA\Property(property: "arret-to-line", type: "string", format: "date"),
                                new OA\Property(property: "rechute-line", type: "integer"),
                                new OA\Property(property: "dt-line", type: "integer"),
                                new OA\Property(property: "gpm-member-line", type: "integer")
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Date-effet calculated",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
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
        summary: "Determine contribution class",
        description: "Automatically determine contribution class (A/B/C) based on revenue and PASS value",
        tags: ["calculations"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "revenu_n_moins_2", type: "number", example: 50000, description: "Revenue N-2"),
                    new OA\Property(property: "date_ouverture_droits", type: "string", format: "date", example: "2024-01-01"),
                    new OA\Property(property: "taxe_office", type: "boolean", example: false),
                    new OA\Property(property: "pass_value", type: "integer", example: 46368, description: "Optional PASS value")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Class determined",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "classe", type: "string", enum: ["A", "B", "C"], example: "B"),
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
