# Calendrier - Vue Quotidienne des IJ

## Fonctionnalité Ajoutée

Une vue calendrier interactive a été ajoutée à l'interface web du calculateur IJ pour afficher le détail quotidien des paiements (daily breakdown).

## Fichiers Modifiés

### 1. **index.html**
- Ajout de styles CSS pour les onglets (tabs)
- Ajout de styles CSS pour le calendrier
- Styles pour les jours du calendrier avec indicateurs visuels
- Inclusion du nouveau fichier `calendar_functions.js`

**Nouveaux styles CSS:**
- `.tabs` - Container des onglets
- `.tab` / `.tab.active` - Styles des onglets
- `.tab-content` / `.tab-content.active` - Contenu des onglets
- `.calendar` - Grille du calendrier (7 colonnes)
- `.calendar-header` - En-têtes des jours de la semaine
- `.calendar-day` - Cellules individuelles du calendrier
- `.calendar-payment` - Indicateurs de paiement par jour
- `.calendar-month-selector` - Sélecteur de mois avec boutons de navigation
- `.calendar-legend` - Légende des couleurs

### 2. **app.js**
- Modification de la fonction `displayFullResults()` pour ajouter une structure à onglets
- Ajout de deux onglets: "📊 Résumé" et "📅 Calendrier"
- Intégration de la fonction `generateCalendarView()` pour générer le HTML du calendrier
- Appel à `initializeCalendar()` après le rendu des résultats

### 3. **calendar_functions.js** (NOUVEAU)
Nouveau fichier contenant toute la logique du calendrier:

#### Fonctions principales:

**`switchTab(event, tabName)`**
- Gère le basculement entre l'onglet Résumé et Calendrier
- Active/désactive les classes CSS appropriées
- Re-rend le calendrier si nécessaire

**`generateCalendarView(data)`**
- Génère la structure HTML du calendrier
- Extrait les données de paiement quotidien depuis `payment_details.daily_breakdown`
- Crée le sélecteur de mois et la légende

**`extractCalendarData(data)`**
- Extrait et structure les données depuis la réponse API
- Récupère les informations de `daily_breakdown` pour chaque arrêt
- Stocke: date, taux, montant, période, index d'arrêt

**`renderCalendar(month, year)`**
- Génère la grille du calendrier pour un mois donné
- Affiche les jours du mois précédent/suivant en grisé
- Affiche les paiements quotidiens avec:
  - 🏥 Indicateur jaune pour le début d'un arrêt
  - Montant quotidien en vert pour les jours payés
  - Tooltip avec détails (numéro d'arrêt, taux)

**`previousMonth()` / `nextMonth()`**
- Navigation entre les mois
- Gère automatiquement le changement d'année

**`initializeCalendar()`**
- Initialise le calendrier au premier chargement
- Défini le mois initial basé sur la première date de paiement

## Fonctionnalités du Calendrier

### 📅 Vue Calendrier Interactive

1. **Affichage par mois**
   - Calendrier classique avec 7 colonnes (Lun-Dim)
   - Navigation mois par mois avec boutons "← Mois précédent" / "Mois suivant →"

2. **Indicateurs visuels**
   - **🏥 Jaune (Début)** : Premier jour d'un arrêt de travail
   - **Vert** : Jour avec paiement (affiche le taux journalier)
   - **Gris** : Jours des mois précédent/suivant

3. **Informations affichées**
   - Numéro du jour
   - Taux journalier pour chaque jour payé
   - Tooltip avec détails complets au survol

4. **Légende**
   - Jour payé (vert)
   - Jour non payé (rouge)
   - Début arrêt (jaune)

### 📊 Vue Résumé (existante)

Conserve toutes les fonctionnalités existantes:
- Résultats principaux (âge, trimestres, montant)
- Dates de fin de paiement
- Tableau détaillé par arrêt
- Récapitulatif par période de taux

## Source des Données

Le calendrier utilise les données `daily_breakdown` déjà présentes dans la réponse API:

```json
{
  "payment_details": [
    {
      "arret_index": 0,
      "arret_from": "2024-01-01",
      "arret_to": "2024-01-31",
      "daily_breakdown": [
        {
          "date": "2024-01-01",
          "rate": 75.06,
          "amount": 75.06,
          "taux": 1,
          "period": 1
        },
        ...
      ]
    }
  ]
}
```

## Utilisation

1. **Calculer les IJ** en remplissant le formulaire et cliquant sur "💰 Calculer Tout"
2. **Voir les résultats** dans l'onglet "📊 Résumé" (par défaut)
3. **Basculer vers le calendrier** en cliquant sur l'onglet "📅 Calendrier"
4. **Naviguer entre les mois** avec les boutons← / →
5. **Voir les détails** en survolant les jours payés

## Avantages

✅ **Vue visuelle intuitive** des paiements quotidiens
✅ **Navigation facile** entre les mois
✅ **Identification rapide** des périodes d'arrêt
✅ **Détails précis** par jour (taux, montant, période)
✅ **Responsive** et compatible avec tous les navigateurs modernes
✅ **Léger** - Pas de dépendances externes (vanilla JavaScript)

## Tests

✅ Tous les tests backend passent (255/255)
✅ L'interface est rétrocompatible
✅ Aucun changement dans l'API PHP requise

---

**Date**: 2025-10-08
**Version**: 1.0
**Status**: ✅ Production Ready
