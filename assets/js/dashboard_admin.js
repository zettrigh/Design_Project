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
        const response = await fetch(BASE_URL + '/admin/workers/list', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
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
