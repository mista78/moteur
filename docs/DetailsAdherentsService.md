# DetailsAdherentsService - Documentation Complète

## Vue d'ensemble

`DetailsAdherentsService` est le service de récupération des informations de revenus des adhérents par année. Il fournit l'historique des revenus nécessaire pour la détermination automatique des classes d'arrêts.

**Emplacement** : `src/Services/DetailsAdherentsService.php`

**Namespace** : `App\Services`

**Lignes de code** : 72 lignes

## Fonctionnalités

- ✅ Récupération du revenu adhérent par année
- ✅ Historique sur 5 années
- ✅ Revenus stockés en centimes
- ✅ Gestion des adhérents inexistants
- ✅ Fallback sur valeur par défaut

## Dépendances

Le service utilise :

- **AdherentInfos** : Modèle Eloquent pour les informations adhérent

## Méthodes Publiques

### revenuAdhParAnnee()

Récupère le revenu de l'adhérent pour les 5 dernières années.

```php
public function revenuAdhParAnnee(string $adherent_number): array
```

#### Paramètres

- `$adherent_number` (string) : Le numéro d'adhérent

#### Retour

```php
[
    2024 => 5200000,  // 52 000 € en centimes
    2023 => 5000000,  // 50 000 € en centimes
    2022 => 4700000,  // 47 000 € en centimes
    2021 => 4500000,  // 45 000 € en centimes
    2020 => 4300000   // 43 000 € en centimes
]
```

**Note** : Les revenus sont en centimes (multiplier par 100)

#### Exemple

```php
use App\Services\DetailsAdherentsService;

$service = new DetailsAdherentsService();

$revenus = $service->revenuAdhParAnnee('123456');

if (empty($revenus)) {
    echo "Adhérent non trouvé\n";
} else {
    echo "=== Revenus Adhérent 123456 ===\n";
    foreach ($revenus as $annee => $revenuCentimes) {
        $revenuEuros = $revenuCentimes / 100;
        echo "$annee : " . number_format($revenuEuros, 2, ',', ' ') . " €\n";
    }
}
```

## Méthodes Privées

### getRevenuForYear()

Récupère le revenu pour une année spécifique.

```php
private function getRevenuForYear(string $adherent_number, int $year): int
```

#### Paramètres

- `$adherent_number` (string) : Le numéro d'adhérent
- `$year` (int) : L'année (ex: 2022)

#### Retour

- `int` : Le revenu en centimes (ex: 4700000 = 47 000 €)

#### Logique

1. Recherche l'adhérent dans la table `AdherentInfos`
2. Si méthode `getRevenuByYear()` existe, l'utilise
3. Sinon, retourne valeur par défaut (47 000 € en centimes)

## Logique d'Implémentation

### 1. Vérification Adhérent

```php
$adherent = AdherentInfos::where('adherent_number', $adherent_number)->first();

if (!$adherent) {
    return [];  // Adhérent inexistant
}
```

### 2. Génération Historique 5 Ans

```php
$currentYear = date('Y');
$revenues = [];

for ($i = 0; $i < 5; $i++) {
    $year = $currentYear - $i;
    $revenues[$year] = $this->getRevenuForYear($adherent_number, $year);
}
```

### 3. Récupération Revenu Année

```php
// Méthode dynamique si elle existe
if (method_exists($adherent, 'getRevenuByYear')) {
    return $adherent->getRevenuByYear($year) * 100; // Convertir en centimes
}

// Valeur par défaut
return 4700000; // 47 000 euros en centimes
```

## Exemples d'Utilisation

### Exemple 1 : Affichage Simple

```php
use App\Services\DetailsAdherentsService;

$service = new DetailsAdherentsService();

$adherentNumber = '123456';
$revenus = $service->revenuAdhParAnnee($adherentNumber);

echo "Adhérent : $adherentNumber\n";
echo "Historique des revenus (5 dernières années) :\n\n";

foreach ($revenus as $annee => $centimes) {
    $euros = $centimes / 100;
    echo sprintf("%d : %s €\n", $annee, number_format($euros, 2, ',', ' '));
}

// Sortie :
// Adhérent : 123456
// Historique des revenus (5 dernières années) :
//
// 2024 : 52 000,00 €
// 2023 : 50 000,00 €
// 2022 : 47 000,00 €
// 2021 : 45 000,00 €
// 2020 : 43 000,00 €
```

### Exemple 2 : Revenu Année Spécifique

```php
$service = new DetailsAdherentsService();

$adherentNumber = '123456';
$anneeReference = 2022;

$revenus = $service->revenuAdhParAnnee($adherentNumber);

if (isset($revenus[$anneeReference])) {
    $revenuCentimes = $revenus[$anneeReference];
    $revenuEuros = $revenuCentimes / 100;

    echo "Revenu $anneeReference : " . number_format($revenuEuros, 2, ',', ' ') . " €\n";
} else {
    echo "Aucun revenu trouvé pour l'année $anneeReference\n";
}
```

### Exemple 3 : Évolution des Revenus

```php
$service = new DetailsAdherentsService();

$revenus = $service->revenuAdhParAnnee('123456');

if (empty($revenus)) {
    echo "Adhérent non trouvé\n";
    exit;
}

// Trier par année croissante
ksort($revenus);

echo "=== Évolution des Revenus ===\n\n";

$anneesPrecedente = null;
foreach ($revenus as $annee => $centimes) {
    $euros = $centimes / 100;

    echo "$annee : " . number_format($euros, 2, ',', ' ') . " €";

    if ($anneesPrecedente !== null) {
        $evolution = $centimes - $revenus[$anneesPrecedente];
        $evolutionEuros = $evolution / 100;
        $evolutionPourcent = ($evolution / $revenus[$anneesPrecedente]) * 100;

        $signe = $evolution >= 0 ? '+' : '';
        echo sprintf(
            " (%s%s €, %s%.2f%%)",
            $signe,
            number_format($evolutionEuros, 2, ',', ' '),
            $signe,
            $evolutionPourcent
        );
    }

    echo "\n";
    $anneesPrecedente = $annee;
}

// Sortie :
// === Évolution des Revenus ===
//
// 2020 : 43 000,00 €
// 2021 : 45 000,00 € (+2 000,00 €, +4,65%)
// 2022 : 47 000,00 € (+2 000,00 €, +4,44%)
// 2023 : 50 000,00 € (+3 000,00 €, +6,38%)
// 2024 : 52 000,00 € (+2 000,00 €, +4,00%)
```

### Exemple 4 : Détermination Classe par Année

```php
use App\Services\DetailsAdherentsService;
use App\Services\TauxDeterminationService;

$detailsService = new DetailsAdherentsService();
$tauxService = new TauxDeterminationService();

$adherentNumber = '123456';
$revenus = $detailsService->revenuAdhParAnnee($adherentNumber);
$pssParAnnee = $tauxService->pssParAnnee();

echo "=== Classe par Année ===\n\n";

foreach ($revenus as $annee => $centimes) {
    $euros = $centimes / 100;

    // Déterminer la classe pour cette année
    $pss = $pssParAnnee[$annee] ?? 47000; // PSS par défaut

    if ($euros < $pss) {
        $classe = 'A';
    } elseif ($euros < $pss * 2) {
        $classe = 'B';
    } else {
        $classe = 'C';
    }

    echo sprintf(
        "%d : %s € (PSS: %s €) → Classe %s\n",
        $annee,
        number_format($euros, 2, ',', ' '),
        number_format($pss, 2, ',', ' '),
        $classe
    );
}

// Sortie :
// === Classe par Année ===
//
// 2024 : 52 000,00 € (PSS: 47 000,00 €) → Classe B
// 2023 : 50 000,00 € (PSS: 46 368,00 €) → Classe B
// 2022 : 47 000,00 € (PSS: 45 864,00 €) → Classe B
// 2021 : 45 000,00 € (PSS: 45 000,00 €) → Classe A
// 2020 : 43 000,00 € (PSS: 44 000,00 €) → Classe A
```

### Exemple 5 : Comparaison Multi-Adhérents

```php
$service = new DetailsAdherentsService();

$adherents = ['123456', '789012', '345678'];
$anneeReference = 2022;

echo "=== Comparaison Revenus $anneeReference ===\n\n";

foreach ($adherents as $adherentNumber) {
    $revenus = $service->revenuAdhParAnnee($adherentNumber);

    if (isset($revenus[$anneeReference])) {
        $euros = $revenus[$anneeReference] / 100;
        echo sprintf(
            "Adhérent %s : %s €\n",
            $adherentNumber,
            number_format($euros, 2, ',', ' ')
        );
    } else {
        echo "Adhérent $adherentNumber : Aucune donnée\n";
    }
}

// Sortie :
// === Comparaison Revenus 2022 ===
//
// Adhérent 123456 : 47 000,00 €
// Adhérent 789012 : 95 000,00 €
// Adhérent 345678 : Aucune donnée
```

## Gestion des Cas Limites

### Adhérent Inexistant

```php
$service = new DetailsAdherentsService();

$revenus = $service->revenuAdhParAnnee('999999'); // Adhérent inexistant

if (empty($revenus)) {
    echo "Aucune donnée trouvée pour cet adhérent\n";
}

// Sortie : Aucune donnée trouvée pour cet adhérent
```

### Année Hors Historique

```php
$revenus = $service->revenuAdhParAnnee('123456');

$annee = 2015; // Plus de 5 ans dans le passé

if (!isset($revenus[$annee])) {
    echo "Pas de données pour l'année $annee\n";
}
```

## Conversion Centimes ↔ Euros

### Centimes → Euros

```php
$centimes = 4700000;
$euros = $centimes / 100;  // 47 000,00 €
```

### Euros → Centimes

```php
$euros = 47000;
$centimes = $euros * 100;  // 4 700 000 centimes
```

### Pourquoi les Centimes ?

1. **Précision** : Évite les erreurs d'arrondi avec les décimales
2. **Performance** : Les opérations sur entiers sont plus rapides
3. **Stockage** : Format standard en base de données pour les montants

## Structure de Données

### Entrée

```php
$adherent_number = '123456';  // String
```

### Sortie

```php
[
    2024 => 5200000,  // int (centimes)
    2023 => 5000000,
    2022 => 4700000,
    2021 => 4500000,
    2020 => 4300000
]
```

### Adhérent Inexistant

```php
[]  // Tableau vide
```

## Valeur Par Défaut

Si le modèle `AdherentInfos` n'a pas de méthode `getRevenuByYear()`, le service retourne :

```php
4700000  // 47 000 € en centimes (1 PSS approximatif)
```

## Intégration avec DetailsArretsService

Ce service est utilisé par `DetailsArretsService` pour déterminer la classe d'un arrêt :

```php
// Dans DetailsArretsService
$detailsAdherentsService = new DetailsAdherentsService();
$revenuAdhParAnnee = $detailsAdherentsService->revenuAdhParAnnee($arret->adherent_number);

// Année de référence : année arrêt - 2
$year = (int)date('Y', strtotime($arret->date_start)) - 2;

// Revenu N-2 en euros
$revenue = isset($revenuAdhParAnnee[$year])
    ? $revenuAdhParAnnee[$year] / 100
    : 0;
```

## Points Importants

1. **Format centimes** : Tous les revenus sont en centimes (× 100)
2. **Historique 5 ans** : Retourne uniquement les 5 dernières années
3. **Tableau vide** : Retourné si adhérent inexistant
4. **Valeur défaut** : 47 000 € si pas de méthode `getRevenuByYear()`
5. **Année courante** : Basée sur `date('Y')` au moment de l'appel

## Extension du Service

### Ajout Méthode au Modèle

Pour implémenter une vraie logique de récupération :

```php
// Dans le modèle AdherentInfos
class AdherentInfos extends Model
{
    public function getRevenuByYear(int $year): int
    {
        // Logique réelle : requête historique, table dédiée, etc.
        $revenuHistory = $this->hasMany(RevenuHistory::class)
            ->where('year', $year)
            ->first();

        return $revenuHistory ? $revenuHistory->revenu : 47000;
    }
}
```

### Table Historique Revenus

Structure possible pour stocker les revenus :

```sql
CREATE TABLE adherent_revenus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adherent_number VARCHAR(20),
    year INT,
    revenu INT,  -- En centimes
    created_at TIMESTAMP,
    UNIQUE KEY (adherent_number, year)
);
```

## Performance

### Optimisations

Pour éviter requêtes multiples dans une boucle :

```php
// Mauvaise pratique
foreach ($arrets as $arret) {
    $service = new DetailsAdherentsService();
    $revenus = $service->revenuAdhParAnnee($arret->adherent_number);  // ❌
}

// Bonne pratique
$service = new DetailsAdherentsService();
$revenus = [];

foreach ($arrets as $arret) {
    $adherentNumber = $arret->adherent_number;

    if (!isset($revenus[$adherentNumber])) {
        $revenus[$adherentNumber] = $service->revenuAdhParAnnee($adherentNumber);  // ✅ Une fois par adhérent
    }
}
```

## Voir Aussi

- [DetailsArretsService](./DetailsArretsService.md) - Utilisation des revenus pour déterminer la classe
- [TauxDeterminationService](./TauxDeterminationService.md) - Valeurs PSS et détermination classe
- [AmountCalculationService](./AmountCalculationService.md) - Auto-détermination classe dans pipeline
