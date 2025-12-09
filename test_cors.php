<?php
/**
 * Test CORS Configuration
 *
 * Vérifie que le middleware CORS accepte toutes les requêtes
 * depuis n'importe quel domaine
 */

echo "============================================\n";
echo "Test de Configuration CORS\n";
echo "============================================\n\n";

$baseUrl = 'http://localhost:8000';

// Fonction pour tester les headers CORS
function testCorsHeaders(string $url, string $method, string $origin, array $extraHeaders = []): array
{
    $ch = curl_init($url);

    $headers = array_merge([
        "Origin: $origin"
    ], $extraHeaders);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, $method === 'OPTIONS');

    $response = curl_exec($ch);

    if ($response === false) {
        curl_close($ch);
        return ['error' => curl_error($ch)];
    }

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headerText = substr($response, 0, $headerSize);
    curl_close($ch);

    // Parse headers
    $headersArray = [];
    $lines = explode("\r\n", $headerText);
    foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
            list($key, $value) = explode(':', $line, 2);
            $headersArray[trim($key)] = trim($value);
        }
    }

    return $headersArray;
}

echo "Test 1: Requête OPTIONS (Preflight)\n";
echo "------------------------------------\n";
$headers = testCorsHeaders(
    "$baseUrl/api/calculations",
    'OPTIONS',
    'https://external-domain.com',
    [
        'Access-Control-Request-Method: POST',
        'Access-Control-Request-Headers: Content-Type'
    ]
);

if (isset($headers['error'])) {
    echo "✗ Erreur: {$headers['error']}\n";
    echo "  Assurez-vous que le serveur tourne: cd public && php -S localhost:8000\n\n";
    exit(1);
}

$corsHeaders = [
    'Access-Control-Allow-Origin',
    'Access-Control-Allow-Methods',
    'Access-Control-Allow-Headers',
    'Access-Control-Expose-Headers',
    'Access-Control-Max-Age'
];

echo "Headers CORS reçus:\n";
$allPresent = true;
foreach ($corsHeaders as $header) {
    if (isset($headers[$header])) {
        echo "  ✓ $header: {$headers[$header]}\n";
    } else {
        echo "  ✗ $header: NON TROUVÉ\n";
        $allPresent = false;
    }
}
echo "\n";

if (!$allPresent) {
    echo "✗ Certains headers CORS sont manquants\n\n";
    exit(1);
}

echo "Test 2: Requête GET depuis domaine externe\n";
echo "-------------------------------------------\n";
$headers = testCorsHeaders(
    "$baseUrl/api/mocks",
    'GET',
    'https://example.com'
);

if (isset($headers['Access-Control-Allow-Origin'])) {
    echo "✓ Origin acceptée: {$headers['Access-Control-Allow-Origin']}\n";
    echo "✓ Méthodes autorisées: {$headers['Access-Control-Allow-Methods']}\n";
} else {
    echo "✗ Headers CORS manquants dans la réponse GET\n";
}
echo "\n";

echo "Test 3: Requête POST depuis domaine différent\n";
echo "----------------------------------------------\n";
$headers = testCorsHeaders(
    "$baseUrl/api/calculations/classe",
    'POST',
    'https://another-domain.org',
    ['Content-Type: application/json']
);

if (isset($headers['Access-Control-Allow-Origin'])) {
    echo "✓ Origin acceptée: {$headers['Access-Control-Allow-Origin']}\n";
    echo "✓ Headers exposés: {$headers['Access-Control-Expose-Headers']}\n";
} else {
    echo "✗ Headers CORS manquants dans la réponse POST\n";
}
echo "\n";

echo "Test 4: Vérification de la configuration complète\n";
echo "--------------------------------------------------\n";

$requirements = [
    'Allow-Origin: *' => $headers['Access-Control-Allow-Origin'] ?? '' === '*',
    'Toutes les méthodes' => strpos($headers['Access-Control-Allow-Methods'] ?? '', 'GET') !== false
                            && strpos($headers['Access-Control-Allow-Methods'] ?? '', 'POST') !== false
                            && strpos($headers['Access-Control-Allow-Methods'] ?? '', 'DELETE') !== false,
    'Tous les headers acceptés' => ($headers['Access-Control-Allow-Headers'] ?? '') === '*',
    'Tous les headers exposés' => ($headers['Access-Control-Expose-Headers'] ?? '') === '*',
    'Cache preflight (24h)' => ($headers['Access-Control-Max-Age'] ?? '') === '86400'
];

$allPassed = true;
foreach ($requirements as $description => $passed) {
    $status = $passed ? '✓' : '✗';
    echo "  $status $description\n";
    if (!$passed) {
        $allPassed = false;
    }
}
echo "\n";

echo "Test 5: Test depuis différents domaines\n";
echo "----------------------------------------\n";
$testDomains = [
    'http://localhost:3000',
    'https://app.example.com',
    'https://api.autre-site.fr',
    'https://192.168.1.100:8080'
];

foreach ($testDomains as $domain) {
    $headers = testCorsHeaders("$baseUrl/api/mocks", 'GET', $domain);
    $allowed = isset($headers['Access-Control-Allow-Origin'])
               && $headers['Access-Control-Allow-Origin'] === '*';

    $status = $allowed ? '✓' : '✗';
    echo "  $status Domaine: $domain\n";
}
echo "\n";

echo "============================================\n";
if ($allPassed) {
    echo "✓ Tous les tests CORS réussis!\n";
    echo "============================================\n\n";
    echo "Configuration CORS:\n";
    echo "  → Accepte TOUTES les origines (*)\n";
    echo "  → Accepte TOUTES les méthodes HTTP\n";
    echo "  → Accepte TOUS les headers personnalisés\n";
    echo "  → Expose TOUS les headers en réponse\n";
    echo "  → Cache preflight pendant 24 heures\n";
    echo "\n";
    echo "Votre API est accessible depuis n'importe quel domaine! 🌍\n";
} else {
    echo "✗ Certains tests CORS ont échoué\n";
    echo "============================================\n";
    exit(1);
}
