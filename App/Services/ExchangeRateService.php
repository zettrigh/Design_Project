<?php

namespace App\Services;

use App\Models\ExchangeRateModel;
use Core\Result;

class ExchangeRateService
{
    private ?ExchangeRateModel $rateModel = null;
    private const FALLBACK_RATES = [
        'USD' => ['VES' => 45.0],
    ];
    private const CACHE_KEY = 'exchange_rate_';
    private const CACHE_TTL = 3600;
    private const API_URL = 'https://api.exchangerate-api.com/v4/latest/USD';

    public function setRateModel(ExchangeRateModel $model): void
    {
        $this->rateModel = $model;
    }

    public function getExchangeRate(string $from = 'USD', string $to = 'VES', int $userId = 1): float
    {
        $from = strtoupper(trim($from));
        $to   = strtoupper(trim($to));

        if ($this->rateModel) {
            $dbRate = $this->rateModel->getRate($from, $to);
            if ($dbRate !== null) {
                return $dbRate;
            }
        }

        $cached = $this->getFromCache($from, $to);
        if ($cached !== null) {
            return $cached;
        }

        $apiRate = $this->fetchFromApi($from, $to);
        if ($apiRate !== null) {
            $this->saveToCache($from, $to, $apiRate);
            return $apiRate;
        }

        return self::FALLBACK_RATES[$from][$to] ?? 1.0;
    }

    public function getManualRate(string $from = 'USD', string $to = 'VES'): Result
    {
        if (!$this->rateModel) {
            return Result::failure('Modelo de tasa de cambio no configurado.');
        }

        $rate = $this->rateModel->getRate($from, $to);
        return $rate !== null
            ? Result::success(['from' => $from, 'to' => $to, 'rate' => $rate])
            : Result::failure('No se encontró una tasa manual configurada.');
    }

    public function setManualRate(string $from, string $to, float $rate, ?int $updatedBy = null): Result
    {
        if (!$this->rateModel) {
            return Result::failure('Modelo de tasa de cambio no configurado.');
        }
        if ($rate <= 0) {
            return Result::failure('La tasa de cambio debe ser mayor a cero.');
        }

        $saved = $this->rateModel->setRate($from, $to, $rate, $updatedBy);
        return $saved
            ? Result::success(['from' => $from, 'to' => $to, 'rate' => $rate], 'Tasa de cambio actualizada exitosamente.')
            : Result::failure('Error al guardar la tasa de cambio.');
    }

    public function getAllRates(): Result
    {
        if (!$this->rateModel) {
            return Result::failure('Modelo de tasa de cambio no configurado.');
        }

        return Result::success($this->rateModel->getAll());
    }

    private function getFromCache(string $from, string $to): ?float
    {
        $key = self::CACHE_KEY . "{$from}_{$to}";
        return isset($_SESSION[$key]) && (time() - $_SESSION[$key]['time']) < self::CACHE_TTL
            ? (float) $_SESSION[$key]['rate']
            : null;
    }

    private function saveToCache(string $from, string $to, float $rate): void
    {
        $key = self::CACHE_KEY . "{$from}_{$to}";
        $_SESSION[$key] = ['rate' => $rate, 'time' => time()];
    }

    private function fetchFromApi(string $from, string $to): ?float
    {
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $response = @file_get_contents(self::API_URL, false, $ctx);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!isset($data['rates'][$from]) || !isset($data['rates'][$to])) {
            return null;
        }

        $fromRate = (float) $data['rates'][$from];
        $toRate   = (float) $data['rates'][$to];

        if ($fromRate <= 0) {
            return null;
        }

        return $toRate / $fromRate;
    }
}
