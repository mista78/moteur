// Calendar functions for displaying daily breakdown

let currentCalendarMonth = new Date().getMonth();
let currentCalendarYear = new Date().getFullYear();
window.calendarData = null;

/**
 * Switch between tabs
 */
function switchTab(event, tabName) {
    // Hide all tab contents
    const tabContents = document.getElementsByClassName('tab-content');
    for (let content of tabContents) {
        content.classList.remove('active');
    }

    // Remove active class from all tabs
    const tabs = document.getElementsByClassName('tab');
    for (let tab of tabs) {
        tab.classList.remove('active');
    }

    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');

    // If switching to calendar, render it
    if (tabName === 'calendar' && window.calendarData) {
        renderCalendar(currentCalendarMonth, currentCalendarYear);
    }
}

/**
 * Generate calendar view HTML structure
 */
function generateCalendarView(data) {
    // Store calendar data globally
    window.calendarData = extractCalendarData(data);

    // Set initial month/year from first payment
    if (window.calendarData.payments.length > 0) {
        const firstDate = new Date(window.calendarData.payments[0].date);
        currentCalendarMonth = firstDate.getMonth();
        currentCalendarYear = firstDate.getFullYear();
    }

    let html = '<div class="calendar-container">';

    // Month selector
    html += '<div class="calendar-month-selector">';
    html += '<button onclick="previousMonth()">← Mois précédent</button>';
    html += '<div class="calendar-month-title" id="calendar-month-title"></div>';
    html += '<button onclick="nextMonth()">Mois suivant →</button>';
    html += '</div>';

    // Calendar grid container
    html += '<div id="calendar-grid"></div>';

    // Legend
    html += '<div class="calendar-legend">';
    html += '<h4 style="margin-bottom: 10px; color: #667eea;">📅 Légende</h4>';

    html += '<div style="margin-bottom: 15px;">';
    html += '<strong>États des jours:</strong><br>';
    html += '<div class="calendar-legend-item">';
    html += '<div class="calendar-legend-color" style="background: #28a745;"></div>';
    html += '<span>Jour payé</span>';
    html += '</div>';
    html += '<div class="calendar-legend-item">';
    html += '<div class="calendar-legend-color" style="background: #dc3545;"></div>';
    html += '<span>Jour non payé (avant droits)</span>';
    html += '</div>';
    html += '<div class="calendar-legend-item">';
    html += '<div class="calendar-legend-color" style="background: #ffc107;"></div>';
    html += '<span>Début d\'arrêt</span>';
    html += '</div>';
    html += '</div>';

    html += '<div>';
    html += '<strong>Types d\'arrêts (bordures):</strong><br>';
    html += '<div class="calendar-legend-item">';
    html += '<div class="calendar-legend-color" style="background: #fff; border: 3px solid #ff9800;"></div>';
    html += '<span>🔄 Rechute</span>';
    html += '</div>';
    html += '<div class="calendar-legend-item">';
    html += '<div class="calendar-legend-color" style="background: #fff; border: 3px solid #4caf50;"></div>';
    html += '<span>🆕 Nouvelle pathologie</span>';
    html += '</div>';
    html += '<div class="calendar-legend-item">';
    html += '<div class="calendar-legend-color" style="background: #fff; border: 2px solid #ccc;"></div>';
    html += '<span>1ère pathologie</span>';
    html += '</div>';
    html += '</div>';

    html += '</div>';

    html += '</div>';

    return html;
}

/**
 * Extract calendar data from API response
 */
function extractCalendarData(data) {
    const payments = [];

    // Build a map of arret info (is_rechute, rechute_of_arret_index)
    const arretInfo = {};
    if (data && data.arrets && Array.isArray(data.arrets)) {
        data.arrets.forEach((arret, index) => {
            arretInfo[index] = {
                is_rechute: arret.is_rechute,
                rechute_of_arret_index: arret.rechute_of_arret_index,
                arret_from: arret['arret-from-line'],
                arret_to: arret['arret-to-line']
            };
        });
    }

    if (data && data.payment_details && Array.isArray(data.payment_details)) {
        data.payment_details.forEach(detail => {
            const arretIdx = detail.arret_index || 0;
            const info = arretInfo[arretIdx] || {};

            // Add the arrêt start date as a special marker
            if (detail.arret_from) {
                payments.push({
                    date: detail.arret_from,
                    rate: 0,
                    amount: 0,
                    taux: 0,
                    period: 0,
                    arret_index: arretIdx,
                    arret_from: detail.arret_from || '',
                    arret_to: detail.arret_to || '',
                    is_arret_start: true,
                    is_rechute: info.is_rechute,
                    rechute_of_arret_index: info.rechute_of_arret_index
                });
            }

            // Add all daily breakdown entries
            if (detail && detail.daily_breakdown && Array.isArray(detail.daily_breakdown)) {
                detail.daily_breakdown.forEach(day => {
                    if (day && day.date) {
                        payments.push({
                            date: day.date,
                            rate: day.daily_rate || 0,
                            amount: day.amount || 0,
                            taux: day.taux || 0,
                            period: day.period || 0,
                            arret_index: arretIdx,
                            arret_from: detail.arret_from || '',
                            arret_to: detail.arret_to || '',
                            is_arret_start: false,
                            is_rechute: info.is_rechute,
                            rechute_of_arret_index: info.rechute_of_arret_index
                        });
                    }
                });
            }
        });
    }

    return { payments, arretInfo };
}

/**
 * Render calendar for given month/year
 */
function renderCalendar(month, year) {
    const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                        'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    // Update title
    document.getElementById('calendar-month-title').textContent = `${monthNames[month]} ${year}`;

    // Get calendar grid
    const calendarGrid = document.getElementById('calendar-grid');

    // Build calendar
    let html = '<div class="calendar">';

    // Headers (days of week)
    const dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    dayNames.forEach(day => {
        html += `<div class="calendar-header">${day}</div>`;
    });

    // Get first day of month (0 = Sunday, 1 = Monday, etc.)
    const firstDay = new Date(year, month, 1);
    let startingDayOfWeek = firstDay.getDay();
    // Convert Sunday from 0 to 7
    startingDayOfWeek = startingDayOfWeek === 0 ? 7 : startingDayOfWeek;
    // Adjust to start from Monday (1)
    startingDayOfWeek = startingDayOfWeek - 1;

    // Get number of days in month
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Get previous month info
    const prevMonth = month === 0 ? 11 : month - 1;
    const prevYear = month === 0 ? year - 1 : year;
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    // Add days from previous month
    for (let i = startingDayOfWeek - 1; i >= 0; i--) {
        const day = daysInPrevMonth - i;
        html += `<div class="calendar-day other-month">`;
        html += `<div class="calendar-day-number">${day}</div>`;
        html += '</div>';
    }

    // Add days of current month
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const payments = window.calendarData.payments.filter(p => p.date === dateStr);

        html += `<div class="calendar-day">`;
        html += `<div class="calendar-day-number">${day}</div>`;

        // Add payment info
        if (payments.length > 0) {
            payments.forEach(payment => {
                const isArretStart = payment.is_arret_start === true;
                const isPaid = payment.rate > 0;

                let bgColor, displayText, titleText, borderStyle = '';

                // Determine arret type info
                let arretTypeInfo = '';
                if (payment.is_rechute === true) {
                    if (payment.rechute_of_arret_index !== undefined && payment.rechute_of_arret_index !== null) {
                        arretTypeInfo = ` - 🔄 Rechute de l'arrêt #${payment.rechute_of_arret_index + 1}`;
                    } else {
                        arretTypeInfo = ' - 🔄 Rechute';
                    }
                    borderStyle = 'border: 3px solid #ff9800;'; // Orange border for rechute
                } else if (payment.is_rechute === false && payment.arret_index > 0) {
                    arretTypeInfo = ' - 🆕 Nouvelle pathologie';
                    borderStyle = 'border: 3px solid #4caf50;'; // Green border for new pathology
                } else if (payment.arret_index === 0) {
                    arretTypeInfo = ' - 1ère pathologie';
                }

                if (isArretStart) {
                    bgColor = '#ffc107';
                    let startLabel = '🏥 Début';

                    // Add rechute indicator for start date
                    if (payment.is_rechute === true) {
                        startLabel = '🔄 Début rechute';
                        if (payment.rechute_of_arret_index !== undefined && payment.rechute_of_arret_index !== null) {
                            startLabel = `🔄 Rechute #${payment.rechute_of_arret_index + 1}`;
                        }
                    } else if (payment.is_rechute === false && payment.arret_index > 0) {
                        startLabel = '🆕 Nouvelle patho';
                    }

                    displayText = startLabel;
                    titleText = `Arrêt #${payment.arret_index + 1}${arretTypeInfo} - Début: ${payment.arret_from}`;
                } else if (isPaid) {
                    bgColor = '#28a745';
                    displayText = `${payment.rate.toFixed(2)}€`;
                    titleText = `Arrêt #${payment.arret_index + 1}${arretTypeInfo}: ${payment.rate.toFixed(2)}€`;
                } else {
                    bgColor = '#dc3545';
                    displayText = 'Non payé';
                    titleText = `Arrêt #${payment.arret_index + 1}${arretTypeInfo} - Jour non payé (avant droits)`;
                }

                html += `<div class="calendar-payment" style="background: ${bgColor}; ${borderStyle}" title="${titleText}">`;
                html += displayText;
                html += '</div>';
            });
        }

        html += '</div>';
    }

    // Fill remaining days from next month
    const totalCells = startingDayOfWeek + daysInMonth;
    const remainingCells = 7 - (totalCells % 7);
    if (remainingCells < 7) {
        for (let day = 1; day <= remainingCells; day++) {
            html += `<div class="calendar-day other-month">`;
            html += `<div class="calendar-day-number">${day}</div>`;
            html += '</div>';
        }
    }

    html += '</div>';

    calendarGrid.innerHTML = html;
}

/**
 * Navigate to previous month
 */
function previousMonth() {
    currentCalendarMonth--;
    if (currentCalendarMonth < 0) {
        currentCalendarMonth = 11;
        currentCalendarYear--;
    }
    renderCalendar(currentCalendarMonth, currentCalendarYear);
}

/**
 * Navigate to next month
 */
function nextMonth() {
    currentCalendarMonth++;
    if (currentCalendarMonth > 11) {
        currentCalendarMonth = 0;
        currentCalendarYear++;
    }
    renderCalendar(currentCalendarMonth, currentCalendarYear);
}

/**
 * Initialize calendar on first load
 */
function initializeCalendar() {
    renderCalendar(currentCalendarMonth, currentCalendarYear);
}
