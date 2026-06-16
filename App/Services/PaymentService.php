<?php

namespace App\Services;

use App\Models\PaymentModel;
use App\Models\ReservationModel;
use App\Models\HairstyleModel;

/**
 * App\Services\PaymentService
 *
 * Capa de servicio para el procesamiento de pagos e integración con pasarela
 * de pagos (Stripe). Todos los precios se manejan en USD.
 *
 * Flujo de pago:
 *   1. Cliente selecciona un peinado (precios en USD).
 *   2. El cliente confirma el pago.
 *   3. Se procesa el pago a través de la pasarela (Stripe).
 *   4. Se registra el pago en la base de datos.
 *   5. Se actualiza el estado de la reserva.
 *
 * Principios aplicados:
 *   - SRP: Solo maneja lógica de pagos.
 *   - DIP: Depende de modelos inyectados.
 *   - DRY: Centraliza lógica de pago.
 *
 * @package App\Services
 */
class PaymentService
{
    /**
     * @var PaymentModel Modelo de acceso a datos de pagos.
     */
    private PaymentModel $paymentModel;

    /**
     * @var ReservationModel Modelo de acceso a datos de reservas.
     */
    private ReservationModel $reservationModel;

    /**
     * @var HairstyleModel Modelo de acceso a datos de peinados.
     */
    private HairstyleModel $hairstyleModel;

    /**
     * Clave secreta de Stripe para el servidor.
     *
     * @var string
     */
    private string $stripeSecretKey;

    /**
     * Constructor con inyección de dependencias.
     *
     * @param PaymentModel     $paymentModel     Modelo de pagos.
     * @param ReservationModel $reservationModel Modelo de reservas.
     * @param HairstyleModel   $hairstyleModel   Modelo de peinados.
     */
    public function __construct(
        PaymentModel $paymentModel,
        ReservationModel $reservationModel,
        HairstyleModel $hairstyleModel
    ) {
        $this->paymentModel        = $paymentModel;
        $this->reservationModel    = $reservationModel;
        $this->hairstyleModel      = $hairstyleModel;

        $env = \Config\Environment::all();
        $this->stripeSecretKey = $env['STRIPE_SECRET_KEY'] ?? '';
    }

    /**
     * Obtiene el precio de un peinado en USD.
     *
     * Los precios se almacenan directamente en USD.
     *
     * @param int $hairstyleId ID del peinado.
     * @return array{success: bool, hairstyle_name?: string, price_usd?: float, message?: string}
     */
    public function getPriceUSD(int $hairstyleId): array
    {
        $hairstyle = $this->hairstyleModel->getHairstyleById($hairstyleId);

        if (!$hairstyle || $hairstyle['status'] !== 'active') {
            return ['success' => false, 'message' => 'Peinado no encontrado o no disponible.'];
        }

        $priceUsd = (float) $hairstyle['price'];

        return [
            'success'        => true,
            'hairstyle_name' => $hairstyle['name'],
            'price_usd'      => $priceUsd,
            'message'        => "Precio: \${$priceUsd} USD",
        ];
    }

    /**
     * Procesa un pago a través de la pasarela Stripe.
     *
     * Flujo completo:
     *   1. Valida la reserva y el peinado.
     *   2. Obtiene el precio en USD.
     *   3. Crea un PaymentIntent en Stripe.
     *   4. Registra el pago en la BD.
     *   5. Actualiza el estado de la reserva a 'confirmed'.
     *
     * @param int    $reservationId  ID de la reserva a pagar.
     * @param string $paymentMethodId ID del método de pago de Stripe (token).
     * @return array{success: bool, message: string, payment_id?: int, amount_usd?: float}
     */
    public function processPayment(int $reservationId, string $paymentMethodId): array
    {
        // 1. Validar la reserva
        $reservation = $this->reservationModel->getReservationById($reservationId);

        if (!$reservation) {
            return ['success' => false, 'message' => 'La reserva no existe.'];
        }

        if ($reservation['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Esta reserva no está pendiente de pago.'];
        }

        // 2. Obtener el precio en USD directamente
        $hairstyleId = (int) $reservation['hairstyle_id'];
        $priceResult = $this->getPriceUSD($hairstyleId);

        if (!$priceResult['success']) {
            return ['success' => false, 'message' => $priceResult['message']];
        }

        $amountUsd = $priceResult['price_usd'];

        // 3. Procesar pago con Stripe
        $stripeResult = $this->processStripePayment($paymentMethodId, $amountUsd, $priceResult['hairstyle_name']);

        if (!$stripeResult['success']) {
            return ['success' => false, 'message' => 'Error al procesar el pago: ' . $stripeResult['message']];
        }

        // 4. Registrar el pago en la BD (todo en USD)
        $paymentId = $this->paymentModel->createPayment([
            'reservation_id'   => $reservationId,
            'user_id'          => $reservation['user_id'],
            'amount'           => $amountUsd,
            'currency'         => 'USD',
            'exchange_rate'    => 1.0,
            'amount_usd'       => $amountUsd,
            'payment_method'   => 'stripe',
            'transaction_id'   => $stripeResult['transaction_id'] ?? '',
            'status'           => 'completed',
        ]);

        if (!$paymentId) {
            return ['success' => false, 'message' => 'Error al registrar el pago en la base de datos.'];
        }

        // 5. Actualizar estado de la reserva
        $this->reservationModel->updateReservationStatus($reservationId, 'confirmed');

        return [
            'success'    => true,
            'message'    => 'Pago procesado exitosamente. Tu reserva ha sido confirmada.',
            'payment_id' => $paymentId,
            'amount_usd' => $amountUsd,
        ];
    }

    /**
     * Procesa un pago a través de la API de Stripe.
     *
     * Crea un PaymentIntent y confirma el pago con el método de pago proporcionado.
     *
     * @param string $paymentMethodId ID del método de pago de Stripe.
     * @param float  $amount          Monto en USD a cobrar.
     * @param string $description     Descripción del pago.
     * @return array{success: bool, transaction_id?: string, message?: string}
     */
    private function processStripePayment(string $paymentMethodId, float $amount, string $description): array
    {
        // Si no hay API key configurada, simular el pago (modo desarrollo)
        if (empty($this->stripeSecretKey) || str_starts_with($this->stripeSecretKey, 'sk_test_YOUR')) {
            $simulatedId = 'sim_' . bin2hex(random_bytes(16));
            return [
                'success'        => true,
                'transaction_id' => $simulatedId,
                'message'        => 'Pago simulado en modo desarrollo.',
            ];
        }

        // Llamada real a la API de Stripe
        $amountCents = (int) round($amount * 100); // Stripe usa centavos

        $postData = http_build_query([
            'amount'               => $amountCents,
            'currency'             => 'usd',
            'payment_method'       => $paymentMethodId,
            'description'          => $description,
            'confirm'              => 'true',
            'automatic_payment_methods' => json_encode([
                'enabled'             => true,
                'allow_redirects'     => 'never',
            ]),
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.stripe.com/v1/payment_intents',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->stripeSecretKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error) {
            return ['success' => false, 'message' => 'Error de conexión con Stripe: ' . $error];
        }

        $data = json_decode($response, true);

        if ($httpCode === 200 && isset($data['id']) && $data['status'] === 'succeeded') {
            return [
                'success'        => true,
                'transaction_id' => $data['id'],
                'message'        => 'Pago procesado exitosamente por Stripe.',
            ];
        }

        $errorMessage = $data['error']['message'] ?? 'Error desconocido de Stripe.';
        return ['success' => false, 'message' => $errorMessage];
    }

    /**
     * Obtiene el historial de pagos de un usuario.
     *
     * @param int $userId ID del usuario.
     * @return array<int, array> Lista de pagos del usuario.
     */
    public function getUserPayments(int $userId): array
    {
        return $this->paymentModel->getPaymentsByUserId($userId);
    }

    /**
     * Obtiene el historial completo de pagos (para admin).
     *
     * @return array<int, array> Lista de todos los pagos.
     */
    public function getAllPayments(): array
    {
        return $this->paymentModel->getAllPayments();
    }
}
