<?php

return array (
  'items' => 
  array (
    'pacientes-mes' => 
    array (
      'label' => 'Órdenes por mes',
      'short_label' => 'Órdenes',
      'description' => 'Consulta de órdenes atendidas por mes.',
      'route' => 'dashboards.pacientes-mes',
      'icon' => '📊',
      'admin' => false,
      'section' => 'dashboard',
    ),
    'estudios' => 
    array (
      'label' => 'Estudios',
      'short_label' => 'Estudios',
      'description' => 'Órdenes y estudios por mes, estatus y área.',
      'route' => 'dashboards.estudios',
      'icon' => '🧪',
      'admin' => false,
      'section' => 'dashboard',
    ),
    'consulta-externa' => 
    array (
      'label' => 'Consulta externa',
      'short_label' => 'Consulta externa',
      'description' => 'Rueda de consulta externa con detalle de órdenes y estudios.',
      'route' => 'dashboards.rueda',
      'icon' => '🎯',
      'admin' => false,
      'section' => 'dashboard',
      'default_access' => false,
    ),
    'opportunity-status' => 
    array (
      'label' => 'Tiempo oportunidad',
      'short_label' => 'Tiempo oportunidad',
      'description' => 'Seguimiento operativo por estado, prioridad y tiempo oportunidad.',
      'route' => 'dashboards.opportunity-status',
      'icon' => '🚦',
      'admin' => false,
      'section' => 'dashboard',
    ),
    'productividad' => 
    array (
      'label' => 'Productividad',
      'short_label' => 'Productividad',
      'description' => 'Órdenes y exámenes validados por turno, usuario y área.',
      'route' => 'dashboards.productividad',
      'icon' => '📈',
      'admin' => false,
      'section' => 'dashboard',
    ),
    'venopunciones' => 
    array (
      'label' => 'Venopunciones',
      'short_label' => 'Venopunciones',
      'description' => 'Consulta de venopunciones duplicadas por cama y día.',
      'route' => 'dashboards.venopunciones',
      'icon' => '💉',
      'admin' => false,
      'section' => 'dashboard',
      'default_access' => false,
    ),
    'sample-collection' => 
    array (
      'label' => 'Toma de muestra',
      'short_label' => 'Toma muestra',
      'description' => 'Pacientes atendidos, oportunidad y desempeño por puerta.',
      'route' => 'dashboards.sample-collection',
      'icon' => '🩸',
      'admin' => false,
      'section' => 'dashboard',
    ),
    'tv-dashboard' => 
    array (
      'label' => 'Pantalla de estudios',
      'short_label' => 'Pantalla estudios',
      'description' => 'Pantalla operativa para TV con estados y tiempos de oportunidad.',
      'route' => 'dashboards.tv',
      'icon' => '🖥️',
      'admin' => false,
      'section' => 'screen',
      'default_access' => false,
    ),
    'sample-collection-tv' => 
    array (
      'label' => 'Pantalla de toma de muestra',
      'short_label' => 'Pantalla toma',
      'description' => 'Pantalla operativa para TV con puertas, tiempos y complejidad.',
      'route' => 'dashboards.sample-collection-tv',
      'icon' => '📺',
      'admin' => false,
      'section' => 'screen',
      'default_access' => false,
    ),
    'users' => 
    array (
      'label' => 'Usuarios',
      'short_label' => 'Usuarios',
      'description' => 'Administración de usuarios y permisos por tablero.',
      'route' => 'users.index',
      'icon' => '👥',
      'admin' => true,
      'section' => 'admin',
    ),
    'sample-collection-analytics' => 
    array (
      'label' => 'Analítica de Toma de muestra',
      'short_label' => 'Analítica toma',
      'description' => 'Resumen ejecutivo de volumen, tiempos y comportamiento por rango de fechas.',
      'route' => 'dashboards.sample-collection-analytics',
      'icon' => '📊',
      'admin' => false,
      'section' => 'dashboard',
      'default_access' => false,
    ),
    'supplier-studies' => 
    array (
      'label' => 'Proveedores',
      'short_label' => 'Proveedores',
      'description' => 'Consulta, administración y asignación de estudios por proveedor.',
      'route' => 'dashboards.supplier-studies',
      'icon' => '🧾',
      'admin' => false,
      'section' => 'dashboard',
      'default_access' => false,
    ),
  ),
);
