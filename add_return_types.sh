#!/bin/bash

# Add return types to IJCalculator methods
file="src/IJCalculator.php"

# Methods that return int
sed -i 's/\(public function calculateAge([^)]*)\)$/\1: int/' "$file"
sed -i 's/\(public function getTrimesterFromDate([^)]*)\)$/\1: int/' "$file"
sed -i 's/\(public function calculateTrimesters([^)]*)\)$/\1: int/' "$file"
sed -i 's/\(private function determineTauxNumber([^)]*)\)$/\1: int/' "$file"

# Methods that return array
sed -i 's/\(public function mergeProlongations([^)]*)\)$/\1: array/' "$file"
sed -i 's/\(public function calculateDateEffet([^)]*)\)$/\1: array/' "$file"
sed -i 's/\(public function calculatePayableDays([^)]*)\)$/\1: array/' "$file"
sed -i 's/\(public function calculateEndPaymentDates([^)]*)\)$/\1: array/' "$file"
sed -i 's/\(public function calculateAmount([^)]*)\)$/\1: array/' "$file"
sed -i 's/\(public function validateAndCorrectOption([^)]*)\)$/\1: array/' "$file"
sed -i 's/\(private function calculateMontantByAgeWithDetails([^)]*)\)$/\1: array/' "$file"
sed -i 's/\(public function generateDailyBreakdown([^)]*)\)$/\1: array/' "$file"
sed -i 's/\(public function sortByDateStartDesc([^)]*)\)$/\1: array/' "$file"
sed -i 's/\(public function splitPaymentByYear([^)]*)\)$/\1: array/' "$file"

# Methods that return ?array
sed -i 's/\(public function getRateForYear([^)]*)\)$/\1: ?array/' "$file"
sed -i 's/\(public function getRateForDate([^)]*)\)$/\1: ?array/' "$file"

# Methods that return float
sed -i 's/\(public function getRate([^)]*)\)$/\1: float/' "$file"
sed -i 's/\(public function calculateRevenuAnnuel([^)]*)\)$/\1: float/' "$file"

# Methods that return void
sed -i 's/\(public function setPassValue([^)]*)\)$/\1: void/' "$file"
sed -i 's/\(private function loadRates([^)]*)\)$/\1: void/' "$file"

echo "Added return types to $file"

# Apply same to AmountCalculationService
file="src/Services/AmountCalculationService.php"
sed -i 's/\(public function splitPaymentByYear([^)]*)\)$/\1: array/' "$file"
sed -i 's/\(public function truncatePaymentDetails([^)]*)\)$/\1: array/' "$file"
sed -i 's/\(public function determineClasseForYear([^)]*)\)$/\1: ?string/' "$file"
sed -i 's/\(public function getRevenuForYear([^)]*)\)$/\1: ?float/' "$file"

echo "Added return types to AmountCalculationService.php"

echo "Done!"
