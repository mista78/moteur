<?php

declare(strict_types=1);

namespace Tests;

use App\Services\RateService;
use PHPUnit\Framework\TestCase;

class RateServiceTest extends TestCase
{
    private RateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RateService(__DIR__ . '/../data/taux.csv');
    }

    public function testLoadRatesFromCsv(): void
    {
        $rate2024 = $this->service->getRateForYear(2024);

        $this->assertArrayHasKey('taux_a1', $rate2024);
        $this->assertArrayHasKey('taux_b1', $rate2024);
        $this->assertArrayHasKey('taux_c1', $rate2024);
    }

    public function testGetRateForSpecificYear(): void
    {
        $rate2024 = $this->service->getRateForYear(2024);

        $this->assertEquals('75.06', $rate2024['taux_a1']);
    }

    public function testGetRateForSpecificDate(): void
    {
        $rate = $this->service->getRateForDate('2024-06-15');

        $this->assertEquals('75.06', $rate['taux_a1']);
    }

    public function testCalculateDailyRateForMedecinClassATaux1(): void
    {
        $dailyRate = $this->service->getDailyRate('M', 'A', 100, 1, 2024);

        $this->assertEqualsWithDelta(75.06, $dailyRate, 0.01);
    }

    public function testCalculateDailyRateForMedecinClassBTaux1(): void
    {
        $dailyRate = $this->service->getDailyRate('M', 'B', 100, 1, 2024);

        $this->assertEqualsWithDelta(112.59, $dailyRate, 0.01);
    }

    public function testCalculateDailyRateForMedecinClassCTaux1(): void
    {
        $dailyRate = $this->service->getDailyRate('M', 'C', 100, 1, 2024);

        $this->assertEqualsWithDelta(150.12, $dailyRate, 0.01);
    }

    public function testApplyOptionMultiplierForCCPL(): void
    {
        $dailyRate = $this->service->getDailyRate('CCPL', 'C', 25, 1, 2024);

        // 150.12 * 0.25 = 37.53
        $this->assertEqualsWithDelta(37.53, $dailyRate, 0.01);
    }

    public function testApplyOptionMultiplierForRSPM(): void
    {
        $dailyRate = $this->service->getDailyRate('RSPM', 'A', 100, 1, 2024);

        // Full rate with 100% option
        $this->assertEqualsWithDelta(75.06, $dailyRate, 0.01);
    }

    public function testUseTier1ForTaux1To3(): void
    {
        $rate1 = $this->service->getDailyRate('M', 'A', 100, 1, 2024);
        $rate2 = $this->service->getDailyRate('M', 'A', 100, 2, 2024);
        $rate3 = $this->service->getDailyRate('M', 'A', 100, 3, 2024);

        // All should use taux_a1 (tier 1)
        $this->assertEquals($rate2, $rate1);
        $this->assertEquals($rate3, $rate2);
    }

    public function testUseTier3ForTaux7To9(): void
    {
        $rate7 = $this->service->getDailyRate('M', 'A', 100, 7, 2024);

        // Should use taux_a3 (tier 3) = 56.3
        $this->assertEqualsWithDelta(56.3, $rate7, 0.01);
    }

    public function testUseTier2ForTaux4To6WithUsePeriode2True(): void
    {
        $rate4 = $this->service->getDailyRate('M', 'A', 100, 4, 2024, null, 65, true);

        // Should use taux_a2 (tier 2) = 38.3
        $this->assertEqualsWithDelta(38.3, $rate4, 0.01);
    }

    public function testUseTier3ForTaux4To6WithUsePeriode2False(): void
    {
        $rate4 = $this->service->getDailyRate('M', 'A', 100, 4, 2024, null, 65, false);

        // Should use taux_a3 (tier 3) = 56.3
        $this->assertEqualsWithDelta(56.3, $rate4, 0.01);
    }

    public function testUseTier2ForTaux4To6WhenAge70Plus(): void
    {
        $rate4 = $this->service->getDailyRate('M', 'A', 100, 4, 2024, null, 70, false);

        // Should use taux_a2 (tier 2) regardless of usePeriode2
        $this->assertEqualsWithDelta(38.3, $rate4, 0.01);
    }
}
