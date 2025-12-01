<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\TauxDeterminationService;

class TauxDeterminationServiceTest extends TestCase
{
    private TauxDeterminationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TauxDeterminationService();
    }

    public function testShouldReturnHistoricalRateIfProvided(): void
    {
        $taux = $this->service->determineTauxNumber(50, 20, true, 5);
        $this->assertEquals(5, $taux);
    }

    public function testShouldReturnTaux1ForAgeLessThan62WithoutPathology(): void
    {
        $taux = $this->service->determineTauxNumber(50, 30, false, null);
        $this->assertEquals(1, $taux);
    }

    public function testShouldReturnTaux7ForAge62To69WithoutPathology(): void
    {
        $taux = $this->service->determineTauxNumber(65, 30, false, null);
        $this->assertEquals(7, $taux);
    }

    public function testShouldReturnTaux4ForAge70OrMoreWithoutPathology(): void
    {
        $taux = $this->service->determineTauxNumber(70, 30, false, null);
        $this->assertEquals(4, $taux);
    }

    public function testShouldReturnTaux1ForAgeLessThan62With24OrMoreTrimestresFullRate(): void
    {
        $taux = $this->service->determineTauxNumber(50, 24, true, null);
        $this->assertEquals(1, $taux);
    }

    public function testShouldReturnTaux2ForAgeLessThan62WithPathologyAnd8To15Trimestres(): void
    {
        $taux = $this->service->determineTauxNumber(50, 10, true, null);
        $this->assertEquals(2, $taux); // Reduction 1/3
    }

    public function testShouldReturnTaux3ForAgeLessThan62WithPathologyAnd16To23Trimestres(): void
    {
        $taux = $this->service->determineTauxNumber(50, 20, true, null);
        $this->assertEquals(3, $taux); // Reduction 2/3
    }

    public function testShouldReturnTaux8ForAge62To69WithPathologyAnd8To15Trimestres(): void
    {
        $taux = $this->service->determineTauxNumber(65, 10, true, null);
        $this->assertEquals(8, $taux); // Reduction 1/3
    }

    public function testShouldReturnTaux9ForAge62To69WithPathologyAnd16To23Trimestres(): void
    {
        $taux = $this->service->determineTauxNumber(65, 20, true, null);
        $this->assertEquals(9, $taux); // Reduction 2/3
    }

    public function testShouldReturnTaux5ForAge70OrMoreWithPathologyAnd8To15Trimestres(): void
    {
        $taux = $this->service->determineTauxNumber(70, 10, true, null);
        $this->assertEquals(5, $taux); // Reduction 1/3
    }

    public function testShouldReturnTaux6ForAge70OrMoreWithPathologyAnd16To23Trimestres(): void
    {
        $taux = $this->service->determineTauxNumber(70, 20, true, null);
        $this->assertEquals(6, $taux); // Reduction 2/3
    }

    public function testShouldDetermineClassAForRevenueLessThan1Pass(): void
    {
        $this->service->setPassValue(47000);
        $classe = $this->service->determineClasse(30000, '2024-01-01', false);
        $this->assertEquals('A', $classe);
    }

    public function testShouldDetermineClassBForRevenueBetween1And3Pass(): void
    {
        $this->service->setPassValue(47000);
        $classe = $this->service->determineClasse(100000, '2024-01-01', false);
        $this->assertEquals('B', $classe);
    }

    public function testShouldDetermineClassCForRevenueMoreThan3Pass(): void
    {
        $this->service->setPassValue(47000);
        $classe = $this->service->determineClasse(150000, '2024-01-01', false);
        $this->assertEquals('C', $classe);
    }

    public function testShouldReturnClassAIfTaxedByOffice(): void
    {
        $classe = $this->service->determineClasse(150000, '2024-01-01', true);
        $this->assertEquals('A', $classe);
    }

    public function testShouldReturnClassAIfRevenueNotProvided(): void
    {
        $classe = $this->service->determineClasse(null, '2024-01-01', false);
        $this->assertEquals('A', $classe);
    }
}
