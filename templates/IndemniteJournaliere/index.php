<?php
/**
 * @var \App\View\AppView $this
 */
$this->setLayout('ij');
$this->assign('title', 'Simulateur IJ - Indemnités Journalières');
?>

<div class="container">
    <h1>🏥 Simulateur IJ - Indemnités Journalières</h1>
    <p style="color: #777; margin-bottom: 20px;">Calcul des indemnités journalières pour les professionnels de santé</p>

    <div class="info-box">
        <strong>PASS CPAM:</strong> La valeur du Plafond Annuel de la Sécurité Sociale est configurable ci-dessous (par défaut: 47 000 €)
    </div>

    <div class="info-box" style="background: #fff3cd; border-left-color: #ffc107;">
        <strong>📊 Détermination automatique de la classe:</strong>
        <ul style="margin: 8px 0 0 20px; color: #856404;">
            <li>Classe A: Revenus N-2 &lt; 47 000 € (1 PASS) → Revenu/jour = <strong>1 PASS / 730</strong></li>
            <li>Classe B: Revenus N-2 entre 47 000 € et 141 000 € (1-3 PASS) → Revenu/jour = <strong>Revenu réel / 730</strong></li>
            <li>Classe C: Revenus N-2 &gt; 141 000 € (3 PASS) → Revenu/jour = <strong>3 PASS / 730</strong></li>
            <li>Si taxé d'office (revenus non communiqués): Classe A automatiquement</li>
        </ul>
        <small style="color: #856404;">La classe est basée sur les revenus de l'année N-2 (ex: pour 2024, revenus de 2022)</small>
    </div>

    <div id="revenu_info_box" class="info-box" style="background: #e7f3ff; border-left-color: #2196F3; display: none;">
        <strong>💰 Calcul du revenu journalier:</strong>
        <div id="revenu_calculation_detail" style="margin-top: 8px; color: #1976D2;"></div>
    </div>

    <h2>Configuration</h2>
    <div class="form-grid">
        <div class="form-group">
            <label for="statut">Statut *</label>
            <select id="statut" required>
                <option value="M">Médecin (M)</option>
                <option value="RSPM">RSPM</option>
                <option value="CCPL">CCPL</option>
            </select>
        </div>

        <div class="form-group">
            <label for="revenu_n_moins_2">Revenus N-2 (€)</label>
            <input type="number" id="revenu_n_moins_2" placeholder="Ex: 85000" step="1000">
            <small style="color: #888; font-size: 12px;">Détermine automatiquement la classe</small>
        </div>

        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" id="taxe_office">
                <label for="taxe_office">Taxé d'office (revenus non communiqués)</label>
            </div>
        </div>

        <div class="form-group">
            <label for="classe">Classe de cotisation *</label>
            <select id="classe" required>
                <option value="">-- Sélectionner ou auto-déterminer --</option>
                <option value="A">Classe A (&lt; 1 PASS, &lt; 47 000 €)</option>
                <option value="B">Classe B (1-3 PASS, 47 000-141 000 €)</option>
                <option value="C">Classe C (&gt; 3 PASS, &gt; 141 000 €)</option>
            </select>
            <small id="classe_auto_info" style="color: #28a745; font-size: 12px; display: none;"></small>
        </div>

        <div class="form-group">
            <label for="option">Option de cotisation</label>
            <select id="option">
                <option value="0,25">0,25%</option>
                <option value="0,5">0,5%</option>
                <option value="1">1%</option>
            </select>
        </div>

        <div class="form-group">
            <label for="pass_value">Valeur PASS (€)</label>
            <input type="number" id="pass_value" value="47000" step="100">
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="birth_date">Date de naissance *</label>
            <input type="date" id="birth_date" required>
        </div>

        <div class="form-group">
            <label for="current_date">Date actuelle</label>
            <input type="date" id="current_date" required>
        </div>

        <div class="form-group">
            <label for="attestation_date">Date d'attestation (optionnelle)</label>
            <input type="date" id="attestation_date">
            <small style="color: #888; font-size: 12px;">Si omise, calcul jusqu'à la fin de chaque arrêt</small>
        </div>

        <div class="form-group">
            <label for="last_payment_date">Date du dernier paiement</label>
            <input type="date" id="last_payment_date">
        </div>
    </div>

    <div class="info-box">
        <strong>Note:</strong> La date d'attestation peut être définie globalement ci-dessus ou individuellement pour chaque arrêt. Les dates individuelles ont la priorité sur la date globale.
        <br><strong>Calcul sans attestation:</strong> Si aucune date d'attestation n'est fournie, le calcul sera effectué jusqu'à la date de fin de chaque arrêt (ou la date actuelle si l'arrêt est en cours).
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="affiliation_date">Date d'affiliation (auto-calcul trimestres)</label>
            <input type="date" id="affiliation_date">
        </div>

        <div class="form-group">
            <label for="nb_trimestres">Nombre de trimestres d'affiliation</label>
            <input type="number" id="nb_trimestres" min="0" value="8">
            <small style="color: #888; font-size: 12px;">Auto-calculé si date d'affiliation fournie</small>
        </div>

        <div class="form-group">
            <label for="previous_cumul_days">Jours cumulés antérieurs</label>
            <input type="number" id="previous_cumul_days" min="0" value="0">
        </div>

        <div class="form-group">
            <label for="prorata">Prorata (1 = 100%)</label>
            <input type="number" id="prorata" min="0" max="1" step="0.01" value="1">
        </div>

        <div class="form-group">
            <label for="forced_rate">Taux forcé (optionnel)</label>
            <input type="number" id="forced_rate" step="0.01">
        </div>
    </div>

    <div class="info-box">
        <strong>Trimestres:</strong> Si vous fournissez la date d'affiliation, les trimestres seront automatiquement calculés.
        Les trimestres sont comptés par périodes: Q1 (01/01-31/03), Q2 (01/04-30/06), Q3 (01/07-30/09), Q4 (01/10-31/12).
        Si la date d'affiliation tombe dans un trimestre, ce trimestre est compté comme complet.
    </div>

    <div class="form-group">
        <div class="checkbox-group">
            <input type="checkbox" id="patho_anterior">
            <label for="patho_anterior">Pathologie antérieure</label>
        </div>
    </div>

    <h2>Arrêts de travail</h2>
    <div id="arrets-container"></div>

    <div class="btn-group">
        <button class="btn btn-secondary" onclick="addArret()">+ Ajouter un arrêt</button>
        <div id="mock-buttons-container" style="display: inline-flex; gap: 10px;flex-wrap: wrap;">
            <!-- Mock buttons will be dynamically loaded here -->
        </div>
    </div>

    <div class="btn-group">
        <button class="btn btn-primary" onclick="calculateDateEffet()">📅 Calculer Date d'Effet</button>
        <button class="btn btn-primary" onclick="calculateEndPayment()">📆 Calculer Date Fin de Paiement</button>
        <button class="btn btn-primary" onclick="calculateAll()">💰 Calculer Tout</button>
    </div>

    <div id="loading">
        <div class="spinner"></div>
        <p style="margin-top: 10px;">Calcul en cours...</p>
    </div>

    <div id="results"></div>
</div>
