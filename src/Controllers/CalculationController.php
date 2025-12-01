<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseFormatter;
use App\IJCalculator;
use App\Services\DateNormalizer;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Calculation Controller
 * Handles all IJ calculation endpoints
 */
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
