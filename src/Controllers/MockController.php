<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseFormatter;
use Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Mock Controller
 * Handles mock data endpoints for testing
 */
class MockController
{
    private string $mocksPath;
    private LoggerInterface $logger;

    /**
     * @param array<string, mixed> $settings
     * @param LoggerInterface $logger
     */
    public function __construct(array $settings, LoggerInterface $logger)
    {
        $this->mocksPath = $settings['paths']['mocks'];
        $this->logger = $logger;
    }

    /**
     * GET /api/mocks
     * List all available mock files
     */
    #[OA\Get(
        path: "/api/mocks",
        summary: "List all mock files",
        description: "Returns a list of available mock data files for testing",
        tags: ["mocks"],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of mock files",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(type: "string", example: "mock1.json")
                        )
                    ]
                )
            )
        ]
    )]
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $mockFiles = glob($this->mocksPath . '/mock*.json');

            // Check if glob succeeded
            if ($mockFiles === false) {
                return ResponseFormatter::error($response, 'Failed to read mock files');
            }

            // Extract just the filenames
            $mockFiles = array_map('basename', $mockFiles);
            sort($mockFiles);

            return ResponseFormatter::success($response, $mockFiles);

        } catch (Exception $e) {
            $this->logger->error('Error listing mocks', ['error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * GET /api/mocks/{file}
     * Load a specific mock file
     * @param array<string, mixed> $args
     */
    #[OA\Get(
        path: "/api/mocks/{file}",
        summary: "Load a specific mock file",
        description: "Load mock data for testing. File can be specified with or without .json extension",
        tags: ["mocks"],
        parameters: [
            new OA\Parameter(
                name: "file",
                in: "path",
                required: true,
                description: "Mock file name (e.g., 'mock1' or 'mock1.json')",
                schema: new OA\Schema(type: "string", example: "mock1")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Mock data loaded",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "data", type: "object", description: "Mock calculation input data"),
                                new OA\Property(property: "config", type: "object", description: "Test configuration with expected values", nullable: true)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Invalid file name"),
            new OA\Response(response: 404, description: "Mock file not found")
        ]
    )]
    public function load(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $mockFile = $args['file'] ?? 'mock';

            // Add .json extension if not present
            if (!str_ends_with($mockFile, '.json')) {
                $mockFile .= '.json';
            }

            // Validate filename to prevent directory traversal
            $mockFile = basename($mockFile);
            if (!preg_match('/^mock[0-9]*\.json$/', $mockFile)) {
                return ResponseFormatter::error($response, 'Invalid mock file name', 400);
            }

            $filePath = $this->mocksPath . '/' . $mockFile;

            if (!file_exists($filePath)) {
                return ResponseFormatter::notFound($response, "Mock file not found: $mockFile");
            }

            $fileContents = file_get_contents($filePath);
            if ($fileContents === false) {
                return ResponseFormatter::error($response, 'Failed to read mock file');
            }

            $mockData = json_decode($fileContents, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ResponseFormatter::error($response, 'Invalid JSON in mock file');
            }

            // Load test configuration if available
            $testConfig = $this->loadTestConfig($mockFile);

            return ResponseFormatter::success($response, [
                'data' => $mockData,
                'config' => $testConfig
            ]);

        } catch (Exception $e) {
            $this->logger->error('Error loading mock', ['file' => $mockFile ?? 'unknown', 'error' => $e->getMessage()]);
            return ResponseFormatter::error($response, $e->getMessage());
        }
    }

    /**
     * Load test configuration for a mock file
     * This extracts expected values from test_mocks.php if available
     *
     * @param string $mockFile
     * @return array<string, mixed>|null
     */
    private function loadTestConfig(string $mockFile): ?array
    {
        $testMocksFile = dirname($this->mocksPath) . '/tests/Integration/test_mocks.php';

        if (!file_exists($testMocksFile)) {
            // Try old location
            $testMocksFile = dirname($this->mocksPath) . '/Tests/test_mocks.php';
        }

        if (!file_exists($testMocksFile)) {
            return null;
        }

        $testMocksContent = file_get_contents($testMocksFile);

        if ($testMocksContent === false) {
            return null;
        }

        // Find the configuration for this mock file
        $mockKey = preg_quote($mockFile, '/');
        $startPos = strpos($testMocksContent, "'$mockFile'");

        if ($startPos === false) {
            return null;
        }

        // Find the matching closing bracket
        $bracketCount = 0;
        $inArray = false;
        $configStart = strpos($testMocksContent, '[', $startPos);

        if ($configStart === false) {
            return null;
        }

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

        // Extract configuration values
        $config = [];

        // Extract simple values using regex
        $patterns = [
            'statut' => "/'statut'\s*=>\s*'([^']*)'/",
            'classe' => "/'classe'\s*=>\s*'([^']*)'/",
            'option' => "/'option'\s*=>\s*(\d+)/",
            'pass_value' => "/'pass_value'\s*=>\s*(\d+)/",
            'nb_trimestres' => "/'nb_trimestres'\s*=>\s*(\d+)/",
            'previous_cumul_days' => "/'previous_cumul_days'\s*=>\s*(\d+)/",
            'prorata' => "/'prorata'\s*=>\s*([\d.]+)/",
            'patho_anterior' => "/'patho_anterior'\s*=>\s*(\d+)/",
            'expected' => "/'expected'\s*=>\s*([\d.]+)/",
            'nbe_jours' => "/'nbe_jours'\s*=>\s*(\d+)/",
            'forced_rate' => "/['\"]forced_rate['\"]\s*=>\s*([\d.]+)/",
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $configStr, $m)) {
                $config[$key] = $m[1];
            }
        }

        // Handle date fields that might be null
        $dateFields = ['birth_date', 'attestation_date', 'affiliation_date'];
        foreach ($dateFields as $field) {
            if (preg_match("/'$field'\s*=>\s*null/", $configStr)) {
                $config[$field] = null;
            } elseif (preg_match("/'$field'\s*=>\s*[\"']([^\"']*)[\"']/", $configStr, $m)) {
                $config[$field] = $m[1];
            }
        }

        return $config;
    }
}
