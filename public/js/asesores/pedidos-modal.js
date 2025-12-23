// ========================================
// GESTIÓN DE MODALES - PEDIDOS
// ========================================

let productoCountModal = 0;
let siguientePedido = 1;

// ========================================
// FUNCIONES ELIMINADAS: verFactura, verSeguimiento, setCurrentOrder
// Estas funciones fueron removidas ya que los modales fueron eliminados
// ========================================

// ========================================
// MOSTRAR TAB ESPECÍFICO
// ========================================
function mostrarTabModal(tabName) {
    // Ocultar todos los tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));

    // Mostrar el tab seleccionado
    const tabSeleccionado = document.getElementById(`tab-${tabName}`);
    if (tabSeleccionado) {
        tabSeleccionado.classList.add('active');
    }

    // Actualizar botones activos en navegación
    const buttons = document.querySelectorAll('.tab-button');
    buttons.forEach(btn => btn.classList.remove('active'));

    // Marcar como activo el botón del tab actual
    event?.target?.classList.add('active');
    if (tabName === 'info-general') {
        buttons[0].classList.add('active');
    } else if (tabName === 'productos') {
        buttons[1].classList.add('active');
    } else if (tabName === 'resumen') {
        buttons[2].classList.add('active');
        actualizarResumenModal();
    }
}

// ========================================
// OBTENER SIGUIENTE PEDIDO
// ========================================
async function obtenerSiguientePedido() {
    try {
        const response = await fetch("{{ route('asesores.next-pedido') }}");
        const data = await response.json();
        siguientePedido = data.siguiente_pedido;
        document.getElementById('nuevoPedido').value = siguientePedido;
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('nuevoPedido').value = 1;
    }
}

// ========================================
// ABRIR MODAL CREAR PEDIDO
// ========================================
function abrirModalCrearPedido() {
    const modal = document.getElementById('modalCrearPedido');
    modal.style.display = 'flex';
    modal.addEventListener('click', cerrarAlClickAfuera);
    
    // Evitar scroll del body cuando el modal está abierto
    document.body.style.overflow = 'hidden';
    document.body.style.overflowX = 'hidden';
    
    productoCountModal = 0;
    
    // NO obtener el siguiente pedido aquí - se asignará al crear
    document.getElementById('nuevoPedido').value = '';
    
    document.getElementById('productosModalContainer').innerHTML = '';
    agregarProductoModal();
    
    // Mostrar primer tab
    mostrarTabModal('info-general');
    
    // Focus al cliente
    setTimeout(() => {
        document.getElementById('nuevoCliente').focus();
    }, 100);
}

// ========================================
// CERRAR AL HACER CLIC FUERA
// ========================================
function cerrarAlClickAfuera(event) {
    const modal = document.getElementById('modalCrearPedido');
    const container = document.querySelector('.modal-container-tabs');
    
    if (event.target === modal) {
        cerrarModalCrearPedido();
    }
}

// ========================================
// CERRAR MODAL CREAR PEDIDO
// ========================================
function cerrarModalCrearPedido() {
    const modal = document.getElementById('modalCrearPedido');
    modal.style.display = 'none';
    modal.removeEventListener('click', cerrarAlClickAfuera);
    document.getElementById('formCrearPedidoModal').reset();
    
    // Restaurar el scroll del body
    document.body.style.overflow = 'auto';
    document.body.style.overflowX = 'auto';
}

// ========================================
// AGREGAR PRODUCTO AL MODAL
// ========================================
function agregarProductoModal() {
    const container = document.getElementById('productosModalContainer');
    const template = document.getElementById('productoModalTemplate');
    const clone = template.content.cloneNode(true);

    // Actualizar número de prenda
    const numeroPrenda = container.querySelectorAll('.producto-modal-item').length + 1;
    clone.querySelector('.numero-prenda').textContent = numeroPrenda;

    // Actualizar índices
    const inputs = clone.querySelectorAll('input, select');
    inputs.forEach(input => {
        const name = input.getAttribute('name');
        if (name) {
            input.setAttribute('name', name.replace('[0]', `[${productoCountModal}]`));
        }
        if (input.classList.contains('producto-modal-cantidad')) {
            input.addEventListener('change', actualizarResumenModal);
        }
    });

    container.appendChild(clone);
    productoCountModal++;
    actualizarResumenModal();
}

// ========================================
// ELIMINAR PRODUCTO DEL MODAL
// ========================================
function eliminarProductoModal(button) {
    button.closest('.producto-modal-item').remove();
    actualizarResumenModal();
}

// ========================================
// ACTUALIZAR RESUMEN DEL MODAL
// ========================================
function actualizarResumenModal() {
    const productos = document.querySelectorAll('.producto-modal-item');
    let cantidadTotal = 0;

    productos.forEach(producto => {
        const cantidadInput = producto.querySelector('.producto-modal-cantidad');
        if (cantidadInput && cantidadInput.value) {
            cantidadTotal += Number.parseInt(cantidadInput.value) || 0;
        }
    });

    document.getElementById('resumenTotalProductos').textContent = productos.length;
    document.getElementById('resumenCantidadTotal').textContent = cantidadTotal;

    // Actualizar también los datos del resumen en el tab final
    const cliente = document.getElementById('nuevoCliente').value || '-';
    const formaPago = document.getElementById('nuevoFormaPago').value || '-';

    document.getElementById('resumenCliente').textContent = cliente;
    document.getElementById('resumenFormaPago').textContent = formaPago;
    document.getElementById('resumenEstado').textContent = 'No iniciado';
}

// ========================================
// RECOPILAR DATOS DEL LOGO (PASO 3)
// ========================================
function recopilarDatosLogo() {
    console.log('📸 Recopilando datos del logo...');
    
    const descripcionLogo = document.getElementById('descripcion_logo')?.value || '';
    
    // Recopilar técnicas
    const tecnicasElementos = document.querySelectorAll('#tecnicas_seleccionadas input[name="tecnicas[]"]');
    const tecnicas = Array.from(tecnicasElementos).map(el => el.value);
    
    // Recopilar observaciones
    const observacionesTecnicas = document.getElementById('observaciones_tecnicas')?.value || '';
    
    // Recopilar ubicaciones
    const ubicacionesElementos = document.querySelectorAll('#secciones_agregadas .seccion-item');
    const ubicaciones = Array.from(ubicacionesElementos).map(el => {
        return {
            seccion: el.querySelector('input[name="seccion"]')?.value || '',
            ubicaciones_seleccionadas: Array.from(el.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value)
        };
    });
    
    // Recopilar observaciones generales con tipo y valor (similar a cotizaciones.js)
    const observacionesGenerales = [];
    document.querySelectorAll('#observaciones_lista > div').forEach(obs => {
        const textoInput = obs.querySelector('input[name="observaciones_generales[]"]') || obs.querySelector('textarea');
        const checkboxInput = obs.querySelector('input[name="observaciones_check[]"]');
        const valorInput = obs.querySelector('input[name="observaciones_valor[]"]');
        const checkboxModeDiv = obs.querySelector('.obs-checkbox-mode');
        const textModeDiv = obs.querySelector('.obs-text-mode');

        const texto = textoInput?.value || '';
        if (!texto.trim()) return;

        const esModoTexto = textModeDiv && textModeDiv.style.display !== 'none';
        const esModoCheckbox = checkboxModeDiv && checkboxModeDiv.style.display !== 'none';

        if (esModoTexto) {
            observacionesGenerales.push({
                tipo: 'texto',
                texto: texto,
                valor: valorInput?.value || ''
            });
        } else {
            // Por defecto, tratar como checkbox
            observacionesGenerales.push({
                tipo: 'checkbox',
                texto: texto,
                valor: checkboxInput?.checked ? 'on' : ''
            });
        }
    });
    
    // Recopilar imágenes (File objects)
    const imagenes = Array.from(document.querySelectorAll('#galeria_imagenes img')).map(img => {
        // Intentar obtener el File object si existe
        return img.dataset.file || img.src;
    });
    
    console.log('✅ Datos del logo recopilados:', {
        descripcion: descripcionLogo.substring(0, 50),
        tecnicas: tecnicas.length,
        ubicaciones: ubicaciones.length,
        imagenes: imagenes.length
    });
    
    return {
        descripcion: descripcionLogo,
        tecnicas: tecnicas,
        observaciones_tecnicas: observacionesTecnicas,
        ubicaciones: ubicaciones,
        observaciones_generales: observacionesGenerales,
        imagenes: imagenes
    };
}

// ========================================
// GUARDAR PEDIDO MODAL COMO BORRADOR
// ========================================
function guardarPedidoModal() {
    const form = document.getElementById('formCrearPedidoModal');
    
    if (!form.checkValidity()) {
        Swal.fire({
            title: 'Validación',
            text: 'Por favor completa todos los campos requeridos',
            icon: 'warning',
            confirmButtonColor: '#0066cc'
        });
        return;
    }

    const formData = new FormData(form);
    // NO incluir el ID de pedido - se asignará después
    formData.delete('pedido');
    
    // ✅ AGREGAR DATOS DEL LOGO (PASO 3)
    const datosLogo = recopilarDatosLogo();
    
    // Agregar descripción del logo
    formData.append('logo[descripcion]', datosLogo.descripcion);
    formData.append('logo[observaciones_tecnicas]', datosLogo.observaciones_tecnicas);
    formData.append('logo[tecnicas]', JSON.stringify(datosLogo.tecnicas));
    formData.append('logo[ubicaciones]', JSON.stringify(datosLogo.ubicaciones));
    formData.append('logo[observaciones_generales]', JSON.stringify(datosLogo.observaciones_generales));
    
    console.log('📸 Datos del logo agregados a FormData');
    
    // Agregar imágenes del logo si existen en memoria
    if (window.imagenesEnMemoria && window.imagenesEnMemoria.logo && Array.isArray(window.imagenesEnMemoria.logo)) {
        window.imagenesEnMemoria.logo.forEach((imagen, idx) => {
            if (imagen instanceof File) {
                formData.append(`logo[imagenes][]`, imagen);
                console.log(`✅ Imagen de logo agregada [${idx}]:`, imagen.name);
            }
        });
    }
    
    Swal.fire({
        title: '¿Guardar pedido?',
        text: 'El pedido se guardará como borrador.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0066cc',
        cancelButtonColor: '#f0f0f0',
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch("{{ route('asesores.pedidos.store') }}", {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cerrarModalCrearPedido();
                    
                    // Mostrar Toast con opción de crear
                    mostrarToastCrear(data.borrador_id);
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Ocurrió un error al guardar el pedido',
                        icon: 'error',
                        confirmButtonColor: '#0066cc'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Ocurrió un error al guardar el pedido',
                    icon: 'error',
                    confirmButtonColor: '#0066cc'
                });
            });
        }
    });
}

// ========================================
// MOSTRAR TOAST CON OPCIÓN DE CREAR
// ========================================
function mostrarToastCrear(borradorId) {
    Swal.fire({
        position: 'top-end',
        icon: 'success',
        title: '¡Pedido guardado!',
        html: 'El pedido se guardó como borrador. <br><strong>¿Deseas crear el pedido ahora?</strong>',
        showConfirmButton: true,
        showCancelButton: true,
        confirmButtonText: 'Crear Pedido',
        cancelButtonText: 'Luego',
        confirmButtonColor: '#0066cc',
        timer: 10000,
        timerProgressBar: true
    }).then((result) => {
        if (result.isConfirmed) {
            crearPedidoFromBorrador(borradorId);
        }
    });
}

// ========================================
// CREAR PEDIDO A PARTIR DEL BORRADOR
// ========================================
function crearPedidoFromBorrador(borradorId) {
    // Obtener el siguiente número de pedido
    fetch("{{ route('asesores.next-pedido') }}")
        .then(response => response.json())
        .then(data => {
            const siguientePedido = data.siguiente_pedido;
            
            // Mostrar modal de confirmación para crear
            Swal.fire({
                title: 'Crear Pedido',
                html: `<p>Tu pedido recibirá el ID: <strong>${siguientePedido}</strong></p>
                       <p style="color: #666; font-size: 0.9rem;">Esto no se puede cambiar.</p>`,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#0066cc',
                cancelButtonColor: '#f0f0f0',
                confirmButtonText: 'Confirmar y Crear',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Llamar al método confirm del controlador
                    fetch("{{ route('asesores.pedidos.confirm') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            borrador_id: borradorId,
                            pedido: siguientePedido
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: '¡Éxito!',
                                text: `Pedido creado con ID: ${data.pedido}`,
                                icon: 'success',
                                confirmButtonColor: '#0066cc'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.message || 'Error al crear el pedido',
                                icon: 'error',
                                confirmButtonColor: '#0066cc'
                            });
                        }
                    });
                }
            });
        });
}

// ========================================
// AUTOCOMPLETE FORMA DE PAGO
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const inputFormaPago = document.getElementById('nuevoFormaPago');
    const datalist = document.getElementById('formasPagoList');
    const formasPagoStandard = ['CRÉDITO', 'CONTADO', '50/50', 'ANTICIPO'];
    let formasPersonalizadas = [];

    // Cargar formas personalizadas del localStorage
    const formasGuardadas = localStorage.getItem('formasPagoPersonalizadas');
    if (formasGuardadas) {
        formasPersonalizadas = JSON.parse(formasGuardadas);
        actualizarDatalist();
    }

    // Actualizar datalist con todas las opciones
    function actualizarDatalist() {
        datalist.innerHTML = '';
        const todasLasFormas = [...new Set([...formasPagoStandard, ...formasPersonalizadas])];
        todasLasFormas.forEach(forma => {
            const option = document.createElement('option');
            option.value = forma;
            datalist.appendChild(option);
        });
    }

    // Convertir a mayúscula mientras se escribe
    inputFormaPago?.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Al seleccionar del datalist o escribir, solo usar lo que existe
    inputFormaPago?.addEventListener('change', function() {
        const valor = this.value.trim().toUpperCase();
        const todasLasFormas = [...formasPagoStandard, ...formasPersonalizadas];
        
        // Si no existe la forma exacta, preguntar
        const existe = todasLasFormas.some(forma => forma === valor);
        
        if (!existe && valor) {
            // Mostrar sugerencia para crear
            Swal.fire({
                title: '¿Crear nueva forma de pago?',
                text: `"${valor}" no existe. ¿Deseas agregarla?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0066cc',
                cancelButtonColor: '#f0f0f0',
                confirmButtonText: 'Sí, crear',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    formasPersonalizadas.push(valor);
                    localStorage.setItem('formasPagoPersonalizadas', JSON.stringify(formasPersonalizadas));
                    actualizarDatalist();
                    inputFormaPago.value = valor;
                } else {
                    inputFormaPago.value = '';
                }
            });
        }
    });
});

