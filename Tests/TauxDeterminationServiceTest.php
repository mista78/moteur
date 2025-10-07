<?php

require_once __DIR__ . '/../jest-php.php';
require_once __DIR__ . '/../Services/TauxDeterminationInterface.php';
require_once __DIR__ . '/../Services/TauxDeterminationService.php';

use IJCalculator\Services\TauxDeterminationService;

describe('TauxDeterminationService', function() {

    test('should return historical rate if provided', function() {
        $service = new TauxDeterminationService();
        $taux = $service->determineTauxNumber(50, 20, true, 5);

        expect($taux)->toBe(5);
    });

    test('should return taux 1 for age < 62 without pathology', function() {
        $service = new TauxDeterminationService();
        $taux = $service->determineTauxNumber(50, 30, false, null);

        expect($taux)->toBe(1);
    });

    test('should return taux 7 for age 62-69 without pathology', function() {
        $service = new TauxDeterminationService();
        $taux = $service->determineTauxNumber(65, 30, false, null);

        expect($taux)->toBe(7);
    });

    test('should return taux 4 for age >= 70 without pathology', function() {
        $service = new TauxDeterminationService();
        $taux = $service->determineTauxNumber(70, 30, false, null);

        expect($taux)->toBe(4);
    });

    test('should return taux 1 for age < 62 with >= 24 trimestres (full rate)', function() {
        $service = new TauxDeterminationService();
        $taux = $service->determineTauxNumber(50, 24, true, null);

        expect($taux)->toBe(1);
    });

    test('should return taux 2 for age < 62 with pathology and 8-15 trimestres', function() {
        $service = new TauxDeterminationService();
        $taux = $service->determineTauxNumber(50, 10, true, null);

        expect($taux)->toBe(2); // Reduction 1/3
    });

    test('should return taux 3 for age < 62 with pathology and 16-23 trimestres', function() {
        $service = new TauxDeterminationService();
        $taux = $service->determineTauxNumber(50, 20, true, null);

        expect($taux)->toBe(3); // Reduction 2/3
    });

    test('should return taux 8 for age 62-69 with pathology and 8-15 trimestres', function() {
        $service = new TauxDeterminationService();
        $taux = $service->determineTauxNumber(65, 10, true, null);

        expect($taux)->toBe(8); // Reduction 1/3
    });

    test('should return taux 9 for age 62-69 with pathology and 16-23 trimestres', function() {
        $service = new TauxDeterminationService();
        $taux = $service->determineTauxNumber(65, 20, true, null);

        expect($taux)->toBe(9); // Reduction 2/3
    });

    test('should return taux 5 for age >= 70 with pathology and 8-15 trimestres', function() {
        $service = new TauxDeterminationService();
        $taux = $service->determineTauxNumber(70, 10, true, null);

        expect($taux)->toBe(5); // Reduction 1/3
    });

    test('should return taux 6 for age >= 70 with pathology and 16-23 trimestres', function() {
        $service = new TauxDeterminationService();
        $taux = $service->determineTauxNumber(70, 20, true, null);

        expect($taux)->toBe(6); // Reduction 2/3
    });

    test('should determine class A for revenue < 1 PASS', function() {
        $service = new TauxDeterminationService();
        $service->setPassValue(47000);
        $classe = $service->determineClasse(30000, '2024-01-01', false);

        expect($classe)->toBe('A');
    });

    test('should determine class B for revenue between 1 and 3 PASS', function() {
        $service = new TauxDeterminationService();
        $service->setPassValue(47000);
        $classe = $service->determineClasse(100000, '2024-01-01', false);

        expect($classe)->toBe('B');
    });

    test('should determine class C for revenue > 3 PASS', function() {
        $service = new TauxDeterminationService();
        $service->setPassValue(47000);
        $classe = $service->determineClasse(150000, '2024-01-01', false);

        expect($classe)->toBe('C');
    });

    test('should return class A if taxed by office', function() {
        $service = new TauxDeterminationService();
        $classe = $service->determineClasse(150000, '2024-01-01', true);

        expect($classe)->toBe('A');
    });

    test('should return class A if revenue not provided', function() {
        $service = new TauxDeterminationService();
        $classe = $service->determineClasse(null, '2024-01-01', false);

        expect($classe)->toBe('A');
    });
});

JestPHP::run();
