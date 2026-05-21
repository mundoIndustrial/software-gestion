import { httpJson } from '../api/http';
import { mostrarError, mostrarExito } from '../ui/messages';
import { abrirModalCostura } from '../costura/modal-asignacion';

function normalizarColorDistribucion(color) {
    const colorLimpio = String(color || '').trim().toLowerCase();
    if (!colorLimpio || colorLimpio === 'sin color') {
        return 'sin_color';
    }

    return colorLimpio.replace(/\s+/g, '_');
}

function normalizarGeneroDistribucion(genero) {
    const generoLimpio = String(genero || '').trim().toLowerCase();
    if (!generoLimpio || generoLimpio === 'sin género') {
        return 'sin_genero';
    }

    return generoLimpio.replace(/\s+/g, '_');
}

function esperarDatosDistribucion(timeoutMs = 10000) {
    if (window.datosDistribucion) {
        return Promise.resolve(window.datosDistribucion);
    }

    return new Promise((resolve, reject) => {
        let timeoutId = null;

        const onReady = (event) => {
            window.removeEventListener('costura:datos-distribucion-listos', onReady);
            if (timeoutId) clearTimeout(timeoutId);
            resolve(event?.detail || window.datosDistribucion || null);
        };

        window.addEventListener('costura:datos-distribucion-listos', onReady, { once: true });

        timeoutId = setTimeout(() => {
            window.removeEventListener('costura:datos-distribucion-listos', onReady);
            reject(new Error('timeout_datos_distribucion'));
        }, timeoutMs);
    });
}

export function abrirEditarEncargados(btn) {
    const reciboId = btn.dataset.reciboId;
    const prendaId = btn.dataset.prendaId;
    const prendaBodegaId = btn.dataset.prendaBodegaId;
    const numeroRecibo = btn.dataset.numeroRecibo;
    const numeroPedido = btn.dataset.numeroPedido;
    const pedidoId = btn.dataset.pedidoId;
    const nombre = btn.dataset.nombre;
    const tipoRecibo = btn.dataset.tipoRecibo;
    const esReciboBodega = String(tipoRecibo || '').toUpperCase().includes('BODEGA');
    const prendaBodegaIdFinal = prendaBodegaId || (esReciboBodega ? prendaId : null);

    console.log('[EDITAR ENCARGADOS] Abriendo modal para editar:', {
        reciboId,
        prendaId,
        prendaBodegaId: prendaBodegaIdFinal,
        numeroRecibo,
        numeroPedido,
        pedidoId,
        nombre,
        tipoRecibo
    });

    if (!reciboId || !prendaId || !pedidoId || !numeroPedido) {
        mostrarError('Error: Faltan datos necesarios para editar los encargados');
        return;
    }

    // Obtener la distribución actual para cargar los datos en el modal
    const urlApi = `/operario/api/recibos/${reciboId}/distribucion`;
    
    httpJson(urlApi, 'GET')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Abrir el modal de asignación con los datos cargados
                abrirModalCosturaConDatos(
                    pedidoId,
                    prendaId,
                    nombre,
                    tipoRecibo,
                    numeroRecibo,
                    data,
                    numeroPedido,
                    prendaBodegaIdFinal
                );
            } else {
                mostrarError(data.message || 'Error obteniendo la distribución actual');
            }
        })
        .catch(error => {
            console.error('[EDITAR ENCARGADOS] Error:', error);
            mostrarError('Error al obtener la distribución: ' + error.message);
        });
}

function abrirModalCosturaConDatos(
    pedidoId,
    prendaId,
    nombre,
    tipoRecibo,
    recibo,
    datosDistribucion,
    numeroPedido,
    prendaBodegaId = null
) {
    const claveEdicion = `${recibo || ''}|${prendaId || ''}|${tipoRecibo || ''}`;
    window.__edicionDistribucionActiva = {
        clave: claveEdicion,
        precargaAplicada: false,
    };

    // Guardar los datos de parciales para usarlos en modo edición
    // Hacer una copia para poder modificarla sin afectar los datos originales
    window.__datosParcialesEdicion = JSON.parse(JSON.stringify(datosDistribucion?.parciales || []));

    // Abrir el modal normalmente
    abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, null, numeroPedido, null, prendaBodegaId);
    
    // Marcar que estamos en modo edición
    if (window.datosModalCostura) {
        window.datosModalCostura.esEdicion = true;
    }
    
    // Detectar el tipo de asignación original
    const tipoAsignacionOriginal = datosDistribucion?.tipo_asignacion_original || 'modulos';
    console.log('[EDITAR ENCARGADOS] Tipo de asignación original detectado:', tipoAsignacionOriginal);
    
    // Esperar a que el modal esté listo y seleccionar automáticamente la opción correcta
    setTimeout(() => {
        // Seleccionar la opción correcta según el tipo de asignación original
        let opcionASeleccionar = 'distribuir'; // Por defecto
        
        if (tipoAsignacionOriginal === 'taller') {
            opcionASeleccionar = 'taller';
            console.log('[EDITAR ENCARGADOS] Seleccionando opción: Distribuir a Taller');
        } else {
            console.log('[EDITAR ENCARGADOS] Seleccionando opción: Distribuir por Módulos');
        }
        
        if (typeof window.seleccionarOpcionAsignacion === 'function') {
            window.seleccionarOpcionAsignacion(opcionASeleccionar);
        }
        
        // Esperar a que se cargue el contenido
        setTimeout(() => {
            console.log('[EDITAR ENCARGADOS] Verificando si estamos en modo edición...');

            // Si es tipo taller, ir directamente a múltiples talleres
            if (tipoAsignacionOriginal === 'taller') {
                console.log('[EDITAR ENCARGADOS] Saltando selección de tipo taller, yendo a múltiples talleres...');
                if (typeof window.seleccionarTipoTaller === 'function') {
                    window.seleccionarTipoTaller('multiple');
                }

                // Esperar a que se cargue la interfaz de múltiples talleres
                setTimeout(() => {
                    console.log('[EDITAR ENCARGADOS] Cargando datos existentes de talleres...');
                    if (datosDistribucion && datosDistribucion.parciales) {
                        cargarDatosDistribucionExistenteTaller(datosDistribucion.parciales);
                    }
                }, 500);
                return;
            }

            // Para módulos, esperar explícitamente datos de distribución del modal
            esperarDatosDistribucion(12000)
                .then(() => {
                    if (datosDistribucion && datosDistribucion.parciales) {
                        console.log('[EDITAR ENCARGADOS] Cargando datos existentes de módulos...');
                        cargarDatosDistribucionExistente(datosDistribucion.parciales);
                    }
                })
                .catch(() => {
                    console.warn('[EDITAR ENCARGADOS] window.datosDistribucion no está disponible');
                });
        }, 500);
    }, 100);
}

function cargarDatosDistribucionExistenteTaller(parciales) {
    console.log('[CARGAR DATOS TALLER] Iniciando carga de datos para talleres');
    console.log('[CARGAR DATOS TALLER] Parciales recibidos:', parciales);
    
    if (!parciales || parciales.length === 0) {
        console.warn('[CARGAR DATOS TALLER] No hay parciales para cargar');
        return;
    }
    
    // Guardar los datos de parciales para usarlos después de renderizar
    window.parcialesParaCargar = parciales;
    
    // Esperar a que talleresDisponibles esté disponible
    const esperarTalleresDisponibles = () => {
        if (window.talleresDisponibles && window.talleresDisponibles.length > 0) {
            procesarCargarDatosExistenteTaller(parciales);
        } else {
            console.log('[CARGAR DATOS TALLER] Esperando a que talleresDisponibles esté disponible...');
            setTimeout(esperarTalleresDisponibles, 100);
        }
    };
    
    esperarTalleresDisponibles();
}

function procesarCargarDatosExistenteTaller(parciales) {
    // Agrupar parciales por encargado (taller)
    const talleresAgrupados = {};
    parciales.forEach(parcial => {
        const encargado = parcial.encargado || 'SIN ASIGNAR';
        if (!talleresAgrupados[encargado]) {
            talleresAgrupados[encargado] = [];
        }
        talleresAgrupados[encargado].push(parcial);
    });
    
    console.log('[CARGAR DATOS TALLER] Talleres agrupados:', Object.keys(talleresAgrupados));
    
    // Primero, agregar todos los talleres a talleresSeleccionadosDistribucion
    if (!window.talleresSeleccionadosDistribucion) {
        window.talleresSeleccionadosDistribucion = [];
    }
    
    Object.keys(talleresAgrupados).forEach(tallerNombre => {
        // Buscar el taller en talleresDisponibles para obtener su ID
        const tallerDisponible = window.talleresDisponibles?.find(t => t.name === tallerNombre);
        
        if (tallerDisponible && !window.talleresSeleccionadosDistribucion.find(t => t.id === tallerDisponible.id)) {
            console.log(`[CARGAR DATOS TALLER] Agregando taller: ${tallerNombre} (ID: ${tallerDisponible.id})`);
            window.talleresSeleccionadosDistribucion.push({
                id: tallerDisponible.id,
                nombre: tallerDisponible.name
            });
        }
    });
    
    console.log('[CARGAR DATOS TALLER] Talleres seleccionados:', window.talleresSeleccionadosDistribucion);
    
    // Esperar a que la interfaz esté lista y renderizar las cards
    setTimeout(() => {
        console.log('[CARGAR DATOS TALLER] Renderizando cards de talleres...');
        console.log('[CARGAR DATOS TALLER] talleresSeleccionadosDistribucion:', window.talleresSeleccionadosDistribucion);
        
        // Actualizar la lista de talleres seleccionados en la UI
        if (typeof window.actualizarListaTalleresSeleccionados === 'function') {
            window.actualizarListaTalleresSeleccionados();
        }
        
        // Cargar la interfaz de distribución y esperar a que se complete
        if (typeof window.cargarInterfazDistribucionTallerMultiple === 'function') {
            Promise.resolve(window.cargarInterfazDistribucionTallerMultiple(window.talleresSeleccionadosDistribucion))
                .then(() => {
                    // Después de que se renderice, cargar las tallas pre-seleccionadas
                    console.log('[CARGAR DATOS TALLER] Interfaz renderizada, cargando tallas pre-seleccionadas...');
                    cargarTallasPreSeleccionadas(window.parcialesParaCargar);
                })
                .catch(error => {
                    console.error('[CARGAR DATOS TALLER] Error cargando interfaz:', error);
                });
        }
    }, 300);
}

function cargarTallasPreSeleccionadas(parciales) {
    console.log('[CARGAR TALLAS] Iniciando carga de tallas pre-seleccionadas');
    console.log('[CARGAR TALLAS] Parciales:', parciales);
    
    // Esperar un poco más para asegurar que el DOM esté completamente renderizado
    setTimeout(() => {
        // Procesar cada parcial
        parciales.forEach(parcial => {
            const encargado = parcial.encargado || 'SIN ASIGNAR';
            const tallerSeleccionado = window.talleresSeleccionadosDistribucion?.find(t => t.nombre === encargado);
            const tallerId = tallerSeleccionado?.id;
            
            console.log(`[CARGAR TALLAS] Procesando taller: ${encargado} (ID: ${tallerId})`);
            
            // Procesar cada talla del parcial
            (parcial.tallas || []).forEach(tallaDelParcial => {
                const tallaOriginal = tallaDelParcial.talla;
                const cantidadParcial = tallaDelParcial.cantidad || 0;
                const generoDelParcial = tallaDelParcial.genero || 'Sin género';
                const colorDelParcial = tallaDelParcial.color_nombre || 'Sin color';
                
                console.log(`[CARGAR TALLAS] Buscando talla: ${tallaOriginal}, Género: ${generoDelParcial}, Color: ${colorDelParcial}, Cantidad: ${cantidadParcial}`);
                
                // Construir el tallaIdUnico esperado (igual a como se construye en modal-asignacion.js)
                const tallaIdEsperado = `${tallaOriginal}_${normalizarColorDistribucion(colorDelParcial)}_${normalizarGeneroDistribucion(generoDelParcial)}`;
                console.log(`[CARGAR TALLAS] TallaIdEsperado: ${tallaIdEsperado}`);
                
                // Buscar todos los checkboxes para esta talla en este taller
                // Buscar primero con data-tallerid (para talleres) y luego con data-moduloid (para módulos)
                let checkboxes = document.querySelectorAll(`input[type="checkbox"].dist-talla-check[data-tallerid]`);
                if (checkboxes.length === 0) {
                    checkboxes = document.querySelectorAll(`input[type="checkbox"].dist-talla-check[data-moduloid]`);
                }
                console.log(`[CARGAR TALLAS] Total de checkboxes encontrados: ${checkboxes.length}`);
                
                let encontrado = false;
                checkboxes.forEach(checkbox => {
                    const tallaId = checkbox.dataset.tallaid;
                    // Intentar obtener el ID del taller o módulo
                    const tallerModuloId = checkbox.dataset.tallerid || checkbox.dataset.moduloid;
                    
                    console.log(`[CARGAR TALLAS] Comparando: tallaId=${tallaId}===${tallaIdEsperado}, taller/modulo=${tallerModuloId}===${tallerId}`);
                    
                    // Comparar: si el tallaId coincide y el taller/módulo es el correcto
                    if (tallaId === tallaIdEsperado && tallerModuloId == tallerId) {
                        encontrado = true;
                        console.log(`[CARGAR TALLAS] ✓ Encontrado checkbox para: ${tallaId} en taller/módulo ${tallerModuloId}`);
                        
                        // Buscar el input de cantidad
                        // Intentar ambos formatos: taller y módulo
                        let inputCantidad = document.querySelector(`input[id="talla_${tallaId}_taller_${tallerModuloId}"]`);
                        if (!inputCantidad) {
                            inputCantidad = document.querySelector(`input[id="talla_${tallaId}_modulo_${tallerModuloId}"]`);
                        }
                        
                        // Marcar el checkbox
                        checkbox.checked = true;
                        
                        // Establecer la cantidad
                        if (inputCantidad) {
                            inputCantidad.value = cantidadParcial;
                            inputCantidad.disabled = false;
                            console.log(`[CARGAR TALLAS] ✓ Establecida cantidad: ${cantidadParcial} para ${tallaId}`);
                            
                            // Disparar eventos para actualizar la UI
                            inputCantidad.dispatchEvent(new Event('input', { bubbles: true }));
                            inputCantidad.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        
                        // Disparar evento del checkbox
                        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
                
                if (!encontrado) {
                    console.warn(`[CARGAR TALLAS] ✗ No se encontró checkbox para talla: ${tallaIdEsperado} en taller ${tallerId}`);
                }
            });
        });
        
        console.log('[CARGAR TALLAS] Carga de tallas pre-seleccionadas completada');
    }, 2000); // Esperar 2 segundos para asegurar que el DOM esté listo
}

function cargarDatosDistribucionExistente(parciales) {
    if (window.__edicionDistribucionActiva?.precargaAplicada) {
        console.log('[EDITAR ENCARGADOS] Precarga de edición ya aplicada, se omite reprocesar');
        return;
    }

    if (!window.datosDistribucion) {
        // Si no hay datos de distribución, esperar un poco y reintentar
        setTimeout(() => {
            if (window.datosDistribucion) {
                procesarCargarDatosExistente(parciales);
            } else {
                console.warn('[EDITAR ENCARGADOS] No se pudieron cargar los datos de distribución después de esperar');
            }
        }, 500);
        return;
    }
    
    procesarCargarDatosExistente(parciales);
}

function procesarCargarDatosExistente(parciales) {
    console.log('[PROCESAR DATOS] Iniciando procesamiento de datos existentes');
    console.log('[PROCESAR DATOS] window.datosDistribución:', window.datosDistribucion);
    console.log('[PROCESAR DATOS] parciales recibidos:', parciales);
    
    if (!window.datosDistribucion) {
        console.warn('[PROCESAR DATOS] No hay window.datosDistribucion');
        return;
    }
    
    // Limpiar asignaciones anteriores
    window.asignacionesPorModulo = {};
    window.modulosSeleccionadosDistribucion = [];
    
    const modulos = window.datosDistribucion.modulos;
    const tallas = window.datosDistribucion.tallas;
    
    console.log('[PROCESAR DATOS] Módulos disponibles:', modulos);
    console.log('[PROCESAR DATOS] Tallas disponibles:', tallas);
    
    // Crear un mapa de tallas con sus colores para lookup rápido
    const mapaTallasConColores = {};
    tallas.forEach(talla => {
        const clave = `${talla.tallaOriginal || talla.talla}_${talla.genero || ''}`;
        mapaTallasConColores[clave] = talla.color;
        
        // Si la talla tiene colores_detalle, guardarlos también
        if (talla.colores_detalle && Array.isArray(talla.colores_detalle)) {
            mapaTallasConColores[clave + '_detalles'] = talla.colores_detalle;
        }
    });
    
    console.log('[PROCESAR DATOS] Mapa de tallas con colores:', mapaTallasConColores);
    
    parciales.forEach(parcial => {
        const encargado = parcial.encargado_costura || parcial.encargado;
        console.log(`[PROCESAR DATOS] Procesando parcial con encargado: ${encargado}`);
        
        if (!encargado) return;
        
        // Buscar el módulo correspondiente al encargado
        const modulo = modulos.find(m => 
            m.encargado.toLowerCase().trim() === encargado.toLowerCase().trim()
        );
        
        console.log(`[PROCESAR DATOS] Módulo encontrado para ${encargado}:`, modulo);
        
        if (!modulo) return;
        
        // Procesar las tallas de este parcial
        const tallasParcial = parcial.tallas || [];
        console.log(`[PROCESAR DATOS] Tallas del parcial ${encargado}:`, tallasParcial);
        
        tallasParcial.forEach(talla => {
            const nombreTalla = talla.talla;
            const cantidad = parseInt(talla.cantidad) || 0;
            let color = talla.color_nombre || null;
            const genero = talla.genero || talla.sexo || 'Sin género';
            
            // Si el color es null, intentar obtenerlo del mapa de tallas
            if (!color) {
                // Buscar en las tallas disponibles una que coincida
                const tallaDisponible = tallas.find(t => 
                    (t.tallaOriginal || t.talla) === nombreTalla
                );
                
                if (tallaDisponible) {
                    color = tallaDisponible.color;
                    console.log(`[PROCESAR DATOS] Color encontrado para ${nombreTalla}: ${color} (desde tallas disponibles)`);
                }
            }
            
            // Crear ID único que incluya el color (manejar minúsculas y espacios)
            const colorNormalizado = normalizarColorDistribucion(color);
            const generoNormalizado = normalizarGeneroDistribucion(genero);
            const tallaIdUnico = `${nombreTalla}_${colorNormalizado}_${generoNormalizado}`;
            
            console.log(`[PROCESAR DATOS] Procesando talla: ${nombreTalla}, cantidad: ${cantidad}, color: ${color}, género: ${genero}, ID único: ${tallaIdUnico}`);
            
            if (nombreTalla && cantidad > 0) {
                if (!window.asignacionesPorModulo[modulo.id]) {
                    window.asignacionesPorModulo[modulo.id] = {};
                }
                
                // Guardar la talla con su ID único (que incluye color)
                window.asignacionesPorModulo[modulo.id][tallaIdUnico] = {
                    cantidad: cantidad,
                    color: color,
                    genero: genero,
                    tallaOriginal: nombreTalla // Guardar el nombre original para referencia
                };
                
                console.log(`[PROCESAR DATOS] Guardada asignación - Módulo ${modulo.id}, ID ${tallaIdUnico}:`, window.asignacionesPorModulo[modulo.id][tallaIdUnico]);
            }
        });
    });
    
    console.log('[PROCESAR DATOS] Asignaciones finales:', window.asignacionesPorModulo);
    if (window.__edicionDistribucionActiva) {
        window.__edicionDistribucionActiva.precargaAplicada = true;
    }
    
    // Actualizar la interfaz para mostrar los datos cargados
    setTimeout(() => {
        console.log('[PROCESAR DATOS] Intentando actualizar interfaz...');
        console.log('[PROCESAR DATOS] window.datosDistribucion disponible:', !!window.datosDistribucion);
        console.log('[PROCESAR DATOS] window.mostrarCardsEncargados disponible:', typeof window.mostrarCardsEncargados);
        
        if (window.datosDistribucion) {
            const modulosConAsignaciones = Object.keys(window.asignacionesPorModulo || {});
            console.log('[PROCESAR DATOS] Módulos con asignaciones:', modulosConAsignaciones);
            
            // En modo edición, mostrar las cards de encargados en lugar de usar el selector
            if (typeof window.mostrarCardsEncargados === 'function') {
                console.log('[PROCESAR DATOS] Mostrando cards de encargados...');
                window.mostrarCardsEncargados(window.datosDistribucion.tallas, window.datosDistribucion.modulos);
            } else {
                console.error('[PROCESAR DATOS] La función mostrarCardsEncargados no está disponible');
            }
        } else {
            console.error('[PROCESAR DATOS] window.datosDistribucion no está disponible');
        }
    }, 500);
}

export function abrirDistribucionRecibo(btn) {
    const reciboId = btn.dataset.reciboId;
    const prendaId = btn.dataset.prendaId;
    const numeroRecibo = btn.dataset.numeroRecibo;
    const ordenCard = btn.closest('.orden-card-simple');

    console.log('[VER DISTRIBUCIÓN] Toggling distribución:', {
        reciboId,
        prendaId,
        numeroRecibo
    });

    if (!reciboId) {
        mostrarError('Error: No se pudo determinar el ID del recibo');
        return;
    }

    // Buscar si ya existe la sección de distribución (buscar como hermano siguiente)
    let distribucionSection = ordenCard?.nextElementSibling;
    
    // Validar que sea la sección de distribución correcta
    if (distribucionSection && !distribucionSection.classList.contains('distribucion-parciales-section')) {
        distribucionSection = null;
    }
    
    if (distribucionSection) {
        console.log('[VER DISTRIBUCIÓN] Sección encontrada, iniciando toggle');
        console.log('[VER DISTRIBUCIÓN] Clases actuales:', distribucionSection.className);
        
        // Si ya existe, toggle (mostrar/ocultar)
        const isHidden = distribucionSection.classList.contains('hidden');
        console.log('[VER DISTRIBUCIÓN] Está oculta:', isHidden);
        
        distribucionSection.classList.toggle('hidden');
        console.log('[VER DISTRIBUCIÓN] Clases después de toggle:', distribucionSection.className);
        console.log('[VER DISTRIBUCIÓN] style.display:', distribucionSection.style.display);
        
        // Cambiar el texto del botón
        btn.textContent = isHidden ? 'OCULTAR' : 'VER DISTRIBUCIÓN';
        
        // Re-agregar el ícono
        const icon = document.createElement('span');
        icon.className = 'material-symbols-rounded';
        icon.textContent = isHidden ? 'visibility_off' : 'visibility';
        btn.prepend(icon);
        
        console.log('[VER DISTRIBUCIÓN] Toggle completado. Nuevo texto:', btn.textContent);
        return;
    }

    // Si no existe, obtener datos y crear
    obtennerDistribucionParciales(reciboId, numeroRecibo, ordenCard, btn);
}

function obtennerDistribucionParciales(reciboId, numeroRecibo, ordenCard, btn) {
    console.log('[DISTRIBUCION] Obteniendo parciales del recibo:', reciboId);

    const urlApi = `/operario/api/recibos/${reciboId}/distribucion`;
    
    httpJson(urlApi, 'GET')
        .then(response => {
            console.log('[DISTRIBUCION] Response:', response);
            
            if (!response.ok) {
                console.error('[DISTRIBUCION] HTTP Error:', response.status, response.statusText);
                mostrarError(`Error HTTP ${response.status}: ${response.statusText}`);
                return;
            }
            
            return response.json();
        })
        .then(data => {
            if (!data) {
                console.error('[DISTRIBUCION] Sin datos');
                return;
            }
            
            console.log('[DISTRIBUCION] Datos parseados:', data);
            
            if (data.success) {
                console.log('[DISTRIBUCION] Parciales obtenidos exitosamente:', data);
                mostrarDistribucionCards(data, numeroRecibo, ordenCard, btn);
            } else {
                const errorMsg = data.message || 'Error desconocido al obtener distribución';
                console.error('[DISTRIBUCION] Error en respuesta:', errorMsg);
                mostrarError(errorMsg);
            }
        })
        .catch(error => {
            console.error('[DISTRIBUCION] Error en petición:', error);
            console.error('[DISTRIBUCION] Stack trace:', error.stack);
            mostrarError('Error al obtener la distribución de parciales: ' + (error.message || 'Error desconocido'));
        });
}

function mostrarDistribucionCards(datos, numeroRecibo, ordenCard, btn) {
    const parciales = datos.parciales || [];
    const totalParciales = datos.total_parciales || 0;
    const numeroPedido = datos.recibo?.numero_pedido || numeroRecibo; // Obtener número de pedido real de la respuesta

    console.log('[DISTRIBUCION CARDS] Preparando cards con', totalParciales, 'parciales');
    console.log('[DISTRIBUCION CARDS] Número de pedido real:', numeroPedido);
    console.log('[DISTRIBUCION CARDS] Datos de parciales:', parciales);

    if (!ordenCard) {
        console.error('[DISTRIBUCION CARDS] No se encontró orden card');
        return;
    }

    // Crear el HTML de las tarjetas con el número de pedido correcto
    const cardsHTML = crearHTMLDistribucionCards(parciales, numeroPedido, totalParciales, datos.recibo?.id || null);

    // Crear contenedor de distribución
    const distribucionSection = document.createElement('div');
    distribucionSection.className = 'distribucion-parciales-section';
    distribucionSection.innerHTML = cardsHTML;

    // Insertar después de la orden-card
    ordenCard.insertAdjacentElement('afterend', distribucionSection);

    // Cambiar el texto del botón a "OCULTAR" y el ícono
    if (btn) {
        btn.innerHTML = '<span class="material-symbols-rounded">visibility_off</span> OCULTAR';
    }

    console.log('[DISTRIBUCION CARDS] Cards insertadas en el DOM');
}

function crearHTMLDistribucionCards(parciales, numeroRecibo, totalParciales, reciboId = null) {
    if (totalParciales === 0) {
        return `
            <div class="parcial-card parcial-card-vacio">
                <div class="parcial-header">
                    <h4 class="parcial-title">No hay parciales</h4>
                </div>
                <div class="parcial-body">
                    <div class="parcial-info">
                        <span class="material-symbols-rounded">info</span>
                        <p>No hay parciales creados para este recibo #${numeroRecibo}</p>
                    </div>
                </div>
            </div>
        `;
    }

    const rolActual = String(document.body?.dataset?.userRole || window.USUARIO_ACTUAL?.rol || '').toLowerCase();
    const esVistaCostura = rolActual === 'vista-costura';

    // Generar tarjetas para cada parcial
    const parcialCards = parciales.map((parcial, index) => {
        const estadoParcial = String(parcial.proceso_estado || 'En Progreso');
        const badgeClass = `badge-estado-${estadoParcial.toLowerCase().replace(/\s+/g, '-')}`;
        const areaParcial = String(parcial.area || 'SIN ASIGNAR');
        const estaEnControlCalidad = ['control calidad', 'control de calidad'].includes(areaParcial.trim().toLowerCase());
        const esBodega = String(parcial.tipo_recibo || '').toUpperCase() === 'CORTE-PARA-BODEGA';
        const numeroPedidoDetalle = esBodega ? '0' : numeroRecibo;
        const reciboDetalleId = parcial.recibo_id || reciboId || null;
        
        // Generar el HTML de tallas (S: 23, M: 1, L: 20, etc.)
        const tallasHTML = generarTallasHTML(parcial.tallas || []);
        
        return `
            <div class="parcial-card" data-parcial-id="${parcial.id}">
                <div class="parcial-header">
                    <div class="parcial-numero">
                        <h4 class="parcial-title">Parcial #${parcial.consecutivo_parcial}</h4>
                        <span class="parcial-tipo-recibo">${parcial.tipo_recibo}</span>
                    </div>
                    <span class="badge-estado ${badgeClass}">
                        ${estadoParcial}
                    </span>
                </div>
                
                <div class="parcial-body">
                    <div class="parcial-row">
                        <div class="parcial-info-group">
                            <span class="parcial-label">Módulo/Encargado</span>
                            <span class="parcial-value parcial-encargado">
                                <span class="material-symbols-rounded">person</span>
                                ${parcial.encargado || 'SIN ASIGNAR'}
                            </span>
                        </div>
                        <div class="parcial-info-group">
                            <span class="parcial-label">Área</span>
                            <span class="parcial-value parcial-area">
                                <span class="material-symbols-rounded">location_on</span>
                                ${areaParcial}
                            </span>
                        </div>
                    </div>
                    
                    <div class="parcial-row">
                        <div class="parcial-info-group full-width">
                            <span class="parcial-label">Recibo Original</span>
                            <span class="parcial-value">
                                Recibo #${parcial.consecutivo_original}
                            </span>
                        </div>
                    </div>

                    ${tallasHTML ? `
                    <div class="parcial-row parcial-tallas-row">
                        <div class="parcial-tallas-container">
                            ${tallasHTML}
                        </div>
                    </div>
                    ` : ''}

                    <div class="parcial-row parcial-acciones">
                        ${esVistaCostura ? `
                        <button class="btn-pasar-cc"
                                onclick="pasarAControlCalidad(this)"
                                data-pedido-id="${parcial.pedido_produccion_id}"
                                data-prenda-id="${parcial.prenda_pedido_id}"
                                data-tipo-recibo="${String(parcial.tipo_recibo || '').replace(/"/g, '&quot;')}"
                                data-recibo="${parcial.consecutivo_original}"
                                data-parcial-id="${parcial.id}"
                                data-es-parcial="1"
                                data-area="${String(areaParcial).replace(/"/g, '&quot;')}">
                            <span class="material-symbols-rounded">${estaEnControlCalidad ? 'undo' : 'check_circle'}</span>
                            ${estaEnControlCalidad ? 'DESHACER C.C' : 'PASAR A C.C'}
                        </button>
                        ` : ''}
                        <button class="btn-ver-recibo-parcial" 
                                onclick="verReciboParcial(${parcial.id}, '${String(parcial.consecutivo_parcial).replace(/'/g, "\\'")}'  , '${numeroPedidoDetalle}', ${parcial.prenda_pedido_id || 'null'}, ${reciboDetalleId || 'null'})">
                            <span class="material-symbols-rounded">visibility</span>
                            VER RECIBO
                        </button>
                        <button class="btn-anular-parcial" 
                                onclick="anularParcial(${parcial.id}, this)"
                                data-parcial-id="${parcial.id}">
                            <span class="material-symbols-rounded">block</span>
                            ANULAR
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    return parcialCards;
}

function generarTallasHTML(tallas) {
    if (!tallas || tallas.length === 0) {
        console.log('[TALLAS] Sin tallas para este parcial');
        return '';
    }

    console.log('[TALLAS] Procesando tallas:', tallas);

    // Agrupar por género, luego por color, luego sumar cantidades por talla
    const gruposGenero = {};
    
    tallas.forEach(talla => {
        const genero = talla.genero || 'Sin género';
        const color = talla.color_nombre || 'Sin color';
        const nombreTalla = talla.talla.toUpperCase();
        const cantidad = talla.cantidad || 0;
        
        if (!gruposGenero[genero]) {
            gruposGenero[genero] = {};
        }
        
        if (!gruposGenero[genero][color]) {
            gruposGenero[genero][color] = {};
        }
        
        if (!gruposGenero[genero][color][nombreTalla]) {
            gruposGenero[genero][color][nombreTalla] = 0;
        }
        
        gruposGenero[genero][color][nombreTalla] += cantidad;
    });

    console.log('[TALLAS] Grupos por género y color:', gruposGenero);

    // Generar HTML agrupado por género y color
    let html = '';
    
    Object.entries(gruposGenero).forEach(([genero, colores]) => {
        html += `<div class="tallas-genero-group">`;
        html += `<div class="tallas-genero-header">${genero}</div>`;
        
        Object.entries(colores).forEach(([color, tallasCantidades]) => {
            // Mostrar color solo si no es "Sin color"
            const colorDisplay = color !== 'Sin color' ? color : null;
            const colorStyle = colorDisplay ? `background: ${colorDisplay}; border: 1px solid #d1d5db;` : 'background: #f3f4f6; border: 1px solid #d1d5db;';
            
            html += `<div class="tallas-color-group">`;
            
            if (colorDisplay) {
                html += `
                    <div class="tallas-color-header">
                        <span style="display: inline-block; width: 14px; height: 14px; ${colorStyle} border-radius: 3px; margin-right: 0.5rem;"></span>
                        <span>${color}</span>
                    </div>
                `;
            }
            
            // Generar items de tallas para este color
            const tallasItems = Object.entries(tallasCantidades)
                .map(([talla, cantidad]) => `<span class="talla-item">${talla}: <strong>${cantidad}</strong></span>`)
                .join('');
            
            html += `<div class="tallas-items">${tallasItems}</div>`;
            html += `</div>`;
        });
        
        html += `</div>`;
    });

    return html;
}

/**
 * Deshacer un parcial específico
 */
/**
 * Anular un parcial específico (cambiar estado a Anulado)
 */
async function anularParcial(parcialId, btn) {
    const confirmado = await mostrarModalConfirmacionAnularParcial();
    if (!confirmado) {
        return;
    }

    try {
        console.log('[ANULAR PARCIAL] Anulando parcial:', parcialId);

        const response = await fetch(`/operario/api/parciales/${parcialId}/anular`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                estado: 'Anulado'
            })
        });

        console.log('[ANULAR PARCIAL] Response status:', response.status);

        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            console.log('[ANULAR PARCIAL] Parcial anulado exitosamente');
            
            // Cambiar el estilo del botón y deshabilitarlo
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor = 'not-allowed';
            btn.innerHTML = '<span class="material-symbols-rounded">check_circle</span> PARTE ANULADA';
            
            // Cambiar el color de la tarjeta a gris
            const parcialCard = btn.closest('.parcial-card');
            if (parcialCard) {
                parcialCard.style.opacity = '0.6';
                parcialCard.style.backgroundColor = '#f3f4f6';
            }
            
            // Mostrar mensaje de éxito
            showSuccessMessage('Parte anulada correctamente');
        } else {
            console.error('[ANULAR PARCIAL] Error en respuesta:', data);
            alert('Error: ' + (data.message || 'No se pudo anular el parcial'));
        }
    } catch (error) {
        console.error('[ANULAR PARCIAL] Error:', error);
        alert('Error al anular la parte: ' + error.message);
    }
}

function mostrarModalConfirmacionAnularParcial() {
    return new Promise((resolve) => {
        const existente = document.getElementById('modal-confirmar-anular-parcial');
        if (existente) {
            existente.remove();
        }

        const overlay = document.createElement('div');
        overlay.id = 'modal-confirmar-anular-parcial';
        overlay.style.cssText = `
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 1rem;
        `;

        overlay.innerHTML = `
            <div style="width: 100%; max-width: 460px; background: #fff; border-radius: 14px; box-shadow: 0 18px 40px rgba(0,0,0,.25); overflow: hidden;">
                <div style="padding: 1rem 1.25rem; background: #f59e0b; color: #fff; display: flex; align-items: center; gap: .6rem;">
                    <span class="material-symbols-rounded">warning</span>
                    <span style="font-weight: 600;">Anular Parte</span>
                </div>
                <div style="padding: 1.1rem 1.25rem; color: #334155; font-size: .95rem; line-height: 1.45;">
                    ¿Estás seguro de que deseas anular esta parte? El estado cambiará a "Anulado" y no se podrá editar.
                </div>
                <div style="display: flex; justify-content: flex-end; gap: .6rem; padding: 0 1.25rem 1rem;">
                    <button onclick="document.getElementById('modal-confirmar-anular-parcial').remove(); arguments[0].detail = false;" style="padding: .5rem 1rem; background: #e2e8f0; color: #334155; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                        Cancelar
                    </button>
                    <button onclick="document.getElementById('modal-confirmar-anular-parcial').remove();" style="padding: .5rem 1rem; background: #f59e0b; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                        Anular
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        const btnCancelar = overlay.querySelector('button:nth-of-type(1)');
        const btnConfirmar = overlay.querySelector('button:nth-of-type(2)');

        btnCancelar.onclick = () => {
            overlay.remove();
            resolve(false);
        };

        btnConfirmar.onclick = () => {
            overlay.remove();
            resolve(true);
        };

        overlay.onclick = (e) => {
            if (e.target === overlay) {
                overlay.remove();
                resolve(false);
            }
        };
    });
}

function showSuccessMessage(message) {
    const notification = document.createElement('div');
    notification.className = 'notification notification-success';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        z-index: 9999;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Ver detalles del recibo parcial
 * Abre la página de detalles mostrando las tallas asignadas al parcial
 */
async function verReciboParcial(parcialId, consecutivoParcial, numeroPedido, prendaPedidoId, reciboId = null) {
    try {
        // Sanitizar y asegurar tipos correctos
        const sanitizedParcialId = parseInt(parcialId, 10);
        const sanitizedNumeroPedido = String(numeroPedido).trim();
        const sanitizedConsecutivoParcial = String(consecutivoParcial).trim().replace(/[^0-9.]/g, '');
        const sanitizedPrendaId = prendaPedidoId && prendaPedidoId !== 'null' ? parseInt(prendaPedidoId, 10) : null;
        const sanitizedReciboId = reciboId && reciboId !== 'null' ? parseInt(reciboId, 10) : null;

        console.log('[VER RECIBO PARCIAL] Parámetros sanitizados', {
            parcialId: sanitizedParcialId,
            consecutivoParcial: sanitizedConsecutivoParcial,
            numeroPedido: sanitizedNumeroPedido,
            prendaPedidoId: sanitizedPrendaId,
            reciboId: sanitizedReciboId
        });

        if (!sanitizedNumeroPedido || isNaN(sanitizedNumeroPedido)) {
            console.error('[VER RECIBO PARCIAL] numeroPedido es inválido');
            alert('Error: No se pudo determinar el número de pedido');
            return;
        }

        if (!sanitizedParcialId || isNaN(sanitizedParcialId)) {
            console.error('[VER RECIBO PARCIAL] parcialId es inválido');
            alert('Error: ID de parcial inválido');
            return;
        }

        // Construir URL de navegación usando window.location.origin
        const baseUrl = window.location.origin || 'http://localhost:8000';
        let url = baseUrl + '/operario/pedido/' + sanitizedNumeroPedido;
        const params = new URLSearchParams();

        // Parámetros de la prenda
        if (sanitizedPrendaId) {
            params.append('prenda_id', sanitizedPrendaId);
        }

        if (sanitizedReciboId) {
            params.append('recibo_id', sanitizedReciboId);
        }

        // Parámetros del parcial
        params.append('tipo_recibo', 'PARCIAL');
        params.append('parcial_id', sanitizedParcialId);
        params.append('consecutivo_parcial', sanitizedConsecutivoParcial);

        if (params.toString()) {
            url += '?' + params.toString();
        }

        console.log('[VER RECIBO PARCIAL] URL de navegación completa:', url);

        // Navegar a la vista de detalles usando método seguro
        setTimeout(() => {
            window.location.href = url;
        }, 100);

    } catch (error) {
        console.error('[VER RECIBO PARCIAL] Error:', error);
        alert('Error al abrir los detalles del recibo parcial: ' + error.message);
    }
}
        
        // Limpiar después de la animación
// Registrar función global
window.abrirDistribucionRecibo = abrirDistribucionRecibo;
window.anularParcial = anularParcial;
window.verReciboParcial = verReciboParcial;
