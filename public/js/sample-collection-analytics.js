(function () {
    'use strict';

    const root = document.getElementById('sampleAnalyticsDashboard');
    if (!root || typeof Highcharts === 'undefined') return;

    const dataUrl = root.dataset.dataUrl;
    const loginUrl = root.dataset.loginUrl;

    const form = document.getElementById('analyticsFiltersForm');
    const fromInput = document.getElementById('analyticsFrom');
    const toInput = document.getElementById('analyticsTo');
    const consultButton = document.getElementById('analyticsConsultButton');
    const clearButton = document.getElementById('analyticsClearButton');
    const errorBox = document.getElementById('analyticsError');
    const partialBox = document.getElementById('analyticsPartialWarning');
    const lastUpdated = document.getElementById('analyticsLastUpdated');

    let activeRequest = null;

    Highcharts.setOptions({
        lang: {
            decimalPoint: '.',
            thousandsSep: ',',
            noData: 'Sin información para el periodo',
        },
        chart: {
            style: {
                fontFamily: 'Poppins, Arial, sans-serif',
            },
        },
        credits: { enabled: false },
        title: { style: { color: '#18345d', fontWeight: '800' } },
        subtitle: { style: { color: '#718096' } },
        legend: {
            itemStyle: { color: '#40536e', fontSize: '10px', fontWeight: '700' },
            itemHoverStyle: { color: '#18345d' },
        },
        tooltip: {
            borderWidth: 0,
            borderRadius: 10,
            shadow: true,
            backgroundColor: 'rgba(255,255,255,.98)',
            style: { color: '#203653', fontSize: '10px' },
        },
    });

    function number(value) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatNumber(value, decimals = 0) {
        return new Intl.NumberFormat('es-MX', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        }).format(number(value));
    }

    function formatMinutes(value) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? formatNumber(parsed, 2) + ' min' : '—';
    }

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    }

    function showBox(element, message) {
        if (!element) return;
        if (!message) {
            element.hidden = true;
            element.textContent = '';
            return;
        }
        element.textContent = message;
        element.hidden = false;
    }

    function setLoading(isLoading) {
        consultButton.disabled = isLoading;
        clearButton.disabled = isLoading;
        consultButton.setAttribute('aria-busy', isLoading ? 'true' : 'false');

        if (window.VCareLoading) {
            if (isLoading) {
                window.VCareLoading.show(
                    'Cargando datos',
                    'Estamos consultando y consolidando la información del periodo. Esto puede tomar unos segundos.'
                );
            } else {
                window.VCareLoading.hide();
            }
        }
    }

    function validateFilters() {
        if (!fromInput.value || !toInput.value) {
            return 'Selecciona una fecha inicial y final.';
        }

        const from = new Date(fromInput.value + 'T00:00:00');
        const to = new Date(toInput.value + 'T00:00:00');

        if (Number.isNaN(from.getTime()) || Number.isNaN(to.getTime())) {
            return 'Selecciona fechas válidas.';
        }

        if (from > to) {
            return 'La fecha inicial no puede ser posterior a la fecha final.';
        }

        const diffDays = Math.round((to.getTime() - from.getTime()) / 86400000);
        if (diffDays > 365) {
            return 'El rango no puede exceder 366 días.';
        }

        return null;
    }

    function buildQuery() {
        const params = new URLSearchParams({
            from: fromInput.value,
            to: toInput.value,
        });
        return dataUrl + '?' + params.toString();
    }

    function destroyChart(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (typeof Highcharts !== 'undefined' && Array.isArray(Highcharts.charts)) {
            Highcharts.charts.forEach(function (chart) {
                if (chart && chart.renderTo === container) {
                    chart.destroy();
                }
            });
        }

        container.replaceChildren();
    }

    function resetDashboard(options) {
        const settings = options || {};

        setText('analyticsAppointmentsTotal', '—');
        setText('analyticsAttendedPatients', '—');
        setText('analyticsWaitAverage', '—');
        setText('analyticsPhlebotomyAverage', '—');
        setText('analyticsNotArrivedTotal', '—');
        setText('analyticsAverageAge', '—');

        destroyChart('analyticsStatesChart');
        destroyChart('analyticsAgeChart');

        const phlebotomistContainer = document.getElementById('analyticsPhlebotomistChart');
        if (phlebotomistContainer) {
            phlebotomistContainer.replaceChildren();
        }

        showBox(partialBox, '');

        if (!settings.keepError) {
            showBox(errorBox, '');
        }

        if (settings.loading) {
            lastUpdated.textContent = 'Consultando…';
        } else if (!settings.keepLastUpdated) {
            lastUpdated.textContent = 'Sin consultar';
        }
    }

    async function loadSummary() {
        const validationError = validateFilters();
        if (validationError) {
            showBox(errorBox, validationError);
            return;
        }

        if (activeRequest) activeRequest.abort();
        activeRequest = new AbortController();

        // Limpiamos el resultado anterior antes de iniciar la consulta para evitar
        // que se confunda con información ya actualizada.
        resetDashboard({ loading: true });
        setLoading(true);

        try {
            const response = await fetch(buildQuery(), {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: activeRequest.signal,
            });

            let payload = null;
            try {
                payload = await response.json();
            } catch (_) {
                throw new Error('El servidor no regresó una respuesta JSON válida.');
            }

            if (response.status === 401 || payload.session_expired) {
                window.location.href = payload.login_url || loginUrl;
                return;
            }

            if (!response.ok || payload.ok !== true) {
                throw new Error(payload.message || 'No se pudo consultar la analítica del periodo.');
            }

            renderSummary(payload.data || {});
            lastUpdated.textContent = payload.updated_at || 'Actualizado';

            const errors = Array.isArray(payload.data && payload.data.errors)
                ? payload.data.errors
                : [];

            if (errors.length) {
                const messages = errors.map(function (item) {
                    if (typeof item === 'string') return item;
                    if (item && typeof item.message === 'string') return item.message;
                    return 'Una parte del resumen no pudo calcularse.';
                });
                showBox(partialBox, 'La API regresó información parcial: ' + messages.join(' · '));
            }
        } catch (error) {
            if (error && error.name === 'AbortError') return;
            showBox(errorBox, error && error.message ? error.message : 'Ocurrió un error al consultar la analítica.');
        } finally {
            setLoading(false);
            activeRequest = null;
        }
    }

    function formatDateLabel(value) {
        if (!value) return '';
        const parts = String(value).split('-');
        if (parts.length !== 3) return value;
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function renderSummary(data) {
        const orders = data && data.orders && typeof data.orders === 'object' ? data.orders : null;
        const patients = data && data.patients && typeof data.patients === 'object' ? data.patients : null;
        const appointments = orders && orders.appointments && typeof orders.appointments === 'object'
            ? orders.appointments
            : null;
        const attention = orders && orders.attention && typeof orders.attention === 'object'
            ? orders.attention
            : null;

        renderKpis(orders, patients, appointments);
        renderStatesChart(orders && orders.states ? orders.states : null);
        renderAgeAverage(patients);
        renderAgeChart(patients && Array.isArray(patients.byAgeGroup) ? patients.byAgeGroup : []);
        renderPhlebotomistChart(attention && Array.isArray(attention.byPhlebotomist) ? attention.byPhlebotomist : []);
    }

    function renderKpis(orders, patients, appointments) {
        const states = orders && orders.states ? orders.states : null;
        const compliance = appointments && appointments.compliance ? appointments.compliance : null;
        const wait = appointments && appointments.waitTime ? appointments.waitTime : null;
        const phleb = appointments && appointments.phlebotomyTime ? appointments.phlebotomyTime : null;

        setText('analyticsAppointmentsTotal', orders ? formatNumber(orders.total) : '—');
        setText('analyticsAttendedPatients', states ? formatNumber(states.attendedAppointment) : '—');
        setText('analyticsWaitAverage', wait ? formatMinutes(wait.avgMinutes) : '—');
        setText('analyticsPhlebotomyAverage', phleb ? formatMinutes(phleb.avgMinutes) : '—');
        setText('analyticsNotArrivedTotal', compliance ? formatNumber(compliance.notArrived) : '—');
    }


    function monthKeysBetween(fromValue, toValue) {
        if (!fromValue || !toValue) return [];

        const fromParts = String(fromValue).split('-').map(Number);
        const toParts = String(toValue).split('-').map(Number);

        if (fromParts.length !== 3 || toParts.length !== 3) return [];
        if (!fromParts.every(Number.isFinite) || !toParts.every(Number.isFinite)) return [];

        const cursor = new Date(fromParts[0], fromParts[1] - 1, 1);
        const end = new Date(toParts[0], toParts[1] - 1, 1);
        const keys = [];

        while (cursor <= end && keys.length <= 13) {
            keys.push(
                String(cursor.getFullYear()) +
                String(cursor.getMonth() + 1).padStart(2, '0')
            );
            cursor.setMonth(cursor.getMonth() + 1);
        }

        return keys;
    }

    function formatMonthLabel(monthKey) {
        const value = String(monthKey || '');
        if (!/^\d{6}$/.test(value)) return value || 'Sin mes';

        const year = Number(value.slice(0, 4));
        const month = Number(value.slice(4, 6));
        const date = new Date(year, month - 1, 1);

        const monthName = new Intl.DateTimeFormat('es-MX', {
            month: 'short',
        }).format(date).replace('.', '');

        return monthName.charAt(0).toUpperCase() + monthName.slice(1) + ' ' + year;
    }

    function renderStatesChart(states) {
        const monthlyRows = states && Array.isArray(states.byMonth)
            ? states.byMonth
            : [];

        const monthlyMap = new Map();

        monthlyRows.forEach(function (item) {
            const month = String(item && item.month != null ? item.month : '').trim();
            if (!/^\d{6}$/.test(month)) return;

            monthlyMap.set(month, {
                pending: number(item.pendingAppointment),
                attended: number(item.attendedAppointment),
            });
        });

        // Backend solo regresa meses con citas. Completamos el rango consultado
        // con ceros para que el eje siempre mantenga continuidad mensual.
        let monthKeys = monthKeysBetween(fromInput.value, toInput.value);

        if (!monthKeys.length) {
            monthKeys = Array.from(monthlyMap.keys()).sort();
        }

        // Compatibilidad temporal con una respuesta anterior sin states.byMonth.
        if (!monthlyRows.length && !monthKeys.length) {
            monthKeys = ['period'];
        }

        const categories = monthKeys.map(function (month) {
            if (month === 'period') {
                const fromLabel = fromInput && fromInput.value ? formatDateLabel(fromInput.value) : 'Inicio';
                const toLabel = toInput && toInput.value ? formatDateLabel(toInput.value) : 'Fin';
                return fromLabel === toLabel ? fromLabel : fromLabel + ' – ' + toLabel;
            }
            return formatMonthLabel(month);
        });

        const pendingData = monthKeys.map(function (month) {
            if (month === 'period') return states ? number(states.pendingAppointment) : 0;
            const row = monthlyMap.get(month);
            return row ? row.pending : 0;
        });

        const attendedData = monthKeys.map(function (month) {
            if (month === 'period') return states ? number(states.attendedAppointment) : 0;
            const row = monthlyMap.get(month);
            return row ? row.attended : 0;
        });

        const hasData = pendingData.some(value => value > 0) || attendedData.some(value => value > 0);

        Highcharts.chart('analyticsStatesChart', {
            chart: {
                type: 'column',
                backgroundColor: 'transparent',
                spacing: [12, 18, 8, 8],
                zooming: { type: 'x' },
            },
            title: { text: null },
            xAxis: {
                categories: categories.length ? categories : ['Sin datos'],
                lineColor: '#dbe4ef',
                tickColor: '#dbe4ef',
                labels: {
                    autoRotation: [-25, -45],
                    style: {
                        color: '#425875',
                        fontSize: '9px',
                        fontWeight: '700',
                    },
                },
            },
            yAxis: {
                min: 0,
                allowDecimals: false,
                title: {
                    text: 'Citas',
                    style: { color: '#6f8097', fontSize: '9px' },
                },
                gridLineColor: '#edf1f6',
                labels: {
                    style: { color: '#77869b', fontSize: '8px' },
                },
            },
            legend: {
                enabled: true,
                align: 'center',
                verticalAlign: 'top',
                layout: 'horizontal',
                symbolRadius: 5,
                itemStyle: {
                    color: '#425875',
                    fontSize: '9px',
                    fontWeight: '700',
                },
            },
            tooltip: {
                shared: true,
                formatter: function () {
                    let html = '<b>' + this.x + '</b>';
                    let total = 0;

                    this.points.forEach(function (point) {
                        total += number(point.y);
                        html += '<br><span style="color:' + point.color + '">●</span> ' +
                            point.series.name + ': <b>' + formatNumber(point.y) + '</b>';
                    });

                    html += '<br><span style="color:#1769f6">●</span> Total: <b>' + formatNumber(total) + '</b>';
                    return html;
                },
            },
            plotOptions: {
                column: {
                    borderWidth: 0,
                    borderRadius: 5,
                    maxPointWidth: 54,
                    groupPadding: 0.18,
                    pointPadding: 0.07,
                    dataLabels: {
                        enabled: hasData,
                        formatter: function () {
                            return this.y > 0 ? formatNumber(this.y) : '';
                        },
                        style: {
                            color: '#18365f',
                            fontSize: '8px',
                            fontWeight: '800',
                            textOutline: 'none',
                        },
                    },
                },
            },
            series: [
                {
                    name: 'Cita pendiente',
                    color: '#f1b33b',
                    data: hasData ? pendingData : [0],
                },
                {
                    name: 'Cita atendida',
                    color: '#10a66a',
                    data: hasData ? attendedData : [0],
                },
            ],
            credits: { enabled: false },
        });
    }

    function renderDonut(containerId, data, total, unitLabel) {
        const hasData = Array.isArray(data) && data.length > 0 && total > 0;

        Highcharts.chart(containerId, {
            chart: {
                type: 'pie',
                backgroundColor: 'transparent',
                spacing: [4, 4, 4, 4],
            },
            title: {
                text: hasData
                    ? '<span style="font-size:22px;font-weight:900;color:#102b55">' + formatNumber(total) + '</span><br><span style="font-size:9px;color:#76879e">' + unitLabel + '</span>'
                    : '<span style="font-size:11px;color:#8492a6">Sin datos</span>',
                useHTML: true,
                align: 'center',
                verticalAlign: 'middle',
                y: 5,
                floating: true,
            },
            tooltip: {
                pointFormatter: function () {
                    const percent = total > 0 ? (this.y / total) * 100 : 0;
                    return '<b>' + formatNumber(this.y) + '</b><br>' + formatNumber(percent, 1) + '%';
                },
            },
            plotOptions: {
                pie: {
                    innerSize: '66%',
                    size: '78%',
                    center: ['34%', '50%'],
                    borderWidth: 0,
                    dataLabels: { enabled: false },
                    showInLegend: true,
                },
            },
            legend: {
                enabled: hasData,
                align: 'right',
                verticalAlign: 'middle',
                layout: 'vertical',
                width: '42%',
                symbolHeight: 9,
                symbolWidth: 9,
                symbolRadius: 5,
                labelFormatter: function () {
                    const percent = total > 0 ? (this.y / total) * 100 : 0;
                    return this.name + '  ·  ' + formatNumber(this.y) + ' (' + formatNumber(percent, 1) + '%)';
                },
            },
            series: [{
                name: unitLabel,
                data: hasData ? data : [],
            }],
        });
    }


    function renderAgeAverage(patients) {
        const avgAge = patients && patients.avgAge != null
            ? Number(patients.avgAge)
            : NaN;

        setText(
            'analyticsAverageAge',
            Number.isFinite(avgAge) ? formatNumber(Math.round(avgAge)) + ' años' : '—'
        );
    }

    function renderAgeChart(rows) {
        const desiredOrder = ['0-17', '18-39', '40-59', '60-69', '70-79', '80+'];
        const valuesByGroup = new Map();

        rows.forEach(function (item) {
            const rawName = String(item && item.name != null ? item.name : '').trim();
            const normalizedName = rawName
                .replace(/\s*[–—-]\s*/g, '-')
                .replace(/\s*\+\s*/g, '+');

            // "Sin dato" se omite intencionalmente del análisis visual.
            if (normalizedName.toLowerCase() === 'sin dato') return;
            valuesByGroup.set(normalizedName, number(item && item.count));
        });

        const categories = desiredOrder.filter(group => valuesByGroup.has(group));
        const safeCategories = categories.length ? categories : desiredOrder;
        const values = safeCategories.map(group => valuesByGroup.get(group) || 0);
        const hasData = values.some(value => value > 0);

        Highcharts.chart('analyticsAgeChart', {
            chart: {
                type: 'bar',
                backgroundColor: 'transparent',
                spacing: [14, 24, 10, 8],
            },
            title: { text: null },
            xAxis: {
                categories: safeCategories,
                lineColor: '#dbe4ef',
                tickColor: '#dbe4ef',
                labels: {
                    style: {
                        color: '#5e7089',
                        fontSize: '10px',
                        fontWeight: '700',
                    },
                },
            },
            yAxis: {
                min: 0,
                allowDecimals: false,
                title: { text: null },
                gridLineColor: '#edf1f6',
                labels: {
                    style: {
                        color: '#77869b',
                        fontSize: '9px',
                    },
                },
            },
            legend: { enabled: false },
            tooltip: {
                pointFormatter: function () {
                    return '<b>' + formatNumber(this.y) + '</b> pacientes';
                },
            },
            plotOptions: {
                bar: {
                    borderWidth: 0,
                    borderRadius: 5,
                    maxPointWidth: 34,
                    pointPadding: 0.12,
                    groupPadding: 0.08,
                    dataLabels: {
                        enabled: hasData,
                        inside: false,
                        align: 'left',
                        x: 8,
                        formatter: function () { return formatNumber(this.y); },
                        style: {
                            color: '#17355d',
                            fontSize: '9px',
                            fontWeight: '800',
                            textOutline: 'none',
                        },
                    },
                },
            },
            series: [{
                name: 'Pacientes',
                color: '#1769f6',
                data: hasData ? values : values.map(() => 0),
            }],
            credits: { enabled: false },
        });
    }


    function renderPhlebotomistChart(rows) {
        const container = document.getElementById('analyticsPhlebotomistChart');
        if (!container) return;

        const top = rows
            .slice(0, 10)
            .map(function (item) {
                return {
                    name: item && item.name ? String(item.name) : 'Sin nombre',
                    count: number(item && item.count),
                };
            });

        container.replaceChildren();

        if (!top.length || !top.some(item => item.count > 0)) {
            const empty = document.createElement('div');
            empty.className = 'analytics-phlebotomist-empty';
            empty.textContent = 'Sin información de flebotomistas para el periodo consultado.';
            container.appendChild(empty);
            return;
        }

        const maxCount = Math.max.apply(null, top.map(item => item.count));
        const colorClasses = [
            'analytics-progress-blue',
            'analytics-progress-purple',
            'analytics-progress-cyan',
            'analytics-progress-green',
            'analytics-progress-red',
            'analytics-progress-orange',
            'analytics-progress-navy',
            'analytics-progress-slate',
            'analytics-progress-blue',
            'analytics-progress-green',
        ];

        top.forEach(function (item, index) {
            const width = maxCount > 0 ? Math.max((item.count / maxCount) * 100, item.count > 0 ? 2 : 0) : 0;

            const row = document.createElement('div');
            row.className = 'analytics-phlebotomist-row';

            const info = document.createElement('div');
            info.className = 'analytics-phlebotomist-info';

            const identity = document.createElement('div');
            identity.className = 'analytics-phlebotomist-identity';

            const icon = document.createElement('span');
            icon.className = 'analytics-phlebotomist-user-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.innerHTML = '<svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"></circle><path d="M5.5 19c1-3.2 3.2-5 6.5-5s5.5 1.8 6.5 5"></path></svg>';

            const name = document.createElement('strong');
            name.className = 'analytics-phlebotomist-name';
            name.textContent = item.name;

            identity.appendChild(icon);
            identity.appendChild(name);

            const value = document.createElement('span');
            value.className = 'analytics-phlebotomist-value';
            value.textContent = formatNumber(item.count) + ' atenciones';

            info.appendChild(identity);
            info.appendChild(value);

            const progress = document.createElement('div');
            progress.className = 'analytics-progress';
            progress.setAttribute('role', 'progressbar');
            progress.setAttribute('aria-label', item.name + ': ' + formatNumber(item.count) + ' atenciones');
            progress.setAttribute('aria-valuemin', '0');
            progress.setAttribute('aria-valuemax', String(maxCount));
            progress.setAttribute('aria-valuenow', String(item.count));

            const bar = document.createElement('div');
            bar.className = 'analytics-progress-bar ' + colorClasses[index % colorClasses.length];
            bar.style.width = width.toFixed(2) + '%';

            progress.appendChild(bar);
            row.appendChild(info);
            row.appendChild(progress);
            container.appendChild(row);
        });

        const note = document.createElement('div');
        note.className = 'analytics-phlebotomist-note';
        note.textContent = 'La longitud de cada barra es relativa al flebotomista con mayor número de atenciones del periodo.';
        container.appendChild(note);
    }

    function renderColumnChart(containerId, categories, values, seriesName, color, wide) {
        const hasData = categories.length > 0 && values.some(value => value > 0);

        Highcharts.chart(containerId, {
            chart: {
                type: 'column',
                backgroundColor: 'transparent',
                spacing: [12, 10, 6, 4],
            },
            title: { text: null },
            xAxis: {
                categories: hasData ? categories : ['Sin datos'],
                lineColor: '#dbe4ef',
                tickColor: '#dbe4ef',
                labels: {
                    autoRotation: [-25, -45],
                    style: { color: '#5e7089', fontSize: wide ? '9px' : '8px' },
                },
            },
            yAxis: {
                min: 0,
                allowDecimals: false,
                title: { text: seriesName, style: { color: '#6f8097', fontSize: '9px' } },
                gridLineColor: '#edf1f6',
                labels: { style: { color: '#77869b', fontSize: '8px' } },
            },
            legend: { enabled: false },
            tooltip: {
                pointFormatter: function () {
                    return '<b>' + formatNumber(this.y) + '</b> ' + seriesName.toLowerCase();
                },
            },
            plotOptions: {
                column: {
                    borderWidth: 0,
                    borderRadius: 5,
                    maxPointWidth: wide ? 54 : 62,
                    dataLabels: {
                        enabled: hasData,
                        formatter: function () { return formatNumber(this.y); },
                        style: {
                            color: '#18365f',
                            fontSize: '8px',
                            fontWeight: '800',
                            textOutline: 'none',
                        },
                    },
                },
            },
            series: [{
                name: seriesName,
                color: color,
                data: hasData ? values : [0],
            }],
        });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        loadSummary();
    });

    clearButton.addEventListener('click', function () {
        if (activeRequest) {
            activeRequest.abort();
            activeRequest = null;
        }

        setLoading(false);
        fromInput.value = '';
        toInput.value = '';
        resetDashboard();
        fromInput.focus();
    });

    // El tablero inicia vacío. El usuario define el periodo y ejecuta la consulta.
    fromInput.value = '';
    toInput.value = '';
    resetDashboard();
})();
