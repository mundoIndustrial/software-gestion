/**
 * ================================================
 * COMPONENTE: PRENDAS EDITABLES
 * Gestión completa de prendas, imágenes, modales y funciones
 * ================================================
 * 
 * Este módulo contiene todas las funciones necesarias para:
 * - Manejo de imágenes de prendas
 * - Actualización de previsualizaciones
 * - Gestión de galerías
 * - Abrir/cerrar modales de prendas
 * - Agregar prendas nuevas
 * - Limpiar formularios
 */

/**
 * Maneja la carga de imágenes para prendas
 * @param {HTMLInputElement} input - Input file con la imagen
 */
function manejarImagenesPrenda(input) {
    if (!input.files || input.files.length === 0) {
        return;
    }
    
    window.imagenesPrendaStorage.agregarImagen(input.files[0])
        .then(() => {
            actualizarPreviewPrenda();
        })
        .catch(err => {
            alert(err.message);
        });
    input.value = '';
}

/**
 * Actualiza el preview de las imágenes de prenda
 */
function actualizarPreviewPrenda() {
    const preview = document.getElementById('nueva-prenda-foto-preview');
    const contador = document.getElementById('nueva-prenda-foto-contador');
    const btn = document.getElementById('nueva-prenda-foto-btn');
    const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
    
    if (imagenes.length === 0) {
        preview.innerHTML = '<div style="text-align: center;"><div class="material-symbols-rounded" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.25rem;">add_photo_alternate</div><div style="font-size: 0.7rem; color: #9ca3af;">Click para agregar</div></div>';
        preview.style.cursor = 'pointer';
        contador.textContent = '';
        btn.style.display = 'block';
        return;
    }
    
    preview.innerHTML = '';
    preview.style.cursor = 'pointer';
    const img = document.createElement('img');
    img.src = imagenes[0].data;
    img.style.cssText = 'width: 100%; height: 100%; object-fit: cover; cursor: pointer;';
    
    preview.appendChild(img);
    
    contador.textContent = imagenes.length === 1 ? '1 foto' : imagenes.length + ' fotos';
    btn.style.display = imagenes.length < 3 ? 'block' : 'none';
}

/**
 * Abre la galería de imágenes si las hay
 */
function abrirGaleriaOSelectorPrenda() {
    const imagenes = window.imagenesPrendaStorage.obtenerImagenes();
    
    if (imagenes.length > 0) {
        // Si hay imágenes, abre la galería
        mostrarGaleriaPrenda(imagenes, 0);
    }
    // Si no hay imágenes, no hace nada (el usuario debe usar el botón "Agregar")
}

/**
 * Abre el selector de archivos para agregar foto a prenda
 */
window.abrirSelectorPrendas = function() {
    const inputFotos = document.getElementById('nueva-prenda-foto-input');
    if (inputFotos) {
        inputFotos.click();
    }
};

/**
 * Configura los eventos del formulario de prenda
 */
function configurarEventosFormulario() {
    // Habilitar/deshabilitar inputs de variaciones
    const mangaCb = document.getElementById('aplica-manga');
    const bolsillosCb = document.getElementById('aplica-bolsillos');
    const brocheCb = document.getElementById('aplica-broche');
    
    // Si no existen los elementos, no hacer nada
    if (!mangaCb || !bolsillosCb || !brocheCb) {
        return;
    }
    
    // Remover listeners anteriores si existen
    if (mangaCb._configured) return;
    
    mangaCb.addEventListener('change', function() {
        const input = document.getElementById('manga-input');
        const obs = document.getElementById('manga-obs');
        if (input) {
            input.disabled = !this.checked;
            input.style.opacity = this.checked ? '1' : '0.5';
        }
        if (obs) {
            obs.disabled = !this.checked;
            obs.style.opacity = this.checked ? '1' : '0.5';
        }
    });
    
    bolsillosCb.addEventListener('change', function() {
        const input = document.getElementById('bolsillos-input');
        if (input) {
            input.disabled = !this.checked;
            input.style.opacity = this.checked ? '1' : '0.5';
        }
    });
    
    brocheCb.addEventListener('change', function() {
        const input = document.getElementById('broche-input');
        const obs = document.getElementById('broche-obs');
        if (input) {
            input.disabled = !this.checked;
            input.style.opacity = this.checked ? '1' : '0.5';
        }
        if (obs) {
            obs.disabled = !this.checked;
            obs.style.opacity = this.checked ? '1' : '0.5';
        }
    });
    
    // Marcar como configurado
    mangaCb._configured = true;
}

/**
 * Abre el modal de agregar prenda nueva
 */
window.abrirModalPrendaNueva = function() {
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        modal.style.display = 'flex';
        limpiarFormularioPrendaNueva();
        configurarEventosFormulario();
        
        // Registrar listener para el preview
        const preview = document.getElementById('nueva-prenda-foto-preview');
        if (preview) {
            // Remover listener anterior si existe
            preview.removeEventListener('click', abrirGaleriaOSelectorPrenda);
            // Registrar nuevo listener
            preview.addEventListener('click', abrirGaleriaOSelectorPrenda);
        }
        
        // Registrar listener para el botón agregar
        const btnAgregar = document.getElementById('nueva-prenda-foto-btn');
        if (btnAgregar) {
            // Remover listener anterior si existe
            btnAgregar.removeEventListener('click', abrirSelectorPrendas);
            // Registrar nuevo listener
            btnAgregar.addEventListener('click', abrirSelectorPrendas);
        }
        
        // ✅ NUEVO: Registrar listeners para botones de género (igual que archivo antiguo)
        const btnDama = document.getElementById('btn-genero-dama');
        const btnCaballero = document.getElementById('btn-genero-caballero');
        
        if (btnDama && typeof abrirModalSeleccionarTallas === 'function') {
            btnDama.onclick = function() {
                abrirModalSeleccionarTallas('dama');
            };
        }
        
        if (btnCaballero && typeof abrirModalSeleccionarTallas === 'function') {
            btnCaballero.onclick = function() {
                abrirModalSeleccionarTallas('caballero');
            };
        }
    }
};

/**
 * Limpia el formulario de prenda nueva
 */
function limpiarFormularioPrendaNueva() {
    document.getElementById('nueva-prenda-nombre').value = '';
    document.getElementById('nueva-prenda-descripcion').value = '';
    document.getElementById('nueva-prenda-color').value = '';
    document.getElementById('nueva-prenda-tela').value = '';
    document.getElementById('nueva-prenda-referencia').value = '';
    
    // Limpiar telas agregadas
    window.telasAgregadas = [];
    actualizarTablaTelas();
    
    // Limpiar storage de imágenes
    if (window.imagenesPrendaStorage) {
        window.imagenesPrendaStorage.limpiar();
    }
    if (window.imagenesTelaStorage) {
        window.imagenesTelaStorage.limpiar();
    }
    
    // Reset tallas seleccionadas SOLO si es la primera vez que se abre el modal
    // Si ya existen tallas seleccionadas, las mantenemos
    if (!window.tallasSeleccionadas) {
        window.tallasSeleccionadas = {
            dama: { tallas: [], tipo: null },
            caballero: { tallas: [], tipo: null }
        };
    }
    
    // Reset botones
    const btnDama = document.getElementById('btn-genero-dama');
    const btnCaballero = document.getElementById('btn-genero-caballero');
    
    btnDama.dataset.selected = 'false';
    btnDama.style.borderColor = '#d1d5db';
    btnDama.style.background = 'white';
    document.getElementById('check-dama').style.display = 'none';
    
    btnCaballero.dataset.selected = 'false';
    btnCaballero.style.borderColor = '#d1d5db';
    btnCaballero.style.background = 'white';
    document.getElementById('check-caballero').style.display = 'none';
    
    // Limpiar tarjetas
    document.getElementById('tarjetas-generos-container').innerHTML = '';
    
    // Reset total
    document.getElementById('total-prendas').textContent = '0';
    
    // Limpiar variaciones
    document.querySelectorAll('#modal-agregar-prenda-nueva input[type="checkbox"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('#manga-input, #manga-obs, #bolsillos-input, #broche-input, #broche-obs').forEach(input => {
        if (input) {
            input.value = '';
            input.disabled = true;
            input.style.opacity = '0.5';
        }
    });
    
    // Reset origen
    document.getElementById('nueva-prenda-origen-select').value = 'bodega';
}

/**
 * Cierra el modal de prenda nueva
 */
window.cerrarModalPrendaNueva = function() {
    console.log('🔐 [CERRAR MODAL PRENDA] Cerrando modal de prenda nueva');
    console.log('📊 [CERRAR MODAL PRENDA] Estado de tallas antes de limpiar:', JSON.stringify(window.tallasSeleccionadas));
    
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        modal.style.display = 'none';
    }
    
    // Limpiar botones de género
    const btnDama = document.getElementById('btn-genero-dama');
    const btnCaballero = document.getElementById('btn-genero-caballero');
    if (btnDama) btnDama.setAttribute('data-selected', 'false');
    if (btnCaballero) btnCaballero.setAttribute('data-selected', 'false');
    
    // Limpiar contenedor de géneros
    const generosContainer = document.getElementById('tarjetas-generos-container');
    if (generosContainer) generosContainer.innerHTML = '';
    
    // Limpiar imágenes de tela almacenadas
    window.imagenesTelaModalNueva = [];
    // Limpiar preview de tela
    const previewTela = document.getElementById('nueva-prenda-tela-preview');
    if (previewTela) {
        previewTela.innerHTML = '';
    }
    // Limpiar input file
    const imgInput = document.getElementById('nueva-prenda-tela-img-input');
    if (imgInput) {
        imgInput.value = '';
    }
    // Limpiar imágenes de prenda almacenadas
    window.imagenesPrendaStorage = [];
    
    console.log('✅ [CERRAR MODAL PRENDA] Limpieza completada, tallas preservadas');
    // Limpiar preview de prenda
    const previewPrenda = document.getElementById('nueva-prenda-foto-preview');
    if (previewPrenda) {
        previewPrenda.innerHTML = '<div style="text-align: center;"><div class="material-symbols-rounded" style="font-size: 2rem; color: #9ca3af; margin-bottom: 0.25rem;">add_photo_alternate</div><div style="font-size: 0.7rem; color: #9ca3af;">Click para agregar</div></div>';
    }
    const contadorPrenda = document.getElementById('nueva-prenda-foto-contador');
    if (contadorPrenda) {
        contadorPrenda.textContent = '';
    }
    const btnPrenda = document.getElementById('nueva-prenda-foto-btn');
    if (btnPrenda) {
        btnPrenda.style.display = 'none';
    }
    
    // Limpiar telas agregadas
    window.telasAgregadas = [];
    actualizarTablaTelas();
    
    console.log('📊 [CERRAR MODAL PRENDA] Cantidades preservadas:', JSON.stringify(window.cantidadesTallas));
};

/**
 * Captura las cantidades de prenda desde la tabla
 */
window.capturarCantidadesPrenda = function() {
    if (!window.cantidadesPrenda) {
        window.cantidadesPrenda = {};
    }
    
    // Buscar en la tabla de tallas del modal principal
    const tbody = document.querySelector('#tbody-tallas-principal tbody') || document.querySelector('table tbody');
    if (!tbody) return;
    
    const filas = tbody.querySelectorAll('tr');
    filas.forEach(fila => {
        const celdas = fila.querySelectorAll('td');
        if (celdas.length >= 3) {
            const talla = celdas[0].textContent.trim();
            const genero = celdas[1].textContent.trim().toLowerCase();
            const inputCantidad = celdas[2].querySelector('input[type="number"]');
            
            if (inputCantidad && talla && genero) {
                const cantidad = parseInt(inputCantidad.value) || 0;
                window.cantidadesPrenda[`${genero}-${talla}`] = cantidad;
            }
        }
    });
};

/**
 * Agrega una prenda nueva al pedido
 */
window.agregarPrendaNueva = function() {
    console.log('⭐ [AGREGAR PRENDA] Iniciando agregar prenda');
    console.log('📊 [AGREGAR PRENDA] Tallas seleccionadas ANTES de agregar:', JSON.stringify(window.tallasSeleccionadas));
    
    const nombre = document.getElementById('nueva-prenda-nombre').value.trim().toUpperCase();
    const descripcion = document.getElementById('nueva-prenda-descripcion').value.trim();
    const origen = document.getElementById('nueva-prenda-origen-select').value;
    
    if (!nombre) {
        alert('Por favor ingresa el nombre de la prenda');
        return;
    }
    
    // Verificar que hay telas agregadas
    if (window.telasAgregadas.length === 0) {
        alert('Por favor agrega al menos una tela');
        return;
    }
    
    // Obtener tallas y cantidades del nuevo sistema - Formato: { genero: { talla: cantidad } }
    const tallasObj = {};
    let cantidadTotal = 0;
    
    document.querySelectorAll('#tarjetas-generos-container input[type="number"]').forEach(input => {
        const cantidad = parseInt(input.value) || 0;
        if (cantidad > 0) {
            const genero = input.dataset.genero;
            const talla = input.dataset.talla;
            
            // Inicializar género si no existe
            if (!tallasObj[genero]) {
                tallasObj[genero] = {};
            }
            
            tallasObj[genero][talla] = cantidad;
            cantidadTotal += cantidad;
        }
    });
    
    if (cantidadTotal === 0) {
        alert('Por favor ingresa al menos una cantidad en las tallas');
        return;
    }
    
    // Convertir a array para compatibilidad con renderizado
    const tallas = [];
    Object.keys(tallasObj).forEach(genero => {
        Object.keys(tallasObj[genero]).forEach(talla => {
            tallas.push({
                genero: genero,
                talla: talla,
                cantidad: tallasObj[genero][talla]
            });
        });
    });
    
    console.log('📋 [AGREGAR PRENDA] Tallas para agregar:', tallas);
    
    // Obtener variaciones
    const variaciones = {};
    if (document.getElementById('aplica-manga').checked) {
        variaciones.manga = {
            tipo: document.getElementById('manga-input').value.trim(),
            observacion: document.getElementById('manga-obs')?.value.trim() || ''
        };
    }
    if (document.getElementById('aplica-bolsillos').checked) {
        variaciones.bolsillos = {
            tipo: document.getElementById('bolsillos-input').value.trim(),
            observacion: document.getElementById('bolsillos-obs')?.value.trim() || ''
        };
    }
    if (document.getElementById('aplica-broche').checked) {
        variaciones.broche = {
            tipo: document.getElementById('broche-input').value.trim(),
            observacion: document.getElementById('broche-obs')?.value.trim() || ''
        };
    }
    
    // Obtener procesos seleccionados
    const procesos = [];
    document.querySelectorAll('input[name="nueva-prenda-procesos"]:checked').forEach(cb => {
        procesos.push(cb.value);
    });
    
    console.log('➕ [AGREGAR PRENDA] Agregando prenda nueva:', { nombre, cantidadTotal, origen, procesos, tallas, variaciones });
    
    // Estructura completa de la prenda - con MÚLTIPLES TELAS
    const prendaData = {
        nombre: nombre,
        descripcion: descripcion,
        telas: window.telasAgregadas,  // Array de {tela, color, referencia}
        cantidad: cantidadTotal,
        tallas: tallas,
        variaciones: variaciones,
        imagenes: window.imagenesPrendaStorage.obtenerImagenes()  // Guardar imágenes
    };
    
    // REGLA DE SPLIT: Si tiene procesos, crear 2 ítems
    if (procesos.length > 0) {
        // ÍTEM 1: Prenda BASE (sin procesos)
        window.itemsPedido.push({
            tipo: 'nuevo',
            prenda: prendaData,
            origen: origen,
            procesos: [],
            es_proceso: false,
            tallas: tallas,  // Pasar tallas al nivel del ítem
            variaciones: variaciones,  // Pasar variaciones al nivel del ítem
            imagenes: window.imagenesPrendaStorage.obtenerImagenes()  // Pasar imágenes al nivel del ítem
        });
        
        // ÍTEM 2: Prenda PROCESO (con procesos)
        window.itemsPedido.push({
            tipo: 'nuevo',
            prenda: prendaData,
            origen: origen,
            procesos: procesos,
            es_proceso: true,
            tallas: tallas,  // Pasar tallas al nivel del ítem
            variaciones: variaciones,  // Pasar variaciones al nivel del ítem
            imagenes: window.imagenesPrendaStorage.obtenerImagenes()  // Pasar imágenes al nivel del ítem
        });
        
        console.log(`✅ [AGREGAR PRENDA] Prenda "${nombre}" agregada como 2 ítems (BASE + PROCESO)`);
    } else {
        // Sin procesos: 1 solo ítem
        window.itemsPedido.push({
            tipo: 'nuevo',
            prenda: prendaData,
            origen: origen,
            procesos: [],
            es_proceso: false,
            tallas: tallas,  // Pasar tallas al nivel del ítem
            variaciones: variaciones,  // Pasar variaciones al nivel del ítem
            imagenes: window.imagenesPrendaStorage.obtenerImagenes()  // Pasar imágenes al nivel del ítem
        });
        
        console.log(`✅ [AGREGAR PRENDA] Prenda "${nombre}" agregada como 1 ítem (sin procesos)`);
    }
    
    // Actualizar vista
    window.actualizarVistaItems();
    
    // ✅ Limpiar tallas DESPUÉS de confirmar la prenda
    console.log('🧹 [AGREGAR PRENDA] Limpiando tallas después de confirmar prenda');
    window.tallasSeleccionadas = {
        dama: { tallas: [], tipo: null },
        caballero: { tallas: [], tipo: null }
    };
    console.log('📊 [AGREGAR PRENDA] Tallas DESPUÉS de limpiar:', JSON.stringify(window.tallasSeleccionadas));
    
    // ✅ Limpiar cantidades también
    console.log('🧹 [AGREGAR PRENDA] Limpiando cantidades');
    window.cantidadesTallas = {};
    console.log('📊 [AGREGAR PRENDA] Cantidades DESPUÉS de limpiar:', JSON.stringify(window.cantidadesTallas));
    
    // Cerrar modal
    console.log('🔐 [AGREGAR PRENDA] Cerrando modal');
    window.cerrarModalPrendaNueva();
};
