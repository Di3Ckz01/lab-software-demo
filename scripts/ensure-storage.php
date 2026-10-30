<?php

declare(strict_types=1);

/**
 * Prepara de forma segura los directorios escribibles de Laravel.
 *
 * Composer invoca este script antes de descubrir paquetes, de forma que
 * una copia recien clonada no dependa de carpetas vacias en Git.
 * No crea datos, contrasenas ni enlaces a servicios externos.
 */
$root = dirname(__DIR__);

$directories = [
    'storage/app/private',
    'storage/app/public',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/testing',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
];

foreach ($directories as $relative) {
    $directory = $root . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relative);

    if (is_dir($directory)) {
        continue;
    }

    if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
        fwrite(STDERR, "No se pudo crear el directorio: {$relative}\n");
        exit(1);
    }
}
