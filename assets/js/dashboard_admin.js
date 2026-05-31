document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('hairstyle-form');
    const formTitle = document.getElementById('form-title');
    const formDesc = document.getElementById('form-desc');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const cancelBtn = document.getElementById('cancel-btn');

    // CRUD: Store or Update
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const isEdit = document.getElementById('hairstyle-id').value !== '';
        const endpoint = isEdit ? '/HomeWorks/Design_Project/admin/hairstyles/update' : '/HomeWorks/Design_Project/admin/hairstyles/store';

        submitBtn.disabled = true;
        btnText.textContent = "Procesando...";

        try {
            const formData = new URLSearchParams(new FormData(form));
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            } else {
                if (data.redirect) {
                    showModal('Sesión Inválida', data.message, false, () => {
                        window.location.href = data.redirect;
                    });
                } else {
                    showModal('Error en Operación', data.message, false);
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

    // Cancel Edit Reset
    cancelBtn.addEventListener('click', () => {
        form.reset();
        document.getElementById('hairstyle-id').value = '';
        formTitle.textContent = "Agregar Nuevo Peinado";
        formDesc.textContent = "Completa los campos para publicar un peinado en el catálogo";
        btnText.textContent = "Publicar Peinado";
        cancelBtn.classList.add('hidden');
    });
});

// Form Autofill for Editing
function iniciarEdicion(style) {
    document.getElementById('hairstyle-id').value = style.id;
    document.getElementById('name').value = style.name;
    document.getElementById('description').value = style.description;
    document.getElementById('price').value = style.price;
    document.getElementById('status').value = style.status;
    document.getElementById('image_url').value = style.image_url;

    // Change UI text
    document.getElementById('form-title').textContent = "Editar Peinado";
    document.getElementById('form-desc').textContent = `Modificando detalles del peinado "${style.name}"`;
    document.getElementById('btn-text').textContent = "Guardar Cambios";
    document.getElementById('cancel-btn').classList.remove('hidden');

    // Scroll to form smoothly
    document.getElementById('hairstyle-form').scrollIntoView({ behavior: 'smooth' });
}

// CRUD: Delete
function eliminarPeinado(id) {
    showModal('¿Estás seguro?', 'Esta acción es irreversible y eliminará el peinado del catálogo permanentemente.', false, async () => {
        try {
            const formData = new URLSearchParams();
            formData.append('id', id);

            const response = await fetch('/HomeWorks/Design_Project/admin/hairstyles/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            } else {
                if (data.redirect) {
                    showModal('Sesión Inválida', data.message, false, () => {
                        window.location.href = data.redirect;
                    });
                } else {
                    showModal('No se puede eliminar', data.message, false);
                }
            }
        } catch (err) {
            showModal('Error', 'No se pudo procesar la eliminación.', false);
        }
    });
}

// Reservation Management: Status Update
async function cambiarEstadoReserva(id, status) {
    try {
        const formData = new URLSearchParams();
        formData.append('id', id);
        formData.append('status', status);

        const response = await fetch('/HomeWorks/Design_Project/admin/reservations/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, true);
            setTimeout(() => {
                window.location.reload();
            }, 1200);
        } else {
            if (data.redirect) {
                showModal('Sesión Inválida', data.message, false, () => {
                    window.location.href = data.redirect;
                });
            } else {
                showModal('Error', data.message, false);
            }
        }
    } catch (err) {
        showModal('Error', 'No se pudo comunicar con el servidor para actualizar el estado.', false);
    }
}
