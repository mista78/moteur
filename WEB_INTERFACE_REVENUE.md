# Interface Web - Détermination Automatique de Classe

## Vue d'ensemble

L'interface web `index.html` a été mise à jour pour inclure la **détermination automatique de la classe** du médecin basée sur les revenus de l'année N-2.

## Nouveaux champs ajoutés

### 1. Revenus N-2

```html
<label for="revenu_n_moins_2">Revenus N-2 (€)</label>
<input type="number" id="revenu_n_moins_2" placeholder="Ex: 85000" step="1000">
```

- **But**: Saisir les revenus de l'année N-2
- **Format**: Nombre en euros (ex: 85000)
- **Optionnel**: Si laissé vide et non taxé d'office, la classe doit être sélectionnée manuellement

### 2. Taxé d'office

```html
<input type="checkbox" id="taxe_office">
<label for="taxe_office">Taxé d'office (revenus non communiqués)</label>
```

- **But**: Indiquer que le médecin est taxé d'office (revenus non communiqués)
- **Effet**: Si coché, la classe A est automatiquement appliquée

### 3. Classe mise à jour

Le sélecteur de classe a été modifié pour inclure:

```html
<select id="classe" required>
    <option value="">-- Sélectionner ou auto-déterminer --</option>
    <option value="A">Classe A (< 1 PASS, < 47 000 €)</option>
    <option value="B">Classe B (1-3 PASS, 47 000-141 000 €)</option>
    <option value="C">Classe C (> 3 PASS, > 141 000 €)</option>
</select>
```

- Les seuils sont maintenant affichés directement dans les options
- La classe peut être sélectionnée manuellement ou déterminée automatiquement

### 4. Indicateur de détermination automatique

```html
<small id="classe_auto_info" style="color: #28a745; font-size: 12px; display: none;"></small>
```

- Affiche un message de confirmation quand la classe est déterminée automatiquement
- Affiche le calcul exact (ex: "85 000 € = 1.81 PASS")

## Fonctionnement

### 1. Détermination automatique

Lorsque l'utilisateur saisit les revenus N-2:

1. Le JavaScript calcule automatiquement la classe appropriée
2. La classe est sélectionnée dans le dropdown
3. Un message de confirmation s'affiche en vert

**Exemple de messages:**

- "✓ Classe A déterminée: 30 000 € < 1 PASS (47 000 €)"
- "✓ Classe B déterminée: 85 000 € = 1.81 PASS (entre 1 et 3 PASS)"
- "✓ Classe C déterminée: 200 000 € = 4.26 PASS (> 3 PASS)"
- "✓ Classe A déterminée automatiquement (taxé d'office)"

### 2. Modification de la valeur PASS

Si l'utilisateur modifie la valeur du PASS, la classe est recalculée automatiquement.

### 3. Override manuel

L'utilisateur peut toujours sélectionner manuellement une classe différente de celle déterminée automatiquement.

## Info-box ajoutée

Une nouvelle boîte d'information a été ajoutée en haut de la page:

```
📊 Détermination automatique de la classe:
• Classe A: Revenus N-2 < 47 000 € (1 PASS)
• Classe B: Revenus N-2 entre 47 000 € et 141 000 € (1-3 PASS)
• Classe C: Revenus N-2 > 141 000 € (3 PASS)
• Si taxé d'office (revenus non communiqués): Classe A automatiquement

La classe est basée sur les revenus de l'année N-2 (ex: pour 2024, revenus de 2022)
```

## Code JavaScript ajouté

### Fonction principale

```javascript
function setupClassAutoDetermination() {
    const revenuInput = document.getElementById('revenu_n_moins_2');
    const taxeOfficeCheckbox = document.getElementById('taxe_office');
    const classeSelect = document.getElementById('classe');
    const classeAutoInfo = document.getElementById('classe_auto_info');
    const passValueInput = document.getElementById('pass_value');

    function updateClasseAuto() {
        const revenu = parseFloat(revenuInput.value);
        const taxeOffice = taxeOfficeCheckbox.checked;
        const passValue = parseFloat(passValueInput.value) || 47000;

        // Logique de détermination...
    }

    // Event listeners
    revenuInput.addEventListener('input', updateClasseAuto);
    taxeOfficeCheckbox.addEventListener('change', updateClasseAuto);
    passValueInput.addEventListener('input', updateClasseAuto);
}
```

### Fonction de formatage

```javascript
function formatMoney(value) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
}
```

## Page de test autonome

Une page HTML autonome a été créée pour tester la fonctionnalité:

**Fichier:** `test_classe_determination.html`

### Fonctionnalités

- Interface simple avec revenus et checkbox "taxé d'office"
- Résultat en temps réel avec couleur selon la classe
- Boutons d'exemples rapides:
  - Médecin débutant (30 000 €)
  - Médecin établi (85 000 €)
  - Hauts revenus (200 000 €)
  - Cas limites (46 500 €, 47 000 €, 141 000 €, 142 000 €)
  - Taxé d'office
- Tableau récapitulatif des seuils

### Utilisation

```bash
# Ouvrir dans un navigateur
open test_classe_determination.html
```

## Scénarios d'utilisation

### Scénario 1: Revenus normaux

1. Utilisateur saisit "85000" dans le champ "Revenus N-2"
2. La classe B est automatiquement sélectionnée
3. Message affiché: "✓ Classe B déterminée: 85 000 € = 1.81 PASS (entre 1 et 3 PASS)"
4. L'utilisateur peut continuer avec le calcul IJ

### Scénario 2: Taxé d'office

1. Utilisateur coche "Taxé d'office"
2. La classe A est automatiquement sélectionnée
3. Message affiché: "✓ Classe A déterminée automatiquement (taxé d'office)"
4. Le champ revenus N-2 n'est pas nécessaire

### Scénario 3: Modification PASS

1. Utilisateur saisit "85000" dans revenus
2. Classe B sélectionnée (avec PASS=47000, c'est 1.81 PASS)
3. Utilisateur change PASS à "50000"
4. La classe est recalculée: 85000/50000 = 1.7 PASS → toujours Classe B
5. Message mis à jour automatiquement

### Scénario 4: Override manuel

1. Utilisateur saisit "85000" → Classe B auto-déterminée
2. Utilisateur change manuellement la classe à "C"
3. Le message d'auto-détermination reste visible mais la classe sélectionnée est C
4. Si l'utilisateur modifie les revenus, la classe est re-déterminée automatiquement

## Validation

### Cas testés

| Revenus | Taxé | Classe attendue | Message |
|---------|------|-----------------|---------|
| 30 000 € | Non | A | < 1 PASS |
| 85 000 € | Non | B | 1.81 PASS |
| 200 000 € | Non | C | 4.26 PASS |
| 46 500 € | Non | A | < 1 PASS |
| 47 000 € | Non | B | Exactement 1 PASS |
| 141 000 € | Non | B | Exactement 3 PASS |
| 142 000 € | Non | C | > 3 PASS |
| - | Oui | A | Taxé d'office |
| (vide) | Non | - | Pas de détermination |

## Intégration avec le calcul IJ

Les données de revenus sont envoyées au backend via l'API:

```javascript
const formData = {
    // ... autres champs
    revenu_n_moins_2: parseFloat(document.getElementById('revenu_n_moins_2').value),
    taxe_office: document.getElementById('taxe_office').checked,
    classe: document.getElementById('classe').value
};
```

Le backend peut alors:
1. Utiliser la classe fournie directement
2. Ou re-vérifier la détermination côté serveur pour sécurité

## Améliorations futures possibles

1. **Historique PASS par année**
   - Stocker les valeurs PASS historiques (2022: 41 136 €, 2023: 43 992 €, etc.)
   - Utiliser la valeur correcte selon l'année N-2

2. **Calendrier des revenus**
   - Permettre de saisir les revenus par année
   - Calculer automatiquement l'année N-2 selon la date d'ouverture des droits

3. **Validation côté serveur**
   - Ajouter un endpoint API pour valider la classe déterminée
   - Retourner une confirmation ou un avertissement

4. **Aide contextuelle**
   - Tooltip expliquant comment trouver les revenus N-2
   - Lien vers les documents fiscaux pertinents

5. **Sauvegarde de profil**
   - Permettre de sauvegarder les revenus du médecin
   - Pré-remplir automatiquement lors des prochains calculs

## Compatibilité

- Tous navigateurs modernes (Chrome, Firefox, Safari, Edge)
- Mobile responsive
- JavaScript ES6+
- Pas de dépendances externes

## CSS ajouté

Styles pour l'info-box de détermination automatique:

```css
.info-box {
    background: #fff3cd;
    border-left-color: #ffc107;
}
```

Message de confirmation:

```css
#classe_auto_info {
    color: #28a745;
    font-size: 12px;
}
```

## Affichage du calcul du revenu journalier

### Boîte d'information dynamique

Une nouvelle boîte d'information s'affiche automatiquement pour montrer le détail du calcul du revenu journalier :

```html
<div id="revenu_info_box" class="info-box" style="background: #e7f3ff; border-left-color: #2196F3; display: none;">
    <strong>💰 Calcul du revenu journalier:</strong>
    <div id="revenu_calculation_detail" style="margin-top: 8px; color: #1976D2;"></div>
</div>
```

### Contenu affiché

Pour chaque classe, la boîte affiche :

- **Classe** : A, B ou C
- **Formule** : Calcul détaillé avec les valeurs
- **Revenu annuel** : Montant total annuel
- **Revenu par jour** : Montant journalier (mis en évidence)
- **Nombre de PASS** : Ratio revenu/PASS

**Exemple pour Classe B avec 85 000 € :**
```
Classe:          B
Formule:         85 000 € / 730 = 116,44 €
Revenu annuel:   85 000 €
Revenu par jour: 116,44 €
Nombre de PASS:  1.81
```

### Fonctions JavaScript ajoutées

#### showRevenuCalculation()

```javascript
function showRevenuCalculation(classe, passValue, revenu) {
    const infoBox = document.getElementById('revenu_info_box');
    const detailDiv = document.getElementById('revenu_calculation_detail');

    if (!classe || classe === '') {
        hideRevenuCalculation();
        return;
    }

    let revenuAnnuel, revenuPerDay, formula, nbPass;

    switch (classe.toUpperCase()) {
        case 'A':
            revenuAnnuel = passValue;
            revenuPerDay = passValue / 730;
            nbPass = 1;
            formula = `${formatMoney(passValue)} / 730 = ${formatMoney(revenuPerDay, true)}`;
            break;

        case 'B':
            if (revenu && !isNaN(revenu)) {
                revenuAnnuel = revenu;
                revenuPerDay = revenu / 730;
                nbPass = revenu / passValue;
                formula = `${formatMoney(revenu)} / 730 = ${formatMoney(revenuPerDay, true)}`;
            } else {
                // Default to 2 PASS if no revenue specified
                revenuAnnuel = 2 * passValue;
                revenuPerDay = revenuAnnuel / 730;
                nbPass = 2;
                formula = `${formatMoney(revenuAnnuel)} / 730 = ${formatMoney(revenuPerDay, true)} (défaut: 2 PASS)`;
            }
            break;

        case 'C':
            revenuAnnuel = 3 * passValue;
            revenuPerDay = revenuAnnuel / 730;
            nbPass = 3;
            formula = `(${formatMoney(passValue)} × 3) / 730 = ${formatMoney(revenuPerDay, true)}`;
            break;

        default:
            hideRevenuCalculation();
            return;
    }

    detailDiv.innerHTML = `
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 8px 16px; font-size: 14px;">
            <strong>Classe:</strong> <span>${classe.toUpperCase()}</span>
            <strong>Formule:</strong> <span>${formula}</span>
            <strong>Revenu annuel:</strong> <span>${formatMoney(revenuAnnuel)}</span>
            <strong>Revenu par jour:</strong> <span style="font-size: 16px; font-weight: bold; color: #0d6efd;">${formatMoney(revenuPerDay, true)}</span>
            <strong>Nombre de PASS:</strong> <span>${nbPass.toFixed(2)}</span>
        </div>
    `;

    infoBox.style.display = 'block';
}
```

#### hideRevenuCalculation()

```javascript
function hideRevenuCalculation() {
    const infoBox = document.getElementById('revenu_info_box');
    if (infoBox) {
        infoBox.style.display = 'none';
    }
}
```

#### formatMoney() mise à jour

```javascript
function formatMoney(value, showDecimals = false) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: showDecimals ? 2 : 0,
        maximumFractionDigits: showDecimals ? 2 : 0
    }).format(value);
}
```

### Déclencheurs d'affichage

La boîte de calcul s'affiche automatiquement quand :

1. L'utilisateur saisit un revenu N-2
2. L'utilisateur coche/décoche "Taxé d'office"
3. L'utilisateur change la valeur du PASS
4. L'utilisateur sélectionne manuellement une classe

### Page de test autonome

Une page de test a été créée pour tester l'interface de calcul du revenu :

**Fichier:** `test_revenue_interface.html`

#### Fonctionnalités

- Saisie du revenu avec détermination automatique de classe
- Affichage en temps réel du calcul du revenu journalier
- Boutons d'exemples rapides (30k, 85k, 100k, 200k, 1 PASS, 3 PASS)
- Formules détaillées avec mise en évidence du résultat

#### Utilisation

```bash
# Ouvrir dans un navigateur
open test_revenue_interface.html
```

## Formules de calcul implémentées

### Classe A
```
Revenu par jour = montant_1_pass / 730
```
**Exemple** : 47 000 € / 730 = **64,38 €/jour**

### Classe B
```
Revenu par jour = revenu / 730
```
**Exemples** :
- 85 000 € / 730 = **116,44 €/jour**
- 100 000 € / 730 = **136,99 €/jour**

### Classe C
```
Revenu par jour = (montant_1_pass * 3) / 730
```
**Exemple** : (47 000 € × 3) / 730 = **193,15 €/jour**

## Fichiers modifiés

### index.html
1. Ajout d'info-box montrant les formules de calcul (lignes 286-295)
2. Ajout de boîte d'information pour affichage du calcul (lignes 297-300)

### app.js
1. Mise à jour `setupClassAutoDetermination()` pour appeler `showRevenuCalculation()`
2. Ajout fonction `showRevenuCalculation()` (lignes 91-151)
3. Ajout fonction `hideRevenuCalculation()` (lignes 153-161)
4. Mise à jour `formatMoney()` avec paramètre `showDecimals` (lignes 163-175)
5. Ajout listener sur changement de classe manuelle (lignes 80-88)

### Nouveaux fichiers
- `test_revenue_interface.html` : Page de test autonome de l'interface

## Scénarios d'utilisation mis à jour

### Scénario 1: Revenus normaux avec affichage du calcul

1. Utilisateur saisit "85000" dans le champ "Revenus N-2"
2. La classe B est automatiquement sélectionnée
3. Message affiché: "✓ Classe B déterminée: 85 000 € = 1.81 PASS"
4. **Nouveau** : Boîte de calcul affiche :
   - Formule : 85 000 € / 730 = 116,44 €
   - Revenu annuel : 85 000 €
   - Revenu par jour : **116,44 €**
   - Nombre de PASS : 1.81

### Scénario 2: Changement de PASS avec recalcul

1. Utilisateur saisit "85000" dans revenus
2. Classe B sélectionnée, revenu/jour = 116,44 €
3. Utilisateur change PASS à "50000"
4. Classe B toujours sélectionnée (85000 / 50000 = 1.7 PASS)
5. **Nouveau** : Boîte de calcul mise à jour automatiquement :
   - Formule : 85 000 € / 730 = 116,44 €
   - Revenu par jour reste : **116,44 €** (dépend du revenu, pas du PASS)

### Scénario 3: Sélection manuelle de classe

1. Utilisateur saisit "85000" → Classe B auto-déterminée
2. Utilisateur change manuellement la classe à "C"
3. **Nouveau** : Boîte de calcul mise à jour :
   - Formule : (47 000 € × 3) / 730 = 193,15 €
   - Revenu annuel : 141 000 €
   - Revenu par jour : **193,15 €**
   - Nombre de PASS : 3.00

## Conclusion

L'interface web permet maintenant :
1. Une détermination automatique et intuitive de la classe du médecin
2. **Un affichage en temps réel du calcul du revenu journalier avec les formules détaillées**
3. Une flexibilité d'override manuel si nécessaire
4. Une transparence complète sur les calculs effectués

Ces améliorations simplifient grandement le processus de calcul des indemnités journalières et permettent à l'utilisateur de comprendre exactement comment son revenu journalier est calculé.
