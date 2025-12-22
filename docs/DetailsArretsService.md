# DetailsArretsService - Documentation Complète

## Vue d'ensemble

`DetailsArretsService` est le service de détermination automatique de la classe d'un arrêt basée sur le revenu de l'adhérent et les valeurs PSS (Plafond Sécurité Sociale). Il calcule la classe (A/B/C) en fonction du revenu de l'année N-2.

**Emplacement** : `src/Services/DetailsArretsService.php`

**Namespace** : `App\Services`

**Lignes de code** : 53 lignes

## Fonctionnalités

- ✅ Détermination automatique de la classe d'arrêt
- ✅ Récupération du revenu adhérent par année
- ✅ Utilisation des valeurs PSS historiques
- ✅ Gestion des dates de début de droit
- ✅ Conversion automatique centimes → euros

## Dépendances

Le service utilise :

- **DetailsAdherentsService** : Récupération des revenus adhérent
- **TauxDeterminationService** : Valeurs PSS et détermination classe
- **IjArret** : Modèle Eloquent pour les arrêts

## Méthode Principale

### getArretClasse()

Détermine la classe d'un arrêt en fonction du revenu de l'adhérent.

```php
public function getArretClasse($arret): string
```

#### Paramètres

- `$arret` (IjArret|object) : L'arrêt de travail (modèle Eloquent ou objet)

#### Propriétés Requises de l'Arrêt

- `adherent_number` : Numéro d'adhérent
- `date_start` : Date de début de l'arrêt
- `date_deb_dr_force` : Date de début de droit forcée (optionnel)
- `date_deb_droit` : Date de début de droit (fallback)

#### Retour

- `string` : La classe déterminée ('A', 'B', ou 'C')

#### Exemple

```php
use App\Services\DetailsArretsService;
use App\Models\IjArret;

$detailsArretsService = new DetailsArretsService();

// Depuis un modèle Eloquent
$arret = IjArret::find(123);
$classe = $detailsArretsService->getArretClasse($arret);

echo "Classe déterminée : $classe\n";
// Sortie : "Classe déterminée : B"
```

## Logique de Détermination

### 1. Récupération du Revenu

Le service récupère le revenu de l'adhérent par année via `DetailsAdherentsService` :

```php
$detailsAdherentsService = new DetailsAdherentsService();
$revenuAdhParAnnee = $detailsAdherentsService->revenuAdhParAnnee($arret->adherent_number);
```

### 2. Récupération des Valeurs PSS

Les valeurs PSS (Plafond Sécurité Sociale) sont récupérées via `TauxDeterminationService` :

```php
$tauxDeterminationService = new TauxDeterminationService();
$pssParAnnee = $tauxDeterminationService->pssParAnnee();
```

### 3. Calcul de l'Année de Référence

L'année de référence est **l'année de début de l'arrêt - 2** (revenu N-2) :

```php
$year = (int)date('Y', strtotime($arret->date_start)) - 2;
```

**Exemple** :
- Arrêt débutant en 2024 → Revenu de 2022
- Arrêt débutant en 2023 → Revenu de 2021

### 4. Détermination de la Date de Début de Droit

Le service utilise la date forcée en priorité, sinon la date de droit normale :

```php
$dateDebutDroit = $arret->date_deb_dr_force ?? $arret->date_deb_droit;
```

### 5. Conversion Centimes → Euros

Le revenu stocké en base est en centimes et doit être converti :

```php
$revenue = isset($revenuAdhParAnnee[$year])
    ? $revenuAdhParAnnee[$year] / 100
    : 0;
```

**Exemple** :
- Revenu BDD : 4 700 000 centimes
- Revenu converti : 47 000 €

### 6. Détermination de la Classe

La classe est déterminée via `TauxDeterminationService::determineClasse()` :

```php
$tauxClass = new TauxDeterminationService(47000);
$tauxClass->setPassValuesByYear($pssParAnnee);
return $tauxClass->determineClasse($revenue, $dateDebutDroit);
```

## Règles de Classification

### Classes Basées sur PSS

| Classe | Revenu N-2 | Multiple PSS |
|--------|------------|--------------|
| A | < 1 PSS | Moins de 47 000 € (2024) |
| B | 1-2 PSS | 47 000 € - 94 000 € |
| C | > 2 PSS | Plus de 94 000 € |

**Note** : Les seuils varient selon l'année (valeur PSS historique)

## Exemples d'Utilisation

### Exemple 1 : Détermination Simple

```php
use App\Services\DetailsArretsService;
use App\Models\IjArret;

$service = new DetailsArretsService();

$arret = IjArret::find(123);
// Propriétés :
// - adherent_number = '123456'
// - date_start = '2024-01-15'
// - date_deb_droit = '2024-04-15'

$classe = $service->getArretClasse($arret);

echo "Arrêt #{$arret->id}\n";
echo "Adhérent : {$arret->adherent_number}\n";
echo "Date début : {$arret->date_start}\n";
echo "Année référence : " . (date('Y', strtotime($arret->date_start)) - 2) . "\n";
echo "Classe déterminée : $classe\n";
```

### Exemple 2 : Traitement par Lot

```php
$service = new DetailsArretsService();

// Récupérer tous les arrêts d'un adhérent
$arrets = IjArret::where('adherent_number', '123456')->get();

$classesCount = ['A' => 0, 'B' => 0, 'C' => 0];

foreach ($arrets as $arret) {
    $classe = $service->getArretClasse($arret);
    $classesCount[$classe]++;

    echo "Arrêt du {$arret->date_start} : Classe $classe\n";
}

echo "\n=== Statistiques ===\n";
echo "Classe A : {$classesCount['A']} arrêt(s)\n";
echo "Classe B : {$classesCount['B']} arrêt(s)\n";
echo "Classe C : {$classesCount['C']} arrêt(s)\n";
```

### Exemple 3 : Affichage Détaillé avec Revenu

```php
use App\Services\DetailsArretsService;
use App\Services\DetailsAdherentsService;
use App\Models\IjArret;

$detailsArretsService = new DetailsArretsService();
$detailsAdherentsService = new DetailsAdherentsService();

$arret = IjArret::find(123);

// Récupérer le revenu de l'adhérent
$revenus = $detailsAdherentsService->revenuAdhParAnnee($arret->adherent_number);
$yearRef = (int)date('Y', strtotime($arret->date_start)) - 2;
$revenuRef = isset($revenus[$yearRef]) ? $revenus[$yearRef] / 100 : 0;

// Déterminer la classe
$classe = $detailsArretsService->getArretClasse($arret);

echo "=== Détails Arrêt #{$arret->id} ===\n";
echo "Adhérent : {$arret->adherent_number}\n";
echo "Date début arrêt : {$arret->date_start}\n";
echo "Année de référence : $yearRef\n";
echo "Revenu N-2 : " . number_format($revenuRef, 2, ',', ' ') . " €\n";
echo "Classe déterminée : $classe\n";

// Afficher l'historique des revenus
echo "\n=== Historique Revenus ===\n";
foreach ($revenus as $year => $revenuCentimes) {
    $revenuEuros = $revenuCentimes / 100;
    echo "$year : " . number_format($revenuEuros, 2, ',', ' ') . " €\n";
}
```

### Exemple 4 : Gestion Date de Droit Forcée

```php
$service = new DetailsArretsService();

// Arrêt avec date de droit normale
$arret1 = IjArret::find(100);
$classe1 = $service->getArretClasse($arret1);
echo "Arrêt {$arret1->id} (date normale) : Classe $classe1\n";

// Arrêt avec date de droit forcée
$arret2 = IjArret::find(101);
$arret2->date_deb_dr_force = '2024-01-15';  // Override
$classe2 = $service->getArretClasse($arret2);
echo "Arrêt {$arret2->id} (date forcée) : Classe $classe2\n";
```

## Gestion des Dates

### Format DateTime

Le service gère automatiquement les objets DateTime :

```php
// Si l'arrêt a un objet DateTime
if (is_object($dateDebutDroit)) {
    $dateDebutDroit = $dateDebutDroit->format('Y-m-d');
}

// Pour l'année
if (is_object($year)) {
    $year = (int)$year->format('Y') - 2;
} else {
    $year = (int)date('Y', strtotime($year)) - 2;
}
```

### Priorité Date de Droit

```
1. date_deb_dr_force (si définie)
2. date_deb_droit (fallback)
```

## Cas Limites

### Revenu Absent

Si le revenu n'est pas trouvé pour l'année de référence :

```php
$revenue = isset($revenuAdhParAnnee[$year])
    ? $revenuAdhParAnnee[$year] / 100
    : 0;  // Revenu par défaut : 0€
```

**Résultat** : Classe A (revenu minimal)

### Adhérent Inexistant

Si l'adhérent n'existe pas, `DetailsAdherentsService` retourne un tableau vide :

```php
$revenuAdhParAnnee = [];  // Aucun revenu trouvé
$revenue = 0;             // Revenu par défaut
```

**Résultat** : Classe A

## Structure de Données

### Entrée (Arrêt)

```php
$arret = [
    'id' => 123,
    'adherent_number' => '123456',
    'date_start' => '2024-01-15',           // Date début arrêt
    'date_deb_droit' => '2024-04-15',       // Date début droit
    'date_deb_dr_force' => null             // Date forcée (optionnel)
];
```

### Sortie

```php
$classe = 'B';  // String : 'A', 'B', ou 'C'
```

## Points Importants

1. **Règle N-2** : Le revenu utilisé est toujours celui de 2 ans avant l'arrêt
2. **Conversion centimes** : Les revenus stockés en base sont en centimes
3. **Date de droit** : Priorité à la date forcée si définie
4. **PSS historiques** : Utilise les valeurs PSS de l'année appropriée
5. **Gestion DateTime** : Supporte objets DateTime et strings

## Dépendances Externes

### DetailsAdherentsService

Fournit les revenus de l'adhérent par année :

```php
$revenus = $detailsAdherentsService->revenuAdhParAnnee('123456');
// Retourne : [2022 => 4700000, 2021 => 4500000, ...]
```

### TauxDeterminationService

Fournit :
1. Les valeurs PSS par année
2. La logique de détermination de classe

```php
$pss = $tauxService->pssParAnnee();
// Retourne : [2024 => 47000, 2023 => 46368, ...]

$classe = $tauxService->determineClasse($revenue, $dateDebutDroit);
// Retourne : 'A', 'B', ou 'C'
```

## Performance

### Optimisations Possibles

Pour éviter les instanciations multiples dans une boucle :

```php
// Mauvaise pratique (dans une boucle)
foreach ($arrets as $arret) {
    $service = new DetailsArretsService();  // ❌ Nouvelle instance
    $classe = $service->getArretClasse($arret);
}

// Bonne pratique
$service = new DetailsArretsService();  // ✅ Une seule instance
foreach ($arrets as $arret) {
    $classe = $service->getArretClasse($arret);
}
```

## Voir Aussi

- [DetailsAdherentsService](./DetailsAdherentsService.md) - Récupération revenus adhérent
- [TauxDeterminationService](./TauxDeterminationService.md) - Détermination classe et taux
- [ArretService](./ArretService.md) - Gestion des arrêts de travail
