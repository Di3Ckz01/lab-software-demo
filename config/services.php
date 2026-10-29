<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file stores credentials and configuration for third party services.
    |
    */

    'go_api' => [
        'base_url' => env(
            'GO_API_BASE_URL',
            ''
        ),

        'timeout' => (int) env('GO_API_TIMEOUT', 60),
        'use_mock' => env('GO_API_USE_MOCK', false),

        /*
        |------------------------------------------------------------------
        | Autenticación y órdenes
        |------------------------------------------------------------------
        */
        'auth_endpoint' => env(
            'GO_API_AUTH_ENDPOINT',
            '/login'
        ),

        'orders_endpoint' => env(
            'GO_API_ORDERS_ENDPOINT',
            '/orders'
        ),

        /*
        |------------------------------------------------------------------
        | Tablero de estudios
        |------------------------------------------------------------------
        */
        'studies_wheel_endpoint' => env(
            'GO_API_STUDIES_WHEEL_ENDPOINT',
            '/rueda/{date}'
        ),

        'orders_by_year_endpoint' => env(
            'GO_API_ORDERS_BY_YEAR_ENDPOINT',
            '/orders-by-year/{year}'
        ),

        'tests_by_year_endpoint' => env(
            'GO_API_TESTS_BY_YEAR_ENDPOINT',
            '/tests-by-year/{year}'
        ),

        /*
         * /tests-by-year/{year} construye un resumen anual y puede tardar
         * más que las demás consultas. En Postman puede superar 60 segundos.
         */
        'tests_by_year_timeout' => (int) env(
            'GO_API_TESTS_BY_YEAR_TIMEOUT',
            180
        ),

        'studies_paginated_endpoint' => env(
            'GO_API_STUDIES_PAGINATED_ENDPOINT',
            '/paginated-tests/{cursor}-{pageSize}'
        ),

        /*
         * Agrega aquí las equivalencias oficiales cuando
         * backend confirme el catálogo de áreas.
         */
        'studies_area_labels' => [
            // '11' => 'Hematología',
            // '20' => 'Bioquímica',
        ],


        /*
        |------------------------------------------------------------------
        | Tablero de Toma de Muestra
        |------------------------------------------------------------------
        | La fecha se envía como YYYYMMDD:
        | GET /tomaDeMuestra/20260622
        */
        'sample_collection_endpoint' => env(
            'GO_API_SAMPLE_COLLECTION_ENDPOINT',
            '/tomaDeMuestra/{day}'
        ),


        /*
         * Cola paginada para la pantalla de Toma de Muestra.
         * Primera página: GET /phlebotomy_queue/0-50
         * Siguiente:      GET /phlebotomy_queue/{nextCursor}-50
         * Endpoint protegido: requiere Bearer token
         */
        'phlebotomy_queue_endpoint' => env(
            'GO_API_PHLEBOTOMY_QUEUE_ENDPOINT',
            '/phlebotomy_queue/{cursor}-{pageSize}'
        ),

        /*
        |------------------------------------------------------------------
        | Tablero de productividad
        |------------------------------------------------------------------
        */
        /*
         * Endpoint diario mediante GET.
         *
         * General:          GET /productivity/20260706
         * Consulta externa: GET /productivity/20260706/0
         * Hospitalización:  GET /productivity/20260706/1
         *
         * No se envía body JSON ni serviceIDs en query string.
         */
        'productivity_endpoint' => env(
            'GO_API_PRODUCTIVITY_ENDPOINT',
            '/productivity/{date}'
        ),

        /*
         * Sufijos definidos por backend para las etiquetas del tablero.
         * El tablero general no utiliza sufijo.
         */
        'productivity_scope_suffixes' => [
            'consulta_externa' => (int) env(
                'GO_API_PRODUCTIVITY_OUTPATIENT_SUFFIX',
                0
            ),
            'hospitalizacion' => (int) env(
                'GO_API_PRODUCTIVITY_HOSPITALIZATION_SUFFIX',
                1
            ),
        ],

        'duplicated_venopunctures_endpoint' => env(
            'GO_API_DUPLICATED_VENOPUNCTURES_ENDPOINT',
            '/duplicated-venopunctures/{period}'
        ),

        /*
         * Tiempo máximo de espera para productividad.
         * 600 segundos equivalen a 10 minutos.
         */
        'productivity_timeout' => (int) env(
            'GO_API_PRODUCTIVITY_TIMEOUT',
            600
        ),

        /*
         * Productividad trabaja por día para no descargar semanas o meses completos.
         */
        'productivity_max_range_days' => (int) env(
            'PRODUCTIVITY_MAX_RANGE_DAYS',
            1
        ),

        /*
         * Máximo de registros que Laravel agregará en una sola solicitud.
         */
        'productivity_max_records' => (int) env(
            'PRODUCTIVITY_MAX_RECORDS',
            250000
        ),

        /*
         * Caché breve de resultados agregados (no guarda el JSON completo).
         */
        'productivity_cache_seconds' => (int) env(
            'PRODUCTIVITY_CACHE_SECONDS',
            90
        ),

        /*
         * Evita decodificar respuestas JSON demasiado grandes en memoria.
         */
        'productivity_max_response_mb' => (int) env(
            'PRODUCTIVITY_MAX_RESPONSE_MB',
            64
        ),

        /*
         * Tiempo máximo para venopunciones duplicadas.
         * 300 segundos equivalen a 5 minutos.
         */
        'duplicated_venopunctures_timeout' => (int) env(
            'GO_API_DUPLICATED_VENOPUNCTURES_TIMEOUT',
            300
        ),

        'productivity_opportunity_limit_minutes' => (int) env(
            'PRODUCTIVITY_OPPORTUNITY_LIMIT_MINUTES',
            60
        ),

        'productivity_warning_limit_minutes' => (int) env(
            'PRODUCTIVITY_WARNING_LIMIT_MINUTES',
            90
        ),

        'productivity_shifts' => [
            'morning' => env(
                'PRODUCTIVITY_SHIFT_MORNING',
                'Matutino'
            ),

            'evening' => env(
                'PRODUCTIVITY_SHIFT_EVENING',
                'Vespertino'
            ),

            'night' => env(
                'PRODUCTIVITY_SHIFT_NIGHT',
                'Nocturno'
            ),

            'unknown' => env(
                'PRODUCTIVITY_SHIFT_UNKNOWN',
                'Sin turno'
            ),
        ],

        /*
        |------------------------------------------------------------------
        | Usuarios
        |------------------------------------------------------------------
        */
        'users_endpoint' => env(
            'GO_API_USERS_ENDPOINT',
            '/users'
        ),

        'all_users_endpoint' => env(
            'GO_API_ALL_USERS_ENDPOINT',
            '/all-users'
        ),

        'user_update_endpoint' => env(
            'GO_API_USER_UPDATE_ENDPOINT',
            '/edit-user'
        ),

        'user_update_method' => env(
            'GO_API_USER_UPDATE_METHOD',
            'POST'
        ),

        /*
        |------------------------------------------------------------------
        | Tiempo de oportunidad
        |------------------------------------------------------------------
        | /opp-times/{date} : tablero diario de estudios/estados.
        | /opp-times-1 : tablero independiente para pantalla completa (TV).
        */
        'opp_times_endpoint' => env(
            'GO_API_OPP_TIMES_ENDPOINT',
            '/opp-times/{date}'
        ),

        'opp_times_tv_endpoint' => env(
            'GO_API_OPP_TIMES_TV_ENDPOINT',
            '/opp-times-1'
        ),

        // Order trace
        'order_trace_endpoint' => env(
            'GO_API_ORDER_TRACE_ENDPOINT',
            '/order-trace/{orderNumber}'
        ),

        // Executive analytics
        'executive_summary_endpoint' => env(
            'GO_API_EXECUTIVE_SUMMARY_ENDPOINT',
            '/executive-summary/{from}-{to}'
        ),

        // Supplier management
        'supplier_endpoint' => env('GO_API_SUPPLIER_ENDPOINT', '/supplier'),
        'suppliers_create_endpoint' => env('GO_API_SUPPLIERS_CREATE_ENDPOINT', '/suppliers'),
        'supplier_edit_endpoint' => env('GO_API_SUPPLIER_EDIT_ENDPOINT', '/edit-supplier'),
        'supplier_assign_study_endpoint' => env('GO_API_SUPPLIER_ASSIGN_STUDY_ENDPOINT', '/suppliers/assign-study'),

        'supplier_studies_endpoint' => env(
            'GO_API_SUPPLIER_STUDIES_ENDPOINT',
            '/suppliers/{supplierID}/studies'
        ),

        'supplier_unassign_study_endpoint' => env(
            'GO_API_SUPPLIER_UNASSIGN_STUDY_ENDPOINT',
            '/suppliers/{supplierID}/studies/{clinicalStudyID}'
        ),

        // Clinical studies
        'clinical_study_endpoint' => env(
            'GO_API_CLINICAL_STUDY_ENDPOINT',
            '/clinicalStudy/{start}-{end}'
        ),

        'clinical_studies_catalog_endpoint' => env(
            'GO_API_CLINICAL_STUDIES_CATALOG_ENDPOINT',
            '/clinical-studies/{page}'
        ),

        'clinical_studies_totals_endpoint' => env(
            'GO_API_CLINICAL_STUDIES_TOTALS_ENDPOINT',
            '/clinical-studies/{year}/{monthNumber}/totals'
        ),

        'clinical_studies_pending_endpoint' => env(
            'GO_API_CLINICAL_STUDIES_PENDING_ENDPOINT',
            '/clinical-studies/{year}/{monthNumber}/pending'
        ),

        'clinical_studies_available_periods_endpoint' => env(
            'GO_API_CLINICAL_STUDIES_AVAILABLE_PERIODS_ENDPOINT',
            '/clinical-studies/available-periods'
        ),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env(
            'AWS_DEFAULT_REGION',
            'us-east-1'
        ),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env(
                'SLACK_BOT_USER_OAUTH_TOKEN'
            ),

            'channel' => env(
                'SLACK_BOT_USER_DEFAULT_CHANNEL'
            ),
        ],
    ],

];