// Crear Pedido - Script EDITABLE con soporte para edición y eliminación de prendas
// NOTA: Las funciones de pedido SIN COTIZACIÓN se encuentran en:
// - init-gestor-sin-cotizacion.js: crearPedidoSinCotizacionConGestor(), agregarPrendaSinCotizacionConGestor()
//
// DEPENDENCIAS REQUERIDAS (deben cargar ANTES de este script):
// - utilidades-crear-pedido.js: Inicializa window.fotosEliminadas, FotoHelper, CantidadesManager, ESTILOS_FOTOS

let eliminarImagenTimeout = null;

window.eliminarTallaReflectivo = window.eliminarTallaReflectivo || function(prendaIndex, talla) {
    if (typeof modalConfirmarEliminarTallaReflectivo === 'function') {
        modalConfirmarEliminarTallaReflectivo(talla).then((result) => {
            if (result.isConfirmed) {
                const tallaElement = document.querySelector(`.talla-item-reflectivo[data-talla="${talla}"][data-prenda="${prendaIndex}"]`);
                if (tallaElement) {
                    console.log(`✅ Eliminando talla ${talla} de la prenda ${prendaIndex + 1}`);
                    
                    // GUARDAR CANTIDADES ANTES DE RE-RENDERIZAR
                    guardarCantidadesActuales(prendaIndex);
                    
                    // Eliminar del array de tallas
                    if (window.prendasCargadas && window.prendasCargadas[prendaIndex]) {
                        const tallaIdx = window.prendasCargadas[prendaIndex].tallas?.indexOf(talla);
                        if (tallaIdx >= 0) {
                            window.prendasCargadas[prendaIndex].tallas.splice(tallaIdx, 1);
                        }
                    }
                    
                    tallaElement.remove();
                    modalExito('Talla eliminada', `La talla ${talla} no se incluirá en el pedido`);
                    
                    if (eliminarImagenTimeout) clearTimeout(eliminarImagenTimeout);
                    eliminarImagenTimeout = setTimeout(() => {
                        if (typeof renderizarPrendas === 'function') {
                            console.log(`🔄 Renderizando prendas después de eliminar talla...`);
                            renderizarPrendas();
                            // Restaurar cantidades guardadas después del render
                            setTimeout(() => {
                                restaurarCantidadesGuardadas(prendaIndex);
                            }, 100);
                        }
                        eliminarImagenTimeout = null;
                    }, 200);
                }
            }
        });
    }
};

// ============================================================
// FUNCIÓN GLOBAL: Eliminar Prenda del Pedido
// ============================================================
window.eliminarPrendaDelPedido = function(index) {
    console.log(`🗑️ Eliminando prenda ${index + 1}`);
    
    const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${index}"]`);
    if (prendaCard) {
        prendaCard.remove();
        console.log(`✅ Prenda ${index + 1} eliminada`);
        
        // Si no hay más prendas, mostrar mensaje
        window.prendasContainer = document.getElementById('prendas-container-editable');
        if (window.prendasContainer.querySelectorAll('.prenda-card-editable').length === 0) {
            window.prendasContainer.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <p style="color: #6b7280; margin-bottom: 1rem;">No hay prendas agregadas. Haz clic en el botón de abajo para agregar.</p>
                    <button type="button" onclick="agregarPrendaSinCotizacion()" class="btn btn-primary" style="background: #0066cc; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-plus"></i> Agregar Prenda
                    </button>
                </div>
            `;
        }
    }
};

/**
 * FUNCIÓN HELPER: Procesa imágenes restantes después de eliminar una
 * Actualiza los índices y asegura que todos los datos sean consistentes
 * 
 * @param {number|null} prendaIndex - Índice de la prenda (null si es logo global)
 * @param {string} tipo - Tipo de imagen: 'prenda', 'tela', 'logo' o 'reflectivo'
 */
function procesarImagenesRestantes(prendaIndex, tipo = 'prenda') {
    if (prendaIndex === null || prendaIndex === undefined) {
        // Procesamiento para imágenes globales (logo, reflectivo)
        console.log(`🔄 Procesando imágenes restantes de ${tipo}...`);
        
        if (tipo === 'logo') {
            const imagenesLogo = document.querySelectorAll('img[data-logo-url]');
            console.log(`   📸 Imágenes de logo restantes: ${imagenesLogo.length}`);
            imagenesLogo.forEach((img, idx) => {
                console.log(`     - Logo ${idx + 1} será incluido`);
            });
        } else if (tipo === 'reflectivo') {
            const imagenesReflectivo = document.querySelectorAll('.reflectivo-foto-item');
            console.log(`   📸 Imágenes de reflectivo restantes: ${imagenesReflectivo.length}`);
            imagenesReflectivo.forEach((item, idx) => {
                const fotoId = item.getAttribute('data-foto-id');
                console.log(`     - Reflectivo ID ${fotoId} será incluido`);
            });
        }
    } else {
        // Procesamiento para imágenes de prenda específica
        const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
        
        if (prendasCard) {
            if (tipo === 'prenda') {
                const imagenesPrenda = prendasCard.querySelectorAll('img[data-foto-url]');
                console.log(`🔄 Procesando imágenes restantes de prenda ${prendaIndex + 1}`);
                console.log(`   📸 Imágenes de prenda restantes: ${imagenesPrenda.length}`);
                imagenesPrenda.forEach((img, idx) => {
                    console.log(`     - Foto ${idx + 1} de prenda será incluida`);
                });
            } else if (tipo === 'tela') {
                const imagenesTela = prendasCard.querySelectorAll('img[data-tela-foto-url]');
                console.log(`🔄 Procesando imágenes restantes de telas para prenda ${prendaIndex + 1}`);
                console.log(`   📸 Imágenes de tela restantes: ${imagenesTela.length}`);
                imagenesTela.forEach((img, idx) => {
                    console.log(`     - Foto de tela ${idx + 1} será incluida`);
                });
            }
        }
    }
    
    console.log(`✅ Procesamiento completado. Las imágenes restantes están listas para ser enviadas al servidor.`);
}

/**
 * Aliases para compatibilidad - usar CantidadesManager directamente
 */
window.guardarCantidadesActuales = (prendaIndex) => window.CantidadesManager.guardar(prendaIndex);
window.restaurarCantidadesGuardadas = (prendaIndex) => window.CantidadesManager.restaurar(prendaIndex);

/**
 * FUNCIÓN GLOBAL: Cambiar entre tabs
 * Maneja la activación y desactivación de tabs
 */
window.cambiarTab = function(tabName, element = null) {
    console.log('🔄 Cambiando a tab:', tabName);
    
    // Ocultar todos los tabs
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => {
        tab.classList.remove('active');
        tab.style.display = 'none';
    });
    
    // Mostrar el tab seleccionado
    const tabSeleccionado = document.getElementById(`tab-${tabName}`);
    if (tabSeleccionado) {
        tabSeleccionado.classList.add('active');
        tabSeleccionado.style.display = 'block';
    }
    
    // Actualizar estilos de botones
    const tabButtons = document.querySelectorAll('.tab-button-editable');
    tabButtons.forEach(btn => {
        btn.classList.remove('active');
        btn.style.color = '#64748b';
        btn.style.background = 'none';
        btn.style.borderBottomColor = 'transparent';
    });
    
    // Activar botón del tab actual
    if (element) {
        element.classList.add('active');
        element.style.color = 'white';
        element.style.background = 'linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%)';
        element.style.borderBottomColor = '#0ea5e9';
    } else {
        const activeBtn = document.querySelector(`.tab-button-editable[data-tab="${tabName}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
            activeBtn.style.color = 'white';
            activeBtn.style.background = 'linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%)';
            activeBtn.style.borderBottomColor = '#0ea5e9';
        }
    }
};

// ============================================================
// VARIABLES GLOBALES (fuera del DOMContentLoaded)
// NOTA: Las funciones de modales (galerías, confirmaciones, etc) 
// se encuentran en: modulos/crear-pedido/modales-pedido.js
// ============================================================
let tallasDisponiblesCotizacion = []; // Tallas disponibles en la cotización
let currentLogoCotizacion = null;
let currentEspecificaciones = null;
let currentEsReflectivo = false;
let currentDatosReflectivo = null;
let currentEsLogo = false;
let currentTipoCotizacion = 'P';

// Usar constantes del archivo de configuración
const logoOpcionesPorUbicacion = LOGO_OPCIONES_POR_UBICACION;
const tallaEstandar = TALLAS_ESTANDAR;
const generosDisponibles = GENEROS_DISPONIBLES;
const tecnicasDisponibles = TECNICAS_DISPONIBLES;

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('cotizacion_search_editable');
    const hiddenInput = document.getElementById('cotizacion_id_editable');
    const dropdown = document.getElementById('cotizacion_dropdown_editable');
    const selectedDiv = document.getElementById('cotizacion_selected_editable');
    const selectedText = document.getElementById('cotizacion_selected_text_editable');
    
    window.prendasContainer = document.getElementById('prendas-container-editable');
    const clienteInput = document.getElementById('cliente_editable');
    const asesoraInput = document.getElementById('asesora_editable');
    const formaPagoInput = document.getElementById('forma_de_pago_editable');
    const numeroCotizacionInput = document.getElementById('numero_cotizacion_editable');
    const numeroPedidoInput = document.getElementById('numero_pedido_editable');
    const formCrearPedido = document.getElementById('formCrearPedidoEditable');

    // Variables locales del DOMContentLoaded
    window.prendasCargadas = [];
    let prendasEliminadas = new Set(); // Rastrear índices de prendas eliminadas

    const misCotizaciones = window.cotizacionesData || [];

    // ============================================================
    // BÚSQUEDA Y SELECCIÓN DE COTIZACIÓN
    // ============================================================
    
    function mostrarOpciones(filtro = '') {
        const opciones = filtrarCotizaciones(misCotizaciones, filtro);

        if (misCotizaciones.length === 0) {
            dropdown.innerHTML = '<div style="padding: 1rem; color: #ef4444; text-align: center;"><strong>No hay cotizaciones aprobadas</strong></div>';
        } else if (opciones.length === 0) {
            dropdown.innerHTML = `<div style="padding: 1rem; color: #9ca3af; text-align: center;">No se encontraron cotizaciones</div>`;
        } else {
            dropdown.innerHTML = opciones.map(cot => {
                return `
                    <div onclick="seleccionarCotizacion(${cot.id}, '${cot.numero}', '${cot.cliente}', '${cot.asesora}', '${cot.formaPago}')" 
                         style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; cursor: pointer; transition: background 0.2s;"
                         onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='white'">
                        <div style="font-weight: 600; color: #1f2937;">${cot.numero}</div>
                        <div style="font-size: 0.875rem; color: #6b7280;">${cot.cliente} - ${cot.asesora}</div>
                    </div>
                `;
            }).join('');
        }
        dropdown.style.display = 'block';
    }

    searchInput.addEventListener('focus', () => mostrarOpciones(searchInput.value));
    searchInput.addEventListener('input', (e) => mostrarOpciones(e.target.value));
    document.addEventListener('click', (e) => {
        if (e.target !== searchInput && e.target !== dropdown) {
            dropdown.style.display = 'none';
        }
    });

    window.seleccionarCotizacion = function(id, numero, cliente, asesora, formaPago) {
        hiddenInput.value = id;
        searchInput.value = numero;
        numeroCotizacionInput.value = numero;
        clienteInput.value = cliente;
        asesoraInput.value = asesora;
        formaPagoInput.value = formaPago || '';
        dropdown.style.display = 'none';
        selectedText.textContent = `${numero} - ${cliente}`;
        selectedDiv.style.display = 'block';

        // Cargar prendas
        cargarPrendasDesdeCotizacion(id);
    };

    // ============================================================
    // CARGAR PRENDAS DESDE COTIZACIÓN (VÍA AJAX)
    // ============================================================
    
    function cargarPrendasDesdeCotizacion(cotizacionId) {
        console.log('📥 Cargando prendas de cotización:', cotizacionId);
        
        // Mostrar los pasos 2 y 3
        const seccionInfoPrenda = document.getElementById('seccion-info-prenda');
        const seccionPrendas = document.getElementById('seccion-prendas');
        if (seccionInfoPrenda) seccionInfoPrenda.style.display = 'block';
        if (seccionPrendas) seccionPrendas.style.display = 'block';
        
        fetch(`/asesores/pedidos-produccion/obtener-datos-cotizacion/${cotizacionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    prendasContainer.innerHTML = `<p style="color: #ef4444;">Error: ${data.error}</p>`;
                } else {
                    console.log('Datos de cotización obtenidos:', data);
                    window.prendasCargadas = data.prendas || [];
                    
                    // Determinar tipo y banderas antes de asignar a variables globales
                    window.tipoCotizacion = data.tipo_cotizacion_codigo || 'PL';
                    const esReflectivo = window.tipoCotizacion === 'RF';
                    const esLogo = window.tipoCotizacion === 'L';

                    currentLogoCotizacion = data.logo || null;
                    // ✅ AGREGAR: Asignar logoCotizacionId si existe
                    if (data.logo && data.logo.id) {
                        document.getElementById('logoCotizacionId').value = data.logo.id;
                        console.log('✅ logoCotizacionId asignado:', data.logo.id);
                    }
                    currentEspecificaciones = data.especificaciones || null;
                    currentEsReflectivo = esReflectivo;
                    currentDatosReflectivo = data.reflectivo || null;
                    currentEsLogo = esLogo;
                    currentTipoCotizacion = window.tipoCotizacion;
                    prendasEliminadas.clear(); // Limpiar eliminadas
                    // Asegurar que fotosEliminadas existe antes de limpiar
                    if (!window.fotosEliminadas) window.fotosEliminadas = new Set();
                    window.fotosEliminadas.clear(); // Limpiar fotos marcadas como eliminadas
                    
                    // Inicializar arrays de fotos nuevas cuando se carga una cotización
                    window.prendasFotosNuevas = [];
                    window.telasFotosNuevas = [];
                    window.reflectiveFotosNuevas = [];
                    console.log('🔄 Arrays de fotos nuevas reinicializados');
                    
                    // Extraer todas las tallas disponibles de la cotización
                    tallasDisponiblesCotizacion = [];
                    if (prendasCargadas && prendasCargadas.length > 0) {
                        prendasCargadas.forEach(prenda => {
                            if (prenda.tallas && Array.isArray(prenda.tallas)) {
                                prenda.tallas.forEach(talla => {
                                    if (!tallasDisponiblesCotizacion.includes(talla)) {
                                        tallasDisponiblesCotizacion.push(talla);
                                    }
                                });
                            }
                        });
                    }
                    console.log('📏 Tallas disponibles en la cotización:', tallasDisponiblesCotizacion);
                    
                    // Actualizar forma de pago con los datos completos del servidor
                    if (data.forma_pago) {
                        formaPagoInput.value = data.forma_pago;
                        console.log('✅ Forma de pago actualizada:', data.forma_pago);
                    }
                    
                    // Mostrar información de logos si existe
                    if (data.logo && data.logo.fotos && data.logo.fotos.length > 0) {
                        console.log('Logos encontrados:', data.logo.fotos.length);
                    }
                    
                    // ✅ CARGAR PRENDAS TÉCNICAS DEL LOGO SI EXISTEN
                    if (data.prendas_tecnicas && data.prendas_tecnicas.length > 0) {
                        console.log('✅ Prendas técnicas detectadas en la respuesta:', data.prendas_tecnicas.length);
                        if (typeof cargarLogoPrendasDesdeCotizacion === 'function') {
                            console.log('   Llamando a cargarLogoPrendasDesdeCotizacion()...');
                            cargarLogoPrendasDesdeCotizacion(data.prendas_tecnicas);
                            console.log('   ✅ Prendas técnicas cargadas');
                        } else {
                            console.warn('   ⚠️ cargarLogoPrendasDesdeCotizacion no está disponible');
                        }
                    } else {
                        console.log('⚠️ No hay prendas técnicas en la respuesta:', data.prendas_tecnicas);
                    }
                    
                    // Mostrar especificaciones generales
                    if (data.especificaciones) {
                        console.log('📋 Especificaciones de cotización:', data.especificaciones);
                        console.log('📋 Tipo de especificaciones:', typeof data.especificaciones);
                        console.log('📋 Es array?:', Array.isArray(data.especificaciones));
                    } else {
                        console.log('⚠️ No hay especificaciones en data');
                    }
                    
                    // Pasar tipo de cotización para renderizado diferente
                    console.log('🎯 Tipo de cotización:', tipoCotizacion);
                    console.log('📦 ¿Es Reflectivo?:', esReflectivo);
                    console.log('🎨 ¿Es Logo?:', esLogo);
                    console.log('📊 Data Logo:', data.logo);
                    
                    // GUARDAR ID DEL LOGO COTIZACION para usar después
                    if (esLogo && data.logo) {
                        logoCotizacionId = data.logo.id;
                        console.log('🎨 LogoCotizacion ID guardado:', logoCotizacionId);
                    }
                    
                    // Cambiar título y alerta dinámicamente
                    const paso3Titulo = document.getElementById('paso3_titulo_logo');
                    const paso3Alerta = document.getElementById('paso3_alerta_logo');
                    const tituloPrendasDinamico = document.getElementById('titulo-prendas-dinamico');
                    
                    console.log('📌 paso3Titulo element:', paso3Titulo);
                    console.log('📌 paso3Alerta element:', paso3Alerta);
                    console.log('📌 tituloPrendasDinamico element:', tituloPrendasDinamico);
                    
                    if (paso3Titulo && paso3Alerta) {
                        if (esLogo) {
                            // Actualizar solo el texto del título
                            paso3Titulo.textContent = 'Pedido de Logo';
                            paso3Alerta.innerHTML = 'ℹ️ Completa la información del logo: descripción, ubicaciones, técnicas y observaciones.';
                        } else {
                            paso3Titulo.textContent = 'Prendas y Cantidades (Editables)';
                            paso3Alerta.innerHTML = 'ℹ️ Puedes editar los campos de cada prenda, cambiar cantidades por talla, o eliminar prendas que no desees incluir en el pedido.';
                        }
                        console.log('✅ Título y alerta actualizados');
                    } else {
                        console.warn('⚠️ No se encontraron los elementos paso3_titulo_logo o paso3_alerta_logo');
                    }
                    
                    // Actualizar el título dinámico junto al círculo del índice 3
                    if (tituloPrendasDinamico) {
                        if (esLogo) {
                            tituloPrendasDinamico.textContent = 'Información del Logo';
                        } else if (tipoPedido === 'REFLECTIVO') {
                            tituloPrendasDinamico.textContent = 'Prendas Reflectivo';
                        } else if (tipoPedido === 'PRENDA') {
                            tituloPrendasDinamico.textContent = 'Prendas';
                        } else {
                            tituloPrendasDinamico.textContent = 'Prendas Técnicas del Logo';
                        }
                        console.log('✅ Título dinámico actualizado:', tituloPrendasDinamico.textContent);
                    } else {
                        console.warn('⚠️ No se encontró el elemento titulo-prendas-dinamico');
                    }
                    
                    // Mostrar/ocultar botón "Agregar Prenda Técnica" solo cuando hay cotización seleccionada
                    const btnAgregarPrendaTecnica = document.getElementById('btn-agregar-prenda-tecnica-logo');
                    if (btnAgregarPrendaTecnica) {
                        if (esLogo) {
                            btnAgregarPrendaTecnica.style.display = 'block';
                            console.log('✅ Botón "Agregar Prenda Técnica" mostrado');
                        } else {
                            btnAgregarPrendaTecnica.style.display = 'none';
                            console.log('✅ Botón "Agregar Prenda Técnica" ocultado');
                        }
                    }
                    
                    renderizarPrendasEditables(
                        prendasCargadas,
                        currentLogoCotizacion,
                        currentEspecificaciones,
                        currentEsReflectivo,
                        currentDatosReflectivo,
                        currentEsLogo,
                        currentTipoCotizacion
                    );
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                prendasContainer.innerHTML = `<p style="color: #ef4444;">Error al cargar las prendas: ${error.message}</p>`;
            });
    }

    // ============================================================
    // RENDERIZAR PRENDAS EDITABLES
    // ============================================================
    
    window.renderizarPrendasEditables = function renderizarPrendasEditables(prendas, logoCotizacion = null, especificacionesCotizacion = null, esReflectivo = false, datosReflectivo = null, esLogo = false, tipoCotizacion = 'P') {
        try {
            console.log(`\n🎯 INICIANDO renderizarPrendasEditables`);
            console.log(`   Prendas recibidas: ${prendas?.length || 0}`);
            console.log(`   window.prendasCargadas: ${window.prendasCargadas?.length || 0}`);
            if (window.prendasCargadas) {
                window.prendasCargadas.forEach((p, i) => {
                    console.log(`     [${i}] ${p.nombre_producto}: ${p.fotos?.length || 0} fotos`);
                });
            }
            console.log(`   window.fotosEliminadas: ${window.fotosEliminadas?.size || 0} elementos`);
            
            // Reset galerías por prenda
            window.prendasGaleria = [];
            window.telasGaleria = [];

        // Estilos responsivos para botón "Agregar talla"
        if (!document.getElementById('tallas-btn-responsive-style')) {
            const style = document.createElement('style');
            style.id = 'tallas-btn-responsive-style';
            style.textContent = `
                .btn-agregar-talla-texto { display: inline; }
                @media (max-width: 640px) {
                    .btn-agregar-talla-texto { display: none; }
                }
            `;
            document.head.appendChild(style);
        }

        // Usar FotoHelper para gestionar URLs de fotos (definido en utilidades-crear-pedido.js)
        const fotoToUrl = window.FotoHelper.toUrl.bind(window.FotoHelper);

        if (!prendas || prendas.length === 0) {
            // Si no hay prendas pero hay LOGO, mostrar nuevo diseño de TARJETAS de prendas técnicas
            if (esLogo && logoCotizacion) {
                console.log('🎨 RENDERIZANDO COTIZACIÓN TIPO LOGO (con nuevo diseño de tarjetas)');
                
                // Guardar datos globales
                window.currentTipoCotizacion = tipoCotizacion;
                window.currentEsLogo = esLogo;
                
                // Mostrar el nuevo diseño (desde integracion-logo-pedido-tecnicas.js)
                if (typeof mostrarSeccionPrendasTecnicasLogoNuevo === 'function') {
                    mostrarSeccionPrendasTecnicasLogoNuevo();
                } else {
                    console.warn('⚠️ mostrarSeccionPrendasTecnicasLogoNuevo no está disponible');
                }
                return;
            }
            prendasContainer.innerHTML = '<p class="text-gray-500 text-center py-8">Esta cotización no tiene prendas</p>';
            return;
        }

        // Si es REFLECTIVO, mostrar información completa y editable
        if (esReflectivo) {
            const htmlReflectivo = renderizarReflectivo(prendas, datosReflectivo);
            
            // ✅ AGREGAR ATRIBUTO data-tipo-cotizacion AL CONTENEDOR
            prendasContainer.setAttribute('data-tipo-cotizacion', tipoCotizacion);
            prendasContainer.innerHTML = htmlReflectivo;
            return;
        }

        // Crear estructura de tabs
        let html = '';
        let prendasTabHtml = '';
        let logoTabHtml = '';
        
        // Verificar si hay prendas y logo para mostrar los tabs correspondientes
        const tienePrendas = prendas && prendas.length > 0;
        
        // Usar helper para determinar si debe renderizarse tab de logo
        const tieneLogoPrendas = debeRenderizarLogoTab(tipoCotizacion, logoCotizacion);
        
        // LOG para debugging
        console.log('🔍 VERIFICACIÓN TABS:');
        console.log('  - tipoCotizacion:', tipoCotizacion);
        console.log('  - tienePrendas:', tienePrendas);
        console.log('  - logoCotizacion:', logoCotizacion);
        console.log('  - tieneLogoPrendas:', tieneLogoPrendas);
        if (logoCotizacion) {
            console.log('  - logoCotizacion.descripcion:', logoCotizacion.descripcion);
            console.log('  - logoCotizacion.tecnicas:', logoCotizacion.tecnicas);
            console.log('  - logoCotizacion.ubicaciones:', logoCotizacion.ubicaciones);
            console.log('  - logoCotizacion.fotos:', logoCotizacion.fotos);
        }
        
        // Crear estructura de tabs solo si hay prendas O hay logo
        if (tienePrendas || tieneLogoPrendas) {
            console.log('🔵 CREANDO TABS:');
            console.log('   - tienePrendas:', tienePrendas);
            console.log('   - tieneLogoPrendas:', tieneLogoPrendas);
            
            // Tab Navigation - USANDO TEMPLATES
            const tabsContainerHTML = window.templates.tabsContainer();
            console.log('   - tabsContainer HTML:', tabsContainerHTML.substring(0, 100) + '...');
            html += tabsContainerHTML;
            
            if (tienePrendas) {
                const prendasBtn = window.templates.tabButton('PRENDAS', 'fas fa-box', true);
                console.log('   - PRENDAS btn HTML:', prendasBtn.substring(0, 100) + '...');
                html += prendasBtn;
            }
            
            if (tieneLogoPrendas) {
                const isActive = !tienePrendas;
                const logoBtn = window.templates.tabButton('LOGO', 'fas fa-tools', isActive);
                console.log('   - LOGO btn HTML:', logoBtn.substring(0, 100) + '...');
                console.log('   - LOGO isActive:', isActive);
                html += logoBtn;
            }
            
            html += `</div>`;
            console.log('   ✅ Cierre de tabs container');
            
            // Tab Content Wrapper - USANDO TEMPLATES
            html += window.templates.tabContentWrapper();
            
            // Tab Prendas
            if (tienePrendas) {
                html += window.templates.tabContent('tab-prendas', true);
            }
        }

        prendas.forEach((prenda, index) => {
            // Saltar si la prenda fue eliminada
            if (prendasEliminadas.has(index)) {
                return;
            }

            const tallas = prenda.tallas || [];

            // Incorporar fotos nuevas agregadas desde el navegador
            const nuevasFotosPrenda = (window.prendasFotosNuevas && window.prendasFotosNuevas[index]) ? window.prendasFotosNuevas[index] : [];
            
            console.log(`\n📸 RENDERIZANDO PRENDA ${index}`);
            console.log(`   Fotos originales: ${(prenda.fotos || []).length}`);
            console.log(`   Fotos nuevas: ${nuevasFotosPrenda.length}`);
            
            // Deduplicar fotos: combinar base + nuevas sin duplicados
            // IMPORTANTE: Usar window.prendasCargadas[index].fotos que puede haber sido modificado
            const fotosBase = (window.prendasCargadas && window.prendasCargadas[index]) 
                ? window.prendasCargadas[index].fotos 
                : (prenda.fotos || []);
            let fotos = [...fotosBase];
            
            console.log(`   URLs originales:`, fotosBase.map(f => fotoToUrl(f)));
            
            // Agregar fotos nuevas solo si no están ya en la base
            nuevasFotosPrenda.forEach(fotoNueva => {
                const yaExiste = fotosBase.some(f => {
                    const urlBase = fotoToUrl(f);
                    const urlNueva = fotoToUrl(fotoNueva);
                    return urlBase === urlNueva;
                });
                if (!yaExiste) {
                    fotos.push(fotoNueva);
                }
            });
            
            console.log(`   Fotos combinadas (antes filtrado): ${fotos.length}`);
            console.log(`   URLs combinadas:`, fotos.map(f => fotoToUrl(f)));
            
            // FILTRAR fotos que han sido eliminadas (por URL)
            const fotosAntes = fotos.length;
            fotos = fotos.filter(foto => {
                const fotoUrl = fotoToUrl(foto);
                const estaEliminada = window.fotosEliminadas.has(fotoUrl);
                if (estaEliminada) {
                    console.log(`   🗑️ Filtrando (ELIMINADA): ${fotoUrl}`);
                } else if (window.fotosEliminadas.size > 0) {
                    console.log(`   ✓ Conservando: ${fotoUrl}`);
                }
                return !estaEliminada;
            });
            console.log(`   📊 ANÁLISIS POST-FILTRADO:`);
            console.log(`      Fotos ANTES filtrado: ${fotosAntes}`);
            console.log(`      Fotos DESPUÉS filtrado: ${fotos.length}`);
            console.log(`      URLs después filtrado:`, fotos.map(f => fotoToUrl(f)));
            console.log(`      window.fotosEliminadas.size: ${window.fotosEliminadas.size}`);
            console.log(`      Fotos finales: ${fotos.length} (eliminadas: ${window.fotosEliminadas.size})\n`);

            // Telas: incorporar fotos nuevas por tela
            const telaFotosBase = prenda.telaFotos || [];
            let telaFotos = [...telaFotosBase];
            if (window.telasFotosNuevas && window.telasFotosNuevas[index]) {
                Object.entries(window.telasFotosNuevas[index]).forEach(([telaIdx, fotosArr]) => {
                    fotosArr.forEach(f => {
                        // Marcar tela_id por índice para que el render las asigne
                        telaFotos.push({
                            ...f,
                            tela_id: prenda.telas?.[telaIdx]?.id ?? prenda.telaFotos?.[telaIdx]?.tela_id ?? null
                        });
                    });
                });
            }
            // Normalizar fotos de prenda
            const fotosNormalizadas = (fotos || []).map(f => fotoToUrl(f)).filter(Boolean);
            console.log(`   📊 fotosNormalizadas después de filtrar eliminadas:`, fotosNormalizadas);
            window.prendasGaleria[index] = fotosNormalizadas; // Guardar para navegación con flechas
            let fotoPrincipal = fotosNormalizadas.length > 0 ? fotosNormalizadas[0] : null;
            let fotosAdicionales = fotosNormalizadas.slice(1);
            console.log(`   🖼️ fotoPrincipal: ${fotoPrincipal ? fotoPrincipal.substring(0, 80) + '...' : 'NULL'}`);
            console.log(`   🎯 fotosAdicionales: ${fotosAdicionales.length}`);
            if (!fotoPrincipal && fotosAdicionales.length > 0) {
                fotoPrincipal = fotosAdicionales[0];
                fotosAdicionales = fotosAdicionales.slice(1);
                console.log(`   ⚠️ Asignando fotoPrincipal desde adicionales`);
            }
            const variantes = prenda.variantes || {};

            // LOG PARA DEBUGUEO
            console.log(`👕 Prenda ${index}:`, prenda);
            console.log(`   - telaFotos recibidas:`, telaFotos);
            console.log(`   - variantes.telas_multiples:`, variantes.telas_multiples);
            if (variantes.telas_multiples && variantes.telas_multiples.length > 0) {
                variantes.telas_multiples.forEach((tela, idx) => {
                    console.log(`      - Tela ${idx}: id=${tela.id}, nombre=${tela.nombre_tela}, color=${tela.color}`);
                });
            }

            let nombreProenda = prenda.nombre_producto || '';
            const variacionesPrincipales = [];
            if (variantes.color) variacionesPrincipales.push(variantes.color);
            if (variacionesPrincipales.length > 0) {
                nombreProenda += ' (' + variacionesPrincipales.join(' - ') + ')';
            }

            // Generar HTML de tallas editables (TABLA ESTILO SIMILAR A TELAS)
            let tallasHtml = '';
            if (tallas.length > 0) {
                tallasHtml = '<div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">';
                
                // Encabezado de tabla usando template
                tallasHtml += window.templates.tallasTableHeader(index);
                
                // Filas de tallas usando template
                tallas.forEach(talla => {
                    tallasHtml += window.templates.tallaRow(index, talla);
                });
                
                tallasHtml += '</div>';
            }

            // Género (removido del formulario visual pero se mantiene en datos)

            // Generar HTML de variaciones de variantes (TABLA EDITABLE CON ELIMINACIÓN)
            let variacionesHtml = '';
            const variacionesArray = [];
            
            // Recopilar todas las variaciones en un array
            if (variantes.tipo_manga) {
                variacionesArray.push({
                    tipo: 'Manga',
                    valor: variantes.tipo_manga,
                    obs: variantes.obs_manga,
                    campo: 'tipo_manga',
                    esCheckbox: false
                });
            }
            // Siempre mostrar Broche/Botón como opción disponible
            variacionesArray.push({
                tipo: 'Broche/Botón',
                valor: variantes.tipo_broche || '',
                obs: variantes.obs_broche || '',
                campo: 'tipo_broche',
                esCheckbox: false
            });
            if (variantes.tiene_bolsillos !== undefined) {
                variacionesArray.push({
                    tipo: 'Bolsillos',
                    valor: variantes.tiene_bolsillos ? 'Sí' : 'No',
                    obs: variantes.obs_bolsillos,
                    campo: 'tiene_bolsillos',
                    esCheckbox: true
                });
            }
            if (variantes.tiene_reflectivo !== undefined) {
                variacionesArray.push({
                    tipo: 'Reflectivo',
                    valor: variantes.tiene_reflectivo ? 'Sí' : 'No',
                    obs: variantes.obs_reflectivo,
                    campo: 'tiene_reflectivo',
                    esCheckbox: true
                });
            }
            
            if (variacionesArray.length > 0) {
                variacionesHtml = '<div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">';
                variacionesHtml += window.templates.variacionesTableHeader();
                
                variacionesArray.forEach((variacion, varIdx) => {
                    let inputHtml = '';
                    if (variacion.esCheckbox) {
                        // Para campos booleanos, mostrar selector Sí/No
                        const isYes = variacion.valor === true || variacion.valor === 'Sí' || variacion.valor === 1;
                        inputHtml = `<select 
                                           data-field="${variacion.campo}" 
                                           data-prenda="${index}"
                                           data-variacion="${varIdx}"
                                           style="width: 100%; padding: 0.4rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem; transition: border-color 0.2s; box-sizing: border-box; cursor: pointer; background-color: white;">
                                        <option value="No" ${!isYes ? 'selected' : ''}>No</option>
                                        <option value="Sí" ${isYes ? 'selected' : ''}>Sí</option>
                                   </select>`;
                    } else {
                        // Para campos de texto, convertir a selectores con opciones predefinidas
                        let opciones = [];
                        let selectedValue = variacion.valor ? variacion.valor.trim() : '';
                        
                        // Definir opciones según el tipo de variación
                        if (variacion.campo === 'tipo_manga') {
                            opciones = ['No aplica', 'Corta', 'Larga'];
                        } else if (variacion.campo === 'tipo_broche') {
                            opciones = ['No aplica', 'Broche', 'Botón'];
                        } else {
                            opciones = ['No aplica', selectedValue || 'Personalizado'];
                        }
                        
                        // Construir HTML del select con opciones
                        let selectOptions = '<option value="">-- Seleccionar --</option>';
                        
                        // Agregar todas las opciones
                        opciones.forEach(opcion => {
                            const trimmedOpcion = opcion.trim();
                            const isSelected = (selectedValue === trimmedOpcion || selectedValue === opcion) ? 'selected' : '';
                            selectOptions += '<option value="' + trimmedOpcion + '" ' + isSelected + '>' + trimmedOpcion + '</option>';
                        });
                        
                        // Si el valor no está en la lista predefinida, agregarlo
                        if (selectedValue && !opciones.includes(selectedValue) && selectedValue !== '' && selectedValue !== 'No aplica') {
                            selectOptions += '<option value="' + selectedValue + '" selected>' + selectedValue + '</option>';
                        }
                        
                        inputHtml = `<select 
                                           data-field="${variacion.campo}" 
                                           data-prenda="${index}"
                                           data-variacion="${varIdx}"
                                           style="width: 100%; padding: 0.4rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem; transition: border-color 0.2s; box-sizing: border-box; cursor: pointer; background-color: white;">
                                        ${selectOptions}
                                   </select>`;
                    }
                    
                    variacionesHtml += window.templates.variacionRow(index, varIdx, variacion, inputHtml);
                });
                
                variacionesHtml += '</div>';
            }

            // Generar HTML de telas/colores múltiples (EDITABLE - MODERNO Y RESPONSIVO)
            let telasHtml = '';
            // Combinar prenda.telas (con IDs) con variantes.telas_multiples (con detalles)
            const telasMapeadas = [];
            const telasMultiples = variantes.telas_multiples || [];
            const telasDelServidor = prenda.telas || [];
            
            // Mapear: usar telas_multiples como fuente principal, pero agregar IDs de prenda.telas
            telasMultiples.forEach((telaMult, idx) => {
                const telaDelServidor = telasDelServidor[idx];
                telasMapeadas.push({
                    id: telaDelServidor?.id || null,
                    nombre_tela: telaMult.nombre_tela || telaMult.tela || '',
                    color: telaMult.color || '',
                    referencia: telaDelServidor?.referencia || telaMult.referencia || '',
                });
            });
            
            const telasParaTabla = telasMapeadas.length > 0 ? telasMapeadas : telasMultiples;
            
            if (telasParaTabla && telasParaTabla.length > 0) {
                // Detectar si tela_id es null en TODAS las fotos (para hacer distribución por orden)
                const todasLasFotosConTelaIdNull = telaFotos.length > 0 && telaFotos.every(f => f.tela_id === null);
                
                console.log(`   - Todas las fotos con tela_id null? ${todasLasFotosConTelaIdNull}`);
                console.log(`   - Total de fotos: ${telaFotos.length}, Total de telas: ${telasParaTabla.length}`);
                console.log(`   - Telas mapeadas:`, telasMapeadas);
                
                // Si todas las fotos tienen tela_id null, distribuirlas por orden
                const fotosDistribuidas = {};
                if (todasLasFotosConTelaIdNull && telaFotos.length > 0) {
                    const fotosXTela = Math.ceil(telaFotos.length / telasParaTabla.length);
                    telasParaTabla.forEach((tela, telaIdx) => {
                        const inicio = telaIdx * fotosXTela;
                        const fin = inicio + fotosXTela;
                        fotosDistribuidas[telaIdx] = telaFotos.slice(inicio, fin);
                        console.log(`   - Tela ${telaIdx}: fotos ${inicio}-${fin-1}`);
                    });
                }
                
                telasHtml = '<div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">';
                telasHtml += window.templates.telasTableHeader(index);
                
                telasParaTabla.forEach((tela, telaIdx) => {
                    // Obtener fotos específicas de esta tela
                    let fotosDeTela = [];
                    
                    if (todasLasFotosConTelaIdNull) {
                        // Usar fotos distribuidas por orden
                        fotosDeTela = fotosDistribuidas[telaIdx] || [];
                    } else {
                        // Filtrar fotos por tela_id (cuando exista)
                        const telaId = tela.id;
                        fotosDeTela = telaId ? (telaFotos || []).filter(f => f.tela_id === telaId) : [];
                    }

                    // Agregar fotos nuevas en memoria asociadas por índice de tela
                    const fotosNuevas = (window.telasFotosNuevas && window.telasFotosNuevas[index] && window.telasFotosNuevas[index][telaIdx]) ? window.telasFotosNuevas[index][telaIdx] : [];
                    fotosDeTela = [...fotosDeTela, ...fotosNuevas];

                    // Quitar duplicados por URL para evitar que se repitan al renderizar
                    const vistos = new Set();
                    fotosDeTela = fotosDeTela.filter(f => {
                        const u = fotoToUrl(f);
                        if (!u) return false;
                        if (vistos.has(u)) return false;
                        vistos.add(u);
                        return true;
                    });

                    // FILTRAR FOTOS DE TELA ELIMINADAS (igual que fotos de prenda)
                    fotosDeTela = fotosDeTela.filter(foto => {
                        const fotoUrl = fotoToUrl(foto);
                        return !window.fotosEliminadas.has(fotoUrl);
                    });

                    // Normalizar URLs para galería de tela
                    const fotosTelaNormalizadas = fotosDeTela.map(f => fotoToUrl(f)).filter(Boolean);
                    if (!window.telasGaleria[index]) window.telasGaleria[index] = [];
                    window.telasGaleria[index][telaIdx] = fotosTelaNormalizadas;
                    
                    console.log(`   - Tela ${telaIdx} (id=${tela.id}): fotos encontradas=${fotosDeTela.length}`);
                    
                    
                    let fotosTelaHtml = '';
                    if (fotosDeTela.length > 0) {
                        // Mantener orden original (primera agregada primero)
                        const fotoPrincipalTela = fotosDeTela[0];
                        const restantes = Math.max(0, fotosDeTela.length - 1);
                        const fotoUrl = fotoPrincipalTela?.url || fotoPrincipalTela?.ruta_webp || fotoPrincipalTela?.ruta_original || fotoPrincipalTela?.preview || '';
                        fotosTelaHtml = `
                            <div style="width: 100%; max-width: 110px; margin: 0 auto; border: 2px solid #1e40af; border-radius: 10px; background: #f0f7ff; padding: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.06); display: flex; flex-direction: column; align-items: center; gap: 0.4rem;">
                                <div style="position: relative; width: 90px; height: 90px; overflow: hidden; border-radius: 8px; border: 1px solid #d0d0d0; box-shadow: 0 1px 4px rgba(0,0,0,0.08); background: white;">
                                    ${fotoUrl ? `
                                        <img src="${fotoUrl}" alt="Foto de tela"
                                             data-prenda-index="${index}"
                                             data-tela-index="${telaIdx}"
                                             style="width: 100%; height: 100%; object-fit: cover; cursor: pointer; transition: transform 0.2s;"
                                             onclick="abrirGaleriaTela(${index}, ${telaIdx}, 0); return false;"
                                             title="Click para ver galería; use flechas ← →">
                                    ` : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:0.8rem;">Sin foto</div>'}
                                    ${restantes > 0 ? `<span style="position:absolute; bottom:6px; right:6px; background:#1e40af; color:white; padding:2px 6px; border-radius:12px; font-size:0.75rem; font-weight:700;">+${restantes}</span>` : ''}
                                    <button type="button"
                                            onclick="eliminarImagenTela(this)"
                                            style="position: absolute; top: 6px; right: 6px; background: #dc3545; color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2); z-index: 8;"
                                            title="Eliminar imagen">×</button>
                                </div>
                                <button type="button"
                                        onclick="abrirModalAgregarFotosTela(${index}, ${telaIdx})"
                                        style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-weight: 900; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 3px 8px rgba(14,165,233,0.2);"
                                        title="Agregar foto de tela"><i class="fas fa-plus" style="font-size: 0.75rem;"></i></button>
                            </div>
                        `;
                    } else {
                        fotosTelaHtml = `
                            <div style="width: 100%; max-width: 110px; margin: 0 auto; border: 2px dashed #1e40af; border-radius: 10px; background: #f0f7ff; padding: 0.5rem; display: flex; flex-direction: column; align-items: center; gap: 0.35rem;">
                                <div style="font-size: 0.8rem; color: #1e3a8a; font-weight: 600; text-align: center;">Sin fotos</div>
                                <button type="button"
                                        onclick="abrirModalAgregarFotosTela(${index}, ${telaIdx})"
                                        style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-weight: 900; font-size: 0.95rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 3px 8px rgba(14,165,233,0.2);"
                                        title="Agregar foto de tela"><i class="fas fa-plus" style="font-size: 0.75rem;"></i></button>
                            </div>
                        `;
                    }
                    
                    // Obtener valores de tela desde el objeto mapeado
                    const nombreTela = tela.nombre_tela || '';
                    const colorTela = typeof tela.color === 'object' ? (tela.color?.nombre || tela.color?.name || '') : (tela.color || '');
                    const referenciaTela = tela.referencia || '';
                    
                    console.log(`   - Tela ${telaIdx}: nombre="${nombreTela}", color="${colorTela}", referencia="${referenciaTela}"`);
                    
                    telasHtml += window.templates.telaRow(index, telaIdx, tela, fotosTelaHtml);
                });
                telasHtml += '</div>';
            }

            // Generar HTML de fotos de prenda (mostrar 1 y badge +N) - SIEMPRE RENDERIZAR
            let fotosHtml = '';
            console.log(`\n   🎨 GENERANDO HTML GALERÍA PRENDA ${index}`);
            console.log(`      fotoPrincipal: ${fotoPrincipal ? 'SÍ' : 'NO'}`);
            console.log(`      fotosNormalizadas: ${fotosNormalizadas.length}`);
            console.log(`      window.fotosEliminadas.size: ${window.fotosEliminadas.size}`);
            
            // SIEMPRE RENDERIZAR LA GALERÍA, pero con contenido diferente si está vacía
            const fotosMostrar = fotosNormalizadas.slice(0, 1);
            const restantes = fotosNormalizadas.length - fotosMostrar.length;
            
            if (fotoPrincipal) {
                console.log(`      ✅ Renderizando galería CON fotos`);
                console.log(`         fotosMostrar: ${fotosMostrar.length}`);
                console.log(`         restantes: ${restantes}`);
                console.log(`         Mostrará: ${fotoPrincipal.substring(0, 60)}...`);
                if (restantes > 0) console.log(`         Badge: +${restantes} más`);
            } else {
                console.log(`      ℹ️ Galería VACÍA - permitir agregar fotos`);
            }

            fotosHtml += `
                <div style="position: relative; width: 100%; border: 2px solid #1e40af; border-radius: 10px; background: #f0f7ff; padding: 0.75rem 0.75rem 0.6rem 0.75rem; box-shadow: 0 6px 16px rgba(0,0,0,0.06);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                        <div style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">Galería de la prenda</div>
                        <button type="button"
                                onclick="abrirModalAgregarFotosPrenda(${index})"
                                style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-weight: 900; font-size: 1.2rem; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(14,165,233,0.25);"
                                title="Agregar foto">
                            ＋
                        </button>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.65rem;">
                        ${fotoPrincipal ? `
                            ${fotosMostrar.map((foto) => `
                                <div style="position: relative; width: 100%; aspect-ratio: 1 / 1; max-height: 180px; overflow: hidden; border-radius: 8px; border: 1px solid #d1d5db; box-shadow: 0 2px 6px rgba(0,0,0,0.08); background: white;">
                                    <img src="${foto}" alt="Foto prenda"
                                         class="prenda-foto-thumb"
                                         data-prenda-index="${index}"
                                         data-foto-idx="${fotosNormalizadas.indexOf(foto)}"
                                         style="width: 100%; height: 100%; object-fit: cover; cursor: pointer; transition: transform 0.2s;"
                                         onclick="abrirGaleriaPrenda(${index}, ${fotosNormalizadas.indexOf(foto)})">
                                    <button type="button"
                                            onclick="eliminarImagenPrenda(this)"
                                            style="position: absolute; top: 8px; right: 8px; background: #dc3545; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2); z-index: 10; transform: translate(0,0); opacity: 0.95;" title="Eliminar imagen">×</button>
                                </div>
                            `).join('')}
                            ${restantes > 0 ? `<div style="width: 100%; aspect-ratio: 1/1; max-height: 180px; display:flex; align-items:center; justify-content:center; border: 1px dashed #1e40af; border-radius: 8px; background: #e0f2fe; color: #1e40af; font-weight: 700; font-size: 0.95rem;">+${restantes} más</div>` : ''}
                        ` : `
                            <div style="width: 100%; aspect-ratio: 1/1; max-height: 180px; display:flex; align-items:center; justify-content:center; border: 2px dashed #9ca3af; border-radius: 8px; background: #f3f4f6; color: #6b7280; font-weight: 600; font-size: 0.9rem; text-align: center; padding: 1rem; flex-direction: column;">
                                <i class="fas fa-folder-open" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                                <span>Sin fotos</span><br><span style="font-size: 0.8rem; font-weight: 400;">Agrega fotos para mostrar aquí</span>
                            </div>
                        `}
                    </div>
                </div>
            `;

            
            // Crear tarjeta completa usando templates
            html += window.templates.prendaCardStart(index, nombreProenda);
            html += window.templates.prendaCardContent(index, prenda, fotosHtml);
            html += variacionesHtml;
            html += tallasHtml;
            html += telasHtml;
            html += window.templates.prendaCardClose();
        });

        // Cerrar tab de prendas si existe y abrir tab de logo
        if (tienePrendas || tieneLogoPrendas) {
            if (tienePrendas) {
                html += window.templates.prendasTabClose();
            }

            if (tieneLogoPrendas) {
                html += window.templates.logoTabContainer();
            }
        }

        // ========== SECCIÓN DE LOGO COMPLETA (para cotizaciones combinadas) ==========
        // 🔍 LÓGICA: No renderizar logo si es tipo 'P' (PRENDA únicamente)
        // ✅ IMPORTANTE: Usar la misma condición que tieneLogoPrendas para ser consistente
        if (tieneLogoPrendas) {
            // Función helper para parsear datos JSON
            function parseArrayData(data) {
                if (!data) return [];
                if (Array.isArray(data)) return data;
                if (typeof data === 'string') {
                    try {
                        return JSON.parse(data);
                    } catch (e) {
                        console.warn('⚠️ No se pudo parsear:', data);
                        return [];
                    }
                }
                return [];
            }

            // Parsear ubicaciones
            let ubicacionesArray = parseArrayData(logoCotizacion.ubicaciones);
            let logoSeccionesSeleccionadasTab = [];
            
            // Cargar ubicaciones iniciales
            if (ubicacionesArray && ubicacionesArray.length > 0) {
                ubicacionesArray.forEach(ubicacion => {
                    if (typeof ubicacion === 'object' && ubicacion.ubicacion) {
                        logoSeccionesSeleccionadasTab.push({
                            id: window.generarUUID(),
                            ubicacion: ubicacion.ubicacion,
                            opciones: Array.isArray(ubicacion.opciones) ? ubicacion.opciones : [],
                            tallas: Array.isArray(ubicacion.tallas) ? ubicacion.tallas.map(t => t.talla || t) : [],
                            tallasCantidad: ubicacion.tallasCantidad || (Array.isArray(ubicacion.tallas) ? ubicacion.tallas.reduce((acc, t) => {
                                acc[t.talla || t] = t.cantidad || 0;
                                return acc;
                            }, {}) : {}),
                            observaciones: ubicacion.observaciones || ''
                        });
                    }
                });
            }
            
            // Asignar a variable global para acceso desde modales
            window.logoSeccionesSeleccionadasTab = logoSeccionesSeleccionadasTab;
            
            // ========== DESCRIPCIÓN - USANDO TEMPLATES ==========
            html += window.templates.logoDescripcion(logoCotizacion.descripcion);
            
            // ========== FOTOS - USANDO TEMPLATES ==========
            html += window.templates.logoFotosGaleriaStart();
            
            // Cargar fotos iniciales
            if (logoCotizacion.fotos && logoCotizacion.fotos.length > 0) {
                logoCotizacion.fotos.forEach((foto) => {
                    const fotoUrl = foto.url || foto.ruta_webp || foto.ruta_original;
                    if (fotoUrl) {
                        html += `<div style="position: relative; display: inline-block; width: 100%;">
                            <img src="${fotoUrl}" 
                                 alt="Foto" 
                                 style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 1px solid #d0d0d0;" 
                                 onclick="abrirModalImagen('${fotoUrl}', 'Foto del logo')">
                            <button type="button" 
                                    style="position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 0.9rem; display: flex; align-items: center; justify-content: center;">×</button>
                        </div>`;
                    }
                });
            }
            
            html += window.templates.logoFotosGaleriaEnd();
            
            // ========== TÉCNICAS - USANDO TEMPLATES ==========
            html += window.templates.logoTecnicasSelectorAndTable();
            
            // ========== OBSERVACIONES DE TÉCNICAS - USANDO TEMPLATES ==========
            html += window.templates.logoObservacionesTecnicas(logoCotizacion.observaciones_tecnicas);
            
            // ========== TALLAS A COTIZAR - CONSOLIDADAS EN UBICACIONES (ELIMINAR) ==========
            // Las tallas ahora se manejan dentro de cada sección en el modal de ubicaciones
            // Esta tabla vieja ha sido eliminada
            

            // ========== UBICACIÓN (TABLA EDITABLE - USANDO TEMPLATES) ==========
            html += window.templates.logoUbicacionesTabla();
            
            // Llenar filas de ubicaciones
            const tbody = document.getElementById('logo-ubicaciones-tbody-tab');
            if (tbody) {
                tbody.innerHTML = '';
                if (logoSeccionesSeleccionadasTab && logoSeccionesSeleccionadasTab.length > 0) {
                    logoSeccionesSeleccionadasTab.forEach((seccion) => {
                        if (!seccion.id) {
                            seccion.id = window.generarUUID();
                        }
                        const seccionId = seccion.id;
                        const tallasConCantidad = seccion.tallas && seccion.tallas.length > 0 
                            ? seccion.tallas.map(t => `${t} (${seccion.tallasCantidad && seccion.tallasCantidad[t] ? seccion.tallasCantidad[t] : 0})`).join(', ')
                            : '—';
                        const rowHtml = `<tr style="border-bottom: 1px solid #e5e7eb; transition: all 0.2s;" data-seccion-id="${seccionId}" onmouseover="this.style.backgroundColor = '#f8fafb';" onmouseout="this.style.backgroundColor = 'white';">
                            <td style="padding: 1rem; border-right: 1px solid #e5e7eb;">
                                <strong style="font-weight: 600; color: #1f2937;">${seccion.ubicacion}</strong>
                            </td>
                            <td style="padding: 1rem; border-right: 1px solid #e5e7eb; font-size: 0.8rem; color: #666;">
                                ${tallasConCantidad}
                            </td>
                            <td style="padding: 1rem; border-right: 1px solid #e5e7eb;">
                                <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;" id="opciones-${seccionId}">
                                    ${seccion.opciones && seccion.opciones.length > 0 ? seccion.opciones.map((opcion, opIdx) => `
                                        <span style="display: inline-flex; align-items: center; gap: 0.3rem; background: #dbeafe; color: #1976d2; padding: 0.4rem 0.7rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;">
                                            ${opcion}
                                            <button type="button" onclick="eliminarUbicacionItemTab('${seccionId}', ${opIdx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.85rem; line-height: 1; margin-left: 0.1rem;">×</button>
                                        </span>
                                    `).join('') : '<span style="color: #999; font-size: 0.75rem;">Sin ubicaciones</span>'}
                                </div>
                            </td>
                            <td style="padding: 1rem; border-right: 1px solid #e5e7eb;">
                                <textarea class="logo-ubicacion-obs-tab" data-seccion-id="${seccionId}" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.75rem; min-height: 40px; resize: vertical; font-family: inherit; background: #fafafa; box-sizing: border-box;"
                                          onfocus="this.style.borderColor = '#0066cc'; this.style.backgroundColor = 'white';"
                                          onblur="this.style.borderColor = '#d0d0d0'; this.style.backgroundColor = '#fafafa';"
                                          placeholder="...">${seccion.observaciones || ''}</textarea>
                            </td>
                            <td style="padding: 1rem; text-align: center; display: flex; gap: 0.4rem; justify-content: center;">
                                <button type="button" onclick="editarSeccionLogoTab('${seccionId}')" title="Editar sección" style="background: #3b82f6; color: white; border: none; padding: 0.5rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.9rem; transition: all 0.2s; min-width: 35px; hover: background: #2563eb;">✏</button>
                                <button type="button" onclick="eliminarSeccionLogoTab('${seccionId}')" title="Eliminar sección" style="background: #dc3545; color: white; border: none; padding: 0.5rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.9rem; transition: all 0.2s; min-width: 35px; hover: background: #c82333;">✕</button>
                            </td>
                        </tr>`;
                        tbody.innerHTML += rowHtml;
                    });
                } else {
                    tbody.innerHTML = `<tr><td colspan="5" style="padding: 1rem; text-align: center; color: #999;">Sin ubicaciones definidas. Agrega una haciendo clic en el botón +</td></tr>`;
                }
            }
            
            // Cerrar tab-logo después de todas las secciones
            html += window.templates.logoTabContainerClose();
        }

        // Cerrar tab-content-wrapper si se crearon tabs
        if (tienePrendas || tieneLogoPrendas) {
            html += '</div>'; // cierra tab-content-wrapper
        }
        
        // ✅ AGREGAR ATRIBUTO data-tipo-cotizacion AL CONTENEDOR
        prendasContainer.setAttribute('data-tipo-cotizacion', tipoCotizacion);
        
        console.log('🟢 RENDERIZANDO HTML EN DOM');
        console.log('   - Largo total del HTML:', html.length);
        console.log('   - Primer 200 chars:', html.substring(0, 200));
        console.log('   - Buscando tabs en HTML...');
        const tabsMatch = html.match(/<div style="[^"]*flex[^"]*">/);
        console.log('   - Tabs container encontrado:', !!tabsMatch);
        const buttonMatch = html.match(/<button[^>]*tab-button-editable[^>]*>/g);
        console.log('   - Cantidad de buttons:', buttonMatch ? buttonMatch.length : 0);
        
        prendasContainer.innerHTML = html;
        
        console.log('Prendas y logo renderizados con información completa');
        
        // Verificar después de insertar en el DOM
        setTimeout(() => {
            const tabsDiv = prendasContainer.querySelector('#tabs-container-pedido');
            console.log('🔍 VERIFICACIÓN POST-DOM:');
            console.log('   - Tabs div encontrado en DOM:', !!tabsDiv);
            if (tabsDiv) {
                console.log('   - Tabs div style:', tabsDiv.getAttribute('style'));
                console.log('   - Tabs div children count:', tabsDiv.children.length);
                console.log('   - Tabs div computed display:', window.getComputedStyle(tabsDiv).display);
                console.log('   - Tabs div computed flex-wrap:', window.getComputedStyle(tabsDiv).flexWrap);
                console.log('   - Children HTML:');
                Array.from(tabsDiv.children).forEach((child, idx) => {
                    console.log(`      Child ${idx}:`, child.tagName, child.className, child.textContent.substring(0, 50));
                });
            }
            const buttons = prendasContainer.querySelectorAll('.tab-button-editable');
            console.log('   - Buttons encontrados:', buttons.length);
            buttons.forEach((btn, idx) => {
                console.log(`      Button ${idx}: ${btn.textContent.trim()} - display: ${window.getComputedStyle(btn).display}`);
            });
        }, 100);

        // Log comparativo de estilos entre imagen principal y miniaturas
        setTimeout(() => {
            const principal = prendasContainer.querySelector('.prenda-foto-principal');
            const thumb = prendasContainer.querySelector('.prenda-foto-thumb');
            if (principal && thumb) {
                const props = ['width', 'height', 'borderRadius', 'border', 'boxShadow', 'objectFit'];
                const logStyles = (el) => {
                    const cs = getComputedStyle(el);
                    return props.reduce((acc, p) => { acc[p] = cs[p]; return acc; }, {});
                };
                console.log('🎨 Estilos imagen principal:', logStyles(principal));
                console.log('🎨 Estilos miniatura:', logStyles(thumb));
            } else {
                console.warn('⚠️ No se encontraron imágenes para comparar estilos');
            }
        }, 50);
        
        // ============================================================
        // CARGAR TÉCNICAS EN EL TAB LOGO (SI ES COTIZACIÓN COMBINADA)
        // ============================================================
        if (tieneLogoPrendas && logoCotizacion && logoCotizacion.tecnicas) {
            setTimeout(() => {
                const galeriaFotos = document.getElementById('galeria-fotos-logo');
                const tecnicasSeleccionadasDiv = document.getElementById('tecnicas_seleccionadas_logo');
                
                // Renderizar fotos iniciales
                if (galeriaFotos && logoCotizacion.fotos && logoCotizacion.fotos.length > 0) {
                    galeriaFotos.innerHTML = '';
                    logoCotizacion.fotos.forEach((foto, idx) => {
                        const fotoUrl = foto.url || foto.ruta_webp || foto.ruta_original;
                        if (fotoUrl) {
                            const div = document.createElement('div');
                            div.style.cssText = 'position: relative; display: inline-block; width: 100%;';
                            div.innerHTML = `
                                <img src="${fotoUrl}" 
                                     alt="Foto" 
                                     style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 1px solid #d0d0d0;" 
                                     onclick="abrirModalImagen('${fotoUrl}', 'Foto del logo')">
                                <button type="button" 
                                        style="position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 0.9rem; display: flex; align-items: center; justify-content: center;">×</button>
                            `;
                            galeriaFotos.appendChild(div);
                        }
                    });
                }
                
                // Renderizar técnicas seleccionadas
                if (tecnicasSeleccionadasDiv && logoCotizacion.tecnicas && logoCotizacion.tecnicas.length > 0) {
                    tecnicasSeleccionadasDiv.innerHTML = '';
                    logoCotizacion.tecnicas.forEach((tecnica, idx) => {
                        const tecnicaText = typeof tecnica === 'object' ? tecnica.nombre : tecnica;
                        const span = document.createElement('span');
                        span.style.cssText = 'display: inline-flex; align-items: center; gap: 0.5rem; background: #e3f2fd; color: #1976d2; padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.9rem; font-weight: 600;';
                        span.innerHTML = `
                            ${tecnicaText}
                            <button type="button" onclick="eliminarTecnicaDelTabLogo(${idx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-size: 1.2rem; padding: 0;">×</button>
                        `;
                        tecnicasSeleccionadasDiv.appendChild(span);
                    });
                }
            }, 50);
        }
        
        // ============================================================
        // EVENT LISTENERS PARA ACTUALIZAR TÍTULO DE PRENDA EN TIEMPO REAL
        // ============================================================
        
        // Agregar listeners a todos los inputs de "Nombre del Producto"
        const nombreProductoInputs = document.querySelectorAll('.prenda-nombre');
        nombreProductoInputs.forEach(input => {
            input.addEventListener('input', function() {
                const prendasIndex = this.dataset.prenda;
                const nuevoNombre = this.value.trim();
                
                // Encontrar el elemento .prenda-title de la tarjeta correspondiente
                const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendasIndex}"]`);
                if (prendasCard) {
                    const prendasTitle = prendasCard.querySelector('.prenda-title');
                    if (prendasTitle) {
                        // Actualizar el título con el nuevo nombre
                        prendasTitle.textContent = `🧥 Prenda ${parseInt(prendasIndex) + 1}: ${nuevoNombre}`;
                    }
                }
            });
        });

        // ✅ AGREGAR LISTENERS PARA CHECKBOXES DE GÉNERO (MÚLTIPLE SELECCIÓN)
        const generoCheckboxes = document.querySelectorAll('.genero-checkbox');
        generoCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const prendaIndex = parseInt(e.target.dataset.prenda);
                const prendasCard = e.target.closest('.prenda-card-editable');
                
                if (!prendasCard) return;
                
                // Obtener todos los géneros seleccionados
                const generosSeleccionados = [];
                prendasCard.querySelectorAll('.genero-checkbox:checked').forEach(cb => {
                    generosSeleccionados.push(cb.value);
                });
                
                logWithEmoji('✅', `Géneros actualizados para prenda ${prendaIndex}:`, generosSeleccionados);
                
                // Actualizar el contenedor de tallas dinámico
                actualizarContenedorTallasPorGeneroEditable(prendaIndex, prendasCard, generosSeleccionados);
            });
        });
        } catch (error) {
            console.error(`❌ ERROR CRÍTICO en renderizarPrendasEditables:`, error);
            console.error(`   Mensaje:`, error.message);
            console.error(`   Stack:`, error.stack);
        }
    }

    // ============================================================
    // RENDERIZAR CAMPOS SOLO PARA LOGO (sin prendas)
    // ============================================================
    
    // Arrays globales para almacenar datos editables del LOGO
    let logoTecnicasSeleccionadas = [];
    let logoSeccionesSeleccionadas = [];
    let logoFotosSeleccionadas = [];  // Array para guardar fotos editables
    let logoCotizacionId = null;  // ID del LogoCotizacion para guardar en BD

    // Usar constantes del archivo de configuración (línea 4)
    // 🔧 Función renderizarCamposLogo() movida a logo-pedido.js

    // ====== FUNCIONES DE FOTOS LOGO ======
    window.renderizarFotosLogo = function() {
        const container = document.getElementById('galeria-fotos-logo');
        if (!container) return;
        container.innerHTML = '';
        
        if (logoFotosSeleccionadas.length === 0) {
            container.innerHTML = '<p style="grid-column: 1/-1; color: #9ca3af; text-align: center; padding: 2rem;">Sin imágenes</p>';
            return;
        }
        
        logoFotosSeleccionadas.forEach((foto, idx) => {
            const div = document.createElement('div');
            div.style.cssText = 'position: relative; display: inline-block; width: 100%; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: all 0.3s; group: 1;';
            div.innerHTML = `
                <img src="${foto.preview}" 
                     alt="Imagen ${idx + 1}" 
                     style="width: 100%; height: 120px; object-fit: cover; cursor: pointer; transition: transform 0.2s; display: block;" 
                     onmouseover="this.style.transform='scale(1.05)'"
                     onmouseout="this.style.transform=''"
                     onclick="abrirModalImagen('${foto.preview}', 'Logo - Imagen ${idx + 1}')">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0); transition: background 0.2s;" class="overlay-foto" onmouseover="this.parentElement.querySelector('.btn-eliminar-foto').style.opacity='1'; this.style.background='rgba(0,0,0,0.3)'" onmouseout="this.parentElement.querySelector('.btn-eliminar-foto').style.opacity='0'; this.style.background='rgba(0,0,0,0)'"></div>
                <button type="button" onclick="eliminarFotoLogo(${idx})" 
                        style="position: absolute; top: 8px; right: 8px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; z-index: 10; padding: 0; line-height: 1;" 
                        class="btn-eliminar-foto">×</button>
            `;
            container.appendChild(div);
        });
    };

    window.abrirModalAgregarFotosLogo = function() {
        if (logoFotosSeleccionadas.length >= 5) {
            Swal.fire({
                icon: 'warning',
                title: 'Límite de imágenes',
                text: 'Ya has alcanzado el máximo de 5 imágenes permitidas',
                confirmButtonColor: '#0066cc'
            });
            return;
        }
        
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = true;
        
        input.addEventListener('change', (e) => {
            manejarArchivosFotosLogo(e.target.files);
        });
        
        input.click();
    };

    window.manejarArchivosFotosLogo = function(files) {
        const espacioDisponible = 5 - logoFotosSeleccionadas.length;
        
        if (files.length > espacioDisponible) {
            Swal.fire({
                icon: 'warning',
                title: 'Demasiadas imágenes',
                text: `Solo puedes agregar ${espacioDisponible} imagen${espacioDisponible !== 1 ? 's' : ''} más. Máximo 5 en total.`,
                confirmButtonColor: '#0066cc'
            });
            return;
        }
        
        let fotosAgregadas = 0;
        
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    logoFotosSeleccionadas.push({
                        file: file,
                        preview: e.target.result,
                        existing: false
                    });
                    fotosAgregadas++;
                    
                    if (fotosAgregadas === files.length) {
                        renderizarFotosLogo();
                        Swal.fire({
                            icon: 'success',
                            title: 'Imágenes agregadas',
                            text: `Se agregaron ${fotosAgregadas} imagen${fotosAgregadas !== 1 ? 's' : ''} correctamente`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    };

    window.eliminarFotoLogo = function(index) {
        const fotoAEliminar = logoFotosSeleccionadas[index];
        
        // Si es una foto existente (de la BD), eliminarla del servidor
        if (fotoAEliminar && fotoAEliminar.existing && fotoAEliminar.id) {
            console.log('🗑️ Eliminando foto existente de la BD:', fotoAEliminar.id);
            
            // Obtener ID de la cotización de la URL
            const urlParams = new URLSearchParams(window.location.search);
            const cotizacionId = urlParams.get('cotizacion') || document.querySelector('input[name="cotizacion_id"]')?.value;
            
            if (!cotizacionId) {
                console.warn('⚠️ No se encontró el ID de la cotización');
            } else {
                // Enviar petición al servidor para eliminar la foto
                fetch(`/asesores/logos/${cotizacionId}/eliminar-foto`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || 
                                       document.querySelector('input[name="_token"]')?.value
                    },
                    body: JSON.stringify({
                        foto_id: fotoAEliminar.id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✅ Foto eliminada del servidor:', fotoAEliminar.id);
                        // Quitar de array local
                        logoFotosSeleccionadas.splice(index, 1);
                        renderizarFotosLogo();
                    } else {
                        console.error('❌ Error al eliminar foto:', data.message);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo eliminar la imagen'
                        });
                    }
                })
                .catch(error => {
                    console.error('❌ Error en petición:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al eliminar la imagen'
                    });
                });
            }
        } else {
            // Si es una foto nueva (no guardada en BD), simplemente quitarla del array
            console.log('🗑️ Eliminando foto nueva del array');
            logoFotosSeleccionadas.splice(index, 1);
            renderizarFotosLogo();
        }
    };

    // ====== FUNCIONES PARA AGREGAR FOTOS DE PRENDAS Y TELAS ======
    window.abrirModalAgregarFotosPrenda = function(prendaIndex) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = true;
        
        input.addEventListener('change', (e) => {
            manejarArchivosFotosPrenda(e.target.files, prendaIndex);
        });
        
        input.click();
    };

    window.manejarArchivosFotosPrenda = function(files, prendaIndex) {
        // Detectar si estamos en modo PRENDA sin cotización
        const esModosPrendaSinCotizacion = window.gestorPrendaSinCotizacion && 
                                          document.querySelector('input[name="tipo_pedido_editable"]:checked')?.value === 'nuevo' &&
                                          document.getElementById('tipo_pedido_nuevo')?.value === 'P';
        
        // Inicializar almacenamiento según modo
        let fotosNuevasObj;
        if (esModosPrendaSinCotizacion) {
            // Usar almacenamiento del gestor para PRENDA sin cotización
            if (!window.gestorPrendaSinCotizacion.fotosNuevas) {
                window.gestorPrendaSinCotizacion.fotosNuevas = {};
            }
            if (!window.gestorPrendaSinCotizacion.fotosNuevas[prendaIndex]) {
                window.gestorPrendaSinCotizacion.fotosNuevas[prendaIndex] = [];
            }
            fotosNuevasObj = window.gestorPrendaSinCotizacion.fotosNuevas[prendaIndex];
        } else {
            // Usar almacenamiento global para modo cotización
            if (!window.prendasFotosNuevas) window.prendasFotosNuevas = {};
            if (!window.prendasFotosNuevas[prendaIndex]) {
                window.prendasFotosNuevas[prendaIndex] = [];
            }
            fotosNuevasObj = window.prendasFotosNuevas[prendaIndex];
        }

        let fotosAgregadas = 0;
        let fotosADeProcesar = Array.from(files).filter(f => f.type.startsWith('image/')).length;
        
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    // Crear objeto de foto
                    const fotoObj = {
                        url: e.target.result,
                        preview: e.target.result,
                        file: file,
                        isNew: true,
                        fileName: file.name
                    };
                    
                    // Verificar si esta foto ya existe (por nombre de archivo)
                    const yaExiste = fotosNuevasObj.some(f => f.fileName === file.name && f.url === e.target.result);
                    
                    if (!yaExiste) {
                        fotosNuevasObj.push(fotoObj);
                        fotosAgregadas++;
                        console.log(`📸 Foto agregada a prenda ${prendaIndex}:`, file.name);
                    } else {
                        console.log(`⚠️ Foto duplicada ignorada: ${file.name}`);
                    }
                    
                    // Cuando se terminen de procesar todas las fotos, renderizar una sola vez
                    if (fotosAgregadas === fotosADeProcesar || fotosAgregadas > 0) {
                        // Renderizar según el modo
                        if (esModosPrendaSinCotizacion) {
                            // Sincronizar primero antes de renderizar
                            window.sincronizarDatosTelas(prendaIndex);
                            window.renderizarPrendasTipoPrendaSinCotizacion();
                        } else {
                            renderizarPrendas();
                        }
                    }
                };
                reader.readAsDataURL(file);
            }
        });
        
        Swal.fire({
            icon: 'success',
            title: 'Fotos agregadas',
            text: `Se agregaron ${Array.from(files).filter(f => f.type.startsWith('image/')).length} imagen(es) correctamente`,
            timer: 2000,
            showConfirmButton: false
        });
    };

    window.abrirModalAgregarFotosTela = function(prendaIndex, telaIndex) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = true;
        
        input.addEventListener('change', (e) => {
            manejarArchivosFotosTela(e.target.files, prendaIndex, telaIndex);
        });
        
        input.click();
    };

    window.manejarArchivosFotosTela = function(files, prendaIndex, telaIndex) {
        // Detectar si estamos en modo PRENDA sin cotización
        const esModosPrendaSinCotizacion = window.gestorPrendaSinCotizacion && 
                                          document.querySelector('input[name="tipo_pedido_editable"]:checked')?.value === 'nuevo' &&
                                          document.getElementById('tipo_pedido_nuevo')?.value === 'P';
        
        // Inicializar almacenamiento según modo
        let telasFotosObj;
        if (esModosPrendaSinCotizacion) {
            // Usar almacenamiento del gestor para PRENDA sin cotización
            if (!window.gestorPrendaSinCotizacion.telasFotosNuevas) {
                window.gestorPrendaSinCotizacion.telasFotosNuevas = {};
            }
            if (!window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex]) {
                window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex] = {};
            }
            if (!window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex]) {
                window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex] = [];
            }
            telasFotosObj = window.gestorPrendaSinCotizacion.telasFotosNuevas[prendaIndex][telaIndex];
        } else {
            // Usar almacenamiento global para modo cotización
            if (!window.telasFotosNuevas) window.telasFotosNuevas = {};
            if (!window.telasFotosNuevas[prendaIndex]) {
                window.telasFotosNuevas[prendaIndex] = {};
            }
            if (!window.telasFotosNuevas[prendaIndex][telaIndex]) {
                window.telasFotosNuevas[prendaIndex][telaIndex] = [];
            }
            telasFotosObj = window.telasFotosNuevas[prendaIndex][telaIndex];
        }
        
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    // Crear objeto de foto
                    const fotoObj = {
                        url: e.target.result,
                        preview: e.target.result,
                        file: file,
                        isNew: true
                    };
                    
                    telasFotosObj.push(fotoObj);
                    console.log(`📸 Foto agregada a tela ${telaIndex} de prenda ${prendaIndex}:`, file.name);
                    
                    // Re-renderizar la sección de prendas
                    if (esModosPrendaSinCotizacion) {
                        // ✅ Sincronizar TODOS los datos del DOM primero (incluyendo observaciones)
                        const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
                        if (prenda) {
                            const container = document.querySelector(`[data-prenda-index="${prendaIndex}"]`);
                            if (container) {
                                // Guardar nombre y descripción
                                const inputNombre = container.querySelector('.prenda-nombre');
                                const inputDesc = container.querySelector('.prenda-descripcion');
                                if (inputNombre?.value) prenda.nombre_producto = inputNombre.value;
                                if (inputDesc?.value) prenda.descripcion = inputDesc.value;
                                
                                // Guardar TODAS las variaciones (incluyendo observaciones)
                                container.querySelectorAll('[data-field]').forEach(field => {
                                    const nombreCampo = field.dataset.field;
                                    if (nombreCampo) {
                                        const valor = field.value || field.textContent;
                                        if (prenda.variantes) {
                                            if (nombreCampo.includes('tiene_')) {
                                                prenda.variantes[nombreCampo] = valor === 'Sí';
                                            } else {
                                                prenda.variantes[nombreCampo] = valor;
                                            }
                                        }
                                    }
                                });

                                // ✅ GUARDAR OBSERVACIONES EXPLÍCITAMENTE
                                container.querySelectorAll('.variacion-obs').forEach(textarea => {
                                    const campoObs = textarea.dataset.field;
                                    if (campoObs && prenda.variantes) {
                                        prenda.variantes[campoObs] = textarea.value;
                                    }
                                });
                                
                                // Guardar telas
                                const telaRows = container.querySelectorAll('[data-tela-index]');
                                telaRows.forEach(row => {
                                    const telaIdx = parseInt(row.dataset.telaIndex);
                                    const nombreInput = row.querySelector('.tela-nombre');
                                    const colorInput = row.querySelector('.tela-color');
                                    const refInput = row.querySelector('.tela-referencia');
                                    
                                    if (prenda.variantes?.telas_multiples?.[telaIdx]) {
                                        prenda.variantes.telas_multiples[telaIdx].nombre_tela = nombreInput?.value || '';
                                        prenda.variantes.telas_multiples[telaIdx].color = colorInput?.value || '';
                                        prenda.variantes.telas_multiples[telaIdx].referencia = refInput?.value || '';
                                    }
                                    if (prenda.telas?.[telaIdx]) {
                                        prenda.telas[telaIdx].nombre_tela = nombreInput?.value || '';
                                        prenda.telas[telaIdx].color = colorInput?.value || '';
                                        prenda.telas[telaIdx].referencia = refInput?.value || '';
                                    }
                                });
                                
                                logWithEmoji('💾', `Datos sincronizados antes de re-renderizar (incluyendo observaciones)`);
                            }
                        }
                        
                        // ✅ Solo re-renderizar la sección de TELAS (no toda la prenda)
                        const prenda2 = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIndex);
                        if (prenda2) {
                            const container = document.querySelector(`[data-prenda-index="${prendaIndex}"]`);
                            if (container) {
                                const telasSection = container.querySelector('[data-section="telas"]');
                                if (telasSection) {
                                    const telasHtml = window.renderizarTelasPrendaTipo(prenda2, prendaIndex);
                                    telasSection.innerHTML = telasHtml;
                                    logWithEmoji('📸', `Sección de telas actualizada con la nueva foto`);
                                }
                            }
                        }
                    } else {
                        renderizarPrendas();
                    }
                };
                reader.readAsDataURL(file);
            }
        });
        
        Swal.fire({
            icon: 'success',
            title: 'Fotos de tela agregadas',
            text: `Se agregaron ${Array.from(files).filter(f => f.type.startsWith('image/')).length} imagen(es) correctamente`,
            timer: 2000,
            showConfirmButton: false
        });
    };

    // Agregar una nueva fila de tela a la prenda (estructura en memoria)
    window.agregarFilaTela = function(prendaIndex) {
        if (!prendasCargadas || !prendasCargadas[prendaIndex]) return;
        const prenda = prendasCargadas[prendaIndex];

        // Asegurar estructuras
        if (!prenda.variantes) prenda.variantes = {};
        if (!Array.isArray(prenda.variantes.telas_multiples)) prenda.variantes.telas_multiples = [];
        if (!Array.isArray(prenda.telas)) prenda.telas = [];

        // Agregar una tela vacía
        prenda.variantes.telas_multiples.push({
            nombre_tela: '',
            tela: '',
            color: '',
            referencia: ''
        });
        prenda.telas.push({
            id: null,
            nombre_tela: '',
            color: '',
            referencia: ''
        });

        // Mantener estructura de fotos nuevas por prenda
        if (!window.telasFotosNuevas) window.telasFotosNuevas = {};
        if (!window.telasFotosNuevas[prendaIndex]) window.telasFotosNuevas[prendaIndex] = {};

        renderizarPrendas();
    };

    window.eliminarFilaTela = function(prendaIndex, telaIndex) {
        if (!prendasCargadas || !prendasCargadas[prendaIndex]) return;
        const prenda = prendasCargadas[prendaIndex];

        if (Array.isArray(prenda.variantes?.telas_multiples) && prenda.variantes.telas_multiples[telaIndex]) {
            prenda.variantes.telas_multiples.splice(telaIndex, 1);
        }
        if (Array.isArray(prenda.telas) && prenda.telas[telaIndex]) {
            prenda.telas.splice(telaIndex, 1);
        }
        if (window.telasFotosNuevas && window.telasFotosNuevas[prendaIndex]) {
            delete window.telasFotosNuevas[prendaIndex][telaIndex];
        }

        renderizarPrendas();
    };

    // ====== RENDER UTIL PARA FOTOS NUEVAS ======
    window.renderizarPrendas = function() {
        console.log(`\n🔵 renderizarPrendas() EJECUTADA`);
        console.log(`   window.prendasCargadas existe? ${!!window.prendasCargadas}`);
        console.log(`   Llamando a window.renderizarPrendasEditables...`);
        try {
            window.renderizarPrendasEditables(
                window.prendasCargadas,
                currentLogoCotizacion,
                currentEspecificaciones,
                currentEsReflectivo,
                currentDatosReflectivo,
                currentEsLogo,
                currentTipoCotizacion
            );
            console.log(`   ✓ window.renderizarPrendasEditables completada sin errores`);
        } catch (error) {
            console.error(`   ❌ ERROR en window.renderizarPrendasEditables:`, error);
            console.error(`      Stack:`, error.stack);
        }
    };

    // ====== FUNCIONES DE TÉCNICAS LOGO ======
    window.agregarTecnicaLogo = function() {
        const selector = document.getElementById('selector_tecnicas_logo');
        const tecnica = selector.value;
        
        if (!tecnica) {
            alert('Selecciona una técnica');
            return;
        }
        
        if (logoTecnicasSeleccionadas.includes(tecnica)) {
            alert('Esta técnica ya está agregada');
            return;
        }
        
        logoTecnicasSeleccionadas.push(tecnica);
        selector.value = '';
        renderizarTecnicasLogo();
    };

    window.renderizarTecnicasLogo = function() {
        const container = document.getElementById('tecnicas_seleccionadas_logo');
        if (!container) return;
        container.innerHTML = '';
        
        logoTecnicasSeleccionadas.forEach((tecnica, index) => {
            const badge = document.createElement('span');
            badge.style.cssText = 'background: #0066cc; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;';
            badge.innerHTML = `
                ${tecnica}
                <span style="cursor: pointer; font-weight: bold; font-size: 1rem;" onclick="eliminarTecnicaLogo(${index})">×</span>
            `;
            container.appendChild(badge);
        });
    };

    window.eliminarTecnicaLogo = function(index) {
        logoTecnicasSeleccionadas.splice(index, 1);
        renderizarTecnicasLogo();
    };

    // ====== FUNCIONES DE UBICACIONES LOGO ======
    // Variable temporal para saber si estamos editando
    let logoUbicacionEditIndex = null;
    let logoUbicacionTempNombre = '';

    window.agregarSeccionLogo = function() {
        const selector = document.getElementById('seccion_prenda_logo');
        const ubicacion = selector.value;
        const errorDiv = document.getElementById('errorSeccionPrendaLogo');
        
        if (!ubicacion) {
            selector.style.border = '2px solid #ef4444';
            selector.style.background = '#fee2e2';
            selector.classList.add('shake');
            errorDiv.style.display = 'block';
            
            setTimeout(() => {
                selector.style.border = '';
                selector.style.background = '';
                selector.classList.remove('shake');
            }, 600);
            
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 3000);
            
            return;
        }
        
        selector.style.border = '';
        selector.style.background = '';
        errorDiv.style.display = 'none';
        
        // Crear modal con opciones
        const opciones = logoOpcionesPorUbicacion[ubicacion] || [];
        logoUbicacionTempNombre = ubicacion;
        logoUbicacionEditIndex = null;
        
        abrirModalUbicacionLogo(ubicacion, opciones, null);
    };

    window.editarSeccionLogo = function(index) {
        const seccion = logoSeccionesSeleccionadas[index];
        const opciones = logoOpcionesPorUbicacion[seccion.ubicacion] || [];
        logoUbicacionTempNombre = seccion.ubicacion;
        logoUbicacionEditIndex = index;
        
        abrirModalUbicacionLogo(seccion.ubicacion, opciones, seccion);
    };

    window.abrirModalUbicacionLogo = function(ubicacion, opciones, seccionActual) {
        let html = `
            <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;" id="modalUbicacionLogo">
                <div style="background: white; border-radius: 16px; padding: 2rem; max-width: 600px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
                    
                    <!-- Header del modal -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #f0f0f0;">
                        <h2 style="margin: 0; color: #1e40af; font-size: 1.3rem; font-weight: 700;">Editar Ubicación</h2>
                        <button type="button" onclick="cerrarModalUbicacionLogo()" style="background: none; border: none; color: #999; font-size: 1.8rem; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">×</button>
                    </div>
                    
                    <!-- Sección 1: Nombre de la sección -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #1e40af; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">1. Nombre de la Sección</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; font-size: 1.2rem;">👕</span>
                            <input type="text" id="nombreSeccionLogo" value="${ubicacion}" placeholder="Ej: CAMISA, JEAN, GORRA" style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 2.5rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem; transition: all 0.3s; box-sizing: border-box;">
                        </div>
                    </div>
                    
                    <!-- Sección 2: Ubicaciones -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; font-weight: 700; margin-bottom: 1rem; color: #1e40af; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">2. Ubicaciones Disponibles</label>
                        <div id="opcionesUbicacionLogo" style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #f9f9f9; border-radius: 8px; max-height: 250px; overflow-y: auto;"></div>
                    </div>
                    
                    <!-- Sección 3: Agregar personalizado -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #1e40af; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">3. Agregar Personalizado</label>
                        <div style="display: flex; gap: 0.75rem;">
                            <input type="text" id="nuevaOpcionLogo" placeholder="Ej: BOLSILLO, MANGA" style="flex: 1; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95rem; transition: all 0.3s; box-sizing: border-box;">
                            <button type="button" onclick="agregarOpcionPersonalizadaLogo()" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; white-space: nowrap;">+ Agregar</button>
                        </div>
                    </div>
                    
                    <!-- Sección 4: Observaciones -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #1e40af; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">4. Observaciones</label>
                        <textarea id="obsUbicacionLogo" placeholder="Añade cualquier observación o nota importante..." style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95rem; resize: vertical; min-height: 80px; box-sizing: border-box; font-family: inherit; transition: all 0.3s;">${seccionActual && seccionActual.observaciones ? seccionActual.observaciones : ''}</textarea>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" onclick="cerrarModalUbicacionLogo()" style="background: #f0f0f0; color: #333; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s;">Cancelar</button>
                        <button type="button" onclick="guardarUbicacionLogo()" style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s;">✓ Guardar</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', html);
        
        // Agregar opciones como checkboxes
        setTimeout(() => {
            const container = document.getElementById('opcionesUbicacionLogo');
            if (container) {
                // Agregar opciones predefinidas
                if (opciones.length > 0) {
                    opciones.forEach(opcion => {
                        const isChecked = seccionActual && seccionActual.opciones.includes(opcion);
                        const label = document.createElement('label');
                        label.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.75rem; border-radius: 8px; transition: all 0.2s; background: white; border: 1px solid #e0e0e0;';
                        label.innerHTML = `
                            <input type="checkbox" value="${opcion}" ${isChecked ? 'checked' : ''} style="width: 20px; height: 20px; cursor: pointer; accent-color: #0066cc;" class="opcion-ubicacion-logo">
                            <span style="flex: 1; font-weight: 500; color: #333;">${opcion}</span>
                            <button type="button" onclick="eliminarOpcionLogo('${opcion}')" style="background: none; border: none; color: #ef4444; font-size: 1.2rem; cursor: pointer; padding: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">×</button>
                        `;
                        label.addEventListener('mouseover', () => label.style.background = '#f0f7ff');
                        label.addEventListener('mouseout', () => label.style.background = 'white');
                        container.appendChild(label);
                    });
                }
            }
        }, 10);
        
        // Mejorar inputs con estilos al enfocar
        setTimeout(() => {
            const inputs = document.querySelectorAll('#modalUbicacionLogo input[type="text"], #modalUbicacionLogo textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.borderColor = '#0066cc';
                    this.style.boxShadow = '0 0 0 3px rgba(0, 102, 204, 0.1)';
                });
                input.addEventListener('blur', function() {
                    this.style.borderColor = '#e0e0e0';
                    this.style.boxShadow = 'none';
                });
            });
        }, 20);
    };

    window.agregarOpcionPersonalizadaLogo = function() {
        const input = document.getElementById('nuevaOpcionLogo');
        const opcion = input.value.trim().toUpperCase();
        
        if (!opcion) {
            alert('Escribe una ubicación');
            return;
        }
        
        const container = document.getElementById('opcionesUbicacionLogo');
        if (!container) return;
        
        // Verificar si ya existe
        const yaExiste = Array.from(container.querySelectorAll('input[type="checkbox"]')).some(
            cb => cb.value.toUpperCase() === opcion
        );
        
        if (yaExiste) {
            alert('Esta ubicación ya está agregada');
            return;
        }
        
        const label = document.createElement('label');
        label.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem; border-radius: 6px; transition: background 0.2s; background: #e8f5e9; border: 1px solid #27ae60;';
        label.innerHTML = `
            <input type="checkbox" value="${opcion}" checked style="width: 18px; height: 18px; cursor: pointer;" class="opcion-ubicacion-logo">
            <span style="flex: 1;">${opcion}</span>
            <button type="button" onclick="eliminarOpcionLogo('${opcion}')" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; padding: 0;">×</button>
        `;
        label.addEventListener('mouseover', () => label.style.background = '#c8e6c9');
        label.addEventListener('mouseout', () => label.style.background = '#e8f5e9');
        container.appendChild(label);
        
        input.value = '';
        input.focus();
    };

    window.eliminarOpcionLogo = function(opcion) {
        const container = document.getElementById('opcionesUbicacionLogo');
        if (!container) return;
        
        Array.from(container.querySelectorAll('input[type="checkbox"]')).forEach(cb => {
            if (cb.value === opcion) {
                cb.closest('label').remove();
            }
        });
    };

    window.cerrarModalUbicacionLogo = function() {
        const modal = document.getElementById('modalUbicacionLogo');
        if (modal) modal.remove();
    };

    window.guardarUbicacionLogo = function() {
        const nombreNuevo = document.getElementById('nombreSeccionLogo').value.trim().toUpperCase();
        const checkboxes = document.querySelectorAll('#opcionesUbicacionLogo input[type="checkbox"]:checked');
        const obs = document.getElementById('obsUbicacionLogo').value;
        
        if (!nombreNuevo) {
            alert('Ingresa un nombre para la sección');
            return;
        }
        
        if (checkboxes.length === 0) {
            alert('Selecciona al menos una ubicación');
            return;
        }
        
        const opciones = Array.from(checkboxes).map(cb => cb.value);
        
        if (logoUbicacionEditIndex !== null) {
            // Editar existente
            logoSeccionesSeleccionadas[logoUbicacionEditIndex] = {
                ubicacion: nombreNuevo,
                opciones: opciones,
                observaciones: obs
            };
        } else {
            // Agregar nuevo
            logoSeccionesSeleccionadas.push({
                ubicacion: nombreNuevo,
                opciones: opciones,
                observaciones: obs
            });
        }
        
        cerrarModalUbicacionLogo();
        document.getElementById('seccion_prenda_logo').value = '';
        renderizarSeccionesLogo();
    };

    window.renderizarSeccionesLogo = function() {
        const container = document.getElementById('secciones_agregadas_logo');
        if (!container) return;
        container.innerHTML = '';
        
        logoSeccionesSeleccionadas.forEach((seccion, index) => {
            const div = document.createElement('div');
            div.style.cssText = 'background: white; border: 2px solid #3498db; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem;';
            
            const opcionesText = Array.isArray(seccion.opciones) ? seccion.opciones.join(', ') : seccion;
            const ubicacionText = seccion.ubicacion || seccion;
            const obsText = seccion.observaciones || '';
            
            div.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #1e40af; font-size: 0.95rem;">${ubicacionText}</h4>
                        <p style="margin: 0 0 0.5rem 0; color: #666; font-size: 0.85rem;"><strong>Ubicación:</strong> ${opcionesText}</p>
                        ${obsText ? `<p style="margin: 0; color: #666; font-size: 0.85rem;"><strong>Observaciones:</strong> ${obsText}</p>` : ''}
                    </div>
                    <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
                        <button type="button" onclick="editarSeccionLogo(${index})" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; font-weight: bold;" title="Editar">✎</button>
                        <button type="button" onclick="eliminarSeccionLogo(${index})" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center;">×</button>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });
    };

    window.eliminarSeccionLogo = function(index) {
        logoSeccionesSeleccionadas.splice(index, 1);
        renderizarSeccionesLogo();
    };
    
    // ====== FUNCIONES DE ACTUALIZACIÓN DE TALLAS Y UBICACIONES EN TABLA ======
    window.eliminarTallaLogo = function(index) {
        // Obtener todas las tallas del formulario y eliminar por índice
        const tbody = document.getElementById('logo-tallas-tbody');
        if (tbody && tbody.rows[index]) {
            tbody.deleteRow(index);
        }
    };
    
    window.eliminarUbicacionItem = function(ubicacionIdx, itemIdx) {
        if (logoSeccionesSeleccionadas[ubicacionIdx]) {
            logoSeccionesSeleccionadas[ubicacionIdx].opciones.splice(itemIdx, 1);
            // Re-renderizar la tabla
            const tbody = document.getElementById('logo-ubicaciones-tbody');
            const fila = tbody.rows[ubicacionIdx];
            if (fila) {
                const ubicacionesText = logoSeccionesSeleccionadas[ubicacionIdx].opciones.join(', ');
                const celda = fila.cells[1];
                if (celda) {
                    celda.innerHTML = `<div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                        ${logoSeccionesSeleccionadas[ubicacionIdx].opciones.map((opcion, opIdx) => `
                            <span style="display: inline-flex; align-items: center; gap: 0.3rem; background: #e3f2fd; color: #1976d2; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.8rem;">
                                ${opcion}
                                <button type="button" onclick="eliminarUbicacionItem(${ubicacionIdx}, ${opIdx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">×</button>
                            </span>
                        `).join('')}
                    </div>`;
                }
            }
        }
    };
    
    window.agregarUbicacionNueva = function(ubicacionIdx) {
        const input = document.getElementById(`logo-ubicaciones-tbody`).rows[ubicacionIdx]?.cells[1]?.querySelector('input[data-field="ubicacion_nueva"]');
        if (input && input.value.trim()) {
            const nuevaUbicacion = input.value.trim().toUpperCase();
            if (!logoSeccionesSeleccionadas[ubicacionIdx].opciones.includes(nuevaUbicacion)) {
                logoSeccionesSeleccionadas[ubicacionIdx].opciones.push(nuevaUbicacion);
                input.value = '';
                // Re-renderizar
                const celda = input.closest('td');
                if (celda) {
                    celda.innerHTML = `<div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                        ${logoSeccionesSeleccionadas[ubicacionIdx].opciones.map((opcion, opIdx) => `
                            <span style="display: inline-flex; align-items: center; gap: 0.3rem; background: #e3f2fd; color: #1976d2; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.8rem;">
                                ${opcion}
                                <button type="button" onclick="eliminarUbicacionItem(${ubicacionIdx}, ${opIdx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">×</button>
                            </span>
                        `).join('')}
                    </div>`;
                }
            }
        }
    };

    // ====== FUNCIONES DE OBSERVACIONES LOGO ======
    window.agregarObservacionLogo = function() {
        logoObservacionesGenerales.push('');
        renderizarObservacionesLogo();
    };

    // ====== FUNCIONES PARA TAB LOGO EN COTIZACIONES COMBINADAS ======
    // eliminarTallaLogoTab() - FUNCIÓN ANTIGUA ELIMINADA
    // Las tallas ahora se eliminan desde el modal de cada sección
    

    // Función auxiliar para generar UUID
    window.generarUUID = function() {
        return 'sec_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    };

    window.agregarSeccionLogoTab = function() {
        const input = document.getElementById('seccion_prenda_logo_tab');
        const seccion = input.value.trim().toUpperCase();
        
        if (!seccion) {
            input.style.border = '2px solid #ef4444';
            input.style.background = '#fee2e2';
            input.classList.add('shake');
            
            setTimeout(() => {
                input.style.border = '1px solid #d0d0d0';
                input.style.background = '';
                input.classList.remove('shake');
            }, 600);
            
            Swal.fire({
                icon: 'warning',
                title: 'Campo vacío',
                text: 'Por favor escribe el nombre de la sección',
                timer: 2000
            });
            return;
        }
        
        // Verificar si ya existe
        if (!window.logoSeccionesSeleccionadasTab) {
            window.logoSeccionesSeleccionadasTab = [];
        }
        
        if (window.logoSeccionesSeleccionadasTab.some(s => s.ubicacion.toUpperCase() === seccion)) {
            input.style.border = '2px solid #ef4444';
            input.style.background = '#fee2e2';
            
            setTimeout(() => {
                input.style.border = '1px solid #d0d0d0';
                input.style.background = '';
            }, 600);
            
            Swal.fire({
                icon: 'info',
                title: 'Sección duplicada',
                text: 'Esta sección ya existe',
                timer: 2000
            });
            return;
        }
        
        // Limpiar el input
        input.value = '';
        input.style.border = '1px solid #d0d0d0';
        input.style.background = '';
        
        // Obtener opciones disponibles para esta sección
        const logoOpcionesDisponibles = logoOpcionesPorUbicacion[seccion] || [];
        
        // Crear ID único para la nueva sección
        const seccionId = window.generarUUID();
        
        // Guardar temporalmente
        window.logoSeccionTempTab = {
            id: seccionId,
            ubicacion: seccion,
            opciones: [],
            tallas: [],
            tallasCantidad: {},
            observaciones: ''
        };
        
        // Abrir el modal de edición directamente (sin intermediate modal)
        abrirModalSeccionEditarTab(seccion, logoOpcionesDisponibles, null);
    };

    window.abrirModalSeccionEditarTab = function(ubicacion, opcionesDisponibles, seccionData) {
        // seccionData será null si es crear, o un objeto con datos si es editar
        const isEditar = seccionData !== null;
        const tituloModal = isEditar ? 'Editar Sección' : 'Configurar Sección';
        const textoBtnGuardar = isEditar ? '✓ Actualizar' : '✓ Guardar';
        const fnGuardar = isEditar ? 'guardarSeccionTabEdicion()' : 'guardarSeccionTab()';
        
        let html = `
            <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;" id="modalSeccionTab">
                <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 550px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.3); max-height: 85vh; overflow-y: auto;">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <h2 style="margin: 0; color: #1e40af; font-size: 1.25rem; font-weight: 700;">${tituloModal}</h2>
                        <button type="button" onclick="cerrarModalSeccionTab()" style="background: none; border: none; color: #999; font-size: 1.6rem; cursor: pointer; padding: 0; width: 30px; height: 30px;">×</button>
                    </div>
                    
                    <!-- 1. Nombre de la Sección -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e40af; font-size: 0.9rem;">1. Nombre de la Sección</label>
                        <input type="text" id="nombreSeccionTab" value="${ubicacion}" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 0.9rem; box-sizing: border-box;" ${isEditar ? 'readonly' : ''}>
                    </div>
                    
                    <!-- 2. Ubicaciones -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1e40af; font-size: 0.9rem;">2. Ubicaciones</label>
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem;">
                            <input type="text" id="inputUbicacionTab" placeholder="Busca o escribe una ubicación..." list="opcionesUbicacionList" style="flex: 1; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 0.9rem; box-sizing: border-box;">
                            <button type="button" onclick="agregarUbicacionDesdeInputTab()" style="background: #27ae60; color: white; border: none; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem; white-space: nowrap;">✓ Agregar</button>
                        </div>
                        <datalist id="opcionesUbicacionList"></datalist>
                        <div id="opcionesSeccionTab" style="display: flex; flex-direction: column; gap: 0.4rem; padding: 1rem; background: #f9f9f9; border-radius: 6px; min-height: 50px; max-height: 200px; overflow-y: auto;"></div>
                    </div>
                    
                    <!-- 3. Tallas -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1e40af; font-size: 0.9rem;">3. Tallas</label>
                        <div id="tallasSeccionTab" style="display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 1rem; background: #f9f9f9; border-radius: 6px; min-height: 45px;"></div>
                        <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem; align-items: flex-start;">
                            <input type="text" id="nuevaTallaTab" placeholder="Ej: S, M, L, XL" style="flex: 1; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 0.85rem; box-sizing: border-box;">
                            <input type="number" id="nuevaTallaCantidadTab" placeholder="Cant." min="1" value="1" style="width: 70px; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 0.85rem; box-sizing: border-box;">
                            <button type="button" onclick="agregarTallaSeccionTab()" style="background: #3b82f6; color: white; border: none; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem; white-space: nowrap;">+ Agregar</button>
                        </div>
                    </div>
                    
                    <!-- 4. Observaciones -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e40af; font-size: 0.9rem;">4. Observaciones</label>
                        <textarea id="obsSeccionTab" placeholder="Notas importantes..." style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 0.85rem; min-height: 70px; box-sizing: border-box; font-family: inherit; resize: none;">${seccionData && seccionData.observaciones ? seccionData.observaciones : ''}</textarea>
                    </div>
                    
                    <!-- Botones -->
                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" onclick="cerrarModalSeccionTab()" style="background: #f0f0f0; color: #333; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem;">Cancelar</button>
                        <button type="button" onclick="${fnGuardar}" style="background: #0066cc; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem;">${textoBtnGuardar}</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', html);
        
        // Agregar opciones disponibles
        setTimeout(() => {
            // Llenar el datalist con opciones disponibles
            const datalist = document.getElementById('opcionesUbicacionList');
            if (datalist && opcionesDisponibles.length > 0) {
                opcionesDisponibles.forEach(opcion => {
                    const option = document.createElement('option');
                    option.value = opcion;
                    datalist.appendChild(option);
                });
            }
            
            // Cargar ubicaciones existentes si estamos editando
            const container = document.getElementById('opcionesSeccionTab');
            if (container && seccionData && seccionData.opciones && seccionData.opciones.length > 0) {
                seccionData.opciones.forEach(opcion => {
                    const label = document.createElement('label');
                    label.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 4px; background: #dbeafe; border: 1px solid #bfdbfe; transition: all 0.2s; font-size: 0.85rem;';
                    label.innerHTML = `
                        <input type="checkbox" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;" class="opcion-seccion-tab">
                        <span style="flex: 1; color: #1e40af; font-weight: 500;">${opcion}</span>
                        <button type="button" onclick="this.parentElement.remove(); window.logoSeccionTempTab.opciones = window.logoSeccionTempTab.opciones.filter(o => o !== '${opcion}');" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; font-size: 0.9rem; font-weight: bold;">×</button>
                    `;
                    label.addEventListener('mouseover', () => label.style.background = '#c8e6f5');
                    label.addEventListener('mouseout', () => label.style.background = '#dbeafe');
                    container.appendChild(label);
                });
            }
        }, 10);
        
        // Cargar tallas existentes si estamos editando
        setTimeout(() => {
            const container = document.getElementById('tallasSeccionTab');
            if (container && seccionData && seccionData.tallas && seccionData.tallas.length > 0) {
                seccionData.tallas.forEach(talla => {
                    const cantidad = seccionData.tallasCantidad && seccionData.tallasCantidad[talla] ? seccionData.tallasCantidad[talla] : 0;
                    const chip = document.createElement('span');
                    chip.style.cssText = 'display: inline-flex; align-items: center; gap: 0.4rem; background: #dbeafe; color: #1e40af; padding: 0.3rem 0.8rem; border-radius: 16px; font-size: 0.8rem; font-weight: 500;';
                    chip.innerHTML = `${talla} (${cantidad}) <button type="button" onclick="eliminarTallaSeccionTab('${talla}')" style="background: none; border: none; color: #1e40af; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">×</button>`;
                    container.appendChild(chip);
                });
            }
        }, 10);
    };
    
    window.agregarUbicacionDesdeInputTab = function() {
        const input = document.getElementById('inputUbicacionTab');
        const valor = input.value.trim().toUpperCase();
        
        if (!valor) {
            Swal.fire({
                icon: 'warning',
                title: 'Escribe una ubicación',
                timer: 1500
            });
            return;
        }
        
        // Verificar si ya existe
        if (window.logoSeccionTempTab.opciones.includes(valor)) {
            Swal.fire({
                icon: 'info',
                title: 'Ubicación duplicada',
                text: 'Esta ubicación ya fue agregada',
                timer: 1500
            });
            return;
        }
        
        // Agregar a opciones temporales
        window.logoSeccionTempTab.opciones.push(valor);
        
        // Mostrar en el contenedor
        const container = document.getElementById('opcionesSeccionTab');
        const label = document.createElement('label');
        label.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 4px; background: #dbeafe; border: 1px solid #bfdbfe; transition: all 0.2s; font-size: 0.85rem;';
        label.innerHTML = `
            <input type="checkbox" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;" class="opcion-seccion-tab">
            <span style="flex: 1; color: #1e40af; font-weight: 500;">${valor}</span>
            <button type="button" onclick="this.parentElement.remove(); window.logoSeccionTempTab.opciones = window.logoSeccionTempTab.opciones.filter(o => o !== '${valor}');" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; font-size: 0.9rem; font-weight: bold;">×</button>
        `;
        container.appendChild(label);
        
        // Resetear input
        input.value = '';
        input.focus();
    };


    window.agregarOpcionSeccionTab = function() {
        const input = document.getElementById('nuevaOpcionTab');
        const valor = input.value.trim().toUpperCase();
        if (!valor) return;
        
        if (!window.logoSeccionTempTab.opciones.includes(valor)) {
            window.logoSeccionTempTab.opciones.push(valor);
            const container = document.getElementById('opcionesSeccionTab');
            const label = document.createElement('label');
            label.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.5rem; border-radius: 4px; background: #dbeafe; border: 1px solid #bfdbfe; transition: all 0.2s; font-size: 0.85rem;';
            label.innerHTML = `
                <input type="checkbox" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: #1e40af;" class="opcion-seccion-tab">
                <span style="flex: 1; color: #1e40af; font-weight: 500;">${valor}</span>
                <button type="button" onclick="this.parentElement.remove(); window.logoSeccionTempTab.opciones = window.logoSeccionTempTab.opciones.filter(o => o !== '${valor}');" style="background: none; border: none; color: #1e40af; cursor: pointer; padding: 0; font-size: 0.9rem;">×</button>
            `;
            container.appendChild(label);
            input.value = '';
            input.focus();
        }
    };
    
    window.agregarTallaSeccionTab = function() {
        const inputTalla = document.getElementById('nuevaTallaTab');
        const inputCantidad = document.getElementById('nuevaTallaCantidadTab');
        const container = document.getElementById('tallasSeccionTab');
        
        if (!inputTalla || !inputCantidad || !container) {
            console.error('Elementos del modal no encontrados');
            return;
        }
        
        const talla = inputTalla.value.trim().toUpperCase();
        const cantidad = parseInt(inputCantidad.value) || 0;
        
        if (!talla || cantidad <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Datos inválidos',
                text: 'Ingresa una talla y una cantidad mayor a 0',
                timer: 2000
            });
            return;
        }
        
        if (!window.logoSeccionTempTab) {
            window.logoSeccionTempTab = {
                id: window.generarUUID(),
                opciones: [],
                tallas: [],
                tallasCantidad: {},
                observaciones: ''
            };
        }
        
        if (!window.logoSeccionTempTab.tallasCantidad) {
            window.logoSeccionTempTab.tallasCantidad = {};
        }
        
        if (!window.logoSeccionTempTab.tallas.includes(talla)) {
            window.logoSeccionTempTab.tallas.push(talla);
            window.logoSeccionTempTab.tallasCantidad[talla] = cantidad;
            
            const chip = document.createElement('span');
            chip.style.cssText = 'display: inline-flex; align-items: center; gap: 0.4rem; background: #dbeafe; color: #1e40af; padding: 0.3rem 0.8rem; border-radius: 16px; font-size: 0.8rem; font-weight: 500;';
            chip.id = `chip-${talla}`;
            chip.innerHTML = `${talla} (${cantidad}) <button type="button" onclick="eliminarTallaSeccionTab('${talla}')" style="background: none; border: none; color: #1e40af; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">×</button>`;
            container.appendChild(chip);
            inputTalla.value = '';
            inputCantidad.value = '1';
            inputTalla.focus();
            
            console.log('Talla agregada:', talla, 'Cantidad:', cantidad);
        } else {
            Swal.fire({
                icon: 'info',
                title: 'Talla duplicada',
                text: 'Esta talla ya fue agregada',
                timer: 2000
            });
        }
    };
    
    window.eliminarTallaSeccionTab = function(talla) {
        if (window.logoSeccionTempTab.tallas.includes(talla)) {
            window.logoSeccionTempTab.tallas = window.logoSeccionTempTab.tallas.filter(t => t !== talla);
            if (window.logoSeccionTempTab.tallasCantidad) {
                delete window.logoSeccionTempTab.tallasCantidad[talla];
            }
            // Re-renderizar chip
            const container = document.getElementById('tallasSeccionTab');
            if (container) {
                const chip = Array.from(container.children).find(c => c.textContent.includes(talla));
                if (chip) chip.remove();
            }
        }
    };
    
    window.eliminarTallaDelModalTab = function(talla) {
        if (window.logoSeccionTempTab.tallas.includes(talla)) {
            window.logoSeccionTempTab.tallas = window.logoSeccionTempTab.tallas.filter(t => t !== talla);
            if (window.logoSeccionTempTab.tallasCantidad) {
                delete window.logoSeccionTempTab.tallasCantidad[talla];
            }
        }
    };
    
    window.guardarSeccionTab = function() {
        const nombreInput = document.getElementById('nombreSeccionTab');
        const obsInput = document.getElementById('obsSeccionTab');
        
        const seccionName = nombreInput.value.trim().toUpperCase() || window.logoSeccionTempTab.ubicacion;
        window.logoSeccionTempTab.ubicacion = seccionName;
        window.logoSeccionTempTab.observaciones = obsInput.value.trim();
        
        // Las opciones ya están en window.logoSeccionTempTab.opciones (se actualizan dinámicamente)
        
        // Agregar al array global
        if (!window.logoSeccionesSeleccionadasTab) {
            window.logoSeccionesSeleccionadasTab = [];
        }
        window.logoSeccionesSeleccionadasTab.push(window.logoSeccionTempTab);
        
        // Renderizar la fila en la tabla
        const tbody = document.getElementById('logo-ubicaciones-tbody-tab');
        if (tbody) {
            const seccionId = window.logoSeccionTempTab.id;
            // Mostrar tallas con cantidades
            const tallasText = window.logoSeccionTempTab.tallas && window.logoSeccionTempTab.tallas.length > 0 
                ? window.logoSeccionTempTab.tallas.map(t => `${t} (${window.logoSeccionTempTab.tallasCantidad && window.logoSeccionTempTab.tallasCantidad[t] ? window.logoSeccionTempTab.tallasCantidad[t] : 0})`).join(', ')
                : '—';
            const tr = document.createElement('tr');
            tr.style.cssText = 'border-bottom: 1px solid #e5e7eb; transition: all 0.2s;';
            tr.onmouseover = function() { this.style.backgroundColor = '#f9fafb'; };
            tr.onmouseout = function() { this.style.backgroundColor = 'white'; };
            tr.setAttribute('data-seccion-id', seccionId);
            tr.innerHTML = `
                <td style="padding: 0.75rem; font-weight: 500; color: #1f2937;">
                    <input type="text" value="${window.logoSeccionTempTab.ubicacion}" class="logo-ubicacion-nombre-tab" data-seccion-id="${seccionId}" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.8rem; background: #f5f5f5; box-sizing: border-box;"
                           onfocus="this.style.borderColor = '#0066cc'; this.style.backgroundColor = 'white';"
                           onblur="this.style.borderColor = '#d0d0d0'; this.style.backgroundColor = '#f5f5f5';">
                </td>
                <td style="padding: 0.75rem; color: #666; font-size: 0.75rem;">
                    <span style="display: inline-block; max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${tallasText}">${tallasText}</span>
                </td>
                <td style="padding: 0.75rem;">
                    <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;" id="opciones-${seccionId}">
                        ${window.logoSeccionTempTab.opciones.map((opcion, opIdx) => `
                            <span style="display: inline-flex; align-items: center; gap: 0.2rem; background: #dbeafe; color: #1e40af; padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.7rem; font-weight: 500;">
                                ${opcion}
                                <button type="button" onclick="eliminarUbicacionItemTab('${seccionId}', ${opIdx})" style="background: none; border: none; color: #1e40af; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.8rem; line-height: 1; margin-left: 0.2rem;">×</button>
                            </span>
                        `).join('')}
                    </div>
                </td>
                <td style="padding: 0.75rem;">
                    <textarea class="logo-ubicacion-obs-tab" data-seccion-id="${seccionId}" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.75rem; min-height: 45px; resize: none; font-family: inherit; background: #fafafa; box-sizing: border-box;"
                              onfocus="this.style.borderColor = '#0066cc'; this.style.backgroundColor = 'white';"
                              onblur="this.style.borderColor = '#d0d0d0'; this.style.backgroundColor = '#fafafa';"
                              placeholder="...">${window.logoSeccionTempTab.observaciones}</textarea>
                </td>
                <td style="padding: 0.75rem; text-align: center; display: flex; gap: 0.4rem; justify-content: center;">
                    <button type="button" onclick="editarSeccionLogoTab('${seccionId}')" 
                            style="background: #0066cc; color: white; border: none; padding: 0.4rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600; transition: all 0.3s; white-space: nowrap;">
                        ✏ Editar
                    </button>
                    <button type="button" onclick="eliminarSeccionLogoTab('${seccionId}')" 
                            style="background: #ef4444; color: white; border: none; padding: 0.4rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600; transition: all 0.3s; white-space: nowrap;">
                        ✕ Eliminar
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        }
        
        cerrarModalSeccionTab();
    };
    
    window.cerrarModalSeccionTab = function() {
        const modal = document.getElementById('modalSeccionTab');
        if (modal) modal.remove();
        window.logoSeccionTempTab = null;
        window.logoSeccionEditIdTab = null;
    };

    window.editarSeccionLogoTab = function(seccionId) {
        const seccion = window.logoSeccionesSeleccionadasTab.find(s => s.id === seccionId);
        if (!seccion) return;
        
        // Marcar que estamos en modo edición
        window.logoSeccionEditIdTab = seccionId;
        
        // Crear objeto temporal con datos de la sección
        window.logoSeccionTempTab = {
            id: seccion.id,
            ubicacion: seccion.ubicacion || '',
            opciones: [...(seccion.opciones || [])],
            tallas: [...(seccion.tallas || [])],
            tallasCantidad: { ...(seccion.tallasCantidad || {}) },
            observaciones: seccion.observaciones || ''
        };
        
        // Obtener opciones disponibles
        const opcionesDisponibles = logoOpcionesPorUbicacion[seccion.ubicacion] || [];
        
        // Abrir modal con datos precargados - pasar los datos de la sección
        abrirModalSeccionEditarTab(seccion.ubicacion, opcionesDisponibles, seccion);
    };

    // Función duplicada eliminada - se usa abrirModalSeccionEditarTab en su lugar
    


    window.guardarSeccionTabEdicion = function() {
        const nombreInput = document.getElementById('nombreSeccionTab');
        const obsInput = document.getElementById('obsSeccionTab');
        
        const seccionName = nombreInput.value.trim().toUpperCase() || window.logoSeccionTempTab.ubicacion;
        window.logoSeccionTempTab.ubicacion = seccionName;
        window.logoSeccionTempTab.observaciones = obsInput.value.trim();
        
        // Las opciones ya están en window.logoSeccionTempTab.opciones (se actualizan dinámicamente)
        
        // Actualizar en el array global
        const index = window.logoSeccionesSeleccionadasTab.findIndex(s => s.id === window.logoSeccionEditIdTab);
        if (index !== -1) {
            window.logoSeccionesSeleccionadasTab[index] = window.logoSeccionTempTab;
        }
        
        // Re-renderizar la fila en la tabla
        const seccionId = window.logoSeccionTempTab.id;
        const tallasText = window.logoSeccionTempTab.tallas && window.logoSeccionTempTab.tallas.length > 0 
            ? window.logoSeccionTempTab.tallas.map(t => `${t} (${window.logoSeccionTempTab.tallasCantidad && window.logoSeccionTempTab.tallasCantidad[t] ? window.logoSeccionTempTab.tallasCantidad[t] : 0})`).join(', ')
            : '—';
        
        // Actualizar la fila en la tabla
        const filaExistente = document.querySelector(`tr[data-seccion-id="${seccionId}"]`);
        if (filaExistente) {
            // Actualizar nombre
            filaExistente.children[0].innerHTML = `
                <input type="text" value="${window.logoSeccionTempTab.ubicacion}" class="logo-ubicacion-nombre-tab" data-seccion-id="${seccionId}" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.8rem; background: #f5f5f5; box-sizing: border-box;"
                       onfocus="this.style.borderColor = '#0066cc'; this.style.backgroundColor = 'white';"
                       onblur="this.style.borderColor = '#d0d0d0'; this.style.backgroundColor = '#f5f5f5';">
            `;
            
            // Actualizar tallas
            filaExistente.children[1].innerHTML = tallasText;
            
            // Actualizar ubicaciones
            const opcionesDiv = filaExistente.children[2].querySelector(`div`) || filaExistente.children[2];
            if (opcionesDiv) {
                opcionesDiv.innerHTML = `
                    <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;" id="opciones-${seccionId}">
                        ${window.logoSeccionTempTab.opciones && window.logoSeccionTempTab.opciones.length > 0 ? window.logoSeccionTempTab.opciones.map((opcion, opIdx) => `
                            <span style="display: inline-flex; align-items: center; gap: 0.2rem; background: #dbeafe; color: #1e40af; padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.7rem; font-weight: 500;">
                                ${opcion}
                                <button type="button" onclick="eliminarUbicacionItemTab('${seccionId}', ${opIdx})" style="background: none; border: none; color: #1e40af; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.8rem; line-height: 1; margin-left: 0.2rem;">×</button>
                            </span>
                        `).join('') : '<span style="color: #999; font-size: 0.75rem;">Sin ubicaciones</span>'}
                    </div>
                `;
            }
            
            // Actualizar observaciones (encontrar textarea)
            const textareaExistente = filaExistente.querySelector('textarea.logo-ubicacion-obs-tab');
            if (textareaExistente) {
                textareaExistente.value = window.logoSeccionTempTab.observaciones;
            }
        }
        
        cerrarModalSeccionTab();
    };

    window.editarSeccionLogoTab = function(seccionId) {
        if (!window.logoSeccionesSeleccionadasTab) return;
        
        // Buscar la sección a editar
        const seccion = window.logoSeccionesSeleccionadasTab.find(s => s.id === seccionId);
        if (!seccion) return;
        
        // Guardar el ID para saber que estamos editando
        window.logoSeccionEditIdTab = seccionId;
        
        // Cargar los datos en la variable temporal
        window.logoSeccionTempTab = { ...seccion };
        
        // Obtener opciones disponibles
        const logoOpcionesDisponibles = logoOpcionesPorUbicacion[seccion.ubicacion] || [];
        
        // Abrir el modal en modo edición
        abrirModalSeccionEditarTab(seccion.ubicacion, logoOpcionesDisponibles, seccion);
    };

    window.eliminarSeccionLogoTab = function(seccionId) {
        if (!window.logoSeccionesSeleccionadasTab) return;
        
        // Eliminar del array por ID
        window.logoSeccionesSeleccionadasTab = window.logoSeccionesSeleccionadasTab.filter(s => s.id !== seccionId);
        
        // Eliminar de la tabla por ID
        const tbody = document.getElementById('logo-ubicaciones-tbody-tab');
        if (tbody) {
            const filaAEliminar = document.querySelector(`tr[data-seccion-id="${seccionId}"]`);
            if (filaAEliminar) {
                filaAEliminar.remove();
            }
        }
    };

    window.eliminarUbicacionItemTab = function(seccionId, itemIdx) {
        if (!window.logoSeccionesSeleccionadasTab) return;
        
        // Buscar la sección por ID
        const seccion = window.logoSeccionesSeleccionadasTab.find(s => s.id === seccionId);
        if (!seccion) return;
        
        seccion.opciones.splice(itemIdx, 1);
        
        // Re-renderizar las ubicaciones en el contenedor específico
        const opcionesDiv = document.getElementById(`opciones-${seccionId}`);
        if (opcionesDiv) {
            opcionesDiv.innerHTML = `
                ${seccion.opciones.map((opcion, opIdx) => `
                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; box-shadow: 0 2px 4px rgba(30,64,175,0.1);">
                        ${opcion}
                        <button type="button" onclick="eliminarUbicacionItemTab('${seccionId}', ${opIdx})" style="background: none; border: none; color: #1e40af; cursor: pointer; font-weight: bold; padding: 0; font-size: 1rem; transition: all 0.2s; line-height: 1;" onmouseover="this.style.transform = 'scale(1.3)';" onmouseout="this.style.transform = 'scale(1)';">×</button>
                    </span>
                `).join('')}
            `;
        }
    };

    window.agregarTecnicaTabLogo = function() {
        const select = document.getElementById('selector_tecnicas_logo');
        const tecnicaValue = select.value.trim();
        
        if (!tecnicaValue) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona una técnica',
                text: 'Debes seleccionar una técnica antes de agregarla',
                timer: 2000
            });
            return;
        }
        
        // Obtener el array global de técnicas (crear si no existe)
        if (!window.logoTecnicasSeleccionadasTab) {
            window.logoTecnicasSeleccionadasTab = [];
        }
        
        // Verificar que no esté duplicada
        if (window.logoTecnicasSeleccionadasTab.includes(tecnicaValue)) {
            Swal.fire({
                icon: 'info',
                title: 'Técnica duplicada',
                text: 'Esta técnica ya ha sido agregada',
                timer: 2000
            });
            return;
        }
        
        window.logoTecnicasSeleccionadasTab.push(tecnicaValue);
        
        const tecnicasDiv = document.getElementById('tecnicas_seleccionadas_logo');
        if (tecnicasDiv) {
            const span = document.createElement('span');
            span.style.cssText = 'display: inline-flex; align-items: center; gap: 0.5rem; background: #e3f2fd; color: #1976d2; padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.9rem; font-weight: 600;';
            const idx = window.logoTecnicasSeleccionadasTab.length - 1;
            span.innerHTML = `
                ${tecnicaValue}
                <button type="button" onclick="eliminarTecnicaDelTabLogo(${idx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-size: 1.2rem; padding: 0;">×</button>
            `;
            tecnicasDiv.appendChild(span);
        }
        
        select.value = '';
    };

    // agregarTallaAlTab() - FUNCIÓN ANTIGUA ELIMINADA
    // Las tallas ahora se agregan en el modal de cada sección
    

    window.agregarUbicacionNuevaTab = function(seccionId) {
        const input = document.querySelector(`.ubicacion-nueva-input-tab[data-seccion-id="${seccionId}"]`);
        if (!input || !input.value.trim()) {
            Swal.fire({
                icon: 'warning',
                title: 'Ingresa una ubicación',
                text: 'Por favor escribe el nombre de la ubicación',
                timer: 2000
            });
            return;
        }
        
        const nuevaUbicacion = input.value.trim().toUpperCase();
        
        if (!window.logoSeccionesSeleccionadasTab) {
            window.logoSeccionesSeleccionadasTab = [];
        }
        
        // Buscar la sección por ID
        const seccion = window.logoSeccionesSeleccionadasTab.find(s => s.id === seccionId);
        if (!seccion) return;
        
        if (seccion.opciones.includes(nuevaUbicacion)) {
            Swal.fire({
                icon: 'info',
                title: 'Ubicación duplicada',
                text: 'Esta ubicación ya existe en esta sección',
                timer: 2000
            });
            return;
        }
        
        seccion.opciones.push(nuevaUbicacion);
        input.value = '';
        input.focus();
        
        // Re-renderizar las ubicaciones en el contenedor específico
        const opcionesDiv = document.getElementById(`opciones-${seccionId}`);
        if (opcionesDiv) {
            opcionesDiv.innerHTML = `
                ${seccion.opciones.map((opcion, opIdx) => `
                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; box-shadow: 0 2px 4px rgba(30,64,175,0.1);">
                        ${opcion}
                        <button type="button" onclick="eliminarUbicacionItemTab('${seccionId}', ${opIdx})" style="background: none; border: none; color: #1e40af; cursor: pointer; font-weight: bold; padding: 0; font-size: 1rem; transition: all 0.2s; line-height: 1;" onmouseover="this.style.transform = 'scale(1.3)';" onmouseout="this.style.transform = 'scale(1)';">×</button>
                    </span>
                `).join('')}
            `;
        }
    };

    window.eliminarTecnicaDelTabLogo = function(index) {
        if (!window.logoTecnicasSeleccionadasTab) return;
        window.logoTecnicasSeleccionadasTab.splice(index, 1);
        
        const tecnicasDiv = document.getElementById('tecnicas_seleccionadas_logo');
        if (tecnicasDiv) {
            tecnicasDiv.innerHTML = '';
            if (window.logoTecnicasSeleccionadasTab.length > 0) {
                window.logoTecnicasSeleccionadasTab.forEach((tecnica, idx) => {
                    const span = document.createElement('span');
                    span.style.cssText = 'display: inline-flex; align-items: center; gap: 0.5rem; background: #e3f2fd; color: #1976d2; padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.9rem; font-weight: 600;';
                    span.innerHTML = `
                        ${tecnica}
                        <button type="button" onclick="eliminarTecnicaDelTabLogo(${idx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-size: 1.2rem; padding: 0;">×</button>
                    `;
                    tecnicasDiv.appendChild(span);
                });
            }
        }
    };

    window.renderizarObservacionesLogo = function() {
        const container = document.getElementById('observaciones_lista_logo');
        if (!container) return;
        container.innerHTML = '';
        
        logoObservacionesGenerales.forEach((obs, index) => {
            const fila = document.createElement('div');
            fila.style.cssText = 'display: flex; gap: 10px; align-items: stretch; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
            fila.innerHTML = `
                <textarea class="logo-obs-input" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; resize: vertical; min-height: 60px; font-family: inherit;">${obs}</textarea>
                <button type="button" onclick="eliminarObservacionLogo(${index})" style="background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 1rem; flex-shrink: 0; height: fit-content;">✕</button>
            `;
            
            // Actualizar array cuando se escribe
            const textarea = fila.querySelector('.logo-obs-input');
            textarea.addEventListener('input', (e) => {
                logoObservacionesGenerales[index] = e.target.value;
            });
            
            container.appendChild(fila);
        });
    };

    window.eliminarObservacionLogo = function(index) {
        logoObservacionesGenerales.splice(index, 1);
        renderizarObservacionesLogo();
    };


    // ============================================================
    // FUNCIONES DE MANIPULACIÓN DE PRENDAS
    // ============================================================
    
    window.eliminarPrendaDelPedido = function(index) {
        confirmarEliminacion(
            'Eliminar prenda',
            'Esta acción no se puede deshacer. ¿Deseas continuar?',
            () => {
                prendasEliminadas.add(index);
                logWithEmoji('🗑️', `Prenda ${index + 1} eliminada`);
                renderizarPrendasEditables(window.prendasCargadas);
                mostrarExito('Prenda eliminada', '✓ Prenda eliminada correctamente');
            }
        );
    };

    window.eliminarVariacionDePrenda = function(prendaIndex, variacionIndex) {
        confirmarEliminacion(
            'Eliminar variación',
            '¿Deseas eliminar esta variación?',
            () => {
                const filaVariacion = document.querySelector(`tr[data-variacion="${variacionIndex}"][data-prenda="${prendaIndex}"]`);
                if (filaVariacion) {
                    filaVariacion.remove();
                    logWithEmoji('🗑️', `Variación eliminada`);
                    mostrarExito('Variación eliminada', '✓ Variación eliminada correctamente');
                }
            }
        );
    };

    window.eliminarTecnicaDeBordado = function(tecnicaIndex) {
        Swal.fire({
            title: 'Eliminar técnica',
            text: '¿Estás seguro de que quieres eliminar esta técnica?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
            const filaTecnica = document.querySelector(`tr[data-tecnica="${tecnicaIndex}"]`);
            if (filaTecnica) {
                filaTecnica.remove();
                console.log(`Técnica ${tecnicaIndex} eliminada`);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Técnica eliminada',
                    text: 'La técnica ha sido eliminada',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
        });
    };

    window.eliminarUbicacionDeBordado = function(ubicacionIndex) {
        Swal.fire({
            title: 'Eliminar ubicación',
            text: '¿Estás seguro de que quieres eliminar esta ubicación?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
            const filaUbicacion = document.querySelector(`tr[data-ubicacion="${ubicacionIndex}"]`);
            if (filaUbicacion) {
                filaUbicacion.remove();
                console.log(`Ubicación ${ubicacionIndex} eliminada`);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Ubicación eliminada',
                    text: 'La ubicación ha sido eliminada',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
        });
    };

    window.eliminarUbicacionItem = function(ubicacionIndex, itemIndex) {
        Swal.fire({
            title: 'Eliminar ubicación',
            text: '¿Estás seguro de que quieres eliminar esta ubicación seleccionada?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const filaUbicacion = document.querySelector(`tr[data-ubicacion="${ubicacionIndex}"]`);
                if (filaUbicacion) {
                    const spans = filaUbicacion.querySelectorAll('span');
                    if (spans[itemIndex]) {
                        spans[itemIndex].remove();
                        console.log(`Ubicación seleccionada ${itemIndex} removida de ubicación ${ubicacionIndex}`);
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Ubicación removida',
                            text: 'La ubicación seleccionada ha sido removida',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                }
            }
        });
    };

    window.agregarUbicacionNueva = function(ubicacionIndex) {
        const input = document.querySelector(`input[data-field="ubicacion_nueva"][data-idx="${ubicacionIndex}"]`);
        if (input && input.value.trim()) {
            const filaUbicacion = document.querySelector(`tr[data-ubicacion="${ubicacionIndex}"]`);
            if (filaUbicacion) {
                const divUbicaciones = filaUbicacion.querySelector('div[style*="display: flex"]');
                if (divUbicaciones) {
                    const newBadge = document.createElement('span');
                    newBadge.style.cssText = 'display: inline-flex; align-items: center; gap: 0.3rem; background: #e3f2fd; color: #1976d2; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.8rem;';
                    newBadge.innerHTML = `
                        ${input.value.trim()}
                        <button type="button" 
                                onclick="eliminarUbicacionItem(${ubicacionIndex}, this.closest('span').previousElementSibling ? Array.from(this.closest('span').parentElement.querySelectorAll('span')).indexOf(this.closest('span')) : 0)"
                                style="background: none; border: none; color: #1976d2; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">×</button>
                    `;
                    divUbicaciones.appendChild(newBadge);
                    input.value = '';
                    console.log(`Ubicación "${input.value}" agregada`);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Ubicación agregada',
                        text: 'La nueva ubicación ha sido agregada',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            }
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Campo vacío',
                text: 'Por favor ingresa una ubicación antes de agregar',
                timer: 1500,
                showConfirmButton: false
            });
        }
    };

    window.quitarTallaDelFormulario = function(prendaIndex, talla) {
        confirmarEliminacion(
            'Eliminar talla',
            MENSAJES.TALLA_ELIMINAR_CONFIRMAR(talla, prendaIndex + 1),
            () => {
                const input = document.querySelector(`input[name="cantidades[${prendaIndex}][${talla}]"]`);
                if (input) {
                    const tallaRow = input.closest('div[style*="display: grid"]');
                    if (tallaRow) {
                        tallaRow.remove();
                        console.log(`✅ Talla ${talla} removida de prenda ${prendaIndex}`);
                        mostrarExito('Talla eliminada', MENSAJES.TALLA_ELIMINADA);
                    } else {
                        console.error('No se encontró el contenedor de talla');
                    }
                } else {
                    console.error('No se encontró el input de talla');
                }
            }
        );
    };

    // ============================================================
    // ENVÍO DEL FORMULARIO
    // ============================================================
    
    formCrearPedido.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Detectar si es un pedido sin cotización
        const tipoPedido = document.querySelector('input[name="tipo_pedido_editable"]:checked')?.value;
        const cotizacionId = document.getElementById('cotizacion_id_editable').value;
        
        console.log('🔍 [SUBMIT] Detectando tipo de pedido:', {
            tipoPedido: tipoPedido,
            cotizacionId: cotizacionId
        });
        
        if (tipoPedido === 'nuevo') {
            // Usar procesador de pedido sin cotización (NUEVO)
            console.log('✅ [SUBMIT] Detectado: NUEVO PEDIDO - usando procesarSubmitSinCotizacion()');
            window.procesarSubmitSinCotizacion();
        } else if (tipoPedido === 'cotizacion' && cotizacionId) {
            // Usar gestor de validación/envío (DESDE COTIZACIÓN)
            console.log('✅ [SUBMIT] Detectado: DESDE COTIZACIÓN - usando handleSubmitPrendaConCotizacion()');
            handleSubmitPrendaConCotizacion();
        } else {
            console.error('❌ [SUBMIT] Error: Tipo de pedido inválido o falta cotización', {
                tipoPedido,
                cotizacionId
            });
            mostrarAdvertencia('Selecciona una cotización', 'Por favor selecciona una cotización antes de continuar');
        }
    });

    // ============================================================
    // MANEJADOR: Crear Pedido SIN Cotización
    // ============================================================

    // NOTA: handleSubmitPedidoSinCotizacion se encuentra en init-gestor-sin-cotizacion.js
    // como procesarSubmitSinCotizacion()

    // Manejador del submit original para prendas con cotización
    function handleSubmitPrendaConCotizacion() {
        const cotizacionId = document.getElementById('cotizacion_id_editable').value;
        
        if (!cotizacionId) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona una cotización',
                text: 'Por favor selecciona una cotización antes de continuar',
                confirmButtonText: 'OK'
            });
            return;
        }

        // ✅ DETECTAR TIPO DE COTIZACIÓN Y SI TIENE LOGO
        const tipoCotizacionElement = document.querySelector('[data-tipo-cotizacion]');
        const tipoCotizacion = tipoCotizacionElement?.dataset.tipoCotizacion || 'P';
        
        const esLogo = logoTecnicasSeleccionadas.length > 0 || 
                       logoSeccionesSeleccionadas.length > 0 || 
                       logoFotosSeleccionadas.length > 0;
        
        const esCombinada = tipoCotizacion === 'PL';
        const esLogoSolo = tipoCotizacion === 'L';

        console.log('🎯 Análisis de cotización:', {
            tipoCotizacion: tipoCotizacion,
            esCombinada: esCombinada,
            esLogoSolo: esLogoSolo,
            esLogo: esLogo,
            logoTecnicas: logoTecnicasSeleccionadas.length,
            logoSecciones: logoSeccionesSeleccionadas.length,
            logoFotos: logoFotosSeleccionadas.length
        });

        if (esLogoSolo || esCombinada) {
            // ============================================================
            // FLUJO PARA LOGO SOLO (Tipo L) o COMBINADA (Tipo PL)
            // ============================================================
            if (esLogoSolo) {
                console.log('🎨 [LOGO SOLO] Preparando datos de LOGO para enviar');
            } else {
                console.log('🎨 [COMBINADA PL] Preparando pedidos de PRENDAS y LOGO para enviar');
            }

            // Para COMBINADA (PL), preparar prendas; para LOGO SOLO, enviar vacío
            let prendasParaEnviar = [];
            if (esCombinada) {
                // Recopilar prendas igual que en el flujo normal
                prendasCargadas.forEach((prenda, index) => {
                    if (prendasEliminadas.has(index)) {
                        console.log(`Saltando prenda eliminada: ${index}`);
                        return;
                    }

                    const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${index}"]`);
                    if (!prendasCard) return;
                    
                    // 🔍 LÓGICA: Usar .talla-cantidad (clase correcta, no .talla-input)
                    const tallasInputs = prendasCard.querySelectorAll('.talla-cantidad');
                    const cantidadesPorTalla = {};
                    
                    tallasInputs.forEach(input => {
                        const talla = input.getAttribute('data-talla');
                        const cantidad = parseInt(input.value) || 0;
                        if (talla && cantidad > 0) {
                            cantidadesPorTalla[talla] = cantidad;
                        }
                    });
                    
                    prendasParaEnviar.push({
                        index: index,
                        nombre_producto: prenda.nombre_producto,
                        cantidades: cantidadesPorTalla
                    });
                });
                console.log('📦 [COMBINADA] Prendas a enviar:', prendasParaEnviar);
            }

            // Crear el pedido primero
            const bodyCrearPedido = {
                cotizacion_id: cotizacionId,
                forma_de_pago: formaPagoInput.value,
                prendas: prendasParaEnviar  // Vacío para LOGO SOLO, lleno para COMBINADA
            };

            console.log('📤 [LOGO] Enviando creación de pedido...', bodyCrearPedido);

            fetch(`/asesores/pedidos-produccion/crear-desde-cotizacion/${cotizacionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify(bodyCrearPedido)
            })
            .then(response => response.json())
            .then(dataCrearPedido => {
                console.log('✅ [LOGO] Pedido creado:', dataCrearPedido);

                if (!dataCrearPedido.success) {
                    throw new Error(dataCrearPedido.message || 'Error al crear pedido');
                }

                // ✅ DIFERENCIACIÓN: Depende del tipo de cotización
                // - LOGO SOLO (L): dataCrearPedido.logo_pedido_id
                // - COMBINADA (PL): dataCrearPedido.pedido_id (del pedidos_produccion)
                const esCombinada = (dataCrearPedido.es_combinada === true || dataCrearPedido.es_combinada === 'true' || dataCrearPedido.tipo_cotizacion === 'PL');
                const pedidoId = esCombinada ? dataCrearPedido.pedido_id : (dataCrearPedido.logo_pedido_id || dataCrearPedido.pedido_id);
                
                console.log('🎯 [PRIMER REQUEST COMPLETADO] Respuesta completa del servidor:', dataCrearPedido);
                console.log('🎯 [LOGO] DETECTANDO TIPO:', {
                    esCombinada: esCombinada,
                    'dataCrearPedido.es_combinada': dataCrearPedido.es_combinada,
                    'typeof es_combinada': typeof dataCrearPedido.es_combinada,
                    'dataCrearPedido.tipo_cotizacion': dataCrearPedido.tipo_cotizacion,
                    pedidoId: pedidoId,
                    'dataCrearPedido.pedido_id': dataCrearPedido.pedido_id,
                    'dataCrearPedido.logo_pedido_id': dataCrearPedido.logo_pedido_id
                });
                
                // ✅ CORREGIDO: Usar logo_cotizacion_id devuelto por el servidor (más confiable)
                // Si no viene en la respuesta, usar la variable global como fallback
                const logoCotizacionIdAUsar = dataCrearPedido.logo_cotizacion_id || logoCotizacionId;

                // ✅ ESTRATEGIA DE RECOLECCIÓN DE DATOS:
                // 1. Si existe currentLogoCotizacion (creando desde cotización) → usar esos datos
                // 2. Si no existe (creando desde formulario paso-tres) → leer del DOM
                console.log('🔍 [RECOLECCIÓN] Verificando fuente de datos...');
                console.log('   - currentLogoCotizacion existe:', !!currentLogoCotizacion);
                console.log('   - currentLogoCotizacion data:', currentLogoCotizacion);
                
                let tecnicasActualizadas = [];
                let seccionesActualizadas = [];
                let observacionesTecnicasVar = '';
                
                if (currentLogoCotizacion && Object.keys(currentLogoCotizacion).length > 0) {
                    // ✅ CASO 1: Creando desde cotización existente
                    console.log('📦 [COTIZACIÓN] Usando datos de currentLogoCotizacion');
                    
                    // Técnicas
                    if (currentLogoCotizacion.tecnicas) {
                        tecnicasActualizadas = Array.isArray(currentLogoCotizacion.tecnicas) 
                            ? currentLogoCotizacion.tecnicas 
                            : [currentLogoCotizacion.tecnicas];
                        console.log('✅ [COTIZACIÓN] Técnicas:', tecnicasActualizadas);
                    }
                    
                    // Observaciones técnicas
                    if (currentLogoCotizacion.observaciones_tecnicas) {
                        observacionesTecnicasVar = currentLogoCotizacion.observaciones_tecnicas;
                        console.log('✅ [COTIZACIÓN] Observaciones técnicas:', observacionesTecnicasVar);
                    }
                    
                    // Ubicaciones/Secciones
                    if (currentLogoCotizacion.ubicaciones && Array.isArray(currentLogoCotizacion.ubicaciones)) {
                        seccionesActualizadas = currentLogoCotizacion.ubicaciones.map(ub => {
                            const tallas = ub.tallas || [];
                            const cantidadTotal = tallas.reduce((sum, t) => sum + (parseInt(t.cantidad) || 0), 0);
                            
                            return {
                                seccion: ub.seccion || ub.ubicacion || '',
                                tallas: tallas,
                                ubicaciones: ub.ubicaciones || [],
                                observaciones: ub.observaciones || '',
                                cantidad: cantidadTotal
                            };
                        });
                        console.log('✅ [COTIZACIÓN] Secciones/Ubicaciones:', seccionesActualizadas);
                    }
                    
                } else {
                    // ✅ CASO 2: Creando desde formulario paso-tres
                    console.log('📝 [FORMULARIO] Leyendo datos del DOM (paso-tres)');
                    
                    // 🎨 Técnicas desde campo hidden
                    const tecnicasHiddenField = document.getElementById('paso3_tecnicas_datos');
                    if (tecnicasHiddenField && tecnicasHiddenField.value) {
                        try {
                            tecnicasActualizadas = JSON.parse(tecnicasHiddenField.value);
                            console.log('✅ [FORMULARIO] Técnicas desde hidden:', tecnicasActualizadas);
                        } catch (e) {
                            console.warn('⚠️ Error parseando técnicas desde hidden:', e);
                        }
                    }
                    
                    // Fallback: badges visuales
                    if (tecnicasActualizadas.length === 0) {
                        const tecnicasBadges = document.querySelectorAll('#tecnicas_seleccionadas span');
                        tecnicasBadges.forEach(badge => {
                            const tecnicaText = badge.textContent.replace('×', '').trim();
                            if (tecnicaText) tecnicasActualizadas.push(tecnicaText);
                        });
                        console.log('🎨 [FORMULARIO] Técnicas desde badges:', tecnicasActualizadas);
                    }
                    
                    // Observaciones técnicas
                    const obsInput = document.getElementById('observaciones_tecnicas');
                    if (obsInput) {
                        observacionesTecnicasVar = obsInput.value || '';
                        console.log('✅ [FORMULARIO] Observaciones técnicas:', observacionesTecnicasVar);
                    }
                    
                    // 📍 Secciones desde campo hidden
                    const seccionesHiddenField = document.getElementById('paso3_secciones_datos');
                    if (seccionesHiddenField && seccionesHiddenField.value) {
                        try {
                            const seccionesRaw = JSON.parse(seccionesHiddenField.value);
                            seccionesActualizadas = seccionesRaw.map(seccion => {
                                const cantidadTotal = seccion.tallas?.reduce((sum, t) => sum + (parseInt(t.cantidad) || 0), 0) || 0;
                                return {
                                    seccion: seccion.ubicacion,
                                    tallas: seccion.tallas || [],
                                    ubicaciones: seccion.opciones || [],
                                    observaciones: seccion.observaciones || '',
                                    cantidad: cantidadTotal
                                };
                            });
                            console.log('📍 [FORMULARIO] Secciones desde hidden:', seccionesActualizadas);
                        } catch (e) {
                            console.warn('⚠️ Error parseando secciones:', e);
                        }
                    }
                    
                    // Fallback: cards visuales
                    if (seccionesActualizadas.length === 0) {
                        const seccionCards = document.querySelectorAll('#secciones_agregadas > div');
                        seccionCards.forEach(card => {
                            const headerSpan = card.querySelector('div:first-child span:first-child');
                            const prenda = headerSpan?.textContent.trim() || '';
                            
                            const contentDiv = card.querySelector('div:last-child');
                            const contentHtml = contentDiv?.innerHTML || '';
                            
                            const tallas = [];
                            const tallasMatch = contentHtml.match(/<strong>Tallas:<\/strong>\s*([^<]+)/);
                            if (tallasMatch) {
                                const tallaMatches = tallasMatch[1].matchAll(/([A-Z0-9]+)\s*\((\d+)\)/g);
                                for (const match of tallaMatches) {
                                    tallas.push({ talla: match[1], cantidad: parseInt(match[2]) });
                                }
                            }
                            
                            const ubicacionesMatch = contentHtml.match(/<strong>Ubicaciones:<\/strong>\s*([^<]+)/);
                            const ubicaciones = ubicacionesMatch ? ubicacionesMatch[1].split(',').map(u => u.trim()).filter(u => u) : [];
                            
                            const obsMatch = contentHtml.match(/<strong>Obs:<\/strong>\s*([^<]+)/);
                            const observaciones = obsMatch ? obsMatch[1].trim() : '';
                            
                            const cantidadTotal = tallas.reduce((sum, t) => sum + t.cantidad, 0);
                            
                            if (prenda) {
                                seccionesActualizadas.push({
                                    seccion: prenda,
                                    tallas: tallas,
                                    ubicaciones: ubicaciones,
                                    observaciones: observaciones,
                                    cantidad: cantidadTotal
                                });
                            }
                        });
                        console.log('📍 [FORMULARIO] Secciones desde cards:', seccionesActualizadas);
                    }
                }

                // ✅ NUEVO: Calcular cantidad total (suma de todas las tallas del logo)
                let cantidadTotal = 0;
                seccionesActualizadas.forEach(seccion => {
                    seccion.tallas.forEach(talla => {
                        cantidadTotal += talla.cantidad || 0;
                    });
                });
                
                console.log('📦 [LOGO] Cantidad total calculada (suma de tallas):', cantidadTotal);

                // Descripción del logo
                let descripcionLogoPedido = '';
                const descripcionInput = document.getElementById('logo_descripcion');
                if (descripcionInput) {
                    descripcionLogoPedido = descripcionInput.value || '';
                } else if (currentLogoCotizacion && currentLogoCotizacion.descripcion) {
                    descripcionLogoPedido = currentLogoCotizacion.descripcion;
                }

                console.log('🎨 [LOGO] Descripción:', descripcionLogoPedido);
                console.log('🎨 [LOGO] Técnicas seleccionadas (array):', tecnicasActualizadas);
                console.log('🎨 [LOGO] Observaciones técnicas:', observacionesTecnicasVar);
                console.log('🎨 [LOGO] Ubicaciones seleccionadas:', seccionesActualizadas);

                // ✅ FIX: Para COMBINADA, usar logo_pedido_id que ya fue creado en el primer request
                // Para LOGO SOLO, usar logo_pedido_id (que es el pedido_id del logo)
                const pedidoIdParaGuardar = esCombinada 
                    ? dataCrearPedido.logo_pedido_id  // ← Usar ID del logo_pedido ya creado
                    : pedidoId;  // ← Para LOGO SOLO
                
                console.log('🔑 [FIX DUPLICADOS] ID a usar para guardar:', {
                    esCombinada: esCombinada,
                    'dataCrearPedido.logo_pedido_id': dataCrearPedido.logo_pedido_id,
                    'pedidoId original': pedidoId,
                    'pedidoIdParaGuardar': pedidoIdParaGuardar
                });

                const bodyLogoPedido = {
                    pedido_id: pedidoIdParaGuardar,  // ✅ FIX: Usar logo_pedido_id para COMBINADA
                    logo_cotizacion_id: logoCotizacionIdAUsar,  // ← Usar valor del servidor
                    cotizacion_id: cotizacionId,  // ✅ Enviar cotizacion_id
                    cliente: clienteInput.value,  // ✅ Enviar cliente
                    forma_de_pago: formaPagoInput.value,  // ✅ Enviar forma de pago
                    descripcion: descripcionLogoPedido,  // ✅ Descripción del logo
                    cantidad: cantidadTotal,  // ✅ Cantidad total
                    tecnicas: tecnicasActualizadas,  // ✅ Datos de cotización o formulario
                    observaciones_tecnicas: observacionesTecnicasVar,  // ✅ Datos de cotización o formulario
                    ubicaciones: seccionesActualizadas,  // ✅ Datos de cotización o formulario
                    fotos: logoFotosSeleccionadas  // ✅ Fotos del logo
                };

                console.log('🎨 [LOGO] Datos del LOGO pedido a guardar:', bodyLogoPedido);

                // ✅ CRÍTICO: Solo hacer fetch para COMBINADA (PL)
                // Para LOGO SOLO, el pedido ya se creó en el primer request
                console.log('\n==================== DECISIÓN CRÍTICA ====================');
                console.log('⚠️  [DECISIÓN] Valor de esCombinada:', esCombinada);
                console.log('⚠️  [DECISIÓN] Tipo de esCombinada:', typeof esCombinada);
                console.log('⚠️  [DECISIÓN] ¿!esCombinada (NO combinada)?', !esCombinada);
                console.log('⚠️  [DECISIÓN] ¿esCombinada (SÍ combinada)?', esCombinada);
                console.log('==========================================================\n');
                
                // ✅ VERIFICAR SI HAY DATOS DE LOGO PARA GUARDAR
                const tieneDescripcion = descripcionLogoPedido && descripcionLogoPedido.trim().length > 0;
                const tieneTecnicas = tecnicasActualizadas && tecnicasActualizadas.length > 0;
                const tieneUbicaciones = seccionesActualizadas && seccionesActualizadas.length > 0;
                const tieneCantidad = cantidadTotal > 0;
                const tieneDataLogo = tieneDescripcion || tieneTecnicas || tieneUbicaciones || tieneCantidad;
                
                console.log('🔍 [VERIFICACIÓN DATA LOGO] Análisis:', {
                    tieneDescripcion: tieneDescripcion,
                    tieneTecnicas: tieneTecnicas,
                    tieneUbicaciones: tieneUbicaciones,
                    tieneCantidad: tieneCantidad,
                    tieneDataLogo: tieneDataLogo
                });
                
                if (!esCombinada) {
                    // Para LOGO SOLO, saltarse el segundo fetch y mostrar éxito directamente
                    console.log('📍 [LOGO SOLO] Es LOGO SOLO, no enviar segundo request');
                    return Promise.resolve({
                        success: true,
                        numero_pedido_logo: dataCrearPedido.numero_pedido || 'LOGO-PENDIENTE',
                        logo_pedido: {
                            numero_pedido: dataCrearPedido.numero_pedido || 'LOGO-PENDIENTE'
                        }
                    });
                }
                
                // ✅ SI ES COMBINADA PERO NO HAY DATOS DE LOGO, NO ENVIAR SEGUNDO REQUEST
                if (esCombinada && !tieneDataLogo) {
                    console.log('📍 [COMBINADA] No hay datos de logo, mostrando solo pedido de prendas');
                    return Promise.resolve({
                        success: true,
                        message: 'Pedido de PRENDAS creado (sin logo)',
                        numero_pedido_produccion: dataCrearPedido.pedido_numero,
                        pedido_produccion: {
                            numero_pedido: dataCrearPedido.pedido_numero
                        },
                        _tieneDataLogo: tieneDataLogo  // ✅ Pasar información para usar después
                    });
                }

                console.log('📍 [COMBINADA] ¡¡¡ ES COMBINADA CON DATOS DE LOGO, ENVIANDO SEGUNDO REQUEST !!!');
                console.log('📍 [COMBINADA] URL: /asesores/pedidos/guardar-logo-pedido');
                console.log('📍 [COMBINADA] BODY:', bodyLogoPedido);
                
                return fetch('/asesores/pedidos/guardar-logo-pedido', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify(bodyLogoPedido)
                });
            })
            .then(response => {
                console.log('✅ [RESPUESTA] Type de respuesta:', typeof response);
                console.log('✅ [RESPUESTA] ¿Es Response?:', response instanceof Response);
                
                // ✅ Si ya es un objeto (Promise.resolve), devolver tal cual
                // Si es una Response real, parsearla
                if (response instanceof Response) {
                    console.log('✅ [RESPUESTA SEGUNDO REQUEST] Status:', response.status);
                    return response.json();
                } else {
                    console.log('ℹ️ [RESPUESTA] No es segundo request, es respuesta local sin logo');
                    return response;
                }
            })
            .then(data => {
                console.log('✅ [RESPUESTA JSON] Respuesta completa:', data);

                if (data.success) {
                    // Para LOGO SOLO, mostrar éxito con número de LOGO
                    if (esLogoSolo) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Pedido de LOGO creado exitosamente\nNúmero de LOGO: ' + (data.logo_pedido?.numero_pedido || data.numero_pedido_logo || ''),
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = '/asesores/pedidos';
                        });
                    } else if (esCombinada) {
                        // Para COMBINADA (PL), mostrar diferentes mensajes según si hay logo o no
                        const numeroPrendas = data.numero_pedido_produccion || data.pedido_produccion?.numero_pedido || dataCrearPedido.pedido_numero || 'N/A';
                        const numeroLogo = data.numero_pedido_logo || data.logo_pedido?.numero_pedido || 'N/A';
                        const hayDataLogo = data._tieneDataLogo !== undefined ? data._tieneDataLogo : tieneDataLogo;
                        
                        // ✅ Si NO hay datos de logo, mostrar solo pedido de prendas
                        if (!hayDataLogo) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                html: '<p style="font-size: 16px; line-height: 1.8;">' +
                                      'Pedido de PRENDAS creado exitosamente<br><br>' +
                                      '<strong>📦 Pedido:</strong> ' + numeroPrendas +
                                      '</p>',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.href = '/asesores/pedidos';
                            });
                        } else {
                            // Si HAY datos de logo, mostrar AMBOS números
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                html: '<p style="font-size: 16px; line-height: 1.8;">' +
                                      'Pedidos creados exitosamente<br><br>' +
                                      '<strong>📦 Pedido Producción:</strong> ' + numeroPrendas + '<br>' +
                                      '<strong>🎨 Pedido Logo:</strong> ' + numeroLogo +
                                      '</p>',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.href = '/asesores/pedidos';
                            });
                        }
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al guardar el LOGO',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('❌ [LOGO] Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error: ' + error.message,
                    confirmButtonText: 'OK'
                });
            });

            return;  // Salir aquí (tanto LOGO SOLO como COMBINADA terminan aquí)
        }

        // ============================================================
        // FLUJO PARA PRENDAS (PRENDA/REFLECTIVO)
        // ============================================================
        const prendas = [];
        
        // Recopilar fotos de logo que quedan en el DOM (las no eliminadas)
        const fotosLogoGlobales = [];
        const imagenesLogoDOM = document.querySelectorAll('img[data-logo-url]');
        imagenesLogoDOM.forEach(img => {
            const logoJSON = img.getAttribute('data-logo-url');
            if (logoJSON) {
                try {
                    const logo = JSON.parse(decodeURIComponent(logoJSON));
                    fotosLogoGlobales.push(logo);
                } catch (e) {
                    console.error('Error parseando logo:', e);
                }
            }
        });
        
        console.log('📸 Fotos de logo globales encontradas:', fotosLogoGlobales.length);
        
        // Recopilar fotos del reflectivo que quedan en el DOM (las no eliminadas)
        const fotosReflectivoGlobales = [];
        const fotosReflectivoInputs = document.querySelectorAll('input[name="reflectivo_fotos_incluir[]"]');
        console.log('🔍 Inputs de fotos reflectivo encontrados:', fotosReflectivoInputs.length);
        fotosReflectivoInputs.forEach(input => {
            const fotoId = parseInt(input.value);
            if (!isNaN(fotoId)) {
                fotosReflectivoGlobales.push(fotoId);
                console.log('  ✅ Foto ID agregada:', fotoId);
            } else {
                console.warn('  ⚠️ ID inválido:', input.value);
            }
        });
        console.log('📸 Fotos de reflectivo seleccionadas (total):', fotosReflectivoGlobales);
        
        prendasCargadas.forEach((prenda, index) => {
            // Saltar prendas eliminadas
            if (prendasEliminadas.has(index)) {
                console.log(`Saltando prenda eliminada: ${index}`);
                return;
            }

            const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${index}"]`);
            if (!prendasCard) return;
            
            // Obtener valores editados
            const nombreProducto = prendasCard.querySelector(`.prenda-nombre`)?.value || prenda.nombre_producto;
            let descripcion = prendasCard.querySelector(`.prenda-descripcion`)?.value || prenda.descripcion;
            
            // Para cotizaciones reflectivas, recopilar descripción y ubicaciones
            const descripcionReflectivoInput = prendasCard.querySelector(`textarea[name="reflectivo_descripcion[${index}]"]`);
            if (descripcionReflectivoInput) {
                descripcion = descripcionReflectivoInput.value || '';
                
                // Agregar ubicaciones a la descripción
                const ubicacionesInputs = prendasCard.querySelectorAll(`input[name^="reflectivo_ubicaciones[${index}]"][name$="[ubicacion]"]`);
                if (ubicacionesInputs.length > 0) {
                    const ubicaciones = [];
                    ubicacionesInputs.forEach(input => {
                        if (input.value) {
                            ubicaciones.push(input.value);
                        }
                    });
                    if (ubicaciones.length > 0) {
                        descripcion += '\n\nUbicaciones del reflectivo:\n' + ubicaciones.join(', ');
                    }
                }
            }
            
            // Obtener cantidades por talla
            const cantidadesPorTalla = {};
            const tallaInputs = prendasCard.querySelectorAll('.talla-cantidad');
            tallaInputs.forEach(input => {
                const cantidad = parseInt(input.value) || 0;
                const talla = input.getAttribute('data-talla');
                if (cantidad > 0) {
                    cantidadesPorTalla[talla] = cantidad;
                }
            });

            // Si no hay cantidades, omitir la prenda
            if (Object.keys(cantidadesPorTalla).length === 0) {
                console.log(`Omitiendo prenda sin cantidades: ${index}`);
                return;
            }

            // Recopilar TODOS los datos editados de variaciones
            const variacionesEditadas = {};
            const inputsVariaciones = prendasCard.querySelectorAll('[data-field]');
            inputsVariaciones.forEach(input => {
                const field = input.getAttribute('data-field');
                let value;
                
                // Distinguir entre checkbox e input text
                if (input.type === 'checkbox') {
                    value = input.checked ? 1 : 0;
                } else {
                    value = input.value || '';
                }
                
                if (field && value !== '') {
                    variacionesEditadas[field] = value;
                }
            });

            // Recopilar telas/colores editadas
            const telasEditadas = [];
            const telaCards = prendasCard.querySelectorAll('[data-prenda="' + index + '"]');
            telaCards.forEach(card => {
                const telaNombre = card.querySelector('[data-field="tela_nombre"]')?.value;
                const telaColor = card.querySelector('[data-field="tela_color"]')?.value;
                const telaRef = card.querySelector('[data-field="tela_ref"]')?.value;
                
                if (telaNombre || telaColor || telaRef) {
                    telasEditadas.push({
                        tela: telaNombre || prenda.tela,
                        color: telaColor || prenda.color,
                        referencia: telaRef || ''
                    });
                }
            });

            // Obtener géneros seleccionados
            const generosSeleccionados = [];
            const generosCheckboxes = prendasCard.querySelectorAll('.genero-checkbox:checked');
            generosCheckboxes.forEach(checkbox => {
                generosSeleccionados.push(checkbox.value);
            });

            // ✅ RECOPILAR FOTOS QUE QUEDAN EN EL DOM (las no eliminadas por el usuario)
            const fotosEnDOM = [];
            const imagenesPrendaDOM = prendasCard.querySelectorAll('img[data-foto-url][data-prenda-index="' + index + '"]');
            imagenesPrendaDOM.forEach(img => {
                // Leer la foto completa del atributo data-foto-url (no usar índice)
                const fotoJSON = img.getAttribute('data-foto-url');
                if (fotoJSON) {
                    try {
                        const foto = JSON.parse(decodeURIComponent(fotoJSON));
                        fotosEnDOM.push(foto);
                    } catch (e) {
                        console.error('Error parseando foto:', e);
                    }
                }
            });

            // ✅ RECOPILAR FOTOS DE TELAS QUE QUEDAN EN EL DOM
            const fotosTelaEnDOM = [];
            const imagenesTelaDOM = prendasCard.querySelectorAll('img[data-tela-foto-url][data-prenda-index="' + index + '"]');
            imagenesTelaDOM.forEach(img => {
                // Leer la foto completa del atributo data-tela-foto-url
                const fotoJSON = img.getAttribute('data-tela-foto-url');
                if (fotoJSON) {
                    try {
                        const foto = JSON.parse(decodeURIComponent(fotoJSON));
                        fotosTelaEnDOM.push(foto);
                    } catch (e) {
                        console.error('Error parseando foto de tela:', e);
                    }
                }
            });

            console.log(`Prenda ${index}: Fotos restantes: ${fotosEnDOM.length}, Fotos tela: ${fotosTelaEnDOM.length}`);
            console.log(`Prenda ${index}: Fotos tela originales: ${prenda.telaFotos?.length || 0}, Fotos tela restantes: ${fotosTelaEnDOM.length}`);

            // ✅ RECOPILAR TALLAS POR GÉNERO DESDE EL CONTENEDOR DINÁMICO
            const generosConTallas = {};
            generosSeleccionados.forEach(genero => {
                generosConTallas[genero] = {};
                const tallaInputs = prendasCard.querySelectorAll(`.talla-cantidad-genero-editable[data-genero="${genero}"]`);
                tallaInputs.forEach(input => {
                    const talla = input.dataset.talla;
                    const cantidad = parseInt(input.value) || 0;
                    if (cantidad > 0) {
                        generosConTallas[genero][talla] = cantidad;
                    }
                });
            });

            prendas.push({
                index: index,
                nombre_producto: nombreProducto,
                descripcion: descripcion,
                genero: generosSeleccionados.length > 0 ? generosSeleccionados : prenda.variantes?.genero,
                generosConTallas: Object.keys(generosConTallas).length > 0 ? generosConTallas : {},
                manga: variacionesEditadas['tipo_manga'] || prenda.variantes?.tipo_manga || prenda.variantes?.manga,
                broche: variacionesEditadas['tipo_broche'] || prenda.variantes?.tipo_broche || prenda.variantes?.broche,
                tiene_bolsillos: variacionesEditadas['tiene_bolsillos'] === 'Sí' ? true : (prenda.variantes?.tiene_bolsillos || false),
                tiene_reflectivo: variacionesEditadas['tiene_reflectivo'] === 'Sí' ? true : (prenda.variantes?.tiene_reflectivo || false),
                manga_obs: variacionesEditadas['tipo_manga_obs'] || prenda.variantes?.obs_manga || '',
                bolsillos_obs: variacionesEditadas['tiene_bolsillos_obs'] || prenda.variantes?.obs_bolsillos || '',
                broche_obs: variacionesEditadas['tipo_broche_obs'] || prenda.variantes?.obs_broche || '',
                reflectivo_obs: variacionesEditadas['tiene_reflectivo_obs'] || prenda.variantes?.obs_reflectivo || '',
                observaciones: prenda.variantes?.observaciones,
                telas_multiples: telasEditadas.length > 0 ? telasEditadas : prenda.telas_multiples,
                cantidades: cantidadesPorTalla,
                fotos: fotosEnDOM.length > 0 ? fotosEnDOM : prenda.fotos || [],
                telas: fotosTelaEnDOM.length > 0 ? fotosTelaEnDOM : (prenda.telaFotos || prenda.telas || []),
                logos: fotosLogoGlobales.length > 0 ? fotosLogoGlobales : (prenda.logos || [])
            });
        });

        if (prendas.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Sin prendas con cantidades',
                text: 'Debes agregar cantidades a al menos una prenda',
                confirmButtonText: 'OK'
            });
            return;
        }

        console.log('Prendas a enviar:', prendas);

        // Enviar al servidor
        const url = `/asesores/pedidos-produccion/crear-desde-cotizacion/${cotizacionId}`;
        console.log('📤 URL completa:', url);
        console.log('📤 cotizacionId:', cotizacionId);
        console.log('📤 Fotos reflectivo a enviar:', fotosReflectivoGlobales);
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
                cotizacion_id: cotizacionId,
                forma_de_pago: formaPagoInput.value,
                prendas: prendas,
                reflectivo_fotos_ids: fotosReflectivoGlobales
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta del servidor:', data);
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'Pedido de producción creado exitosamente',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Redirigir a la lista de pedidos
                    window.location.href = '/asesores/pedidos';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al crear el pedido',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('❌ Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al enviar el formulario: ' + error.message,
                confirmButtonText: 'OK'
            });
        });
    }

    console.log('Script de formulario editable cargado correctamente');

    /**
     * Actualizar resumen de una prenda (tallas y fotos)
     * DESHABILITADO: El resumen fue removido de la interfaz
     */
    window.actualizarResumenPrenda = function(prendasContainer) {
        // Función disponible pero inactiva
        console.log('actualizarResumenPrenda: Resumen removido de la interfaz');
    };
});

// ============================================================
// FUNCIONES GLOBALES PARA ELIMINAR TALLAS (REFLECTIVO)
// ============================================================


// ============================================================
// CONFIRMACIONES Y ALERTAS - TRASLADADAS A modales-pedido.js
// ============================================================
// Funciones: eliminarTallaReflectivo(), eliminarImagenPrenda(), 
//            eliminarImagenTela(), eliminarImagenLogo(), eliminarFotoReflectivoPedido()
// Se encuentran en: modulos/crear-pedido/modales-pedido.js

// ============================================================
// GESTIÓN DINÁMICA DE TALLAS EN PRENDAS
// ============================================================

/**
 * Mostrar modal para agregar una talla a una prenda
 * @param {number} prendaIndex - Índice de la prenda
 */
window.mostrarModalAgregarTalla = function(prendaIndex) {
    console.log('🔘 Botón "+ Talla" clickeado para prenda:', prendaIndex);
    console.log('📏 tallasDisponiblesCotizacion actual:', tallasDisponiblesCotizacion);
    
    // Obtener tallas actuales de la prenda
    const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) {
        console.error('❌ No se encontró la tarjeta de prenda con índice:', prendaIndex);
        mostrarError('Error', 'No se encontró la prenda');
        return;
    }

    console.log('✅ Tarjeta de prenda encontrada');

    // Obtener tallas actuales
    const tallasActuales = Array.from(prendaCard.querySelectorAll('input[data-talla]')).map(input => input.dataset.talla);
    
    console.log('📏 Tallas actuales de prenda ' + prendaIndex + ':', tallasActuales);
    console.log('📏 Tallas disponibles en cotización:', tallasDisponiblesCotizacion);
    
    // Filtrar tallas disponibles que no estén en la prenda actual
    const tallasDisponibles = tallasDisponiblesCotizacion.filter(talla => !tallasActuales.includes(talla));
    
    console.log('📏 Tallas disponibles para agregar:', tallasDisponibles);

    if (!tallasDisponiblesCotizacion || tallasDisponiblesCotizacion.length === 0) {
        mostrarAdvertencia('Sin tallas cargadas', 'La cotización no tiene tallas definidas. Por favor, selecciona una cotización válida.');
        return;
    }

    if (tallasDisponibles.length === 0) {
        modalInfo('Sin tallas disponibles', 'Ya tienes todas las tallas disponibles en esta prenda.');
        return;
    }

    // Mostrar modal de selección usando función helper de modales
    modalAgregarTalla(prendaIndex, tallasDisponibles).then((result) => {
        if (result.isConfirmed) {
            const tallaSeleccionada = document.getElementById('selector_talla_agregar').value;
            console.log('📏 Talla seleccionada:', tallaSeleccionada);
            if (tallaSeleccionada) {
                agregarTallaAlFormulario(prendaIndex, tallaSeleccionada);
            } else {
                mostrarAdvertencia('Selecciona una talla', 'Por favor selecciona una talla para continuar');
            }
        }
    });
};

/**
 * Agregar una talla al formulario de una prenda
 * @param {number} prendaIndex - Índice de la prenda
 * @param {string} talla - Talla a agregar
 */
window.agregarTallaAlFormulario = function(prendaIndex, talla) {
    const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) {
        console.error('No se encontró la tarjeta de prenda');
        return;
    }

    // Verificar si la talla ya existe
    const inputExistente = prendaCard.querySelector(`input[data-talla="${talla}"]`);
    if (inputExistente) {
        Swal.fire({
            icon: 'warning',
            title: 'Talla duplicada',
            text: `La talla ${talla} ya está en esta prenda.`
        });
        return;
    }

    // Encontrar el contenedor de tallas buscando por todos los divs que tengan inputs de talla
        let tallasContainer = null;
        const allDivs = prendaCard.querySelectorAll('div[style*="margin-top: 1.5rem"]');
        for (let div of allDivs) {
            if (div.querySelector('input[data-talla]')) {
                tallasContainer = div;
                break;
            }
        }
        
        if (!tallasContainer) {
            console.error('No se encontró el contenedor de tallas');
            console.log('Divs encontrados:', allDivs.length);
            return;
        }

        // Crear el HTML de la nueva talla
        const nuevoTallaHtml = `<div style="padding: 1rem; background: white; border: 1px solid #e0e0e0; border-top: none; display: grid; grid-template-columns: 1.5fr 1fr 100px; gap: 1rem; align-items: center; transition: background 0.2s; width: 100%;">
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Talla</label>
                <div style="font-weight: 500; color: #1f2937;">${talla}</div>
            </div>
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Cantidad</label>
                <input type="number" 
                       name="cantidades[${prendaIndex}][${talla}]" 
                       class="talla-cantidad"
                       min="0" 
                       value="0" 
                       placeholder="0"
                       data-talla="${talla}"
                       data-prenda="${prendaIndex}"
                       style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;">
            </div>
            <div style="text-align: center;">
                <button type="button" class="btn-quitar-talla" onclick="quitarTallaDelFormulario(${prendaIndex}, '${talla}')" style="background: #dc3545; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.3rem; white-space: nowrap;">
                    ✕ Quitar
                </button>
            </div>
        </div>`;

        // Insertar el nuevo elemento antes del cierre del contenedor
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = nuevoTallaHtml;
        const newElement = tempDiv.firstElementChild;
        
        // Insertar antes del cierre (buscar el último elemento que no sea un div de talla)
        const ultimoTallaRow = tallasContainer.querySelector('div[style*="border-top: none"]:last-of-type');
        if (ultimoTallaRow) {
            ultimoTallaRow.insertAdjacentElement('afterend', newElement);
        } else {
            tallasContainer.appendChild(newElement);
        }

        console.log(`✅ Talla ${talla} agregada a prenda ${prendaIndex}`);
        
        Swal.fire({
            icon: 'success',
            title: 'Talla agregada',
            text: `La talla ${talla} ha sido agregada a la prenda ${prendaIndex + 1}`,
            timer: 1500,
            showConfirmButton: false
        });
};

/**
 * ✅ ACTUALIZAR CONTENEDOR DINÁMICO DE TALLAS POR GÉNERO (VERSIÓN EDITABLE)
 * Usa MODAL para seleccionar tallas específicas por género, no muestra todas
 * @param {number} prendaIndex - Índice de la prenda
 * @param {HTMLElement} prendasCard - Elemento tarjeta de la prenda
 * @param {Array} generosSeleccionados - Array de géneros seleccionados
 */
window.actualizarContenedorTallasPorGeneroEditable = function(prendaIndex, prendasCard, generosSeleccionados) {
    const container = prendasCard.querySelector('.tallas-por-genero-container');
    if (!container) return;
    
    if (generosSeleccionados.length === 0) {
        container.innerHTML = '<p style="color: #9ca3af; font-size: 0.9rem; margin-top: 0.5rem;">Selecciona al menos un género para agregar tallas</p>';
        return;
    }
    
    let html = '';
    
    // Para cada género seleccionado, crear sección con botón para agregar tallas
    generosSeleccionados.forEach((genero) => {
        const generoLabel = genero.charAt(0).toUpperCase() + genero.slice(1);
        
        // Encabezado de género con botón para agregar talla
        html += `
            <div style="margin-top: 1.5rem; padding: 0.75rem 1rem; background: linear-gradient(135deg, #0066cc 0%, #0066cc 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-user"></i> ${generoLabel}
                </div>
                <button type="button" 
                        onclick="agregarTallaParaGenero(${prendaIndex}, '${genero}')"
                        style="background: white; color: #0066cc; border: none; padding: 0.4rem 0.6rem; border-radius: 999px; cursor: pointer; font-size: 0.9rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 0.3rem; white-space: nowrap; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.15);" title="Agregar talla">
                    <i class="fas fa-plus" style="font-size: 0.75rem;"></i> Talla
                </button>
            </div>
            <div class="tallas-genero-container" data-prenda="${prendaIndex}" data-genero="${genero}" style="min-height: 50px;"></div>
        `;
    });
    
    container.innerHTML = html;
    
    // Renderizar las tallas ya agregadas para cada género
    generosSeleccionados.forEach((genero) => {
        renderizarTallasDelGenero(prendaIndex, genero);
    });
};

/**
 * Renderizar las tallas ya agregadas para un género específico
 */
function renderizarTallasDelGenero(prendaIndex, genero) {
    const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendasCard) return;
    
    const containerGenero = prendasCard.querySelector(`.tallas-genero-container[data-prenda="${prendaIndex}"][data-genero="${genero}"]`);
    if (!containerGenero) return;
    
    // Buscar todos los inputs de tallas para este género
    const tallasInputs = prendasCard.querySelectorAll(`.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${genero}"]`);
    
    if (tallasInputs.length === 0) {
        containerGenero.innerHTML = '<p style="padding: 0.75rem 1rem; background: white; color: #9ca3af; font-size: 0.85rem; margin: 0; border: 1px solid #e0e0e0; border-top: none; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;">Sin tallas agregadas</p>';
        return;
    }
    
    let html = '';
    let isFirst = true;
    
    tallasInputs.forEach((input) => {
        const talla = input.dataset.talla;
        const cantidad = input.value || '0';
        
        html += `
            <div style="padding: 1rem; background: white; border: 1px solid #e0e0e0; ${isFirst ? '' : 'border-top: none;'} display: grid; grid-template-columns: 1.5fr 1fr 100px; gap: 1rem; align-items: center; transition: background 0.2s; width: 100%;">
                <div style="display: flex; flex-direction: column;">
                    <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Talla</label>
                    <div style="font-weight: 500; color: #1f2937;">${talla}</div>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Cantidad</label>
                    <input type="number" 
                           min="0" 
                           value="${cantidad}" 
                           placeholder="0"
                           class="talla-cantidad-display-editable"
                           data-talla="${talla}"
                           data-genero="${genero}"
                           data-prenda="${prendaIndex}"
                           style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;">
                </div>
                <div style="text-align: center;">
                    <button type="button" class="btn-eliminar-talla-genero" onclick="eliminarTallaDelGenero(${prendaIndex}, '${genero}', '${talla}')" style="background: #dc3545; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.3rem; white-space: nowrap;" title="Eliminar talla">
                        <i class="fas fa-trash-alt" style="font-size: 0.7rem;"></i> Quitar
                    </button>
                </div>
            </div>
        `;
        isFirst = false;
    });
    
    containerGenero.innerHTML = html;
    
    // Agregar listeners a los inputs de display y sincronizar con hidden
    containerGenero.querySelectorAll('.talla-cantidad-display-editable').forEach(input => {
        input.addEventListener('change', function() {
            const prendaIdx = parseInt(this.dataset.prenda);
            const gen = this.dataset.genero;
            const talla = this.dataset.talla;
            const cantidad = parseInt(this.value) || 0;
            
            // Actualizar el input hidden correspondiente
            const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIdx}"]`);
            if (prendasCard) {
                const hiddenInput = prendasCard.querySelector(`.talla-cantidad-genero-editable[data-prenda="${prendaIdx}"][data-genero="${gen}"][data-talla="${talla}"]`);
                if (hiddenInput) {
                    hiddenInput.value = cantidad;
                }
            }
            
            // ✅ CRÍTICO: Actualizar directamente en el gestor cuando el usuario cambia cantidad
            if (window.gestorPrendaSinCotizacion) {
                const prenda = window.gestorPrendaSinCotizacion.obtenerPorIndice(prendaIdx);
                if (prenda) {
                    if (!prenda.generosConTallas[gen]) {
                        prenda.generosConTallas[gen] = {};
                    }
                    prenda.generosConTallas[gen][talla] = cantidad;
                    console.log(`✅ ACTUALIZADO EN GESTOR - Prenda ${prendaIdx}, ${gen} ${talla}: ${cantidad}`);
                }
            }
            
            console.log(`✅ Cantidad actualizada - Prenda: ${prendaIdx}, Género: ${gen}, Talla: ${talla}, Cantidad: ${cantidad}`);
        });
        
        input.addEventListener('input', function() {
            if (this.value < 0) this.value = 0;
        });
    });
}

/**
 * Obtener el tipo de talla del otro género (si existe)
 */
window.obtenerTipoTallaDelOtroGenero = function(prendaIndex, generoActual) {
    const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendasCard) return null;
    
    const otroGenero = generoActual === 'dama' ? 'caballero' : 'dama';
    
    // Buscar si hay tallas del otro género con tipo LETRA
    const tallasLetra = prendasCard.querySelectorAll(
        `.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${otroGenero}"][data-tipo-talla="letra"]`
    );
    if (tallasLetra.length > 0) return 'letra';
    
    // Buscar si hay tallas del otro género con tipo NÚMERO
    const tallasNumero = prendasCard.querySelectorAll(
        `.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${otroGenero}"][data-tipo-talla="numero"]`
    );
    if (tallasNumero.length > 0) return 'numero';
    
    return null;
};

/**
 * Agregar talla(s) a un género - Flujo interactivo
 * Paso 1: Elegir tipo de talla (LETRA o NÚMERO)
 * Paso 2: Elegir método (MANUAL o RANGO)
 * Paso 3: Seleccionar tallas
 * 
 * RESTRICCIÓN: Si el otro género ya tiene tallas, debe ser del mismo tipo
 */
window.agregarTallaParaGenero = function(prendaIndex, genero) {
    const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendasCard) return;
    
    // Verificar qué tipo de talla usa el otro género (si es que tiene)
    const tipoDelOtroGenero = obtenerTipoTallaDelOtroGenero(prendaIndex, genero);
    const otroGenero = genero === 'dama' ? 'caballero' : 'dama';
    
    if (tipoDelOtroGenero) {
        // Si el otro género ya tiene tallas, forzar el mismo tipo
        const tipoLabel = tipoDelOtroGenero === 'letra' ? 'LETRA' : 'NÚMERO';
        Swal.fire({
            icon: 'info',
            title: 'Tipo de Talla Definido',
            html: `
                <p style="margin: 0 0 1rem 0;">El género <strong>${otroGenero.charAt(0).toUpperCase() + otroGenero.slice(1)}</strong> ya usa tallas por <strong>${tipoLabel}</strong>.</p>
                <p style="margin: 0; color: #666;">Este género también debe usar el mismo tipo.</p>
            `
        }).then(() => {
            agregarTallasPorMetodo(prendaIndex, genero, tipoDelOtroGenero);
        });
        return;
    }
    
    // Paso 1: Seleccionar tipo de talla (si no hay restricción)
    Swal.fire({
        title: 'Tipo de Talla',
        html: `
            <div style="display: flex; gap: 1rem; justify-content: center; padding: 1rem;">
                <button type="button" id="btn-letra" style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><i class="fas fa-font" style="color: #0066cc;"></i></div>
                    <div>LETRA</div>
                    <div style="font-size: 0.75rem; color: #666; margin-top: 0.5rem;">XS, S, M, L, XL...</div>
                </button>
                <button type="button" id="btn-numero" style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><i class="fas fa-hashtag" style="color: #0066cc;"></i></div>
                    <div>NÚMERO</div>
                    <div style="font-size: 0.75rem; color: #666; margin-top: 0.5rem;">6, 8, 10, 12...</div>
                </button>
            </div>
        `,
        showConfirmButton: false,
        didOpen: () => {
            document.getElementById('btn-letra').addEventListener('click', () => {
                Swal.close();
                agregarTallasPorMetodo(prendaIndex, genero, 'letra');
            });
            document.getElementById('btn-numero').addEventListener('click', () => {
                Swal.close();
                agregarTallasPorMetodo(prendaIndex, genero, 'numero');
            });
        }
    });
};

/**
 * Paso 2 y 3: Seleccionar método (MANUAL o RANGO) y luego las tallas
 */
window.agregarTallasPorMetodo = function(prendaIndex, genero, tipoTalla) {
    // Definir tallas disponibles según TIPO y GÉNERO
    // LETRA: ambos géneros usan lo mismo
    // NÚMERO: diferentes números para cada género
    const tallasLetra = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
    const tallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
    const tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46'];
    
    let tallasPorTipo;
    if (tipoTalla === 'letra') {
        tallasPorTipo = tallasLetra;
    } else {
        // Para números, usar diferentes según género
        tallasPorTipo = (genero === 'dama') ? tallasDama : tallasCaballero;
    }
    
    // Obtener tallas ya agregadas
    const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendasCard) return;
    
    const tallasActuales = Array.from(prendasCard.querySelectorAll(`.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${genero}"]`))
        .map(input => input.dataset.talla);
    
    const tallasDisponibles = tallasPorTipo.filter(talla => !tallasActuales.includes(talla));
    
    if (tallasDisponibles.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin tallas disponibles',
            text: `Ya tienes todas las tallas de ${tipoTalla === 'letra' ? 'LETRA' : 'NÚMERO'} agregadas`
        });
        return;
    }
    
    // Paso 2: Seleccionar método (MANUAL o RANGO) - Ambos tipos tienen esta opción
    Swal.fire({
        title: 'Método de Selección',
        html: `
            <div style="display: flex; gap: 1rem; justify-content: center; padding: 1rem;">
                <button type="button" id="btn-manual" style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><i class="fas fa-hand-pointer" style="color: #0066cc;"></i></div>
                    <div>MANUAL</div>
                    <div style="font-size: 0.75rem; color: #666; margin-top: 0.5rem;">Una por una</div>
                </button>
                <button type="button" id="btn-rango" style="flex: 1; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s;">
                    <div style="font-size: 1.5rem; margin-bottom: 0.5rem;"><i class="fas fa-sliders-h" style="color: #0066cc;"></i></div>
                    <div>RANGO</div>
                    <div style="font-size: 0.75rem; color: #666; margin-top: 0.5rem;">Desde... hasta</div>
                </button>
            </div>
        `,
        showConfirmButton: false,
        didOpen: () => {
            document.getElementById('btn-manual').addEventListener('click', () => {
                Swal.close();
                seleccionarTallasManual(prendaIndex, genero, tallasDisponibles, tipoTalla);
            });
            document.getElementById('btn-rango').addEventListener('click', () => {
                Swal.close();
                seleccionarTallasRango(prendaIndex, genero, tallasPorTipo, tallasActuales, tipoTalla);
            });
        }
    });
};

/**
 * Paso 3A: Selección MANUAL (una por una)
 */
window.seleccionarTallasManual = function(prendaIndex, genero, tallasDisponibles, tipoTalla) {
    const generoLabel = genero.charAt(0).toUpperCase() + genero.slice(1);
    
    Swal.fire({
        title: `Agregar Tallas - ${generoLabel} (MANUAL)`,
        html: `
            <div style="max-height: 400px; overflow-y: auto; padding: 1rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 0.5rem;">
                    ${tallasDisponibles.map(talla => `
                        <button type="button" class="btn-talla-manual" data-talla="${talla}" 
                                style="padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 6px; background: white; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s;">
                            ${talla}
                        </button>
                    `).join('')}
                </div>
            </div>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                <div style="font-size: 0.85rem; color: #666; font-weight: 500;">Tallas seleccionadas: <span id="contador-tallas">0</span></div>
                <div id="lista-tallas-seleccionadas" style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem;"></div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const tallasSeleccionadas = new Set();
            
            document.querySelectorAll('.btn-talla-manual').forEach(btn => {
                btn.addEventListener('click', function() {
                    const talla = this.dataset.talla;
                    
                    if (tallasSeleccionadas.has(talla)) {
                        tallasSeleccionadas.delete(talla);
                        this.style.background = 'white';
                        this.style.borderColor = '#e5e7eb';
                        this.classList.remove('btn-talla-seleccionada');
                    } else {
                        tallasSeleccionadas.add(talla);
                        this.style.background = '#0066cc';
                        this.style.color = 'white';
                        this.style.borderColor = '#0066cc';
                        this.classList.add('btn-talla-seleccionada');
                    }
                    
                    // Actualizar contador y lista
                    document.getElementById('contador-tallas').textContent = tallasSeleccionadas.size;
                    document.getElementById('lista-tallas-seleccionadas').innerHTML = 
                        Array.from(tallasSeleccionadas).map(t => `<span style="background: #e3f2fd; color: #0066cc; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">${t}</span>`).join('');
                });
            });
        },
        preConfirm: () => {
            const contador = parseInt(document.getElementById('contador-tallas').textContent);
            if (contador === 0) {
                Swal.showValidationMessage('Selecciona al menos una talla');
                return false;
            }
            return Array.from(document.querySelectorAll('.btn-talla-manual.btn-talla-seleccionada')).map(btn => btn.dataset.talla);
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            agregarTallasAlGenero(prendaIndex, genero, result.value, tipoTalla);
        }
    });
};

/**
 * Paso 3B: Selección por RANGO (desde... hasta)
 */
window.seleccionarTallasRango = function(prendaIndex, genero, todasLasTallas, tallasActuales, tipoTalla) {
    const generoLabel = genero.charAt(0).toUpperCase() + genero.slice(1);
    const tallasDisponibles = todasLasTallas.filter(t => !tallasActuales.includes(t));
    
    Swal.fire({
        title: `Agregar Tallas por Rango - ${generoLabel}`,
        html: `
            <div style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; text-align: left;">Desde:</label>
                    <select id="talla-inicio" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem;">
                        <option value="">-- Selecciona --</option>
                        ${todasLasTallas.map(talla => `<option value="${talla}">${talla}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; text-align: left;">Hasta:</label>
                    <select id="talla-fin" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem;">
                        <option value="">-- Selecciona --</option>
                        ${todasLasTallas.map(talla => `<option value="${talla}">${talla}</option>`).join('')}
                    </select>
                </div>
                <div style="background: #f0f7ff; padding: 0.75rem; border-radius: 4px; font-size: 0.85rem; color: #1e3a8a; font-weight: 500;">
                    Tallas a agregar: <span id="preview-rango">0</span>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            const selectInicio = document.getElementById('talla-inicio');
            const selectFin = document.getElementById('talla-fin');
            const preview = document.getElementById('preview-rango');
            
            const actualizarPreview = () => {
                const inicio = selectInicio.value;
                const fin = selectFin.value;
                
                if (inicio && fin) {
                    const idxInicio = todasLasTallas.indexOf(inicio);
                    const idxFin = todasLasTallas.indexOf(fin);
                    
                    if (idxInicio >= 0 && idxFin >= 0) {
                        const [min, max] = idxInicio <= idxFin ? [idxInicio, idxFin] : [idxFin, idxInicio];
                        const rango = todasLasTallas.slice(min, max + 1);
                        preview.textContent = rango.filter(t => !tallasActuales.includes(t)).length;
                    }
                } else {
                    preview.textContent = '0';
                }
            };
            
            selectInicio.addEventListener('change', actualizarPreview);
            selectFin.addEventListener('change', actualizarPreview);
        },
        preConfirm: () => {
            const inicio = document.getElementById('talla-inicio').value;
            const fin = document.getElementById('talla-fin').value;
            
            if (!inicio || !fin) {
                Swal.showValidationMessage('Selecciona talla inicial y final');
                return false;
            }
            
            const idxInicio = todasLasTallas.indexOf(inicio);
            const idxFin = todasLasTallas.indexOf(fin);
            const [min, max] = idxInicio <= idxFin ? [idxInicio, idxFin] : [idxFin, idxInicio];
            const rango = todasLasTallas.slice(min, max + 1);
            
            return rango.filter(t => !tallasActuales.includes(t));
        }
    }).then((result) => {
        if (result.isConfirmed && result.value && result.value.length > 0) {
            agregarTallasAlGenero(prendaIndex, genero, result.value, tipoTalla);
        }
    });
};

/**
 * Agregar tallas al género (después de seleccionarlas)
 */
window.agregarTallasAlGenero = function(prendaIndex, genero, tallas, tipoTalla) {
    const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendasCard) return;
    
    // Crear inputs hidden para cada talla (solo si no existen)
    tallas.forEach(talla => {
        // Verificar si ya existe una talla con este valor
        const existente = prendasCard.querySelector(`.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${genero}"][data-talla="${talla}"]`);
        if (existente) {
            console.warn(`⚠️ Talla ${talla} ya existe para ${genero}`);
            return; // Saltar si ya existe
        }
        
        const inputTalla = document.createElement('input');
        inputTalla.type = 'hidden';
        inputTalla.name = `cantidades_genero[${prendaIndex}][${genero}][${talla}]`;
        inputTalla.className = 'talla-cantidad-genero-editable';
        inputTalla.value = '0';
        inputTalla.dataset.talla = talla;
        inputTalla.dataset.genero = genero;
        inputTalla.dataset.prenda = prendaIndex;
        inputTalla.dataset.tipoTalla = tipoTalla;  // Guardar el tipo de talla
        
        prendasCard.appendChild(inputTalla);
        
        // ✅ CRÍTICO: Agregar talla al gestor para que aparezca en validación
        if (window.gestorPrendaSinCotizacion) {
            window.gestorPrendaSinCotizacion.agregarTalla(prendaIndex, talla);
        }
    });
    
    // Re-renderizar la sección del género
    renderizarTallasDelGenero(prendaIndex, genero);
    
    Swal.fire({
        icon: 'success',
        title: 'Tallas agregadas',
        text: `Se agregaron ${tallas.length} talla(s) a ${genero}`,
        timer: 1500,
        showConfirmButton: false
    });
};

/**
 * Eliminar una talla de un género
 */
window.eliminarTallaDelGenero = function(prendaIndex, genero, talla) {
    Swal.fire({
        title: '¿Eliminar talla?',
        text: `¿Eliminar talla ${talla} de ${genero}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
            if (!prendasCard) return;
            
            // Buscar y eliminar el input
            const input = prendasCard.querySelector(`.talla-cantidad-genero-editable[data-prenda="${prendaIndex}"][data-genero="${genero}"][data-talla="${talla}"]`);
            if (input) {
                input.remove();
            }
            
            // ✅ CRÍTICO: Eliminar talla del gestor
            if (window.gestorPrendaSinCotizacion) {
                window.gestorPrendaSinCotizacion.eliminarTalla(prendaIndex, talla);
            }
            
            // Re-renderizar la sección del género
            renderizarTallasDelGenero(prendaIndex, genero);
        }
    });
};

// ============================================================
// GALERÍAS Y MODALES - TRASLADADOS A modales-pedido.js
// Funciones: abrirModalImagen(), abrirGaleriaPrenda(), abrirGaleriaTela()
// Se encuentran en: modulos/crear-pedido/modales-pedido.js
// ============================================================

