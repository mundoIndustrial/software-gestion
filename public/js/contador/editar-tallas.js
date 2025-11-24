// Almacenar notas adicionales de tallas
window.tallasNotas = window.tallasNotas || {};

function editarTallas(element, prendaId, tallasBase) {
    // Si ya está en modo edición, no hacer nada
    if (element.querySelector('input')) {
        return;
    }

    // Obtener el texto actual
    const textoActual = element.textContent.trim();
    
    // Crear input para edición
    const input = document.createElement('input');
    input.type = 'text';
    input.value = textoActual;
    input.style.cssText = `
        width: 100%;
        padding: 0.5rem;
        font-weight: bold;
        color: #e74c3c;
        font-size: 0.95rem;
        border: 2px solid #e74c3c;
        border-radius: 4px;
        font-family: inherit;
        box-sizing: border-box;
    `;

    // Reemplazar el contenido del div con el input
    element.innerHTML = '';
    element.appendChild(input);
    input.focus();
    input.select();

    // Función para guardar
    function guardarCambios() {
        let nuevoTexto = input.value.trim();
        
        // Asegurar que comienza con "TALLAS:"
        if (!nuevoTexto.startsWith('TALLAS:')) {
            nuevoTexto = 'TALLAS: ' + nuevoTexto;
        }

        // Guardar en memoria
        window.tallasNotas[prendaId] = nuevoTexto;

        // Restaurar el div con el nuevo texto
        element.textContent = nuevoTexto;
        element.style.cursor = 'pointer';
        element.style.padding = '0.5rem';
        element.style.borderRadius = '4px';
        element.style.transition = 'all 0.2s';

        console.log('✅ Tallas guardadas para prenda ' + prendaId + ':', nuevoTexto);

        // Guardar en la base de datos
        guardarEnBD(prendaId, nuevoTexto);
    }

    // Función para guardar en la base de datos
    function guardarEnBD(prendaId, notas) {
        fetch(`/contador/prenda/${prendaId}/notas-tallas`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                notas: notas
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ Guardado en BD:', data.message);
                // Mostrar notificación de éxito
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Guardado',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            } else {
                console.error('❌ Error al guardar:', data.message);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            }
        })
        .catch(error => {
            console.error('❌ Error de red:', error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al guardar en la base de datos'
                });
            }
        });
    }

    // Guardar al presionar Enter
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            guardarCambios();
        } else if (e.key === 'Escape') {
            // Cancelar edición
            element.textContent = textoActual;
            element.style.cursor = 'pointer';
            element.style.padding = '0.5rem';
            element.style.borderRadius = '4px';
            element.style.transition = 'all 0.2s';
        }
    });

    // Guardar al perder el foco
    input.addEventListener('blur', guardarCambios);
}

// Mostrar notas guardadas en consola
function mostrarTallasGuardadas() {
    console.log('📋 Notas de Tallas Guardadas:', window.tallasNotas);
    return window.tallasNotas;
}
