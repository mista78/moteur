<?php

require_once __DIR__ . '/../jest-php.php';
require_once __DIR__ . '/../Services/RateServiceInterface.php';
require_once __DIR__ . '/../Services/RateService.php';

use IJCalculator\Services\RateService;

describe('RateService', function() {

    test('should load rates from CSV', function() {
        $service = new RateService(__DIR__ . '/../taux.csv');
        $rate2024 = $service->getRateForYear(2024);

        expect($rate2024)->toHaveProperty('taux_a1');
        expect($rate2024)->toHaveProperty('taux_b1');
        expect($rate2024)->toHaveProperty('taux_c1');
    });

    test('should get rate for specific year', function() {
        $service = new RateService(__DIR__ . '/../taux.csv');
        $rate2024 = $service->getRateForYear(2024);

        expect($rate2024['taux_a1'])->toBe('75.06');
    });

    test('should get rate for specific date', function() {
        $service = new RateService(__DIR__ . '/../taux.csv');
        $rate = $service->getRateForDate('2024-06-15');

        expect($rate['taux_a1'])->toBe('75.06');
    });

    test('should calculate daily rate for Medecin class A taux 1', function() {
        $service = new RateService(__DIR__ . '/../taux.csv');
        $dailyRate = $service->getDailyRate('M', 'A', 100, 1, 2024);

        expect($dailyRate)->toBeCloseTo(75.06, 0.01);
    });

    test('should calculate daily rate for Medecin class B taux 1', function() {
        $service = new RateService(__DIR__ . '/../taux.csv');
        $dailyRate = $service->getDailyRate('M', 'B', 100, 1, 2024);

        expect($dailyRate)->toBeCloseTo(112.59, 0.01);
    });

    test('should calculate daily rate for Medecin class C taux 1', function() {
        $service = new RateService(__DIR__ . '/../taux.csv');
        $dailyRate = $service->getDailyRate('M', 'C', 100, 1, 2024);

        expect($dailyRate)->toBeCloseTo(150.12, 0.01);
    });

    test('should apply option multiplier for CCPL', function() {
        $service = new RateService(__DIR__ . '/../taux.csv');
        $dailyRate = $service->getDailyRate('CCPL', 'C', 25, 1, 2024);

        // 150.12 * 0.25 = 37.53
        expect($dailyRate)->toBeCloseTo(37.53, 0.01);
    });

    test('should apply option multiplier for RSPM', function() {
        $service = new RateService(__DIR__ . '/../taux.csv');
        $dailyRate = $service->getDailyRate('RSPM', 'A', 100, 1, 2024);

        // Full rate with 100% option
        expect($dailyRate)->toBeCloseTo(75.06, 0.01);
    });

    test('should use tier 1 for taux 1-3', function() {
        $service = new RateService(__DIR__ . '/../taux.csv');
        $rate1 = $service->getDailyRate('M', 'A', 100, 1, 2024);
        $rate2 = $service->getDailyRate('M', 'A', 100, 2, 2024);
        $rate3 = $service->getDailyRate('M', 'A', 100, 3, 2024);

        // All should use taux_a1 (tier 1)
        expect($rate1)->toBe($rate2);
        expect($rate2)->toBe($rate3);
    });

    test('should use tier 3 for taux 7-9', function() {
        $service = new RateService(__DIR__ . '/../taux.csv');
        $rate7 = $service->getDailyRate('M', 'A', 100, 7, 2024);

        // Should use taux_a3 (tier 3) = 56.3
        expect($rate7)->toBeCloseTo(56.3, 0.01);
    });

    test('should use tier 2 for taux 4-6 with usePeriode2=true', function() {
        $service = new RateService(__DIR__ . '/../taux.csv');
        $rate4 = $service->getDailyRate('M', 'A', 100, 4, 2024, null, 65, true);

        // Should use taux_a2 (tier 2) = 38.3
        expect($rate4)->toBeCloseTo(38.3, 0.01);
    });

    test('should use tier 3 for taux 4-6 with usePeriode2=false', function() {
        $service = new RateService(__DIR__ . '/../taux.csv');
        $rate4 = $service->getDailyRate('M', 'A', 100, 4, 2024, null, 65, false);

        // Should use taux_a3 (tier 3) = 56.3
        expect($rate4)->toBeCloseTo(56.3, 0.01);
    });

    test('should use tier 2 for taux 4-6 when age >= 70', function() {
        $service = new RateService(__DIR__ . '/../taux.csv');
        $rate4 = $service->getDailyRate('M', 'A', 100, 4, 2024, null, 70, false);

        // Should use taux_a2 (tier 2) regardless of usePeriode2
        expect($rate4)->toBeCloseTo(38.3, 0.01);
    });
});

JestPHP::run();
