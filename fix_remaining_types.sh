#!/bin/bash

# Fix property types
sed -i 's/^\t\(private\|public\) \$rates = \[\];$/\t\/** @var array<int, array<string, mixed>> *\/\n\t\1 array $rates = [];/' src/IJCalculator.php
sed -i 's/^\t\(private\|public\) \$passValue;$/\t\/** @var float|int|null *\/\n\t\1 $passValue;/' src/IJCalculator.php

# Fix common method return types - using @ to avoid sed delimiter issues
sed -i 's@public function calculateAge(\([^)]*\))$@public function calculateAge(\1): int@' src/IJCalculator.php
sed -i 's@public function getTrimesterFromDate(\([^)]*\))$@public function getTrimesterFromDate(\1): int@' src/IJCalculator.php
sed -i 's@public function calculateTrimesters(\([^)]*\))$@public function calculateTrimesters(\1): int@' src/IJCalculator.php
sed -i 's@public function getRateForYear(\([^)]*\))$@public function getRateForYear(\1): ?array@' src/IJCalculator.php
sed -i 's@public function getRateForDate(\([^)]*\))$@public function getRateForDate(\1): ?array@' src/IJCalculator.php
sed -i 's@public function calculateRevenuAnnuel(\([^)]*\))$@public function calculateRevenuAnnuel(\1): float@' src/IJCalculator.php
sed -i 's@public function getRate(\([^)]*\))$@public function getRate(\1): float@' src/IJCalculator.php

# Add type hints to common parameters
sed -i 's@function \([a-zA-Z]*\)(\$year)@function \1(int $year)@g' src/IJCalculator.php
sed -i 's@function \([a-zA-Z]*\)(\$age,@function \1(int $age,@g' src/IJCalculator.php
sed -i 's@function \([a-zA-Z]*\)(\$value)@function \1(float|int $value)@g' src/IJCalculator.php
sed -i 's@setPassValue(\$value)@setPassValue(float|int $value)@g' src/IJCalculator.php

echo "Applied fixes to IJCalculator.php"
