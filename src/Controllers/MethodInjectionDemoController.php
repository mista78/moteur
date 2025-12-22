<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseFormatter;
use App\IJCalculator;
use App\Models\IjTaux;
use App\Repositories\RateRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur de Démonstration de l'Injection par Méthode
 *
 * Ce contrôleur démontre comment injecter des dépendances
 * directement dans les méthodes au lieu d'utiliser l'injection par constructeur
 */
class MethodInjectionDemoController
{
    /**
     * Exemple 1 : Injection par méthode avec IJCalculator
     *
     * POST /api/demo/calculate
     *
     * Les dépendances sont injectées directement dans cette méthode
     */
    public function calculateWithMethodInjection(
        ServerRequestInterface $request,
        ResponseInterface $response,
        IJCalculator $calculator,               // ✅ Injecté depuis le conteneur
        LoggerInterface $logger                 // ✅ Injecté depuis le conteneur
    ): ResponseInterface {
        try {
            $logger->info('Method injection demo: calculate endpoint called');

            $input = $request->getParsedBody();

            if (!is_array($input)) {
                return ResponseFormatter::error($response, 'Invalid input');
            }

            // Utiliser le calculateur injecté par méthode
            $result = $calculator->calculateAmount($input);

            // Utiliser le logger injecté par méthode
            $logger->info('Calculation completed via method injection', [
                'montant' => $result['montant'],
                'nb_jours' => $result['nb_jours']
            ]);

            return ResponseFormatter::success($response, [
                'message' => 'This result was calculated using METHOD INJECTION! 🎯',
                'injection_type' => 'method',
                'result' => $result
            ]);

        } catch (\Exception $e) {
            $logger->error('Method injection demo error', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * Exemple 2 : Injection par méthode avec RateRepository
     *
     * GET /api/demo/rates
     *
     * Méthode différente, dépendances différentes
     */
    public function getRatesWithMethodInjection(
        ServerRequestInterface $request,
        ResponseInterface $response,
        RateRepository $rateRepository,         // ✅ Dépendance différente
        LoggerInterface $logger                 // ✅ Également injecté
    ): ResponseInterface {
        try {
            $logger->info('Method injection demo: rates endpoint called');

            // Utiliser le repository injecté par méthode
            $rates = $rateRepository->loadRates();

            return ResponseFormatter::success($response, [
                'message' => 'Rates loaded using METHOD INJECTION! 🎯',
                'injection_type' => 'method',
                'rate_count' => count($rates),
                'rates' => array_slice($rates, 0, 3) // Premiers 3 pour la démo
            ]);

        } catch (\Exception $e) {
            $logger->error('Method injection demo error', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * Exemple 3 : Injection par méthode avec paramètre de route
     *
     * GET /api/demo/rate/{year}
     *
     * Combine les paramètres de route avec l'injection de dépendances
     */
    public function getRateByYear(
        ServerRequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger,                // ✅ Injecté depuis le conteneur
        int $year                               // ✅ Depuis le paramètre de route
    ): ResponseInterface {
        try {
            $logger->info('Method injection demo: rate by year', ['year' => $year]);

            // Utiliser le modèle Eloquent directement
            $rate = IjTaux::getRateForYear($year);

            if (!$rate) {
                return ResponseFormatter::error(
                    $response,
                    "No rate found for year {$year}",
                    404
                );
            }

            return ResponseFormatter::success($response, [
                'message' => 'Rate found using METHOD INJECTION + ROUTE PARAM! 🎯',
                'injection_type' => 'method + route parameter',
                'year' => $year,
                'rate' => [
                    'date_start' => $rate->date_start->format('Y-m-d'),
                    'date_end' => $rate->date_end->format('Y-m-d'),
                    'taux_a1' => $rate->taux_a1,
                    'taux_b1' => $rate->taux_b1,
                    'taux_c1' => $rate->taux_c1,
                ]
            ]);

        } catch (\Exception $e) {
            $logger->error('Method injection demo error', ['year' => $year, 'error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * Exemple 4 : Dépendances multiples dans une seule méthode
     *
     * POST /api/demo/advanced
     *
     * Montre comment injecter plusieurs dépendances dans une seule méthode
     */
    public function advancedMethodInjection(
        ServerRequestInterface $request,
        ResponseInterface $response,
        IJCalculator $calculator,               // ✅ Injecté
        RateRepository $rateRepository,         // ✅ Injecté
        LoggerInterface $logger                 // ✅ Injecté
    ): ResponseInterface {
        try {
            $logger->info('Method injection demo: advanced endpoint called');

            $input = $request->getParsedBody();

            if (!is_array($input)) {
                return ResponseFormatter::error($response, 'Invalid input');
            }

            // Utiliser plusieurs dépendances injectées
            $rates = $rateRepository->loadRates();
            $result = $calculator->calculateAmount($input);

            $logger->info('Advanced calculation completed', [
                'rate_count' => count($rates),
                'montant' => $result['montant']
            ]);

            return ResponseFormatter::success($response, [
                'message' => 'Using MULTIPLE METHOD-INJECTED dependencies! 🎯',
                'injection_type' => 'multiple method injections',
                'available_rates' => count($rates),
                'calculation_result' => $result
            ]);

        } catch (\Exception $e) {
            $logger->error('Advanced method injection error', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }
}
