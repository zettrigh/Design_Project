const BASE_URL = window.BASE_URL || '/HomeWorks/Design_Project';
let currentPaymentReservationId = null;
let currentHairstyleId = null;

// ── Schedule Modal ──
function openScheduleModal(hairstyleId, hairstyleName) {
    currentHairstyleId = hairstyleId;
    document.getElementById('schedule-hairstyle-id').value = hairstyleId;
    document.getElementById('schedule-hairstyle-name').textContent = hairstyleName;
    document.getElementById('schedule-date').value = '';
    document.getElementById('schedule-time').value = '';
    document.getElementById('schedule-worker-id').value = '';
    document.getElementById('schedule-slots-container').classList.add('hidden');
    document.getElementById('schedule-slots').innerHTML = '<p class="text-sm text-[#5C4333]/50 col-span-full text-center py-4">Selecciona una fecha primero.</p>';
    document.getElementById('confirm-schedule-btn').disabled = true;

    const modal = document.getElementById('schedule-modal');
    modal.classList.remove('opacity-0', 'pointer-events-none');
    setTimeout(() => {
        document.getElementById('schedule-box').classList.remove('scale-95');
        document.getElementById('schedule-box').classList.add('scale-100');
    }, 10);
}

function closeScheduleModal() {
    const modal = document.getElementById('schedule-modal');
    document.getElementById('schedule-box').classList.remove('scale-100');
    document.getElementById('schedule-box').classList.add('scale-95');
    modal.classList.add('opacity-0', 'pointer-events-none');
    currentHairstyleId = null;
}

// Date change -> load slots
document.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.getElementById('schedule-date');
    if (dateInput) {
        dateInput.addEventListener('change', loadAvailableSlots);
    }
});

async function loadAvailableSlots() {
    const date = document.getElementById('schedule-date').value;
    const hairstyleId = document.getElementById('schedule-hairstyle-id').value;
    const slotsContainer = document.getElementById('schedule-slots');
    const slotsSection = document.getElementById('schedule-slots-container');

    if (!date || !hairstyleId) return;

    slotsSection.classList.remove('hidden');
    slotsContainer.innerHTML = '<p class="text-sm text-[#5C4333]/50 col-span-full text-center py-4">Buscando horarios disponibles...</p>';
    document.getElementById('confirm-schedule-btn').disabled = true;

    try {
        const formData = new URLSearchParams();
        formData.append('date', date);
        formData.append('hairstyle_id', hairstyleId);

        const response = await fetch(BASE_URL + '/client/available-slots', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });
        const data = await response.json();

        if (!data.success) {
            slotsContainer.innerHTML = `<p class="text-sm text-amber-600 col-span-full text-center py-4">${data.message}</p>`;
            return;
        }

        if (!data.slots_by_worker || data.slots_by_worker.length === 0) {
            slotsContainer.innerHTML = '<p class="text-sm text-[#5C4333]/50 col-span-full text-center py-4">No hay horarios disponibles para esta fecha.</p>';
            return;
        }

        let html = '';
        data.slots_by_worker.forEach(worker => {
            if (worker.slots && worker.slots.length > 0) {
                html += `<div class="col-span-full">
                    <p class="text-xs font-bold text-[#5C4333]/60 uppercase tracking-wider mb-2">
                        ${worker.worker_name ? 'Con: ' + worker.worker_name : 'Sin asignar'}
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">`;
                worker.slots.forEach(slot => {
                    const workerId = worker.worker_id || '';
                    html += `<button type="button" onclick="selectSlot('${slot.time}', '${slot.end_time}', ${workerId}, this)"
                        class="slot-btn px-3 py-2 text-xs font-bold border border-[#EFE5D9] rounded-xl hover:border-[#B56B45] hover:bg-[#B56B45]/5 transition-all text-center cursor-pointer">
                        ${slot.display}
                    </button>`;
                });
                html += `</div></div>`;
            }
        });

        if (!html) {
            html = '<p class="text-sm text-[#5C4333]/50 col-span-full text-center py-4">No hay horarios disponibles.</p>';
        }

        slotsContainer.innerHTML = html;
    } catch (err) {
        slotsContainer.innerHTML = '<p class="text-sm text-red-500 col-span-full text-center py-4">Error al cargar horarios.</p>';
    }
}

function selectSlot(time, endTime, workerId, btn) {
    document.querySelectorAll('.slot-btn').forEach(b => {
        b.classList.remove('border-[#B56B45]', 'bg-[#B56B45]/10', 'text-[#B56B45]');
        b.classList.add('border-[#EFE5D9]');
    });
    btn.classList.remove('border-[#EFE5D9]');
    btn.classList.add('border-[#B56B45]', 'bg-[#B56B45]/10', 'text-[#B56B45]');

    document.getElementById('schedule-time').value = time;
    document.getElementById('schedule-worker-id').value = workerId;
    document.getElementById('confirm-schedule-btn').disabled = false;
}

async function confirmarReservaConCita() {
    const hairstyleId = document.getElementById('schedule-hairstyle-id').value;
    const date = document.getElementById('schedule-date').value;
    const time = document.getElementById('schedule-time').value;
    const workerId = document.getElementById('schedule-worker-id').value;

    if (!hairstyleId || !date || !time) {
        showModal('Campos Incompletos', 'Selecciona una fecha y un horario disponible.', false);
        return;
    }

    const btn = document.getElementById('confirm-schedule-btn');
    btn.disabled = true;
    btn.textContent = 'Reservando...';

    try {
        const formData = new URLSearchParams();
        formData.append('hairstyle_id', hairstyleId);
        formData.append('appointment_date', date);
        formData.append('appointment_time', time);
        if (workerId) formData.append('worker_id', workerId);

        const response = await fetch(BASE_URL + '/client/reserve', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            closeScheduleModal();
            showToast(data.message, true);
            setTimeout(() => { window.location.reload(); }, 1500);
        } else {
            showModal('Error', data.message, false);
            btn.disabled = false;
            btn.textContent = 'Confirmar Cita';
        }
    } catch (err) {
        showModal('Error de Conexión', 'No se pudo procesar la reserva.', false);
        btn.disabled = false;
        btn.textContent = 'Confirmar Cita';
    }
}

// ── Reserve Hairstyle (legacy - reserva sin cita) ──
async function apartarPeinado(id) {
    try {
        const formData = new URLSearchParams();
        formData.append('hairstyle_id', id);

        const response = await fetch(BASE_URL + '/client/reserve', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            showToast(data.message, true);
            setTimeout(() => { window.location.reload(); }, 1500);
        } else {
            if (data.redirect) {
                showModal('Sesión Inválida', data.message, false, () => { window.location.href = data.redirect; });
            } else {
                showModal('Apartado No Disponible', data.message, false);
            }
        }
    } catch (err) {
        showModal('Error', 'No se pudo registrar la reserva. Intenta nuevamente.', false);
    }
}

// ── Payment Flow ──
async function iniciarPago(reservationId, priceUsd, hairstyleName) {
    currentPaymentReservationId = reservationId;

    document.getElementById('pay-hairstyle-name').textContent = hairstyleName;
    document.getElementById('pay-hairstyle-detail').textContent = hairstyleName;
    document.getElementById('pay-amount-usd').textContent = '$' + parseFloat(priceUsd).toFixed(2) + ' USD';

    const modal = document.getElementById('payment-modal');
    modal.classList.remove('opacity-0', 'pointer-events-none');
    setTimeout(() => {
        document.getElementById('payment-box').classList.remove('scale-95');
        document.getElementById('payment-box').classList.add('scale-100');
    }, 10);
}

function closePaymentModal() {
    const modal = document.getElementById('payment-modal');
    document.getElementById('payment-box').classList.remove('scale-100');
    document.getElementById('payment-box').classList.add('scale-95');
    modal.classList.add('opacity-0', 'pointer-events-none');
    currentPaymentReservationId = null;
}

async function confirmarPago() {
    if (!currentPaymentReservationId) return;

    const btn = document.getElementById('confirm-pay-btn');
    btn.disabled = true;
    btn.textContent = 'Procesando...';

    try {
        const formData = new URLSearchParams();
        formData.append('reservation_id', currentPaymentReservationId);
        formData.append('payment_method_id', 'pm_card_visa');

        const response = await fetch(BASE_URL + '/client/process-payment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            closePaymentModal();
            showToast(data.message, true);
            setTimeout(() => { window.location.reload(); }, 1500);
        } else {
            showModal('Error de Pago', data.message, false);
            btn.disabled = false;
            btn.textContent = 'Confirmar Pago';
        }
    } catch {
        showModal('Error de Conexión', 'No se pudo procesar el pago.', false);
        btn.disabled = false;
        btn.textContent = 'Confirmar Pago';
    }
}
