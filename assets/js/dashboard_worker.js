const BASE_URL = window.BASE_URL || '/HomeWorks/Design_Project';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('hairstyle-form');
    const formTitle = document.getElementById('form-title');
    const formDesc = document.getElementById('form-desc');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const cancelBtn = document.getElementById('cancel-btn');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const isEdit = document.getElementById('hairstyle-id').value !== '';
        const endpoint = isEdit ? BASE_URL + '/worker/hairstyles/update' : BASE_URL + '/worker/hairstyles/store';

        submitBtn.disabled = true;
        btnText.textContent = "Procesando...";

        try {
            const formData = new URLSearchParams(new FormData(form));
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                setTimeout(() => { window.location.reload(); }, 1200);
            } else {
                if (data.redirect) {
                    showModal('Sesión Inválida', data.message, false, () => { window.location.href = data.redirect; });
                } else {
                    showModal('Error', data.message, false);
                    submitBtn.disabled = false;
                    btnText.textContent = isEdit ? "Guardar Cambios" : "Publicar Peinado";
                }
            }
        } catch (err) {
            showModal('Error de Conexión', 'No se pudo comunicar con el servidor.', false);
            submitBtn.disabled = false;
            btnText.textContent = isEdit ? "Guardar Cambios" : "Publicar Peinado";
        }
    });

    cancelBtn.addEventListener('click', () => {
        form.reset();
        document.getElementById('hairstyle-id').value = '';
        formTitle.textContent = "Agregar Nuevo Peinado";
        formDesc.textContent = "Completa los campos para publicar un peinado";
        btnText.textContent = "Publicar Peinado";
        cancelBtn.classList.add('hidden');
    });

    loadWorkerSchedule();
});

function iniciarEdicion(style) {
    document.getElementById('hairstyle-id').value = style.id;
    document.getElementById('name').value = style.name;
    document.getElementById('description').value = style.description;
    document.getElementById('price').value = style.price;
    document.getElementById('duration_minutes').value = style.duration_minutes || 60;
    document.getElementById('status').value = style.status;
    document.getElementById('image_url').value = style.image_url;
    document.getElementById('form-title').textContent = "Editar Peinado";
    document.getElementById('form-desc').textContent = `Modificando "${style.name}"`;
    document.getElementById('btn-text').textContent = "Guardar Cambios";
    document.getElementById('cancel-btn').classList.remove('hidden');
    document.getElementById('hairstyle-form').scrollIntoView({ behavior: 'smooth' });
}

function eliminarPeinado(id) {
    showModal('¿Estás seguro?', 'Esta acción eliminará el peinado permanentemente.', false, async () => {
        try {
            const formData = new URLSearchParams();
            formData.append('id', id);
            const response = await fetch(BASE_URL + '/worker/hairstyles/delete', {
                method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData
            });
            const data = await response.json();
            if (data.success) { showToast(data.message, true); setTimeout(() => { window.location.reload(); }, 1200); }
            else { showModal('Error', data.message, false); }
        } catch (err) { showModal('Error', 'No se pudo procesar la eliminación.', false); }
    });
}

async function cambiarEstadoReserva(id, status) {
    try {
        const formData = new URLSearchParams();
        formData.append('id', id);
        formData.append('status', status);
        const response = await fetch(BASE_URL + '/worker/reservations/update', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData
        });
        const data = await response.json();
        if (data.success) { showToast(data.message, true); setTimeout(() => { window.location.reload(); }, 1200); }
        else { showModal('Error', data.message, false); }
    } catch (err) { showModal('Error', 'No se pudo actualizar el estado.', false); }
}

// ── Worker Schedule Management ──
const DAYS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

async function loadWorkerSchedule() {
    const container = document.getElementById('worker-schedule-form');
    container.innerHTML = '<p class="text-sm text-[#5C4333]/50 text-center py-4">Cargando tu disponibilidad...</p>';
    try {
        const response = await fetch(BASE_URL + '/worker/schedule/get', { method: 'POST' });
        const data = await response.json();
        if (!data.success) {
            container.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Error al cargar disponibilidad.</p>';
            return;
        }

        let html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">';
        const scheduleMap = {};
        (data.schedule || []).forEach(s => { scheduleMap[s.day_of_week] = s; });

        for (let d = 0; d <= 6; d++) {
            const s = scheduleMap[d];
            const isActive = s ? s.is_active == 1 : false;
            const start = s ? s.start_time.substring(0, 5) : '09:00';
            const end = s ? s.end_time.substring(0, 5) : '18:00';
            const checked = isActive ? 'checked' : '';

            html += `<div class="bg-[#FAF6F0]/40 rounded-xl p-4 border border-[#EFE5D9] space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-extrabold text-[#5C4333]">${DAYS[d]}</label>
                    <label class="inline-flex items-center gap-2 text-xs text-[#5C4333]/60 cursor-pointer">
                        <input type="checkbox" class="ws-active" data-day="${d}" ${checked} onchange="toggleWorkerDay(${d})">
                        Disponible
                    </label>
                </div>
                <div class="grid grid-cols-2 gap-2 ws-time-row" id="ws-row-${d}" style="${isActive ? '' : 'opacity:0.5;pointer-events:none'}">
                    <div>
                        <label class="text-[10px] uppercase font-bold text-[#5C4333]/40">Inicio</label>
                        <input type="time" class="ws-start block w-full px-2 py-1.5 bg-white border border-[#EFE5D9] rounded-lg text-xs" data-day="${d}" value="${start}">
                    </div>
                    <div>
                        <label class="text-[10px] uppercase font-bold text-[#5C4333]/40">Fin</label>
                        <input type="time" class="ws-end block w-full px-2 py-1.5 bg-white border border-[#EFE5D9] rounded-lg text-xs" data-day="${d}" value="${end}">
                    </div>
                </div>
            </div>`;
        }
        html += '</div>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Error de conexión.</p>';
    }
}

function toggleWorkerDay(day) {
    const row = document.getElementById('ws-row-' + day);
    const checkbox = document.querySelector(`.ws-active[data-day="${day}"]`);
    if (checkbox.checked) {
        row.style.opacity = '1';
        row.style.pointerEvents = 'auto';
    } else {
        row.style.opacity = '0.5';
        row.style.pointerEvents = 'none';
    }
}

async function saveWorkerSchedule() {
    const scheduleData = {};
    document.querySelectorAll('.ws-active').forEach(cb => {
        const day = cb.dataset.day;
        const startInput = document.querySelector(`.ws-start[data-day="${day}"]`);
        const endInput = document.querySelector(`.ws-end[data-day="${day}"]`);
        scheduleData[day] = {
            start_time: startInput ? startInput.value : '09:00',
            end_time: endInput ? endInput.value : '18:00',
            is_active: cb.checked ? '1' : '',
        };
    });

    try {
        const formData = new URLSearchParams();
        formData.append('schedule', JSON.stringify(scheduleData));

        const response = await fetch(BASE_URL + '/worker/schedule/update', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData
        });
        const data = await response.json();
        if (data.success) {
            showToast(data.message, true);
            setTimeout(() => loadWorkerSchedule(), 800);
        } else {
            showModal('Error', data.message, false);
        }
    } catch (err) {
        showModal('Error de Conexión', 'No se pudo guardar tu disponibilidad.', false);
    }
}
