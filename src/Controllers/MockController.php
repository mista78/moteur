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
 * Contrôleur Mock
 * Gère les endpoints de données de test
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
     * Lister tous les fichiers mock disponibles
     */
    #[OA\Get(
        path: "/api/mocks",
        summary: "Lister les fichiers de test",
        description: "Retourne la liste des fichiers de données de test (mocks) disponibles pour les tests",
        tags: ["mocks"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des fichiers de test",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            description: "Tableau des noms de fichiers",
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

            // Vérifier si glob a réussi
            if ($mockFiles === false) {
                return ResponseFormatter::error($response, 'Failed to read mock files');
            }

            // Extraire seulement les noms de fichiers
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
     * Charger un fichier mock spécifique
     * @param array<string, mixed> $args
     */
    #[OA\Get(
        path: "/api/mocks/{file}",
        summary: "Charger un fichier de test spécifique",
        description: "Charge les données de test pour les calculs. Le fichier peut être spécifié avec ou sans l'extension .json",
        tags: ["mocks"],
        parameters: [
            new OA\Parameter(
                name: "file",
                in: "path",
                required: true,
                description: "Nom du fichier de test (ex: 'mock1' ou 'mock1.json')",
                schema: new OA\Schema(type: "string", example: "mock1")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Données de test chargées avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "data", type: "object", description: "Données d'entrée pour le calcul"),
                                new OA\Property(property: "config", type: "object", description: "Configuration de test avec les valeurs attendues", nullable: true)
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Nom de fichier invalide"),
            new OA\Response(response: 404, description: "Fichier de test non trouvé")
        ]
    )]
    public function load(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $mockFile = $args['file'] ?? 'mock';

            // Ajouter l'extension .json si absente
            if (!str_ends_with($mockFile, '.json')) {
                $mockFile .= '.json';
            }

            // Valider le nom de fichier pour éviter la traversée de répertoires
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

            // Charger la configuration de test si disponible
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
     * Charger la configuration de test pour un fichier mock
     * Cela extrait les valeurs attendues depuis test_mocks.php si disponible
     *
     * @param string $mockFile
     * @return array<string, mixed>|null
     */
    private function loadTestConfig(string $mockFile): ?array
    {
        $testMocksFile = dirname($this->mocksPath) . '/tests/Integration/test_mocks.php';

        if (!file_exists($testMocksFile)) {
            // Essayer l'ancien emplacement
            $testMocksFile = dirname($this->mocksPath) . '/Tests/test_mocks.php';
        }

        if (!file_exists($testMocksFile)) {
            return null;
        }

        $testMocksContent = file_get_contents($testMocksFile);

        if ($testMocksContent === false) {
            return null;
        }

        // Trouver la configuration pour ce fichier mock
        $mockKey = preg_quote($mockFile, '/');
        $startPos = strpos($testMocksContent, "'$mockFile'");

        if ($startPos === false) {
            return null;
        }

        // Trouver le crochet de fermeture correspondant
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

        // Extraire les valeurs de configuration
        $config = [];

        // Extraire les valeurs simples en utilisant regex
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

        // Gérer les champs de date qui peuvent être null
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
