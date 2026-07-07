<?php

namespace App\Services;

use App\Models\BusinessHoursModel;
use App\Models\WorkerScheduleModel;
use App\Models\ReservationModel;
use App\Models\HairstyleModel;
use Core\Result;

class ScheduleService
{
    private BusinessHoursModel $businessHoursModel;
    private WorkerScheduleModel $workerScheduleModel;
    private ReservationModel $reservationModel;
    private HairstyleModel $hairstyleModel;

    private const SLOT_INTERVAL = 30;
    private const DAY_NAMES = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    public function __construct(
        BusinessHoursModel $businessHoursModel,
        WorkerScheduleModel $workerScheduleModel,
        ReservationModel $reservationModel,
        HairstyleModel $hairstyleModel
    ) {
        $this->businessHoursModel = $businessHoursModel;
        $this->workerScheduleModel = $workerScheduleModel;
        $this->reservationModel = $reservationModel;
        $this->hairstyleModel = $hairstyleModel;
    }

    // ── Horarios de atención ──

    public function getBusinessHours(): array
    {
        return $this->businessHoursModel->getAll();
    }

    public function updateBusinessHours(array $hoursData): Result
    {
        foreach ($hoursData as $day => $data) {
            $dayOfWeek = (int) $day;
            $openTime = preg_replace('/[^0-9:]/', '', $data['open_time'] ?? '09:00');
            $closeTime = preg_replace('/[^0-9:]/', '', $data['close_time'] ?? '18:00');
            $isActive = !empty($data['is_active']);

            if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $openTime)) {
                $openTime = '09:00:00';
            }
            if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $closeTime)) {
                $closeTime = '18:00:00';
            }
            if (strlen($openTime) === 5) $openTime .= ':00';
            if (strlen($closeTime) === 5) $closeTime .= ':00';

            if ($dayOfWeek < 0 || $dayOfWeek > 6) continue;

            if (!$this->businessHoursModel->upsert($dayOfWeek, $openTime, $closeTime, $isActive)) {
                return Result::failure('Error al guardar horario para ' . self::DAY_NAMES[$dayOfWeek]);
            }
        }
        return Result::success(null, 'Horarios de atención actualizados correctamente.');
    }

    // ── Horarios del trabajador ──

    public function getWorkerSchedule(int $workerId): array
    {
        return $this->workerScheduleModel->getByWorker($workerId);
    }

    public function updateWorkerSchedule(int $workerId, array $scheduleData): Result
    {
        foreach ($scheduleData as $day => $data) {
            $dayOfWeek = (int) $day;
            $startTime = preg_replace('/[^0-9:]/', '', $data['start_time'] ?? '09:00');
            $endTime = preg_replace('/[^0-9:]/', '', $data['end_time'] ?? '18:00');
            $isActive = !empty($data['is_active']);

            if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $startTime)) {
                $startTime = '09:00:00';
            }
            if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $endTime)) {
                $endTime = '18:00:00';
            }
            if (strlen($startTime) === 5) $startTime .= ':00';
            if (strlen($endTime) === 5) $endTime .= ':00';

            if ($dayOfWeek < 0 || $dayOfWeek > 6) continue;

            if (!$this->workerScheduleModel->upsert($workerId, $dayOfWeek, $startTime, $endTime, $isActive)) {
                return Result::failure('Error al guardar horario del trabajador.');
            }
        }
        return Result::success(null, 'Tu disponibilidad se actualizó correctamente.');
    }

    // ── Generación de slots disponibles ──

    public function getAvailableSlots(string $date, int $hairstyleId, ?int $workerId = null): array
    {
        $dayOfWeek = (int) date('w', strtotime($date));
        $today = date('Y-m-d');
        $now = date('H:i:s');

        $hairstyle = $this->hairstyleModel->getHairstyleById($hairstyleId);
        if (!$hairstyle) {
            return ['success' => false, 'message' => 'Peinado no encontrado.'];
        }
        $duration = (int) ($hairstyle['duration_minutes'] ?? 60);

        $businessHours = $this->businessHoursModel->getActiveHoursByDay($dayOfWeek);
        if (!$businessHours) {
            return ['success' => false, 'message' => 'El negocio no atiende este día.'];
        }

        $openTime = $businessHours['open_time'];
        $closeTime = $businessHours['close_time'];

        $workers = [];
        if ($workerId !== null) {
            $schedule = $this->workerScheduleModel->getActiveScheduleByWorkerAndDay($workerId, $dayOfWeek);
            if ($schedule) {
                $effectiveStart = max($openTime, $schedule['start_time']);
                $effectiveEnd = min($closeTime, $schedule['end_time']);
                if ($effectiveStart < $effectiveEnd) {
                    $workers[] = ['id' => $workerId, 'start_time' => $effectiveStart, 'end_time' => $effectiveEnd];
                }
            }
        } else {
            $allWorkers = $this->workerScheduleModel->getAllActiveWorkers();
            foreach ($allWorkers as $w) {
                $schedule = $this->workerScheduleModel->getActiveScheduleByWorkerAndDay((int)$w['id'], $dayOfWeek);
                if ($schedule) {
                    $effectiveStart = max($openTime, $schedule['start_time']);
                    $effectiveEnd = min($closeTime, $schedule['end_time']);
                    if ($effectiveStart < $effectiveEnd) {
                        $workers[] = [
                            'id' => (int)$w['id'],
                            'username' => $w['username'],
                            'start_time' => $effectiveStart,
                            'end_time' => $effectiveEnd,
                        ];
                    }
                }
            }
            if (empty($workers)) {
                $workers[] = ['id' => null, 'username' => 'Sin asignar', 'start_time' => $openTime, 'end_time' => $closeTime];
            }
        }

        if (empty($workers)) {
            return ['success' => false, 'message' => 'No hay trabajadores disponibles para esta fecha.'];
        }

        $slotsByWorker = [];
        foreach ($workers as $worker) {
            $slots = $this->generateTimeSlots(
                $worker['start_time'],
                $worker['end_time'],
                $duration,
                $date,
                $today,
                $now,
                $worker['id']
            );
            if (!empty($slots)) {
                $slotsByWorker[] = [
                    'worker_id' => $worker['id'],
                    'worker_name' => $worker['username'] ?? 'Sin asignar',
                    'slots' => $slots,
                ];
            }
        }

        return [
            'success' => true,
            'slots_by_worker' => $slotsByWorker,
            'duration_minutes' => $duration,
            'hairstyle_name' => $hairstyle['name'],
            'hairstyle_price' => $hairstyle['price'],
        ];
    }

    private function generateTimeSlots(
        string $startTime,
        string $endTime,
        int $duration,
        string $date,
        string $today,
        string $now,
        ?int $workerId
    ): array {
        $slots = [];
        $start = strtotime($startTime);
        $end = strtotime($endTime);
        $interval = self::SLOT_INTERVAL * 60;

        for ($time = $start; $time + ($duration * 60) <= $end; $time += $interval) {
            $slotStart = date('H:i:s', $time);
            $slotEnd = date('H:i:s', $time + ($duration * 60));

            if ($date === $today && $time <= strtotime($now)) {
                continue;
            }

            if ($this->reservationModel->hasTimeConflict($date, $slotStart, $slotEnd, $workerId)) {
                continue;
            }

            $slots[] = [
                'time' => $slotStart,
                'end_time' => $slotEnd,
                'display' => date('g:i A', $time) . ' - ' . date('g:i A', $time + ($duration * 60)),
            ];
        }

        return $slots;
    }

    // ── Gestión de citas ──

    public function reserveWithSchedule(int $userId, int $hairstyleId, string $date, string $time, ?int $workerId = null): Result
    {
        if ($hairstyleId <= 0) {
            return Result::failure('Identificador de peinado inválido.');
        }

        $hairstyle = $this->hairstyleModel->getHairstyleById($hairstyleId);
        if (!$hairstyle || $hairstyle['status'] !== 'active') {
            return Result::failure('El peinado seleccionado no está disponible.');
        }

        $duration = (int) ($hairstyle['duration_minutes'] ?? 60);
        $startTime = date('H:i:s', strtotime($time));
        $endTime = date('H:i:s', strtotime($time) + ($duration * 60));

        $dateToday = date('Y-m-d');
        $timeNow = date('H:i:s');
        if ($date < $dateToday || ($date === $dateToday && $startTime <= $timeNow)) {
            return Result::failure('La cita debe ser en el futuro.');
        }

        $dayOfWeek = (int) date('w', strtotime($date));
        $businessHours = $this->businessHoursModel->getActiveHoursByDay($dayOfWeek);
        if (!$businessHours) {
            return Result::failure('El negocio no atiende este día.');
        }
        if ($startTime < $businessHours['open_time'] || $endTime > $businessHours['close_time']) {
            return Result::failure('El horario seleccionado está fuera del horario de atención.');
        }

        if ($workerId !== null) {
            $workerSchedule = $this->workerScheduleModel->getActiveScheduleByWorkerAndDay($workerId, $dayOfWeek);
            if (!$workerSchedule || $startTime < $workerSchedule['start_time'] || $endTime > $workerSchedule['end_time']) {
                return Result::failure('El trabajador no está disponible en este horario.');
            }
        }

        if ($this->reservationModel->hasTimeConflict($date, $startTime, $endTime, $workerId)) {
            return Result::failure('Este horario ya está ocupado. Por favor selecciona otro.');
        }

        $existingReservations = $this->reservationModel->getUserReservations($userId);
        foreach ($existingReservations as $res) {
            if ($res['hairstyle_name'] === $hairstyle['name'] && $res['status'] !== 'cancelled') {
                return Result::failure('Ya tienes una reserva activa para este peinado.');
            }
        }

        $reservationId = $this->reservationModel->createReservation($userId, $hairstyleId, $workerId, $date, $startTime, $endTime);

        return Result::success(
            ['reservation_id' => $reservationId],
            '¡Cita agendada con éxito! Recibirás la confirmación pronto.'
        );
    }

    public function getScheduleOverview(string $date): array
    {
        $reservations = $this->reservationModel->getReservationsByDate($date);
        $dayOfWeek = (int) date('w', strtotime($date));
        $businessHours = $this->businessHoursModel->getActiveHoursByDay($dayOfWeek);

        return [
            'date' => $date,
            'day_name' => self::DAY_NAMES[$dayOfWeek],
            'business_hours' => $businessHours,
            'reservations' => $reservations,
            'total' => count($reservations),
        ];
    }
}
