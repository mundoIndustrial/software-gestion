/**
 * EVENT LISTENERS - Insumos Materiales
 * Todas las suscripciones a eventos del DOM
 */

/**
 * Inicializa todos los event listeners
 * Debe ser llamada cuando el DOM esté completamente cargado
 */
function initializeEventListeners() {
    console.log('[EventListeners] Inicializando handlers de eventos');

    // Listeners para checkboxes de materiales
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('material-checkbox')) {
            const fila = e.target.closest('tr');
            if (fila && e.target.type === 'checkbox') {
                if (e.target.checked) {
                    fila.classList.add('checked');
                } else {
                    fila.classList.remove('checked');
                }
            }
        }
        
        // Recalcular días de demora cuando cambian las fechas
        if (e.target.type === 'date') {
            const fila = e.target.closest('tr');
            if (fila) {
                actualizarDiasDemora(fila);
            }
        }
    });

    // Listeners para botones de acciones en tablas
    document.addEventListener('click', function(e) {
        // Cerrar dropdowns al hacer clic fuera
        if (!e.target.closest('.btn-acciones') && !e.target.closest('.acciones-dropdown-fixed')) {
            cerrarDropdownAcciones();
        }

        // Listener para modal de insumosModal click fuera
        const insumosModal = document.getElementById('insumosModal');
        if (insumosModal && e.target === insumosModal) {
            cerrarModalInsumos();
        }

        // Listener para modal de observacionesModal click fuera
        const observacionesModal = document.getElementById('observacionesModal');
        if (observacionesModal && e.target === observacionesModal) {
            cerrarModalObservaciones();
        }

        // Listener para modal de modalAnchoMetraje click fuera
        const modalAnchoMetraje = document.getElementById('modalAnchoMetraje');
        if (modalAnchoMetraje && e.target === modalAnchoMetraje) {
            cerrarModalAnchoMetraje();
        }

        // Listener para modal de confirmación click fuera
        const modalConfirmacionEliminar = document.getElementById('modalConfirmacionEliminar');
        if (modalConfirmacionEliminar && e.target === modalConfirmacionEliminar) {
            cerrarModalConfirmacionEliminar();
        }
    });

    // Listener para cambio de modo en Ancho y Metraje
    const modoRadios = document.querySelectorAll('input[name="modoAnchoMetraje"]');
    modoRadios.forEach(radio => {
        radio.addEventListener('change', cambiarModoAnchoMetraje);
    });

    // Listener para guardardo de observaciones con Ctrl+Enter
    const observacionesTexto = document.getElementById('observacionesTexto');
    if (observacionesTexto) {
        observacionesTexto.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                guardarObservaciones();
            }
        });
    }

    // Listener para actualizar contador de caracteres (si existe modal de pasar a revisar)
    document.addEventListener('input', function(e) {
        if (e.target.id === 'motivoPasarRevisar') {
            const contador = document.getElementById('contadorPasarRevisar');
            if (contador) {
                contador.textContent = e.target.value.length;
            }
        }
    });

    // Listener para modal de pasar a revisar (click fuera)
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('modalPasarRevisar');
        if (modal && e.target === modal) {
            cerrarModalPasarRevisar();
        }
    });

    // Listener para botones de paginación
    document.querySelectorAll('.pagination-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.classList.add('active');
            }
        });
    });

    // Listener para dropdown de acciones
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-acciones')) {
            const btn = e.target.closest('.btn-acciones');
            e.preventDefault();
            e.stopPropagation();
            crearDropdownAcciones(e, btn);
        }
    });

    // Listener para botón de check row (marcar/desmarcar)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-check-row')) {
            const btn = e.target.closest('.btn-check-row');
            toggleRowCheck(btn, e);
        }
    });

    // Listener para lazy load de imágenes
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => imageObserver.observe(img));
    }

    console.log('[EventListeners] Inicialización completada');
}

/**
 * Cierra todos los dropdowns de acciones
 */
function cerrarDropdownAcciones() {
    document.querySelectorAll('.acciones-dropdown-fixed').forEach(menu => {
        menu.remove();
    });
    dropdownAbiertoButton = null;
}

/**
 * Actualiza los días de demora en tiempo real
 */
function actualizarDiasDemora(fila) {
    const todosInputsFecha = fila.querySelectorAll('input[type="date"]');
    const fechaPedido = todosInputsFecha[0]?.value;
    const fechaLlegada = todosInputsFecha[1]?.value;
    
    if (!fechaPedido || !fechaLlegada) {
        return;
    }
    
    // Calcular días laborales (sin contar sábados, domingos)
    const diasLaborales = calcularDiasLaborales(fechaPedido, fechaLlegada);
    
    // Actualizar el span de días de demora
    const diasSpan = fila.querySelector('span[class*="bg-"]');
    if (diasSpan) {
        const color = getColorByDias(diasLaborales);
        diasSpan.textContent = `${diasLaborales} días`;
        diasSpan.className = `inline-block px-3 py-1 rounded-full text-sm font-semibold ${color.bgColor} ${color.textColor}`;
    }
}

/**
 * Alterna el estado de una fila (marcada/sin marcar)
 */
function toggleRowCheck(button, event) {
    event.preventDefault();
    event.stopPropagation();
    
    // Encontrar la fila (tr) del botón
    const row = button.closest('tr');
    if (!row) return;
    
    // Alternar clase de marcado en el botón
    button.classList.toggle('checked');
    
    // Alternar clase de marcado en la fila
    row.classList.toggle('row-checked');
    
    // Obtener el estado marcado actual
    const isMarcado = button.classList.contains('checked');
    
    // Obtener el ID del material desde algún atributo del row
    const materialId = row.dataset.materialId || row.dataset.reciboId;
    
    if (materialId) {
        guardarEstadoMarcado(materialId, isMarcado, button);
    }
}

/**
 * Envía el estado de marcado al servidor
 */
function guardarEstadoMarcado(materialId, marcado, button) {
    if (!materialId) {
        console.error('[guardarEstadoMarcado] Material ID no encontrado');
        return;
    }
    
    const url = `/insumos/materiales/${materialId}/toggle-marcado`;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ marcado })
    })
    .then(response => response.json())
    .then(data => {
        console.log('[guardarEstadoMarcado] Respuesta:', data);
    })
    .catch(error => {
        console.error('[guardarEstadoMarcado] Error:', error);
        // Revertir el estado en el UI si hay error
        button.classList.toggle('checked');
        button.closest('tr').classList.toggle('row-checked');
        showToast('Error al guardar el estado', 'error');
    });
}

/**
 * Cierra todos los dropdowns al hacer clic fuera
 */
document.addEventListener('click', function(e) {
    if (!e.target.closest('.filter-btn-insumos') && !e.target.closest('#filterModalInsumos')) {
        const filterModal = document.getElementById('filterModalInsumos');
        if (filterModal) {
            filterModal.style.display = 'none';
        }
    }
});

export {
    initializeEventListeners,
    cerrarDropdownAcciones,
    actualizarDiasDemora,
    toggleRowCheck,
    guardarEstadoMarcado
};
