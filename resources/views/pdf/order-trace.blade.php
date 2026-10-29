<!doctype html>

<html lang="es">

<head>

    <meta charset="utf-8">

    <title>Reporte de Toma de muestra - Orden {{ $report['orderNumber'] }}</title>

    @php
        /*
        |--------------------------------------------------------------------------
        | Fuentes Poppins
        |--------------------------------------------------------------------------
        |
        | Convierte la ruta local a file:/// para que DOMPDF pueda leerla
        | correctamente tanto en Windows como en Linux.
        |
        */

        $reportLogoDataUri = function (string $file) {
            $path = public_path('images/report/' . $file);

            if (!is_file($path) || !is_readable($path)) {
                return null;
            }

            $contents = file_get_contents($path);

            if ($contents === false) {
                return null;
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                default => 'image/png',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($contents);
        };

        $reportLogoLeft = $reportLogoDataUri('logo-1.png');
        $reportLogoRight = $reportLogoDataUri('logo-2.png');

        $poppinsFont = function (string $file) {
            $path = str_replace(
                '\\',
                '/',
                public_path('fonts/poppins/' . $file)
            );

            if (preg_match('/^[A-Za-z]:\//', $path)) {
                return 'file:///' . $path;
            }

            return 'file://' . $path;
        };


        /*
        |--------------------------------------------------------------------------
        | Formato de unidades para DOMPDF
        |--------------------------------------------------------------------------
        |
        | El API puede entregar unidades como X10^3/µL o X10^6/µL. En el tablero
        | el navegador puede mostrarlas correctamente, pero el PDF necesita que
        | el exponente se renderice como HTML <sup> para no imprimir el símbolo ^.
        |
        */
        $formatPdfUnit = function (?string $unit): string {
            $unit = trim((string) $unit);

            if ($unit === '') {
                return '—';
            }

            /*
             * DOMPDF puede aplanar <sup> o dejar el símbolo ^ visible según
             * la fuente/caché del servidor. Para evitar depender de HTML de
             * superíndice, convertimos el exponente directamente a caracteres
             * Unicode reales: ^3 -> ³, ^6 -> ⁶, etc.
             */
            $superscriptMap = [
                '0' => '⁰',
                '1' => '¹',
                '2' => '²',
                '3' => '³',
                '4' => '⁴',
                '5' => '⁵',
                '6' => '⁶',
                '7' => '⁷',
                '8' => '⁸',
                '9' => '⁹',
                '-' => '⁻',
                '+' => '⁺',
            ];

            $formatted = preg_replace_callback(
                '/\^([+-]?\d+)/u',
                static function (array $matches) use ($superscriptMap): string {
                    $exponent = $matches[1];
                    $result = '';

                    foreach (preg_split('//u', $exponent, -1, PREG_SPLIT_NO_EMPTY) as $char) {
                        $result .= $superscriptMap[$char] ?? $char;
                    }

                    return $result;
                },
                $unit
            ) ?? $unit;

            return e($formatted);
        };


        /*
        |--------------------------------------------------------------------------
        | SVG -> Data URI
        |--------------------------------------------------------------------------
        |
        | Para el PDF usamos los iconos como imágenes SVG embebidas.
        | Así no dependemos de Font Awesome ni de otra fuente de iconos.
        |
        */

        $svgDataUri = function (string $svg) {
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        };


        /*
        |--------------------------------------------------------------------------
        | Iconos de secciones
        |--------------------------------------------------------------------------
        */

        $sectionIcons = [

            'attention' => $svgDataUri('
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="8.5"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.8"/>
                    <path d="M12 7.5V12L15 14"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"/>
                    <path d="M9 3.5H15"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.8"
                        stroke-linecap="round"/>
                </svg>
            '),

            'patient' => $svgDataUri('
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="3.4"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.8"/>
                    <path d="M5.5 19C6.5 15.8 8.7 14 12 14C15.3 14 17.5 15.8 18.5 19"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.8"
                        stroke-linecap="round"/>
                </svg>
            '),

            'order' => $svgDataUri('
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <rect x="6" y="4.5" width="12" height="16"
                        rx="2"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.8"/>
                    <path d="M9 9H15"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.8"
                        stroke-linecap="round"/>
                    <path d="M9 13H15"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.8"
                        stroke-linecap="round"/>
                    <path d="M9 17H13"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.8"
                        stroke-linecap="round"/>
                    <path d="M10 3.5H14"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.8"
                        stroke-linecap="round"/>
                </svg>
            '),

            'phlebotomy' => $svgDataUri('
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 3.8C15.4 8.1 17.3 10.6 17.3 13.4
                             C17.3 16.4 14.9 18.8 12 18.8
                             C9.1 18.8 6.7 16.4 6.7 13.4
                             C6.7 10.6 8.6 8.1 12 3.8Z"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.8"/>
                    <path d="M9.6 13.7C10.1 14.8 10.9 15.4 12 15.6"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.6"
                        stroke-linecap="round"/>
                </svg>
            '),

            'studies' => $svgDataUri('
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M9 4V9L5.6 16.1
                             C4.8 17.8 6 19.8 7.9 19.8
                             H16.1
                             C18 19.8 19.2 17.8 18.4 16.1
                             L15 9V4"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"/>
                    <path d="M8.5 4H15.5"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.8"
                        stroke-linecap="round"/>
                    <path d="M8 14H16"
                        fill="none"
                        stroke="#2873C8"
                        stroke-width="1.5"
                        stroke-linecap="round"/>
                </svg>
            '),
        ];


        /*
        |--------------------------------------------------------------------------
        | Iconos Timeline
        |--------------------------------------------------------------------------
        */

        $timelineIcons = [

            'start' => $svgDataUri('
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="8"
                        fill="none"
                        stroke="#337DD6"
                        stroke-width="1.9"/>
                    <path d="M12 7.4V12L15 14"
                        fill="none"
                        stroke="#337DD6"
                        stroke-width="1.9"
                        stroke-linecap="round"
                        stroke-linejoin="round"/>
                    <path d="M9.4 3.7H14.6"
                        fill="none"
                        stroke="#337DD6"
                        stroke-width="1.8"
                        stroke-linecap="round"/>
                </svg>
            '),

            'phlebotomy' => $svgDataUri('
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 4
                             C15 7.9 16.8 10.3 16.8 13
                             C16.8 15.8 14.7 18 12 18
                             C9.3 18 7.2 15.8 7.2 13
                             C7.2 10.3 9 7.9 12 4Z"
                        fill="none"
                        stroke="#B97A08"
                        stroke-width="1.8"/>
                    <path d="M12 8V15"
                        fill="none"
                        stroke="#B97A08"
                        stroke-width="1.6"
                        stroke-linecap="round"/>
                    <path d="M9.5 12.5H14.5"
                        fill="none"
                        stroke="#B97A08"
                        stroke-width="1.6"
                        stroke-linecap="round"/>
                </svg>
            '),

            'complete' => $svgDataUri('
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="8"
                        fill="none"
                        stroke="#24986E"
                        stroke-width="1.9"/>
                    <path d="M8.4 12.2L10.8 14.6L15.7 9.7"
                        fill="none"
                        stroke="#24986E"
                        stroke-width="1.9"
                        stroke-linecap="round"
                        stroke-linejoin="round"/>
                </svg>
            '),

            'total' => $svgDataUri('
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <circle cx="12" cy="13" r="7.2"
                        fill="none"
                        stroke="#7656D5"
                        stroke-width="1.9"/>
                    <path d="M12 9V13L14.7 14.8"
                        fill="none"
                        stroke="#7656D5"
                        stroke-width="1.9"
                        stroke-linecap="round"
                        stroke-linejoin="round"/>
                    <path d="M9.5 3.8H14.5"
                        fill="none"
                        stroke="#7656D5"
                        stroke-width="1.8"
                        stroke-linecap="round"/>
                    <path d="M16.8 6.8L18.1 5.5"
                        fill="none"
                        stroke="#7656D5"
                        stroke-width="1.7"
                        stroke-linecap="round"/>
                </svg>
            '),
        ];


        /*
        |--------------------------------------------------------------------------
        | Timeline del tablero
        |--------------------------------------------------------------------------
        */

        $boardTimeline = [

            [
                'title' => 'Inicio de atención',
                'value' => $report['timeline'][0]['value'] ?? 'Sin registro',
                'complete' => $report['timeline'][0]['complete'] ?? false,
                'icon' => $timelineIcons['start'],
                'background' => '#EEF6FF',
                'border' => '#BFD7F6',
            ],

            [
                'title' => 'Inicio de flebotomía',
                'value' => $report['timeline'][1]['value'] ?? 'Sin registro',
                'complete' => $report['timeline'][1]['complete'] ?? false,
                'icon' => $timelineIcons['phlebotomy'],
                'background' => '#FFF8E9',
                'border' => '#EACB8B',
            ],

            [
                'title' => 'Fin de flebotomía',
                'value' => $report['timeline'][2]['value'] ?? 'Sin registro',
                'complete' => $report['timeline'][2]['complete'] ?? false,
                'icon' => $timelineIcons['complete'],
                'background' => '#ECFBF4',
                'border' => '#A9E2CA',
            ],

            [
                'title' => 'Tiempo total de atención',
                'value' => $report['durations']['total'] ?? 'Sin registro',
                'complete' => !empty($report['durations']['total']),
                'icon' => $timelineIcons['total'],
                'background' => '#F5F0FF',
                'border' => '#D5C7F6',
            ],
        ];
    @endphp


    <style>

        /*
        |--------------------------------------------------------------------------
        | POPPINS
        |--------------------------------------------------------------------------
        */

        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 400;
            src: url("{{ $poppinsFont('Poppins-Regular.ttf') }}") format('truetype');
        }

        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 500;
            src: url("{{ $poppinsFont('Poppins-Medium.ttf') }}") format('truetype');
        }

        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 600;
            src: url("{{ $poppinsFont('Poppins-SemiBold.ttf') }}") format('truetype');
        }

        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 700;
            src: url("{{ $poppinsFont('Poppins-Bold.ttf') }}") format('truetype');
        }


        html,
        body,
        body *,
        table,
        thead,
        tbody,
        tfoot,
        tr,
        td,
        th,
        div,
        span,
        p,
        small,
        strong,
        h1,
        h2,
        h3,
        h4 {
            font-family: 'Poppins', sans-serif !important;
        }


        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;

            color: #253a56;

            font-family: 'Poppins', sans-serif;

            font-size: 9px;

            font-weight: 400;

            line-height: 1.42;
        }


        h1,
        h2,
        h3,
        p {
            margin: 0;
        }


        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .header {
            padding-bottom: 10px;

            border-bottom: 2px solid #1b64bd;

            display: table;

            width: 100%;
        }


        .header-brand,
        .header-center,
        .header-meta {
            display: table-cell;
            vertical-align: middle;
        }


        .header-brand {
            width: 32%;
            text-align: left;
        }


        .header-center {
            width: 44%;
            text-align: center;
        }


        .header-meta {
            width: 24%;
            text-align: right;
        }


        .logo {
            width: 122px;
            height: auto;
            margin-bottom: 4px;
        }

        /*
         * Los dos archivos forman el identificador institucional. Se mantienen
         * prácticamente unidos para que visualmente se lean como un solo logo.
         */
        .report-logos {
            white-space: nowrap;
            line-height: 0;
        }

        .report-logo {
            display: inline-block;
            width: auto;
            vertical-align: middle;
            object-fit: contain;
        }

        .report-logo-left {
            max-width: 48px;
            max-height: 48px;
            margin-right: 2px;
        }

        .report-logo-right {
            max-width: 145px;
            max-height: 44px;
            margin-left: 0;
        }


        .brand-text {
            color: #1b64bd;

            font-size: 19px;

            font-weight: 700;

            letter-spacing: .01em;
        }


        .title {
            margin: 0;
            color: #18375f;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.25;
            text-align: center;
        }


        .subtitle {
            margin-top: 3px;
            color: #70839c;
            font-size: 8px;
            font-weight: 400;
            text-align: center;
        }


        .meta-label {
            color: #7d8ea4;

            font-size: 7px;

            font-weight: 500;

            text-transform: uppercase;

            letter-spacing: .05em;
        }


        .meta-value {
            margin-top: 3px;

            color: #18375f;

            font-size: 10px;

            font-weight: 600;
        }


        .meta-user {
            margin-top: 5px;
            color: #70839c;
            font-size: 6.8px;
            font-weight: 400;
            line-height: 1.35;
        }

        .meta-user strong {
            color: #294566;
            font-size: 7.2px;
            font-weight: 600;
        }


        /*
        |--------------------------------------------------------------------------
        | ORDEN
        |--------------------------------------------------------------------------
        */

        .order-banner {
            margin-top: 11px;

            padding: 10px 12px;

            border: 1px solid #d8e5f4;

            border-radius: 8px;

            background: #f5f9ff;

            display: table;

            width: 100%;
        }


        .order-banner-main,
        .order-banner-state {
            display: table-cell;

            vertical-align: middle;
        }


        .order-banner-main {
            width: 72%;
        }


        .order-banner-state {
            width: 28%;

            text-align: right;
        }


        .eyebrow {
            color: #7a8ca3;

            font-size: 7px;

            font-weight: 500;

            text-transform: uppercase;

            letter-spacing: .05em;
        }


        .order-number {
            margin-top: 2px;

            color: #1457b6;

            font-size: 16px;

            font-weight: 700;

            letter-spacing: .02em;
        }


        .state-pill {
            display: inline-block;

            padding: 5px 9px;

            border-radius: 12px;

            color: #31577e;

            background: #e7f1fc;

            font-size: 8px;

            font-weight: 600;
        }


        /*
        |--------------------------------------------------------------------------
        | ALERTAS
        |--------------------------------------------------------------------------
        */

        .alerts {
            margin: 9px 0 0;

            padding: 0;

            list-style: none;
        }


        .alerts li {
            margin-top: 5px;

            padding: 7px 9px;

            border-left: 3px solid #dca72f;

            background: #fff9eb;

            color: #6d5722;

            font-size: 8px;

            font-weight: 400;
        }


        .alerts li strong {
            font-weight: 600;
        }


        .alerts li.info {
            border-left-color: #4c84c8;

            background: #f3f8fe;

            color: #3b5f87;
        }


        /*
        |--------------------------------------------------------------------------
        | SECCIONES
        |--------------------------------------------------------------------------
        */

        .section {
            margin-top: 11px;

            padding: 10px 11px 11px;

            border: 1px solid #dfe7f1;

            border-radius: 8px;

            page-break-inside: avoid;
        }


        .section.studies-section {
            page-break-inside: auto;
        }


        /*
        |--------------------------------------------------------------------------
        | ENCABEZADOS DE SECCIÓN CON ICONO
        |--------------------------------------------------------------------------
        */

        .section-heading {
            width: 100%;

            margin: 0 0 9px 0;

            padding: 0;

            border-collapse: collapse;
        }


        .section-heading td {
            padding: 0;

            vertical-align: middle;
        }


        .section-heading-icon-cell {
            width: 31px;
        }


        .section-heading-icon {
            position: relative;

            width: 26px;
            height: 26px;

            border: 1px solid #d5e4f6;
            border-radius: 7px;

            background: #f2f7ff;
        }

        .section-heading-icon img {
            position: absolute;

            width: 15px;
            height: 15px;

            top: 50%;
            left: 50%;

            margin-top: -7.5px;
            margin-left: -7.5px;
        }


        .section-title {
            margin: 0;

            color: #19365f;

            font-size: 10.5px;

            font-weight: 600;

            line-height: 1.25;
        }


        /*
        |--------------------------------------------------------------------------
        | TIMELINE DEL TABLERO
        |--------------------------------------------------------------------------
        */

        .board-timeline {
            width: 100%;

            margin-top: 2px;

            border-collapse: collapse;

            table-layout: fixed;
        }


        .board-timeline > tbody > tr > td {
            width: 25%;

            padding: 0 2px;

            vertical-align: top;

            text-align: center;
        }


        /*
         * Esta tabla interior genera las líneas a izquierda y derecha
         * del círculo sin depender de flexbox ni position:absolute.
         */

        .timeline-node-row {
            width: 100%;

            border-collapse: collapse;

            table-layout: fixed;
        }


        .timeline-node-row td {
            padding: 0;

            vertical-align: middle;
        }


        .timeline-line-cell {
            width: auto;
        }


        .timeline-line {
            height: 1px;

            background: #d7e2f0;

            font-size: 0;

            line-height: 0;
        }


        .timeline-line.hidden {
            background: transparent;
        }


        .timeline-icon-cell {
            width: 40px;

            text-align: center;
        }


        .timeline-icon-circle {
            position: relative;

            width: 34px;
            height: 34px;

            margin: 0 auto;

            border: 1.5px solid #bfd7f6;
            border-radius: 50%;
        }

        .timeline-icon-circle img {
            position: absolute;

            width: 18px;
            height: 18px;

            top: 50%;
            left: 50%;

            margin-top: -9px;
            margin-left: -9px;
        }


        .timeline-step-title {
            margin-top: 7px;

            color: #213f68;

            font-size: 7.8px;

            font-weight: 600;

            line-height: 1.3;

            text-align: center;
        }


        .timeline-step-value {
            margin-top: 3px;

            color: #70839e;

            font-size: 7px;

            font-weight: 400;

            line-height: 1.3;

            text-align: center;
        }


        .timeline-step-value.total-value {
            color: #7656d5;

            font-size: 8px;

            font-weight: 600;
        }


        /*
        |--------------------------------------------------------------------------
        | PACIENTE
        |--------------------------------------------------------------------------
        */

        .patient-name {
            margin-bottom: 8px;

            padding: 9px 10px;

            border-radius: 6px;

            background: #edf5ff;
        }


        .patient-name small {
            display: block;

            color: #6e839e;

            font-size: 7px;

            font-weight: 500;

            text-transform: uppercase;
        }


        .patient-name strong {
            display: block;

            margin-top: 3px;

            color: #183b68;

            font-size: 12px;

            font-weight: 700;

            line-height: 1.3;
        }


        /*
        |--------------------------------------------------------------------------
        | DATOS GENERALES
        |--------------------------------------------------------------------------
        */

        .facts {
            width: 100%;

            border-collapse: separate;

            border-spacing: 6px 5px;

            margin: -5px -6px -5px;
        }


        .facts td {
            width: 50%;

            padding: 7px 8px;

            border-radius: 5px;

            background: #f6f8fb;

            vertical-align: top;
        }


        .facts.four td {
            width: 25%;
        }


        .fact-label {
            display: block;

            margin-bottom: 3px;

            color: #7a8b9f;

            font-size: 7px;

            font-weight: 400;
        }


        .fact-value {
            color: #294566;

            font-size: 8.7px;

            font-weight: 600;

            word-break: break-word;

            line-height: 1.35;
        }


        /*
        |--------------------------------------------------------------------------
        | ESTUDIOS
        |--------------------------------------------------------------------------
        */

        .studies-summary {
            margin-bottom: 7px;

            color: #6f8299;

            font-size: 7.5px;

            font-weight: 400;
        }


        .study {
            margin-top: 7px;

            border: 1px solid #e1e8f0;

            border-radius: 6px;

            page-break-inside: avoid;
        }


        .study-head {
            padding: 7px 8px;

            background: #f9fbfd;

            display: table;

            width: 100%;
        }


        .study-name,
        .study-state {
            display: table-cell;

            vertical-align: middle;
        }


        .study-name {
            width: 78%;
        }


        .study-state {
            width: 22%;

            text-align: right;
        }


        .study-name strong {
            display: block;

            color: #1d3c65;

            font-size: 8.5px;

            font-weight: 600;

            line-height: 1.3;
        }


        .study-name small {
            display: block;

            margin-top: 2px;

            color: #7c8da2;

            font-size: 7px;

            font-weight: 400;
        }


        .study-pill {
            display: inline-block;

            padding: 3px 6px;

            border-radius: 9px;

            font-size: 6.8px;

            font-weight: 600;
        }


        .study-pill.done {
            color: #176d4d;

            background: #e5f7ef;
        }


        .study-pill.pending {
            color: #8a5c00;

            background: #fff2d4;
        }


        .test-table {
            width: 100%;

            border-collapse: collapse;
        }

        .test-table th:nth-child(1),
        .test-table td:nth-child(1) {
            width: 26%;
        }

        .test-table th:nth-child(2),
        .test-table td:nth-child(2) {
            width: 11%;
        }

        .test-table th:nth-child(3),
        .test-table td:nth-child(3) {
            width: 12%;
        }

        .test-table th:nth-child(4),
        .test-table td:nth-child(4) {
            width: 14%;
        }

        .test-table th:nth-child(5),
        .test-table td:nth-child(5) {
            width: 15%;
        }

        .test-table th:nth-child(6),
        .test-table td:nth-child(6) {
            width: 22%;
        }


        .test-table th,
        .test-table td {
            padding: 5px 7px;

            border-top: 1px solid #edf1f5;

            text-align: left;

            vertical-align: top;
        }


        .test-table th {
            color: #71849c;

            font-size: 6.7px;

            font-weight: 600;

            background: #fcfdfe;

            text-transform: uppercase;
        }


        .test-table td {
            color: #405a7b;

            font-size: 7.2px;

            font-weight: 400;

            line-height: 1.35;
        }


        /*
         * Solo la columna Unidad usa DejaVu Sans como respaldo técnico.
         * DOMPDF la incluye y soporta µ y superíndices Unicode (³, ⁶, etc.).
         * El resto del reporte conserva Poppins.
         */
        .test-table td.test-unit {
            font-family: 'DejaVu Sans', sans-serif !important;
        }


        .no-tests {
            padding: 7px 8px;

            color: #8594a7;

            font-size: 7px;

            font-weight: 400;
        }


        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        /* Pie y paginado se dibujan desde OrderTraceDomPdfDriver. */



        /*
        |--------------------------------------------------------------------------
        | Legibilidad para impresión
        |--------------------------------------------------------------------------
        |
        | El reporte conserva fondos, bordes e iconos de color, pero todo el texto
        | informativo se fuerza a negro para evitar que impresoras con poco contraste
        | conviertan los tonos gris/azul en texto demasiado tenue.
        |
        */
        body,
        .title,
        .subtitle,
        .meta-label,
        .meta-value,
        .eyebrow,
        .order-number,
        .section-title,
        .timeline-step-title,
        .timeline-step-value,
        .timeline-step-value.total-value,
        .patient-name small,
        .patient-name strong,
        .fact-label,
        .fact-value,
        .studies-summary,
        .study-name strong,
        .study-name small,
        .test-table th,
        .test-table td,
        .no-tests,
        .footer-note {
            color: #000000 !important;
        }

    </style>

</head>


<body>


    {{-- ================================================================
        HEADER
    ================================================================= --}}

    <header class="header">

        <div class="header-brand">

            <div class="report-logos">
                @if ($reportLogoLeft)
                    <img class="report-logo report-logo-left" src="{{ $reportLogoLeft }}" alt="Logo institucional 1">
                @endif
                @if ($reportLogoRight)
                    <img class="report-logo report-logo-right" src="{{ $reportLogoRight }}" alt="Logo institucional 2">
                @endif
            </div>

        </div>


        <div class="header-center">

            <div class="title">
                Reporte de Toma de muestra
            </div>

            <div class="subtitle">
                Trazabilidad individual de orden
            </div>

        </div>


        <div class="header-meta">

            <div class="meta-label">
                Generado por {{ $report['generatedBy'] ?? 'Usuario' }}
            </div>


            <div class="meta-value">
                {{ $report['generatedAt'] }}
            </div>

        </div>

    </header>



    {{-- ================================================================
        ORDEN CONSULTADA
    ================================================================= --}}

    <div class="order-banner">

        <div class="order-banner-main">

            <div class="eyebrow">
                Orden consultada
            </div>


            <div class="order-number">
                {{ $report['orderNumber'] }}
            </div>

        </div>


        <div class="order-banner-state">

            <span class="state-pill">
                {{ $report['state'] }}
            </span>

        </div>

    </div>



    {{-- ================================================================
        ALERTAS
    ================================================================= --}}

    @if (!empty($report['alerts']))

        <ul class="alerts">

            @foreach ($report['alerts'] as $alert)

                <li class="{{ $alert['severity'] === 'warning' ? '' : 'info' }}">

                    <strong>
                        {{ $alert['severity'] === 'warning'
                            ? 'Revisar:'
                            : 'Información:' }}
                    </strong>

                    {{ $alert['message'] }}

                </li>

            @endforeach

        </ul>

    @endif



    {{-- ================================================================
        ATENCIÓN
    ================================================================= --}}

    <section class="section">


        <table class="section-heading">

            <tr>

                <td class="section-heading-icon-cell">

                    <div class="section-heading-icon">

                        <img
                            src="{{ $sectionIcons['attention'] }}"
                            alt=""
                        >

                    </div>

                </td>


                <td>

                    <h2 class="section-title">
                        Atención
                    </h2>

                </td>

            </tr>

        </table>



        <table class="board-timeline">

            <tr>

                @foreach ($boardTimeline as $index => $step)

                    <td>

                        <table class="timeline-node-row">

                            <tr>

                                {{-- Línea izquierda --}}

                                <td class="timeline-line-cell">

                                    <div
                                        class="timeline-line {{ $index === 0 ? 'hidden' : '' }}"
                                    ></div>

                                </td>


                                {{-- Icono --}}

                                <td class="timeline-icon-cell">

                                    <div
                                        class="timeline-icon-circle"
                                        style="
                                            background: {{ $step['background'] }};
                                            border-color: {{ $step['border'] }};
                                        "
                                    >

                                        <img
                                            src="{{ $step['icon'] }}"
                                            alt=""
                                        >

                                    </div>

                                </td>


                                {{-- Línea derecha --}}

                                <td class="timeline-line-cell">

                                    <div
                                        class="timeline-line {{ $index === count($boardTimeline) - 1 ? 'hidden' : '' }}"
                                    ></div>

                                </td>

                            </tr>

                        </table>


                        <div class="timeline-step-title">

                            {{ $step['title'] }}

                        </div>


                        <div
                            class="timeline-step-value {{ $index === 3 ? 'total-value' : '' }}"
                        >

                            {{ $step['value'] }}

                        </div>

                    </td>

                @endforeach

            </tr>

        </table>

    </section>



    {{-- ================================================================
        DATOS DEL PACIENTE
    ================================================================= --}}

    <section class="section">


        <table class="section-heading">

            <tr>

                <td class="section-heading-icon-cell">

                    <div class="section-heading-icon">

                        <img
                            src="{{ $sectionIcons['patient'] }}"
                            alt=""
                        >

                    </div>

                </td>


                <td>

                    <h2 class="section-title">
                        Datos del paciente
                    </h2>

                </td>

            </tr>

        </table>



        <div class="patient-name">

            <small>
                Nombre del paciente
            </small>


            <strong>
                {{ $report['patient']['name'] }}
            </strong>

        </div>



        <table class="facts">

            <tr>

                <td>

                    <span class="fact-label">
                        Temporal
                    </span>

                    <span class="fact-value">
                        {{ $report['patient']['temporal'] }}
                    </span>

                </td>


                <td>

                    <span class="fact-label">
                        Registro
                    </span>

                    <span class="fact-value">
                        {{ $report['patient']['record'] }}
                    </span>

                </td>

            </tr>


            <tr>

                <td>

                    <span class="fact-label">
                        Fecha de nacimiento
                    </span>

                    <span class="fact-value">
                        {{ $report['patient']['birthDate'] }}
                    </span>

                </td>


                <td>

                    <span class="fact-label">
                        Edad / Sexo
                    </span>

                    <span class="fact-value">
                        {{ $report['patient']['age'] }}
                        ·
                        {{ $report['patient']['gender'] }}
                    </span>

                </td>

            </tr>

        </table>

    </section>



    {{-- ================================================================
        DATOS DE LA ORDEN
    ================================================================= --}}

    <section class="section">


        <table class="section-heading">

            <tr>

                <td class="section-heading-icon-cell">

                    <div class="section-heading-icon">

                        <img
                            src="{{ $sectionIcons['order'] }}"
                            alt=""
                        >

                    </div>

                </td>


                <td>

                    <h2 class="section-title">
                        Datos de la orden
                    </h2>

                </td>

            </tr>

        </table>



        <table class="facts">

            <tr>

                <td>

                    <span class="fact-label">
                        Número de orden
                    </span>

                    <span class="fact-value">
                        {{ $report['orderNumber'] }}
                    </span>

                </td>


                <td>

                    <span class="fact-label">
                        Fecha y hora de creación
                    </span>

                    <span class="fact-value">
                        {{ $report['order']['createdAt'] }}
                    </span>

                </td>

            </tr>


            <tr>

                <td>

                    <span class="fact-label">
                        Servicio
                    </span>

                    <span class="fact-value">
                        {{ $report['order']['service'] }}
                    </span>

                </td>


                <td>

                    <span class="fact-label">
                        Tipo
                    </span>

                    <span class="fact-value">
                        {{ $report['order']['type'] }}
                    </span>

                </td>

            </tr>

        </table>

    </section>



    {{-- ================================================================
        FLEBOTOMÍA
    ================================================================= --}}

    <section class="section">


        <table class="section-heading">

            <tr>

                <td class="section-heading-icon-cell">

                    <div class="section-heading-icon">

                        <img
                            src="{{ $sectionIcons['phlebotomy'] }}"
                            alt=""
                        >

                    </div>

                </td>


                <td>

                    <h2 class="section-title">
                        Flebotomía
                    </h2>

                </td>

            </tr>

        </table>



        <table class="facts four">

            <tr>

                <td>

                    <span class="fact-label">
                        Puerta asignada
                    </span>

                    <span class="fact-value">
                        {{ $report['phlebotomy']['door'] }}
                    </span>

                </td>


                <td>

                    <span class="fact-label">
                        Flebotomista
                    </span>

                    <span class="fact-value">
                        {{ $report['phlebotomy']['phlebotomist'] }}
                    </span>

                </td>


                <td>

                    <span class="fact-label">
                        Inicio
                    </span>

                    <span class="fact-value">
                        {{ $report['phlebotomy']['start'] }}
                    </span>

                </td>


                <td>

                    <span class="fact-label">
                        Fin
                    </span>

                    <span class="fact-value">
                        {{ $report['phlebotomy']['end'] }}
                    </span>

                </td>

            </tr>

        </table>

    </section>



    {{-- ================================================================
        ESTUDIOS
    ================================================================= --}}

    <section class="section studies-section">


        <table class="section-heading">

            <tr>

                <td class="section-heading-icon-cell">

                    <div class="section-heading-icon">

                        <img
                            src="{{ $sectionIcons['studies'] }}"
                            alt=""
                        >

                    </div>

                </td>


                <td>

                    <h2 class="section-title">
                        Estudios
                    </h2>

                </td>

            </tr>

        </table>



        <div class="studies-summary">

            {{ count($report['studies']) }}

            {{ count($report['studies']) === 1
                ? 'estudio registrado'
                : 'estudios registrados' }}

            en la orden.

        </div>



        @forelse ($report['studies'] as $study)


            <article class="study">


                <div class="study-head">


                    <div class="study-name">

                        <strong>
                            {{ $study['name'] }}
                        </strong>


                        <small>

                            {{ $study['number'] }}

                            {{ $study['isProfile'] ? ' · Perfil' : '' }}

                        </small>

                    </div>



                    <div class="study-state">

                        <span
                            class="study-pill {{ $study['validated'] ? 'done' : 'pending' }}"
                        >

                            {{ $study['validated']
                                ? 'Validado'
                                : 'Pendiente' }}

                        </span>

                    </div>

                </div>



                @if (!empty($study['tests']))


                    <table class="test-table">

                        <thead>

                            <tr>

                                <th>
                                    Prueba
                                </th>

                                <th>
                                    Resultado
                                </th>

                                <th>
                                    Unidad
                                </th>

                                <th>
                                    Área
                                </th>

                                <th>
                                    Referencia
                                </th>

                                <th>
                                    Químico
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            @foreach ($study['tests'] as $test)

                                <tr>

                                    <td>

                                        {{ $test['name'] }}

                                    </td>


                                    <td>
                                        {{ $test['result'] !== '' ? $test['result'] : '—' }}
                                    </td>

                                    <td class="test-unit">
                                        {!! $formatPdfUnit($test['unit'] ?? '') !!}
                                    </td>

                                    <td>
                                        {{ $test['area'] !== '' ? $test['area'] : '—' }}
                                    </td>

                                    <td>
                                        {{ $test['reference'] !== '' ? $test['reference'] : '—' }}
                                    </td>

                                    <td>
                                        {{ $test['validator'] !== '' ? $test['validator'] : '—' }}
                                    </td>

                                </tr>

                            @endforeach


                        </tbody>

                    </table>


                @else


                    <div class="no-tests">

                        Este estudio no tiene pruebas detalladas.

                    </div>


                @endif


            </article>


        @empty


            <div class="no-tests">

                No hay estudios registrados en la orden.

            </div>


        @endforelse


    </section>



</body>

</html>