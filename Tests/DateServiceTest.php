<?php

require_once __DIR__ . '/../jest-php.php';
require_once __DIR__ . '/../Services/DateCalculationInterface.php';
require_once __DIR__ . '/../Services/DateService.php';

use IJCalculator\Services\DateService;

describe('DateService', function() {

    test('should calculate age correctly', function() {
        $service = new DateService();
        $age = $service->calculateAge('2024-09-09', '1989-09-26');

        expect($age)->toBe(34);
    });

    test('should calculate age when birthday not yet reached', function() {
        $service = new DateService();
        $age = $service->calculateAge('2024-09-25', '1989-09-26');

        expect($age)->toBe(34);
    });

    test('should calculate age when birthday already passed', function() {
        $service = new DateService();
        $age = $service->calculateAge('2024-09-27', '1989-09-26');

        expect($age)->toBe(35);
    });

    test('should calculate trimesters correctly', function() {
        $service = new DateService();
        $trimestres = $service->calculateTrimesters('2017-07-01', '2024-09-09');

        expect($trimestres)->toBeGreaterThan(27); // ~28-29 trimestres
    });

    test('should return 0 for empty affiliation date', function() {
        $service = new DateService();
        $trimestres = $service->calculateTrimesters('', '2024-09-09');

        expect($trimestres)->toBe(0);
    });

    test('should return 0 for future affiliation date', function() {
        $service = new DateService();
        $trimestres = $service->calculateTrimesters('2025-01-01', '2024-09-09');

        expect($trimestres)->toBe(0);
    });

    test('should get trimester from date Q1', function() {
        $service = new DateService();
        expect($service->getTrimesterFromDate('2024-01-15'))->toBe(1);
        expect($service->getTrimesterFromDate('2024-02-15'))->toBe(1);
        expect($service->getTrimesterFromDate('2024-03-15'))->toBe(1);
    });

    test('should get trimester from date Q2', function() {
        $service = new DateService();
        expect($service->getTrimesterFromDate('2024-04-15'))->toBe(2);
        expect($service->getTrimesterFromDate('2024-05-15'))->toBe(2);
        expect($service->getTrimesterFromDate('2024-06-15'))->toBe(2);
    });

    test('should get trimester from date Q3', function() {
        $service = new DateService();
        expect($service->getTrimesterFromDate('2024-07-15'))->toBe(3);
        expect($service->getTrimesterFromDate('2024-08-15'))->toBe(3);
        expect($service->getTrimesterFromDate('2024-09-15'))->toBe(3);
    });

    test('should get trimester from date Q4', function() {
        $service = new DateService();
        expect($service->getTrimesterFromDate('2024-10-15'))->toBe(4);
        expect($service->getTrimesterFromDate('2024-11-15'))->toBe(4);
        expect($service->getTrimesterFromDate('2024-12-15'))->toBe(4);
    });

    test('should merge consecutive prolongations', function() {
        $service = new DateService();
        $arrets = [
            ['arret-from-line' => '2024-01-01', 'arret-to-line' => '2024-01-10'],
            ['arret-from-line' => '2024-01-11', 'arret-to-line' => '2024-01-20']
        ];

        $merged = $service->mergeProlongations($arrets);

        expect(count($merged))->toBe(1);
        expect($merged[0]['arret-from-line'])->toBe('2024-01-01');
        expect($merged[0]['arret-to-line'])->toBe('2024-01-20');
    });

    test('should not merge non-consecutive periods', function() {
        $service = new DateService();
        $arrets = [
            ['arret-from-line' => '2024-01-01', 'arret-to-line' => '2024-01-10'],
            ['arret-from-line' => '2024-01-15', 'arret-to-line' => '2024-01-20']
        ];

        $merged = $service->mergeProlongations($arrets);

        expect(count($merged))->toBe(2);
    });

    test('should calculate date effet after 90 days', function() {
        $service = new DateService();
        $arrets = [[
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-12-31',
            'valid_med_controleur' => 1,
            'rechute-line' => 0,
            'dt-line' => 1,
            'declaration-date-line' => '2024-01-01'
        ]];

        $result = $service->calculateDateEffet($arrets, '1989-09-26', 0);

        expect($result[0])->toHaveProperty('date-effet');
        expect($result[0]['date-effet'])->toBeTruthy();
    });

    test('should not set date effet if valid_med_controleur != 1', function() {
        $service = new DateService();
        $arrets = [[
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-12-31',
            'valid_med_controleur' => 0,
            'rechute-line' => 0,
            'dt-line' => 1
        ]];

        $result = $service->calculateDateEffet($arrets, '1989-09-26', 0);

        expect($result[0]['date-effet'])->toBeNull();
    });

    test('should calculate payable days correctly', function() {
        $service = new DateService();
        $arrets = [[
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-01-31',
            'date-effet' => '2024-01-01',
            'valid_med_controleur' => 1
        ]];

        $result = $service->calculatePayableDays($arrets, '2024-01-31', null, '2024-01-31');

        expect($result['total_days'])->toBeGreaterThan(0);
        expect($result['payment_details'][0]['payable_days'])->toBeGreaterThan(0);
    });

    test('should return 0 payable days if valid_med_controleur != 1', function() {
        $service = new DateService();
        $arrets = [[
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-01-31',
            'date-effet' => '2024-01-01',
            'valid_med_controleur' => 0
        ]];

        $result = $service->calculatePayableDays($arrets, '2024-01-31', null, '2024-01-31');

        expect($result['total_days'])->toBe(0);
        expect($result['payment_details'][0]['payable_days'])->toBe(0);
    });

    test('should return empty payment_start when payable_days is 0', function() {
        $service = new DateService();
        $arrets = [[
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-01-31',
            'date-effet' => '2024-02-01', // After end date
            'valid_med_controleur' => 1
        ]];

        $result = $service->calculatePayableDays($arrets, '2024-01-31', null, '2024-01-31');

        expect($result['payment_details'][0]['payment_start'])->toBe('');
        expect($result['payment_details'][0]['payment_end'])->toBe('');
    });
});

JestPHP::run();
