(function () {
    'use strict';

    const root = document.getElementById('sampleCollectionDashboard');
    if (!root || typeof Highcharts === 'undefined') {
        return;
    }

    const state = {
        data: window.sampleCollectionInitialData || null,
        orderNumbers: [],
        traceRequest: 0,
        charts: {
            hour: null,
            door: null
        }
    };

    const elements = {
        form: document.getElementById('sampleCollectionFilters'),
        date: document.getElementById('sampleDate'),
        door: document.getElementById('sampleDoor'),
        reset: document.getElementById('sampleResetButton'),
        submit: document.getElementById('sampleFilterButton'),
        alert: document.getElementById('sampleInlineAlert'),
        lastUpdated: document.getElementById('sampleLastUpdated'),
        totalPatients: document.getElementById('sampleTotalPatients'),
        totalHelper: document.getElementById('sampleTotalHelper'),
        withinPercent: document.getElementById('sampleWithinPercent'),
        withinHelper: document.getElementById('sampleWithinHelper'),
        overPercent: document.getElementById('sampleOverPercent'),
        overHelper: document.getElementById('sampleOverHelper'),
        averageTime: document.getElementById('sampleAverageTime'),
        averageHelper: document.getElementById('sampleAverageHelper'),
        averageStatus: document.getElementById('sampleAverageStatus'),
        peakHour: document.getElementById('samplePeakHour'),
        topDoor: document.getElementById('sampleTopDoor'),
        doorCoverage: document.getElementById('sampleDoorCoverage'),
        orderSearchForm: document.getElementById('sampleOrderSearchForm'),
        orderSearchInput: document.getElementById('sampleOrderSearchInput'),
        orderSuggestions: document.getElementById('sampleOrderSearchSuggestions'),
        orderSearchStatus: document.getElementById('sampleOrderSearchStatus'),
        orderDetails: document.getElementById('sampleOrderDetails'),
        orderPdfButton: document.getElementById('sampleOrderPdfButton')
    };

    const numberFormatter = new Intl.NumberFormat('es-MX');
    const decimalFormatter = new Intl.NumberFormat('es-MX', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1
    });

    const statusLabels = {
        good: 'En meta',
        warning: 'Atención',
        danger: 'Fuera de meta'
    };

    const detailIcons = {
        patient: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0M18 7v5m-2.5-2.5h5"/></svg>',
        order: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v6h5M9 13h6m-6 4h6"/></svg>',
        studies: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6m-5 0v6l-5.5 9.2A2 2 0 0 0 6.2 21h11.6a2 2 0 0 0 1.7-2.8L14 9V3M8 16h8"/></svg>',
        phlebotomy: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.5S6 10.2 6 14a6 6 0 0 0 12 0c0-3.8-6-10.5-6-10.5Z"/><path d="M9.5 15.5a2.5 2.5 0 0 0 2.5 2.5"/></svg>',
        phlebotomist: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/></svg>',
        arrival: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2m-8-7 2-2"/></svg>',
        phlebotomyStart: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.5S6.5 9.8 6.5 13.7a5.5 5.5 0 0 0 11 0C17.5 9.8 12 3.5 12 3.5Z"/><path d="M12 10v7m-3.5-3.5h7"/></svg>',
        phlebotomyEnd: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>',
        totalAttention: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 1.5M9 2h6m-3 0v3m5.7 1.3 1.4-1.4"/></svg>'
    };

    function formatNumber(value) {
        return numberFormatter.format(Number(value) || 0);
    }

    function formatDecimal(value) {
        return decimalFormatter.format(Number(value) || 0);
    }

    // Los promedios vienen en minutos decimales; se presentan como tiempo común.
    function formatDurationFromMinutes(value) {
        const totalSeconds = Math.max(0, Math.round((Number(value) || 0) * 60));
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        const parts = [];
        if (hours) parts.push(hours + (hours === 1 ? ' h' : ' h'));
        if (minutes) parts.push(minutes + ' min');
        if (seconds && hours === 0) parts.push(seconds + ' s');
        return parts.length ? parts.join(' ') : '0 s';
    }

    function hourSlot(value) {
        const raw = String(value == null ? '' : value).trim();
        if (/^-?\d+$/.test(raw)) return Number(raw);
        const match = raw.match(/^(\d{1,2}):\d{2}/);
        return match ? Number(match[1]) - 6 : null;
    }

    function hourRangeLabel(slot) {
        const startHour = 6 + Number(slot);
        const endHour = startHour + 1;
        const clock = function (hour) {
            const normalized = ((hour % 24) + 24) % 24;
            return (normalized % 12 || 12) + ':00';
        };
        const period = function (hour) { return hour % 24 < 12 ? 'am' : 'pm'; };
        const start = clock(startHour);
        const end = clock(endHour);
        if (period(startHour) === period(endHour)) return start + ' - ' + end + ' ' + period(endHour);
        return start + ' ' + period(startHour) + ' - ' + end + ' ' + period(endHour);
    }

    function sharedHourSlots(data) {
        const slots = new Set();
        (Array.isArray(data.hours) ? data.hours : []).forEach(function (value) {
            const slot = hourSlot(value);
            if (slot !== null) slots.add(slot);
        });
        (Array.isArray(data.patientsByHour) ? data.patientsByHour : []).forEach(function (row) {
            const slot = hourSlot(row.hour);
            if (slot !== null) slots.add(slot);
        });
        (Array.isArray(data.averageTimeByDoorAndHour) ? data.averageTimeByDoorAndHour : []).forEach(function (row) {
            (Array.isArray(row.hours) ? row.hours : []).forEach(function (cell) {
                const slot = hourSlot(cell.hour);
                if (slot !== null) slots.add(slot);
            });
        });

        // Mostrar siempre la jornada desde las 6:00 a. m. y rellenar los
        // bloques sin actividad para que la gráfica y la tabla no comiencen
        // en la primera hora que tenga datos.
        let lastSlot = 8;
        slots.forEach(function (slot) {
            if (Number.isFinite(slot) && slot >= 0) {
                lastSlot = Math.max(lastSlot, Math.min(slot, 23));
            }
        });
        return Array.from({ length: lastSlot + 1 }, function (_, slot) { return slot; });
    }

    function formatDate(dateValue) {
        if (!dateValue) {
            return 'Sin fecha';
        }

        const date = new Date(dateValue + 'T12:00:00');
        return new Intl.DateTimeFormat('es-MX', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        }).format(date);
    }

    function formatGeneratedAt(value) {
        if (!value) {
            return 'Actualización no disponible';
        }

        const date = new Date(value);
        return 'Actualizado ' + new Intl.DateTimeFormat('es-MX', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        }).format(date);
    }

    function setLoading(isLoading) {
        root.classList.toggle('sample-loading', isLoading);
        elements.submit.disabled = isLoading;
        elements.reset.disabled = isLoading;

        const buttonText = elements.submit.querySelector('span');
        if (buttonText) {
            buttonText.textContent = isLoading ? 'Consultando...' : 'Consultar';
        }
    }

    function showError(message) {
        elements.alert.hidden = false;
        elements.alert.textContent = message;
    }

    function clearError() {
        elements.alert.hidden = true;
        elements.alert.textContent = '';
    }

    function statusClass(status) {
        if (status === 'good') {
            return 'sample-heat-good';
        }

        if (status === 'warning') {
            return 'sample-heat-warning';
        }

        if (status === 'danger') {
            return 'sample-heat-danger';
        }

        return 'sample-heat-empty';
    }

    function updateSummary(data) {
        const summary = data.summary || {};
        const selectedDoorName = elements.door && elements.door.options[elements.door.selectedIndex]
            ? elements.door.options[elements.door.selectedIndex].text
            : 'Todas las puertas';

        const attendedPatients = Number(summary.totalPatients) || 0;
        const hasTotalAppointments = data.totalAppointments !== undefined && data.totalAppointments !== null;
        const totalAppointments = Number(data.totalAppointments) || 0;

        if (hasTotalAppointments) {
            elements.totalPatients.innerHTML =
                '<span class="sample-kpi-attended">' + formatNumber(attendedPatients) + '</span>' +
                '<span class="sample-kpi-separator"> / </span>' +
                '<span class="sample-kpi-total-appointments">' + formatNumber(totalAppointments) + '</span>';
        } else {
            elements.totalPatients.textContent = formatNumber(attendedPatients);
        }
        elements.totalHelper.textContent = formatDate(data.filters && data.filters.date) + ' · ' + selectedDoorName;

        elements.withinPercent.textContent = formatDecimal(summary.within60Percentage) + '%';
        elements.withinHelper.textContent = formatNumber(summary.patientsWithin60) + ' pacientes dentro de meta';

        elements.overPercent.textContent = formatDecimal(summary.over60Percentage) + '%';
        elements.overHelper.textContent = formatNumber(summary.patientsOver60) + ' pacientes fuera de meta';

        elements.averageTime.textContent = formatDurationFromMinutes(summary.averageAttentionMinutes);
        elements.averageHelper.textContent = 'Promedio general de atención';
        elements.averageStatus.textContent = statusLabels[summary.averageStatus] || 'Sin datos';
        elements.averageStatus.classList.remove('sample-heat-good', 'sample-heat-warning', 'sample-heat-danger');
        elements.averageStatus.classList.add(statusClass(summary.averageStatus));

        elements.lastUpdated.textContent = formatGeneratedAt(data.generatedAt);

    }

    function renderHourChart(data) {
        const rows = Array.isArray(data.patientsByHour) ? data.patientsByHour : [];
        const slots = sharedHourSlots(data);
        const rowsBySlot = new Map();
        rows.forEach(function (row) {
            const slot = hourSlot(row.hour);
            if (slot !== null) rowsBySlot.set(slot, row);
        });
        const categories = slots.map(hourRangeLabel);
        const values = slots.map(function (slot) {
            return Number((rowsBySlot.get(slot) || {}).totalPatients) || 0;
        });
        const averages = slots.map(function (slot) {
            return Number((rowsBySlot.get(slot) || {}).averageAttentionMinutes) || 0;
        });
        const percentages = slots.map(function (slot) {
            return Number((rowsBySlot.get(slot) || {}).percentage) || 0;
        });

        if (state.charts.hour) {
            state.charts.hour.destroy();
        }

        state.charts.hour = Highcharts.chart('samplePatientsByHourChart', {
            chart: {
                type: 'column',
                height: 305,
                spacing: [16, 8, 10, 4]
            },
            title: { text: null },
            xAxis: {
                categories: categories,
                crosshair: true,
                tickLength: 0,
                title: { text: null }
            },
            yAxis: {
                min: 0,
                allowDecimals: false,
                title: { text: 'Pacientes' }
            },
            legend: { enabled: false },
            tooltip: {
                useHTML: true,
                formatter: function () {
                    const index = this.point.index;
                    let detail = '<b>' + this.x + '</b><br>' +
                        '<span style="color:#1769f6">●</span> ' + formatNumber(this.y) + ' pacientes' +
                        ' (' + formatDecimal(percentages[index]) + '%)';

                    if (averages[index] > 0) {
                        detail += '<br><span style="color:#66758f">Tiempo promedio: ' +
                            formatDurationFromMinutes(averages[index]) + '</span>';
                    }

                    return detail;
                }
            },
            plotOptions: {
                column: {
                    color: '#1769f6',
                    borderWidth: 0,
                    borderRadius: 5,
                    maxPointWidth: 42,
                    pointPadding: 0.08,
                    groupPadding: 0.12,
                    dataLabels: {
                        enabled: true,
                        formatter: function () {
                            return this.y > 0 ? formatNumber(this.y) : '';
                        },
                        style: {
                            color: '#27415f',
                            fontSize: '9px',
                            fontWeight: '800',
                            textOutline: '2px #ffffff'
                        }
                    }
                }
            },
            series: [{
                name: 'Pacientes',
                data: values
            }]
        });

        const peak = rows.reduce(function (best, row) {
            return !best || Number(row.totalPatients) > Number(best.totalPatients) ? row : best;
        }, null);

        elements.peakHour.textContent = peak
            ? 'Hora de mayor demanda: ' + hourRangeLabel(hourSlot(peak.hour)) + ' (' + formatNumber(peak.totalPatients) + ' pacientes)'
            : 'Hora de mayor demanda: —';
    }

    function renderDoorChart(data) {
        const rows = Array.isArray(data.patientsByDoor) ? data.patientsByDoor : [];
        const categories = rows.map(function (row) { return row.doorName; });
        const values = rows.map(function (row) {
            return {
                y: Number(row.totalPatients) || 0,
                custom: {
                    percentage: Number(row.percentage) || 0,
                    averageMinutes: Number(row.averageAttentionMinutes) || 0
                }
            };
        });

        if (state.charts.door) {
            state.charts.door.destroy();
        }

        state.charts.door = Highcharts.chart('samplePatientsByDoorChart', {
            chart: {
                type: 'bar',
                height: 305,
                spacing: [12, 12, 8, 4]
            },
            title: { text: null },
            xAxis: {
                categories: categories,
                tickLength: 0,
                title: { text: null }
            },
            yAxis: {
                min: 0,
                allowDecimals: false,
                title: { text: 'Pacientes' }
            },
            legend: { enabled: false },
            tooltip: {
                useHTML: true,
                formatter: function () {
                    return '<b>' + this.x + '</b><br>' +
                        formatNumber(this.y) + ' pacientes (' + formatDecimal(this.point.custom.percentage) + '%)<br>' +
                        'Promedio: ' + formatDecimal(this.point.custom.averageMinutes) + ' min';
                }
            },
            plotOptions: {
                bar: {
                    color: '#0b2f6b',
                    borderWidth: 0,
                    borderRadius: 5,
                    maxPointWidth: 24,
                    dataLabels: {
                        enabled: true,
                        align: 'right',
                        inside: false,
                        formatter: function () {
                            return formatNumber(this.y);
                        },
                        style: {
                            color: '#30435f',
                            fontSize: '9px',
                            fontWeight: '800',
                            textOutline: '2px #ffffff'
                        }
                    }
                }
            },
            series: [{
                name: 'Pacientes',
                data: values
            }]
        });

        const topDoor = rows.reduce(function (best, row) {
            return !best || Number(row.totalPatients) > Number(best.totalPatients) ? row : best;
        }, null);

        elements.topDoor.textContent = topDoor
            ? 'Mayor volumen: ' + topDoor.doorName + ' · ' + formatNumber(topDoor.totalPatients)
            : 'Mayor volumen: —';
        elements.doorCoverage.textContent = formatNumber(rows.length) + (rows.length === 1 ? ' puerta con actividad' : ' puertas con actividad');
    }

    function collectOrderNumbers(data) {
        const numbers = new Set();
        (Array.isArray(data.averageTimeByDoorAndHour) ? data.averageTimeByDoorAndHour : []).forEach(function (door) {
            (Array.isArray(door.hours) ? door.hours : []).forEach(function (hour) {
                const values = Array.isArray(hour.orderNumbers) ? hour.orderNumbers : (Array.isArray(hour.orders) ? hour.orders : []);
                values.forEach(function (item) {
                    const number = typeof item === 'object' && item !== null
                        ? (item.orderNumber || item.order || item.number)
                        : item;
                    const value = String(number || '').trim();
                    if (/^\d{12}$/.test(value)) numbers.add(value);
                });
            });
        });
        return Array.from(numbers).sort();
    }

    function renderOrderSuggestions() {
        const query = elements.orderSearchInput.value.trim();
        elements.orderSuggestions.innerHTML = '';
        if (query.length < 3) {
            elements.orderSuggestions.hidden = true;
            return;
        }

        const matches = state.orderNumbers.filter(function (number) { return number.includes(query); }).slice(0, 8);
        if (!matches.length) {
            elements.orderSuggestions.hidden = true;
            return;
        }

        elements.orderSuggestions.innerHTML = matches.map(function (number) {
            return '<button type="button" role="option" class="sample-order-suggestion" data-order-number="' + escapeHtml(number) + '">' + escapeHtml(number) + '</button>';
        }).join('');
        elements.orderSuggestions.hidden = false;
    }

    function setOrderPdfButton(orderNumber) {
        if (!elements.orderPdfButton) return;

        const value = String(orderNumber || '').trim();
        const validOrder = /^\d{12}$/.test(value);
        elements.orderPdfButton.disabled = !validOrder;
        elements.orderPdfButton.dataset.orderNumber = validOrder ? value : '';
        elements.orderPdfButton.title = validOrder
            ? 'Abrir reporte PDF de la orden ' + value
            : 'Primero consulta una orden';
    }

    function resetOrderSearch(data) {
        state.orderNumbers = collectOrderNumbers(data);
        state.traceRequest += 1;
        elements.orderSearchInput.value = '';
        elements.orderSuggestions.innerHTML = '';
        elements.orderSuggestions.hidden = true;
        setOrderPdfButton('');
        elements.orderDetails.innerHTML = '<div class="sample-order-empty-state">Busca un número de orden para consultar los datos del paciente, sus estudios y la flebotomía.</div>';
        elements.orderSearchStatus.textContent = state.orderNumbers.length
            ? state.orderNumbers.length + (state.orderNumbers.length === 1 ? ' orden disponible para esta consulta.' : ' órdenes disponibles para esta consulta.')
            : 'No hay órdenes disponibles para la fecha y puerta seleccionadas.';
    }

    function traceDateTime(value) {
        if (!value) return 'Sin registro';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return String(value);
        return new Intl.DateTimeFormat('es-MX', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: true
        }).format(date);
    }

    function traceMinutes(value) {
        if (value == null || !Number.isFinite(Number(value))) return 'Pendiente';
        const minutes = Math.max(0, Math.round(Number(value)));
        const hours = Math.floor(minutes / 60);
        const remaining = minutes % 60;
        if (hours && remaining) return hours + ' h ' + remaining + ' min';
        if (hours) return hours + (hours === 1 ? ' hora' : ' horas');
        return minutes + ' min';
    }

    function parseCalendarDate(value) {
        const match = String(value == null ? '' : value).trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!match) return null;
        const date = { year: Number(match[1]), month: Number(match[2]), day: Number(match[3]) };
        const check = new Date(date.year, date.month - 1, date.day);
        if (check.getFullYear() !== date.year || check.getMonth() !== date.month - 1 || check.getDate() !== date.day) return null;
        return date;
    }

    function calculateAge(birthDate, referenceDate) {
        const birth = parseCalendarDate(birthDate);
        const today = new Date();
        const reference = parseCalendarDate(referenceDate) || {
            year: today.getFullYear(),
            month: today.getMonth() + 1,
            day: today.getDate()
        };
        if (!birth || !reference) return '—';

        let age = reference.year - birth.year;
        if (reference.month < birth.month || (reference.month === birth.month && reference.day < birth.day)) age -= 1;
        return age >= 0 ? age + (age === 1 ? ' año' : ' años') : '—';
    }

    function detailHeading(iconName, title) {
        return '<h3 class="sample-detail-heading"><span class="sample-detail-heading-icon">' + detailIcons[iconName] + '</span><span>' + title + '</span></h3>';
    }

    function renderOrderTrace(trace) {
        const order = trace.order || {};
        const patient = trace.patient || {};
        const timeline = Array.isArray(trace.timeline) ? trace.timeline : [];
        const studies = Array.isArray(trace.studies) ? trace.studies : [];
        const alerts = Array.isArray(trace.alerts) ? trace.alerts : [];
        const durations = trace.durations || {};
        const phlebotomist = order.phlebotomist || {};
        const patientName = patient.fullName || [patient.firstName, patient.fatherLastName, patient.motherLastName].filter(Boolean).join(' ') || 'Paciente no identificado';
        const phlebotomistName = [phlebotomist.firstName, phlebotomist.lastName].filter(Boolean).join(' ') || 'Sin registro';
        const eventTimes = {};
        timeline.forEach(function (event) { eventTimes[event.event] = event.at; });
        let attentionMinutes = null;
        if (durations.waitingMinutes != null && durations.phlebotomyMinutes != null) {
            attentionMinutes = Number(durations.waitingMinutes) + Number(durations.phlebotomyMinutes);
        } else if (eventTimes.arrival && eventTimes.phlebotomyEnd) {
            const elapsed = new Date(eventTimes.phlebotomyEnd) - new Date(eventTimes.arrival);
            if (Number.isFinite(elapsed) && elapsed >= 0) attentionMinutes = Math.floor(elapsed / 60000);
        }

        const timelineSteps = [
            { event: 'arrival', label: 'Inicio de atención' },
            { event: 'phlebotomyStart', label: 'Inicio de flebotomía' },
            { event: 'phlebotomyEnd', label: 'Fin de la flebotomía' },
            { event: 'totalAttention', label: 'Tiempo total de atención', value: attentionMinutes }
        ];
        const timelineHtml = '<ol class="sample-order-timeline">' + timelineSteps.map(function (step) {
            const value = step.event === 'totalAttention' ? step.value : eventTimes[step.event];
            const complete = step.event === 'totalAttention' ? value != null : Boolean(value);
            const displayValue = step.event === 'totalAttention' ? traceMinutes(value) : traceDateTime(value);
            return '<li class="' + (complete ? 'is-complete' : 'is-missing') + '"><span class="sample-order-timeline-marker">' + detailIcons[step.event] + '</span><div><strong>' + step.label + '</strong><time>' + escapeHtml(displayValue) + '</time></div></li>';
        }).join('') + '</ol>';

        const studiesHtml = studies.length
            ? studies.map(function (study) {
                const tests = Array.isArray(study.tests) ? study.tests : [];
                return '<details class="sample-trace-study"><summary><span><strong>' + escapeHtml(study.studyName || study.studyAbbreviation || 'Estudio') + '</strong><small>' + escapeHtml(study.studyNumber || 'Sin código') + (study.isProfile ? ' · Perfil' : '') + '</small></span><span class="sample-trace-study-state ' + (study.validated ? 'is-done' : 'is-pending') + '">' + (study.validated ? 'Validado' : 'Pendiente') + '</span></summary><div class="sample-trace-tests">' +
                    (tests.length ? tests.map(function (test) {
                        const validator = test.validatedBy ? [test.validatedBy.firstName, test.validatedBy.lastName].filter(Boolean).join(' ') : '';
                        const reference = [test.referenceMin, test.referenceMax].filter(function (item) { return item != null && item !== ''; }).join(' – ');
                        return '<article><strong>' + escapeHtml(test.testName || test.testAbbreviation || test.testNumber || 'Prueba') + '</strong><span>Resultado: ' + escapeHtml(test.result == null ? '—' : test.result) + (test.unit ? ' ' + escapeHtml(test.unit) : '') + '</span><small>' + escapeHtml(test.areaName || test.laboratory || 'Área no especificada') + (reference ? ' · Referencia: ' + escapeHtml(reference) : '') + (validator ? ' · Validó: ' + escapeHtml(validator) : '') + '</small></article>';
                    }).join('') : '<p class="sample-trace-muted">Este estudio no tiene pruebas detalladas.</p>') +
                    '</div></details>';
            }).join('')
            : '<p class="sample-trace-muted">No hay estudios registrados en la orden.</p>';

        const alertsHtml = alerts.map(function (alert) {
            const message = alert.message || alert.label || alert.description || alert.code || 'Alerta de la orden';
            return '<li class="' + (alert.severity === 'warning' ? 'is-warning' : 'is-info') + '"><strong>' + escapeHtml(alert.severity === 'warning' ? 'Revisar' : 'Información') + '</strong><span>' + escapeHtml(message) + '</span></li>';
        }).join('');
        elements.orderDetails.innerHTML =
            '<section class="sample-order-result-heading"><div><small>Orden consultada</small><h3>' + escapeHtml(order.orderNumber || '—') + '</h3></div><span class="sample-trace-state">' + escapeHtml((order.state && order.state.label) || 'Estado no disponible') + '</span></section>' +
            (alertsHtml ? '<ul class="sample-trace-alerts">' + alertsHtml + '</ul>' : '') +
            '<section class="sample-order-detail-block sample-order-timeline-block">' + detailHeading('arrival', 'Atención') + timelineHtml + '</section>' +
            '<section class="sample-order-detail-block sample-patient-block">' + detailHeading('patient', 'Datos del paciente') +
                '<div class="sample-patient-name"><small>Nombre del paciente</small><strong>' + escapeHtml(patientName) + '</strong></div>' +
                '<dl class="sample-order-facts"><div><dt>Temporal</dt><dd>' + escapeHtml(patient.patientID || '—') + '</dd></div><div><dt>Registro</dt><dd>' + escapeHtml(patient.patientNumber == null || patient.patientNumber === '' ? '—' : patient.patientNumber) + '</dd></div><div><dt>Fecha de nacimiento</dt><dd>' + escapeHtml(patient.birthDate || '—') + '</dd></div><div><dt>Edad</dt><dd>' + escapeHtml(calculateAge(patient.birthDate, order.orderCreatedDate || order.orderCreatedAt)) + '</dd></div><div><dt>Sexo</dt><dd>' + escapeHtml(patient.gender || '—') + '</dd></div></dl>' +
            '</section>' +
            '<section class="sample-order-detail-block">' + detailHeading('order', 'Datos de la orden') + '<dl class="sample-order-facts">' +
                '<div><dt>Número de orden</dt><dd>' + escapeHtml(order.orderNumber || '—') + '</dd></div><div><dt>Fecha y hora de creación</dt><dd>' + escapeHtml(traceDateTime(order.orderCreatedAt || order.orderCreatedDate)) + '</dd></div><div><dt>Servicio</dt><dd>' + escapeHtml(order.serviceName || '—') + '</dd></div><div><dt>Tipo</dt><dd>' + escapeHtml(order.typeName || order.typeInitial || '—') + '</dd></div>' +
            '</dl></section>' +
            '<section class="sample-order-detail-block sample-phlebotomy-block">' + detailHeading('phlebotomy', 'Flebotomía') + '<dl class="sample-phlebotomy-facts">' +
                '<div><dt>Puerta asignada</dt><dd>' + escapeHtml(order.doorName || 'Sin registro') + '</dd></div><div><dt><span class="sample-inline-fact-icon">' + detailIcons.phlebotomist + '</span>Flebotomista</dt><dd>' + escapeHtml(phlebotomistName) + '</dd></div><div><dt>Inicio de flebotomía</dt><dd>' + escapeHtml(traceDateTime(eventTimes.phlebotomyStart)) + '</dd></div><div><dt>Fin de flebotomía</dt><dd>' + escapeHtml(traceDateTime(eventTimes.phlebotomyEnd)) + '</dd></div>' +
            '</dl></section>' +
            '<section class="sample-order-detail-block">' + detailHeading('studies', 'Estudios') + '<div class="sample-trace-study-list">' + studiesHtml + '</div></section>';
    }

    async function openOrderTrace(orderNumber) {
        const requestId = ++state.traceRequest;
        elements.orderSearchInput.value = orderNumber;
        elements.orderSuggestions.hidden = true;
        elements.orderSearchStatus.textContent = 'Consultando la orden ' + orderNumber + '…';
        setOrderPdfButton('');
        elements.orderDetails.innerHTML = '<div class="sample-trace-loading">Consultando la orden…</div>';

        try {
            const traceUrl = root.dataset.orderTraceUrlTemplate.replace('__ORDER_NUMBER__', encodeURIComponent(orderNumber));
            const response = await fetch(traceUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store'
            });
            const payload = await response.json().catch(function () { return {}; });
            if (payload.sessionExpired && payload.redirect) {
                window.location.href = payload.redirect;
                return;
            }
            if (requestId !== state.traceRequest) return;
            if (!response.ok || !payload.ok || !payload.data) {
                throw new Error(payload.message || ('La consulta respondió con estatus ' + response.status + '.'));
            }
            renderOrderTrace(payload.data);
            setOrderPdfButton(orderNumber);
            elements.orderSearchStatus.textContent = 'Detalle de la orden ' + orderNumber + '. Ya puedes abrir su reporte PDF.';
        } catch (error) {
            if (requestId !== state.traceRequest) return;
            setOrderPdfButton('');
            elements.orderDetails.innerHTML = '<div class="sample-trace-error">' + escapeHtml(error.message || 'No se pudo consultar la orden.') + '</div>';
            elements.orderSearchStatus.textContent = '';
        }
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function syncDoorOptions(data) {
        if (!elements.door) {
            return;
        }

        const doors = Array.isArray(data.doors) ? data.doors : [];
        const requestedDoor = data.filters && data.filters.doorID
            ? String(data.filters.doorID)
            : String(elements.door.value || 'all');

        elements.door.innerHTML = '';

        const allOption = document.createElement('option');
        allOption.value = 'all';
        allOption.textContent = 'Todas las puertas';
        elements.door.appendChild(allOption);

        doors.forEach(function (door) {
            const option = document.createElement('option');
            option.value = String(door.doorID);
            option.textContent = door.doorName || ('Puerta ' + door.doorID);
            elements.door.appendChild(option);
        });

        const exists = Array.from(elements.door.options).some(function (option) {
            return option.value === requestedDoor;
        });

        elements.door.value = exists ? requestedDoor : 'all';
    }

    function render(data) {
        syncDoorOptions(data);
        state.data = data;
        updateSummary(data);
        renderHourChart(data);
        renderDoorChart(data);
        resetOrderSearch(data);
    }

    async function loadData() {
        clearError();
        setLoading(true);

        const params = new URLSearchParams({
            date: elements.date.value,
            door: elements.door ? elements.door.value : 'all'
        });

        try {
            const response = await fetch(root.dataset.endpoint + '?' + params.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const payload = await response.json().catch(function () {
                return {};
            });

            if (payload.sessionExpired && payload.redirect) {
                window.location.href = payload.redirect;
                return;
            }

            if (!response.ok || !payload.ok || !payload.data) {
                throw new Error(
                    payload.message || ('La consulta respondió con estatus ' + response.status + '.')
                );
            }

            render(payload.data);

            const url = new URL(window.location.href);
            url.searchParams.set('date', elements.date.value);
            url.searchParams.set('door', elements.door ? elements.door.value : 'all');
            window.history.replaceState({}, '', url);
        } catch (error) {
            showError(error.message || 'No fue posible actualizar el tablero.');
        } finally {
            setLoading(false);
        }
    }

    elements.form.addEventListener('submit', function (event) {
        event.preventDefault();
        loadData();
    });

    elements.orderSearchInput.addEventListener('input', function () {
        elements.orderSearchInput.value = elements.orderSearchInput.value.replace(/\D/g, '').slice(0, 12);
        if (elements.orderPdfButton && elements.orderPdfButton.dataset.orderNumber !== elements.orderSearchInput.value.trim()) {
            setOrderPdfButton('');
        }
        renderOrderSuggestions();
    });
    elements.orderSearchInput.addEventListener('focus', renderOrderSuggestions);
    elements.orderSuggestions.addEventListener('click', function (event) {
        const suggestion = event.target.closest('[data-order-number]');
        if (suggestion) openOrderTrace(suggestion.dataset.orderNumber);
    });
    document.addEventListener('click', function (event) {
        if (!elements.orderSuggestions.contains(event.target) && event.target !== elements.orderSearchInput) {
            elements.orderSuggestions.hidden = true;
        }
    });
    elements.orderSearchForm.addEventListener('submit', function (event) {
        event.preventDefault();
        const orderNumber = elements.orderSearchInput.value.trim();
        if (!/^\d{12}$/.test(orderNumber)) {
            setOrderPdfButton('');
            elements.orderSearchStatus.textContent = 'Escribe un número de orden válido de 12 dígitos.';
            elements.orderSearchInput.focus();
            return;
        }
        if (!state.orderNumbers.includes(orderNumber)) {
            setOrderPdfButton('');
            elements.orderSearchStatus.textContent = 'No encontramos esa orden entre los resultados de la fecha y puerta consultadas.';
            elements.orderDetails.innerHTML = '<div class="sample-order-empty-state">Prueba con una orden sugerida para la consulta actual.</div>';
            return;
        }
        openOrderTrace(orderNumber);
    });

    if (elements.orderPdfButton) {
        elements.orderPdfButton.addEventListener('click', function () {
            const orderNumber = String(elements.orderPdfButton.dataset.orderNumber || '').trim();
            if (!/^\d{12}$/.test(orderNumber)) return;

            const pdfUrl = root.dataset.orderTracePdfUrlTemplate.replace(
                '__ORDER_NUMBER__',
                encodeURIComponent(orderNumber)
            );

            window.open(pdfUrl, '_blank', 'noopener');
        });
    }

    elements.reset.addEventListener('click', function () {
        const today = new Date();
        const localDate = new Date(today.getTime() - today.getTimezoneOffset() * 60000)
            .toISOString()
            .slice(0, 10);

        elements.date.value = localDate;
        if (elements.door) {
            elements.door.value = 'all';
        }
        loadData();
    });

    window.addEventListener('resize', function () {
        window.clearTimeout(window.sampleCollectionResizeTimer);
        window.sampleCollectionResizeTimer = window.setTimeout(function () {
            Object.values(state.charts).forEach(function (chart) {
                if (chart) {
                    chart.reflow();
                }
            });
        }, 120);
    });

    if (state.data) {
        render(state.data);
    }
})();
