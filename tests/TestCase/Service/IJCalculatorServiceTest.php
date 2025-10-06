<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\IJCalculatorService;
use Cake\TestSuite\TestCase;

/**
 * IJCalculatorService Test Case
 */
class IJCalculatorServiceTest extends TestCase
{
    private IJCalculatorService $calculator;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $csvPath = CONFIG . 'taux.csv';
        $this->calculator = new IJCalculatorService($csvPath);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->calculator);
        parent::tearDown();
    }

    /**
     * Test calculateAmount with mock.json
     *
     * @return void
     */
    public function testCalculateAmountMock1(): void
    {
        $mockData = json_decode(
            file_get_contents(TESTS . 'Fixture' . DS . 'mock.json'),
            true
        );

        $result = $this->calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1989-09-26',
            'current_date' => '2024-09-09',
            'attestation_date' => '2024-01-31',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => false,
        ]);

        $this->assertEquals(750.60, $result['montant']);
        $this->assertEquals(5, $result['nb_jours']);
    }

    /**
     * Test calculateAmount with mock2.json
     *
     * @return void
     */
    public function testCalculateAmountMock2(): void
    {
        $mockData = json_decode(
            file_get_contents(TESTS . 'Fixture' . DS . 'mock2.json'),
            true
        );

        $result = $this->calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1989-09-26',
            'current_date' => '2024-09-09',
            'attestation_date' => '2023-03-14',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => false,
        ]);

        $this->assertEquals(17318.92, $result['montant']);
    }

    /**
     * Test calculateAmount with mock3.json
     *
     * @return void
     */
    public function testCalculateAmountMock3(): void
    {
        $mockData = json_decode(
            file_get_contents(TESTS . 'Fixture' . DS . 'mock3.json'),
            true
        );

        $result = $this->calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1966-09-09',
            'current_date' => '2024-09-09',
            'attestation_date' => '2022-03-28',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => false,
        ]);

        $this->assertEquals(41832.60, $result['montant']);
    }

    /**
     * Test calculateAmount with mock4.json
     *
     * @return void
     */
    public function testCalculateAmountMock4(): void
    {
        $mockData = json_decode(
            file_get_contents(TESTS . 'Fixture' . DS . 'mock4.json'),
            true
        );

        $result = $this->calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1966-09-09',
            'current_date' => '2024-09-09',
            'attestation_date' => '2022-03-28',
            'last_payment_date' => '2023-12-12',
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => false,
        ]);

        $this->assertEquals(37875.88, $result['montant']);
    }

    /**
     * Test calculateAmount with mock5.json
     *
     * @return void
     */
    public function testCalculateAmountMock5(): void
    {
        $mockData = json_decode(
            file_get_contents(TESTS . 'Fixture' . DS . 'mock5.json'),
            true
        );

        $result = $this->calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1966-09-09',
            'current_date' => '2024-09-09',
            'attestation_date' => '2022-03-28',
            'last_payment_date' => '2023-04-22',
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => false,
        ]);

        $this->assertEquals(34276.56, $result['montant']);
    }

    /**
     * Test calculateAmount with mock6.json
     *
     * @return void
     */
    public function testCalculateAmountMock6(): void
    {
        $mockData = json_decode(
            file_get_contents(TESTS . 'Fixture' . DS . 'mock6.json'),
            true
        );

        $result = $this->calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1966-09-09',
            'current_date' => '2024-09-09',
            'attestation_date' => '2022-03-28',
            'last_payment_date' => '2023-04-22',
            'affiliation_date' => '2022-03-23',
            'nb_trimestres' => 2,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => false,
        ]);

        $this->assertEquals(31412.61, $result['montant']);
    }

    /**
     * Test calculateAmount with mock7.json - CCPL with patho anterior
     *
     * @return void
     */
    public function testCalculateAmountMock7(): void
    {
        $mockData = json_decode(
            file_get_contents(TESTS . 'Fixture' . DS . 'mock7.json'),
            true
        );

        $result = $this->calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'CCPL',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1989-09-26',
            'current_date' => '2024-09-09',
            'attestation_date' => '2022-12-12',
            'last_payment_date' => null,
            'affiliation_date' => '2022-12-01',
            'nb_trimestres' => 4,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => true,
            'first_pathology_stop_date' => '2022-12-12',
        ]);

        $this->assertEquals(74331.79, $result['montant']);
    }

    /**
     * Test calculateAmount with mock8.json
     *
     * @return void
     */
    public function testCalculateAmountMock8(): void
    {
        $mockData = json_decode(
            file_get_contents(TESTS . 'Fixture' . DS . 'mock8.json'),
            true
        );

        $result = $this->calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1989-09-26',
            'current_date' => '2024-09-09',
            'attestation_date' => '2024-01-01',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => false,
        ]);

        $this->assertEquals(19291.28, $result['montant']);
    }

    /**
     * Test calculateAmount with mock9.json - Age transition during payment
     *
     * @return void
     */
    public function testCalculateAmountMock9(): void
    {
        $mockData = json_decode(
            file_get_contents(TESTS . 'Fixture' . DS . 'mock9.json'),
            true
        );

        $result = $this->calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1953-01-22',
            'current_date' => '2024-05-21',
            'attestation_date' => '2022-02-22',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => false,
        ]);

        $this->assertEquals(53467.98, $result['montant']);
        $this->assertEquals(730, $result['nb_jours']);
    }

    /**
     * Test calculateAmount with mock10.json - Period 2 intermediate rate
     *
     * @return void
     */
    public function testCalculateAmountMock10(): void
    {
        $mockData = json_decode(
            file_get_contents(TESTS . 'Fixture' . DS . 'mock10.json'),
            true
        );

        $result = $this->calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1961-10-14',
            'current_date' => '2025-01-31',
            'attestation_date' => '2022-11-09',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => false,
        ]);

        $this->assertEquals(51744.25, $result['montant']);
        $this->assertEquals(725, $result['nb_jours']);
    }

    /**
     * Test calculateAmount with mock11.json
     *
     * @return void
     */
    public function testCalculateAmountMock11(): void
    {
        $mockData = json_decode(
            file_get_contents(TESTS . 'Fixture' . DS . 'mock11.json'),
            true
        );

        $result = $this->calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1989-09-26',
            'current_date' => '2024-09-09',
            'attestation_date' => '2023-11-25',
            'last_payment_date' => null,
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => false,
        ]);

        $this->assertEquals(10245.69, $result['montant']);
    }

    /**
     * Test calculateAmount with mock12.json
     *
     * @return void
     */
    public function testCalculateAmountMock12(): void
    {
        $mockData = json_decode(
            file_get_contents(TESTS . 'Fixture' . DS . 'mock12.json'),
            true
        );

        $result = $this->calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1989-09-26',
            'current_date' => '2024-09-09',
            'attestation_date' => '2023-11-25',
            'last_payment_date' => '2024-03-01',
            'affiliation_date' => null,
            'nb_trimestres' => 8,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => false,
        ]);

        $this->assertEquals(8330.25, $result['montant']);
    }

    /**
     * Test age calculation
     *
     * @return void
     */
    public function testCalculateAge(): void
    {
        $age = $this->calculator->calculateAge('2024-01-01', '1989-09-26');
        $this->assertEquals(34, $age);

        $age = $this->calculator->calculateAge('2023-09-25', '1989-09-26');
        $this->assertEquals(33, $age);

        $age = $this->calculator->calculateAge('2023-09-26', '1989-09-26');
        $this->assertEquals(34, $age);
    }

    /**
     * Test insufficient quarters
     *
     * @return void
     */
    public function testInsufficientQuarters(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('trimestres de cotisation');

        $this->calculator->calculateAmount([
            'arrets' => [],
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            'birth_date' => '1989-09-26',
            'current_date' => '2024-09-09',
            'attestation_date' => '2024-01-31',
            'nb_trimestres' => 5,
            'previous_cumul_days' => 0,
            'prorata' => 1,
            'patho_anterior' => false,
        ]);
    }
}
