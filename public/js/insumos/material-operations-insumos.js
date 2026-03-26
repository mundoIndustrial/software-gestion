/**
 * Material Operations for Insumos/Materiales Module
 * Single responsibility: persistir cambios del modal de insumos.
 */

function guardarInsumosModal() {
    const pedido = document.getElementById('modalPedido').textContent;
    const prendaId = document.getElementById('modalPrendaId').value;
    const materiales = [];

    const tbody = document.getElementById('insumosTableBody');
    const filas = tbody.querySelectorAll('tr');

    filas.forEach((fila) => {
        const celdas = fila.querySelectorAll('td');
        const nombreMaterialEl = celdas[0];
        let nombreMaterial = nombreMaterialEl.textContent.trim();
        nombreMaterial = nombreMaterial.replace(/^[\u2022\u25cf\u25cb\u25d0\u25d1\s]+/, '').trim();

        const checkbox = fila.querySelector('input[type="checkbox"]');
        const todosInputsFecha = fila.querySelectorAll('input[type="date"]');
        const fechaOrdenInput = todosInputsFecha[0];
        const fechaPedidoInput = todosInputsFecha[1];
        const fechaPagoInput = todosInputsFecha[2];
        const fechaLlegadaInput = todosInputsFecha[3];
        const fechaDespachoInput = todosInputsFecha[4];

        const recibido = checkbox?.checked || false;
        const fechaOrden = fechaOrdenInput?.value || '';
        const fechaPedido = fechaPedidoInput?.value || '';
        const fechaPago = fechaPagoInput?.value || '';
        const fechaLlegada = fechaLlegadaInput?.value || '';
        const fechaDespacho = fechaDespachoInput?.value || '';

        const inputObservaciones = fila.querySelector('input[type="hidden"][id^="observaciones_"]');
        const observaciones = inputObservaciones ? inputObservaciones.value : '';

        if (recibido || fechaOrden || fechaPedido || fechaPago || fechaLlegada || fechaDespacho || observaciones) {
            materiales.push({
                nombre: nombreMaterial,
                fecha_orden: fechaOrden || null,
                fecha_pedido: fechaPedido || null,
                fecha_pago: fechaPago || null,
                fecha_llegada: fechaLlegada || null,
                fecha_despacho: fechaDespacho || null,
                recibido,
                observaciones: observaciones || null,
            });
        }
    });

    fetch(`/insumos/materiales/${pedido}/guardar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ materiales, prenda_id: prendaId || null }),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Materiales guardados correctamente', 'success');
            } else {
                showToast('Error al guardar', 'error');
            }
            const closeModal = window.insumosHandlers?.modalHandlers?.cerrarModalInsumos;
            if (typeof closeModal === 'function') {
                closeModal();
            }
        })
        .catch(() => {
            showToast('Error al guardar los materiales', 'error');
        });
}

function exportMaterialOperations() {
    window.insumosHandlers = window.insumosHandlers || {};
    window.insumosHandlers.materialOperations = {
        guardarInsumosModal,
    };
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', exportMaterialOperations);
} else {
    exportMaterialOperations();
}
