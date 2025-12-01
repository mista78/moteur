<?php
/**
 * Fix all parameter types in IJCalculator.php
 */

$file = 'src/IJCalculator.php';
$content = file_get_contents($file);

// Define method signatures to fix
$fixes = [
    // getRateForYear
    'public function getRateForYear($year): ?array' =>
    '/**
	 * @return array<string, mixed>|null
	 */
	public function getRateForYear(int $year): ?array',

    // getTrimesterFromDate
    'public function getTrimesterFromDate($date): int' =>
    'public function getTrimesterFromDate(string $date): int',

    // calculateTrimesters
    'public function calculateTrimesters($affiliationDate, $currentDate): int' =>
    'public function calculateTrimesters(string $affiliationDate, string $currentDate): int',

    // sortByDateStartDesc
    'public function sortByDateStartDesc($data): array' =>
    '/**
	 * @param array<int, array<string, mixed>> $data
	 * @return array<int, array<string, mixed>>
	 */
	public function sortByDateStartDesc(array $data): array',

    // getRateForDate
    'public function getRateForDate($date): ?array' =>
    '/**
	 * @return array<string, mixed>|null
	 */
	public function getRateForDate(string $date): ?array',

    // calculateAge
    'public function calculateAge($currentDate, $birthDate): int' =>
    'public function calculateAge(string $currentDate, string $birthDate): int',

    // mergeProlongations
    'public function mergeProlongations($arrets): array' =>
    '/**
	 * @param array<int, array<string, mixed>> $arrets
	 * @return array<string, mixed>
	 */
	public function mergeProlongations(array $arrets): array',

    // calculateDateEffet
    'public function calculateDateEffet($arrets, $birthDate = null, $previousCumulDays = 0): array' =>
    '/**
	 * @param array<int, array<string, mixed>> $arrets
	 * @return array<int, array<string, mixed>>
	 */
	public function calculateDateEffet(array $arrets, ?string $birthDate = null, int $previousCumulDays = 0): array',

    // calculatePayableDays
    'public function calculatePayableDays($arrets, $attestationDate = null, $lastPaymentDate = null, $currentDate = null): array' =>
    '/**
	 * @param array<int, array<string, mixed>> $arrets
	 * @return array<string, mixed>
	 */
	public function calculatePayableDays(array $arrets, ?string $attestationDate = null, ?string $lastPaymentDate = null, ?string $currentDate = null): array',

    // calculateEndPaymentDates
    'public function calculateEndPaymentDates($arrets, $previousCumulDays, $birthDate, $currentDate): array' =>
    '/**
	 * @param array<int, array<string, mixed>> $arrets
	 * @return array<string, string>
	 */
	public function calculateEndPaymentDates(array $arrets, int $previousCumulDays, string $birthDate, string $currentDate): array',

    // calculateAmount
    'public function calculateAmount($data): array' =>
    '/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	public function calculateAmount(array $data): array',

    // splitPaymentByYear
    'public function splitPaymentByYear($startDate, $endDate, $birthDate): array' =>
    'public function splitPaymentByYear(string $startDate, string $endDate, string $birthDate): array',

    // getRate
    'public function getRate($date, $age, $statut, $classe, $option, $taux, $year, $usePeriode2 = null): float' =>
    'public function getRate(string $date, int $age, string $statut, string $classe, float|int $option, int $taux, int $year, ?bool $usePeriode2 = null): float',

    // calculateRevenuAnnuel - note: returns array, not float!
    'public function calculateRevenuAnnuel($classe, $revenu = null): float' =>
    '/**
	 * @return array<string, mixed>
	 */
	public function calculateRevenuAnnuel(string $classe, ?float $revenu = null): array',
];

foreach ($fixes as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

file_put_contents($file, $content);
echo "Fixed IJCalculator.php parameter types\n";
