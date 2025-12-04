/**
 * SISTEMA DE COTIZACIONES - GUARDADO Y ENVÍO
 * Responsabilidad: Guardar, enviar cotizaciones y subir imágenes
 * Compatible con: localStorage (persistencia) y WebSockets (sin conflictos)
 */

// ============ GUARDAR COTIZACIÓN ============

async function guardarCotizacion() {
    console.log('='.repeat(60));
    console.log('🚀 INICIANDO GUARDADO DE COTIZACIÓN');
    console.log('   🌐 WebSockets:', window.Echo ? 'Disponible ✓' : 'No disponible');
    console.log('   💾 localStorage:', window.localStorage ? 'Disponible ✓' : 'No disponible');
    console.log('='.repeat(60));
    
    // Debug: Mostrar estado del contenedor antes de recopilar
    const contenedorDebug = document.getElementById('tecnicas_seleccionadas');
    if (contenedorDebug) {
        console.log('📊 DEBUG - Técnicas en DOM:');
        console.log('   - innerHTML:', contenedorDebug.innerHTML);
        console.log('   - children count:', contenedorDebug.children.length);
        Array.from(contenedorDebug.children).forEach((child, i) => {
            const input = child.querySelector('input[name="tecnicas[]"]');
            if (input) {
                console.log(`   - Técnica ${i + 1}:`, input.value);
            }
        });
    }
    
    const btnGuardar = document.querySelector('button[onclick="guardarCotizacion()"]');
    const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
    
    if (btnGuardar) btnGuardar.disabled = true;
    if (btnEnviar) btnEnviar.disabled = true;
    
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
    
    // Validar que tipo_cotizacion esté seleccionado
    const tipoCotizacionSelect = document.getElementById('tipo_cotizacion');
    const tipoCotizacion = tipoCotizacionSelect ? tipoCotizacionSelect.value : '';
    
    if (!tipoCotizacion) {
        Swal.fire({
            title: 'Tipo de cotización requerido',
            text: 'Por favor selecciona el tipo de cotización (M/D/X)',
            icon: 'warning',
            confirmButtonColor: '#1e40af'
        });
        if (btnGuardar) btnGuardar.disabled = false;
        if (btnEnviar) btnEnviar.disabled = false;
        return;
    }
    
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
    
    console.log('🔵 guardarCotizacion() llamado');
    console.log('📸 Imágenes en memoria:', {
        prendaConIndice: window.imagenesEnMemoria.prendaConIndice ? window.imagenesEnMemoria.prendaConIndice.length : 0,
        telaConIndice: window.imagenesEnMemoria.telaConIndice ? window.imagenesEnMemoria.telaConIndice.length : 0,
        logo: window.imagenesEnMemoria.logo.length
    });
    
    try {
        const payloadEnvio = {
            tipo: 'borrador',
            cliente: datos.cliente,
            tipo_venta: tipoCotizacion,
            productos: datos.productos,
            tecnicas: datos.tecnicas,
            observaciones_tecnicas: datos.observaciones_tecnicas,
            ubicaciones: datos.ubicaciones,
            observaciones_generales: datos.observaciones_generales,
            observaciones_check: datos.observaciones_check,
            observaciones_valor: datos.observaciones_valor,
            especificaciones: datos.especificaciones || {}
        };
        
        console.log('📤 PAYLOAD A ENVIAR:', payloadEnvio);
        console.log('📤 Especificaciones en payload:', payloadEnvio.especificaciones);
        console.log('📤 ¿Especificaciones vacías?', Object.keys(payloadEnvio.especificaciones).length === 0);
        
        const response = await fetch(window.routes.guardarCotizacion, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(payloadEnvio)
        });
        
        console.log('📡 Status de respuesta:', response.status);
        console.log('📡 Content-Type:', response.headers.get('content-type'));
        
        const responseText = await response.text();
        console.log('📡 Texto de respuesta:', responseText);
        
        // Intentar parsear como JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Error al parsear JSON:', parseError);
            console.error('📄 Respuesta completa:', responseText.substring(0, 500));
            
            Swal.fire({
                title: 'Error del servidor',
                html: '<p>El servidor retornó una respuesta inválida.</p><p style="font-size: 0.8rem; color: #999; margin-top: 10px; word-break: break-all;">' + 
                      responseText.substring(0, 300) + '</p>',
                icon: 'error',
                confirmButtonColor: '#1e40af'
            });
            if (btnGuardar) btnGuardar.disabled = false;
            if (btnEnviar) btnEnviar.disabled = false;
            return;
        }
        
        if (data.success && data.cotizacion_id) {
            console.log('✅ Cotización creada con ID:', data.cotizacion_id);
            
            // Contar imágenes: prendaConIndice, telaConIndice, y logo
            const cantPrenda = window.imagenesEnMemoria.prendaConIndice ? window.imagenesEnMemoria.prendaConIndice.length : 0;
            const cantTela = window.imagenesEnMemoria.telaConIndice ? window.imagenesEnMemoria.telaConIndice.length : 0;
            const cantLogo = window.imagenesEnMemoria.logo ? window.imagenesEnMemoria.logo.length : 0;
            const totalImagenes = cantPrenda + cantTela + cantLogo;
            
            console.log(`📸 Total imágenes: ${cantPrenda} prenda + ${cantTela} tela + ${cantLogo} logo = ${totalImagenes}`);
            
            if (totalImagenes > 0) {
                console.log('📸 Subiendo', totalImagenes, 'imágenes...');
                
                if (cantPrenda > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.prendaConIndice, 'prenda');
                }
                if (cantTela > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.telaConIndice, 'tela');
                }
                if (cantLogo > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.logo, 'logo');
                }
            }
            
            // Limpiar localStorage después del guardado exitoso
            if (typeof limpiarStorage === 'function') {
                limpiarStorage();
                console.log('✓ localStorage limpiado después del guardado');
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
        console.error('❌ Error en fetch:', error);
        Swal.fire({
            title: 'Error',
            text: 'Error al guardar la cotización: ' + error.message,
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
    
    // Validar que el tipo de cotización esté seleccionado
    const tipoCotizacionSelect = document.getElementById('tipo_cotizacion');
    const tipoCotizacion = tipoCotizacionSelect ? tipoCotizacionSelect.value : '';
    
    if (!tipoCotizacion) {
        Swal.fire({
            title: 'Tipo de cotización requerido',
            text: 'Por favor selecciona el tipo de cotización (M/D/X)',
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
    
    // LOG DETALLADO DE VARIANTES
    if (datos.productos && datos.productos.length > 0) {
        console.log('🔍 DETALLE DE VARIANTES A ENVIAR:');
        datos.productos.forEach((prod, idx) => {
            console.log(`  Producto ${idx}:`, JSON.stringify(prod.variantes, null, 2));
        });
    }
    
    try {
        const payloadEnvio = {
            tipo: 'enviada',
            cliente: datos.cliente,
            tipo_venta: tipoCotizacion,
            productos: datos.productos,
            tecnicas: datos.tecnicas,
            observaciones_tecnicas: datos.observaciones_tecnicas,
            ubicaciones: datos.ubicaciones,
            observaciones_generales: datos.observaciones_generales,
            observaciones_check: datos.observaciones_check,
            observaciones_valor: datos.observaciones_valor,
            imagenes: datos.logo?.imagenes || [],
            especificaciones: especificaciones
        };
        
        console.log('📤 PAYLOAD A ENVIAR (ENVIAR):', payloadEnvio);
        console.log('📤 Especificaciones en payload:', payloadEnvio.especificaciones);
        console.log('📤 ¿Especificaciones vacías?', Object.keys(payloadEnvio.especificaciones).length === 0);
        
        const response = await fetch(window.routes.guardarCotizacion, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(payloadEnvio)
        });
        
        console.log('📡 Status de respuesta:', response.status);
        console.log('📡 Content-Type:', response.headers.get('content-type'));
        
        const responseText = await response.text();
        console.log('📡 Texto de respuesta:', responseText);
        
        // Intentar parsear como JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Error al parsear JSON:', parseError);
            console.error('📄 Respuesta completa:', responseText.substring(0, 500));
            
            Swal.fire({
                title: 'Error del servidor',
                html: '<p>El servidor retornó una respuesta inválida.</p><p style="font-size: 0.8rem; color: #999; margin-top: 10px; word-break: break-all;">' + 
                      responseText.substring(0, 300) + '</p>',
                icon: 'error',
                confirmButtonColor: '#1e40af'
            });
            if (btnGuardar) btnGuardar.disabled = false;
            if (btnEnviar) btnEnviar.disabled = false;
            return;
        }
        
        if (data.success && data.cotizacion_id) {
            console.log('✅ Cotización creada con ID:', data.cotizacion_id);
            
            // Contar imágenes: prendaConIndice, telaConIndice, y logo
            const cantPrenda = window.imagenesEnMemoria.prendaConIndice ? window.imagenesEnMemoria.prendaConIndice.length : 0;
            const cantTela = window.imagenesEnMemoria.telaConIndice ? window.imagenesEnMemoria.telaConIndice.length : 0;
            const cantLogo = window.imagenesEnMemoria.logo ? window.imagenesEnMemoria.logo.length : 0;
            const totalImagenes = cantPrenda + cantTela + cantLogo;
            
            console.log(`📸 Total imágenes: ${cantPrenda} prenda + ${cantTela} tela + ${cantLogo} logo = ${totalImagenes}`);
            
            if (totalImagenes > 0) {
                if (cantPrenda > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.prendaConIndice, 'prenda');
                }
                if (cantTela > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.telaConIndice, 'tela');
                }
                if (cantLogo > 0) {
                    await subirImagenesAlServidor(data.cotizacion_id, window.imagenesEnMemoria.logo, 'logo');
                }
            }
            
            // Limpiar localStorage después del envío exitoso
            if (typeof limpiarStorage === 'function') {
                limpiarStorage();
                console.log('✓ localStorage limpiado después del envío');
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
        console.error('❌ Error en fetch:', error);
        Swal.fire({
            title: 'Error',
            text: 'Error al enviar la cotización: ' + error.message,
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

// ============ INICIALIZACIÓN DE VALIDACIÓN DE TIPO COTIZACIÓN ============

document.addEventListener('DOMContentLoaded', function() {
    // Obtener elementos
    const tipoCotizacionSelect = document.getElementById('tipo_cotizacion');
    const btnGuardar = document.querySelector('button[onclick="guardarCotizacion()"]');
    const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
    
    // Función para actualizar estado de botones
    function actualizarEstadoBotones() {
        const tipoSeleccionado = tipoCotizacionSelect && tipoCotizacionSelect.value;
        const deshabilitado = !tipoSeleccionado;
        
        if (btnGuardar) {
            btnGuardar.disabled = deshabilitado;
            btnGuardar.style.opacity = deshabilitado ? '0.5' : '1';
            btnGuardar.style.cursor = deshabilitado ? 'not-allowed' : 'pointer';
            btnGuardar.title = deshabilitado ? 'Selecciona un tipo de cotización (M, D, X) para continuar' : '';
        }
        
        if (btnEnviar) {
            btnEnviar.disabled = deshabilitado;
            btnEnviar.style.opacity = deshabilitado ? '0.5' : '1';
            btnEnviar.style.cursor = deshabilitado ? 'not-allowed' : 'pointer';
            btnEnviar.title = deshabilitado ? 'Selecciona un tipo de cotización (M, D, X) para continuar' : '';
        }
    }
    
    // Deshabilitar botones inicialmente
    if (tipoCotizacionSelect) {
        actualizarEstadoBotones();
        
        // Escuchar cambios en el select
        tipoCotizacionSelect.addEventListener('change', actualizarEstadoBotones);
    }
});
