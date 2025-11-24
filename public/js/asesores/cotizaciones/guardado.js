/**
 * SISTEMA DE COTIZACIONES - GUARDADO Y ENVÍO
 * Responsabilidad: Guardar, enviar cotizaciones y subir imágenes
 */

// ============ GUARDAR COTIZACIÓN ============

async function guardarCotizacion() {
    const btnGuardar = document.querySelector('button[onclick="guardarCotizacion()"]');
    const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
    
    if (btnGuardar) btnGuardar.disabled = true;
    if (btnEnviar) btnEnviar.disabled = true;
    
    Swal.fire({
        title: 'Guardando...',
        html: '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;"><div style="width: 12px; height: 12px; border-radius: 50%; background: #1e40af; animation: pulse 1.5s infinite;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #1e40af; animation: pulse 1.5s infinite 0.3s;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #1e40af; animation: pulse 1.5s infinite 0.6s;"></div></div><style>@keyframes pulse { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }</style>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: (modal) => {
            modal.style.pointerEvents = 'none';
        }
    });
    
    const datos = recopilarDatos();
    
    if (!datos) {
        Swal.fire({
            title: 'Error',
            text: 'No se pudieron recopilar los datos del formulario',
            icon: 'error',
            confirmButtonColor: '#1e40af'
        });
        if (btnGuardar) btnGuardar.disabled = false;
        if (btnEnviar) btnEnviar.disabled = false;
        return;
    }
    
    console.log('🔵 guardarCotizacion() llamado');
    console.log('📸 Imágenes en memoria:', {
        prendaConIndice: window.imagenesEnMemoria.prendaConIndice ? window.imagenesEnMemoria.prendaConIndice.length : 0,
        telaConIndice: window.imagenesEnMemoria.telaConIndice ? window.imagenesEnMemoria.telaConIndice.length : 0,
        general: window.imagenesEnMemoria.general.length
    });
    
    try {
        // Obtener tipo de cotización
        const tipoCotizacionSelect = document.getElementById('tipo_cotizacion');
        const tipoCotizacion = tipoCotizacionSelect ? tipoCotizacionSelect.value : '';
        
        // Obtener especificaciones (puede ser objeto o array)
        const especificaciones = window.especificacionesSeleccionadas || {};
        
        console.log('📋 Tipo de cotización:', tipoCotizacion);
        console.log('📋 Especificaciones guardadas en window:', window.especificacionesSeleccionadas);
        console.log('📋 Especificaciones a enviar:', especificaciones);
        console.log('📋 ¿Especificaciones vacías?', Object.keys(especificaciones).length === 0);
        console.log('📋 Productos:', datos.productos);
        
        const response = await fetch(window.routes.guardarCotizacion, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
                tipo: 'borrador',
                cliente: datos.cliente,
                tipo_cotizacion: tipoCotizacion,
                productos: datos.productos,
                tecnicas: datos.tecnicas,
                observaciones_tecnicas: datos.observaciones_tecnicas,
                ubicaciones: datos.ubicaciones,
                observaciones_generales: datos.observaciones_generales,
                observaciones_check: datos.observaciones_check,
                observaciones_valor: datos.observaciones_valor,
                especificaciones: especificaciones
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.cotizacion_id) {
            console.log('✅ Cotización creada con ID:', data.cotizacion_id);
            
            // Contar imágenes: prendaConIndice, telaConIndice, y general
            const cantPrenda = window.imagenesEnMemoria.prendaConIndice ? window.imagenesEnMemoria.prendaConIndice.length : 0;
            const cantTela = window.imagenesEnMemoria.telaConIndice ? window.imagenesEnMemoria.telaConIndice.length : 0;
            const cantGeneral = window.imagenesEnMemoria.general ? window.imagenesEnMemoria.general.length : 0;
            const totalImagenes = cantPrenda + cantTela + cantGeneral;
            
            console.log(`📸 Total imágenes: ${cantPrenda} prenda + ${cantTela} tela + ${cantGeneral} general = ${totalImagenes}`);
            
            if (totalImagenes > 0) {
                console.log('📸 Subiendo', totalImagenes, 'imágenes...');
                
                if (cantPrenda > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.prendaConIndice, 'prenda');
                }
                if (cantTela > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.telaConIndice, 'tela');
                }
                if (cantGeneral > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.general, 'general');
                }
            }
            
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '¡Cotización guardada en borradores!',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            
            setTimeout(() => {
                window.location.href = window.routes.cotizacionesIndex + '#borradores';
            }, 2000);
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Error al guardar: ' + (data.message || 'Error desconocido'),
                icon: 'error',
                confirmButtonColor: '#1e40af'
            });
        }
    } catch (error) {
        console.error('❌ Error:', error);
        Swal.fire({
            title: 'Error',
            text: 'Error al guardar la cotización',
            icon: 'error',
            confirmButtonColor: '#1e40af'
        });
    }
}

// ============ SUBIR IMÁGENES ============

async function subirImagenesAlServidor(cotizacionId, archivos, tipo) {
    console.log(`📤 Subiendo ${archivos.length} imágenes de tipo "${tipo}"...`);
    
    const formData = new FormData();
    
    // Si es prenda y tenemos información de índice, usar eso
    if (tipo === 'prenda' && Array.isArray(archivos) && archivos.length > 0 && archivos[0].prendaIndex !== undefined) {
        archivos.forEach((item, index) => {
            formData.append('imagenes[]', item.file);
            formData.append(`prendaIndex[${index}]`, item.prendaIndex);
        });
        console.log('📤 Enviando prendas con índices:', archivos.map(p => p.prendaIndex));
    } 
    // Si es tela y tenemos información de índice, usar eso
    else if (tipo === 'tela' && Array.isArray(archivos) && archivos.length > 0 && archivos[0].prendaIndex !== undefined) {
        archivos.forEach((item, index) => {
            formData.append('imagenes[]', item.file);
            formData.append(`prendaIndex[${index}]`, item.prendaIndex);
        });
        console.log('📤 Enviando telas con índices de prenda:', archivos.map(t => t.prendaIndex));
    } 
    // Para otros tipos, enviar normalmente
    else {
        archivos.forEach((file) => {
            formData.append('imagenes[]', file);
        });
    }
    
    formData.append('tipo', tipo);
    
    try {
        const response = await fetch(`/asesores/cotizaciones/${cotizacionId}/imagenes`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        const data = await response.json();
        if (data.success) {
            console.log(`✅ ${archivos.length} imágenes de tipo "${tipo}" guardadas`);
        } else {
            console.error(`❌ Error al guardar imágenes de tipo "${tipo}":`, data.message);
        }
    } catch (error) {
        console.error(`❌ Error al subir imágenes de tipo "${tipo}":`, error);
    }
}

// ============ ENVIAR COTIZACIÓN ============

async function enviarCotizacion() {
    const datos = recopilarDatos();
    
    if (!datos) {
        Swal.fire({
            title: 'Error',
            text: 'No se pudieron recopilar los datos del formulario',
            icon: 'error',
            confirmButtonColor: '#1e40af'
        });
        return;
    }
    
    if (!datos.cliente.trim()) {
        Swal.fire({
            title: 'Campo requerido',
            text: 'Por favor ingresa el nombre del cliente',
            icon: 'warning',
            confirmButtonColor: '#1e40af'
        });
        return;
    }
    
    if (datos.productos.length === 0) {
        Swal.fire({
            title: 'Productos requeridos',
            text: 'Por favor agrega al menos un producto',
            icon: 'warning',
            confirmButtonColor: '#1e40af'
        });
        return;
    }
    
    Swal.fire({
        title: '¿Listo para enviar?',
        html: '<p style="margin: 0; font-size: 0.95rem; color: #4b5563;">Una vez enviada la cotización <span style="color: #ef4444; font-weight: 700;">no podrá editarse</span>.</p>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d1d5db',
        confirmButtonText: 'Sí, enviar',
        cancelButtonText: 'Revisar primero'
    }).then((result) => {
        if (result.isConfirmed) {
            procederEnviarCotizacion(datos);
        }
    });
}

async function procederEnviarCotizacion(datos) {
    const btnGuardar = document.querySelector('button[onclick="guardarCotizacion()"]');
    const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
    
    if (btnGuardar) btnGuardar.disabled = true;
    if (btnEnviar) btnEnviar.disabled = true;
    
    Swal.fire({
        title: 'Enviando...',
        html: '<div style="display: flex; justify-content: center; align-items: center; gap: 10px;"><div style="width: 12px; height: 12px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite 0.3s;"></div><div style="width: 12px; height: 12px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite 0.6s;"></div></div><style>@keyframes pulse { 0%, 100% { opacity: 0.3; } 50% { opacity: 1; } }</style>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false
    });
    
    console.log('🔵 enviarCotizacion() llamado');
    
    // Obtener tipo de cotización
    const tipoCotizacionSelect = document.getElementById('tipo_cotizacion');
    const tipoCotizacion = tipoCotizacionSelect ? tipoCotizacionSelect.value : '';
    
    // Obtener especificaciones (puede ser objeto o array)
    const especificaciones = window.especificacionesSeleccionadas || {};
    
    console.log('📋 Tipo de cotización:', tipoCotizacion);
    console.log('📋 Especificaciones guardadas en window:', window.especificacionesSeleccionadas);
    console.log('📋 Especificaciones a enviar:', especificaciones);
    console.log('📋 ¿Especificaciones vacías?', Object.keys(especificaciones).length === 0);
    console.log('📋 Productos:', datos.productos);
    
    try {
        const response = await fetch(window.routes.guardarCotizacion, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
                tipo: 'enviada',
                cliente: datos.cliente,
                tipo_cotizacion: tipoCotizacion,
                productos: datos.productos,
                tecnicas: datos.tecnicas,
                observaciones_tecnicas: datos.observaciones_tecnicas,
                ubicaciones: datos.ubicaciones,
                observaciones_generales: datos.observaciones_generales,
                observaciones_check: datos.observaciones_check,
                observaciones_valor: datos.observaciones_valor,
                imagenes: datos.logo?.imagenes || [],
                especificaciones: especificaciones
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.cotizacion_id) {
            console.log('✅ Cotización creada con ID:', data.cotizacion_id);
            
            // Contar imágenes: prendaConIndice, telaConIndice, y general
            const cantPrenda = window.imagenesEnMemoria.prendaConIndice ? window.imagenesEnMemoria.prendaConIndice.length : 0;
            const cantTela = window.imagenesEnMemoria.telaConIndice ? window.imagenesEnMemoria.telaConIndice.length : 0;
            const cantGeneral = window.imagenesEnMemoria.general ? window.imagenesEnMemoria.general.length : 0;
            const totalImagenes = cantPrenda + cantTela + cantGeneral;
            
            console.log(`📸 Total imágenes: ${cantPrenda} prenda + ${cantTela} tela + ${cantGeneral} general = ${totalImagenes}`);
            
            if (totalImagenes > 0) {
                if (cantPrenda > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.prendaConIndice, 'prenda');
                }
                if (cantTela > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.telaConIndice, 'tela');
                }
                if (cantGeneral > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.general, 'general');
                }
            }
            
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '¡Cotización enviada!',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            
            setTimeout(() => {
                window.location.href = window.routes.cotizacionesIndex + '#cotizaciones';
            }, 2000);
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Error al enviar: ' + (data.message || 'Error desconocido'),
                icon: 'error',
                confirmButtonColor: '#1e40af'
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error',
            text: 'Error al enviar la cotización',
            icon: 'error',
            confirmButtonColor: '#1e40af'
        });
    }
}

// ============ TOGGLE APLICA/NO APLICA ============

function toggleAplicaPaso(paso, btn) {
    const isAplica = btn.textContent.trim() === 'APLICA';
    
    if (isAplica) {
        // Cambiar a "NO APLICA"
        btn.textContent = 'NO APLICA';
        btn.style.background = '#ffc107';
        btn.style.color = '#333';
        
        // Ir al siguiente paso
        if (paso === 2) {
            irAlPaso(3);
        } else if (paso === 3) {
            irAlPaso(4);
        }
    } else {
        // Cambiar a "APLICA"
        btn.textContent = 'APLICA';
        btn.style.background = '#10b981';
        btn.style.color = 'white';
    }
}
