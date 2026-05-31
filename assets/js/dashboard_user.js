async function apartarPeinado(id) {
    try {
        const formData = new URLSearchParams();
        formData.append('hairstyle_id', id);

        const response = await fetch('/HomeWorks/Design_Project/user/reserve', {
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
            }, 1500);
        } else {
            if (data.redirect) {
                showModal('Sesión Invalida', data.message, false, () => {
                    window.location.href = data.redirect;
                });
            } else {
                showModal('Apartado No Disponible', data.message, false);
            }
        }
    } catch (err) {
        showModal('Error', 'No se pudo registrar la reserva. Intenta nuevamente.', false);
    }
}
