/**
 * BODEGA - Sistema de Gestión de Pedidos
 * JavaScript Vanilla • Operaciones AJAX
 * Febrero 2026
 */

document.addEventListener('DOMContentLoaded', function() {
    // ==================== ELEMENTOS DOM ====================
    const searchInput = document.getElementById('searchInput');
    const asesorFilter = document.getElementById('asesorFilter');
    const estadoFilter = document.getElementById('estadoFilter');
    const pedidosTableBody = document.getElementById('pedidosTableBody');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');

    // ==================== INICIALIZACIÓN ====================
    initializeEventListeners();
    updateStatistics();

    /**
     * Inicializar event listeners
     */
    function initializeEventListeners() {
        // Filtros
        searchInput?.addEventListener('input', filterTable);
        asesorFilter?.addEventListener('change', filterTable);
        estadoFilter?.addEventListener('change', filterTable);

        // Botones de entregar
        document.querySelectorAll('.entregar-btn').forEach(btn => {
            btn.addEventListener('click', handleEntregarClick);
        });

        // Inputs de observaciones
        document.querySelectorAll('.observaciones-input').forEach(input => {
            input.addEventListener('blur', handleObservacionesChange);
        });

        // Inputs de fecha
        document.querySelectorAll('.fecha-input').forEach(input => {
            input.addEventListener('change', handleFechaChange);
        });
    }

    /**
     * Filtrar tabla según criterios
     */
    function filterTable() {
        const searchValue = searchInput.value.toLowerCase().trim();
        const asesorValue = asesorFilter.value.toLowerCase().trim();
        const estadoValue = estadoFilter.value.toLowerCase().trim();

        const rows = document.querySelectorAll('.pedido-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const rowText = row.getAttribute('data-search');
            const rowAsesor = row.getAttribute('data-asesor').toLowerCase();
            const rowEstado = row.getAttribute('data-estado').toLowerCase();

            let showRow = true;

            if (searchValue && !rowText.includes(searchValue)) showRow = false;
            if (asesorValue && rowAsesor !== asesorValue) showRow = false;
            if (estadoValue && rowEstado !== estadoValue) showRow = false;

            row.style.display = showRow ? '' : 'none';
            if (showRow) visibleCount++;
        });

        updateStatistics();
    }

    /**
     * Manejar clic en botón ENTREGAR
     */
    async function handleEntregarClick(e) {
        const button = e.target.closest('.entregar-btn');
        const id = button.getAttribute('data-id');

        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = '⏳ PROCESANDO...';

        try {
            const response = await fetch(`{{ route('bodega.entregar', '') }}/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (response.ok) {
                // Reemplazar botón con badge
                const badge = document.createElement('span');
                badge.className = 'inline-flex items-center px-3 py-1 text-[11px] font-black bg-blue-600 text-white uppercase tracking-wider rounded';
                badge.textContent = '✓ OK';
                button.parentElement.replaceChild(badge, button);

                // Actualizar estado y estilos de la fila
                const row = button.closest('.pedido-row');
                row.setAttribute('data-estado', 'entregado');
                row.style.backgroundColor = 'rgba(37, 99, 235, 0.05)';

                // Deshabilitar inputs
                const observaciones = row.querySelector('.observaciones-input');
                const fecha = row.querySelector('.fecha-input');
                if (observaciones) observaciones.disabled = true;
                if (fecha) fecha.disabled = true;

                showToast('✓ ENTREGADO CORRECTAMENTE', 'success');
                updateStatistics();
            } else {
                showToast('✗ ERROR: ' + (data.message || 'No se pudo procesar'), 'error');
                button.disabled = false;
                button.textContent = originalText;
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('✗ ERROR DE CONEXIÓN', 'error');
            button.disabled = false;
            button.textContent = originalText;
        }
    }

    /**
     * Manejar cambio en observaciones
     */
    async function handleObservacionesChange(e) {
        const input = e.target;
        const id = input.getAttribute('data-id');
        const observaciones = input.value.trim();

        input.style.opacity = '0.7';

        try {
            const response = await fetch(`{{ route('bodega.actualizar-observaciones') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    id: id,
                    observaciones: observaciones
                })
            });

            const data = await response.json();

            if (response.ok) {
                input.style.opacity = '1';
                input.classList.remove('border-red-500');
                input.classList.add('border-green-400');
                setTimeout(() => {
                    input.classList.remove('border-green-400');
                    input.classList.add('border-slate-300');
                }, 1000);
            } else {
                showToast('✗ Error al guardar', 'error');
                input.style.opacity = '1';
            }
        } catch (error) {
            console.error('Error:', error);
            input.style.opacity = '1';
            showToast('✗ Error de conexión', 'error');
        }
    }

    /**
     * Manejar cambio en fecha
     */
    async function handleFechaChange(e) {
        const input = e.target;
        const id = input.getAttribute('data-id');
        const fecha = input.value;

        if (!fecha) return;

        input.style.opacity = '0.7';

        try {
            const response = await fetch(`{{ route('bodega.actualizar-fecha') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    id: id,
                    fecha_entrega: fecha
                })
            });

            const data = await response.json();

            if (response.ok) {
                input.style.opacity = '1';
                input.classList.remove('border-red-500');
                input.classList.add('border-green-400');
                setTimeout(() => {
                    input.classList.remove('border-green-400');
                    input.classList.add('border-slate-300');
                }, 1000);

                checkRetrasado(input);
                updateStatistics();
            } else {
                showToast('✗ Error al guardar fecha', 'error');
                input.style.opacity = '1';
            }
        } catch (error) {
            console.error('Error:', error);
            input.style.opacity = '1';
            showToast('✗ Error de conexión', 'error');
        }
    }

    /**
     * Verificar si está retrasado
     */
    function checkRetrasado(dateInput) {
        const selectedDate = new Date(dateInput.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const row = dateInput.closest('.pedido-row');
        const currentEstado = row.getAttribute('data-estado');

        if (selectedDate < today && currentEstado !== 'entregado') {
            row.setAttribute('data-estado', 'retrasado');
        } else if (selectedDate >= today && currentEstado === 'retrasado') {
            row.setAttribute('data-estado', 'pendiente');
        }
    }

    /**
     * Mostrar notificación Toast
     */
    function showToast(message, type = 'success') {
        toastMessage.textContent = message;

        toast.classList.remove('hidden', 'bg-green-500', 'bg-red-500', 'bg-blue-500');

        if (type === 'error') {
            toast.classList.add('bg-red-500');
        } else if (type === 'info') {
            toast.classList.add('bg-blue-500');
        } else {
            toast.classList.add('bg-green-500');
        }

        toast.classList.remove('hidden');
        toast.style.display = 'flex';

        setTimeout(() => {
            toast.classList.add('hidden');
        }, 3000);
    }

    /**
     * Actualizar estadísticas
     */
    function updateStatistics() {
        const rows = document.querySelectorAll('.pedido-row');
        let countPendiente = 0;
        let countEntregado = 0;
        let countRetrasado = 0;

        rows.forEach(row => {
            const estado = row.getAttribute('data-estado');
            const isVisible = row.style.display !== 'none';

            if (!isVisible) return;

            switch(estado) {
                case 'entregado':
                    countEntregado++;
                    break;
                case 'retrasado':
                    countRetrasado++;
                    break;
                case 'pendiente':
                default:
                    countPendiente++;
                    break;
            }
        });

        document.getElementById('countPendiente').textContent = countPendiente;
        document.getElementById('countEntregado').textContent = countEntregado;
        document.getElementById('countRetrasado').textContent = countRetrasado;
    }
});
