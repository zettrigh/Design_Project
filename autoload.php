<?php
/**
 * Autoloader PSR-4
 *
 * Mapea prefijos de namespace a directorios del proyecto.
 * Al registrar una nueva carpeta de clases, solo hay que añadir
 * una entrada al array $prefixes.
 *
 * Mapa actual:
 *   App\       → App/
 *   Config\    → Config/
 *   Core\      → Core/
 */

spl_autoload_register(function (string $class): void {
    // Mapa: prefijo de namespace → directorio base
    $prefixes = [
        'App\\'    => __DIR__ . '/App/',
        'Config\\' => __DIR__ . '/Config/',
        'Core\\'   => __DIR__ . '/Core/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);

        // ¿El FQCN comienza con este prefijo?
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        // Convertir el resto del namespace en ruta de archivo
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});