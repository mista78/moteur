<?php

declare(strict_types=1);

namespace Tests;

use App\Services\RateService;
use PHPUnit\Framework\TestCase;

class RateServiceTest extends TestCase
{
    private RateService $service;
    private array $rates;

    protected function setUp(): void
    {
        parent::setUp();

        // Define test rates for 2024 and 2025
        $this->rates = [
            [
                'date_start' => '2024-01-01',
                'date_end' => '2024-12-31',
                'taux_a1' => 80.00,
                'taux_a2' => 40.00,
                'taux_a3' => 60.00,
                'taux_b1' => 160.00,
                'taux_b2' => 80.00,
                'taux_b3' => 120.00,
                'taux_c1' => 240.00,
                'taux_c2' => 120.00,
                'taux_c3' => 180.00,
            ],
            [
                'date_start' => '2025-01-01',
                'date_end' => '2025-12-31',
                'taux_a1' => 100.00,
                'taux_a2' => 50.00,
                'taux_a3' => 75.00,
                'taux_b1' => 200.00,
                'taux_b2' => 100.00,
                'taux_b3' => 150.00,
                'taux_c1' => 300.00,
                'taux_c2' => 150.00,
                'taux_c3' => 225.00,
            ]
        ];

        $this->service = new RateService($this->rates);
        $this->service->setPassValue(46368); // PASS 2024
    }

    public function testLoadRatesFromArray(): void
    {
        $rate2024 = $this->service->getRateForYear(2024);

        $this->assertArrayHasKey('taux_a1', $rate2024);
        $this->assertArrayHasKey('taux_b1', $rate2024);
        $this->assertArrayHasKey('taux_c1', $rate2024);
    }

    public function testGetRateForYear2024(): void
    {
        $rate2024 = $this->service->getRateForYear(2024);

        $this->assertEquals(80.00, $rate2024['taux_a1']);
        $this->assertEquals(160.00, $rate2024['taux_b1']);
        $this->assertEquals(240.00, $rate2024['taux_c1']);
    }

    public function testGetRateForYear2025(): void
    {
        $rate2025 = $this->service->getRateForYear(2025);

        $this->assertEquals(100.00, $rate2025['taux_a1']);
        $this->assertEquals(200.00, $rate2025['taux_b1']);
        $this->assertEquals(300.00, $rate2025['taux_c1']);
    }

    public function testGetRateForSpecificDate(): void
    {
        $rate = $this->service->getRateForDate('2024-06-15');

        $this->assertEquals(80.00, $rate['taux_a1']);
    }

    // ========================================================================
    // Calendar Year Logic Tests (NEW)
    // ========================================================================

    public function testDayIn2024UsesTaux2024(): void
    {
        // Arrêt starting Dec 20, 2024, calculate rate for Dec 25, 2024
        $dailyRate = $this->service->getDailyRate(
            statut: 'M',
            classe: 'A',
            option: 100,
            taux: 1,
            year: 2024,
            date: '2024-12-20',
            calculationDate: '2024-12-25'
        );

        $this->assertEqualsWithDelta(80.00, $dailyRate, 0.01);
    }

    public function testDayIn2025ForArretStarting2024UsesTaux2025(): void
    {
        // Arrêt starting Dec 20, 2024, calculate rate for Jan 5, 2025
        $dailyRate = $this->service->getDailyRate(
            statut: 'M',
            classe: 'A',
            option: 100,
            taux: 1,
            year: 2024,
            date: '2024-12-20',
            calculationDate: '2025-01-05'
        );

        $this->assertEqualsWithDelta(100.00, $dailyRate, 0.01);
    }

    public function testArretStartingIn2025UsesPassFormula(): void
    {
        // Arrêt starting Jan 5, 2025
        $dailyRate = $this->service->getDailyRate(
            statut: 'M',
            classe: 'A',
            option: 100,
            taux: 1,
            year: 2025,
            date: '2025-01-05'
        );

        // PASS formula: (1 * 46368) / 730 = 63.52
        $this->assertEqualsWithDelta(63.52, $dailyRate, 0.01);
    }

    public function testClassBDayIn2024(): void
    {
        $dailyRate = $this->service->getDailyRate(
            statut: 'M',
            classe: 'B',
            option: 100,
            taux: 1,
            year: 2024,
            date: '2024-12-20',
            calculationDate: '2024-12-25'
        );

        $this->assertEqualsWithDelta(160.00, $dailyRate, 0.01);
    }

    public function testClassBDayIn2025ForArretStarting2024(): void
    {
        $dailyRate = $this->service->getDailyRate(
            statut: 'M',
            classe: 'B',
            option: 100,
            taux: 1,
            year: 2024,
            date: '2024-12-20',
            calculationDate: '2025-01-05'
        );

        $this->assertEqualsWithDelta(200.00, $dailyRate, 0.01);
    }

    public function testClassBArretStartingIn2025UsesPassFormula(): void
    {
        $dailyRate = $this->service->getDailyRate(
            statut: 'M',
            classe: 'B',
            option: 100,
            taux: 1,
            year: 2025,
            date: '2025-01-05'
        );

        // PASS formula: (2 * 46368) / 730 = 127.04
        $this->assertEqualsWithDelta(127.04, $dailyRate, 0.01);
    }

    public function testClassCDayIn2024(): void
    {
        $dailyRate = $this->service->getDailyRate(
            statut: 'M',
            classe: 'C',
            option: 100,
            taux: 1,
            year: 2024,
            date: '2024-12-20',
            calculationDate: '2024-12-25'
        );

        $this->assertEqualsWithDelta(240.00, $dailyRate, 0.01);
    }

    public function testClassCDayIn2025ForArretStarting2024(): void
    {
        $dailyRate = $this->service->getDailyRate(
            statut: 'M',
            classe: 'C',
            option: 100,
            taux: 1,
            year: 2024,
            date: '2024-12-20',
            calculationDate: '2025-01-05'
        );

        $this->assertEqualsWithDelta(300.00, $dailyRate, 0.01);
    }

    public function testClassCArretStartingIn2025UsesPassFormula(): void
    {
        $dailyRate = $this->service->getDailyRate(
            statut: 'M',
            classe: 'C',
            option: 100,
            taux: 1,
            year: 2025,
            date: '2025-01-05'
        );

        // PASS formula: (3 * 46368) / 730 = 190.55
        $this->assertEqualsWithDelta(190.55, $dailyRate, 0.01);
    }

    // ========================================================================
    // PASS Formula Tests (2025 Reform)
    // ========================================================================

    public function testPassFormulaClassATaux1(): void
    {
        $dailyRate = $this->service->getDailyRate('M', 'A', 100, 1, 2025, '2025-01-01');

        // (1 * 46368) / 730 = 63.52
        $this->assertEqualsWithDelta(63.52, $dailyRate, 0.01);
    }

    public function testPassFormulaClassATaux2(): void
    {
        $dailyRate = $this->service->getDailyRate('M', 'A', 100, 2, 2025, '2025-01-01');

        // (1 * 46368) / 730 * (2/3) = 42.35
        $this->assertEqualsWithDelta(42.35, $dailyRate, 0.01);
    }

    public function testPassFormulaClassATaux4(): void
    {
        $dailyRate = $this->service->getDailyRate('M', 'A', 100, 4, 2025, '2025-01-01');

        // (1 * 46368) / 730 * 0.5 = 31.76
        $this->assertEqualsWithDelta(31.76, $dailyRate, 0.01);
    }

    public function testPassFormulaClassATaux7(): void
    {
        $dailyRate = $this->service->getDailyRate('M', 'A', 100, 7, 2025, '2025-01-01');

        // (1 * 46368) / 730 * 0.75 = 47.64
        $this->assertEqualsWithDelta(47.64, $dailyRate, 0.01);
    }

    // ========================================================================
    // Option Tests (CCPL/RSPM)
    // ========================================================================

    public function testApplyOptionMultiplierForCCPLIn2024(): void
    {
        $dailyRate = $this->service->getDailyRate(
            statut: 'CCPL',
            classe: 'C',
            option: 25,
            taux: 1,
            year: 2024,
            date: '2024-12-20',
            calculationDate: '2024-12-25'
        );

        // 240 * 0.25 = 60.00
        $this->assertEqualsWithDelta(60.00, $dailyRate, 0.01);
    }

    public function testApplyOptionMultiplierForCCPLIn2025WithPassFormula(): void
    {
        $dailyRate = $this->service->getDailyRate(
            statut: 'CCPL',
            classe: 'A',
            option: 50,
            taux: 1,
            year: 2025,
            date: '2025-01-01'
        );

        // (1 * 46368) / 730 * 0.5 = 31.76
        $this->assertEqualsWithDelta(31.76, $dailyRate, 0.01);
    }

    public function testApplyOptionMultiplierForRSPM(): void
    {
        $dailyRate = $this->service->getDailyRate(
            statut: 'RSPM',
            classe: 'A',
            option: 100,
            taux: 1,
            year: 2024,
            date: '2024-12-20',
            calculationDate: '2024-12-25'
        );

        // Full rate with 100% option
        $this->assertEqualsWithDelta(80.00, $dailyRate, 0.01);
    }

    // ========================================================================
    // Tier Determination Tests
    // ========================================================================

    public function testUseTier1ForTaux1To3(): void
    {
        $rate1 = $this->service->getDailyRate('M', 'A', 100, 1, 2024, '2024-06-15', null, null, null, '2024-06-15');
        $rate2 = $this->service->getDailyRate('M', 'A', 100, 2, 2024, '2024-06-15', null, null, null, '2024-06-15');
        $rate3 = $this->service->getDailyRate('M', 'A', 100, 3, 2024, '2024-06-15', null, null, null, '2024-06-15');

        // All should use taux_a1 (tier 1) = 80.00
        $this->assertEqualsWithDelta(80.00, $rate1, 0.01);
        $this->assertEqualsWithDelta(80.00, $rate2, 0.01);
        $this->assertEqualsWithDelta(80.00, $rate3, 0.01);
    }

    public function testUseTier3ForTaux7To9(): void
    {
        $rate7 = $this->service->getDailyRate('M', 'A', 100, 7, 2024, '2024-06-15', null, null, null, '2024-06-15');

        // Should use taux_a3 (tier 3) = 60.00
        $this->assertEqualsWithDelta(60.00, $rate7, 0.01);
    }

    public function testUseTier2ForTaux4To6WithUsePeriode2True(): void
    {
        $rate4 = $this->service->getDailyRate('M', 'A', 100, 4, 2024, '2024-06-15', 65, true, null, '2024-06-15');

        // Should use taux_a2 (tier 2) = 40.00
        $this->assertEqualsWithDelta(40.00, $rate4, 0.01);
    }

    public function testUseTier3ForTaux4To6WithUsePeriode2False(): void
    {
        $rate4 = $this->service->getDailyRate('M', 'A', 100, 4, 2024, '2024-06-15', 65, false, null, '2024-06-15');

        // Should use taux_a3 (tier 3) = 60.00
        $this->assertEqualsWithDelta(60.00, $rate4, 0.01);
    }

    public function testUseTier2ForTaux4To6WhenAge70Plus(): void
    {
        $rate4 = $this->service->getDailyRate('M', 'A', 100, 4, 2024, '2024-06-15', 70, false, null, '2024-06-15');

        // Should use taux_a2 (tier 2) regardless of usePeriode2 = 40.00
        $this->assertEqualsWithDelta(40.00, $rate4, 0.01);
    }

    // ========================================================================
    // Edge Cases
    // ========================================================================

    public function testLastDayOf2024(): void
    {
        $dailyRate = $this->service->getDailyRate(
            statut: 'M',
            classe: 'A',
            option: 100,
            taux: 1,
            year: 2024,
            date: '2024-12-20',
            calculationDate: '2024-12-31'
        );

        $this->assertEqualsWithDelta(80.00, $dailyRate, 0.01);
    }

    public function testFirstDayOf2025ForArretStarting2024(): void
    {
        $dailyRate = $this->service->getDailyRate(
            statut: 'M',
            classe: 'A',
            option: 100,
            taux: 1,
            year: 2024,
            date: '2024-12-20',
            calculationDate: '2025-01-01'
        );

        $this->assertEqualsWithDelta(100.00, $dailyRate, 0.01);
    }

    public function testWithoutCalculationDateFallsBackToDateEffet(): void
    {
        // Without calculationDate, should use date_effet year
        $dailyRate = $this->service->getDailyRate(
            statut: 'M',
            classe: 'A',
            option: 100,
            taux: 1,
            year: 2024,
            date: '2024-12-20'
            // No calculationDate
        );

        // Should use 2024 rate (year of date_effet)
        $this->assertEqualsWithDelta(80.00, $dailyRate, 0.01);
    }
}
