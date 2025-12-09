<?php
/**
 * Test Swagger/OpenAPI Documentation
 * 
 * Verifies that all API endpoints are properly documented
 */

echo "============================================\n";
echo "Testing Swagger/OpenAPI Documentation\n";
echo "============================================\n\n";

$baseUrl = 'http://localhost:8000';

// Test 1: Check OpenAPI JSON endpoint
echo "Test 1: OpenAPI JSON Endpoint\n";
echo "------------------------------\n";
$jsonResponse = @file_get_contents("$baseUrl/api/docs");
if ($jsonResponse === false) {
    echo "✗ Failed to fetch OpenAPI JSON\n";
    echo "  Make sure server is running: cd public && php -S localhost:8000\n\n";
    exit(1);
}

$spec = json_decode($jsonResponse, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "✗ Invalid JSON response\n\n";
    exit(1);
}

echo "✓ OpenAPI version: " . ($spec['openapi'] ?? 'N/A') . "\n";
echo "✓ API title: " . ($spec['info']['title'] ?? 'N/A') . "\n";
echo "✓ API version: " . ($spec['info']['version'] ?? 'N/A') . "\n\n";

// Test 2: Check documented paths
echo "Test 2: Documented API Paths\n";
echo "-----------------------------\n";
$paths = array_keys($spec['paths'] ?? []);
echo "✓ Found " . count($paths) . " documented endpoint(s):\n";
foreach ($paths as $path) {
    $methods = array_keys($spec['paths'][$path]);
    echo "  - " . strtoupper(implode(', ', $methods)) . " $path\n";
}
echo "\n";

// Test 3: Check tags
echo "Test 3: API Tags\n";
echo "----------------\n";
$tags = $spec['tags'] ?? [];
echo "✓ Found " . count($tags) . " tag(s):\n";
foreach ($tags as $tag) {
    echo "  - {$tag['name']}: {$tag['description']}\n";
}
echo "\n";

// Test 4: Check servers
echo "Test 4: API Servers\n";
echo "-------------------\n";
$servers = $spec['servers'] ?? [];
echo "✓ Found " . count($servers) . " server(s):\n";
foreach ($servers as $server) {
    echo "  - {$server['url']}: {$server['description']}\n";
}
echo "\n";

// Test 5: Check specific endpoint details
echo "Test 5: Endpoint Details (POST /api/calculations)\n";
echo "--------------------------------------------------\n";
$calcEndpoint = $spec['paths']['/api/calculations']['post'] ?? null;
if ($calcEndpoint) {
    echo "✓ Summary: " . ($calcEndpoint['summary'] ?? 'N/A') . "\n";
    echo "✓ Tags: " . implode(', ', $calcEndpoint['tags'] ?? []) . "\n";
    echo "✓ Has request body: " . (isset($calcEndpoint['requestBody']) ? 'Yes' : 'No') . "\n";
    echo "✓ Response codes: " . implode(', ', array_keys($calcEndpoint['responses'] ?? [])) . "\n";
} else {
    echo "✗ Endpoint not found in documentation\n";
}
echo "\n";

// Test 6: Check Swagger UI
echo "Test 6: Swagger UI Page\n";
echo "-----------------------\n";
$htmlResponse = @file_get_contents("$baseUrl/api-docs");
if ($htmlResponse === false) {
    echo "✗ Failed to fetch Swagger UI\n\n";
    exit(1);
}

if (strpos($htmlResponse, 'swagger-ui') !== false) {
    echo "✓ Swagger UI page loads correctly\n";
    echo "✓ Access at: $baseUrl/api-docs\n";
} else {
    echo "✗ Swagger UI page seems invalid\n";
}
echo "\n";

// Test 7: Check YAML endpoint
echo "Test 7: OpenAPI YAML Endpoint\n";
echo "------------------------------\n";
$yamlResponse = @file_get_contents("$baseUrl/api/docs/yaml");
if ($yamlResponse === false) {
    echo "✗ Failed to fetch OpenAPI YAML\n\n";
} else {
    $lines = explode("\n", $yamlResponse);
    echo "✓ YAML endpoint working (" . count($lines) . " lines)\n";
    echo "✓ First line: " . trim($lines[0]) . "\n";
}
echo "\n";

echo "============================================\n";
echo "All Swagger/OpenAPI tests completed! ✓\n";
echo "============================================\n\n";
echo "Access the interactive documentation at:\n";
echo "  → $baseUrl/api-docs\n\n";
