document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('login-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const btnSpinner = document.getElementById('btn-spinner');

    const BASE_URL = window.BASE_URL || '/HomeWorks/Design_Project';

    // Check if session was timed out
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('timeout')) {
        setTimeout(() => {
            showModal('Sesión Expirada', 'Tu sesión ha expirado por inactividad. Por favor, inicia sesión de nuevo.', false);
        }, 300);
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        submitBtn.disabled = true;
        btnText.textContent = "Validando...";
        btnSpinner.classList.remove('hidden');

        try {
            const formData = new URLSearchParams(new FormData(form));
            const response = await fetch(BASE_URL + '/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast('¡Acceso concedido! Entrando a la plataforma.', true);
                setTimeout(() => { window.location.href = data.redirect; }, 1200);
            } else {
                showModal('Acceso Fallido', data.message, false);
                submitBtn.disabled = false;
                btnText.textContent = "Ingresar";
                btnSpinner.classList.add('hidden');
            }
        } catch (err) {
            showModal('Error de Conexión', 'No se pudo conectar con el servidor. Verifica tu conexión de red.', false);
            submitBtn.disabled = false;
            btnText.textContent = "Ingresar";
            btnSpinner.classList.add('hidden');
        }
    });
});
