<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\DateService;

class DateServiceTest extends TestCase
{
    private DateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DateService();
    }

    public function testShouldCalculateAgeCorrectly(): void
    {
        $age = $this->service->calculateAge('2024-09-09', '1989-09-26');
        $this->assertEquals(34, $age);
    }

    public function testShouldCalculateAgeWhenBirthdayNotYetReached(): void
    {
        $age = $this->service->calculateAge('2024-09-25', '1989-09-26');
        $this->assertEquals(34, $age);
    }

    public function testShouldCalculateAgeWhenBirthdayAlreadyPassed(): void
    {
        $age = $this->service->calculateAge('2024-09-27', '1989-09-26');
        $this->assertEquals(35, $age);
    }

    public function testShouldCalculateTrimestersCorrectly(): void
    {
        $trimestres = $this->service->calculateTrimesters('2017-07-01', '2024-09-09');
        $this->assertGreaterThan(27, $trimestres); // ~28-29 trimestres
    }

    public function testShouldReturn0ForEmptyAffiliationDate(): void
    {
        $trimestres = $this->service->calculateTrimesters('', '2024-09-09');
        $this->assertEquals(0, $trimestres);
    }

    public function testShouldReturn0ForFutureAffiliationDate(): void
    {
        $trimestres = $this->service->calculateTrimesters('2025-01-01', '2024-09-09');
        $this->assertEquals(0, $trimestres);
    }

    public function testShouldGetTrimesterFromDateQ1(): void
    {
        $this->assertEquals(1, $this->service->getTrimesterFromDate('2024-01-15'));
        $this->assertEquals(1, $this->service->getTrimesterFromDate('2024-02-15'));
        $this->assertEquals(1, $this->service->getTrimesterFromDate('2024-03-15'));
    }

    public function testShouldGetTrimesterFromDateQ2(): void
    {
        $this->assertEquals(2, $this->service->getTrimesterFromDate('2024-04-15'));
        $this->assertEquals(2, $this->service->getTrimesterFromDate('2024-05-15'));
        $this->assertEquals(2, $this->service->getTrimesterFromDate('2024-06-15'));
    }

    public function testShouldGetTrimesterFromDateQ3(): void
    {
        $this->assertEquals(3, $this->service->getTrimesterFromDate('2024-07-15'));
        $this->assertEquals(3, $this->service->getTrimesterFromDate('2024-08-15'));
        $this->assertEquals(3, $this->service->getTrimesterFromDate('2024-09-15'));
    }

    public function testShouldGetTrimesterFromDateQ4(): void
    {
        $this->assertEquals(4, $this->service->getTrimesterFromDate('2024-10-15'));
        $this->assertEquals(4, $this->service->getTrimesterFromDate('2024-11-15'));
        $this->assertEquals(4, $this->service->getTrimesterFromDate('2024-12-15'));
    }

    public function testShouldMergeConsecutiveProlongations(): void
    {
        $arrets = [
            ['arret-from-line' => '2024-01-01', 'arret-to-line' => '2024-01-10'],
            ['arret-from-line' => '2024-01-11', 'arret-to-line' => '2024-01-20']
        ];

        $merged = $this->service->mergeProlongations($arrets);

        $this->assertEquals(1, count($merged));
        $this->assertEquals('2024-01-01', $merged[0]['arret-from-line']);
        $this->assertEquals('2024-01-20', $merged[0]['arret-to-line']);
    }

    public function testShouldNotMergeNonConsecutivePeriods(): void
    {
        $arrets = [
            ['arret-from-line' => '2024-01-01', 'arret-to-line' => '2024-01-10'],
            ['arret-from-line' => '2024-01-15', 'arret-to-line' => '2024-01-20']
        ];

        $merged = $this->service->mergeProlongations($arrets);

        $this->assertEquals(2, count($merged));
    }

    public function testShouldCalculateDateEffetAfter90Days(): void
    {
        $arrets = [[
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-12-31',
            'valid_med_controleur' => 1,
            'rechute-line' => 0,
            'dt-line' => 1,
            'declaration-date-line' => '2024-01-01'
        ]];

        $result = $this->service->calculateDateEffet($arrets, '1989-09-26', 0);

        $this->assertArrayHasKey('date-effet', $result[0]);
        $this->assertNotEmpty($result[0]['date-effet']);
    }

    public function testShouldNotSetDateEffetIfValidMedControleurNotOne(): void
    {
        $arrets = [[
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-12-31',
            'valid_med_controleur' => 0,
            'rechute-line' => 0,
            'dt-line' => 1
        ]];

        $result = $this->service->calculateDateEffet($arrets, '1989-09-26', 0);

        $this->assertNull($result[0]['date-effet']);
    }

    public function testShouldCalculatePayableDaysCorrectly(): void
    {
        $arrets = [[
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-01-31',
            'date-effet' => '2024-01-01',
            'valid_med_controleur' => 1
        ]];

        $result = $this->service->calculatePayableDays($arrets, '2024-01-31', null, '2024-01-31');

        $this->assertGreaterThan(0, $result['total_days']);
        $this->assertGreaterThan(0, $result['payment_details'][0]['payable_days']);
    }

    public function testShouldReturn0PayableDaysIfValidMedControleurNotOne(): void
    {
        $arrets = [[
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-01-31',
            'date-effet' => '2024-01-01',
            'valid_med_controleur' => 0
        ]];

        $result = $this->service->calculatePayableDays($arrets, '2024-01-31', null, '2024-01-31');

        $this->assertEquals(0, $result['total_days']);
        $this->assertEquals(0, $result['payment_details'][0]['payable_days']);
    }

    public function testShouldReturnEmptyPaymentStartWhenPayableDaysIsZero(): void
    {
        $arrets = [[
            'arret-from-line' => '2024-01-01',
            'arret-to-line' => '2024-01-31',
            'date-effet' => '2024-02-01', // After end date
            'valid_med_controleur' => 1
        ]];

        $result = $this->service->calculatePayableDays($arrets, '2024-01-31', null, '2024-01-31');

        $this->assertEquals('', $result['payment_details'][0]['payment_start']);
        $this->assertEquals('', $result['payment_details'][0]['payment_end']);
    }
}
