document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('register-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const btnSpinner = document.getElementById('btn-spinner');

    const BASE_URL = window.BASE_URL || '/HomeWorks/Design_Project';

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const password = document.getElementById('password').value;
        const confirm = document.getElementById('password_confirm').value;

        if (password !== confirm) {
            showModal('Contraseñas Diferentes', 'Las contraseñas ingresadas no coinciden.', false);
            return;
        }

        if (password.length < 8) {
            showModal('Contraseña Débil', 'La contraseña debe tener al menos 8 caracteres.', false);
            return;
        }

        submitBtn.disabled = true;
        btnText.textContent = "Registrando...";
        btnSpinner.classList.remove('hidden');

        try {
            const formData = new URLSearchParams(new FormData(form));
            const response = await fetch(BASE_URL + '/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showModal('¡Registro Exitoso!', data.message, true, () => { window.location.href = data.redirect; });
            } else {
                showModal('Error de Registro', data.message, false);
                submitBtn.disabled = false;
                btnText.textContent = "Crear Cuenta";
                btnSpinner.classList.add('hidden');
            }
        } catch (err) {
            showModal('Error de Conexión', 'No se pudo comunicar con el servidor.', false);
            submitBtn.disabled = false;
            btnText.textContent = "Crear Cuenta";
            btnSpinner.classList.add('hidden');
        }
    });
});
