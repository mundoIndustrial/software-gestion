/**
 * Componente: Prenda Editor Modal
 * Maneja la edición de prendas en modal SweetAlert
 * 
 * Funciones públicas:
 * - abrirEditarPrendas() - Abre lista de prendas disponibles
 * - abrirEditarPrendaEspecifica(prendasIndex) - Abre formulario de edición de prenda específica
 */

/**
 * Abrir formulario para editar prendas del pedido (lista seleccionable)
 */
function abrirEditarPrendas() {
    if (!window.datosEdicionPedido) {
        Swal.fire('Error', 'No hay datos del pedido disponibles', 'error');
        return;
    }
    const datos = window.datosEdicionPedido;
    const prendas = datos.prendas || [];

    
    // Guardar prendas en variable global para acceso desde onclick
    window.prendasEdicion = {
        pedidoId: datos.id || datos.numero_pedido,
        prendas: prendas
    };
    
    let htmlListaPrendas = '<div style="display: grid; grid-template-columns: 1fr; gap: 0.75rem;">';
    
    if (prendas.length === 0) {
        // Mostrar mensaje cuando la lista está vacía
        htmlListaPrendas += `
            <div style="text-align: center; padding: 2rem; background: #f9fafb; border-radius: 8px; border: 2px dashed #d1d5db;">
                <p style="color: #6b7280; margin: 0;">No hay prendas agregadas aún</p>
            </div>
        `;
    } else {
        // Mostrar lista de prendas

        prendas.forEach((prenda, idx) => {
            const nombrePrenda = prenda.nombre_prenda || prenda.nombre || 'Prenda sin nombre';
            const cantTallas = prenda.tallas ? Object.keys(prenda.tallas).length : 0;
            const cantProcesos = (prenda.procesos || []).length;
            
            htmlListaPrendas += `
                <button onclick="abrirEditarPrendaEspecifica(${idx})" 
                    style="background: white; border: 2px solid #3b82f6; border-radius: 8px; padding: 1rem; text-align: left; cursor: pointer; transition: all 0.3s ease;"
                    onmouseover="this.style.background='#f5f3ff'; this.style.borderColor='#7c3aed';"
                    onmouseout="this.style.background='white'; this.style.borderColor='#1e40af';">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="margin: 0; color: #1f2937; font-size: 0.95rem; font-weight: 700;">PRENDA ${idx + 1}: ${nombrePrenda.toUpperCase()}</h4>
                            <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.85rem;">${prenda.descripcion || 'Sin descripción'}</p>
                            <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #9ca3af;">
                                 Tallas: ${cantTallas} |  Procesos: ${cantProcesos}
                            </div>
                        </div>
                        <span style="background: #1e40af; color: white; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;"> Editar</span>
                    </div>
                </button>
            `;
        });
    }
    
    htmlListaPrendas += '</div>';
    
    Swal.fire({
        title: ' Selecciona una Prenda para Editar',
        html: htmlListaPrendas,
        width: '600px',
        showConfirmButton: false,
        confirmButtonText: 'Cerrar',
        showCancelButton: true,
        cancelButtonText: 'Volver',
        customClass: {
            container: 'swal-centered-container',
            popup: 'swal-centered-popup'
        },
        didOpen: (modal) => {
            const container = modal.closest('.swal2-container');
            if (container) {
                container.style.display = 'flex';
                container.style.alignItems = 'center';
                container.style.justifyContent = 'center';
                container.style.height = '100vh';
                container.style.zIndex = '999998';
            }
            modal.style.marginTop = '0';
            modal.style.marginBottom = '0';
        }
    });
}

/**
 * Abrir modal de edición para una prenda específica
 * USA EL MODAL DE "AGREGAR PRENDA NUEVA" para la edición
 */
async function abrirEditarPrendaEspecifica(prendasIndex) {
    console.log('🔥 [EDITAR-PRENDA] Abriendo modal de edición con índice:', prendasIndex);
    
    if (!window.prendasEdicion) {
        console.error(' No hay datos de prendas disponibles');
        Swal.fire('Error', 'No hay datos de prendas disponibles', 'error');
        return;
    }
    
    const prenda = window.prendasEdicion.prendas[prendasIndex];
    const pedidoId = window.prendasEdicion.pedidoId;
    
    if (!prenda) {
        console.error(' Prenda no encontrada en índice:', prendasIndex);
        Swal.fire('Error', 'Prenda no encontrada', 'error');
        return;
    }
    
    console.log('✅ [EDITAR-PRENDA] Prenda encontrada:', {
        nombre: prenda.nombre_prenda,
        nombre_alt: prenda.nombre,
        id: prenda.id,
        prenda_pedido_id: prenda.prenda_pedido_id,
        pedidoId: pedidoId,
        todosLosCampos: Object.keys(prenda),
        estructura_completa: prenda
    });
    
    try {
        // OBTENER DATOS FRESCOS DE LA BD
        console.log('📡 [EDITAR-PRENDA] Obteniendo datos frescos del servidor...');
        const response = await fetch(`/asesores/pedidos/${pedidoId}/factura-datos`);
        
        if (!response.ok) {
            throw new Error(`Error ${response.status}: No se pudieron obtener los datos`);
        }
        
        const resultado = await response.json();
        
        if (!resultado.success || !resultado.data) {
            throw new Error('No se recibieron datos válidos del servidor');
        }
        
        // 🔍 DEBUG: Mostrar todas las prendas disponibles para comparación
        console.log('🔍 [EDITAR-PRENDA] Prendas disponibles en respuesta:', resultado.data.prendas?.length || 0);
        if (resultado.data.prendas && resultado.data.prendas.length > 0) {
            resultado.data.prendas.forEach((p, idx) => {
                console.log(`🔍 [EDITAR-PRENDA] Prenda[${idx}]:`, {
                    id: p.id,
                    prenda_pedido_id: p.prenda_pedido_id,
                    nombre: p.nombre,
                    nombre_prenda: p.nombre_prenda,
                    match_id: (p.id === prenda.id || p.prenda_pedido_id === prenda.id),
                    match_nombre_directo: (p.nombre === prenda.nombre),
                    match_nombre_cruzado_1: (p.nombre === prenda.nombre_prenda),
                    match_nombre_cruzado_2: (p.nombre_prenda === prenda.nombre),
                    match_nombre_prenda_directo: (p.nombre_prenda === prenda.nombre_prenda)
                });
            });
        }
        
        // 🔍 DEBUG: Mostrar qué estamos buscando
        console.log('🔍 [EDITAR-PRENDA] Buscando prenda con:', {
            buscar_id: prenda.id,
            buscar_prenda_pedido_id: prenda.id,
            buscar_nombre: prenda.nombre,
            buscar_nombre_prenda: prenda.nombre_prenda,
            buscar_nombre_producto: prenda.nombre_producto
        });
        
        // Encontrar la prenda específica en los datos del pedido - BÚSQUEDA BIDIRECCIONAL COMPLETA
        const prendaCompleta = resultado.data.prendas?.find(p => {
            // Coincidencia por ID (prioridad más alta)
            const coincideId = (p.id === prenda.id || p.prenda_pedido_id === prenda.id);
            
            // Coincidencia por nombre (TODAS LAS COMBINACIONES POSIBLES)
            const coincideNombre = (
                // Caso 1: nombre_prenda local == nombre_prenda servidor
                p.nombre_prenda === prenda.nombre_prenda ||
                // Caso 2: nombre local == nombre servidor  
                p.nombre === prenda.nombre ||
                // Caso 3: nombre_prenda local == nombre servidor (cruzado)
                p.nombre === prenda.nombre_prenda ||
                // Caso 4: nombre local == nombre_prenda servidor (cruzado)
                p.nombre_prenda === prenda.nombre ||
                // Caso 5: nombre_producto local == nombre servidor
                p.nombre === prenda.nombre_producto ||
                // Caso 6: nombre_producto local == nombre_prenda servidor
                p.nombre_prenda === prenda.nombre_producto ||
                // Caso 7: nombre local == nombre_producto servidor
                p.nombre_producto === prenda.nombre ||
                // Caso 8: nombre_prenda local == nombre_producto servidor
                p.nombre_producto === prenda.nombre_prenda
            );
            
            // Si coincide por ID, es suficiente (prioridad más alta)
            if (coincideId) {
                console.log('✅ [EDITAR-PRENDA] Coincidencia por ID encontrada:', {
                    encontrado_id: p.id,
                    encontrado_prenda_pedido_id: p.prenda_pedido_id,
                    buscado_id: prenda.id,
                    encontrado_nombre: p.nombre || p.nombre_prenda,
                    buscado_nombre: prenda.nombre || prenda.nombre_prenda
                });
                return true;
            }
            
            // Si no coincide por ID, requerir coincidencia por nombre
            if (coincideNombre) {
                console.log('✅ [EDITAR-PRENDA] Coincidencia por nombre encontrada:', {
                    encontrado_nombre: p.nombre || p.nombre_prenda || p.nombre_producto,
                    encontrado_nombre_prenda: p.nombre_prenda,
                    encontrado_nombre_producto: p.nombre_producto,
                    buscado_nombre: prenda.nombre || prenda.nombre_prenda || prenda.nombre_producto,
                    buscado_nombre_prenda: prenda.nombre_prenda,
                    buscado_nombre_producto: prenda.nombre_producto,
                    tipo_coincidencia: 'nombre'
                });
                return true;
            }
            
            return false;
        });
        
        if (!prendaCompleta) {
            console.error('❌ [EDITAR-PRENDA] No se encontró la prenda. Datos de búsqueda:', {
                buscar: {
                    id: prenda.id,
                    nombre_prenda: prenda.nombre_prenda,
                    nombre: prenda.nombre
                },
                disponibles: resultado.data.prendas?.map(p => ({
                    id: p.id,
                    prenda_pedido_id: p.prenda_pedido_id,
                    nombre: p.nombre,
                    nombre_prenda: p.nombre_prenda
                }))
            });
            throw new Error('Prenda no encontrada en los datos del pedido');
        }
        
        // 🔍 DEBUG: Verificar IDs de prendaCompleta
        console.log('🔍 [EDITAR-PRENDA] IDs en prendaCompleta:', {
            id: prendaCompleta.id,
            prenda_pedido_id: prendaCompleta.prenda_pedido_id,
            nombre_prenda: prendaCompleta.nombre_prenda,
            todosLosCampos: Object.keys(prendaCompleta)
        });
        
        console.log('✅ [EDITAR-PRENDA] Datos del servidor recibidos:', {
            tallas_dama: prendaCompleta.tallas_dama?.length || 0,
            tallas_caballero: prendaCompleta.tallas_caballero?.length || 0,
            colores_telas: prendaCompleta.colores_telas?.length || 0,
            telas_array: prendaCompleta.telas_array?.length || 0,
            variantes: prendaCompleta.variantes?.length || 0,
            procesos: prendaCompleta.procesos?.length || 0
        });
        
        // TRANSFORMAR DATOS PARA EL MODAL
        console.log('🔄 [EDITAR-PRENDA] Transformando datos para el modal...');
        
        // Función auxiliar para manejar URLs de imágenes (robusta para ambos casos)
        const agregarStorage = (url) => {
            if (!url) return '';
            
            // Caso 1: URL ya es absoluta (empieza con /)
            if (url.startsWith('/')) {
                // Si ya tiene /storage/, la dejamos intacta
                if (url.includes('/storage/')) {
                    return url; // Ya está correcta
                }
                // Si empieza con / pero no tiene /storage/, se la agregamos
                return '/storage' + url;
            }
            
            // Caso 2: URL es relativa (no empieza con /)
            if (url.startsWith('http')) {
                return url; // URLs completas se dejan intactas
            }
            
            // Caso 3: URL relativa con 'storage/' al inicio
            if (url.startsWith('storage/')) {
                return '/storage/' + url.replace('storage/', '');
            }
            
            // Caso 4: URL relativa sin prefijo (ej: "pedidos/1/prenda/imagen.webp")
            return '/storage/' + url;
        };

        // SISTEMA DE DETECCIÓN AUTOMÁTICA DE FORMATO - Sin dañar lógica existente
        const formatoDetectado = {
            tallas: 'desconocido',
            telas: 'desconocido', 
            variantes: 'desconocido'
        };
        
        // DETECTAR FORMATO DE TALLAS
        if (prendaCompleta.tallas && typeof prendaCompleta.tallas === 'object' && 
            Object.keys(prendaCompleta.tallas).some(g => ['DAMA', 'CABALLERO', 'UNISEX'].includes(g.toUpperCase()))) {
            formatoDetectado.tallas = 'nuevo'; // {DAMA: {L: 20}}
            console.log('🔍 [DETECCIÓN] Formato de tallas detectado: NUEVO (objeto por género)');
        } else if ((prendaCompleta.tallas_dama && Array.isArray(prendaCompleta.tallas_dama)) || 
                   (prendaCompleta.tallas_caballero && Array.isArray(prendaCompleta.tallas_caballero))) {
            formatoDetectado.tallas = 'antiguo'; // {tallas_dama: [{talla: "L", cantidad: 20}]}
            console.log('🔍 [DETECCIÓN] Formato de tallas detectado: ANTIGUO (arrays por género)');
        } else {
            console.warn('⚠️ [DETECCIÓN] Formato de tallas no reconocido, usando defaults');
        }
        
        // DETECTAR FORMATO DE TELAS
        if (prendaCompleta.telas_array && Array.isArray(prendaCompleta.telas_array) && 
            prendaCompleta.telas_array.length > 0) {
            formatoDetectado.telas = 'nuevo'; // {telas_array: [{id: 1, tela_id: 19, color_id: 61}]}
            console.log('🔍 [DETECCIÓN] Formato de telas detectado: NUEVO (telas_array)');
        } else if (prendaCompleta.colores_telas && Array.isArray(prendaCompleta.colores_telas) && 
                   prendaCompleta.colores_telas.length > 0) {
            formatoDetectado.telas = 'antiguo'; // {colores_telas: [{id: 1, color_id: 61}]}
            console.log('🔍 [DETECCIÓN] Formato de telas detectado: ANTIGUO (colores_telas)');
        } else {
            console.warn('⚠️ [DETECCIÓN] Formato de telas no reconocido, usando defaults');
        }
        
        // DETECTAR FORMATO DE VARIANTES
        if (prendaCompleta.variantes && Array.isArray(prendaCompleta.variantes) && 
            prendaCompleta.variantes.length > 0) {
            const v = prendaCompleta.variantes[0];
            if (v.manga || v.broche || v.bolsillos) {
                formatoDetectado.variantes = 'nuevo'; // {manga: "Larga", broche: "Botón"}
                console.log('🔍 [DETECCIÓN] Formato de variantes detectado: NUEVO (campos directos)');
            } else if (v.tipo_manga || v.tipo_broche_boton || v.tiene_bolsillos !== undefined) {
                formatoDetectado.variantes = 'antiguo'; // {tipo_manga: "Larga", tipo_broche_boton: "Botón"}
                console.log('🔍 [DETECCIÓN] Formato de variantes detectado: ANTIGUO (campos prefijados)');
            } else {
                formatoDetectado.variantes = 'mixto'; // Mezcla de formatos
                console.log('🔍 [DETECCIÓN] Formato de variantes detectado: MIXTO');
            }
        } else {
            console.warn('⚠️ [DETECCIÓN] Formato de variantes no reconocido, usando defaults');
        }
        
        console.log('📊 [DETECCIÓN] Formatos detectados:', formatoDetectado);

        // TRANSFORMAR TELAS - Basado en detección automática
        const telasAgregadas = [];
        
        if (formatoDetectado.telas === 'nuevo') {
            // Formato nuevo: {telas_array: [{id: 1, tela_id: 19, color_id: 61, nombre: "ALFONSO"}]}
            console.log('🔄 [TELAS] Procesando formato NUEVO (telas_array):', prendaCompleta.telas_array);
            
            prendaCompleta.telas_array.forEach((ct) => {
                if (ct && typeof ct === 'object' && ct.id && ct.tela_id && ct.color_id) {
                    // Validación segura de objetos anidados para evitar null reference
                    const color = ct.color || {};
                    const tela = ct.tela || {};
                    
                    telasAgregadas.push({
                        id: ct.id,
                        color_id: ct.color_id,
                        color: color.nombre || ct.color_nombre || ct.color || '',
                        codigo_color: color.codigo || ct.color_codigo || ct.codigo_color || '',
                        tela_id: ct.tela_id,
                        tela: tela.nombre || ct.tela_nombre || ct.nombre || '',
                        nombre_tela: tela.nombre || ct.tela_nombre || ct.nombre || '',
                        referencia: ct.referencia || tela.referencia || ct.tela_referencia || ct.ref || '',
                        imagenes_count: (ct.imagenes_tela || ct.fotos_tela || ct.imagenes || ct.fotos || []).length,
                        imagenes: (ct.imagenes_tela || ct.fotos_tela || ct.imagenes || ct.fotos || []).map(f => {
                            const urlConStorage = agregarStorage(f.ruta_webp || f.ruta_original || f.url || f.ruta || '');
                            return {
                                id: f.id,
                                url: urlConStorage,
                                ruta: urlConStorage,
                                urlDesdeDB: true,
                                ruta_original: f.ruta_original,
                                ruta_webp: f.ruta_webp
                            };
                        })
                    });
                    console.log('✅ [TELAS] Tela transformada (formato nuevo):', telasAgregadas[telasAgregadas.length - 1]);
                } else {
                    console.warn('⚠️ [TELAS] Estructura inválida en formato nuevo, omitiendo:', ct);
                }
            });
        } else if (formatoDetectado.telas === 'antiguo') {
            // Formato antiguo: {colores_telas: [{id: 1, color_id: 61, tela_id: 19}]}
            console.log('🔄 [TELAS] Procesando formato ANTIGUO (colores_telas):', prendaCompleta.colores_telas);
            
            prendaCompleta.colores_telas.forEach((ct) => {
                if (ct && typeof ct === 'object' && ct.id && ct.color_id && ct.tela_id) {
                    // Validación segura de objetos anidados para evitar null reference
                    const color = ct.color || {};
                    const tela = ct.tela || {};
                    
                    telasAgregadas.push({
                        id: ct.id,
                        color_id: ct.color_id,
                        color: color.nombre || ct.color_nombre || '',
                        codigo_color: color.codigo || ct.color_codigo || '',
                        tela_id: ct.tela_id,
                        tela: tela.nombre || ct.tela_nombre || '',
                        nombre_tela: tela.nombre || ct.tela_nombre || '',
                        referencia: ct.referencia || tela.referencia || ct.tela_referencia || '',
                        imagenes_count: (ct.imagenes_tela || ct.fotos_tela || []).length,
                        imagenes: (ct.imagenes_tela || ct.fotos_tela || []).map(f => {
                            const urlConStorage = agregarStorage(f.ruta_webp || f.ruta_original);
                            return {
                                id: f.id,
                                url: urlConStorage,
                                ruta: urlConStorage,
                                urlDesdeDB: true,
                                ruta_original: f.ruta_original,
                                ruta_webp: f.ruta_webp
                            };
                        })
                    });
                    console.log('✅ [TELAS] Tela transformada (formato antiguo):', telasAgregadas[telasAgregadas.length - 1]);
                } else {
                    console.warn('⚠️ [TELAS] Estructura inválida en formato antiguo, omitiendo:', ct);
                }
            });
        } else {
            // Sin datos - array vacío para no romper el flujo
            console.log('🔄 [TELAS] Sin datos, usando array vacío');
        }
        
        // TRANSFORMAR VARIANTES - Basado en detección automática
        let variantes = {};
        
        if (formatoDetectado.variantes !== 'desconocido' && prendaCompleta.variantes && 
            Array.isArray(prendaCompleta.variantes) && prendaCompleta.variantes.length > 0) {
            
            const v = prendaCompleta.variantes[0];
            console.log('🔄 [VARIANTES] Procesando formato detectado:', formatoDetectado.variantes, v);
            
            // Mapeo universal que funciona con ambos formatos
            variantes = {
                // Campos nuevos (prioridad)
                tipo_manga: v.tipo_manga || v.manga || '',
                tipo_manga_id: v.tipo_manga_id || v.manga_id,
                obs_manga: v.manga_obs || v.obs_manga || '',
                tiene_bolsillos: v.tiene_bolsillos !== undefined ? v.tiene_bolsillos : (v.bolsillos || false),
                obs_bolsillos: v.bolsillos_obs || v.obs_bolsillos || '',
                tipo_broche: v.tipo_broche_boton || v.broche || v.tipo_broche || '',
                tipo_broche_id: v.tipo_broche_boton_id || v.broche_id || v.tipo_broche_id,
                obs_broche: v.broche_boton_obs || v.broche_obs || v.obs_broche || '',
                // Campos adicionales
                talla: v.talla || '',
                cantidad: v.cantidad || 0
            };
            
            console.log('✅ [VARIANTES] Variantes procesadas (formato ' + formatoDetectado.variantes + '):', variantes);
            
        } else {
            // Sin datos - defaults seguros para no romper el flujo
            console.log('🔄 [VARIANTES] Sin datos válidos, usando defaults seguros');
            variantes = {
                tipo_manga: '',
                tipo_manga_id: undefined,
                obs_manga: '',
                tiene_bolsillos: false,
                obs_bolsillos: '',
                tipo_broche: '',
                tipo_broche_id: undefined,
                obs_broche: '',
                talla: '',
                cantidad: 0
            };
        }
        
        // TRANSFORMAR TALLAS - Basado en detección automática
        const tallasPorGenero = {};
        
        if (formatoDetectado.tallas === 'nuevo') {
            // Formato nuevo: {DAMA: {L: 20, M: 10}}
            console.log('🔄 [TALLAS] Procesando formato NUEVO:', prendaCompleta.tallas);
            
            Object.entries(prendaCompleta.tallas).forEach(([genero, tallasGenero]) => {
                const generoNormalizado = genero.toUpperCase();
                if (['DAMA', 'CABALLERO', 'UNISEX'].includes(generoNormalizado) && 
                    typeof tallasGenero === 'object' && tallasGenero !== null) {
                    tallasPorGenero[generoNormalizado] = {};
                    Object.entries(tallasGenero).forEach(([talla, cantidad]) => {
                        const cantidadValida = (typeof cantidad === 'number' && cantidad >= 0) ? cantidad : 0;
                        tallasPorGenero[generoNormalizado][talla] = cantidadValida;
                    });
                }
            });
        } else if (formatoDetectado.tallas === 'antiguo') {
            // Formato antiguo: {tallas_dama: [{talla: "L", cantidad: 20}]}
            console.log('🔄 [TALLAS] Procesando formato ANTIGUO');
            
            if (prendaCompleta.tallas_dama && Array.isArray(prendaCompleta.tallas_dama)) {
                tallasPorGenero.DAMA = {};
                prendaCompleta.tallas_dama.forEach(t => {
                    if (t && typeof t === 'object' && t.talla && typeof t.cantidad === 'number') {
                        tallasPorGenero.DAMA[t.talla] = Math.max(0, t.cantidad);
                    }
                });
            }
            
            if (prendaCompleta.tallas_caballero && Array.isArray(prendaCompleta.tallas_caballero)) {
                tallasPorGenero.CABALLERO = {};
                prendaCompleta.tallas_caballero.forEach(t => {
                    if (t && typeof t === 'object' && t.talla && typeof t.cantidad === 'number') {
                        tallasPorGenero.CABALLERO[t.talla] = Math.max(0, t.cantidad);
                    }
                });
            }
        } else {
            // Sin datos - estructura vacía para no romper el flujo
            console.log('🔄 [TALLAS] Sin datos, usando estructura vacía');
            tallasPorGenero.DAMA = {};
            tallasPorGenero.CABALLERO = {};
            tallasPorGenero.UNISEX = {};
        }
        
        // Transformar tallas a array para compatibilidad con el modal
        const tallasArray = [];
        Object.entries(tallasPorGenero).forEach(([genero, tallas]) => {
            Object.entries(tallas).forEach(([talla, cantidad]) => {
                if (cantidad > 0) {
                    tallasArray.push({
                        genero: genero,
                        talla: talla,
                        cantidad: cantidad
                    });
                }
            });
        });
        
        // Transformar a estructura con cantidades para generosConTallas - Manejo robusto
        const generosConTallasEstructura = {};
        
        Object.entries(tallasPorGenero).forEach(([genero, tallas]) => {
            // Validar que el género sea válido y tenga datos
            if (genero && typeof genero === 'string' && tallas && typeof tallas === 'object') {
                const tieneTallas = Object.values(tallas).some(cant => typeof cant === 'number' && cant > 0);
                if (tieneTallas) {
                    // Validar que todas las cantidades sean números válidos
                    const cantidadesValidadas = {};
                    Object.entries(tallas).forEach(([talla, cantidad]) => {
                        cantidadesValidadas[talla] = (typeof cantidad === 'number' && cantidad >= 0) ? cantidad : 0;
                    });
                    
                    // Usar mayúsculas para consistencia con el procesamiento en prenda-editor.js
                    generosConTallasEstructura[genero] = {
                        cantidades: cantidadesValidadas
                    };
                    
                    console.log(`✅ [EDITAR-PRENDA] Género ${genero} procesado:`, cantidadesValidadas);
                } else {
                    console.log(`ℹ️ [EDITAR-PRENDA] Género ${genero} sin tallas con cantidad > 0, omitiendo`);
                }
            } else {
                console.warn(`⚠️ [EDITAR-PRENDA] Estructura inválida para género:`, { genero, tallas });
            }
        });
        
        // Validación final de la estructura
        if (Object.keys(generosConTallasEstructura).length === 0) {
            console.warn('⚠️ [EDITAR-PRENDA] No se encontraron tallas válidas en generosConTallasEstructura');
        } else {
            console.log('✅ [EDITAR-PRENDA] generosConTallasEstructura final:', generosConTallasEstructura);
        }
        
        console.log('✅ [EDITAR-PRENDA] Tallas como array:', tallasArray);
        console.log('🔍 [EDITAR-PRENDA] generosConTallasEstructura:', generosConTallasEstructura);
        console.log('🔍 [EDITAR-PRENDA] tallasPorGenero (crudo):', tallasPorGenero);
        
        // Preparar datos para el modal
        // Transformar imágenes de la prenda
        // IMPORTANTE: Capturar TODAS las imágenes, tanto ruta_webp como ruta_original
        // La API devuelve las imágenes en la propiedad 'fotos', no 'imagenes'
        const prendaImagenesRaw = prendaCompleta.fotos || prendaCompleta.imagenes || [];
        console.log('🖼️ [EDITAR-PRENDA-IMAGENES-RAW] Imágenes RAW recibidas de la API (property: fotos/imagenes):', {
            cantidad: prendaImagenesRaw.length,
            datos: prendaImagenesRaw,
            hayFotos: !!prendaCompleta.fotos,
            hayImagenes: !!prendaCompleta.imagenes
        });
        
        const prendaImagenesMapeadas = prendaImagenesRaw.map((img, idx) => {
            const url = typeof img === 'string' ? img : (img.ruta_webp || img.ruta_original || img.url);
            const urlFinal = agregarStorage(url);
            console.log(`   [${idx}] Mapeo de imagen:`, {
                url: url,
                urlFinal: urlFinal,
                tieneRutaWebp: !!img.ruta_webp,
                tieneRutaOriginal: !!img.ruta_original,
                tieneUrl: !!img.url,
                imageId: img.id
            });
            return {
                id: img.id || idx,
                url: urlFinal,
                ruta: urlFinal,
                ruta_original: img.ruta_original || '',
                ruta_webp: img.ruta_webp || '',
                urlDesdeDB: true
            };
        }).filter(img => {
            // Filtrar imágenes con URLs inválidas
            return img.url && img.url.trim() !== '';
        });
        
        console.log('✅ [EDITAR-PRENDA-IMAGENES-MAPEADAS] Después del mapeo:', {
            cantidad: prendaImagenesMapeadas.length,
            datos: prendaImagenesMapeadas
        });
        
        // Extraer nombre de prenda - Manejo robusto de múltiples formatos
        const nombrePrenda = prendaCompleta.nombre || 
                           prendaCompleta.nombre_prenda || 
                           prendaCompleta.nombre_producto || 
                           '';
        
        console.log('🔍 [EDITAR-PRENDA] Extracción de nombre:', {
            nombre: prendaCompleta.nombre,
            nombre_prenda: prendaCompleta.nombre_prenda,
            nombre_producto: prendaCompleta.nombre_producto,
            nombre_final: nombrePrenda
        });

        const prendaParaEditar = {
            nombre_prenda: nombrePrenda,
            nombre_producto: nombrePrenda,
            descripcion: prendaCompleta.descripcion || '',
            // 🔴 LÓGICA CORRECTA DE ORIGEN:
            // Prioridad 1: prendaCompleta.origen (si viene establecido)
            // Prioridad 2: prenda.origen (origen anterior)
            // Prioridad 3: Convertir de_bodega a origen
            // Prioridad 4: Default 'bodega'
            origen: prendaCompleta.origen || prenda.origen || 
                    (prendaCompleta.de_bodega === false ? 'confeccion' : 
                     prendaCompleta.de_bodega === true || prendaCompleta.de_bodega === 1 ? 'bodega' : 
                     'bodega'),
            de_bodega: prendaCompleta.de_bodega !== undefined ? prendaCompleta.de_bodega : prenda.de_bodega,
            imagenes: prendaImagenesMapeadas,
            telasAgregadas: telasAgregadas,
            tallas: tallasArray,
            generosConTallas: generosConTallasEstructura,
            procesos: (prendaCompleta.procesos || []).map(proc => {
                console.log(' [EDITAR-PRENDA-PROCESOS] Proceso bruto del servidor:', {
                    ...proc,
                    imagenes: `Array(${proc.imagenes?.length || 0})`
                });
                
                // El backend retorna 'tipo' directamente (ej: 'Reflectivo')
                const tipoProcesoBackend = proc.tipo || proc.tipo_proceso || '';
                
                console.log(' [EDITAR-PRENDA-PROCESOS] Transformando proceso:', {
                    procesoId: proc.id,
                    tipoBackend: tipoProcesoBackend,
                    nombre: proc.nombre,
                    nombre_proceso: proc.nombre_proceso,
                    tieneImagenes: !!proc.imagenes,
                    countImagenes: proc.imagenes?.length || 0,
                    tieneUbicaciones: !!proc.ubicaciones,
                    ubicaciones: proc.ubicaciones
                });
                
                const procesoTransformado = {
                    ...proc,
                    imagenes: (proc.imagenes || []).map(img => {
                        // Manejar tanto strings como objetos
                        let url = '';
                        if (typeof img === 'string') {
                            // Es un string directo (ruta)
                            url = img;
                        } else if (typeof img === 'object' && img) {
                            // Es un objeto, extraer ruta
                            url = img.ruta_webp || img.ruta_original || img.url || img.ruta || '';
                        }
                        
                        console.log('  📸 Imagen transformada:', {
                            original: img,
                            urlExtraida: url,
                            urlConStorage: agregarStorage(url)
                        });
                        
                        return {
                            ...(typeof img === 'object' ? img : {}),
                            ruta_webp: agregarStorage(url),
                            ruta_original: agregarStorage(url),
                            url: agregarStorage(url)
                        };
                    })
                };
                
                console.log('  Proceso transformado:', procesoTransformado);
                return procesoTransformado;
            }),
            variantes: variantes
        };
        
        console.log('✅ [EDITAR-PRENDA] Datos listos para cargar en modal:', Object.keys(prendaParaEditar));
        console.log('🔬 [EDITAR-PRENDA] Procesos para modal:', prendaParaEditar.procesos);
        console.log('🖼️ [EDITAR-PRENDA] Imágenes para modal:', prendaParaEditar.imagenes);
        console.log('📊 [EDITAR-PRENDA] Datos de prendaCompleta:', {
            nombre: prendaCompleta.nombre,
            nombre_prenda: prendaCompleta.nombre_prenda,
            descripcion: prendaCompleta.descripcion,
            de_bodega: prendaCompleta.de_bodega,
            origen: prendaCompleta.origen
        });
        
        console.log('🔴🔴🔴 [EDITAR-PRENDA] LÓGICA DE ORIGEN APLICADA 🔴🔴🔴', {
            'prendaCompleta.origen': prendaCompleta.origen,
            'prenda.origen': prenda.origen,
            'prendaCompleta.de_bodega': prendaCompleta.de_bodega,
            'prenda.de_bodega': prenda.de_bodega,
            'origen_calculado': prendaParaEditar.origen,
            'de_bodega_final': prendaParaEditar.de_bodega
        });
        
        console.log('📊 [EDITAR-PRENDA] Datos finales en prendaParaEditar:', {
            nombre_prenda: prendaParaEditar.nombre_prenda,
            nombre_producto: prendaParaEditar.nombre_producto,
            descripcion: prendaParaEditar.descripcion,
            origen: prendaParaEditar.origen,
            de_bodega: prendaParaEditar.de_bodega
        });
        console.log('📊 [EDITAR-PRENDA] Respuesta completa del servidor:', resultado.prenda);
        console.log('🏢 [EDITAR-PRENDA] de_bodega del servidor:', resultado.prenda?.de_bodega, '(1=bodega, 0=confeccion)');
        
        // Cerrar el modal de seleccionar prenda
        Swal.close();
        
        // 🔍 DEBUG: Verificar qué se va a guardar en window.prendaEnEdicion
        console.log('🔍 [EDITAR-PRENDA] Guardando en window.prendaEnEdicion:', {
            prendaCompletaId: prenda.id,
            prendaCompletaPrendaPedidoId: prenda.prenda_pedido_id,
            prendaCompletaNombre: prenda.nombre_prenda,
            todosLosCampos: Object.keys(prenda)
        });
        
        // Guardar en global
        window.prendaEnEdicion = {
            pedidoId: pedidoId,
            prendasIndex: prendasIndex,
            prendaOriginal: JSON.parse(JSON.stringify(prenda)),
            esEdicion: true
        };
        
        // Abrir modal con datos transformados
        if (window.gestionItemsUI && typeof window.gestionItemsUI.abrirModalAgregarPrendaNueva === 'function') {
            console.log('✅ [EDITAR-PRENDA] Abriendo modal con GestionItemsUI');
            
            const modal = document.getElementById('modal-agregar-prenda-nueva');
            if (modal && modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
            
            window.gestionItemsUI.prendaEditIndex = prendasIndex;
            window.gestionItemsUI.prendaEnModoEdicion = true;
            window.gestionItemsUI.abrirModalAgregarPrendaNueva();
            
            if (typeof window.gestionItemsUI.cargarItemEnModal === 'function') {
                console.log('✅ [EDITAR-PRENDA] Cargando datos en modal');
                window.gestionItemsUI.cargarItemEnModal(prendaParaEditar, prendasIndex);
            }
            
            console.log('✅ [EDITAR-PRENDA] Modal abierto exitosamente');
            return;
        }
        
        console.error(' GestionItemsUI no disponible');
        Swal.fire('Error', 'No se pudo abrir el modal de edición', 'error');
        
    } catch (error) {
        console.error(' [EDITAR-PRENDA] Error:', error);
        Swal.fire('Error', `No se pudieron cargar los datos: ${error.message}`, 'error');
    }
}

function abrirEditarProcesoEspecifico(prendasIndex, procesoIndex) {
    if (!window.prendasEdicion) {
        Swal.fire('Error', 'No hay datos de prendas disponibles', 'error');
        return;
    }
    
    const prenda = window.prendasEdicion.prendas[prendasIndex];
    const proceso = prenda.procesos[procesoIndex];
    
    if (!proceso) {
        Swal.fire('Error', 'Proceso no encontrado', 'error');
        return;
    }
    

    
    const tipoProc = proceso.nombre || proceso.nombre_proceso || proceso.descripcion || proceso.tipo_proceso || `Proceso ${procesoIndex + 1}`;
    
    // HTML para imágenes del proceso
    let htmlImagenes = '';
    if (proceso.imagenes && proceso.imagenes.length > 0) {
        htmlImagenes = '<div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px;"><strong style="color: #1f2937; display: block; margin-bottom: 0.75rem;"> Imágenes del Proceso:</strong>';
        htmlImagenes += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.75rem;">';
        htmlImagenes += proceso.imagenes.map((img, imgIdx) => `
            <div style="position: relative; border: 2px dashed #e5e7eb; border-radius: 6px; overflow: hidden; aspect-ratio: 1; background: #f5f5f5;">
                <img src="${img.url || img.ruta || img.path || ''}" alt="Proceso img ${imgIdx + 1}" style="width: 100%; height: 100%; object-fit: cover;">
                <label style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: white; padding: 0.5rem; font-size: 0.75rem; cursor: pointer;">
                    <input type="file" class="imagen-proceso-input" data-img-idx="${imgIdx}" style="display: none;" accept="image/*">
                     Cambiar
                </label>
            </div>
        `).join('');
        htmlImagenes += '</div></div>';
    }
    
    // HTML para ubicaciones
    let htmlUbicaciones = '';
    if (proceso.ubicaciones && proceso.ubicaciones.length > 0) {
        htmlUbicaciones = '<div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px;"><strong style="color: #1f2937; display: block; margin-bottom: 0.75rem;"> Ubicaciones:</strong>';
        htmlUbicaciones += '<ul style="margin: 0; padding-left: 1.5rem; color: #374151; font-size: 0.9rem;">';
        htmlUbicaciones += proceso.ubicaciones.map(ub => `<li>${ub.nombre || ub.descripcion || 'Ubicación sin nombre'}</li>`).join('');
        htmlUbicaciones += '</ul></div>';
    }
    
    const html = `
        <div style="text-align: left;">
            <div style="background: #f3e8ff; border-left: 4px solid #7c3aed; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; color: #1f2937; font-size: 1.1rem;"> PROCESO: ${tipoProc.toUpperCase()}</h3>
            </div>
            
            <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">Observaciones del Proceso:</label>
                <textarea id="editObservacionesProceso" style="width: 100%; padding: 0.75rem; border: 2px solid #3b82f6; border-radius: 6px; font-size: 0.9rem; min-height: 100px;" placeholder="Agrega observaciones del proceso...">${proceso.observaciones || ''}</textarea>
            </div>
            
            ${htmlImagenes}
            ${htmlUbicaciones}
        </div>
    `;
    
    Swal.fire({
        title: ` Editar Proceso - ${tipoProc.toUpperCase()}`,
        html: html,
        width: '600px',
        showConfirmButton: true,
        showCancelButton: true,
        confirmButtonText: ' Guardar Cambios',
        confirmButtonColor: '#7c3aed',
        cancelButtonText: 'Volver',
        customClass: {
            container: 'swal-centered-container',
            popup: 'swal-centered-popup'
        },
        didOpen: (modal) => {
            const container = modal.closest('.swal2-container');
            if (container) {
                container.style.display = 'flex';
                container.style.alignItems = 'center';
                container.style.justifyContent = 'center';
                container.style.height = '100vh';
                container.style.zIndex = '999998';
            }
            modal.style.marginTop = '0';
            modal.style.marginBottom = '0';
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Recopilar imágenes modificadas
            const imagenesActualizadas = [];
            document.querySelectorAll('.imagen-proceso-input').forEach(input => {
                const imgIdx = parseInt(input.dataset.imgIdx) || 0;
                if (input.files && input.files[0]) {
                    imagenesActualizadas.push({
                        index: imgIdx,
                        file: input.files[0],
                        nombre: input.files[0].name
                    });
                }
            });
            
            const cambiosProceso = {
                prendasIndex: prendasIndex,
                procesoIndex: procesoIndex,
                observaciones: document.getElementById('editObservacionesProceso').value,
                imagenesActualizadas: imagenesActualizadas
            };
            

            Swal.fire('', ' Proceso actualizado correctamente', 'success');
        }
    });
}

/**
 * Agregar una nueva fila de tela a la tabla
 */
function agregarFilaTela() {
    const filasTelas = document.getElementById('filasTelas');
    const numFila = filasTelas.children.length;
    
    const fila = document.createElement('div');
    fila.style.cssText = 'display: grid; grid-template-columns: 0.5fr 0.5fr 0.5fr 0.2fr 0.3fr; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center;';
    fila.className = 'fila-tela';
    fila.innerHTML = `
        <input type="text" class="tela-name" placeholder="Nombre tela" style="width: 100%; padding: 0.5rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.9rem;">
        <input type="text" class="tela-color" placeholder="Color" style="width: 100%; padding: 0.5rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.9rem;">
        <input type="text" class="tela-ref" placeholder="Referencia" style="width: 100%; padding: 0.5rem; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 0.9rem;">
        <div style="position: relative; display: flex; align-items: center; gap: 0.25rem;">
            <input type="file" class="tela-imagen" accept="image/*" style="display: none; width: 100%;" title="Cargar imagen de tela">
            <button type="button" class="btn-cargar-imagen-tela" onclick="document.currentScript.parentElement.querySelector('.tela-imagen').click()" style="background: #3b82f6; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 6px; cursor: pointer; font-size: 0.75rem; font-weight: 600; white-space: nowrap;">📷 Imagen</button>
            <span class="tela-imagen-nombre" style="font-size: 0.75rem; color: #666; max-width: 60px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
        </div>
        <button type="button" class="btn-eliminar-fila-tela" onclick="eliminarFilaTela(this)" style="background: #ef4444; color: white; border: none; padding: 0.5rem; border-radius: 6px; cursor: pointer; font-size: 0.75rem; font-weight: 600;">✕</button>
    `;
    
    // Agregar event listener al input file para mostrar el nombre
    const inputFile = fila.querySelector('.tela-imagen');
    const spanNombre = fila.querySelector('.tela-imagen-nombre');
    inputFile.addEventListener('change', function() {
        if (this.files.length > 0) {
            spanNombre.textContent = this.files[0].name;
        }
    });
    
    filasTelas.appendChild(fila);
}

/**
 * Eliminar fila de tela
 */
function eliminarFilaTela(btn) {
    btn.closest('.fila-tela').remove();
}

/**
 * Cerrar modal de prendas
 */
function cerrarModalPrendaNueva() {
    // Cerrar el modal directamente
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        modal.style.setProperty('display', 'none', 'important');
        modal.classList.remove('active');
    }
    
    // Resetear botón de guardar
    const btnGuardar = document.getElementById('btn-guardar-prenda');
    if (btnGuardar) {
        btnGuardar.innerHTML = '<span class="material-symbols-rounded">check</span>Agregar Prenda';
    }
    
    // Limpiar formulario del modal
    const form = document.getElementById('form-prenda-nueva');
    if (form) {
        form.reset();
    }
}

// Exponer funciones globalmente para onclick
window.abrirEditarPrendas = abrirEditarPrendas;
window.abrirEditarPrendaEspecifica = abrirEditarPrendaEspecifica;
window.abrirEditarProcesoEspecifico = abrirEditarProcesoEspecifico;
window.agregarFilaTela = agregarFilaTela;
window.eliminarFilaTela = eliminarFilaTela;
window.cerrarModalPrendaNueva = cerrarModalPrendaNueva;
window.limpiarFormularioPrendaNueva = limpiarFormularioPrendaNueva;
window.cargarPrendaEnFormularioModal = cargarPrendaEnFormularioModal;

/**
 * Función helper: Obtener GestionItemsUI desde cualquier contexto
 * Busca la instancia en window actual, parent, iframes, etc.
 */
window.obtenerGestionItemsUI = function() {
    // Buscar en window actual
    if (window.gestionItemsUI) return window.gestionItemsUI;
    
    // Buscar en parent window
    if (window.parent && window.parent !== window && window.parent.gestionItemsUI) {
        return window.parent.gestionItemsUI;
    }
    
    // Buscar en iframes
    const iframes = document.querySelectorAll('iframe');
    for (let iframe of iframes) {
        try {
            if (iframe.contentWindow && iframe.contentWindow.gestionItemsUI) {
                return iframe.contentWindow.gestionItemsUI;
            }
        } catch (e) {
            // Ignorar errores de cross-origin
        }
    }
    
    return null;
};

/**
 * Función helper: Obtener modal del DOM desde cualquier contexto
 */
window.obtenerModalPrendaNueva = function() {
    // Buscar en document actual
    let modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {

        return modal;
    }
    

    
    // Buscar en iframes
    const iframes = document.querySelectorAll('iframe');

    for (let iframe of iframes) {
        try {
            modal = iframe.contentDocument?.getElementById('modal-agregar-prenda-nueva');
            if (modal) {

                return modal;
            }
        } catch (e) {
            // Ignorar errores de cross-origin
        }
    }
    
    // Buscar en parent
    if (window.parent && window.parent !== window) {
        try {
            modal = window.parent.document.getElementById('modal-agregar-prenda-nueva');
            if (modal) {

                return modal;
            }
        } catch (e) {
            // Ignorar errores de cross-origin
        }
    }
    



    const modalsEnDOM = Array.from(document.querySelectorAll('[id*="modal"]')).map(el => el.id);

    if (modalsEnDOM.length === 0) {

    }
    
    return null;
};

/**
 * Limpiar formulario del modal de prendas
 */
function limpiarFormularioPrendaNueva() {
    const form = document.getElementById('form-prenda-nueva');
    if (form) {
        form.reset();
        
        // Establecer selector de origen a 'confeccion' por defecto
        const origenSelect = document.getElementById('nueva-prenda-origen-select');
        if (origenSelect) {
            origenSelect.value = 'confeccion';
        }
    }
    
    // Limpiar previsualizaciones de fotos
    const prevFoto = document.getElementById('nueva-prenda-foto-preview');
    if (prevFoto) {
        prevFoto.style.backgroundImage = 'none';
        prevFoto.innerHTML = '<div class="foto-preview-content"><div class="material-symbols-rounded">add_photo_alternate</div><div class="foto-preview-text">Agregar</div></div>';
    }
    
    // Limpiar tabla de telas
    const tbodyTelas = document.getElementById('tbody-telas');
    if (tbodyTelas) {
        tbodyTelas.innerHTML = `
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 0.5rem;">
                    <input type="text" id="nueva-prenda-tela" placeholder="TELA..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                </td>
                <td style="padding: 0.5rem;">
                    <input type="text" id="nueva-prenda-color" placeholder="COLOR..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                </td>
                <td style="padding: 0.5rem;">
                    <input type="text" id="nueva-prenda-referencia" placeholder="REF..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                </td>
                <td style="padding: 0.5rem; text-align: center;">
                    <button type="button" onclick="document.getElementById('nueva-prenda-tela-img-input').click()" class="btn btn-primary btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;">
                        <span class="material-symbols-rounded" style="font-size: 1.2rem;">image</span>
                    </button>
                </td>
            </tr>
        `;
    }
    
    // Limpiar storage de imágenes
    if (window.imagenesPrendaStorage) {
        window.imagenesPrendaStorage.limpiar();
    }
    if (window.imagenesTelaStorage) {
        window.imagenesTelaStorage.limpiar();
    }
}

/**
 * Cargar datos de prenda en el formulario del modal
 */
function cargarPrendaEnFormularioModal(prendaData) {
    if (!prendaData) return;
    
    // Cargar datos básicos
    const nombreField = document.getElementById('nueva-prenda-nombre');
    const descripcionField = document.getElementById('nueva-prenda-descripcion');
    const origenSelect = document.getElementById('nueva-prenda-origen-select');
    
    if (nombreField) nombreField.value = prendaData.nombre_producto || prendaData.nombre_prenda || '';
    if (descripcionField) descripcionField.value = prendaData.descripcion || '';
    if (origenSelect) {
        // Si viene de_bodega de la BD: 1/true = bodega, 0/false = confeccion
        if (prendaData.de_bodega !== undefined && prendaData.de_bodega !== null) {
            const origen = (prendaData.de_bodega == 1 || prendaData.de_bodega === true) ? 'bodega' : 'confeccion';
            console.log('🏢 [CARGAR-PRENDA] Asignando origen desde de_bodega:', {de_bodega: prendaData.de_bodega, origen: origen});
            origenSelect.value = origen;
        } else {
            console.log('🏢 [CARGAR-PRENDA] de_bodega no encontrado, usando fallback');
            origenSelect.value = prendaData.origen || 'confeccion';
        }
    }
    

    
    // Cargar imágenes de prenda si existen
    if (prendaData.imagenes && prendaData.imagenes.length > 0) {
        const prevFoto = document.getElementById('nueva-prenda-foto-preview');
        if (prevFoto && prendaData.imagenes[0]) {
            const img = prendaData.imagenes[0];
            const imgUrl = img.url || img.ruta || img.ruta_webp || '';
            if (imgUrl) {
                prevFoto.style.backgroundImage = `url('${imgUrl}')`;
                prevFoto.style.backgroundSize = 'cover';
                prevFoto.style.backgroundPosition = 'center';
            }
        }
    }
    
    // Cargar telas/variantes si existen
    if (prendaData.telasAgregadas && prendaData.telasAgregadas.length > 0) {
        const tbodyTelas = document.getElementById('tbody-telas');
        if (tbodyTelas) {
            tbodyTelas.innerHTML = '';
            
            prendaData.telasAgregadas.forEach((tela, idx) => {
                const row = document.createElement('tr');
                row.style.borderBottom = '1px solid #e5e7eb';
                row.innerHTML = `
                    <td style="padding: 0.5rem;">
                        <input type="text" value="${tela.tela || ''}" placeholder="TELA..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                    </td>
                    <td style="padding: 0.5rem;">
                        <input type="text" value="${tela.color || ''}" placeholder="COLOR..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                    </td>
                    <td style="padding: 0.5rem;">
                        <input type="text" value="${tela.referencia || ''}" placeholder="REF..." class="form-input" style="width: 100%; padding: 0.5rem; text-transform: uppercase;" onkeyup="this.value = this.value.toUpperCase();">
                    </td>
                    <td style="padding: 0.5rem; text-align: center;">
                        <button type="button" onclick="document.getElementById('nueva-prenda-tela-img-input').click()" class="btn btn-primary btn-flex" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;">
                            <span class="material-symbols-rounded" style="font-size: 1.2rem;">image</span>
                        </button>
                    </td>
                </tr>
                `;
                tbodyTelas.appendChild(row);
            });
        }
    }
}

/**
 * Cargar prendas en el datalist para autocomplete
 * Se ejecuta cada vez que el usuario escribe en el campo de nombre de prenda
 */
async function cargarPrendasDatalist() {
    try {
        const inputNombre = document.getElementById('nueva-prenda-nombre');
        const datalist = document.getElementById('lista-prendas-autocomplete');
        
        if (!inputNombre || !datalist) {
            console.log('[cargarPrendasDatalist] No se encontraron elementos del datalist');
            return;
        }
        
        const busqueda = inputNombre.value.trim();
        
        // Realizar búsqueda en el backend
        const url = new URL('/asesores/api/prendas/autocomplete', window.location.origin);
        if (busqueda) {
            url.searchParams.append('q', busqueda);
        }
        
        console.log('[cargarPrendasDatalist] Buscando prendas:', busqueda || 'todas');
        
        const response = await fetch(url.toString(), {
            credentials: 'include'
        });
        
        if (!response.ok) {
            console.error('[cargarPrendasDatalist] Error en la respuesta:', response.status);
            return;
        }
        
        const resultado = await response.json();
        
        if (!resultado.success || !Array.isArray(resultado.prendas)) {
            console.warn('[cargarPrendasDatalist] Respuesta inválida:', resultado);
            return;
        }
        
        // Limpiar opciones anteriores
        datalist.innerHTML = '';
        
        // Agregar opciones del datalist
        resultado.prendas.forEach(prenda => {
            const option = document.createElement('option');
            option.value = prenda.nombre;
            option.dataset.id = prenda.id;
            option.dataset.codigo = prenda.codigo || '';
            option.label = prenda.descripcion ? `${prenda.nombre} - ${prenda.descripcion}` : prenda.nombre;
            datalist.appendChild(option);
        });
        
        console.log(`[cargarPrendasDatalist] Datalist actualizado con ${resultado.prendas.length} prendas`);
        
    } catch (error) {
        console.error('[cargarPrendasDatalist] Error:', error);
    }
}

// Exponer función globalmente
window.cargarPrendasDatalist = cargarPrendasDatalist;
