/**
 * Módulo para manejar la acción de completar recibos en Corte desde la pestaña sobremedida
 * Solo para administrador-costura
 */

// Importar httpJson para usarlo en el módulo
import { httpJson } from '../api/http';
import { mostrarExito, mostrarError } from '../ui/messages';

export function completarReciboCorteSobremedida(btn) {
    const reciboId = btn.dataset.reciboId;
    const prendaId = btn.dataset.prendaId;
    const card = btn.closest('.orden-card-simple');

    if (!reciboId) {
        console.error('No se encontró el ID del recibo');
        return;
    }

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-rounded spin">refresh</span> PROCESANDO...';

    httpJson(`/operario/api/recibos/${reciboId}/completar-corte-sobremedida`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({}),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Mostrar mensaje de éxito
                mostrarExito(`Recibo completado en Corte y movido a Costura`);

                // Actualizar la interfaz
                actualizarInterfazReciboCorteSobremedida(card, btn);

                // Recargar el dashboard después de 1 segundo
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                btn.disabled = false;
                btn.innerHTML = originalText;
                mostrarError(data.message || 'Error al completar el recibo');
            }
        })
        .catch((error) => {
            console.error('Error completando recibo en Corte:', error);
            btn.disabled = false;
            btn.innerHTML = originalText;
            mostrarError('Error al completar el recibo');
        });
}

function actualizarInterfazReciboCorteSobremedida(card, btn) {
    // Cambiar el botón a "DESHACER"
    btn.classList.remove('btn-completar-corte');
    btn.classList.add('btn-deshacer-corte');
    btn.innerHTML = '<span class="material-symbols-rounded">undo</span> DESHACER';
    btn.disabled = false;
    btn.onclick = function() {
        deshacerReciboCorteSobremedida(this);
    };

    // Marcar la tarjeta como completada
    card.classList.add('card-completado-corte');
}

export function deshacerReciboCorteSobremedida(btn) {
    const reciboId = btn.dataset.reciboId;
    const prendaId = btn.dataset.prendaId;
    const card = btn.closest('.orden-card-simple');

    if (!reciboId) {
        console.error('No se encontró el ID del recibo');
        return;
    }

    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-rounded spin">refresh</span> PROCESANDO...';

    httpJson(`/operario/api/recibos/${reciboId}/deshacer`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({}),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Mostrar mensaje de éxito
                mostrarExito('Acción deshecha correctamente');

                // Actualizar la interfaz
                btn.classList.remove('btn-deshacer-corte');
                btn.classList.add('btn-completar-corte');
                btn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> COMPLETAR';
                btn.disabled = false;
                btn.onclick = function() {
                    completarReciboCorteSobremedida(this);
                };

                // Remover la clase de completado
                card.classList.remove('card-completado-corte');

                // Recargar el dashboard después de 1 segundo
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                btn.disabled = false;
                btn.innerHTML = originalText;
                mostrarError(data.message || 'Error al deshacer la acción');
            }
        })
        .catch((error) => {
            console.error('Error deshaciendo recibo en Corte:', error);
            btn.disabled = false;
            btn.innerHTML = originalText;
            mostrarError('Error al deshacer la acción');
        });
}
