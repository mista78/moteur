# SinistreService - Documentation Complète

## Vue d'ensemble

`SinistreService` est le service de gestion des sinistres avec calcul automatique de la date-effet pour tous les arrêts associés. Il gère la logique métier liée aux sinistres et délègue les calculs de dates au `DateService` (séparation des préoccupations SOLID).

**Emplacement** : `src/Services/SinistreService.php`

**Namespace** : `App\Services`

**Lignes de code** : 130 lignes

## Fonctionnalités

- ✅ Récupération sinistre avec date-effet calculée
- ✅ Chargement eager des relations (évite N+1)
- ✅ Filtrage par adhérent et numéro de dossier
- ✅ Récupération de tous les sinistres d'un adhérent
- ✅ Intégration avec recap indemnisations
- ✅ Délégation des calculs au DateService

## Dépendances

Le service utilise l'injection de dépendances pour recevoir :

- **DateCalculationInterface** : Service de calcul de dates (DateService)

## Méthodes Publiques

### 1. getSinistreWithDateEffet()

Récupère un sinistre par ID avec la date-effet calculée pour tous ses arrêts.

```php
public function getSinistreWithDateEffet(int $sinistreId): array
```

#### Paramètres

- `$sinistreId` (int) : L'ID du sinistre

#### Retour

```php
[
    'arrets_with_date_effet' => array,  // Arrêts avec date-effet calculée
    'recap_indems' => array             // Recap indems des AUTRES sinistres
]
```

#### Exceptions

- `RuntimeException` : Si le sinistre n'est pas trouvé

#### Exemple

```php
use App\Services\SinistreService;
use App\Services\DateService;

$dateService = new DateService();
$sinistreService = new SinistreService($dateService);

try {
    $result = $sinistreService->getSinistreWithDateEffet(8038);

    echo "Nombre d'arrêts : " . count($result['arrets_with_date_effet']) . "\n";

    foreach ($result['arrets_with_date_effet'] as $arret) {
        echo "Arrêt du {$arret['arret-from-line']} au {$arret['arret-to-line']}\n";
        echo "Date effet : {$arret['date_effet']}\n";
        echo "Jours décompte : {$arret['jours_decompte']}\n\n";
    }

    echo "Autres indemnisations : " . count($result['recap_indems']) . "\n";

} catch (RuntimeException $e) {
    echo "Erreur : " . $e->getMessage();
}
```

### 2. getSinistreWithDateEffetForAdherent()

Récupère un sinistre spécifique d'un adhérent avec date-effet calculée.

```php
public function getSinistreWithDateEffetForAdherent(
    string $adherentNumber,
    int $numero_dossier
): array
```

#### Paramètres

- `$adherentNumber` (string) : Le numéro d'adhérent
- `$numero_dossier` (int) : Le numéro de dossier du sinistre

#### Retour

```php
[
    'arrets_with_date_effet' => array,  // Arrêts avec date-effet calculée
    'recap_indems' => array             // Recap indems des AUTRES sinistres
]
```

#### Exceptions

- `RuntimeException` : Si le sinistre n'est pas trouvé ou n'appartient pas à l'adhérent

#### Exemple

```php
$sinistreService = new SinistreService($dateService);

try {
    $result = $sinistreService->getSinistreWithDateEffetForAdherent(
        '123456',
        8038
    );

    echo "Sinistre trouvé pour l'adhérent 123456\n";
    echo "Arrêts : " . count($result['arrets_with_date_effet']) . "\n";

    // Afficher les détails
    foreach ($result['arrets_with_date_effet'] as $arret) {
        echo "- {$arret['arret-from-line']} → {$arret['arret-to-line']}\n";
        echo "  Date effet : {$arret['date_effet']}\n";

        if ($arret['is_rechute']) {
            echo "  RECHUTE détectée\n";
        }
    }

} catch (RuntimeException $e) {
    echo "Sinistre non trouvé : " . $e->getMessage();
}
```

### 3. getAllSinistresWithDateEffet()

Récupère tous les sinistres d'un adhérent avec date-effet calculée.

```php
public function getAllSinistresWithDateEffet(string $adherentNumber): array
```

#### Paramètres

- `$adherentNumber` (string) : Le numéro d'adhérent

#### Retour

Tableau de sinistres, chacun avec :

```php
[
    [
        'arrets_with_date_effet' => array,
        'recap_indems' => array
    ],
    // ... autres sinistres
]
```

#### Exemple

```php
$sinistreService = new SinistreService($dateService);

$sinistres = $sinistreService->getAllSinistresWithDateEffet('123456');

echo "Adhérent 123456 : " . count($sinistres) . " sinistre(s)\n\n";

foreach ($sinistres as $index => $sinistreData) {
    echo "Sinistre " . ($index + 1) . ":\n";
    echo "  Arrêts : " . count($sinistreData['arrets_with_date_effet']) . "\n";

    $totalJoursDecompte = 0;
    foreach ($sinistreData['arrets_with_date_effet'] as $arret) {
        $totalJoursDecompte += $arret['jours_decompte'] ?? 0;
    }

    echo "  Total jours décompte : $totalJoursDecompte\n";
    echo "  Autres indemnisations : " . count($sinistreData['recap_indems']) . "\n\n";
}
```

## Relations et Chargement Eager

Le service utilise le chargement eager pour optimiser les performances :

```php
$sinistre = IjSinistre::with(['arrets', 'adherent', 'recapIndems'])->find($sinistreId);
```

### Relations Chargées

1. **arrets** : Collection des arrêts de travail du sinistre
2. **adherent** : Informations de l'adhérent (notamment birth_date)
3. **recapIndems** : Récapitulatifs des indemnisations (ordonnés par date desc)

### Avantages

- ✅ Évite le problème N+1 (une seule requête)
- ✅ Performance optimale
- ✅ Toutes les données disponibles immédiatement

## Calcul de la Date-Effet

Le service délègue le calcul de la date-effet au `DateService` :

```php
$arretsWithDateEffet = $this->dateService->calculateDateEffet($arrets, $birthDate);
```

### Données Calculées

Pour chaque arrêt, le service enrichit les données avec :

- `date_effet` : Date d'ouverture des droits
- `jours_decompte` : Nombre de jours décomptés avant date-effet
- `is_rechute` : Indicateur de rechute (true/false)
- `rechute_reason` : Raison de la classification rechute

## Récap Indemnisations

Le service récupère les recap indemnisations des **autres sinistres** (excluant le sinistre courant) :

```php
$otherRecapIndems = RecapIdem::byAdherent($adherentNumber)
    ->where('num_sinistre', '!=', $sinistre->id)
    ->orderBy('indemnisation_from_line', 'desc')
    ->get()
    ->toArray();
```

### Utilité

- Permet de voir l'historique des indemnisations de l'adhérent
- Exclut le sinistre actuel pour éviter les doublons
- Ordonné par date décroissante (plus récent en premier)

## Formatage de Sortie

Les résultats sont formatés via `Tools::formatForOutput()` pour assurer une cohérence :

```php
'arrets_with_date_effet' => Tools::formatForOutput($arretsWithDateEffet)
```

## Exemples d'Utilisation Complets

### Exemple 1 : Affichage Détaillé d'un Sinistre

```php
use App\Services\SinistreService;
use App\Services\DateService;

$dateService = new DateService();
$sinistreService = new SinistreService($dateService);

$sinistreId = 8038;
$result = $sinistreService->getSinistreWithDateEffet($sinistreId);

echo "=== Sinistre $sinistreId ===\n\n";

echo "ARRÊTS AVEC DATE-EFFET:\n";
foreach ($result['arrets_with_date_effet'] as $i => $arret) {
    echo "\nArrêt " . ($i + 1) . ":\n";
    echo "  Période : {$arret['arret-from-line']} → {$arret['arret-to-line']}\n";
    echo "  Date effet : {$arret['date_effet']}\n";
    echo "  Jours décompte : {$arret['jours_decompte']}\n";

    if ($arret['is_rechute']) {
        echo "  Type : RECHUTE\n";
        echo "  Raison : {$arret['rechute_reason']}\n";
    } else {
        echo "  Type : Nouvelle pathologie\n";
    }
}

echo "\n\nAUTRES INDEMNISATIONS:\n";
foreach ($result['recap_indems'] as $recap) {
    echo "  - Du {$recap['indemnisation_from_line']} au {$recap['indemnisation_to_line']}\n";
    echo "    Montant : {$recap['montant_total']}€\n";
}
```

### Exemple 2 : Statistiques par Adhérent

```php
$adherentNumber = '123456';
$sinistres = $sinistreService->getAllSinistresWithDateEffet($adherentNumber);

$stats = [
    'total_sinistres' => count($sinistres),
    'total_arrets' => 0,
    'total_jours_decompte' => 0,
    'total_rechutes' => 0
];

foreach ($sinistres as $sinistreData) {
    $stats['total_arrets'] += count($sinistreData['arrets_with_date_effet']);

    foreach ($sinistreData['arrets_with_date_effet'] as $arret) {
        $stats['total_jours_decompte'] += $arret['jours_decompte'] ?? 0;

        if ($arret['is_rechute']) {
            $stats['total_rechutes']++;
        }
    }
}

echo "=== Statistiques Adhérent $adherentNumber ===\n";
echo "Sinistres : {$stats['total_sinistres']}\n";
echo "Arrêts : {$stats['total_arrets']}\n";
echo "Jours décompte : {$stats['total_jours_decompte']}\n";
echo "Rechutes : {$stats['total_rechutes']}\n";
```

### Exemple 3 : Export JSON

```php
$sinistreId = 8038;
$result = $sinistreService->getSinistreWithDateEffet($sinistreId);

// Export JSON formaté
$json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents("sinistre_{$sinistreId}_details.json", $json);

echo "Export réussi : sinistre_{$sinistreId}_details.json\n";
```

## Gestion des Erreurs

```php
use RuntimeException;

try {
    // Sinistre inexistant
    $result = $sinistreService->getSinistreWithDateEffet(99999);

} catch (RuntimeException $e) {
    echo "Erreur : " . $e->getMessage();
    // Sortie : "Sinistre non trouvé avec ID: 99999"
}

try {
    // Sinistre n'appartenant pas à l'adhérent
    $result = $sinistreService->getSinistreWithDateEffetForAdherent(
        '999999',
        8038
    );

} catch (RuntimeException $e) {
    echo "Erreur : " . $e->getMessage();
    // Sortie : "Sinistre non trouvé ou n'appartient pas à l'adhérent 999999"
}
```

## Structure de Données

### Format de Sortie

```php
[
    'arrets_with_date_effet' => [
        [
            // Données originales de l'arrêt
            'id' => 123,
            'arret-from-line' => '2023-09-01',
            'arret-to-line' => '2023-11-30',
            'dt-line' => 1,
            'rechute-line' => 0,
            'gpm-member-line' => 0,

            // Données calculées par DateService
            'date_effet' => '2023-12-01',
            'jours_decompte' => 91,  // 90 + 1 (DT)
            'is_rechute' => false,
            'rechute_reason' => null
        ],
        // ... autres arrêts
    ],

    'recap_indems' => [
        [
            'id' => 456,
            'num_sinistre' => 8042,  // Autre sinistre
            'indemnisation_from_line' => '2022-05-01',
            'indemnisation_to_line' => '2022-08-31',
            'montant_total' => 12500.00,
            'nbe_jours' => 123
        ],
        // ... autres indemnisations
    ]
]
```

## Points Importants

1. **Séparation des préoccupations** : Délègue calculs au DateService
2. **Performance optimisée** : Chargement eager des relations
3. **Filtrage intelligent** : Exclut le sinistre actuel des recap indems
4. **Gestion erreurs robuste** : Exceptions claires et explicites
5. **Format cohérent** : Utilise Tools::formatForOutput()

## Intégration avec DateService

Le service ne calcule PAS directement les dates-effet. Il :

1. Charge le sinistre et ses relations
2. Récupère la date de naissance de l'adhérent
3. Convertit les arrêts au format tableau
4. Appelle `DateService::calculateDateEffet()`
5. Enrichit le résultat avec les recap indems

Ceci respecte le principe SOLID de responsabilité unique.

## Voir Aussi

- [DateService](./DateService.md) - Calcul de la date-effet
- [ArretService](./ArretService.md) - Gestion des arrêts
- [RecapService](./RecapService.md) - Génération des récapitulatifs
