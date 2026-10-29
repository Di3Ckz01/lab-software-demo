<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Manual de ayuda contextual
    |--------------------------------------------------------------------------
    |
    | El PDF se publica desde /public/manuales y cada ruta abre automáticamente
    | la página donde comienza la explicación de esa pantalla.
    |
    | Las pantallas TV NO están incluidas intencionalmente.
    |
    */

    'manual' => [
        'path' => 'manuales/manual-usuario-vcare.pdf',
        'label' => 'Manual de usuario V-Care',
    ],

    'routes' => [
        'login' => [
            'title' => 'Inicio de sesión',
            'page' => 3,
            'page_end' => 4,
        ],

        'dashboards.home' => [
            'title' => 'Dashboard principal',
            'page' => 5,
            'page_end' => 8,
        ],

        'dashboards.pacientes-mes' => [
            'title' => 'Órdenes atendidas por mes',
            'page' => 9,
            'page_end' => 11,
        ],

        'dashboards.estudios' => [
            'title' => 'Tablero de estudios',
            'page' => 12,
            'page_end' => 15,
        ],

        'dashboards.rueda' => [
            'title' => 'Consulta externa',
            'page' => 16,
            'page_end' => 19,
        ],

        'dashboards.opportunity-status' => [
            'title' => 'Tiempo de oportunidad',
            'page' => 20,
            'page_end' => 21,
        ],

        'dashboards.productividad' => [
            'title' => 'Productividad',
            'page' => 23,
            'page_end' => 27,
        ],

        'dashboards.venopunciones' => [
            'title' => 'Venopunciones',
            'page' => 28,
            'page_end' => 30,
        ],

        'dashboards.sample-collection' => [
            'title' => 'Toma de muestra',
            'page' => 7,
            'page_end' => 11,
            'manual_path' => 'manuales/manual-usuario-vcare-actualizado.pdf',
            'manual_label' => 'Manual actualizado de V-Care',
        ],

        'dashboards.supplier-studies' => [
            'title' => 'Auditoría de exámenes por proveedores',
            'page' => 12,
            'page_end' => 17,
            'manual_path' => 'manuales/manual-usuario-vcare-actualizado.pdf',
            'manual_label' => 'Manual actualizado de V-Care',
        ],

        'users.index' => [
            'title' => 'Usuarios y permisos',
            'page' => 37,
            'page_end' => 38,
        ],

        'users.create' => [
            'title' => 'Crear usuario',
            'page' => 39,
            'page_end' => 40,
        ],

        'users.edit' => [
            'title' => 'Editar usuario',
            'page' => 41,
            'page_end' => 42,
        ],
    ],
];
