const API_URL = 'api.php';

let arretCount = 0;
let currentMockInfo = null; // Store current mock file info and expected values

// Initialize with current date
document.addEventListener('DOMContentLoaded', () => {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('current_date').value = today;
    addArret(); // Add first arrêt by default
    loadMockList(); // Load available mock files

    // Setup auto-determination of class based on revenue
    setupClassAutoDetermination();

    // Setup automatic option validation based on statut
    setupOptionValidation();
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
 * Setup automatic option validation based on statut
 * - Médecin (M): option 100% only
 * - CCPL: options 25%, 50% (no 100%)
 * - RSPM: options 25%, 100% (no 50%)
 */
function setupOptionValidation() {
    const statutSelect = document.getElementById('statut');
    const optionSelect = document.getElementById('option');

    function updateOptionsByStatut() {
        const statut = statutSelect.value;
        const currentOption = optionSelect.value;

        // Clear current options
        optionSelect.innerHTML = '';

        let validOptions = [];
        let defaultOption = '1'; // Default to 100%

        switch (statut) {
            case 'M': // Médecin
                // Only 100%
                validOptions = [
                    { value: '1', label: '100%' }
                ];
                defaultOption = '1';
                break;

            case 'CCPL':
                // 25% and 50% only (no 100%)
                validOptions = [
                    { value: '0,25', label: '25%' },
                    { value: '0,5', label: '50%' }
                ];
                defaultOption = '0,25';
                break;

            case 'RSPM':
                // 25% and 100% only (no 50%)
                validOptions = [
                    { value: '0,25', label: '25%' },
                    { value: '1', label: '100%' }
                ];
                defaultOption = '0,25';
                break;

            default:
                // If no statut selected, show all options
                validOptions = [
                    { value: '0,25', label: '25%' },
                    { value: '0,5', label: '50%' },
                    { value: '1', label: '100%' }
                ];
                defaultOption = '1';
        }

        // Add options to select
        validOptions.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.value;
            option.textContent = opt.label;
            optionSelect.appendChild(option);
        });

        // Try to preserve current selection if valid, otherwise use default
        const isCurrentValid = validOptions.some(opt => opt.value === currentOption);
        optionSelect.value = isCurrentValid ? currentOption : defaultOption;
    }

    // Update options when statut changes
    if (statutSelect) {
        statutSelect.addEventListener('change', updateOptionsByStatut);
        // Initialize on page load
        updateOptionsByStatut();
    }
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

function displayLoadedMock(mockFile, config = null) {
    // Create or update the loaded mock indicator
    let indicator = document.getElementById('loaded-mock-indicator');

    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'loaded-mock-indicator';
        indicator.style.cssText = `
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 13px;
            margin-left: 15px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
            animation: fadeIn 0.3s ease-in;
        `;

        // Insert after the mock buttons container
        const container = document.getElementById('mock-buttons-container');
        container.parentNode.insertBefore(indicator, container.nextSibling);
    }

    // Make sure it's visible
    indicator.style.display = 'inline-block';

    // Extract number from filename
    const match = mockFile.match(/mock(\d*)\.json/);
    const number = match[1] || '';
    const label = number ? `Mock ${number}` : 'Mock';

    indicator.innerHTML = `✅ ${label} chargé`;

    // Store mock info globally
    currentMockInfo = {
        file: mockFile,
        label: label,
        config: config
    };
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
            <div id="arret_status_${arretCount}" class="arret-status-badge" style="display: none;"></div>
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
                    <label>Rechute (si droits déjà ouverts)</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="dt_${arretCount}">
                    <label>DT non excusée</label>
                    <input type="hidden" id="dt_original_${arretCount}" value="0">
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="gpm_${arretCount}">
                    <label>Membre GPM mis à jour</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="cco_a_jour_${arretCount}" checked>
                    <label>Compte cotisant à jour</label>
                </div>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="valid_med_controleur_${arretCount}" checked>
                    <label>Validé par médecin contrôleur</label>
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

        const ccoAJourCheckbox = document.getElementById(`cco_a_jour_${id}`);
        const dtCheckbox = document.getElementById(`dt_${id}`);

        // DT handling: checkbox label is "DT non excusée" (NOT excused)
        // - Checked → string "0" (NOT excused, block payment)
        // - Unchecked → integer 0 or 1 (normal or excused, allow payment)
        // We need to check if there's a hidden field storing the original value
        const dtOriginalValue = document.getElementById(`dt_original_${id}`)?.value;
        let dtLineValue;

        if (dtCheckbox.checked) {
            // Checkbox checked = DT NOT excused = block
            dtLineValue = "0"; // String "0" to block
        } else {
            // Checkbox unchecked = normal or excused
            // Use original value if available, otherwise default to integer 0
            if (dtOriginalValue !== undefined && dtOriginalValue !== '') {
                dtLineValue = dtOriginalValue === '1' ? 1 : 0;
            } else {
                dtLineValue = 0; // Default to normal (integer 0)
            }
        }

        const validMedControleurCheckbox = document.getElementById(`valid_med_controleur_${id}`);

        const rechute = document.getElementById(`rechute_${id}`);

        const arret = {
            'arret-from-line': arretFrom,
            'arret-to-line': arretTo,
            'code-patho-line': document.getElementById(`code_patho_${id}`).value || '',
            'rechute-line': rechute.checked ? 1 : null, // null = auto-determine by backend
            'dt-line': dtLineValue,
            'gpm-member-line': document.getElementById(`gpm_${id}`).checked ? 1 : 0,
            'cco_a_jour': ccoAJourCheckbox ? (ccoAJourCheckbox.checked ? 1 : 0) : 1,
            'valid_med_controleur': validMedControleurCheckbox ? (validMedControleurCheckbox.checked ? 1 : 0) : 1,
            'declaration-date-line': document.getElementById(`declaration_date_${id}`).value || '',
            'attestation-date-line': document.getElementById(`attestation_date_${id}`).value || ''
        };

        const dateEffetForced = document.getElementById(`date_effet_forced_${id}`).value;
        if (dateEffetForced) {
            arret['date-effet-forced'] = dateEffetForced;
            arret['date_deb_droit'] = dateEffetForced; // Also send as date_deb_droit for backend compatibility
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

/**
 * Clear all form fields before loading new mock data
 */
function clearAllFormFields() {
    // Clear main form fields
    document.getElementById('statut').value = 'M'; // Reset to Médecin
    document.getElementById('statut').dispatchEvent(new Event('change')); // Trigger option update
    document.getElementById('classe').value = '';
    document.getElementById('option').value = '1';
    document.getElementById('pass_value').value = '47000';
    document.getElementById('birth_date').value = '';
    document.getElementById('current_date').value = new Date().toISOString().split('T')[0];
    document.getElementById('attestation_date').value = '';
    document.getElementById('last_payment_date').value = '';
    document.getElementById('affiliation_date').value = '';
    document.getElementById('nb_trimestres').value = '0';
    document.getElementById('previous_cumul_days').value = '0';
    document.getElementById('prorata').value = '1';
    document.getElementById('patho_anterior').checked = false;
    document.getElementById('forced_rate').value = '';

    // Clear revenue fields
    const revenuInput = document.getElementById('revenu_n_moins_2');
    const taxeOfficeCheckbox = document.getElementById('taxe_office');
    if (revenuInput) revenuInput.value = '';
    if (taxeOfficeCheckbox) taxeOfficeCheckbox.checked = false;

    // Hide auto-determination info
    const classeAutoInfo = document.getElementById('classe_auto_info');
    if (classeAutoInfo) classeAutoInfo.style.display = 'none';

    // Hide revenue calculation
    hideRevenuCalculation();

    // Clear results
    document.getElementById('results').innerHTML = '';

    // Clear arrets
    document.getElementById('arrets-container').innerHTML = '';
    arretCount = 0;

    // Hide loaded mock indicator and clear mock info
    const indicator = document.getElementById('loaded-mock-indicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
    currentMockInfo = null;
}

async function loadMockData(mockFile = 'mock.json') {
    try {
        const response = await fetch(`${API_URL}?endpoint=load-mock&file=${mockFile}`);
        const result = await response.json();

        if (result.success) {
            // Clear all form fields first
            clearAllFormFields();

            // Load configuration if available
            const config = result.config || null;

            // Show which mock is loaded (pass config for expected values)
            displayLoadedMock(mockFile, config);

            if (config) {

                // Populate form fields with configuration
                if (config.statut) {
                    document.getElementById('statut').value = config.statut;
                    // Trigger change event to update option validation
                    document.getElementById('statut').dispatchEvent(new Event('change'));
                }
                if (config.classe) document.getElementById('classe').value = config.classe;
                if (config.option !== undefined) {
                    // Convert 100 to "1", 25 to "0,25", 50 to "0,5" (French format with comma)
                    const optionValue = (config.option / 100).toString().replace('.', ',');
                    document.getElementById('option').value = optionValue;
                }
                if (config.pass_value) document.getElementById('pass_value').value = config.pass_value;
                if (config.birth_date) document.getElementById('birth_date').value = config.birth_date;
                if (config.attestation_date) document.getElementById('attestation_date').value = config.attestation_date;
                if (config.affiliation_date) document.getElementById('affiliation_date').value = config.affiliation_date;
                if (config.nb_trimestres !== undefined) document.getElementById('nb_trimestres').value = config.nb_trimestres;
                if (config.previous_cumul_days !== undefined) document.getElementById('previous_cumul_days').value = config.previous_cumul_days;
                if (config.prorata !== undefined) document.getElementById('prorata').value = config.prorata;
                if (config.patho_anterior !== undefined) document.getElementById('patho_anterior').checked = config.patho_anterior === 1;
                if (config.forced_rate !== undefined) document.getElementById('forced_rate').value = config.forced_rate;

                // Set current date to today
                document.getElementById('current_date').value = new Date().toISOString().split('T')[0];

                // Display expected results as info
                if (config.expected !== undefined || config.nbe_jours !== undefined) {
                    let infoText = '<strong>📊 Résultats attendus pour ce mock:</strong><br>';
                    if (config.expected !== undefined) {
                        infoText += `Montant: <strong style="color: #28a745;">${config.expected.toFixed(2)} €</strong><br>`;
                    }
                    if (config.nbe_jours !== undefined) {
                        infoText += `Nombre de jours: <strong style="color: #28a745;">${config.nbe_jours} jours</strong>`;
                    }

                    // Show info box at the top
                    const resultsDiv = document.getElementById('results');
                    resultsDiv.innerHTML = `<div class="info-box" style="background: #d4edda; border-left-color: #28a745;">${infoText}</div>`;
                }
            }

            // Load mock arrets
            result.data.forEach((arret, index) => {
                arretCount++;
                const container = document.getElementById('arrets-container');

                const arretDiv = document.createElement('div');
                arretDiv.className = 'arret-item';
                arretDiv.id = `arret-${arretCount}`;

                arretDiv.innerHTML = `
                    <div class="arret-header">
                        <h3>Arrêt ${arretCount}</h3>
                        <div id="arret_status_${arretCount}" class="arret-status-badge" style="display: none;"></div>
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
                                <label>Rechute (si droits déjà ouverts)</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="dt_${arretCount}">
                                <label>DT non excusée (bloquer paiement)</label>
                                <input type="hidden" id="dt_original_${arretCount}" value="${arret['dt-line'] === 1 || arret['dt-line'] === '1' ? '1' : '0'}">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="gpm_${arretCount}" ${arret['gpm-member-line'] == '1' ? 'checked' : ''}>
                                <label>Membre GPM mis à jour</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="cco_a_jour_${arretCount}" ${(arret['cco_a_jour'] === undefined || arret['cco_a_jour'] == 1) ? 'checked' : ''}>
                                <label>Compte cotisant à jour</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="valid_med_controleur_${arretCount}" ${(arret['valid_med_controleur'] === undefined || arret['valid_med_controleur'] == 1) ? 'checked' : ''}>
                                <label>Validé par médecin contrôleur</label>
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
                            <input type="date" id="date_effet_forced_${arretCount}" value="${arret['date_deb_droit'] && arret['date_deb_droit'] !== '0000-00-00' ? arret['date_deb_droit'] : ''}">
                        </div>
                    </div>
                `;

                container.appendChild(arretDiv);
            });

            // Update success message
            let successMsg = `✅ ${mockFile} chargé avec succès`;
            if (result.config && result.config.expected !== undefined) {
                successMsg += ` - Résultat attendu: ${result.config.expected.toFixed(2)} €`;
            }
            showSuccess(successMsg);
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

function updateArretStatusBadges(arrets) {
    const container = document.getElementById('arrets-container');
    const arretItems = container.querySelectorAll('.arret-item');

    arretItems.forEach((item, index) => {
        const id = item.id.split('-')[1];
        const badge = document.getElementById(`arret_status_${id}`);

        if (badge && arrets[index]) {
            const arret = arrets[index];
            let badgeHtml = '';
            let badgeStyle = '';

            if (arret.is_rechute === true) {
                // Show which arret this is a rechute of
                if (arret.rechute_of_arret_index !== undefined && arret.rechute_of_arret_index !== null) {
                    const sourceArretNum = arret.rechute_of_arret_index + 1;
                    badgeHtml = `🔄 Rechute de l'arrêt #${sourceArretNum}`;
                } else {
                    badgeHtml = '🔄 Rechute';
                }
                badgeStyle = 'background-color: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;';
            } else if (arret.is_rechute === false && index > 0) {
                badgeHtml = '🆕 Nouvelle pathologie';
                badgeStyle = 'background-color: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;';
            } else if (index === 0) {
                badgeHtml = '1ère pathologie';
                badgeStyle = 'color: #666; padding: 4px 8px; border-radius: 4px; font-size: 12px;';
            }

            if (badgeHtml) {
                badge.innerHTML = badgeHtml;
                badge.style = badgeStyle + '; display: inline-block; margin-left: 10px;';
            }
        }
    });
}

function displayFullResults(data) {
    const resultsDiv = document.getElementById('results');

    let html = '<div class="results">';

    // Show loaded mock indicator if available
    if (currentMockInfo) {
        html += `<div style="margin-bottom: 15px; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; font-weight: bold; font-size: 14px; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
            <div style="margin-bottom: 8px;">✅ ${currentMockInfo.label} chargé</div>`;

        // Show expected values if available
        if (currentMockInfo.config) {
            const config = currentMockInfo.config;
            html += '<div style="font-size: 12px; font-weight: normal; opacity: 0.95; margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.3);">';
            html += '<strong>Valeurs attendues:</strong><br>';

            if (config.expected_montant !== undefined) {
                const match = data.montant !== undefined && Math.abs(data.montant - config.expected_montant) < 0.01;
                const icon = match ? '✓' : '✗';
                const color = match ? '#d4edda' : '#f8d7da';
                html += `<span style="background: ${color}; color: #333; padding: 2px 6px; border-radius: 3px; margin-right: 8px;">${icon} Montant: ${config.expected_montant.toFixed(2)}€</span>`;
            }

            if (config.expected_nb_jours !== undefined) {
                const match = data.nb_jours !== undefined && data.nb_jours === config.expected_nb_jours;
                const icon = match ? '✓' : '✗';
                const color = match ? '#d4edda' : '#f8d7da';
                html += `<span style="background: ${color}; color: #333; padding: 2px 6px; border-radius: 3px;">${icon} Jours: ${config.expected_nb_jours}</span>`;
            }

            if (config.description) {
                html += `<br><em style="font-size: 11px;">${config.description}</em>`;
            }

            html += '</div>';
        }

        html += '</div>';
    }

    html += '<h2>💰 Résultats Complets</h2>';

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

    // Calculate total decompte days
    let totalDecompte = 0;
    if (data.payment_details && data.payment_details.length > 0) {
        data.payment_details.forEach((detail) => {
            if (detail.decompte_days) {
                totalDecompte += detail.decompte_days;
            }
        });
    }

    if (totalDecompte > 0) {
        html += `<div class="result-item" style="background-color: #fff9e6;">
            <span class="result-label" style="color: #856404;">⏱️ Décompte total (jours non payés)</span>
            <span class="result-value" style="color: #856404; font-weight: bold;">${totalDecompte} jours</span>
        </div>`;
    }

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
        html += '<tr><th>N°</th><th>Début arrêt</th><th>Fin arrêt</th><th>Durée</th><th>Décompte<br>(non payé)</th><th>Date effet</th><th>Attestation</th><th>Début paiem.</th><th>Fin paiem.</th><th>Jours payés</th><th>Taux</th><th>Taux/Jour</th><th>Montant</th><th>Statut</th></tr>';

        data.payment_details.forEach((detail) => {
            html += '<tr>';
            html += `<td>${detail.arret_index + 1}</td>`;
            html += `<td>${detail.arret_from}</td>`;
            html += `<td>${detail.arret_to}</td>`;
            html += `<td>${detail.arret_diff || '-'}j</td>`;

            // Add decompte column with color coding
            let decompteHtml = '';
            if (detail.decompte_days !== undefined && detail.decompte_days > 0) {
                decompteHtml = `<td style="background-color: #fff3cd; color: #856404; font-weight: bold;">${detail.decompte_days}j</td>`;
            } else {
                decompteHtml = `<td style="color: #999;">0j</td>`;
            }
            html += decompteHtml;

            html += `<td>${detail.date_effet || '-'}</td>`;
            html += `<td>${detail.attestation_date || '-'}</td>`;
            html += `<td>${detail.payment_start || '-'}</td>`;
            html += `<td>${detail.payment_end || '-'}</td>`;
            html += `<td><strong>${detail.payable_days}</strong></td>`;

            // Display taux index
            if (detail.rate_breakdown && detail.rate_breakdown.length > 0) {
                // Collect unique taux values
                const tauxSet = new Set();
                detail.rate_breakdown.forEach(rb => {
                    if (rb.taux) {
                        tauxSet.add(rb.taux);
                    }
                });
                const tauxArray = Array.from(tauxSet).sort((a, b) => a - b);

                if (tauxArray.length > 0) {
                    let tauxHtml = '';
                    tauxArray.forEach((taux, idx) => {
                        if (idx > 0) tauxHtml += ', ';
                        tauxHtml += `<span style="background-color: #667eea; color: white; padding: 2px 6px; border-radius: 3px; font-weight: bold;">${taux}</span>`;
                    });
                    html += `<td style="text-align: center;">${tauxHtml}</td>`;
                } else {
                    html += `<td>-</td>`;
                }
            } else {
                html += `<td>-</td>`;
            }

            // Display rate information
            if (detail.rate_breakdown && detail.rate_breakdown.length > 0) {
                let rateStr = '';
                detail.rate_breakdown.forEach(rb => {
                    const yearLabel = rb.year ? `[${rb.year}] ` : '';
                    const periodLabel = rb.period ? `P${rb.period}` : '';
                    const tauxLabel = rb.taux ? ` T${rb.taux}` : '';
                    rateStr += `${yearLabel}${periodLabel}${tauxLabel}: ${rb.days}j × ${rb.rate}€<br>`;
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

        // Add explanation for decompte
        html += `
            <div style="margin-top: 15px; padding: 12px; background-color: #e7f3ff; border-left: 4px solid #667eea; border-radius: 4px;">
                <strong style="color: #667eea;">ℹ️ Décompte (jours non payés) :</strong><br>
                <span style="font-size: 13px; color: #555;">
                    Le décompte représente les jours qui comptent vers le seuil (90 jours pour nouvelle pathologie, 15 jours pour rechute)
                    mais qui ne sont <strong>pas payés</strong> car situés avant la date d'effet.<br>
                    <em>Exemple: Un arrêt de 120 jours avec date d'effet au jour 91 aura 90 jours de décompte et 30 jours payables.</em>
                </span>
            </div>
        `;

        // Add explanation for taux system
        html += `
            <div style="margin-top: 15px; padding: 12px; background-color: #f0f7ff; border-left: 4px solid #667eea; border-radius: 4px;">
                <strong style="color: #667eea;">📊 Système de Taux (1-9) :</strong><br>
                <span style="font-size: 13px; color: #555;">
                    Le <strong>taux</strong> détermine le montant journalier selon l'âge, les trimestres d'affiliation et la pathologie antérieure :<br>
                    • <strong>Taux 1-3</strong> : &lt;62 ans (plein, -1/3, -2/3)<br>
                    • <strong>Taux 4-6</strong> : ≥70 ans (réduit senior, -1/3, -2/3)<br>
                    • <strong>Taux 7-9</strong> : 62-69 ans après 365j (plein-25%, -1/3, -2/3)<br>
                    <em>Les réductions s'appliquent selon le nombre de trimestres : 8-15 trim = -2/3, 16-23 trim = -1/3, ≥24 trim = plein</em>
                </span>
            </div>
        `;

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
    html += '<tr><th>N°</th><th>Début</th><th>Fin</th><th>Date d\'effet</th><th>Durée</th><th>Type</th></tr>';

    data.arrets.forEach((arret, index) => {
        const startDate = new Date(arret['arret-from-line']);
        const endDate = new Date(arret['arret-to-line']);
        const duration = Math.floor((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;

        // Determine arret type based on backend determination
        let typeLabel = '';
        let typeStyle = '';
        if (arret.is_rechute === true) {
            // Show which arret this is a rechute of
            if (arret.rechute_of_arret_index !== undefined && arret.rechute_of_arret_index !== null) {
                const sourceArretNum = arret.rechute_of_arret_index + 1; // +1 for human-readable numbering
                typeLabel = `🔄 Rechute de l'arrêt #${sourceArretNum}`;
            } else {
                typeLabel = '🔄 Rechute';
            }
            typeStyle = 'background-color: #fff3cd; color: #856404; font-weight: bold;';
        } else if (arret.is_rechute === false && index > 0) {
            typeLabel = '🆕 Nouvelle pathologie';
            typeStyle = 'background-color: #d4edda; color: #155724; font-weight: bold;';
        } else {
            typeLabel = '1ère pathologie';
            typeStyle = 'color: #666;';
        }

        html += '<tr>';
        html += `<td>${index + 1}</td>`;
        html += `<td>${arret['arret-from-line']}</td>`;
        html += `<td>${arret['arret-to-line']}</td>`;
        html += `<td><strong>${arret['date-effet'] || 'N/A'}</strong></td>`;
        html += `<td>${duration} jours</td>`;
        html += `<td style="${typeStyle}">${typeLabel}</td>`;
        html += '</tr>';
    });

    html += '</table>';

    // Add explanation for arret types
    html += `
        <div style="margin-top: 15px; padding: 12px; background-color: #e7f3ff; border-left: 4px solid #667eea; border-radius: 4px;">
            <strong style="color: #667eea;">ℹ️ Types d'arrêts :</strong><br>
            <span style="font-size: 13px; color: #555;">
                <strong>🔄 Rechute de l'arrêt #X :</strong> Indique que cet arrêt est une rechute de l'arrêt #X (le dernier arrêt avec droits ouverts).
                Droits déjà ouverts + arrêt < 1 an après → Paiement dès le 15ème jour<br>
                <strong>🆕 Nouvelle pathologie :</strong> Droits pas encore ouverts OU arrêt > 1 an après le dernier avec droits ouverts → Nouveau seuil de 90 jours requis<br>
                <strong>1ère pathologie :</strong> Premier arrêt de travail de l'affiliation
            </span>
        </div>
    `;

    // Close summary tab
    html += '</div>'; // End summary tab-content

    // Calendar tab content
    html += '<div id="calendar" class="tab-content">';
    html += generateCalendarView(data);
    html += '</div>'; // End calendar tab-content

    html += '</div>'; // End results div

    resultsDiv.innerHTML = html;

    // Update arret status badges in the form
    if (data.arrets) {
        updateArretStatusBadges(data.arrets);
    }

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
