const BASE_URL = window.BASE_URL || '/HomeWorks/Design_Project';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('hairstyle-form');
    const formTitle = document.getElementById('form-title');
    const formDesc = document.getElementById('form-desc');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const cancelBtn = document.getElementById('cancel-btn');

    // ── Hairstyle CRUD ──
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const isEdit = document.getElementById('hairstyle-id').value !== '';
        const endpoint = isEdit ? BASE_URL + '/admin/hairstyles/update' : BASE_URL + '/admin/hairstyles/store';

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

    // ── Load Workers ──
    loadWorkers();
});

// ── Hairstyle Helpers ──
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
            formData.append('_csrf_token', window.CSRF_TOKEN || '');
            formData.append('id', id);
            const response = await fetch(BASE_URL + '/admin/hairstyles/delete', {
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
        formData.append('_csrf_token', window.CSRF_TOKEN || '');
        formData.append('id', id);
        formData.append('status', status);
        const response = await fetch(BASE_URL + '/admin/reservations/update', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData
        });
        const data = await response.json();
        if (data.success) { showToast(data.message, true); setTimeout(() => { window.location.reload(); }, 1200); }
        else { showModal('Error', data.message, false); }
    } catch (err) { showModal('Error', 'No se pudo actualizar el estado.', false); }
}

// ── Worker Management ──
function toggleWorkerForm() {
    const container = document.getElementById('worker-form-container');
    container.classList.toggle('hidden');
    if (!container.classList.contains('hidden')) {
        document.getElementById('worker-id').value = '';
        document.getElementById('worker-username').value = '';
        document.getElementById('worker-email').value = '';
        document.getElementById('worker-password').value = '';
        document.getElementById('worker-form-title').textContent = 'Agregar Trabajador';
    }
}

function cancelWorkerEdit() {
    document.getElementById('worker-form-container').classList.add('hidden');
    document.getElementById('worker-form').reset();
    document.getElementById('worker-id').value = '';
}

async function loadWorkers() {
    try {
        const response = await fetch(BASE_URL + '/admin/workers/list', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: '_csrf_token=' + encodeURIComponent(window.CSRF_TOKEN || '')
        });
        const data = await response.json();
        const list = document.getElementById('workers-list');

        if (!data.success || !data.workers || data.workers.length === 0) {
            list.innerHTML = '<p class="text-sm text-[#5C4333]/50 text-center py-4">No hay trabajadores registrados.</p>';
            return;
        }

        let html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-[#EFE5D9]/40"><thead><tr class="bg-[#FAF6F0]/40">';
        html += '<th class="px-4 py-3 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Usuario</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Email</th>';
        html += '<th class="px-4 py-3 text-left text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Registro</th>';
        html += '<th class="px-4 py-3 text-right text-xs font-bold text-[#5C4333]/50 uppercase tracking-widest">Acciones</th>';
        html += '</tr></thead><tbody class="divide-y divide-[#EFE5D9]/30">';

        data.workers.forEach(w => {
            html += `<tr class="hover:bg-[#FAF6F0]/20 transition-colors">`;
            html += `<td class="px-4 py-3 text-sm font-extrabold text-[#5C4333]">${w.username}</td>`;
            html += `<td class="px-4 py-3 text-sm text-[#5C4333]/70">${w.email}</td>`;
            html += `<td class="px-4 py-3 text-sm text-[#5C4333]/50">${w.created_at}</td>`;
            html += `<td class="px-4 py-3 text-right text-xs font-semibold space-x-1">`;
            html += `<button onclick="editWorker(${w.id}, '${w.username}', '${w.email}')" class="px-2.5 py-1 text-blue-600 hover:bg-blue-50 rounded-lg border border-blue-200/60 hover:border-blue-500 transition-all cursor-pointer">Editar</button> `;
            html += `<button onclick="deleteWorker(${w.id})" class="px-2.5 py-1 text-red-600 hover:bg-red-50 rounded-lg border border-red-200/60 hover:border-red-500 transition-all cursor-pointer">Eliminar</button>`;
            html += `</td></tr>`;
        });

        html += '</tbody></table></div>';
        list.innerHTML = html;
    } catch (err) {
        document.getElementById('workers-list').innerHTML = '<p class="text-sm text-red-500 text-center py-4">Error cargando trabajadores.</p>';
    }
}

function editWorker(id, username, email) {
    document.getElementById('worker-id').value = id;
    document.getElementById('worker-username').value = username;
    document.getElementById('worker-email').value = email;
    document.getElementById('worker-password').value = '';
    document.getElementById('worker-form-title').textContent = 'Editar Trabajador';
    document.getElementById('worker-form-container').classList.remove('hidden');
    document.getElementById('worker-form-container').scrollIntoView({ behavior: 'smooth' });
}

function deleteWorker(id) {
    showModal('¿Eliminar trabajador?', 'Esta acción es irreversible.', false, async () => {
        try {
            const formData = new URLSearchParams();
            formData.append('_csrf_token', window.CSRF_TOKEN || '');
            formData.append('id', id);
            const response = await fetch(BASE_URL + '/admin/workers/delete', {
                method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData
            });
            const data = await response.json();
            if (data.success) { showToast(data.message, true); setTimeout(() => { loadWorkers(); }, 800); }
            else { showModal('Error', data.message, false); }
        } catch (err) { showModal('Error', 'No se pudo eliminar.', false); }
    });
}

// Worker Form Submit
document.getElementById('worker-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('worker-id').value;
    const isEdit = id !== '';
    const endpoint = isEdit ? BASE_URL + '/admin/workers/update' : BASE_URL + '/admin/workers/store';

    try {
        const formData = new URLSearchParams();
        formData.append('_csrf_token', window.CSRF_TOKEN || '');
        formData.append('id', id);
        formData.append('username', document.getElementById('worker-username').value);
        formData.append('email', document.getElementById('worker-email').value);
        formData.append('password', document.getElementById('worker-password').value);

        const response = await fetch(endpoint, {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData
        });
        const data = await response.json();

        if (data.success) {
            showToast(data.message, true);
            cancelWorkerEdit();
            setTimeout(() => { loadWorkers(); }, 800);
        } else {
            showModal('Error', data.message, false);
        }
    } catch (err) {
        showModal('Error de Conexión', 'No se pudo comunicar con el servidor.', false);
    }
});

// ── Business Hours Management ──
const DAYS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

async function loadBusinessHours() {
    const container = document.getElementById('business-hours-form');
    container.innerHTML = '<p class="text-sm text-[#5C4333]/50 text-center py-4">Cargando horarios...</p>';
    try {
        const response = await fetch(BASE_URL + '/admin/business-hours', { method: 'GET' });
        const data = await response.json();
        if (!data.success || !data.hours) {
            container.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Error al cargar horarios.</p>';
            return;
        }

        let html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">';
        const hoursMap = {};
        data.hours.forEach(h => { hoursMap[h.day_of_week] = h; });

        for (let d = 0; d <= 6; d++) {
            const h = hoursMap[d];
            const isActive = h ? h.is_active == 1 : false;
            const open = h ? h.open_time.substring(0, 5) : '09:00';
            const close = h ? h.close_time.substring(0, 5) : '18:00';
            const checked = isActive ? 'checked' : '';

            html += `<div class="bg-[#FAF6F0]/40 rounded-xl p-4 border border-[#EFE5D9] space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-extrabold text-[#5C4333]">${DAYS[d]}</label>
                    <label class="inline-flex items-center gap-2 text-xs text-[#5C4333]/60 cursor-pointer">
                        <input type="checkbox" class="business-active" data-day="${d}" ${checked} onchange="toggleBusinessDay(${d})">
                        Abierto
                    </label>
                </div>
                <div class="grid grid-cols-2 gap-2 business-time-row" id="business-row-${d}" style="${isActive ? '' : 'opacity:0.5;pointer-events:none'}">
                    <div>
                        <label class="text-[10px] uppercase font-bold text-[#5C4333]/40">Abre</label>
                        <input type="time" class="business-open block w-full px-2 py-1.5 bg-white border border-[#EFE5D9] rounded-lg text-xs" data-day="${d}" value="${open}">
                    </div>
                    <div>
                        <label class="text-[10px] uppercase font-bold text-[#5C4333]/40">Cierra</label>
                        <input type="time" class="business-close block w-full px-2 py-1.5 bg-white border border-[#EFE5D9] rounded-lg text-xs" data-day="${d}" value="${close}">
                    </div>
                </div>
            </div>`;
        }
        html += '</div>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Error de conexión al cargar horarios.</p>';
    }
}

function toggleBusinessDay(day) {
    const row = document.getElementById('business-row-' + day);
    const checkbox = document.querySelector(`.business-active[data-day="${day}"]`);
    if (checkbox.checked) {
        row.style.opacity = '1';
        row.style.pointerEvents = 'auto';
    } else {
        row.style.opacity = '0.5';
        row.style.pointerEvents = 'none';
    }
}

async function saveBusinessHours() {
    const hoursData = {};
    document.querySelectorAll('.business-active').forEach(cb => {
        const day = cb.dataset.day;
        const openInput = document.querySelector(`.business-open[data-day="${day}"]`);
        const closeInput = document.querySelector(`.business-close[data-day="${day}"]`);
        hoursData[day] = {
            open_time: openInput ? openInput.value : '09:00',
            close_time: closeInput ? closeInput.value : '18:00',
            is_active: cb.checked ? '1' : '',
        };
    });

    try {
        const formData = new URLSearchParams();
        formData.append('_csrf_token', window.CSRF_TOKEN || '');
        formData.append('hours', JSON.stringify(hoursData));

        const response = await fetch(BASE_URL + '/admin/business-hours/update', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData
        });
        const data = await response.json();
        if (data.success) {
            showToast(data.message, true);
            setTimeout(() => loadBusinessHours(), 800);
        } else {
            showModal('Error', data.message, false);
        }
    } catch (err) {
        showModal('Error de Conexión', 'No se pudo guardar.', false);
    }
}

// ── Schedule Overview ──
async function loadScheduleOverview() {
    const date = document.getElementById('overview-date').value;
    if (!date) {
        showModal('Fecha requerida', 'Selecciona una fecha para ver la agenda.', false);
        return;
    }
    const container = document.getElementById('schedule-overview-container');
    container.innerHTML = '<p class="text-sm text-[#5C4333]/50 text-center py-4">Cargando agenda...</p>';

    try {
        const formData = new URLSearchParams();
        formData.append('_csrf_token', window.CSRF_TOKEN || '');
        formData.append('date', date);

        const response = await fetch(BASE_URL + '/admin/schedule/overview', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData
        });
        const data = await response.json();
        if (!data.success) {
            container.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Error al cargar la agenda.</p>';
            return;
        }

        const overview = data.overview;
        let html = `<div class="space-y-4">
            <div class="flex justify-between items-center bg-[#FAF6F0]/60 rounded-xl px-4 py-3">
                <div>
                    <span class="text-sm font-bold text-[#5C4333]">${overview.day_name}, ${overview.date}</span>
                    <span class="text-xs text-[#5C4333]/50 ml-2">${overview.total} cita(s) programada(s)</span>
                </div>
                ${overview.business_hours ? `<span class="text-xs font-bold text-emerald-700">${overview.business_hours.open_time?.substring(0,5)} - ${overview.business_hours.close_time?.substring(0,5)}</span>` : '<span class="text-xs text-red-500">Cerrado</span>'}
            </div>`;

        if (overview.reservations && overview.reservations.length > 0) {
            html += '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-[#EFE5D9]/40"><thead><tr class="bg-[#FAF6F0]/40">';
            html += '<th class="px-4 py-3 text-left text-xs font-bold text-[#5C4333]/50 uppercase">Hora</th>';
            html += '<th class="px-4 py-3 text-left text-xs font-bold text-[#5C4333]/50 uppercase">Cliente</th>';
            html += '<th class="px-4 py-3 text-left text-xs font-bold text-[#5C4333]/50 uppercase">Peinado</th>';
            html += '<th class="px-4 py-3 text-left text-xs font-bold text-[#5C4333]/50 uppercase">Trabajador</th>';
            html += '</tr></thead><tbody class="divide-y divide-[#EFE5D9]/30">';

            overview.reservations.forEach(r => {
                html += `<tr class="hover:bg-[#FAF6F0]/20 transition-colors">
                    <td class="px-4 py-3 text-sm font-bold text-[#5C4333]">${r.appointment_time?.substring(0,5)} - ${r.end_time?.substring(0,5)}</td>
                    <td class="px-4 py-3 text-sm text-[#5C4333]/85">${r.client_name || '—'}</td>
                    <td class="px-4 py-3 text-sm text-[#5C4333]/85">${r.hairstyle_name || '—'}</td>
                    <td class="px-4 py-3 text-sm text-[#5C4333]/70">${r.worker_name || 'Sin asignar'}</td>
                </tr>`;
            });

            html += '</tbody></table></div>';
        } else {
            html += '<p class="text-sm text-[#5C4333]/50 text-center py-6">No hay citas programadas para esta fecha.</p>';
        }
        html += '</div>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<p class="text-sm text-red-500 text-center py-4">Error de conexión.</p>';
    }
}

// Cargar horarios al inicio
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(loadBusinessHours, 100);
    setTimeout(cargarTasaCambio, 200);
});

// ── Tipo de Cambio ──

async function cargarTasaCambio() {
    const input = document.getElementById('exchange-rate-input');
    if (!input) return;
    try {
        const res = await fetch(BASE_URL + '/admin/exchange-rate');
        const data = await res.json();
        if (data.success && data.rate > 0) {
            input.value = data.rate;
        }
    } catch (err) {
        console.error('Error al cargar tasa de cambio:', err);
    }
}

async function guardarTasaCambio() {
    const input = document.getElementById('exchange-rate-input');
    const btn = document.getElementById('save-rate-btn');
    const status = document.getElementById('exchange-rate-status');
    if (!input || !btn) return;

    const rate = parseFloat(input.value);
    if (isNaN(rate) || rate <= 0) {
        status.className = 'text-xs text-red-600 font-medium mt-2';
        status.textContent = 'Ingresa una tasa válida mayor a cero.';
        status.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Guardando...';

    const formData = new URLSearchParams();
    formData.append('_csrf_token', window.CSRF_TOKEN || '');
    formData.append('rate', rate.toString());

    try {
        const res = await fetch(BASE_URL + '/admin/exchange-rate/set', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString(),
        });
        const data = await res.json();
        if (data.success) {
            status.className = 'text-xs text-emerald-600 font-medium mt-2';
            status.textContent = '✔ Tasa de cambio actualizada: 1 USD = ' + rate.toFixed(2) + ' VES';
        } else {
            status.className = 'text-xs text-red-600 font-medium mt-2';
            status.textContent = '✘ ' + (data.message || 'Error al guardar.');
        }
    } catch (err) {
        status.className = 'text-xs text-red-600 font-medium mt-2';
        status.textContent = '✘ Error de conexión.';
    }

    status.classList.remove('hidden');
    btn.disabled = false;
    btn.textContent = 'Guardar Tasa';
}
