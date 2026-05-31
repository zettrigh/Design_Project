document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('register-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const btnSpinner = document.getElementById('btn-spinner');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Client-side validations
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('password_confirm').value;

        if (password !== confirm) {
            showModal('Contraseñas Diferentes', 'Las contraseñas ingresadas no coinciden. Por favor, revísalas.', false);
            return;
        }

        if (password.length < 8) {
            showModal('Contraseña Débil', 'La contraseña debe tener al menos 8 caracteres.', false);
            return;
        }

        // UI Feedback
        submitBtn.disabled = true;
        btnText.textContent = "Registrando...";
        btnSpinner.classList.remove('hidden');

        try {
            const formData = new URLSearchParams(new FormData(form));
            const response = await fetch('/HomeWorks/Design_Project/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showModal('¡Registro Exitoso!', data.message, true, () => {
                    window.location.href = data.redirect;
                });
            } else {
                showModal('Error de Registro', data.message, false);
                submitBtn.disabled = false;
                btnText.textContent = "Crear Cuenta";
                btnSpinner.classList.add('hidden');
            }
        } catch (err) {
            showModal('Error de Conexión', 'No se pudo comunicar con el servidor para procesar el registro.', false);
            submitBtn.disabled = false;
            btnText.textContent = "Crear Cuenta";
            btnSpinner.classList.add('hidden');
        }
    });
});
