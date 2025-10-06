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

            echo json_encode([
                'success' => true,
                'data' => $mockData
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
