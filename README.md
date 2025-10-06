# Simulateur IJ - Indemnités Journalières

Application web pour calculer les indemnités journalières des professionnels de santé en France.

## Fonctionnalités

- ✅ Calcul des dates d'effet des droits aux IJ (règle des 90 jours)
- ✅ Calcul des dates de fin de paiement selon l'âge
- ✅ Calcul du montant total des IJ avec taux progressifs
- ✅ Gestion des rechutes et prolongations
- ✅ Support des statuts : Médecins (M), RSPM, CCPL
- ✅ Classes de cotisation A, B, C
- ✅ Calcul basé sur le PASS CPAM (configurable)
- ✅ Chargement de données de test via mock.json

## Structure des fichiers

```
├── IJCalculator.php    # Classe principale de calcul
├── api.php             # Endpoints API REST
├── index.html          # Interface web
├── app.js              # Logique JavaScript frontend
├── taux.csv            # Table des taux par année
├── mock.json           # Données de test
├── README.md           # Ce fichier
└── CLAUDE.md           # Documentation pour Claude Code
```

## Installation

### Prérequis
- PHP 7.4 ou supérieur
- Serveur web (Apache, Nginx, ou PHP built-in server)

### Lancement rapide

```bash
# Depuis le répertoire du projet
php -S localhost:8000
```

Ouvrir dans le navigateur : `http://localhost:8000`

## API Endpoints

### 1. Calculer la date d'effet des droits

**POST** `/api.php?endpoint=date-effet`

```json
{
  "arrets": [
    {
      "arret-from-line": "2023-09-04",
      "arret-to-line": "2023-11-10",
      "rechute-line": "0",
      "dt-line": "1",
      "gpm-member-line": "1",
      "declaration-date-line": "2023-09-19"
    }
  ],
  "birth_date": "1960-01-15",
  "previous_cumul_days": 0
}
```

**Réponse:**
```json
{
  "success": true,
  "data": [
    {
      "arret-from-line": "2023-09-04",
      "arret-to-line": "2023-11-10",
      "date-effet": "2023-12-03",
      ...
    }
  ]
}
```

### 2. Calculer la date de fin de paiement

**POST** `/api.php?endpoint=end-payment`

```json
{
  "arrets": [...],
  "birth_date": "1960-01-15",
  "current_date": "2024-01-15",
  "previous_cumul_days": 0
}
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "end_period_1": "2024-12-02",
    "end_period_2": "2025-11-02",
    "end_period_3": "2026-10-02"
  }
}
```

### 3. Calcul complet des IJ

**POST** `/api.php?endpoint=calculate`

```json
{
  "statut": "M",
  "classe": "A",
  "option": "0,25",
  "birth_date": "1960-01-15",
  "current_date": "2024-01-15",
  "attestation_date": "2024-01-31",
  "last_payment_date": null,
  "nb_trimestres": 20,
  "previous_cumul_days": 0,
  "patho_anterior": false,
  "prorata": 1,
  "forced_rate": null,
  "pass_value": 47000,
  "arrets": [...]
}
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "nb_jours": 59,
    "montant": 4428.54,
    "age": 64,
    "total_cumul_days": 59,
    "arrets": [...],
    "end_payment_dates": {...}
  }
}
```

### 4. Calculer le revenu annuel (PASS)

**POST** `/api.php?endpoint=revenu`

```json
{
  "classe": "A",
  "nb_pass": null,
  "pass_value": 47000
}
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "nb_pass": 1,
    "revenu_annuel": 47000,
    "pass_value": 47000
  }
}
```

### 5. Charger les données de test

**GET** `/api.php?endpoint=load-mock`

**Réponse:**
```json
{
  "success": true,
  "data": [...]
}
```

## Calcul du revenu annuel selon la classe

### Pour les médecins (Statut M)

- **Classe A**: 1 PASS = 47 000 €
- **Classe B**: Revenu annuel / 730 par PASS
- **Classe C**: 3 PASS = 141 000 €

Le PASS (Plafond Annuel de la Sécurité Sociale) est configurable dans l'interface et peut être modifié selon l'année.

## Règles métier

### Date d'effet des droits
- Commence après 90 jours cumulés d'arrêt de travail
- Rechute : reprise au 1er jour ou au 15ème jour selon configuration
- Ajustement de +31 jours pour DT non excusée
- Ajustement de +31 jours pour mise à jour compte GPM

### Périodes de paiement selon l'âge

**< 62 ans**: Taux unique

**62-69 ans**: 3 périodes
- Période 1 : 0-365 jours (taux 1)
- Période 2 : 366-730 jours (taux 2)
- Période 3 : 731-1095 jours (taux 3)

**≥ 70 ans**: Maximum 365 jours, taux réduit

### Conditions d'éligibilité
- Minimum 8 trimestres d'affiliation
- Maximum 1095 jours (3 ans) d'indemnisation
- Pour les 70+: Maximum 365 jours par affiliation

## Format du fichier taux.csv

```csv
id;date_start;date_end;taux_a1;taux_a2;taux_a3;taux_b1;taux_b2;taux_b3;taux_c1;taux_c2;taux_c3
1;2024-01-01;2024-12-31;75.06;38.3;56.3;112.59;57.45;84.45;150.12;76.6;112.59
```

- `date_start`, `date_end`: Période de validité
- `taux_X1`: Taux période 1 (0-365 jours)
- `taux_X2`: Taux période 2 (366-730 jours)
- `taux_X3`: Taux période 3 (731-1095 jours)
- X = a, b, ou c pour les classes A, B, C

## Format du fichier mock.json

```json
[
  {
    "code-patho-line": "2",
    "arret-from-line": "2023-09-04",
    "arret-to-line": "2023-11-10",
    "rechute-line": "0",
    "dt-line": "1",
    "gpm-member-line": "1",
    "num-gpm-member-line": "123456",
    "reprise-activitev2-line": "1",
    "reprise-activitev2-date-line": "2025-09-01",
    "declaration-date-line": "2023-09-19"
  }
]
```

## Tests

Utiliser le bouton "📋 Charger données de test" dans l'interface pour charger automatiquement les données du fichier mock.json.

## Développement

### Modification des taux

Éditer le fichier `taux.csv` avec les nouveaux taux et dates de validité.

### Modification du PASS

La valeur du PASS peut être modifiée:
1. Dans l'interface web (champ "Valeur PASS")
2. Via l'API en passant le paramètre `pass_value`

### Debug

Pour afficher les erreurs PHP, ajouter au début de `api.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Licence

Usage interne uniquement.
