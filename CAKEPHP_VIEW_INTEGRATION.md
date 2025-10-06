# Intégration de index.html dans CakePHP 5

## Vue d'ensemble

Le fichier `index.html` a été intégré dans l'architecture CakePHP 5 en séparant les composants (HTML, CSS, JS) selon les conventions du framework.

## Structure des fichiers

### 1. Layout CakePHP
**Fichier**: `templates/layout/ij.php`

Layout personnalisé pour l'application IJ Calculator:
- Charge automatiquement `ij-calculator.css`
- Charge automatiquement `ij-calculator.js`
- Support des Flash messages CakePHP
- Support du rendu des meta tags et scripts additionnels

```php
<!DOCTYPE html>
<html lang="fr">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->fetch('title') ?></title>
    <?= $this->Html->meta('icon') ?>
    <?= $this->Html->css('ij-calculator') ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
    <?= $this->Flash->render() ?>
    <?= $this->fetch('content') ?>
    <?= $this->Html->script('ij-calculator') ?>
    <?= $this->fetch('script') ?>
</body>
</html>
```

### 2. Vue principale
**Fichier**: `templates/IndemniteJournaliere/index.php`

Vue contenant tout le contenu HTML de l'interface:
- Formulaire de configuration
- Champs de revenus et classe
- Gestion des arrêts de travail
- Zone de résultats

Configuration de la vue:
```php
<?php
$this->setLayout('ij');
$this->assign('title', 'Simulateur IJ - Indemnités Journalières');
?>
```

### 3. Feuille de style
**Fichier**: `webroot/css/ij-calculator.css`

Tous les styles extraits de `<style>` dans index.html:
- Styles du container et layout
- Styles des formulaires
- Styles des boutons
- Styles des résultats
- Animations (spinner)

### 4. JavaScript
**Fichier**: `webroot/js/ij-calculator.js`

Copie de `app.js` contenant:
- Gestion des arrêts
- Calculs IJ
- Détermination automatique de classe
- Affichage du revenu journalier
- Communication avec l'API

## Controller mis à jour

**Fichier**: `src/Controller/IndemniteJournaliereController.php`

Méthode `index()` mise à jour:
```php
public function index(): void
{
    // Set the custom layout
    $this->viewBuilder()->setLayout('ij');

    // Liste des statuts disponibles
    $statuts = [
        'M' => 'Médecin',
        'RSPM' => 'Régime Spécial Professions Médicales',
        'CCPL' => 'Contrat Complémentaire',
    ];

    // Liste des classes
    $classes = [
        'A' => 'Classe A',
        'B' => 'Classe B',
        'C' => 'Classe C',
    ];

    // Options disponibles
    $options = [
        25 => '25%',
        50 => '50%',
        100 => '100%',
    ];

    $this->set(compact('statuts', 'classes', 'options'));
}
```

## Routes CakePHP

Pour accéder à l'interface, configurez les routes dans `config/routes.php`:

```php
$routes->scope('/', function (RouteBuilder $builder) {
    // Route pour le calculateur IJ
    $builder->connect(
        '/ij',
        ['controller' => 'IndemniteJournaliere', 'action' => 'index']
    );

    // Ou comme route par défaut
    $builder->connect(
        '/',
        ['controller' => 'IndemniteJournaliere', 'action' => 'index']
    );
});
```

## Accès à l'application

### Développement (avec serveur intégré)
```bash
# Démarrer le serveur CakePHP
bin/cake server

# Accéder à l'interface
# http://localhost:8765/ij
# ou
# http://localhost:8765/
```

### Production (avec Apache/Nginx)
```
https://votre-domaine.com/ij
```

## Avantages de l'intégration CakePHP

### 1. Organisation du code
- ✅ Séparation claire HTML/CSS/JS
- ✅ Respect des conventions CakePHP
- ✅ Maintenabilité améliorée

### 2. Fonctionnalités CakePHP
- ✅ Flash messages intégrés
- ✅ Helper HTML pour assets
- ✅ Gestion automatique des URLs
- ✅ Support CSRF
- ✅ Internationalisation (i18n)

### 3. Performance
- ✅ Cache des assets
- ✅ Minification possible
- ✅ CDN compatible

### 4. Sécurité
- ✅ Protection CSRF automatique
- ✅ Échappement des données
- ✅ Headers de sécurité

## Personnalisation

### Modifier le titre de la page
Dans `templates/IndemniteJournaliere/index.php`:
```php
$this->assign('title', 'Votre titre personnalisé');
```

### Ajouter des meta tags
```php
<?php
$this->assign('title', 'Simulateur IJ');
?>
<?php $this->start('meta'); ?>
<meta name="description" content="Calculateur d'indemnités journalières">
<meta name="keywords" content="IJ, indemnités, médecin">
<?php $this->end(); ?>
```

### Ajouter du CSS supplémentaire
```php
<?php $this->start('css'); ?>
<?= $this->Html->css('custom-styles') ?>
<?php $this->end(); ?>
```

### Ajouter du JavaScript supplémentaire
```php
<?php $this->start('script'); ?>
<script>
// Code JS personnalisé
console.log('Application IJ chargée');
</script>
<?php $this->end(); ?>
```

## Compatibilité avec index.html

L'interface CakePHP est **100% compatible** avec l'interface standalone `index.html`:
- Même apparence visuelle
- Même comportement JavaScript
- Mêmes fonctionnalités

### Différences
| Aspect | index.html | CakePHP View |
|--------|------------|--------------|
| Structure | Monolithique | Modulaire (layout + view) |
| CSS | Inline `<style>` | Fichier externe |
| JS | Inline `<script src>` | Fichier webroot |
| URL | `index.html` | `/ij` ou `/` |
| Flash messages | JavaScript | CakePHP Flash |

## API Endpoint

L'API reste inchangée et fonctionne avec les deux interfaces:

```php
// Endpoint API
POST /indemnite-journaliere/api-calculate.json

// Utilisé par
// - index.html (via api.php)
// - CakePHP view (via routing CakePHP)
```

## Migration depuis index.html

Pour migrer d'un usage de `index.html` vers la vue CakePHP:

### Étape 1: Déployer les fichiers
```bash
# Les fichiers sont déjà en place:
# - templates/layout/ij.php
# - templates/IndemniteJournaliere/index.php
# - webroot/css/ij-calculator.css
# - webroot/js/ij-calculator.js
```

### Étape 2: Configurer les routes
Ajouter dans `config/routes.php`:
```php
$builder->connect('/ij', ['controller' => 'IndemniteJournaliere', 'action' => 'index']);
```

### Étape 3: Tester
```bash
bin/cake server
# Ouvrir http://localhost:8765/ij
```

### Étape 4: Vérifier l'API
S'assurer que `api.php` ou l'endpoint CakePHP fonctionne:
```bash
curl -X POST http://localhost:8765/indemnite-journaliere/api-calculate.json \
  -H "Content-Type: application/json" \
  -d '{"arrets":[], "classe":"B", "statut":"M"}'
```

## Développement

### Structure de répertoires
```
/home/mista/work/ij/
├── templates/
│   ├── layout/
│   │   └── ij.php                    # Layout personnalisé
│   └── IndemniteJournaliere/
│       └── index.php                 # Vue principale
├── webroot/
│   ├── css/
│   │   └── ij-calculator.css         # Styles
│   └── js/
│       └── ij-calculator.js          # JavaScript
├── src/
│   └── Controller/
│       └── IndemniteJournaliereController.php
└── config/
    └── routes.php                    # Configuration des routes
```

### Commandes utiles

#### Démarrer le serveur
```bash
bin/cake server
# ou sur un port spécifique
bin/cake server -p 8080
```

#### Vider le cache
```bash
bin/cake cache clear_all
```

#### Vérifier les routes
```bash
bin/cake routes
```

## Dépannage

### Problème: CSS ne se charge pas
```bash
# Vérifier que le fichier existe
ls -la webroot/css/ij-calculator.css

# Vérifier les permissions
chmod 644 webroot/css/ij-calculator.css
```

### Problème: JavaScript ne fonctionne pas
```bash
# Vérifier que le fichier existe
ls -la webroot/js/ij-calculator.js

# Vérifier la console navigateur pour erreurs
# Chrome: F12 > Console
```

### Problème: API ne répond pas
```bash
# Vérifier l'endpoint
curl -v http://localhost:8765/indemnite-journaliere/api-calculate.json

# Vérifier les logs
tail -f logs/error.log
```

### Problème: Layout non appliqué
Dans le controller, vérifier:
```php
$this->viewBuilder()->setLayout('ij');
```

Dans la vue, vérifier:
```php
$this->setLayout('ij');
```

## Tests

### Test de la vue
```bash
# Accéder à la page
curl http://localhost:8765/ij

# Vérifier que le HTML est retourné
# Vérifier que les assets sont chargés
```

### Test de l'API
```bash
# Test avec données minimales
curl -X POST http://localhost:8765/indemnite-journaliere/api-calculate.json \
  -H "Content-Type: application/json" \
  -d '{
    "arrets": [],
    "classe": "B",
    "statut": "M",
    "birth_date": "1989-09-26",
    "current_date": "2024-09-09"
  }'
```

## Production

### Optimisation des assets

#### Minification CSS
```bash
# Installer un minifier
npm install -g clean-css-cli

# Minifier
cleancss -o webroot/css/ij-calculator.min.css webroot/css/ij-calculator.css
```

#### Minification JavaScript
```bash
# Installer un minifier
npm install -g terser

# Minifier
terser webroot/js/ij-calculator.js -o webroot/js/ij-calculator.min.js
```

#### Utiliser les versions minifiées
Dans `templates/layout/ij.php`:
```php
<?php if (Configure::read('debug')): ?>
    <?= $this->Html->css('ij-calculator') ?>
    <?= $this->Html->script('ij-calculator') ?>
<?php else: ?>
    <?= $this->Html->css('ij-calculator.min') ?>
    <?= $this->Html->script('ij-calculator.min') ?>
<?php endif; ?>
```

### Configuration Apache

```apache
<VirtualHost *:80>
    ServerName ij-calculator.local
    DocumentRoot /home/mista/work/ij/webroot

    <Directory /home/mista/work/ij/webroot>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Configuration Nginx

```nginx
server {
    listen 80;
    server_name ij-calculator.local;
    root /home/mista/work/ij/webroot;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Conclusion

L'intégration de `index.html` dans CakePHP 5 est maintenant complète:

✅ **Layout personnalisé**: `templates/layout/ij.php`
✅ **Vue principale**: `templates/IndemniteJournaliere/index.php`
✅ **CSS externe**: `webroot/css/ij-calculator.css`
✅ **JS externe**: `webroot/js/ij-calculator.js`
✅ **Controller mis à jour**: Utilise le layout `ij`
✅ **100% compatible**: Même interface que index.html
✅ **Prêt pour la production**: Optimisations possibles

L'application peut maintenant être:
- Développée avec les outils CakePHP
- Déployée comme application web complète
- Intégrée avec d'autres modules CakePHP
- Maintenue avec les conventions du framework
