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
 * Demo Controller showing Method Injection
 *
 * This controller demonstrates how to inject dependencies
 * directly into methods instead of using constructor injection
 */
class MethodInjectionDemoController
{
    /**
     * Example 1: Method injection with IJCalculator
     *
     * POST /api/demo/calculate
     *
     * Dependencies are injected directly into this method
     */
    public function calculateWithMethodInjection(
        ServerRequestInterface $request,
        ResponseInterface $response,
        IJCalculator $calculator,               // ✅ Injected from container
        LoggerInterface $logger                 // ✅ Injected from container
    ): ResponseInterface {
        try {
            $logger->info('Method injection demo: calculate endpoint called');

            $input = $request->getParsedBody();

            if (!is_array($input)) {
                return ResponseFormatter::error($response, 'Invalid input');
            }

            // Use the method-injected calculator
            $result = $calculator->calculateAmount($input);

            // Use the method-injected logger
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
     * Example 2: Method injection with RateRepository
     *
     * GET /api/demo/rates
     *
     * Different method, different dependencies
     */
    public function getRatesWithMethodInjection(
        ServerRequestInterface $request,
        ResponseInterface $response,
        RateRepository $rateRepository,         // ✅ Different dependency
        LoggerInterface $logger                 // ✅ Also injected
    ): ResponseInterface {
        try {
            $logger->info('Method injection demo: rates endpoint called');

            // Use the method-injected repository
            $rates = $rateRepository->loadRates();

            return ResponseFormatter::success($response, [
                'message' => 'Rates loaded using METHOD INJECTION! 🎯',
                'injection_type' => 'method',
                'rate_count' => count($rates),
                'rates' => array_slice($rates, 0, 3) // First 3 for demo
            ]);

        } catch (\Exception $e) {
            $logger->error('Method injection demo error', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * Example 3: Method injection with route parameter
     *
     * GET /api/demo/rate/{year}
     *
     * Combines route parameters with dependency injection
     */
    public function getRateByYear(
        ServerRequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger,                // ✅ Injected from container
        int $year                               // ✅ From route parameter
    ): ResponseInterface {
        try {
            $logger->info('Method injection demo: rate by year', ['year' => $year]);

            // Use Eloquent model directly
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
     * Example 4: Multiple dependencies in one method
     *
     * POST /api/demo/advanced
     *
     * Shows how to inject multiple dependencies in single method
     */
    public function advancedMethodInjection(
        ServerRequestInterface $request,
        ResponseInterface $response,
        IJCalculator $calculator,               // ✅ Injected
        RateRepository $rateRepository,         // ✅ Injected
        LoggerInterface $logger                 // ✅ Injected
    ): ResponseInterface {
        try {
            $logger->info('Method injection demo: advanced endpoint called');

            $input = $request->getParsedBody();

            if (!is_array($input)) {
                return ResponseFormatter::error($response, 'Invalid input');
            }

            // Use multiple injected dependencies
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
