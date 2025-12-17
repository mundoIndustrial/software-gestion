/**
 * Editar texto personalizado de tallas en el módulo contador
 * Permite hacer doble click en la sección de tallas para agregar texto personalizado
 */

// Almacenar texto personalizado de tallas por prenda
window.tallasTextoPersonalizado = window.tallasTextoPersonalizado || {};

/**
 * Habilitar edición de texto personalizado en tallas
 * @param {HTMLElement} element - Elemento contenedor de las tallas
 * @param {number} prendaId - ID de la prenda
 * @param {string} tallasBase - Tallas base (ej: "XS, S, M, L, XL, XXL, XXXL, XXXXL")
 * @param {string} textoPersonalizadoActual - Texto personalizado actual (si existe)
 */
function editarTallasPersonalizado(element, prendaId, tallasBase, textoPersonalizadoActual = '') {
    // Si ya está en modo edición, no hacer nada
    if (element.querySelector('input')) {
        return;
    }

    // Obtener el texto completo actual (tallas + texto personalizado)
    const textoCompleto = element.textContent.trim();
    
    // Crear input para edición
    const input = document.createElement('input');
    input.type = 'text';
    input.value = textoCompleto;
    input.style.cssText = `
        width: 100%;
        padding: 0.5rem;
        font-weight: bold;
        color: #e74c3c;
        font-size: 0.95rem;
        border: 2px solid #3498db;
        border-radius: 4px;
        font-family: inherit;
        box-sizing: border-box;
        background-color: #ecf0f1;
    `;

    // Placeholder con ejemplo
    input.placeholder = 'Ej: XS, S, M, L, XL, XXL, XXXL, XXXXL ( prueba de escritura 1400)';

    // Reemplazar el contenido del div con el input
    element.innerHTML = '';
    element.appendChild(input);
    
    // Posicionar cursor al final del texto
    input.focus();
    input.setSelectionRange(input.value.length, input.value.length);

    // Prevenir que se borren las tallas base
    input.addEventListener('keydown', function(e) {
        const cursorPos = input.selectionStart;
        const tallasLength = tallasBase.length;
        
        // Si intenta borrar (Backspace o Delete) dentro del área de tallas base, prevenir
        if ((e.key === 'Backspace' && cursorPos <= tallasLength) || 
            (e.key === 'Delete' && cursorPos < tallasLength)) {
            e.preventDefault();
            return;
        }
    });

    // Prevenir selección y eliminación de tallas base
    input.addEventListener('input', function(e) {
        const currentValue = input.value;
        
        // Si el valor actual no comienza con las tallas base, restaurarlas
        if (!currentValue.startsWith(tallasBase)) {
            // Extraer solo el texto personalizado (lo que viene después de las tallas)
            const textoPersonalizado = currentValue.replace(tallasBase, '').trim();
            input.value = tallasBase + (textoPersonalizado ? ' ' + textoPersonalizado : '');
            
            // Posicionar cursor al final
            input.setSelectionRange(input.value.length, input.value.length);
        }
    });

    // Función para guardar cambios
    function guardarCambios() {
        const nuevoTexto = input.value.trim();
        
        // Extraer solo el texto personalizado (después de las tallas base)
        let textoPersonalizado = '';
        
        // Si el texto contiene las tallas base, extraer lo que viene después
        if (nuevoTexto.includes(',')) {
            // Buscar el último paréntesis o texto adicional
            const match = nuevoTexto.match(/\(([^)]+)\)/);
            if (match) {
                textoPersonalizado = match[0]; // Incluye los paréntesis
            } else {
                // Si no hay paréntesis, tomar todo después de la última talla
                const partes = nuevoTexto.split(',');
                const ultimaParte = partes[partes.length - 1].trim();
                // Si la última parte no es una talla estándar, es texto personalizado
                const tallasEstandar = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];
                if (!tallasEstandar.includes(ultimaParte.toUpperCase())) {
                    textoPersonalizado = ultimaParte;
                }
            }
        }

        // Guardar en memoria
        window.tallasTextoPersonalizado[prendaId] = textoPersonalizado;

        // Restaurar el div con el nuevo texto
        element.textContent = nuevoTexto;
        element.style.cursor = 'pointer';
        element.style.padding = '0.5rem';
        element.style.borderRadius = '4px';
        element.style.transition = 'all 0.2s';

        console.log('✅ Texto personalizado de tallas guardado para prenda ' + prendaId + ':', textoPersonalizado);

        // Guardar en la base de datos
        guardarTextoPersonalizadoEnBD(prendaId, textoPersonalizado);
    }

    // Función para guardar en la base de datos
    function guardarTextoPersonalizadoEnBD(prendaId, textoPersonalizado) {
        fetch(`/contador/prenda/${prendaId}/texto-personalizado-tallas`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                texto_personalizado: textoPersonalizado
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
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            } else {
                console.error('❌ Error al guardar:', data.message);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        toast: true,
                        position: 'top-end'
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
                    text: 'Error al guardar en la base de datos',
                    toast: true,
                    position: 'top-end'
                });
            }
        });
    }

    // Guardar al presionar Enter
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            guardarCambios();
        } else if (e.key === 'Escape') {
            // Cancelar edición
            element.textContent = textoCompleto;
            element.style.cursor = 'pointer';
            element.style.padding = '0.5rem';
            element.style.borderRadius = '4px';
            element.style.transition = 'all 0.2s';
        }
    });

    // Guardar al perder el foco
    input.addEventListener('blur', guardarCambios);
}

// Mostrar textos personalizados guardados en consola
function mostrarTallasPersonalizadasGuardadas() {
    console.log('📋 Textos Personalizados de Tallas Guardados:', window.tallasTextoPersonalizado);
    return window.tallasTextoPersonalizado;
}
