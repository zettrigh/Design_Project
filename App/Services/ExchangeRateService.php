<?php

namespace App\Services;

/**
 * App\Services\ExchangeRateService
 *
 * Consumo de API externa para obtención de tipos de cambio en tiempo real.
 * Utiliza ExchangeRate-API (https://www.exchangerate-api.com/) u otra API
 * compatible con el mismo formato de respuesta.
 *
 * Responsabilidades:
 *   - Consultar la tasa de cambio entre dos monedas.
 *   - Convertir un monto de una moneda a otra.
 *   - Manejar errores de conexión y respuestas inválidas.
 *
 * Principios aplicados:
 *   - SRP: Solo maneja conversión de divisas vía API externa.
 *   - OCP: Extensible para soportar otras APIs de cambio sin modificar código existente.
 *
 * @package App\Services
 */
class ExchangeRateService
{
    /**
     * URL base de la API de ExchangeRate.
     *
     * @var string
     */
    private string $apiUrl;

    /**
     * Clave de API para autenticación.
     *
     * @var string
     */
    private string $apiKey;

    /**
     * Tiempo de caché en segundos para las tasas de cambio (5 minutos).
     *
     * @var int
     */
    private int $cacheTtl = 300;

    /**
     * Constructor con configuración desde variables de entorno.
     *
     * @param string|null $apiUrl URL base de la API. Si es null, se lee de .env.
     * @param string|null $apiKey Clave de API. Si es null, se lee de .env.
     */
    public function __construct(?string $apiUrl = null, ?string $apiKey = null)
    {
        $env = \Config\Environment::all();
        $this->apiUrl = $apiUrl ?? ($env['EXCHANGE_RATE_API_URL'] ?? 'https://v6.exchangerate-api.com/v6');
        $this->apiKey = $apiKey ?? ($env['EXCHANGE_RATE_API_KEY'] ?? '');
    }

    /**
     * Obtiene la tasa de cambio entre dos monedas.
     *
     * Consulta la API externa y retorna la tasa de conversión.
     * Implementa caché simple en sesión para evitar llamadas excesivas.
     *
     * @param string $from Moneda origen (ISO 4217, ej: 'MXN').
     * @param string $to   Moneda destino (ISO 4217, ej: 'USD').
     * @return array{success: bool, rate?: float, message?: string, cached?: bool}
     */
    public function getExchangeRate(string $from, string $to): array
    {
        $from = strtoupper(trim($from));
        $to   = strtoupper(trim($to));

        if ($from === $to) {
            return ['success' => true, 'rate' => 1.0, 'message' => 'Misma moneda', 'cached' => false];
        }

        // Verificar caché en sesión
        $cacheKey = "exchange_rate_{$from}_{$to}";
        if (isset($_SESSION[$cacheKey]) && isset($_SESSION[$cacheKey . '_time'])) {
            if ((time() - $_SESSION[$cacheKey . '_time']) < $this->cacheTtl) {
                return [
                    'success' => true,
                    'rate'    => $_SESSION[$cacheKey],
                    'message' => 'Tasa obtenida desde caché.',
                    'cached'  => true,
                ];
            }
        }

        // Si no hay API key configurada, usar tasa de respaldo
        if (empty($this->apiKey)) {
            $fallbackRate = $this->getFallbackRate($from, $to);
            if ($fallbackRate !== null) {
                return [
                    'success' => true,
                    'rate'    => $fallbackRate,
                    'message' => 'Tasa de respaldo (API no configurada).',
                    'cached'  => false,
                ];
            }
            return [
                'success' => false,
                'message' => 'API key de tipo de cambio no configurada. Configure EXCHANGE_RATE_API_KEY en .env',
            ];
        }

        // Consultar API externa
        $url = "{$this->apiUrl}/{$this->apiKey}/pair/{$from}/{$to}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error) {
            // Intentar tasa de respaldo en caso de error de conexión
            $fallbackRate = $this->getFallbackRate($from, $to);
            if ($fallbackRate !== null) {
                return [
                    'success' => true,
                    'rate'    => $fallbackRate,
                    'message' => 'Tasa de respaldo (error de conexión con API).',
                    'cached'  => false,
                ];
            }
            return [
                'success' => false,
                'message' => 'Error de conexión con la API de tipo de cambio: ' . $error,
            ];
        }

        if ($httpCode !== 200) {
            $fallbackRate = $this->getFallbackRate($from, $to);
            if ($fallbackRate !== null) {
                return [
                    'success' => true,
                    'rate'    => $fallbackRate,
                    'message' => 'Tasa de respaldo (API retornó código ' . $httpCode . ').',
                    'cached'  => false,
                ];
            }
            return [
                'success' => false,
                'message' => 'La API de tipo de cambio retornó el código HTTP: ' . $httpCode,
            ];
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Respuesta inválida de la API de tipo de cambio.'];
        }

        // Formato esperado de ExchangeRate-API v6
        if (isset($data['result']) && $data['result'] === 'success' && isset($data['conversion_rate'])) {
            $rate = (float) $data['conversion_rate'];

            // Guardar en caché
            $_SESSION[$cacheKey]      = $rate;
            $_SESSION[$cacheKey . '_time'] = time();

            return [
                'success' => true,
                'rate'    => $rate,
                'message' => "Tasa {$from}/{$to}: {$rate}",
                'cached'  => false,
            ];
        }

        return [
            'success' => false,
            'message' => 'Formato de respuesta inesperado de la API de tipo de cambio.',
        ];
    }

    /**
     * Convierte un monto de una moneda a otra.
     *
     * @param float  $amount Monto a convertir.
     * @param string $from   Moneda origen.
     * @param string $to     Moneda destino.
     * @return array{success: bool, original_amount?: float, converted_amount?: float, rate?: float, message?: string}
     */
    public function convert(float $amount, string $from, string $to): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'El monto debe ser mayor a cero.'];
        }

        $rateResult = $this->getExchangeRate($from, $to);

        if (!$rateResult['success']) {
            return $rateResult;
        }

        $rate            = $rateResult['rate'];
        $convertedAmount = round($amount * $rate, 2);

        return [
            'success'          => true,
            'original_amount'  => $amount,
            'converted_amount' => $convertedAmount,
            'rate'             => $rate,
            'message'          => "{$amount} {$from} = {$convertedAmount} {$to}",
        ];
    }

    /**
     * Tasas de respaldo hardcodeadas para cuando la API no está disponible.
     * Estas tasas se actualizan periódicamente en releases del proyecto.
     *
     * @param string $from Moneda origen.
     * @param string $to   Moneda destino.
     * @return float|null Tasa de cambio o null si no hay respaldo.
     */
    private function getFallbackRate(string $from, string $to): ?float
    {
        // Tasas de referencia (actualizar periódicamente)
        $rates = [
            'MXN_USD' => 0.058,
            'USD_MXN' => 17.20,
            'EUR_USD' => 1.08,
            'USD_EUR' => 0.93,
            'MXN_EUR' => 0.054,
            'EUR_MXN' => 18.58,
        ];

        $key = "{$from}_{$to}";
        return $rates[$key] ?? null;
    }
}
