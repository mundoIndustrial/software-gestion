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
    
    console.log(' [EDITAR-PRENDA] Prenda encontrada:', {
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
        const response = await fetch(`/pedidos-public/${pedidoId}/factura-datos`);
        
        if (!response.ok) {
            throw new Error(`Error ${response.status}: No se pudieron obtener los datos`);
        }
        
        const resultado = await response.json();
        
        if (!resultado.success || !resultado.data) {
            throw new Error('No se recibieron datos válidos del servidor');
        }
        
        //  DEBUG: Mostrar todas las prendas disponibles para comparación
        console.log(' [EDITAR-PRENDA] Prendas disponibles en respuesta:', resultado.data.prendas?.length || 0);
        if (resultado.data.prendas && resultado.data.prendas.length > 0) {
            resultado.data.prendas.forEach((p, idx) => {
                console.log(` [EDITAR-PRENDA] Prenda[${idx}]:`, {
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
        
        //  DEBUG: Mostrar qué estamos buscando
        console.log(' [EDITAR-PRENDA] Buscando prenda con:', {
            buscar_id: prenda.id,
            buscar_prenda_pedido_id: prenda.prenda_pedido_id || prenda.id,
            buscar_nombre: prenda.nombre,
            buscar_nombre_prenda: prenda.nombre_prenda,
            buscar_nombre_producto: prenda.nombre_producto,
            prendasIndex: prendasIndex
        });
        
        // Encontrar la prenda específica en los datos del pedido - BÚSQUEDA BIDIRECCIONAL MEJORADA
        //  FIX: Priorizar búsqueda por prenda_pedido_id que es el identificador más confiable
        const prendaCompleta = resultado.data.prendas?.find(p => {
            // Coincidencia por prenda_pedido_id (PRIORIDAD MÁXIMA - es el ID único de la BD)
            const coincidePrendaPedidoId = (p.prenda_pedido_id === prenda.prenda_pedido_id || 
                                           p.prenda_pedido_id === prenda.id);
            
            // Coincidencia por ID general
            const coincideId = (p.id === prenda.id);
            
            // Coincidencia por índice como último recurso (si el servidor devuelve en mismo orden)
            const coincideIndice = (prendasIndex !== null && prendasIndex !== undefined && 
                                   resultado.data.prendas.indexOf(p) === prendasIndex);
            
            // Coincidencia por nombre (TODAS LAS COMBINACIONES POSIBLES) - baja prioridad
            //  FIX: Evitar comparar undefined === undefined (siempre es true)
            const coincideNombre = (
                // Caso 1: nombre_prenda local == nombre_prenda servidor (AMBOS DEBEN SER VÁLIDOS)
                (prenda.nombre_prenda && p.nombre_prenda && p.nombre_prenda === prenda.nombre_prenda) ||
                // Caso 2: nombre local == nombre servidor  
                (prenda.nombre && p.nombre && p.nombre === prenda.nombre) ||
                // Caso 3: nombre_prenda local == nombre servidor (cruzado - AMBOS DEBEN SER VÁLIDOS)
                (prenda.nombre_prenda && p.nombre && p.nombre === prenda.nombre_prenda) ||
                // Caso 4: nombre local == nombre_prenda servidor (cruzado - AMBOS DEBEN SER VÁLIDOS)
                (prenda.nombre && p.nombre_prenda && p.nombre_prenda === prenda.nombre) ||
                // Caso 5: nombre_producto local == nombre servidor (AMBOS DEBEN SER VÁLIDOS)
                (prenda.nombre_producto && p.nombre && p.nombre === prenda.nombre_producto) ||
                // Caso 6: nombre_producto local == nombre_prenda servidor (AMBOS DEBEN SER VÁLIDOS)
                (prenda.nombre_producto && p.nombre_prenda && p.nombre_prenda === prenda.nombre_producto) ||
                // Caso 7: nombre local == nombre_producto servidor (AMBOS DEBEN SER VÁLIDOS)
                (prenda.nombre && p.nombre_producto && p.nombre_producto === prenda.nombre) ||
                // Caso 8: nombre_prenda local == nombre_producto servidor (AMBOS DEBEN SER VÁLIDOS)
                (prenda.nombre_prenda && p.nombre_producto && p.nombre_producto === prenda.nombre_prenda)
            );
            
            // Si coincide por prenda_pedido_id, es la más confiable (prioridad máxima)
            if (coincidePrendaPedidoId) {
                console.log(' [EDITAR-PRENDA]  Coincidencia por prenda_pedido_id encontrada (ÓPTIMA):', {
                    encontrado_id: p.id,
                    encontrado_prenda_pedido_id: p.prenda_pedido_id,
                    buscado_id: prenda.id,
                    buscado_prenda_pedido_id: prenda.prenda_pedido_id,
                    encontrado_nombre: p.nombre || p.nombre_prenda,
                    buscado_nombre: prenda.nombre || prenda.nombre_prenda
                });
                return true;
            }
            
            // Si coincide por índice, es confiable (segunda prioridad)
            if (coincideIndice) {
                console.log(' [EDITAR-PRENDA]  Coincidencia por índice encontrada:', {
                    encontrado_id: p.id,
                    encontrado_prenda_pedido_id: p.prenda_pedido_id,
                    buscado_id: prenda.id,
                    prendasIndex: prendasIndex,
                    encontrado_nombre: p.nombre || p.nombre_prenda,
                    buscado_nombre: prenda.nombre || prenda.nombre_prenda
                });
                return true;
            }
            
            // Si coincide por ID, es suficiente (tercera prioridad)
            if (coincideId) {
                console.log(' [EDITAR-PRENDA]  Coincidencia por ID encontrada:', {
                    encontrado_id: p.id,
                    encontrado_prenda_pedido_id: p.prenda_pedido_id,
                    buscado_id: prenda.id,
                    encontrado_nombre: p.nombre || p.nombre_prenda,
                    buscado_nombre: prenda.nombre || prenda.nombre_prenda
                });
                return true;
            }
            
            // Si no coincide por ID, requerir coincidencia por nombre (baja prioridad)
            if (coincideNombre) {
                console.log(' [EDITAR-PRENDA]   Coincidencia por nombre encontrada (validar que sea correcta):', {
                    encontrado_nombre: p.nombre || p.nombre_prenda || p.nombre_producto,
                    encontrado_nombre_prenda: p.nombre_prenda,
                    encontrado_nombre_producto: p.nombre_producto,
                    encontrado_id: p.id,
                    encontrado_prenda_pedido_id: p.prenda_pedido_id,
                    buscado_nombre: prenda.nombre || prenda.nombre_prenda || prenda.nombre_producto,
                    buscado_nombre_prenda: prenda.nombre_prenda,
                    buscado_nombre_producto: prenda.nombre_producto,
                    buscado_id: prenda.id,
                    buscado_prenda_pedido_id: prenda.prenda_pedido_id,
                    tipo_coincidencia: 'nombre',
                    precaucion: 'VALIDAR QUE ES LA PRENDA CORRECTA'
                });
                return true;
            }
            
            return false;
        });
        
        if (!prendaCompleta) {
            console.error(' [EDITAR-PRENDA] No se encontró la prenda. Datos de búsqueda:', {
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
        
        //  DEBUG CRÍTICO: Ver exactamente qué propiedades tiene prendaCompleta del servidor
        console.log(' [EDITAR-PRENDA-ESTRUCTURA-COMPLETA] prendaCompleta del servidor tiene:', {
            claves_principales: Object.keys(prendaCompleta),
            tiene_imagenes: !!prendaCompleta.imagenes,
            tiene_fotos: !!prendaCompleta.fotos,
            imagenes_estructura: prendaCompleta.imagenes ? `Array(${prendaCompleta.imagenes.length})` : undefined,
            fotos_estructura: prendaCompleta.fotos ? `Array(${prendaCompleta.fotos.length})` : undefined,
            primerItemImagenes: prendaCompleta.imagenes?.[0],
            primerItemFotos: prendaCompleta.fotos?.[0]
        });
        
        //  DEBUG: Verificar IDs de prendaCompleta
        console.log(' [EDITAR-PRENDA] IDs en prendaCompleta:', {
            id: prendaCompleta.id,
            prenda_pedido_id: prendaCompleta.prenda_pedido_id,
            nombre_prenda: prendaCompleta.nombre_prenda,
            todosLosCampos: Object.keys(prendaCompleta)
        });
        
        console.log(' [EDITAR-PRENDA] Datos del servidor recibidos:', {
            tallas_dama: prendaCompleta.tallas_dama?.length || 0,
            tallas_caballero: prendaCompleta.tallas_caballero?.length || 0,
            colores_telas: prendaCompleta.colores_telas?.length || 0,
            telas_array: prendaCompleta.telas_array?.length || 0,
            variantes: prendaCompleta.variantes?.length || 0,
            procesos: prendaCompleta.procesos?.length || 0
        });
        
        // TRANSFORMAR DATOS PARA EL MODAL
        console.log(' [EDITAR-PRENDA] Transformando datos para el modal...');
        
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
        if (prendaCompleta.generosConTallas && typeof prendaCompleta.generosConTallas === 'object') {
            formatoDetectado.tallas = 'nuevo'; // {DAMA: {L: 20}, SOBREMEDIDA: {DAMA: 34}}
            console.log(' [DETECCIÓN] Formato de tallas detectado: NUEVO (generosConTallas)');
        } else if (prendaCompleta.tallas && typeof prendaCompleta.tallas === 'object' && 
            Object.keys(prendaCompleta.tallas).some(g => ['DAMA', 'CABALLERO', 'UNISEX', 'SOBREMEDIDA'].includes(g.toUpperCase()))) {
            formatoDetectado.tallas = 'nuevo'; // {DAMA: {L: 20}, SOBREMEDIDA: {DAMA: 34}}
            console.log(' [DETECCIÓN] Formato de tallas detectado: NUEVO (objeto por género)');
        } else if ((prendaCompleta.tallas_dama && Array.isArray(prendaCompleta.tallas_dama)) || 
                   (prendaCompleta.tallas_caballero && Array.isArray(prendaCompleta.tallas_caballero))) {
            formatoDetectado.tallas = 'antiguo'; // {tallas_dama: [{talla: "L", cantidad: 20}]}
            console.log(' [DETECCIÓN] Formato de tallas detectado: ANTIGUO (arrays por género)');
        } else {
            console.warn(' [DETECCIÓN] Formato de tallas no reconocido, usando defaults');
        }
        
        // DETECTAR FORMATO DE TELAS
        if (prendaCompleta.telas_array && Array.isArray(prendaCompleta.telas_array) && 
            prendaCompleta.telas_array.length > 0) {
            formatoDetectado.telas = 'nuevo'; // {telas_array: [{id: 1, tela_id: 19, color_id: 61}]}
            console.log(' [DETECCIÓN] Formato de telas detectado: NUEVO (telas_array)');
        } else if (prendaCompleta.colores_telas && Array.isArray(prendaCompleta.colores_telas) && 
                   prendaCompleta.colores_telas.length > 0) {
            formatoDetectado.telas = 'antiguo'; // {colores_telas: [{id: 1, color_id: 61}]}
            console.log(' [DETECCIÓN] Formato de telas detectado: ANTIGUO (colores_telas)');
        } else {
            console.warn(' [DETECCIÓN] Formato de telas no reconocido, usando defaults');
        }
        
        // DETECTAR FORMATO DE VARIANTES
        if (prendaCompleta.variantes && Array.isArray(prendaCompleta.variantes) && 
            prendaCompleta.variantes.length > 0) {
            const v = prendaCompleta.variantes[0];
            if (v.manga || v.broche || v.bolsillos) {
                formatoDetectado.variantes = 'nuevo'; // {manga: "Larga", broche: "Botón"}
                console.log(' [DETECCIÓN] Formato de variantes detectado: NUEVO (campos directos)');
            } else if (v.tipo_manga || v.tipo_broche_boton || v.tiene_bolsillos !== undefined) {
                formatoDetectado.variantes = 'antiguo'; // {tipo_manga: "Larga", tipo_broche_boton: "Botón"}
                console.log(' [DETECCIÓN] Formato de variantes detectado: ANTIGUO (campos prefijados)');
            } else {
                formatoDetectado.variantes = 'mixto'; // Mezcla de formatos
                console.log(' [DETECCIÓN] Formato de variantes detectado: MIXTO');
            }
        } else {
            console.warn(' [DETECCIÓN] Formato de variantes no reconocido, usando defaults');
        }
        
        console.log(' [DETECCIÓN] Formatos detectados:', formatoDetectado);

        // TRANSFORMAR TELAS - Basado en detección automática
        const telasAgregadas = [];
        
        if (formatoDetectado.telas === 'nuevo') {
            // Formato nuevo: {telas_array: [{id: 1, tela_id: 19, color_id: 61, nombre: "ALFONSO"}]}
            console.log(' [TELAS] Procesando formato NUEVO (telas_array):', prendaCompleta.telas_array);
            
            prendaCompleta.telas_array.forEach((ct) => {
                //  FIX: Permitir que color_id sea null (es opcional en la BD)
                // Solo requerimos id y tela_id que son obligatorios
                if (ct && typeof ct === 'object' && ct.id && ct.tela_id) {
                    // DEBUG: Ver TODOS los campos que vienen del servidor
                    console.log(' [TELAS-DEBUG] Estructura COMPLETA de ct del servidor:', {
                        ...ct,
                        tiene_tela_object: typeof ct.tela === 'object',
                        tiene_color_object: typeof ct.color === 'object',
                        ct_keys: Object.keys(ct)
                    });
                    
                    // Validación segura de objetos anidados para evitar null reference
                    const color = (typeof ct.color === 'object') ? (ct.color || {}) : {};
                    const tela = (typeof ct.tela === 'object') ? (ct.tela || {}) : {};
                    
                    // Determinar valores principales
                    const color_principal = (typeof ct.color === 'object') 
                        ? (color.nombre || ct.color_nombre || '')
                        : ct.color; // Si es string directo, usarlo
                    
                    const tela_principal = (typeof ct.tela === 'object')
                        ? (tela.nombre || ct.tela_nombre || '')
                        : ct.tela; // Si es string directo, usarlo
                    
                    telasAgregadas.push({
                        id: ct.id,
                        color_id: ct.color_id,
                        color: color_principal || ct.color_nombre || '',
                        codigo_color: color.codigo || ct.color_codigo || ct.codigo_color || '',
                        tela_id: ct.tela_id,
                        tela: tela_principal || ct.tela_nombre || '',
                        nombre_tela: tela_principal || ct.tela_nombre || '',
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
                    console.log(' [TELAS] Tela transformada (formato nuevo):', telasAgregadas[telasAgregadas.length - 1]);
                } else {
                    console.warn(' [TELAS] Estructura inválida en formato nuevo, omitiendo:', ct);
                }
            });
        } else if (formatoDetectado.telas === 'antiguo') {
            // Formato antiguo: {colores_telas: [{id: 1, color_id: 61, tela_id: 19}]}
            console.log(' [TELAS] Procesando formato ANTIGUO (colores_telas):', prendaCompleta.colores_telas);
            
            prendaCompleta.colores_telas.forEach((ct) => {
                //  FIX: Permitir que color_id sea null (es opcional en la BD)
                if (ct && typeof ct === 'object' && ct.id && ct.tela_id) {
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
                    console.log(' [TELAS] Tela transformada (formato antiguo):', telasAgregadas[telasAgregadas.length - 1]);
                } else {
                    console.warn(' [TELAS] Estructura inválida en formato antiguo, omitiendo:', ct);
                }
            });
        } else {
            // Sin datos - array vacío para no romper el flujo
            console.log(' [TELAS] Sin datos, usando array vacío');
        }
        
        // TRANSFORMAR VARIANTES - Basado en detección automática
        let variantes = {};
        
        if (formatoDetectado.variantes !== 'desconocido' && prendaCompleta.variantes && 
            Array.isArray(prendaCompleta.variantes) && prendaCompleta.variantes.length > 0) {
            
            const v = prendaCompleta.variantes[0];
            console.log(' [VARIANTES] Procesando formato detectado:', formatoDetectado.variantes, v);
            
            //  FIX: Si tipo_manga_id existe pero tipo_manga está vacío, buscar el nombre
            // Prioridad: 1. v.manga (string directo), 2. v.tipo_manga (string), 3. v.manga (objeto)
            let nombreTipoManga = '';
            
            // Prioridad 1: v.manga es un string directo (formato nuevo del servidor)
            if (typeof v.manga === 'string' && v.manga) {
                nombreTipoManga = v.manga;
            }
            // Prioridad 2: v.tipo_manga es un string directo
            else if (typeof v.tipo_manga === 'string' && v.tipo_manga) {
                nombreTipoManga = v.tipo_manga;
            }
            // Prioridad 3: v.manga es un objeto con opcion/nombre
            else if (typeof v.manga === 'object' && v.manga) {
                nombreTipoManga = v.manga.opcion || v.manga.nombre || '';
            }
            
            if ((v.tipo_manga_id || v.manga_id) && !nombreTipoManga) {
                console.log(' [VARIANTES] tipo_manga_id encontrado pero sin nombre, buscando...');
                try {
                    // Intentar cargar tipos de manga si está disponible la función
                    if (typeof cargarTiposMangaDisponibles === 'function') {
                        const tiposManga = await cargarTiposMangaDisponibles();
                        const mangaId = v.tipo_manga_id || v.manga_id;
                        const tipoMangaEncontrado = tiposManga.find(tm => tm.id === mangaId);
                        if (tipoMangaEncontrado) {
                            nombreTipoManga = tipoMangaEncontrado.nombre;
                            console.log('✓ [VARIANTES] Nombre de manga encontrado:', nombreTipoManga);
                        }
                    }
                } catch (error) {
                    console.warn(' [VARIANTES] Error buscando nombre de manga:', error);
                }
            }
            
            //  FIX: Si tipo_broche_boton_id existe pero tipo_broche_boton está vacío, buscar el nombre
            // Prioridad: 1. v.broche (string directo), 2. v.tipo_broche (string), 3. v.tipo_broche_boton (objeto)
            let nombreTipoBroche = '';
            
            // Prioridad 1: v.broche es un string directo (formato nuevo del servidor)
            if (typeof v.broche === 'string' && v.broche) {
                nombreTipoBroche = v.broche;
            }
            // Prioridad 2: v.tipo_broche es un string directo
            else if (typeof v.tipo_broche === 'string' && v.tipo_broche) {
                nombreTipoBroche = v.tipo_broche;
            }
            // Prioridad 3: v.tipo_broche_boton es un objeto con opcion/nombre
            else if (typeof v.tipo_broche_boton === 'object' && v.tipo_broche_boton) {
                nombreTipoBroche = v.tipo_broche_boton.opcion || v.tipo_broche_boton.nombre || '';
            }
            
            if ((v.tipo_broche_boton_id || v.broche_id || v.tipo_broche_id) && !nombreTipoBroche) {
                console.log(' [VARIANTES] tipo_broche_boton_id encontrado pero sin nombre, buscando...');
                try {
                    // Intentar cargar tipos de broche si está disponible la función
                    if (typeof cargarTiposBrocheBotonDisponibles === 'function') {
                        const tiposBroche = await cargarTiposBrocheBotonDisponibles();
                        const brocheId = v.tipo_broche_boton_id || v.broche_id || v.tipo_broche_id;
                        const tipoBrocheEncontrado = tiposBroche.find(tb => tb.id === brocheId);
                        if (tipoBrocheEncontrado) {
                            nombreTipoBroche = tipoBrocheEncontrado.nombre;
                            console.log('✓ [VARIANTES] Nombre de broche encontrado:', nombreTipoBroche);
                        }
                    }
                } catch (error) {
                    console.warn(' [VARIANTES] Error buscando nombre de broche:', error);
                }
            }
            
            // Mapeo universal que funciona con ambos formatos
            variantes = {
                // Campos nuevos (prioridad)
                tipo_manga: nombreTipoManga,
                tipo_manga_id: v.tipo_manga_id || v.manga_id,
                obs_manga: v.manga_obs || v.obs_manga || '',
                tiene_bolsillos: v.tiene_bolsillos !== undefined ? v.tiene_bolsillos : (v.bolsillos || false),
                obs_bolsillos: v.bolsillos_obs || v.obs_bolsillos || '',
                tipo_broche: nombreTipoBroche,
                tipo_broche_id: v.tipo_broche_boton_id || v.broche_id || v.tipo_broche_id,
                obs_broche: v.broche_boton_obs || v.broche_obs || v.obs_broche || '',
                // Campos adicionales
                talla: v.talla || '',
                cantidad: v.cantidad || 0
            };
            
            console.log(' [VARIANTES] Variantes procesadas (formato ' + formatoDetectado.variantes + '):', variantes);
            console.log(' [VARIANTES-DEBUG] MANGA:', {
                nombreTipoManga: nombreTipoManga,
                v_manga: v.manga,
                v_tipo_manga: v.tipo_manga,
                v_manga_id: v.manga_id,
                v_tipo_manga_id: v.tipo_manga_id
            });
            console.log(' [VARIANTES-DEBUG] BROCHE:', {
                nombreTipoBroche: nombreTipoBroche,
                v_broche: v.broche,
                v_tipo_broche: v.tipo_broche,
                v_tipo_broche_boton: v.tipo_broche_boton,
                v_broche_id: v.broche_id,
                v_tipo_broche_boton_id: v.tipo_broche_boton_id,
                v_tipo_broche_id: v.tipo_broche_id
            });
            
        } else {
            // Sin datos - defaults seguros para no romper el flujo
            console.log(' [VARIANTES] Sin datos válidos, usando defaults seguros');
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
            // Formato nuevo: {DAMA: {L: 20, M: 10}, SOBREMEDIDA: {DAMA: 34}}
            console.log(' [TALLAS] Procesando formato NUEVO:', prendaCompleta.tallas || prendaCompleta.generosConTallas);
            
            // Usar generosConTallas si existe, si no usar tallas
            const fuente = prendaCompleta.generosConTallas || prendaCompleta.tallas || {};
            
            Object.entries(fuente).forEach(([genero, tallasGenero]) => {
                const generoNormalizado = genero.toUpperCase();
                
                // Validar que el género sea válido
                if (['DAMA', 'CABALLERO', 'UNISEX', 'SOBREMEDIDA'].includes(generoNormalizado) && 
                    typeof tallasGenero === 'object' && tallasGenero !== null) {
                    
                    // CASO ESPECIAL: SOBREMEDIDA tiene estructura {DAMA: 34, CABALLERO: 20}
                    if (generoNormalizado === 'SOBREMEDIDA') {
                        // SOBREMEDIDA es el género padre, sus claves son sub-géneros
                        Object.entries(tallasGenero).forEach(([subGenero, cantidad]) => {
                            const subGeneroUpper = subGenero.toUpperCase();
                            if (['DAMA', 'CABALLERO', 'UNISEX'].includes(subGeneroUpper)) {
                                if (!tallasPorGenero[subGeneroUpper]) {
                                    tallasPorGenero[subGeneroUpper] = {};
                                }
                                // Usar 'SOBREMEDIDA' como talla especial con cantidad
                                const cantidadValida = (typeof cantidad === 'number' && cantidad >= 0) ? cantidad : 0;
                                if (cantidadValida > 0) {
                                    tallasPorGenero[subGeneroUpper]['SOBREMEDIDA'] = cantidadValida;
                                }
                            }
                        });
                    } else {
                        // Géneros normales: procesar tallas
                        tallasPorGenero[generoNormalizado] = {};
                        Object.entries(tallasGenero).forEach(([talla, cantidad]) => {
                            const cantidadValida = (typeof cantidad === 'number' && cantidad >= 0) ? cantidad : 0;
                            tallasPorGenero[generoNormalizado][talla] = cantidadValida;
                        });
                    }
                }
            });
        } else if (formatoDetectado.tallas === 'antiguo') {
            // Formato antiguo: {tallas_dama: [{talla: "L", cantidad: 20}]}
            console.log(' [TALLAS] Procesando formato ANTIGUO');
            
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
            console.log(' [TALLAS] Sin datos, usando estructura vacía');
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
        
        // Transformar a estructura sin envoltura para generosConTallas - Manejo robusto
        // NOTA: El backend envía {GENERO: {TALLA: CANTIDAD}, SOBREMEDIDA: {DAMA: 34}}
        // NO envolvemos con .cantidades - eso rompe la consistencia con el backend
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
                    //  IMPORTANTE: Pasar directamente SIN envoltura .cantidades
                    generosConTallasEstructura[genero] = cantidadesValidadas;
                    
                    console.log(` [EDITAR-PRENDA] Género ${genero} procesado:`, cantidadesValidadas);
                } else {
                    console.log(` [EDITAR-PRENDA] Género ${genero} sin tallas con cantidad > 0, omitiendo`);
                }
            } else {
                console.warn(` [EDITAR-PRENDA] Estructura inválida para género:`, { genero, tallas });
            }
        });
        
        // Validación final de la estructura
        if (Object.keys(generosConTallasEstructura).length === 0) {
            console.warn(' [EDITAR-PRENDA] No se encontraron tallas válidas en generosConTallasEstructura');
        } else {
            console.log(' [EDITAR-PRENDA] generosConTallasEstructura final:', generosConTallasEstructura);
        }
        
        console.log(' [EDITAR-PRENDA] Tallas como array:', tallasArray);
        console.log(' [EDITAR-PRENDA] generosConTallasEstructura:', generosConTallasEstructura);
        console.log(' [EDITAR-PRENDA] tallasPorGenero (crudo):', tallasPorGenero);
        
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
        
        //  LOG CRÍTICO: Ver EXACTAMENTE qué hay en la primera imagen RAW
        if (prendaImagenesRaw.length > 0) {
            console.log('🖼️ [EDITAR-PRENDA-RAW-DETAIL] ESTRUCTURA DE PRIMERA IMAGEN RAW:', {
                primerImg: prendaImagenesRaw[0],
                claves: Object.keys(prendaImagenesRaw[0]),
                tiene_id: prendaImagenesRaw[0].id !== undefined,
                tiene_ruta_original: prendaImagenesRaw[0].ruta_original !== undefined,
                tiene_ruta_webp: prendaImagenesRaw[0].ruta_webp !== undefined,
                id_valor: prendaImagenesRaw[0].id,
                ruta_original_valor: prendaImagenesRaw[0].ruta_original,
                ruta_webp_valor: prendaImagenesRaw[0].ruta_webp
            });
        }
        
        const prendaImagenesMapeadas = prendaImagenesRaw.map((img, idx) => {
            const url = typeof img === 'string' ? img : (img.ruta_webp || img.ruta_original || img.url);
            const urlFinal = agregarStorage(url);
            
            //  FIX CRÍTICO: Si no tiene ID, crear uno basado en la ruta (workaround)
            // Esto asegura que cada imagen tiene un identificador único incluso si el servidor no lo envía
            let imagenId = img.id;
            if (!imagenId || imagenId === null || imagenId === undefined) {
                // Crear un identificador basado en hash simple de la ruta
                // Esto es temporal hasta que el backend SIEMPRE envíe IDs
                const rutaParaHash = img.ruta_webp || img.ruta_original || img.url || url;
                imagenId = 'hash_' + rutaParaHash.split('/').pop().replace(/\D/g, '').slice(0, 10) || idx;
                console.warn(' [EDITAR-PRENDA-ID-WORKAROUND] Imagen sin ID, creando workaround:', {
                    idx: idx,
                    rutaOriginal: rutaParaHash,
                    idGenerado: imagenId
                });
            }
            
            console.log(`   [${idx}] Mapeo de imagen:`, {
                url: url,
                urlFinal: urlFinal,
                tieneRutaWebp: !!img.ruta_webp,
                tieneRutaOriginal: !!img.ruta_original,
                tieneUrl: !!img.url,
                imageId: imagenId,
                imagePrendaFotoId: img.prenda_foto_id
            });
            return {
                id: imagenId,                                       // ID de prenda_fotos_pedido (o generado si no existe)
                prenda_foto_id: img.prenda_foto_id || imagenId || idx, // Alias para prenda_fotos_pedido
                previewUrl: urlFinal,                               // URL para preview
                url: urlFinal,                                      // URL del accessor
                ruta: urlFinal,                                     // Ruta alternativa
                ruta_original: img.ruta_original || '',             // Ruta original
                ruta_webp: img.ruta_webp || '',                     // Ruta WebP
                nombre: img.nombre || `imagen_${idx}.webp`,         // Nombre de archivo
                urlDesdeDB: true                                    // Indicador de BD
            };
        }).filter(img => {
            // Filtrar imágenes con URLs inválidas
            return img.url && img.url.trim() !== '';
        });
        
        console.log(' [EDITAR-PRENDA-IMAGENES-MAPEADAS] Después del mapeo:', {
            cantidad: prendaImagenesMapeadas.length,
            datos: prendaImagenesMapeadas
        });
        
        //  LOG CRÍTICO: Comparar ANTES vs DESPUÉS del mapeo
        if (prendaImagenesMapeadas.length > 0 && prendaImagenesRaw.length > 0) {
            console.log('🖼️ [EDITAR-PRENDA-MAPEO-COMPARACION] ANTES vs DESPUÉS:', {
                raw_tiene_id: prendaImagenesRaw[0].id !== undefined,
                mapeada_tiene_id: prendaImagenesMapeadas[0].id !== undefined,
                raw_id: prendaImagenesRaw[0].id,
                mapeada_id: prendaImagenesMapeadas[0].id,
                raw_ruta_original: prendaImagenesRaw[0].ruta_original,
                mapeada_ruta_original: prendaImagenesMapeadas[0].ruta_original,
                raw_claves: Object.keys(prendaImagenesRaw[0]),
                mapeada_claves: Object.keys(prendaImagenesMapeadas[0])
            });
        }
        
        // Extraer nombre de prenda - Manejo robusto de múltiples formatos
        const nombrePrenda = prendaCompleta.nombre || 
                           prendaCompleta.nombre_prenda || 
                           prendaCompleta.nombre_producto || 
                           '';
        
        console.log(' [EDITAR-PRENDA] Extracción de nombre:', {
            nombre: prendaCompleta.nombre,
            nombre_prenda: prendaCompleta.nombre_prenda,
            nombre_producto: prendaCompleta.nombre_producto,
            nombre_final: nombrePrenda
        });

        // 🎬 ESTABLECER SNAPSHOT DIRECTAMENTE CON IMÁGENES CORRECTAMENTE MAPEADAS
        // Esto asegura que el snapshot tenga id, ruta_original, ruta_webp
        if (window.imagenesPrendaStorage) {
            // Guardar snapshot ANTES de pasar a modal, con los IDs incluidos
            window.imagenesPrendaStorage.snapshotOriginal = JSON.parse(JSON.stringify(prendaImagenesMapeadas));
            console.log('🎬 [EDITAR-PRENDA] SNAPSHOT ESTABLECIDO CON IMÁGENES MAPEADAS:', {
                cantidad: prendaImagenesMapeadas.length,
                primerImagen: prendaImagenesMapeadas[0]
            });
        }

        const prendaParaEditar = {
            nombre_prenda: nombrePrenda,
            nombre_producto: nombrePrenda,
            descripcion: prendaCompleta.descripcion || '',
            //  CRÍTICO: Incluir imágenes MAPEADAS (con IDs) para que prendaData las tenga
            // Esto asegura que modal-novedad-edicion.js reciba imágenes con id/ruta_original/ruta_webp
            imagenes: prendaImagenesMapeadas,
            //  LÓGICA CORRECTA DE ORIGEN:
            // Prioridad 1: prendaCompleta.origen (si viene establecido)
            // Prioridad 2: prenda.origen (origen anterior)
            // Prioridad 3: Convertir de_bodega a origen (verificar ambos prendaCompleta y prenda)
            // Prioridad 4: Default 'confeccion'
            origen: prendaCompleta.origen || prenda.origen || 
                    ((prendaCompleta.de_bodega !== undefined) ? 
                        (prendaCompleta.de_bodega === false ? 'confeccion' : 'bodega') :
                        ((prenda.de_bodega !== undefined) ?
                            (prenda.de_bodega === false ? 'confeccion' : 'bodega') :
                            'confeccion'
                        )
                    ),
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
        
        console.log(' [EDITAR-PRENDA] Datos listos para cargar en modal:', Object.keys(prendaParaEditar));
        console.log('🔬 [EDITAR-PRENDA] Procesos para modal:', prendaParaEditar.procesos);
        console.log('🖼️ [EDITAR-PRENDA] Imágenes para modal:', prendaParaEditar.imagenes);
        console.log(' [EDITAR-PRENDA] Datos de prendaCompleta:', {
            nombre: prendaCompleta.nombre,
            nombre_prenda: prendaCompleta.nombre_prenda,
            descripcion: prendaCompleta.descripcion,
            de_bodega: prendaCompleta.de_bodega,
            origen: prendaCompleta.origen
        });
        
        console.log(' [EDITAR-PRENDA] LÓGICA DE ORIGEN APLICADA ', {
            'prendaCompleta.origen': prendaCompleta.origen,
            'prenda.origen': prenda.origen,
            'prendaCompleta.de_bodega': prendaCompleta.de_bodega,
            'prenda.de_bodega': prenda.de_bodega,
            'origen_calculado': prendaParaEditar.origen,
            'de_bodega_final': prendaParaEditar.de_bodega
        });
        
        console.log(' [EDITAR-PRENDA] Datos finales en prendaParaEditar:', {
            nombre_prenda: prendaParaEditar.nombre_prenda,
            nombre_producto: prendaParaEditar.nombre_producto,
            descripcion: prendaParaEditar.descripcion,
            origen: prendaParaEditar.origen,
            de_bodega: prendaParaEditar.de_bodega
        });
        console.log(' [EDITAR-PRENDA] Respuesta completa del servidor:', resultado.prenda);
        console.log('🏢 [EDITAR-PRENDA] de_bodega del servidor:', resultado.prenda?.de_bodega, '(1=bodega, 0=confeccion)');
        
        // Cerrar el modal de seleccionar prenda
        Swal.close();
        
        //  DEBUG: Verificar qué se va a guardar en window.prendaEnEdicion
        console.log(' [EDITAR-PRENDA] Guardando en window.prendaEnEdicion:', {
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
            console.log(' [EDITAR-PRENDA] Abriendo modal con GestionItemsUI');
            
            const modal = document.getElementById('modal-agregar-prenda-nueva');
            if (modal && modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
            
            window.gestionItemsUI.prendaEditIndex = prendasIndex;
            window.gestionItemsUI.prendaEnModoEdicion = true;
            window.gestionItemsUI.abrirModalAgregarPrendaNueva();
            
            if (typeof window.gestionItemsUI.cargarItemEnModal === 'function') {
                console.log(' [EDITAR-PRENDA] Cargando datos en modal');
                window.gestionItemsUI.cargarItemEnModal(prendaParaEditar, prendasIndex);
                
                // 🔓 CRÍTICO: Habilitar controles de telas después de cargar los datos
                if (typeof window.habilitarControlsTelasEdicion === 'function') {
                    window.habilitarControlsTelasEdicion();
                }
            }
            
            console.log(' [EDITAR-PRENDA] Modal abierto exitosamente');
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
    const inicioTiempo = performance.now();
    console.log(' [cerrarModalPrendaNueva] INICIANDO cierre del modal...');
    
    try {
        // PASO 1: Resetear prendaEditIndex
        console.log('→ PASO 1: Reseteando prendaEditIndex...');
        if (window.gestionItemsUI) {
            window.gestionItemsUI.prendaEditIndex = null;
        }
        window.prendaEditIndex = null;
        console.log('✓ PASO 1 completado');
        
        // PASO 2: Ocultar el modal
        console.log('→ PASO 2: Ocultando modal...');
        const modal = document.getElementById('modal-agregar-prenda-nueva');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.remove('active');
        }
        console.log('✓ PASO 2 completado - Modal debe estar invisible ahora');
        
        // PASO 3: Resetear botón de guardar
        console.log('→ PASO 3: Reseteando botón guardar...');
        const btnGuardar = document.getElementById('btn-guardar-prenda');
        if (btnGuardar) {
            btnGuardar.innerHTML = '<span class="material-symbols-rounded">check</span>Agregar Prenda';
        }
        console.log('✓ PASO 3 completado');
        
        // PASO 4: Limpiar formulario
        console.log('→ PASO 4: Limpiando formulario...');
        const form = document.getElementById('form-prenda-nueva');
        if (form) {
            form.reset();
        }
        console.log('✓ PASO 4 completado');
        
        // PASO 5: Limpiar telas
        console.log('→ PASO 5: Limpiando arrays de telas...');
        if (window.telasAgregadas) {
            window.telasAgregadas = [];
        }
        if (window.telasCreacion) {
            window.telasCreacion = [];
        }
        const tbodyTelas = document.getElementById('tbody-telas');
        if (tbodyTelas) {
            tbodyTelas.innerHTML = '';
        }
        console.log('✓ PASO 5 completado');
        
        const tiempoTotalMs = performance.now() - inicioTiempo;
        console.log(` [cerrarModalPrendaNueva] Modal cerrado SINCRONAMENTE en ${tiempoTotalMs.toFixed(2)}ms`);
        console.log(` Si ves este mensaje en menos de 100ms, el cierre fue instantáneo`);
        
        // PASO 6: Limpiar asignaciones de colores SIN BLOQUEAR (completamente asíncrono)
        console.log('→ PASO 6: Programando limpieza asíncrona de asignaciones de colores...');
        setTimeout(() => {
            try {
                if (typeof limpiarAsignacionesColores === 'function') {
                    const inicioAsincronoTiempo = performance.now();
                    limpiarAsignacionesColores();
                    const tiempoAsincronoMs = performance.now() - inicioAsincronoTiempo;
                    console.log(`✓ Limpieza asíncrona de colores completada en ${tiempoAsincronoMs.toFixed(2)}ms`);
                }
            } catch (error) {
                console.error(' Error en limpieza asíncrona de colores:', error);
            }
        }, 50);
        console.log('✓ PASO 6 completado (programado para ejecutarse después)');
        
    } catch (error) {
        console.error(' [cerrarModalPrendaNueva] Error:', error);
        console.error('Stack:', error.stack);
    }
}



/**
 * Actualizar título del modal dinámicamente según modo (crear/editar)
 * @param {boolean} esEdicion - true si es modo edición, false si es crear
 */
function actualizarTituloModalPrenda(esEdicion = false) {
    const titulo = document.getElementById('modal-prenda-texto');
    const icon = document.getElementById('modal-prenda-icon');
    
    if (titulo && icon) {
        if (esEdicion) {
            titulo.textContent = 'Editar Prenda';
            icon.textContent = 'edit';
        } else {
            titulo.textContent = 'Agregar Prenda Nueva';
            icon.textContent = 'add_box';
        }
    }
}

// Exponer funciones globalmente para onclick
window.abrirEditarPrendas = abrirEditarPrendas;
window.abrirEditarPrendaEspecifica = abrirEditarPrendaEspecifica;
window.abrirEditarProcesoEspecifico = abrirEditarProcesoEspecifico;
window.agregarFilaTela = agregarFilaTela;
window.eliminarFilaTela = eliminarFilaTela;
window.cerrarModalPrendaNueva = cerrarModalPrendaNueva;
window.actualizarTituloModalPrenda = actualizarTituloModalPrenda;
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
        prevFoto.innerHTML = '<div class="foto-preview-content"><div class="material-symbols-rounded">add_photo_alternate</div><div class="foto-preview-text">Click o arrastra para agregar</div></div>';
        
        // 🔥 IMPORTANTE: Configurar drag & drop después de limpiar
        if (typeof window.setupDragAndDrop === 'function') {
            window.setupDragAndDrop(prevFoto);
        }
    }
    
    // 🔥 CRÍTICO: Limpiar arrays de telas
    if (window.telasAgregadas) {
        window.telasAgregadas = [];
    }
    if (window.telasCreacion) {
        window.telasCreacion = [];
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

/**
 * FUNCIÓN CRÍTICA: Asegurar que TODOS los controles de telas estén habilitados en modo edición
 * Se ejecuta después de cargar los datos en el modal
 */
window.habilitarControlsTelasEdicion = function() {
    console.log('[habilitarControlsTelasEdicion] 🔓 Habilitando controles de telas en modo edición...');
    
    // Esperar a que el DOM esté completamente renderizado
    setTimeout(() => {
        // 1. Habilitar inputs de telas
        const telasInputs = [
            'nueva-prenda-tela',
            'nueva-prenda-color',
            'nueva-prenda-referencia'
        ];
        
        telasInputs.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.disabled = false;
                input.style.pointerEvents = 'auto';
                input.style.opacity = '1';
                input.style.display = '';
                console.log(`  ✓ ${id} habilitado`);
            }
        });
        
        // 2. Habilitar botones de telas  (agregar y eliminar)
        const tbody = document.getElementById('tbody-telas');
        if (tbody) {
            const primeraFila = tbody.querySelector('tr:first-child');
            if (primeraFila) {
                const botones = primeraFila.querySelectorAll('button');
                botones.forEach((btn, idx) => {
                    btn.disabled = false;
                    btn.style.pointerEvents = 'auto';
                    btn.style.opacity = '1';
                    btn.style.display = '';
                    console.log(`  ✓ Botón ${idx} en fila base habilitado`);
                });
            }
        }
        
        // 3. Verificar que las funciones existan
        const funciones = ['agregarTelaNueva', 'eliminarTela', 'actualizarTablaTelas'];
        funciones.forEach(fn => {
            if (typeof window[fn] === 'function') {
                console.log(`  ✓ Función ${fn} disponible`);
            } else {
                console.warn(`  ✗ Función ${fn} NO disponible`);
            }
        });
        
        console.log('[habilitarControlsTelasEdicion] ✓ Todos los controles de telas están habilitados');
    }, 200);
};

// ============================================================
// CONFIGURAR LISTENERS DEL MODAL AL INICIALIZAR
// ============================================================

// Función para configurar los listeners
function configurarListenersModalPrenda() {
    const btnCerrar = document.querySelector('#modal-agregar-prenda-nueva .modal-close-btn');
    const modalOverlay = document.getElementById('modal-agregar-prenda-nueva');
    
    if (!btnCerrar) {
        console.warn(' [Modal] Botón cerrar no encontrado');
        return;
    }
    
    console.log('🔧 [Modal] Configurando listeners...');
    
    // LISTENER DEL BOTÓN CERRAR
    btnCerrar.onclick = function(e) {
        const clickStart = performance.now();
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        console.log('🔘 [Modal] Botón cerrar clickeado → Ejecutando cerrarModalPrendaNueva()');
        console.log(' [Debug] Ejecutando cerrarModalPrendaNueva() ahora...');
        cerrarModalPrendaNueva();
        const clickDuration = performance.now() - clickStart;
        console.log(` [Debug] cerrarModalPrendaNueva() tardó ${clickDuration.toFixed(2)}ms`);
    };
    console.log('✓ Listener del botón cerrar configurado');
    
    // LISTENER PARA CLICK FUERA DEL MODAL
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                console.log('📍 [Modal] Click fuera del modal → Ejecutando cerrarModalPrendaNueva()');
                cerrarModalPrendaNueva();
            }
        });
        console.log('✓ Listener del overlay configurado');
    }
    
    // LISTENER PARA ESC
    window._escListenerModal = function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('modal-agregar-prenda-nueva');
            if (modal && modal.style.display !== 'none') {
                console.log('⌨️ [Modal] ESC presionado → Ejecutando cerrarModalPrendaNueva()');
                cerrarModalPrendaNueva();
            }
        }
    };
    document.addEventListener('keydown', window._escListenerModal);
    console.log('✓ Listener de ESC configurado');
    
    console.log(' [Modal] Todos los listeners configurados exitosamente');
}

// Ejecutar cuando el documento está listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', configurarListenersModalPrenda);
} else {
    // Si el documento ya está listo, ejecutar inmediatamente
    setTimeout(configurarListenersModalPrenda, 100);
}

// ============================================================
// HERRAMIENTA DE DIAGNÓSTICO: DETECTOR DE DELAYS
// ============================================================

// Crear herramienta para diagnosticar qué causa los 3 segundos
window.diagnosticarDelayModalCierre = function() {
    console.log('\n ==================== DIAGNÓSTICO DE DELAY ====================');
    console.log('Para identificar el causante del delay de 3 segundos:\n');
    
    // Listar todos los timers activos
    console.log('📋 Verificando procesos activos...\n');
    
    // Buscar listeners de eventos en el documento
    const modalOverlay = document.getElementById('modal-agregar-prenda-nueva');
    console.log('1️⃣ Modal Overlay:', modalOverlay ? '✓ EXISTE' : ' NO EXISTE');
    
    // Verificar si hay algún setInterval activo
    console.log('2️⃣ setInterval():', '(No se puede listar directamente desde consola)');
    console.log('   → RECOMENDACIÓN: Abre DevTools → Performance → Record y luego intenta cerrar el modal');
    console.log('   → Busca barras largas en la timeline que duren ~3 segundos\n');
    
    // Verificar fetch/XHR pendientes
    console.log('3️⃣ Requests pendientes:');
    // Por desgracia, no podemos acceder directamente a los XHR/fetch pendientes desde consola
    console.log('   → Abre DevTools → Network tab');
    console.log('   → Intenta cerrar el modal');
    console.log('   → Busca alguna request que tarde ~3000ms\n');
    
    // Sugerir pasos de depuración
    console.log(' PASOS DE DEPURACIÓN RECOMENDADOS:\n');
    console.log('1. Abre el archivo gestion-items-pedido.js');
    console.log('2. Busca "setTimeout" y "setInterval"');
    console.log('3. Busca "fetch" y verifica si alguno demora 3 segundos');
    console.log('4. Busca "3000" para encontrar timeouts de 3 segundos\n');
    
    console.log('💡 POSIBLES CULPABLES:\n');
    console.log('- Una petición fetch que espera respuesta');
    console.log('- Un setTimeout de 3000ms programado al abrir el modal');
    console.log('- Un interval que se ejecuta cada 3 segundos');
    console.log('- Validación síncrona que tarda 3 segundos\n');
    
    console.log('===========================================================\n');
};

console.log('💡 Tip: Ejecuta "diagnosticarDelayModalCierre()" en la consola para ver recomendaciones');

