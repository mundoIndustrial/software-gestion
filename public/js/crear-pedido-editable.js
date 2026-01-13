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
            dropdown.innerHTML = '<div style="padding: 1.5rem; text-align: center;"><div style="color: #ef4444; font-weight: 600; margin-bottom: 0.5rem;">⚠️ No hay cotizaciones aprobadas</div><div style="color: #6b7280; font-size: 0.875rem;">No tienes cotizaciones con estado APROBADA o APROBADO PARA PEDIDO.<br>Crea una cotización y espera su aprobación.</div></div>';
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
        // Verificar si agregarCotizacionAItems existe (sistema de ítems dinámicos)
        if (typeof window.abrirModalSeleccionPrendas === 'function') {
            // Abrir modal para seleccionar prendas y definir origen
            window.abrirModalSeleccionPrendas({
                id: id,
                numero_cotizacion: numero,
                cliente: cliente,
                asesora: asesora,
                formaPago: formaPago
            });
            
            // Limpiar el buscador
            searchInput.value = '';
            dropdown.style.display = 'none';
        } else {
            // Comportamiento legacy (selección única)
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
        }
    };

    // ============================================================
    // CARGAR PRENDAS DESDE COTIZACIÓN (VÍA AJAX)
    // ============================================================
    
    function cargarPrendasDesdeCotizacion(cotizacionId) {
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
                    window.prendasCargadas = data.prendas || [];
                    
                    // Determinar tipo y banderas
                    const esReflectivo = data.tipo_cotizacion === 'R' || data.tipo_cotizacion === 'REFLECTIVO';
                    const esLogo = data.logo && Object.keys(data.logo).length > 0;
                    const tipoCotizacion = data.tipo_cotizacion || 'P';
                    
                    // Asignar logoCotizacionId si existe
                    if (data.logo && data.logo.id) {
                        document.getElementById('logoCotizacionId').value = data.logo.id;
                    }
                    currentEspecificaciones = data.especificaciones || null;
                    currentEsReflectivo = esReflectivo;
                    currentDatosReflectivo = data.reflectivo || null;
                    currentEsLogo = esLogo;
                    currentLogoCotizacion = data.logo || null;
                    currentTipoCotizacion = tipoCotizacion;
                    
                    // Reinicializar arrays de fotos nuevas
                    window.prendasFotosNuevas = [];
                    window.telasFotosNuevas = [];
                    window.reflectiveFotosNuevas = [];
                    
                    // Extraer tallas disponibles
                    tallasDisponiblesCotizacion = [];
                    if (data.prendas && data.prendas.length > 0) {
                        data.prendas.forEach(prenda => {
                            if (prenda.tallas && Array.isArray(prenda.tallas)) {
                                prenda.tallas.forEach(talla => {
                                    if (!tallasDisponiblesCotizacion.includes(talla)) {
                                        tallasDisponiblesCotizacion.push(talla);
                                    }
                                });
                            }
                        });
                    }
                    
                    // Actualizar forma de pago
                    if (data.forma_pago) {
                        formaPagoInput.value = data.forma_pago;
                    }
                    
                    // Cargar prendas técnicas del logo si existen
                    if (data.prendas_tecnicas && data.prendas_tecnicas.length > 0) {
                        if (typeof cargarLogoPrendasDesdeCotizacion === 'function') {
                            cargarLogoPrendasDesdeCotizacion(data.prendas_tecnicas);
                        }
                    }
                    
                    // GUARDAR ID DEL LOGO COTIZACION para usar después
                    if (esLogo && data.logo) {
                        logoCotizacionId = data.logo.id;
                    }
                    
                    // Cambiar título y alerta dinámicamente
                    const paso3Titulo = document.getElementById('paso3_titulo_logo');
                    const paso3Alerta = document.getElementById('paso3_alerta_logo');
                    const tituloPrendasDinamico = document.getElementById('titulo-prendas-dinamico');
                    
                    if (paso3Titulo && paso3Alerta) {
                        if (esLogo) {
                            paso3Titulo.textContent = 'Pedido de Logo';
                            paso3Alerta.innerHTML = 'ℹ️ Completa la información del logo: descripción, ubicaciones, técnicas y observaciones.';
                        } else {
                            paso3Titulo.textContent = 'Prendas y Cantidades (Editables)';
                            paso3Alerta.innerHTML = 'ℹ️ Puedes editar los campos de cada prenda, cambiar cantidades por talla, o eliminar prendas que no desees incluir en el pedido.';
                        }
                    } else {
                        console.warn('⚠️ No se encontraron los elementos paso3_titulo_logo o paso3_alerta_logo');
                    }
                    
                    // Actualizar el título dinámico junto al círculo del índice 3
                    if (tituloPrendasDinamico) {
                        const seccionPrendas = document.getElementById('seccion-prendas');
                        if (esLogo) {
                            if (seccionPrendas) seccionPrendas.style.display = 'block';
                            tituloPrendasDinamico.textContent = 'Información del Logo';
                        } else if (tipoPedido === 'REFLECTIVO') {
                            if (seccionPrendas) seccionPrendas.style.display = 'block';
                            tituloPrendasDinamico.innerHTML = 'Nuevo Pedido Reflectivo';
                            console.log('✅ Título dinámico actualizado para REFLECTIVO:', tituloPrendasDinamico.textContent);
                        } else if (tipoPedido === 'PRENDA') {
                            if (seccionPrendas) seccionPrendas.style.display = 'block';
                            tituloPrendasDinamico.textContent = 'Prendas';
                            console.log('✅ Título dinámico actualizado:', tituloPrendasDinamico.textContent);
                        } else {
                            if (seccionPrendas) seccionPrendas.style.display = 'block';
                            tituloPrendasDinamico.textContent = 'Prendas Técnicas del Logo';
                            console.log('✅ Título dinámico actualizado:', tituloPrendasDinamico.textContent);
                        }
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
    // RENDERIZAR PRENDAS EDITABLES (REFACTORIZADO)
    // ============================================================
    // NOTA: La lógica de renderizado fue movida a RenderizadorPrendasComponent.js
    // Esta función ahora delega al componente para mantener el código limpio y modular
    
    window.renderizarPrendasEditables = function renderizarPrendasEditables(prendas, logoCotizacion = null, especificacionesCotizacion = null, esReflectivo = false, datosReflectivo = null, esLogo = false, tipoCotizacion = 'P') {
        try {
            // Inicializar componente si no está inicializado
            if (window.RenderizadorPrendasComponent && !window.RenderizadorPrendasComponent.prendasContainer) {
                window.RenderizadorPrendasComponent.init(prendasContainer, prendasEliminadas);
            }
            
            // Delegar al componente
            if (window.RenderizadorPrendasComponent) {
                window.RenderizadorPrendasComponent.renderizar(
                    prendas, 
                    logoCotizacion, 
                    especificacionesCotizacion, 
                    esReflectivo, 
                    datosReflectivo, 
                    esLogo, 
                    tipoCotizacion
                );
            } else {
                console.error('❌ RenderizadorPrendasComponent no está disponible');
                prendasContainer.innerHTML = '<p style="color: #ef4444;">Error: Componente de renderizado no disponible</p>';
            }
        } catch (error) {
            console.error('❌ ERROR en renderizarPrendasEditables:', error);
            prendasContainer.innerHTML = `<p style="color: #ef4444;">Error al renderizar: ${error.message}</p>`;
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

    // ============================================================
    // OCULTAR LOADING Y MOSTRAR SELECT CUANDO TODO ESTÉ CARGADO
    // ============================================================
    
    // Esperar a que todos los componentes estén cargados
    window.addEventListener('load', function() {
        setTimeout(function() {
            // Ocultar loading del select de tipo de pedido
            const loadingDiv = document.getElementById('tipo-pedido-loading');
            const selectElement = document.getElementById('tipo_pedido_nuevo');
            
            if (loadingDiv && selectElement) {
                loadingDiv.style.display = 'none';
                selectElement.style.display = 'block';
                selectElement.disabled = false;
            }
            
            // Ocultar loading de página completa
            const pageLoadingOverlay = document.getElementById('page-loading-overlay');
            if (pageLoadingOverlay) {
                pageLoadingOverlay.classList.add('fade-out');
                setTimeout(function() {
                    pageLoadingOverlay.style.display = 'none';
                }, 300); // Esperar a que termine la animación de fade
            }
        }, 800); // Esperar 800ms para asegurar que todo esté cargado
    });

}); // Cierre del DOMContentLoaded
