const BASE_URL = window.BASE_URL || '/HomeWorks/Design_Project';
let currentPaymentReservationId = null;

// ── Reserve Hairstyle ──
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

    // Show modal
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
