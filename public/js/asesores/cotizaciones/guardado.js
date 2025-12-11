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
    
    // 📸 Procesar imágenes a Base64
    console.log('🖼️ Procesando imágenes a Base64...');
    try {
        const datosConImagenes = await procesarImagenesABase64(datos);
        console.log('✅ Imágenes procesadas correctamente');
        Object.assign(datos, datosConImagenes);
    } catch (error) {
        console.error('❌ Error al procesar imágenes:', error);
        Swal.fire({
            title: 'Error al procesar imágenes',
            text: 'No se pudieron convertir las imágenes. ' + error.message,
            icon: 'error',
            confirmButtonColor: '#1e40af'
        });
        if (btnGuardar) btnGuardar.disabled = false;
        if (btnEnviar) btnEnviar.disabled = false;
        return;
    }
    
    // Validar que tipo_venta esté seleccionado
    const tipoVentaSelect = document.getElementById('tipo_venta');
    const tipoVenta = tipoVentaSelect ? tipoVentaSelect.value : '';
    
    if (!tipoVenta) {
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
        // ✅ USAR FormData PARA ENVIAR ARCHIVOS File
        const formData = new FormData();
        
        // Datos básicos
        formData.append('tipo', 'borrador');
        formData.append('cliente', datos.cliente);
        formData.append('tipo_venta', tipoVenta);
        formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
        
        // Secciones de texto
        formData.append('descripcion_logo', datos.descripcion_logo || '');
        formData.append('tecnicas', JSON.stringify(datos.tecnicas || []));
        formData.append('observaciones_tecnicas', datos.observaciones_tecnicas || '');
        formData.append('ubicaciones', JSON.stringify(datos.ubicaciones || []));
        formData.append('observaciones_generales', JSON.stringify(datos.observaciones_generales || []));
        
        formData.append('especificaciones', JSON.stringify(datos.especificaciones || {}));
        
        // ✅ PRENDAS CON ARCHIVOS File
        if (datos.productos && Array.isArray(datos.productos)) {
            datos.productos.forEach((producto, index) => {
                // Datos de prenda
                formData.append(`prendas[${index}][nombre_producto]`, producto.nombre_producto || '');
                formData.append(`prendas[${index}][descripcion]`, producto.descripcion || '');
                formData.append(`prendas[${index}][cantidad]`, producto.cantidad || 1);
                formData.append(`prendas[${index}][tallas]`, JSON.stringify(producto.tallas || []));
                
                // Variantes como array (no JSON string)
                const variantes = producto.variantes || {};
                Object.keys(variantes).forEach(key => {
                    const value = variantes[key];
                    if (key === 'telas_multiples' && Array.isArray(value)) {
                        // Caso especial: telas_multiples es un array de objetos
                        // Enviar como JSON string completo
                        formData.append(`prendas[${index}][variantes][${key}]`, JSON.stringify(value));
                    } else if (Array.isArray(value)) {
                        // Si es array (pero no telas_multiples), agregar cada elemento
                        value.forEach((item, idx) => {
                            if (typeof item === 'object' && item !== null) {
                                formData.append(`prendas[${index}][variantes][${key}][${idx}]`, JSON.stringify(item));
                            } else {
                                formData.append(`prendas[${index}][variantes][${key}][${idx}]`, item);
                            }
                        });
                    } else if (typeof value === 'object' && value !== null) {
                        // Si es objeto, convertir a JSON string
                        formData.append(`prendas[${index}][variantes][${key}]`, JSON.stringify(value));
                    } else if (typeof value === 'boolean') {
                        // Convertir booleanos a 1/0 para Laravel
                        formData.append(`prendas[${index}][variantes][${key}]`, value ? '1' : '0');
                    } else {
                        // Si es valor simple, agregar directamente
                        formData.append(`prendas[${index}][variantes][${key}]`, value || '');
                    }
                });
                
                // ✅ FOTOS (File objects o Base64 strings)
                if (producto.fotos && Array.isArray(producto.fotos)) {
                    producto.fotos.forEach((foto, fotoIndex) => {
                        if (foto instanceof File) {
                            formData.append(`prendas[${index}][fotos][]`, foto);
                            console.log(`✅ Foto (File) agregada a FormData [${index}][${fotoIndex}]:`, foto.name);
                        } else if (typeof foto === 'string') {
                            formData.append(`prendas[${index}][fotos_base64]`, foto);
                            console.log(`✅ Foto (Base64) agregada a FormData [${index}][${fotoIndex}]`);
                        }
                    });
                }
                
                // ✅ TELAS (File objects o Base64 strings)
                if (producto.telas && Array.isArray(producto.telas)) {
                    producto.telas.forEach((tela, telaIndex) => {
                        if (tela instanceof File) {
                            formData.append(`prendas[${index}][telas][]`, tela);
                            console.log(`✅ Tela (File) agregada a FormData [${index}][${telaIndex}]:`, tela.name);
                        } else if (typeof tela === 'string') {
                            formData.append(`prendas[${index}][telas_base64]`, tela);
                            console.log(`✅ Tela (Base64) agregada a FormData [${index}][${telaIndex}]`);
                        }
                    });
                }
            });
        }
        
        // ✅ LOGO - IMÁGENES (File objects)
        if (datos.logo && datos.logo.imagenes && Array.isArray(datos.logo.imagenes)) {
            datos.logo.imagenes.forEach((imagen, imagenIndex) => {
                if (imagen instanceof File) {
                    formData.append(`logo[imagenes][]`, imagen);
                    console.log(`✅ Imagen de logo agregada a FormData [${imagenIndex}]:`, imagen.name);
                }
            });
        }
        
        console.log('📤 FORMDATA A ENVIAR:', {
            tipo: 'borrador',
            cliente: datos.cliente,
            tipo_venta: tipoVenta,
            productos_count: datos.productos?.length || 0,
            tecnicas: datos.tecnicas?.length || 0,
            especificaciones_keys: Object.keys(datos.especificaciones || {})
        });
        
        const response = await fetch(window.routes.guardarCotizacion, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                // ⚠️ NO incluir 'Content-Type': 'application/json' - FormData lo establece automáticamente
            },
            body: formData
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
            console.log('✅ Imágenes procesadas y guardadas en el servidor');
            
            // ✅ LIMPIAR TODO DESPUÉS DEL GUARDADO EXITOSO
            if (typeof limpiarFormularioCompleto === 'function') {
                limpiarFormularioCompleto();
            } else if (typeof limpiarStorage === 'function') {
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
            // Construir mensaje de error detallado
            let mensajeError = data.message || 'Error desconocido';
            let htmlError = `<p>${mensajeError}</p>`;
            
            // Si hay errores de validación, mostrarlos
            if (data.validation_errors) {
                htmlError += '<div style="text-align: left; margin-top: 10px;">';
                for (const [campo, errores] of Object.entries(data.validation_errors)) {
                    if (Array.isArray(errores)) {
                        errores.forEach(error => {
                            htmlError += `<p style="margin: 5px 0; font-size: 0.9rem;"><strong>${campo}:</strong> ${error}</p>`;
                        });
                    }
                }
                htmlError += '</div>';
            }
            
            console.error('❌ Error en la respuesta:', data);
            
            Swal.fire({
                title: 'Error al guardar',
                html: htmlError,
                icon: 'error',
                confirmButtonColor: '#1e40af',
                width: '600px'
            });
        }
    } catch (error) {
        console.error('❌ Error en fetch:', error);
        Swal.fire({
            title: 'Error de conexión',
            html: `<p>No se pudo completar la solicitud:</p>
                   <p style="font-size: 0.9rem; color: #d32f2f; margin-top: 10px;">${error.message}</p>`,
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
    
    // 📸 NO convertir a Base64 - mantener File objects
    // Las imágenes se enviarán directamente como archivos en FormData
    console.log('🖼️ Imágenes se enviarán como File objects (sin convertir a Base64)...');
    
    if (!datos.cliente.trim()) {
        Swal.fire({
            title: 'Campo requerido',
            text: 'Por favor ingresa el nombre del cliente',
            icon: 'warning',
            confirmButtonColor: '#1e40af'
        });
        return;
    }
    
    // Validar que el tipo de venta esté seleccionado
    const tipoVentaSelect = document.getElementById('tipo_venta');
    const tipoVenta = tipoVentaSelect ? tipoVentaSelect.value : '';
    
    if (!tipoVenta) {
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
    
    // ✅ VALIDAR ESPECIFICACIONES
    const especificaciones = window.especificacionesSeleccionadas || {};
    const tieneEspecificaciones = Object.keys(especificaciones).length > 0;
    
    if (!tieneEspecificaciones) {
        // Marcar botón flotante en rojo como recordatorio
        const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
        if (btnEnviar) {
            btnEnviar.style.background = '#ef4444';
            btnEnviar.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.3)';
        }
        
        Swal.fire({
            title: '🚫 ESPECIFICACIONES REQUERIDAS',
            html: `
                <div style="text-align: left; margin: 20px 0;">
                    <p style="margin: 0 0 15px 0; font-size: 1rem; color: #ef4444; font-weight: bold;">
                        ⚠️ No puedes enviar sin completar las especificaciones
                    </p>
                    <p style="margin: 0 0 15px 0; font-size: 0.9rem; color: #666;">
                        Las especificaciones son <strong>OBLIGATORIAS</strong> para que el cliente entienda todos los detalles de su pedido.
                    </p>
                    <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 12px; border-radius: 4px; margin: 15px 0;">
                        <p style="margin: 0 0 8px 0; font-size: 0.85rem; color: #991b1b; font-weight: bold;">
                            📋 DEBES COMPLETAR AL MENOS UNA:
                        </p>
                        <p style="margin: 0; font-size: 0.85rem; color: #991b1b;">
                            ✓ Régimen<br>
                            ✓ Se ha vendido<br>
                            ✓ Última venta<br>
                            ✓ Flete de envío
                        </p>
                    </div>
                    <p style="margin: 15px 0 0 0; font-size: 0.9rem; color: #666;">
                        Haz clic en <strong>"Ir a Especificaciones"</strong> para completarlas ahora.
                    </p>
                </div>
            `,
            icon: 'error',
            showCancelButton: false,
            confirmButtonColor: '#3498db',
            confirmButtonText: '✓ Ir a Especificaciones',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Ir a PASO 2 automáticamente
                irAlPaso(2);
                
                // Abrir modal de especificaciones
                setTimeout(() => {
                    abrirModalEspecificaciones();
                }, 300);
                
                // Mostrar toast recordatorio
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: '📋 Completa las especificaciones y haz clic en GUARDAR',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true
                });
            }
        });
        return;
    }
    
    // Si hay especificaciones, cambiar botón a verde
    const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
    if (btnEnviar) {
        btnEnviar.style.background = '';
        btnEnviar.style.boxShadow = '';
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
    
    console.log('🔵 procederEnviarCotizacion() llamado');
    
    // ✅ NO convertir a Base64 - enviar archivos directamente como File objects
    // Base64 es ineficiente (aumenta tamaño 33%) y mala práctica
    console.log('📁 Enviando archivos directamente como File objects (multipart/form-data)');
    
    // Obtener tipo de venta
    const tipoVentaSelect = document.getElementById('tipo_venta');
    const tipoVentaValue = tipoVentaSelect ? tipoVentaSelect.value : '';
    
    // Obtener especificaciones (puede ser objeto o array)
    const especificaciones = window.especificacionesSeleccionadas || {};
    
    console.log('📋 Tipo de venta:', tipoVentaValue);
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
        // ✅ USAR FormData PARA ENVIAR ARCHIVOS File
        const formData = new FormData();
        
        // Datos básicos
        formData.append('tipo', 'enviada');
        formData.append('cliente', datos.cliente);
        formData.append('tipo_venta', tipoVentaValue);
        formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
        
        // Secciones de texto
        formData.append('tecnicas', JSON.stringify(datos.tecnicas || []));
        formData.append('observaciones_tecnicas', datos.observaciones_tecnicas || '');
        formData.append('ubicaciones', JSON.stringify(datos.ubicaciones || []));
        formData.append('observaciones_generales', JSON.stringify(datos.observaciones_generales || []));
        
        // Enviar observaciones_check y observaciones_valor como arrays (no JSON strings)
        const obsCheck = datos.observaciones_check || [];
        const obsValor = datos.observaciones_valor || [];
        
        // Agregar cada elemento del array por separado
        obsCheck.forEach((item, idx) => {
            formData.append(`observaciones_check[${idx}]`, item || '');
        });
        obsValor.forEach((item, idx) => {
            formData.append(`observaciones_valor[${idx}]`, item || '');
        });
        
        formData.append('especificaciones', JSON.stringify(especificaciones || {}));
        formData.append('imagenes', JSON.stringify(datos.logo?.imagenes || []));
        
        // ✅ PRENDAS CON ARCHIVOS File
        if (datos.productos && Array.isArray(datos.productos)) {
            datos.productos.forEach((producto, index) => {
                // Datos de prenda
                formData.append(`prendas[${index}][nombre_producto]`, producto.nombre_producto || '');
                formData.append(`prendas[${index}][descripcion]`, producto.descripcion || '');
                formData.append(`prendas[${index}][cantidad]`, producto.cantidad || 1);
                formData.append(`prendas[${index}][tallas]`, JSON.stringify(producto.tallas || []));
                
                // Variantes como array (no JSON string)
                const variantes = producto.variantes || {};
                Object.keys(variantes).forEach(key => {
                    const value = variantes[key];
                    if (key === 'telas_multiples' && Array.isArray(value)) {
                        // Caso especial: telas_multiples es un array de objetos
                        // Enviar como JSON string completo
                        formData.append(`prendas[${index}][variantes][${key}]`, JSON.stringify(value));
                    } else if (Array.isArray(value)) {
                        // Si es array (pero no telas_multiples), agregar cada elemento
                        value.forEach((item, idx) => {
                            if (typeof item === 'object' && item !== null) {
                                formData.append(`prendas[${index}][variantes][${key}][${idx}]`, JSON.stringify(item));
                            } else {
                                formData.append(`prendas[${index}][variantes][${key}][${idx}]`, item);
                            }
                        });
                    } else if (typeof value === 'object' && value !== null) {
                        // Si es objeto, convertir a JSON string
                        formData.append(`prendas[${index}][variantes][${key}]`, JSON.stringify(value));
                    } else if (typeof value === 'boolean') {
                        // Convertir booleanos a 1/0 para Laravel
                        formData.append(`prendas[${index}][variantes][${key}]`, value ? '1' : '0');
                    } else {
                        // Si es valor simple, agregar directamente
                        formData.append(`prendas[${index}][variantes][${key}]`, value || '');
                    }
                });
                
                // ✅ FOTOS (File objects o Base64 strings)
                if (producto.fotos && Array.isArray(producto.fotos)) {
                    producto.fotos.forEach((foto, fotoIndex) => {
                        if (foto instanceof File) {
                            formData.append(`prendas[${index}][fotos][]`, foto);
                            console.log(`✅ Foto (File) agregada a FormData [${index}][${fotoIndex}]:`, foto.name);
                        } else if (typeof foto === 'string') {
                            formData.append(`prendas[${index}][fotos_base64]`, foto);
                            console.log(`✅ Foto (Base64) agregada a FormData [${index}][${fotoIndex}]`);
                        }
                    });
                }
                
                // ✅ TELAS (File objects o Base64 strings)
                if (producto.telas && Array.isArray(producto.telas)) {
                    producto.telas.forEach((tela, telaIndex) => {
                        if (tela instanceof File) {
                            formData.append(`prendas[${index}][telas][]`, tela);
                            console.log(`✅ Tela (File) agregada a FormData [${index}][${telaIndex}]:`, tela.name);
                        } else if (typeof tela === 'string') {
                            formData.append(`prendas[${index}][telas_base64]`, tela);
                            console.log(`✅ Tela (Base64) agregada a FormData [${index}][${telaIndex}]`);
                        }
                    });
                }
            });
        }
        
        // ✅ LOGO - IMÁGENES (File objects)
        if (datos.logo && datos.logo.imagenes && Array.isArray(datos.logo.imagenes)) {
            datos.logo.imagenes.forEach((imagen, imagenIndex) => {
                if (imagen instanceof File) {
                    formData.append(`logo[imagenes][]`, imagen);
                    console.log(`✅ Imagen de logo agregada a FormData [${imagenIndex}]:`, imagen.name);
                }
            });
        }
        
        console.log('📤 FORMDATA A ENVIAR (ENVIAR):', {
            tipo: 'enviada',
            cliente: datos.cliente,
            tipo_venta: tipoVentaValue,
            productos_count: datos.productos?.length || 0,
            tecnicas: datos.tecnicas?.length || 0,
            especificaciones_keys: Object.keys(especificaciones || {})
        });
        
        const response = await fetch(window.routes.guardarCotizacion, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                // ⚠️ NO incluir 'Content-Type': 'application/json' - FormData lo establece automáticamente
            },
            body: formData
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
        
        if (data.success && (data.cotizacion_id !== undefined || (data.data && data.data.id !== undefined))) {
            const cotizacionId = data.cotizacion_id !== undefined ? data.cotizacion_id : (data.data && data.data.id);
            console.log('✅ Cotización enviada con ID:', cotizacionId);
            console.log('✅ Imágenes procesadas y guardadas en el servidor');
            
            // ✅ LIMPIAR TODO DESPUÉS DEL ENVÍO EXITOSO
            if (typeof limpiarFormularioCompleto === 'function') {
                limpiarFormularioCompleto();
            } else if (typeof limpiarStorage === 'function') {
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
                // Redirigir a la vista de cotizaciones
                window.location.href = '/asesores/cotizaciones?tab=cotizaciones';
            }, 2000);
        } else {
            // Construir mensaje de error detallado
            let mensajeError = data.message || 'Error desconocido';
            let htmlError = `<p>${mensajeError}</p>`;
            
            // Si hay errores de validación, mostrarlos
            if (data.validation_errors) {
                htmlError += '<div style="text-align: left; margin-top: 10px;">';
                for (const [campo, errores] of Object.entries(data.validation_errors)) {
                    if (Array.isArray(errores)) {
                        errores.forEach(error => {
                            htmlError += `<p style="margin: 5px 0; font-size: 0.9rem;"><strong>${campo}:</strong> ${error}</p>`;
                        });
                    }
                }
                htmlError += '</div>';
            }
            
            console.error('❌ Error en la respuesta:', data);
            
            Swal.fire({
                title: 'Error al enviar',
                html: htmlError,
                icon: 'error',
                confirmButtonColor: '#1e40af',
                width: '600px'
            });
        }
    } catch (error) {
        console.error('❌ Error en fetch:', error);
        Swal.fire({
            title: 'Error de conexión',
            html: `<p>No se pudo completar la solicitud:</p>
                   <p style="font-size: 0.9rem; color: #d32f2f; margin-top: 10px;">${error.message}</p>`,
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

// ============ INICIALIZACIÓN DE VALIDACIÓN DE TIPO DE VENTA ============

document.addEventListener('DOMContentLoaded', function() {
    // Obtener elementos
    const tipoVentaSelect = document.getElementById('tipo_venta');
    const btnGuardar = document.querySelector('button[onclick="guardarCotizacion()"]');
    const btnEnviar = document.querySelector('button[onclick="enviarCotizacion()"]');
    
    // Función para actualizar estado de botones
    function actualizarEstadoBotones() {
        const tipoSeleccionado = tipoVentaSelect && tipoVentaSelect.value;
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
    if (tipoVentaSelect) {
        actualizarEstadoBotones();
        
        // Escuchar cambios en el select
        tipoVentaSelect.addEventListener('change', actualizarEstadoBotones);
    }
});
