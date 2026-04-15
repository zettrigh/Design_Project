<?php
/*Uso __DIR__ para evitar errores de ruta y validar existencia de archivos*/
function autoload($className) {
    // Definimos las carpetas donde queremos buscar
    $directories = [
        __DIR__ . '/models/',
        __DIR__ . '/config/',
    ];

    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        
        // Verificamos si el archivo existe antes de intentar cargarlo
        if (file_exists($file)) {
            require_once $file;
            return; // Salimos de la función si ya lo encontramos
        }
    }
}

spl_autoload_register('autoload');
?>