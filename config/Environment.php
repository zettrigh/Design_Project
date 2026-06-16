<?php

namespace Config;

/**
 * Config\Environment
 *
 * Carga y gestiona las variables de entorno del archivo `.env`.
 * Implementa acceso estático para que cualquier componente de la
 * aplicación pueda leer la configuración sin inyección explícita.
 *
 * Patrón de diseño: Singleton (implícito vía métodos estáticos).
 *
 * Ejemplo de uso:
 *   $baseUrl = Environment::get('BASE_URL');
 *   $dbHost  = Environment::get('DB_HOST', 'localhost');
 *
 * @package Config
 * @author  MiMundoTrenzas Team
 */
class Environment
{
    /**
     * Mapa de variables de entorno cargadas desde .env.
     *
     * @var array<string, string>
     */
    private static array $variables = [];

    /**
     * Indica si el archivo .env ya fue procesado.
     *
     * @var bool
     */
    private static bool $loaded = false;

    /**
     * Ruta al archivo .env en el sistema de archivos.
     *
     * @var string
     */
    private static string $envPath = '';

    /**
     * Carga el archivo .env y almacena las variables en memoria.
     *
     * Si el archivo no existe o ya fue cargado, retorna silenciosamente.
     * Las líneas vacías, comentarios (que empiezan con #) y líneas
     * sin signo '=' se ignoran.
     *
     * @param string $path Ruta al archivo .env. Si se omite, busca en la raíz del proyecto.
     * @return void
     */
    public static function load(string $path = ''): void
    {
        if (self::$loaded) {
            return;
        }

        if (empty($path)) {
            $path = dirname(__DIR__) . '/.env';
        }

        self::$envPath = $path;

        if (!file_exists($path)) {
            error_log("[Environment] Archivo .env no encontrado en: {$path}");
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Ignorar comentarios y líneas vacías
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            // Buscar la primera posición de '='
            $equalsPos = strpos($line, '=');
            if ($equalsPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $equalsPos));
            $value = trim(substr($line, $equalsPos + 1));

            // Eliminar comillas envolventes (simples o dobles)
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // Sobrescribir variables de entorno del sistema si existen en .env
            self::$variables[$key] = $value;
        }

        self::$loaded = true;
    }

    /**
     * Obtiene el valor de una variable de entorno.
     *
     * Busca primero en el archivo .env cargado, luego en las
     * variables de entorno del sistema ($_ENV / getenv).
     *
     * @param string     $key     Nombre de la variable de entorno.
     * @param string|int|float|bool $default Valor por defecto si la variable no existe.
     * @return string|int|float|bool
     */
    public static function get(string $key, string|int|float|bool $default = ''): string|int|float|bool
    {
        // Cargar .env si no se ha hecho aún
        if (!self::$loaded) {
            self::load();
        }

        // Buscar en variables cargadas de .env
        if (array_key_exists($key, self::$variables)) {
            return self::castValue(self::$variables[$key]);
        }

        // Buscar en variables de entorno del sistema
        $envValue = getenv($key);
        if ($envValue !== false) {
            return self::castValue($envValue);
        }

        return $default;
    }

    /**
     * Verifica si una variable de entorno está definida.
     *
     * @param string $key Nombre de la variable.
     * @return bool
     */
    public static function has(string $key): bool
    {
        if (!self::$loaded) {
            self::load();
        }

        return array_key_exists($key, self::$variables) || getenv($key) !== false;
    }

    /**
     * Obtiene todas las variables de entorno cargadas.
     *
     * Útil para debugging en modo desarrollo.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$variables;
    }

    /**
     * Realiza un cast automático de valores string a su tipo nativo.
     *
     * Convierte "true"/"false" a bool, valores numéricos a int/float,
     * y deja el resto como string.
     *
     * @param string $value Valor raw del .env.
     * @return string|int|float|bool
     */
    private static function castValue(string $value): string|int|float|bool
    {
        $lower = strtolower($value);

        if ($lower === 'true' || $lower === '1') {
            return true;
        }

        if ($lower === 'false' || $lower === '0') {
            return false;
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }
}
