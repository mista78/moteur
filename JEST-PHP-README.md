# Jest-PHP: Framework de test style Jest pour PHP

Framework de test inspiré de Jest, permettant d'écrire des tests en PHP avec une syntaxe familière.

## Installation

Inclure simplement le fichier `jest-php.php` dans vos tests:

```php
require_once 'jest-php.php';
```

## Utilisation de base

### Structure d'un test

```php
<?php

require_once 'jest-php.php';

describe('Mon composant', function() {

    test('devrait faire quelque chose', function() {
        $result = maFonction();
        expect($result)->toBe(42);
    });

    it('devrait aussi faire autre chose', function() {
        $value = autreFunction();
        expect($value)->toBeGreaterThan(0);
    });
});

// Lancer les tests
JestPHP::run();
```

### Fonctions disponibles

- `describe($description, $callback)` - Groupe de tests
- `test($description, $callback)` - Alias de `it()`
- `it($description, $callback)` - Définit un test individuel
- `expect($value)` - Crée une assertion

## Matchers disponibles

### Égalité

```php
expect($value)->toBe(42);                    // ===
expect($value)->toEqual([1, 2, 3]);         // ==
```

### Comparaisons numériques

```php
expect($value)->toBeCloseTo(3.14, 0.01);    // Approximation (± precision)
expect($value)->toBeGreaterThan(10);         // >
expect($value)->toBeLessThan(100);           // <
```

### Valeurs booléennes et null

```php
expect($value)->toBeNull();                  // === null
expect($value)->toBeTruthy();                // valeur vraie (!!)
expect($value)->toBeFalsy();                 // valeur fausse (!)
```

### Tableaux et strings

```php
expect([1, 2, 3])->toContain(2);            // in_array()
expect('hello world')->toContain('world');   // strpos()
expect($array)->toHaveProperty('key');       // array_key_exists()
expect($array)->toMatchArray(['a' => 1]);    // Comparaison de tableaux
```

## Exemples

### Test simple

```php
describe('Calculator', function() {
    test('should add two numbers', function() {
        $result = 2 + 2;
        expect($result)->toBe(4);
    });
});

JestPHP::run();
```

### Test avec IJCalculator

```php
describe('IJCalculator', function() {

    test('should calculate correct amount', function() {
        $calculator = new IJCalculator('taux.csv');

        $result = $calculator->calculateAmount([
            'arrets' => $mockData,
            'statut' => 'M',
            'classe' => 'A',
            'option' => 100,
            // ... autres paramètres
        ]);

        expect($result['montant'])->toBeCloseTo(750.60, 0.01);
        expect($result)->toHaveProperty('nb_jours');
        expect($result['age'])->toBeGreaterThan(0);
    });

    test('should handle multiple arrets', function() {
        // ... votre test
    });
});

JestPHP::run();
```

### Organiser plusieurs groupes de tests

```php
describe('Age calculation', function() {
    test('should calculate correct age', function() {
        $calculator = new IJCalculator('taux.csv');
        $age = $calculator->calculateAge('2024-09-09', '1989-09-26');
        expect($age)->toBe(34);
    });
});

describe('Trimestres calculation', function() {
    test('should calculate trimestres', function() {
        // ... votre test
    });
});

describe('Payment calculation', function() {
    test('should apply valid_med_controleur', function() {
        // ... votre test
    });
});

JestPHP::run();
```

## Sortie des tests

Le framework affiche les résultats dans un format similaire à Jest:

```
 ✓ IJCalculator › should calculate correct amount
 ✓ IJCalculator › should handle multiple arrets
 ✗ Payment › should fail for invalid data

Failed Tests:
  ● Payment › should fail for invalid data
    Expected 0, but got 100

Test Suites: 1 failed, 3 total
Tests:       1 failed, 2 passed, 3 total
Time:        5.63ms
```

## Conseils d'utilisation

1. **Un fichier par suite de tests** - Créer des fichiers comme `calculator.test.php`, `age.test.php`, etc.

2. **Grouper les tests logiquement** - Utiliser `describe` pour regrouper les tests liés

3. **Noms descriptifs** - Utiliser des descriptions claires pour les tests

4. **Tests atomiques** - Chaque test devrait tester une seule chose

5. **Utiliser les bons matchers** - Choisir le matcher approprié pour le type de test

## Exemples de fichiers de test

Voir `tests.jest.php` pour des exemples complets d'utilisation.

## Lancer les tests

```bash
php tests.jest.php
```

Ou créer un script pour lancer tous les tests:

```bash
php -f tests.jest.php
```
