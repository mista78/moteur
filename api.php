<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'IJCalculator.php';

$calculator = new IJCalculator('taux.csv');

$endpoint = $_GET['endpoint'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($endpoint) {
        case 'date-effet':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['arrets']) || !is_array($input['arrets'])) {
                throw new Exception('Missing or invalid arrets parameter');
            }

            $arrets = $input['arrets'];
            $birthDate = $input['birth_date'] ?? null;
            $previousCumulDays = $input['previous_cumul_days'] ?? 0;

            $result = $calculator->calculateDateEffet($arrets, $birthDate, $previousCumulDays);

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;

        case 'end-payment':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['arrets']) || !is_array($input['arrets'])) {
                throw new Exception('Missing or invalid arrets parameter');
            }

            $arrets = $input['arrets'];
            $previousCumulDays = $input['previous_cumul_days'] ?? 0;
            $birthDate = $input['birth_date'] ?? null;
            $currentDate = $input['current_date'] ?? date('Y-m-d');

            if (!$birthDate) {
                throw new Exception('Missing birth_date parameter');
            }

            // First calculate date effet for each arrêt
            $arretsWithEffet = $calculator->calculateDateEffet($arrets, $birthDate, $previousCumulDays);

            // Then calculate end payment dates
            $result = $calculator->calculateEndPaymentDates(
                $arretsWithEffet,
                $previousCumulDays,
                $birthDate,
                $currentDate
            );

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;

        case 'calculate':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['arrets']) || !is_array($input['arrets'])) {
                throw new Exception('Missing or invalid arrets parameter');
            }

            // Set PASS value if provided
            if (isset($input['pass_value'])) {
                $calculator->setPassValue($input['pass_value']);
            }

            // Auto-determine class if revenu_n_moins_2 provided but classe is not
            if (isset($input['revenu_n_moins_2']) && !isset($input['classe'])) {
                $revenuNMoins2 = (float)$input['revenu_n_moins_2'];
                $taxeOffice = isset($input['taxe_office']) ? (bool)$input['taxe_office'] : false;
                $dateOuvertureDroits = $input['date_ouverture_droits'] ?? null;

                $input['classe'] = $calculator->determineClasse($revenuNMoins2, $dateOuvertureDroits, $taxeOffice);
            }

            $result = $calculator->calculateAmount($input);

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;

        case 'revenu':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['classe'])) {
                throw new Exception('Missing classe parameter');
            }

            $classe = $input['classe'];
            $nbPass = $input['nb_pass'] ?? null;

            // Set PASS value if provided
            if (isset($input['pass_value'])) {
                $calculator->setPassValue($input['pass_value']);
            }

            $result = $calculator->calculateRevenuAnnuel($classe, $nbPass);

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;

        case 'determine-classe':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }

            $input = json_decode(file_get_contents('php://input'), true);

            $revenuNMoins2 = isset($input['revenu_n_moins_2']) ? (float)$input['revenu_n_moins_2'] : null;
            $dateOuvertureDroits = $input['date_ouverture_droits'] ?? null;
            $taxeOffice = isset($input['taxe_office']) ? (bool)$input['taxe_office'] : false;

            // Set PASS value if provided
            if (isset($input['pass_value'])) {
                $calculator->setPassValue($input['pass_value']);
            }

            $classe = $calculator->determineClasse($revenuNMoins2, $dateOuvertureDroits, $taxeOffice);

            echo json_encode([
                'success' => true,
                'data' => [
                    'classe' => $classe,
                    'revenu_n_moins_2' => $revenuNMoins2,
                    'taxe_office' => $taxeOffice,
                    'pass_value' => $input['pass_value'] ?? 47000
                ]
            ]);
            break;

        case 'list-mocks':
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }

            $mockFiles = glob('mock*.json');
            sort($mockFiles);

            echo json_encode([
                'success' => true,
                'data' => $mockFiles
            ]);
            break;

        case 'load-mock':
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }

            $mockFile = $_GET['file'] ?? 'mock.json';

            // Validate filename to prevent directory traversal
            $mockFile = basename($mockFile);
            if (!preg_match('/^mock[0-9]*\.json$/', $mockFile)) {
                throw new Exception('Invalid mock file name');
            }

            if (!file_exists($mockFile)) {
                throw new Exception("Mock file not found: $mockFile");
            }

            $mockData = json_decode(file_get_contents($mockFile), true);

            // Load test configuration if available
            $testConfig = null;
            if (file_exists('test_mocks.php')) {
                // Read test_mocks.php and extract configuration
                $testMocksContent = file_get_contents('test_mocks.php');

                // Find the start of this mock's configuration
                $mockKey = preg_quote($mockFile, '/');
                $startPos = strpos($testMocksContent, "'$mockFile'");

                if ($startPos !== false) {
                    // Find the matching closing bracket for this mock's array
                    $bracketCount = 0;
                    $inArray = false;
                    $configStart = strpos($testMocksContent, '[', $startPos);
                    $configEnd = $configStart;

                    for ($i = $configStart; $i < strlen($testMocksContent); $i++) {
                        $char = $testMocksContent[$i];
                        if ($char === '[') {
                            $bracketCount++;
                            $inArray = true;
                        } elseif ($char === ']') {
                            $bracketCount--;
                            if ($bracketCount === 0 && $inArray) {
                                $configEnd = $i;
                                break;
                            }
                        }
                    }

                    $configStr = substr($testMocksContent, $configStart + 1, $configEnd - $configStart - 1);

                    // Extract configuration values using regex
                    $config = [];

                    // Extract simple values
                    if (preg_match("/'statut'\s*=>\s*'([^']*)'/", $configStr, $m)) $config['statut'] = $m[1];
                    if (preg_match("/'classe'\s*=>\s*'([^']*)'/", $configStr, $m)) $config['classe'] = strtoupper($m[1]);
                    if (preg_match("/'option'\s*=>\s*(\d+)/", $configStr, $m)) $config['option'] = (float)$m[1];
                    if (preg_match("/'pass_value'\s*=>\s*(\d+)/", $configStr, $m)) $config['pass_value'] = (int)$m[1];
                    if (preg_match("/'birth_date'\s*=>\s*null/", $configStr)) {
                        $config['birth_date'] = null;
                    } elseif (preg_match("/'birth_date'\s*=>\s*[\"']([^\"']*)[\"']/", $configStr, $m)) {
                        $config['birth_date'] = $m[1];
                    }
                    if (preg_match("/'attestation_date'\s*=>\s*null/", $configStr)) {
                        $config['attestation_date'] = null;
                    } elseif (preg_match("/'attestation_date'\s*=>\s*[\"']([^\"']*)[\"']/", $configStr, $m)) {
                        $config['attestation_date'] = $m[1];
                    }
                    if (preg_match("/'affiliation_date'\s*=>\s*null/", $configStr)) {
                        $config['affiliation_date'] = null;
                    } elseif (preg_match("/'affiliation_date'\s*=>\s*[\"']([^\"']*)[\"']/", $configStr, $m)) {
                        $config['affiliation_date'] = $m[1];
                    }
                    if (preg_match("/'nb_trimestres'\s*=>\s*(\d+)/", $configStr, $m)) $config['nb_trimestres'] = (int)$m[1];
                    if (preg_match("/'previous_cumul_days'\s*=>\s*(\d+)/", $configStr, $m)) $config['previous_cumul_days'] = (int)$m[1];
                    if (preg_match("/'prorata'\s*=>\s*([\d.]+)/", $configStr, $m)) $config['prorata'] = (float)$m[1];
                    if (preg_match("/'patho_anterior'\s*=>\s*(\d+)/", $configStr, $m)) $config['patho_anterior'] = (int)$m[1];
                    if (preg_match("/'expected'\s*=>\s*([\d.]+)/", $configStr, $m)) $config['expected'] = (float)$m[1];
                    if (preg_match("/'nbe_jours'\s*=>\s*(\d+)/", $configStr, $m)) $config['nbe_jours'] = (int)$m[1];
                    if (preg_match("/['\"]forced_rate['\"]\s*=>\s*([\d.]+)/", $configStr, $m)) $config['forced_rate'] = (float)$m[1];

                    $testConfig = $config;
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $mockData,
                'config' => $testConfig
            ]);
            break;

        default:
            throw new Exception('Unknown endpoint');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
