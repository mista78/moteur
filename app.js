const API_URL = 'api.php';

let arretCount = 0;

// Initialize with current date
document.addEventListener('DOMContentLoaded', () => {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('current_date').value = today;
    addArret(); // Add first arrêt by default
    loadMockList(); // Load available mock files

    // Setup auto-determination of class based on revenue
    setupClassAutoDetermination();
});

/**
 * Setup automatic class determination based on revenue N-2
 */
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

        if (taxeOffice || !revenu || isNaN(revenu)) {
            // Taxé d'office ou revenus non communiqués → Classe A
            if (taxeOffice || (!revenu && taxeOfficeCheckbox.checked === false && revenuInput.value === '')) {
                // Ne rien faire si aucune valeur n'est entrée
                classeAutoInfo.style.display = 'none';
                hideRevenuCalculation();
                return;
            }

            classeSelect.value = 'A';
            classeAutoInfo.textContent = taxeOffice
                ? '✓ Classe A déterminée automatiquement (taxé d\'office)'
                : '✓ Classe A déterminée automatiquement (revenus non communiqués)';
            classeAutoInfo.style.display = 'block';

            // Show revenue calculation for class A
            showRevenuCalculation('A', passValue, null);
            return;
        }

        // Déterminer la classe selon les revenus
        let classe;
        let explication;

        if (revenu < passValue) {
            classe = 'A';
            explication = `✓ Classe A déterminée: ${formatMoney(revenu)} < 1 PASS (${formatMoney(passValue)})`;
        } else if (revenu <= (3 * passValue)) {
            classe = 'B';
            const nbPass = (revenu / passValue).toFixed(2);
            explication = `✓ Classe B déterminée: ${formatMoney(revenu)} = ${nbPass} PASS (entre 1 et 3 PASS)`;
        } else {
            classe = 'C';
            const nbPass = (revenu / passValue).toFixed(2);
            explication = `✓ Classe C déterminée: ${formatMoney(revenu)} = ${nbPass} PASS (> 3 PASS)`;
        }

        classeSelect.value = classe;
        classeAutoInfo.textContent = explication;
        classeAutoInfo.style.display = 'block';

        // Show revenue calculation
        showRevenuCalculation(classe, passValue, revenu);
    }

    // Attach event listeners
    if (revenuInput) revenuInput.addEventListener('input', updateClasseAuto);
    if (taxeOfficeCheckbox) taxeOfficeCheckbox.addEventListener('change', updateClasseAuto);
    if (passValueInput) passValueInput.addEventListener('input', updateClasseAuto);
    if (classeSelect) classeSelect.addEventListener('change', () => {
        // Update revenue calculation when class is manually changed
        const revenu = parseFloat(revenuInput.value);
        const passValue = parseFloat(passValueInput.value) || 47000;
        const classe = classeSelect.value;
        if (classe) {
            showRevenuCalculation(classe, passValue, revenu);
        }
    });
}

/**
 * Show revenue calculation details
 */
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

/**
 * Hide revenue calculation details
 */
function hideRevenuCalculation() {
    const infoBox = document.getElementById('revenu_info_box');
    if (infoBox) {
        infoBox.style.display = 'none';
    }
}

/**
 * Format money value
 * @param {number} value - The value to format
 * @param {boolean} showDecimals - Whether to show decimal places (default: false)
 */
function formatMoney(value, showDecimals = false) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: showDecimals ? 2 : 0,
        maximumFractionDigits: showDecimals ? 2 : 0
    }).format(value);
}

async function loadMockList() {
    try {
        const response = await fetch(`${API_URL}?endpoint=list-mocks`);
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            const container = document.getElementById('mock-buttons-container');
            container.innerHTML = '';

            result.data.forEach(mockFile => {
                const button = document.createElement('button');
                button.className = 'btn btn-success';
                button.onclick = () => loadMockData(mockFile);

                // Extract number from filename (e.g., mock2.json -> 2)
                const match = mockFile.match(/mock(\d*)\.json/);
                const number = match[1] || '';
                const label = number ? `Mock ${number}` : 'Mock';

                button.textContent = `📋 ${label}`;
                container.appendChild(button);
            });
        }
    } catch (error) {
        console.error('Error loading mock list:', error);
    }
}

function addArret() {
    arretCount++;
    const container = document.getElementById('arrets-container');

    const arretDiv = document.createElement('div');
    arretDiv.className = 'arret-item';
    arretDiv.id = `arret-${arretCount}`;

    arretDiv.innerHTML = `
        <div class="arret-header">
            <h3>Arrêt ${arretCount}</h3>
            <button class="btn btn-danger" onclick="removeArret(${arretCount})">Supprimer</button>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Date de début *</label>
                <input type="date" id="arret_from_${arretCount}" required>
            </div>
            <div class="form-group">
                <label>Date de fin *</label>
                <input type="date" id="arret_to_${arretCount}" required>
            </div>
            <div class="form-group">
                <label>Code pathologie</label>
                <input type="text" id="code_patho_${arretCount}" placeholder="ex: 2">
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="rechute_${arretCount}">
                    <label>Rechute</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="dt_${arretCount}">
                    <label>DT non excusée</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="gpm_${arretCount}">
                    <label>Membre GPM mis à jour</label>
                </div>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Date de déclaration</label>
                <input type="date" id="declaration_date_${arretCount}">
            </div>
            <div class="form-group">
                <label>Date d'attestation</label>
                <input type="date" id="attestation_date_${arretCount}">
            </div>
            <div class="form-group">
                <label>Date d'effet forcée (optionnel)</label>
                <input type="date" id="date_effet_forced_${arretCount}">
            </div>
        </div>
    `;

    container.appendChild(arretDiv);
}

function removeArret(id) {
    const arret = document.getElementById(`arret-${id}`);
    if (arret) {
        arret.remove();
    }
}

function collectArrets() {
    const arrets = [];
    const container = document.getElementById('arrets-container');
    const arretItems = container.querySelectorAll('.arret-item');

    arretItems.forEach((item) => {
        const id = item.id.split('-')[1];
        const arretFrom = document.getElementById(`arret_from_${id}`).value;
        const arretTo = document.getElementById(`arret_to_${id}`).value;

        if (!arretFrom || !arretTo) {
            return;
        }

        const arret = {
            'arret-from-line': arretFrom,
            'arret-to-line': arretTo,
            'code-patho-line': document.getElementById(`code_patho_${id}`).value || '',
            'rechute-line': document.getElementById(`rechute_${id}`).checked ? '1' : '0',
            'dt-line': document.getElementById(`dt_${id}`).checked ? '1' : '0',
            'gpm-member-line': document.getElementById(`gpm_${id}`).checked ? '1' : '0',
            'declaration-date-line': document.getElementById(`declaration_date_${id}`).value || '',
            'attestation-date-line': document.getElementById(`attestation_date_${id}`).value || ''
        };

        const dateEffetForced = document.getElementById(`date_effet_forced_${id}`).value;
        if (dateEffetForced) {
            arret['date-effet-forced'] = dateEffetForced;
        }

        arrets.push(arret);
    });

    return arrets;
}

function collectFormData() {
    return {
        statut: document.getElementById('statut').value,
        classe: document.getElementById('classe').value,
        option: document.getElementById('option').value,
        birth_date: document.getElementById('birth_date').value,
        current_date: document.getElementById('current_date').value,
        attestation_date: document.getElementById('attestation_date').value || null,
        last_payment_date: document.getElementById('last_payment_date').value || null,
        affiliation_date: document.getElementById('affiliation_date').value || null,
        nb_trimestres: parseInt(document.getElementById('nb_trimestres').value),
        previous_cumul_days: parseInt(document.getElementById('previous_cumul_days').value),
        patho_anterior: document.getElementById('patho_anterior').checked,
        prorata: parseFloat(document.getElementById('prorata').value),
        forced_rate: document.getElementById('forced_rate').value ? parseFloat(document.getElementById('forced_rate').value) : null,
        pass_value: parseInt(document.getElementById('pass_value').value),
        arrets: collectArrets()
    };
}

async function calculateDateEffet() {
    const data = collectFormData();

    if (data.arrets.length === 0) {
        showError('Veuillez ajouter au moins un arrêt de travail');
        return;
    }

    showLoading(true);

    try {
        const response = await fetch(`${API_URL}?endpoint=date-effet`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                arrets: data.arrets,
                birth_date: data.birth_date,
                previous_cumul_days: data.previous_cumul_days
            })
        });

        const result = await response.json();

        if (result.success) {
            displayDateEffetResults(result.data);
        } else {
            showError(result.error);
        }
    } catch (error) {
        showError('Erreur de communication avec le serveur: ' + error.message);
    } finally {
        showLoading(false);
    }
}

async function calculateEndPayment() {
    const data = collectFormData();

    if (data.arrets.length === 0) {
        showError('Veuillez ajouter au moins un arrêt de travail');
        return;
    }

    if (!data.birth_date) {
        showError('Veuillez saisir la date de naissance');
        return;
    }

    showLoading(true);

    try {
        const response = await fetch(`${API_URL}?endpoint=end-payment`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                arrets: data.arrets,
                birth_date: data.birth_date,
                previous_cumul_days: data.previous_cumul_days,
                current_date: data.current_date
            })
        });

        const result = await response.json();

        if (result.success) {
            displayEndPaymentResults(result.data);
        } else {
            showError(result.error);
        }
    } catch (error) {
        showError('Erreur de communication avec le serveur: ' + error.message);
    } finally {
        showLoading(false);
    }
}

async function calculateAll() {
    const data = collectFormData();

    if (data.arrets.length === 0) {
        showError('Veuillez ajouter au moins un arrêt de travail');
        return;
    }

    if (!data.birth_date) {
        showError('Veuillez saisir la date de naissance');
        return;
    }

    showLoading(true);

    try {
        const response = await fetch(`${API_URL}?endpoint=calculate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            displayFullResults(result.data);
        } else {
            showError(result.error);
        }
    } catch (error) {
        showError('Erreur de communication avec le serveur: ' + error.message);
    } finally {
        showLoading(false);
    }
}

async function loadMockData(mockFile = 'mock.json') {
    try {
        const response = await fetch(`${API_URL}?endpoint=load-mock&file=${mockFile}`);
        const result = await response.json();

        if (result.success) {
            // Clear existing arrets
            document.getElementById('arrets-container').innerHTML = '';
            arretCount = 0;

            // Load mock arrets
            result.data.forEach(arret => {
                arretCount++;
                const container = document.getElementById('arrets-container');

                const arretDiv = document.createElement('div');
                arretDiv.className = 'arret-item';
                arretDiv.id = `arret-${arretCount}`;

                arretDiv.innerHTML = `
                    <div class="arret-header">
                        <h3>Arrêt ${arretCount}</h3>
                        <button class="btn btn-danger" onclick="removeArret(${arretCount})">Supprimer</button>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Date de début *</label>
                            <input type="date" id="arret_from_${arretCount}" value="${arret['arret-from-line']}" required>
                        </div>
                        <div class="form-group">
                            <label>Date de fin *</label>
                            <input type="date" id="arret_to_${arretCount}" value="${arret['arret-to-line']}" required>
                        </div>
                        <div class="form-group">
                            <label>Code pathologie</label>
                            <input type="text" id="code_patho_${arretCount}" value="${arret['code-patho-line'] || ''}" placeholder="ex: 2">
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="rechute_${arretCount}" ${arret['rechute-line'] == '1' ? 'checked' : ''}>
                                <label>Rechute</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="dt_${arretCount}" ${arret['dt-line'] == '1' ? 'checked' : ''}>
                                <label>DT non excusée</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="gpm_${arretCount}" ${arret['gpm-member-line'] == '1' ? 'checked' : ''}>
                                <label>Membre GPM mis à jour</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Date de déclaration</label>
                            <input type="date" id="declaration_date_${arretCount}" value="${arret['declaration-date-line'] || ''}">
                        </div>
                        <div class="form-group">
                            <label>Date d'attestation</label>
                            <input type="date" id="attestation_date_${arretCount}" value="${arret['attestation-date-line'] || ''}">
                        </div>
                        <div class="form-group">
                            <label>Date d'effet forcée (optionnel)</label>
                            <input type="date" id="date_effet_forced_${arretCount}">
                        </div>
                    </div>
                `;

                container.appendChild(arretDiv);
            });

            showSuccess(`Données ${mockFile} chargées avec succès`);
        } else {
            showError(result.error);
        }
    } catch (error) {
        showError('Erreur lors du chargement des données de test: ' + error.message);
    }
}

function displayDateEffetResults(arrets) {
    const resultsDiv = document.getElementById('results');

    let html = '<div class="results"><h2>📅 Résultats - Dates d\'Effet</h2>';

    html += '<table>';
    html += '<tr><th>Arrêt</th><th>Début</th><th>Fin</th><th>Date d\'effet</th><th>Rechute</th></tr>';

    arrets.forEach((arret, index) => {
        html += '<tr>';
        html += `<td>Arrêt ${index + 1}</td>`;
        html += `<td>${arret['arret-from-line']}</td>`;
        html += `<td>${arret['arret-to-line']}</td>`;
        html += `<td><strong>${arret['date-effet'] || 'Non calculée'}</strong></td>`;
        html += `<td>${arret['rechute-line'] == '1' ? 'Oui' : 'Non'}</td>`;
        html += '</tr>';
    });

    html += '</table></div>';

    resultsDiv.innerHTML = html;
}

function displayEndPaymentResults(data) {
    const resultsDiv = document.getElementById('results');

    let html = '<div class="results"><h2>📆 Résultats - Dates de Fin de Paiement</h2>';

    if (data) {
        if (data.end_period_1) {
            html += `<div class="result-item">
                <span class="result-label">Fin période 1 (365 jours)</span>
                <span class="result-value">${data.end_period_1}</span>
            </div>`;
        }

        if (data.end_period_2) {
            html += `<div class="result-item">
                <span class="result-label">Fin période 2 (730 jours)</span>
                <span class="result-value">${data.end_period_2}</span>
            </div>`;
        }

        if (data.end_period_3) {
            html += `<div class="result-item">
                <span class="result-label">Fin période 3 (1095 jours)</span>
                <span class="result-value">${data.end_period_3}</span>
            </div>`;
        }
    } else {
        html += '<p>Aucune date de fin calculée (âge < 62 ans)</p>';
    }

    html += '</div>';

    resultsDiv.innerHTML = html;
}

function displayFullResults(data) {
    const resultsDiv = document.getElementById('results');

    let html = '<div class="results"><h2>💰 Résultats Complets</h2>';

    // Add tabs
    html += '<div class="tabs">';
    html += '<button class="tab active" onclick="switchTab(event, \'summary\')">📊 Résumé</button>';
    html += '<button class="tab" onclick="switchTab(event, \'calendar\')">📅 Calendrier</button>';
    html += '</div>';

    // Summary tab content
    html += '<div id="summary" class="tab-content active">';

    // Main results
    html += `<div class="result-item">
        <span class="result-label">Âge</span>
        <span class="result-value">${data.age} ans</span>
    </div>`;

    html += `<div class="result-item">
        <span class="result-label">Trimestres d'affiliation</span>
        <span class="result-value">${data.nb_trimestres || 0} trimestres</span>
    </div>`;

    html += `<div class="result-item">
        <span class="result-label">Nombre de jours indemnisables</span>
        <span class="result-value">${data.nb_jours} jours</span>
    </div>`;

    html += `<div class="result-item">
        <span class="result-label">Montant total</span>
        <span class="result-value">${(data.montant || 0).toFixed(2)} €</span>
    </div>`;

    html += `<div class="result-item">
        <span class="result-label">Cumul total de jours</span>
        <span class="result-value">${data.total_cumul_days} jours</span>
    </div>`;

    // End payment dates
    if (data.end_payment_dates) {
        html += '<h3 style="margin-top: 20px; color: #667eea;">Dates de fin de paiement</h3>';

        if (data.end_payment_dates.end_period_1) {
            html += `<div class="result-item">
                <span class="result-label">Fin période 1 (365 jours)</span>
                <span class="result-value">${data.end_payment_dates.end_period_1}</span>
            </div>`;
        }

        if (data.end_payment_dates.end_period_2) {
            html += `<div class="result-item">
                <span class="result-label">Fin période 2 (730 jours)</span>
                <span class="result-value">${data.end_payment_dates.end_period_2}</span>
            </div>`;
        }

        if (data.end_payment_dates.end_period_3) {
            html += `<div class="result-item">
                <span class="result-label">Fin période 3 (1095 jours)</span>
                <span class="result-value">${data.end_payment_dates.end_period_3}</span>
            </div>`;
        }
    }

    // Payment details by arrêt
    if (data.payment_details && data.payment_details.length > 0) {
        html += '<h3 style="margin-top: 20px; color: #667eea;">Détail des paiements par arrêt</h3>';
        html += '<table>';
        html += '<tr><th>N°</th><th>Début arrêt</th><th>Fin arrêt</th><th>Date effet</th><th>Attestation</th><th>Début paiem.</th><th>Fin paiem.</th><th>Jours payés</th><th>Taux/Jour</th><th>Montant</th><th>Statut</th></tr>';

        data.payment_details.forEach((detail) => {
            html += '<tr>';
            html += `<td>${detail.arret_index + 1}</td>`;
            html += `<td>${detail.arret_from}</td>`;
            html += `<td>${detail.arret_to}</td>`;
            html += `<td>${detail.date_effet || '-'}</td>`;
            html += `<td>${detail.attestation_date || '-'}</td>`;
            html += `<td>${detail.payment_start || '-'}</td>`;
            html += `<td>${detail.payment_end || '-'}</td>`;
            html += `<td><strong>${detail.payable_days}</strong></td>`;

            // Display rate information
            if (detail.rate_breakdown && detail.rate_breakdown.length > 0) {
                let rateStr = '';
                detail.rate_breakdown.forEach(rb => {
                    const yearLabel = rb.year ? `[${rb.year}] ` : '';
                    const periodLabel = rb.period ? `P${rb.period}` : '';
                    rateStr += `${yearLabel}${periodLabel}: ${rb.days}j × ${rb.rate}€<br>`;
                });
                html += `<td style="font-size: 11px;">${rateStr}</td>`;
            } else if (detail.daily_rate) {
                html += `<td>${detail.daily_rate.toFixed(2)}€</td>`;
            } else {
                html += `<td>-</td>`;
            }

            html += `<td><strong>${detail.montant ? detail.montant.toFixed(2) + '€' : '0€'}</strong></td>`;
            html += `<td style="color: ${detail.payable_days > 0 ? '#28a745' : '#dc3545'}">${detail.reason}</td>`;
            html += '</tr>';
        });

        html += '</table>';

        // Add payment period recap
        html += '<h3 style="margin-top: 30px; color: #667eea;">Récapitulatif par période de taux</h3>';
        html += '<table>';
        html += '<tr><th>Période</th><th>Début</th><th>Fin</th><th>Jours</th><th>Taux journalier</th><th>Montant</th></tr>';

        const periodRecap = {};

        data.payment_details.forEach((detail) => {
            if (detail.rate_breakdown && detail.rate_breakdown.length > 0) {
                detail.rate_breakdown.forEach(rb => {
                    const periodKey = `${rb.year || ''}-P${rb.period || ''}-${rb.rate}`;
                    if (!periodRecap[periodKey]) {
                        periodRecap[periodKey] = {
                            year: rb.year,
                            period: rb.period,
                            rate: rb.rate,
                            days: 0,
                            amount: 0,
                            taux: rb.taux
                        };
                    }
                    periodRecap[periodKey].days += rb.days;
                    periodRecap[periodKey].amount += rb.days * rb.rate;
                });
            }
        });

        // Group periods and track date ranges
        const periodGroups = {};

        data.payment_details.forEach((detail) => {
            if (detail.rate_breakdown && detail.rate_breakdown.length > 0) {
                detail.rate_breakdown.forEach(rb => {
                    const periodKey = `${rb.year || ''}-M${rb.month || ''}-T${rb.trimester || ''}-NbT${rb.nb_trimestres || ''}-Taux${rb.taux || ''}-Rate${rb.rate}`;
                    if (!periodGroups[periodKey]) {
                        periodGroups[periodKey] = {
                            year: rb.year,
                            month: rb.month,
                            trimester: rb.trimester,
                            nb_trimestres: rb.nb_trimestres,
                            period: rb.period,
                            rate: rb.rate,
                            days: 0,
                            amount: 0,
                            taux: rb.taux,
                            start: rb.start,
                            end: rb.end,
                            dateRanges: []
                        };
                    }
                    periodGroups[periodKey].days += rb.days;
                    periodGroups[periodKey].amount += rb.days * rb.rate;

                    // Track all date ranges for this period
                    if (rb.start && rb.end) {
                        periodGroups[periodKey].dateRanges.push({
                            start: rb.start,
                            end: rb.end,
                            days: rb.days
                        });
                    }
                });
            }
        });

        Object.values(periodGroups).forEach(recap => {
            const monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
            const monthLabel = recap.month ? ` ${monthNames[recap.month - 1]}` : '';
            const trimesterLabel = recap.trimester ? ` T${recap.trimester}` : '';
            const nbTrimestreLabel = recap.nb_trimestres ? ` (${recap.nb_trimestres} trim)` : '';
            const yearLabel = recap.year ? `${recap.year}${monthLabel}${trimesterLabel}${nbTrimestreLabel}` : '-';
            const periodLabel = recap.period ? `Période ${recap.period}` : 'Tout';
            const tauxLabel = recap.taux ? ` (Taux ${recap.taux})` : '';

            // Get min start and max end dates
            let minStart = null;
            let maxEnd = null;
            if (recap.dateRanges.length > 0) {
                minStart = recap.dateRanges.reduce((min, r) => !min || r.start < min ? r.start : min, null);
                maxEnd = recap.dateRanges.reduce((max, r) => !max || r.end > max ? r.end : max, null);
            }

            html += '<tr>';
            html += `<td><strong>${yearLabel} - ${periodLabel}${tauxLabel}</strong></td>`;
            html += `<td>${minStart || '-'}</td>`;
            html += `<td>${maxEnd || '-'}</td>`;
            html += `<td><strong>${recap.days}</strong></td>`;
            html += `<td>${recap.rate.toFixed(2)}€</td>`;
            html += `<td><strong>${recap.amount.toFixed(2)}€</strong></td>`;
            html += '</tr>';
        });

        html += '</table>';
    }

    // Arrêts with dates effet
    html += '<h3 style="margin-top: 20px; color: #667eea;">Détail des arrêts</h3>';
    html += '<table>';
    html += '<tr><th>N°</th><th>Début</th><th>Fin</th><th>Date d\'effet</th><th>Durée</th></tr>';

    data.arrets.forEach((arret, index) => {
        const startDate = new Date(arret['arret-from-line']);
        const endDate = new Date(arret['arret-to-line']);
        const duration = Math.floor((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;

        html += '<tr>';
        html += `<td>${index + 1}</td>`;
        html += `<td>${arret['arret-from-line']}</td>`;
        html += `<td>${arret['arret-to-line']}</td>`;
        html += `<td><strong>${arret['date-effet'] || 'N/A'}</strong></td>`;
        html += `<td>${duration} jours</td>`;
        html += '</tr>';
    });

    html += '</table>';

    // Close summary tab
    html += '</div>'; // End summary tab-content

    // Calendar tab content
    html += '<div id="calendar" class="tab-content">';
    html += generateCalendarView(data);
    html += '</div>'; // End calendar tab-content

    html += '</div>'; // End results div

    resultsDiv.innerHTML = html;

    // Initialize calendar if data is available
    if (window.calendarData) {
        initializeCalendar();
    }
}

function showLoading(show) {
    document.getElementById('loading').style.display = show ? 'block' : 'none';
}

function showError(message) {
    const resultsDiv = document.getElementById('results');
    resultsDiv.innerHTML = `<div class="error"><strong>❌ Erreur:</strong> ${message}</div>`;
}

function showSuccess(message) {
    const resultsDiv = document.getElementById('results');
    resultsDiv.innerHTML = `<div class="success"><strong>✅ Succès:</strong> ${message}</div>`;
}
