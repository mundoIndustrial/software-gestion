/**
 * SISTEMA DE COTIZACIONES - GUARDADO Y ENVÍO
 * Responsabilidad: Guardar, enviar cotizaciones y subir imágenes
 * Compatible con: localStorage (persistencia) y WebSockets (sin conflictos)
 */

// ============ FUNCIÓN HELPER: PROCESAR GÉNERO "AMBOS" ============

/**
 * Procesa el campo género para convertir "ambos" en ["dama", "caballero"]
 */
function procesarGenero(genero) {
    if (!genero) return null;
    
    if (typeof genero === 'string') {
        if (genero === 'ambos') {
            return ['dama', 'caballero'];
        }
        return [genero];
    }
    
    if (Array.isArray(genero)) {
        // Si el array contiene "ambos", expandirlo
        if (genero.includes('ambos')) {
            const otros = genero.filter(g => g !== 'ambos');
            return [...new Set([...otros, 'dama', 'caballero'])]; // Evitar duplicados
        }
        return genero;
    }
    
    return null;
}

// ============ GUARDAR COTIZACIÓN ============

async function guardarCotizacion() {
    console.log('='.repeat(60));
    console.log('🚀 INICIANDO GUARDADO DE COTIZACIÓN');
    console.log('   🌐 WebSockets:', window.Echo ? 'Disponible ✓' : 'No disponible');
    console.log('   💾 localStorage:', window.localStorage ? 'Disponible ✓' : 'No disponible');
    console.log('   🆔 Cotización ID Actual:', window.cotizacionIdActual || 'NUEVA');
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
    
    console.log('🔘 Botones encontrados:', {
        guardar: !!btnGuardar,
        enviar: !!btnEnviar
    });
    
    if (btnGuardar) btnGuardar.disabled = true;
    if (btnEnviar) btnEnviar.disabled = true;
    
    console.log('📋 Llamando a recopilarDatos()...');
    const datos = recopilarDatos();
    
    console.log('📦 Datos recopilados:', {
        existe: !!datos,
        cliente: datos?.cliente,
        productos: datos?.productos?.length || 0,
        tecnicas: datos?.tecnicas?.length || 0
    });
    
    if (!datos) {
        console.error('❌ recopilarDatos() retornó null');
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
    
    // ✅ NO convertir a Base64 - enviar archivos directamente como File objects
    console.log('📁 Preparando archivos para envío directo (sin Base64)...');
    
    // Validar que tipo_venta esté seleccionado
    const tipoVentaSelect = document.getElementById('tipo_venta');
    const tipoVentaPaso3Select = document.getElementById('tipo_venta_paso3');
    const tipoVenta = tipoVentaSelect ? tipoVentaSelect.value : '';
    const tipoVentaPaso3 = tipoVentaPaso3Select ? tipoVentaPaso3Select.value : '';
    
    console.log('📋 Validación tipo_venta:', {
        paso2: tipoVenta,
        paso3: tipoVentaPaso3,
        esValidoPaso2: !!tipoVenta
    });
    
    if (!tipoVenta) {
        console.error('❌ Tipo de venta no seleccionado');
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
    
    console.log('✅ Todas las validaciones pasadas, mostrando modal de guardado...');
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
        console.log('🔄 Construyendo FormData...');
        // ✅ USAR FormData PARA ENVIAR ARCHIVOS File
        const formData = new FormData();
        
        // Datos básicos
        formData.append('tipo', 'borrador');     // ← AGREGAR: Identificar acción GUARDAR
        formData.append('accion', 'guardar');    // ← AGREGAR: Identificar acción GUARDAR
        formData.append('es_borrador', '1');     // Marcar como borrador
        formData.append('cliente', datos.cliente);
        formData.append('tipo_venta', tipoVenta);
        formData.append('tipo_venta_paso3', tipoVentaPaso3);  // Enviar PASO 3 independiente
        formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
        
        console.log('📝 Datos básicos agregados:', {
            tipo: 'borrador',
            accion: 'guardar',
            es_borrador: '1',
            cliente: datos.cliente,
            tipo_venta: tipoVenta,
            tipo_cotizacion: window.tipoCotizacionGlobal || 'P'
        });
        
        // Si estamos editando un borrador, enviar el ID
        if (window.cotizacionIdActual) {
            formData.append('cotizacion_id', window.cotizacionIdActual);
            console.log('📝 Editando cotización existente ID:', window.cotizacionIdActual);
        }
        
        // Enviar fotos a eliminar (marcadas como eliminadas)
        if (window.fotosAEliminar && window.fotosAEliminar.length > 0) {
            console.log('🗑️ Fotos a eliminar:', window.fotosAEliminar.length);
            window.fotosAEliminar.forEach((foto, idx) => {
                formData.append(`fotos_a_eliminar[${idx}]`, foto.ruta);
                console.log(`🗑️ Foto ${idx + 1} marcada para eliminar:`, foto.ruta);
            });
        }
        
        // Secciones de texto
        formData.append('descripcion_logo', datos.descripcion_logo || '');
        formData.append('tecnicas', JSON.stringify(datos.tecnicas || []));
        formData.append('observaciones_tecnicas', datos.observaciones_tecnicas || '');
        formData.append('ubicaciones', JSON.stringify(datos.ubicaciones || []));
        formData.append('observaciones_generales', JSON.stringify(datos.observaciones_generales || []));
        
        console.log('📝 Datos de texto agregados:', {
            descripcion_logo: datos.descripcion_logo ? 'Sí' : 'No',
            tecnicas: datos.tecnicas?.length || 0,
            ubicaciones: datos.ubicaciones?.length || 0,
            observaciones_generales: datos.observaciones_generales?.length || 0
        });
        
        formData.append('especificaciones', JSON.stringify(datos.especificaciones || {}));
        console.log('✅ FormData construido correctamente');
        
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
                
                console.log(`🔍 DEBUG VARIANTES PRODUCTO ${index}:`, {
                    keys: Object.keys(variantes),
                    tipo_manga_id: variantes.tipo_manga_id,
                    tipo_manga: variantes.tipo_manga,
                    tiene_bolsillos: variantes.tiene_bolsillos,
                    todas_variantes: variantes
                });
                
                Object.keys(variantes).forEach(key => {
                    let value = variantes[key];
                    
                    if (key === 'telas_multiples' && Array.isArray(value)) {
                        // Caso especial: telas_multiples es un array de objetos
                        // Enviar como JSON string completo
                        formData.append(`prendas[${index}][variantes][${key}]`, JSON.stringify(value));
                    } else if (Array.isArray(value)) {
                        // Si es array, agregar cada elemento
                        value.forEach((item, idx) => {
                            if (typeof item === 'object' && item !== null) {
                                formData.append(`prendas[${index}][variantes][${key}][${idx}]`, JSON.stringify(item));
                            } else {
                                formData.append(`prendas[${index}][variantes][${key}][${idx}]`, item);
                            }
                        });
                        console.log(`   ✅ Array enviado para ${key}:`, value);
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
                    
                    if (key === 'tipo_manga_id') {
                        console.log(`   ✅ AGREGANDO MANGA AL FORMDATA: ${key} = ${value}`);
                    }
                });
                
                // ✅ FOTOS DE PRENDA (Si es nueva: todas, Si es edición: solo nuevas)
                if (window.imagenesEnMemoria && window.imagenesEnMemoria.prendaConIndice) {
                    const fotosDeEstaPrenda = window.imagenesEnMemoria.prendaConIndice.filter(p => p.prendaIndex === index);
                    const esEdicion = !!window.cotizacionIdActual;
                    fotosDeEstaPrenda.forEach((item, fotoIndex) => {
                        if (item.file instanceof File && (!esEdicion || !item.esGuardada)) {
                            // Enviar: File objects nuevos, o TODAS si es cotización nueva
                            formData.append(`prendas[${index}][fotos][]`, item.file);
                            console.log(`✅ Foto de prenda agregada [${index}][${fotoIndex}]:`, item.file.name, esEdicion ? '(NUEVA)' : '');
                        } else if (esEdicion && item.esGuardada) {
                            // Solo omitir si es edición y foto ya guardada
                            console.log(`⏭️ Foto de prenda ya guardada (OMITIDA) [${index}][${fotoIndex}]: ID ${item.fotoId}`);
                        }
                    });
                }
                
                // ✅ TELAS (File objects desde window.telasSeleccionadas)
                console.log(`🧵 Procesando telas para prenda ${index}...`);
                
                // Obtener el producto ID de esta prenda
                const prendaCard = document.querySelectorAll('.producto-card')[index];
                if (prendaCard) {
                    const productoId = prendaCard.dataset.productoId;
                    console.log(`🧵 Producto ID: ${productoId}`);
                    
                    // Buscar telas en window.telasSeleccionadas
                    if (window.telasSeleccionadas && window.telasSeleccionadas[productoId]) {
                        const telasObj = window.telasSeleccionadas[productoId];
                        console.log(`🧵 telasSeleccionadas encontrado para ${productoId}:`, telasObj);
                        
                        // Iterar sobre cada tela (los índices son las claves del objeto)
                        for (let telaIdx in telasObj) {
                            if (telasObj.hasOwnProperty(telaIdx) && Array.isArray(telasObj[telaIdx])) {
                                const fotosDelaTela = telasObj[telaIdx];
                                console.log(`🧵 Tela ${telaIdx}: ${fotosDelaTela.length} fotos`);
                                
                                // Agregar cada foto de esta tela al FormData
                                fotosDelaTela.forEach((foto, fotoIdx) => {
                                    console.log(`🔍 DEBUG Tela ${telaIdx} Foto ${fotoIdx + 1}:`, {
                                        esFile: foto instanceof File,
                                        tipo: typeof foto,
                                        constructor: foto?.constructor?.name,
                                        keys: Object.keys(foto || {}),
                                        foto: foto
                                    });
                                    
                                    if (foto instanceof File) {
                                        // ✅ CORRECCIÓN: Usar prendas[${index}][telas][${telaIdx}][fotos][${fotoIdx}]
                                        // El backend espera este formato exacto para guardar en prenda_tela_fotos_cot
                                        formData.append(`prendas[${index}][telas][${telaIdx}][fotos][${fotoIdx}]`, foto);
                                        console.log(`✅ Tela ${telaIdx} Foto ${fotoIdx} agregada a FormData: ${foto.name}`);
                                        console.log(`   → Key usado: prendas[${index}][telas][${telaIdx}][fotos][${fotoIdx}]`);
                                    } else {
                                        console.error(`❌ Tela ${telaIdx} Foto ${fotoIdx + 1} NO ES File object:`, foto);
                                    }
                                });
                            }
                        }
                    } else {
                        console.log(`⚠️ No hay telas en window.telasSeleccionadas para ${productoId}`);
                    }
                }
                
                // FALLBACK: Buscar en window.imagenesEnMemoria.telaConIndice (Si es nueva: todas, Si es edición: solo nuevas)
                if (window.imagenesEnMemoria && window.imagenesEnMemoria.telaConIndice) {
                    const telasDeEstaPrenda = window.imagenesEnMemoria.telaConIndice.filter(t => t.prendaIndex === index);
                    const esEdicion = !!window.cotizacionIdActual;
                    if (telasDeEstaPrenda.length > 0) {
                        console.log(`🧵 Usando fallback: imagenesEnMemoria.telaConIndice con ${telasDeEstaPrenda.length} telas`);
                        telasDeEstaPrenda.forEach((item, telaIndex) => {
                            if (item.file instanceof File && (!esEdicion || !item.esGuardada)) {
                                formData.append(`prendas[${index}][telas][${item.telaIndex || telaIndex}][fotos][0]`, item.file);
                                console.log(`✅ Tela agregada [${index}][${item.telaIndex || telaIndex}]:`, item.file.name, esEdicion ? '(NUEVA)' : '');
                            } else if (esEdicion && item.esGuardada) {
                                console.log(`⏭️ Tela ya guardada (OMITIDA) [${index}][${item.telaIndex}]: ID ${item.fotoId}`);
                            }
                        });
                    }
                }
            });
        }
        
        // 🗑️ FOTOS ELIMINADAS DEL SERVIDOR (enviar IDs para eliminar)
        if (window.fotosEliminadasServidor) {
            if (window.fotosEliminadasServidor.telas && window.fotosEliminadasServidor.telas.length > 0) {
                window.fotosEliminadasServidor.telas.forEach((fotoId, idx) => {
                    formData.append(`fotos_telas_eliminadas[${idx}]`, fotoId);
                    console.log(`🗑️ Foto de tela ID ${fotoId} marcada para eliminar en el servidor`);
                });
            }
            if (window.fotosEliminadasServidor.prendas && window.fotosEliminadasServidor.prendas.length > 0) {
                window.fotosEliminadasServidor.prendas.forEach((fotoId, idx) => {
                    formData.append(`fotos_prendas_eliminadas[${idx}]`, fotoId);
                    console.log(`🗑️ Foto de prenda ID ${fotoId} marcada para eliminar en el servidor`);
                });
            }
        }
        
        // ✅ LOGO - IMÁGENES (File objects desde window.imagenesEnMemoria + rutas guardadas)
        if (window.imagenesEnMemoria && window.imagenesEnMemoria.logo && Array.isArray(window.imagenesEnMemoria.logo)) {
            console.log('📸 Procesando imágenes de logo:', window.imagenesEnMemoria.logo.length);
            
            // Enviar archivos File nuevos
            window.imagenesEnMemoria.logo.forEach((imagen, imagenIndex) => {
                if (imagen instanceof File) {
                    // Usar nombre con índices entre corchetes: logo[imagenes][0], logo[imagenes][1], etc.
                    formData.append(`logo[imagenes][${imagenIndex}]`, imagen);
                    console.log(`✅ Imagen de logo (File) agregada a FormData [${imagenIndex}]:`, imagen.name);
                } else {
                    console.log(`⚠️ Imagen de logo no es File [${imagenIndex}]:`, typeof imagen);
                }
            });
        } else {
            console.log('⚠️ No hay imágenes de logo en memoria');
        }
        
        // ✅ LOGO - FOTOS GUARDADAS (Para conservar las existentes al reguardar)
        // Buscar imágenes dentro del contenedor galeria_imagenes que tengan data-foto-guardada="true"
        const galeriaImagenes = document.getElementById('galeria_imagenes');
        if (galeriaImagenes) {
            const fotosGuardadas = galeriaImagenes.querySelectorAll('[data-foto-guardada="true"] img');
            if (fotosGuardadas.length > 0) {
                console.log('📸 Agregando fotos de logo guardadas:', fotosGuardadas.length);
                fotosGuardadas.forEach((img, index) => {
                    const ruta = img.getAttribute('data-ruta') || img.src;
                    if (ruta && !ruta.includes('data:image')) {
                        formData.append(`logo_fotos_guardadas[]`, ruta);
                        console.log(`✅ Ruta de logo guardada agregada [${index}]:`, ruta);
                    }
                });
            } else {
                console.log('⚠️ No hay fotos guardadas en la galería');
            }
        } else {
            console.log('⚠️ No se encontró el elemento galeria_imagenes');
        }
        
        console.log('📤 FORMDATA A ENVIAR:', {
            tipo: 'borrador',
            cliente: datos.cliente,
            tipo_venta: tipoVenta,
            productos_count: datos.productos?.length || 0,
            tecnicas: datos.tecnicas?.length || 0,
            especificaciones_keys: Object.keys(datos.especificaciones || {}),
            ruta: window.routes.guardarCotizacion
        });
        
        // Debug: Mostrar contenido del FormData
        console.log('🔍 DEBUG - Contenido completo del FormData:');
        for (let pair of formData.entries()) {
            if (!pair[0].includes('[fotos]')) {  // Excluir archivos para no saturar el log
                console.log(`   ${pair[0]}: ${pair[1]}`);
            }
        }
        
        console.log('🌐 Enviando solicitud POST a:', window.routes.guardarCotizacion);
        const response = await fetch(window.routes.guardarCotizacion, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                // ⚠️ NO incluir 'Content-Type': 'application/json' - FormData lo establece automáticamente
            },
            body: formData
        });
        
        console.log('✅ Solicitud enviada');
        console.log('📡 Status de respuesta:', response.status);
        console.log('📡 Content-Type:', response.headers.get('content-type'));
        console.log('📡 OK:', response.ok);
        
        const responseText = await response.text();
        console.log('📡 Texto de respuesta (primeros 500 caracteres):', responseText.substring(0, 500));
        
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
            console.log('✅ Cotización creada con ID:', cotizacionId);
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
                window.location.href = window.routes.cotizacionesIndex + '?tab=borradores';
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
    const tipoVentaPaso3Select = document.getElementById('tipo_venta_paso3');
    const tipoVentaValue = tipoVentaSelect ? tipoVentaSelect.value : '';
    const tipoVentaPaso3Value = tipoVentaPaso3Select ? tipoVentaPaso3Select.value : '';
    
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
        formData.append('tipo', 'enviada');           // ✅ Identificar acción ENVIAR
        formData.append('accion', 'enviar');          // ← AGREGAR: Identificar acción ENVIAR
        formData.append('es_borrador', '0');          // ← AGREGAR: Marcar que NO es borrador
        formData.append('cliente', datos.cliente);
        formData.append('tipo_venta', tipoVentaValue);
        formData.append('tipo_venta_paso3', tipoVentaPaso3Value);  // Enviar PASO 3 independiente
        formData.append('tipo_cotizacion', window.tipoCotizacionGlobal || 'P');
        
        // Secciones de texto
        formData.append('descripcion_logo', datos.descripcion_logo || '');
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
                
                // ✅ FOTOS DE PRENDA (Si es nueva: todas, Si es edición: solo nuevas)
                if (window.imagenesEnMemoria && window.imagenesEnMemoria.prendaConIndice) {
                    const fotosDeEstaPrenda = window.imagenesEnMemoria.prendaConIndice.filter(p => p.prendaIndex === index);
                    const esEdicion = !!window.cotizacionIdActual;
                    fotosDeEstaPrenda.forEach((item, fotoIndex) => {
                        if (item.file instanceof File && (!esEdicion || !item.esGuardada)) {
                            // Enviar: File objects nuevos, o TODAS si es cotización nueva
                            formData.append(`prendas[${index}][fotos][]`, item.file);
                            console.log(`✅ Foto de prenda agregada [${index}][${fotoIndex}]:`, item.file.name, esEdicion ? '(NUEVA)' : '');
                        } else if (esEdicion && item.esGuardada) {
                            // Solo omitir si es edición y foto ya guardada
                            console.log(`⏭️ Foto de prenda ya guardada (OMITIDA) [${index}][${fotoIndex}]: ID ${item.fotoId}`);
                        }
                    });
                }
                
                // ✅ TELAS (File objects desde window.telasSeleccionadas)
                console.log(`🧵 Procesando telas para prenda ${index}...`);
                
                // Obtener el producto ID de esta prenda
                const prendaCard = document.querySelectorAll('.producto-card')[index];
                if (prendaCard) {
                    const productoId = prendaCard.dataset.productoId;
                    console.log(`🧵 Producto ID: ${productoId}`);
                    
                    // Buscar telas en window.telasSeleccionadas
                    if (window.telasSeleccionadas && window.telasSeleccionadas[productoId]) {
                        const telasObj = window.telasSeleccionadas[productoId];
                        console.log(`🧵 telasSeleccionadas encontrado para ${productoId}:`, telasObj);
                        
                        // Iterar sobre cada tela (los índices son las claves del objeto)
                        for (let telaIdx in telasObj) {
                            if (telasObj.hasOwnProperty(telaIdx) && Array.isArray(telasObj[telaIdx])) {
                                const fotosDelaTela = telasObj[telaIdx];
                                console.log(`🧵 Tela ${telaIdx}: ${fotosDelaTela.length} fotos`);
                                
                                // Agregar cada foto de esta tela al FormData
                                fotosDelaTela.forEach((foto, fotoIdx) => {
                                    console.log(`🔍 DEBUG Tela ${telaIdx} Foto ${fotoIdx + 1}:`, {
                                        esFile: foto instanceof File,
                                        tipo: typeof foto,
                                        constructor: foto?.constructor?.name,
                                        keys: Object.keys(foto || {}),
                                        foto: foto
                                    });
                                    
                                    if (foto instanceof File) {
                                        // ✅ CORRECCIÓN: Usar prendas[${index}][telas][${telaIdx}][fotos][${fotoIdx}]
                                        // El backend espera este formato exacto para guardar en prenda_tela_fotos_cot
                                        formData.append(`prendas[${index}][telas][${telaIdx}][fotos][${fotoIdx}]`, foto);
                                        console.log(`✅ Tela ${telaIdx} Foto ${fotoIdx} agregada a FormData: ${foto.name}`);
                                        console.log(`   → Key usado: prendas[${index}][telas][${telaIdx}][fotos][${fotoIdx}]`);
                                    } else {
                                        console.error(`❌ Tela ${telaIdx} Foto ${fotoIdx + 1} NO ES File object:`, foto);
                                    }
                                });
                            }
                        }
                    } else {
                        console.log(`⚠️ No hay telas en window.telasSeleccionadas para ${productoId}`);
                    }
                }
                
                // FALLBACK: Buscar en window.imagenesEnMemoria.telaConIndice (Si es nueva: todas, Si es edición: solo nuevas)
                if (window.imagenesEnMemoria && window.imagenesEnMemoria.telaConIndice) {
                    const telasDeEstaPrenda = window.imagenesEnMemoria.telaConIndice.filter(t => t.prendaIndex === index);
                    const esEdicion = !!window.cotizacionIdActual;
                    if (telasDeEstaPrenda.length > 0) {
                        console.log(`🧵 Usando fallback: imagenesEnMemoria.telaConIndice con ${telasDeEstaPrenda.length} telas`);
                        telasDeEstaPrenda.forEach((item, telaIndex) => {
                            if (item.file instanceof File && (!esEdicion || !item.esGuardada)) {
                                formData.append(`prendas[${index}][telas][${item.telaIndex || telaIndex}][fotos][0]`, item.file);
                                console.log(`✅ Tela agregada [${index}][${item.telaIndex || telaIndex}]:`, item.file.name, esEdicion ? '(NUEVA)' : '');
                            } else if (esEdicion && item.esGuardada) {
                                console.log(`⏭️ Tela ya guardada (OMITIDA) [${index}][${item.telaIndex}]: ID ${item.fotoId}`);
                            }
                        });
                    }
                }
            });
        }
        
        // ✅ LOGO - IMÁGENES (File objects desde imagenesEnMemoria + rutas guardadas desde DOM)
        if (window.imagenesEnMemoria && window.imagenesEnMemoria.logo && Array.isArray(window.imagenesEnMemoria.logo)) {
            console.log('📸 Procesando imágenes de logo desde memory:', window.imagenesEnMemoria.logo.length);
            
            window.imagenesEnMemoria.logo.forEach((imagen, imagenIndex) => {
                if (imagen instanceof File) {
                    // Es un File object nuevo
                    formData.append(`logo[imagenes][]`, imagen);
                    console.log(`✅ Imagen de logo (File) agregada a FormData [${imagenIndex}]:`, imagen.name);
                } else if (imagen.esGuardada && imagen.fotoId) {
                    // Es una imagen guardada en BD - enviar el ID para conservarla
                    formData.append(`logo_fotos_existentes[]`, imagen.fotoId);
                    console.log(`✅ ID de foto de logo existente agregado [${imagenIndex}]:`, imagen.fotoId);
                }
            });
        }
        
        // ✅ LOGO - FOTOS GUARDADAS EN BD DESDE DOM (por si acaso no estén en memory)
        // Estas son las imágenes que ya están guardadas en BD y necesitan ser conservadas
        const galeriaImagenes = document.getElementById('galeria_imagenes');
        if (galeriaImagenes) {
            const fotosExistentes = galeriaImagenes.querySelectorAll('img[data-foto-id]');
            if (fotosExistentes.length > 0) {
                console.log('📸 Encontradas imágenes existentes en galería:', fotosExistentes.length);
                fotosExistentes.forEach((img, idx) => {
                    const fotoId = img.getAttribute('data-foto-id');
                    if (fotoId && fotoId.trim()) {
                        // Enviar el ID para que el backend sepa cuál conservar
                        formData.append(`logo_fotos_existentes[]`, fotoId);
                        console.log(`✅ ID de foto existente agregado [${idx}]:`, fotoId);
                    }
                });
            } else {
                console.log('⚠️ No hay fotos existentes en la galería');
            }
        } else {
            console.log('⚠️ No se encontró el elemento galeria_imagenes');
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
