// SISTEMA DE FILTROS TIPO EMBUDO PARA COTIZACIONES

class FiltroEmbudo {
    constructor() {
        console.log('🚀 Inicializando FiltroEmbudo...');
        this.filtrosActivos = {};
        this.tablaActual = 'todas';
        this.valoresFiltro = {};
        this.init();
        console.log(' FiltroEmbudo inicializado');
        this.cargarValoresFiltro();
    }

    init() {
        // Cerrar modal al hacer click fuera
        document.addEventListener('click', (e) => {
            const modales = document.querySelectorAll('.filter-modal.active');
            modales.forEach(modal => {
                if (e.target === modal) {
                    this.cerrarModal(modal.id);
                }
            });
        });

        // Cerrar modal con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modales = document.querySelectorAll('.filter-modal.active');
                modales.forEach(modal => {
                    this.cerrarModal(modal.id);
                });
            }
        });
    }

    // Cargar valores únicos desde la BD
    cargarValoresFiltro() {
        console.log('🔄 Iniciando carga de valores de filtro...');
        const url = window.FILTER_VALUES_URL || '/asesores/cotizaciones/filtros/valores';
        console.log('📡 Usando URL:', url);
        fetch(url)
            .then(response => {
                console.log('📡 Respuesta recibida:', response.status);
                console.log('📡 Content-Type:', response.headers.get('content-type'));
                return response.text();
            })
            .then(text => {
                console.log(' Respuesta raw:', text);
                const data = JSON.parse(text);
                console.log(' Valores de filtro cargados:', data);
                console.log(' Tipo de datos:', typeof data);
                console.log(' Es array:', Array.isArray(data));
                console.log(' Fechas:', data.fechas?.length ?? 0);
                console.log(' Códigos:', data.codigos?.length ?? 0);
                console.log(' Clientes:', data.clientes?.length ?? 0);
                console.log(' Tipos:', data.tipos?.length ?? 0);
                console.log(' Estados:', data.estados?.length ?? 0);
                this.valoresFiltro = data;
                this.poblarSelectores();
            })
            .catch(error => {
                console.error(' Error al cargar valores de filtro:', error);
            });
    }

    // Poblar los checkboxes con valores de la BD
    poblarSelectores() {
        // Poblar Fecha
        this.poblarCheckboxes('fecha', this.valoresFiltro.fechas);

        // Poblar Código
        this.poblarCheckboxes('codigo', this.valoresFiltro.codigos);

        // Poblar Cliente
        this.poblarCheckboxes('cliente', this.valoresFiltro.clientes);

        // Poblar Asesor (para supervisor-asesores)
        this.poblarCheckboxes('asesor', this.valoresFiltro.asesores);

        // Poblar Tipo
        this.poblarCheckboxes('tipo', this.valoresFiltro.tipos);

        // Poblar Estado
        this.poblarCheckboxes('estado', this.valoresFiltro.estados);
    }

    // Método auxiliar para poblar checkboxes
    poblarCheckboxes(columna, valores) {
        const container = document.querySelector(`#filter-modal-${columna} .filter-checkbox-group`);
        if (!container || !valores) return;

        // Limpiar checkboxes existentes
        container.innerHTML = '';

        // Filtrar valores null/undefined
        const valoresValidos = valores.filter(valor => valor !== null && valor !== undefined && valor !== '');

        // Agregar checkbox para cada valor
        valoresValidos.forEach(valor => {
            const div = document.createElement('div');
            div.className = 'filter-checkbox';
            div.setAttribute('data-valor', String(valor).toLowerCase());

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.value = valor;
            checkbox.id = `checkbox-${columna}-${valor}`;

            const label = document.createElement('label');
            label.htmlFor = `checkbox-${columna}-${valor}`;
            label.textContent = valor;

            div.appendChild(checkbox);
            div.appendChild(label);
            container.appendChild(div);
        });

        // Agregar buscador si hay más de 5 valores
        if (valoresValidos.length > 5) {
            this.agregarBuscador(columna);
        }
    }

    // Agregar buscador al modal
    agregarBuscador(columna) {
        const modal = document.getElementById(`filter-modal-${columna}`);
        if (!modal) return;

        // Verificar si ya existe un buscador
        if (modal.querySelector('.filter-search-box')) return;

        // Crear contenedor del buscador
        const searchBox = document.createElement('div');
        searchBox.className = 'filter-search-box';

        // Crear input de búsqueda
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'filter-search-input';
        input.placeholder = ' Buscar...';
        input.setAttribute('data-columna', columna);

        // Evento de búsqueda
        input.addEventListener('keyup', (e) => {
            const termino = e.target.value.toLowerCase();
            const container = modal.querySelector('.filter-checkbox-group');
            const checkboxes = container.querySelectorAll('.filter-checkbox');

            checkboxes.forEach(checkbox => {
                const valor = checkbox.getAttribute('data-valor');
                if (valor.includes(termino)) {
                    checkbox.style.display = '';
                } else {
                    checkbox.style.display = 'none';
                }
            });
        });

        searchBox.appendChild(input);

        // Insertar buscador antes del contenedor de checkboxes
        const container = modal.querySelector('.filter-checkbox-group');
        container.parentElement.insertBefore(searchBox, container);
    }

    // Abrir modal de filtro
    abrirModal(columna) {
        console.log('🔓 Abriendo modal para columna:', columna);
        const modalId = `filter-modal-${columna}`;
        const modal = document.getElementById(modalId);
        console.log('📍 Modal encontrado:', !!modal, 'ID:', modalId);
        if (modal) {
            console.log(' Agregando clase active al modal');
            modal.classList.add('active');
            // Enfocar el primer input
            const input = modal.querySelector('input, select');
            console.log(' Input encontrado:', !!input);
            if (input) {
                setTimeout(() => input.focus(), 100);
            }
        } else {
            console.error(' Modal no encontrado para:', modalId);
        }
    }

    // Cerrar modal de filtro
    cerrarModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    }

    // Aplicar filtro
    aplicarFiltro(columna, valor, tipo = 'text') {
        if (!valor || valor.trim() === '') {
            delete this.filtrosActivos[columna];
        } else {
            this.filtrosActivos[columna] = {
                valor: valor.trim(),
                tipo: tipo
            };
        }

        // Actualizar botón de filtro
        this.actualizarBotonFiltro(columna);

        // Aplicar filtro a la tabla
        this.filtrarTabla();

        // Cerrar modal
        this.cerrarModal(`filter-modal-${columna}`);
    }

    // Limpiar filtro de una columna
    limpiarFiltro(columna) {
        delete this.filtrosActivos[columna];
        
        // Limpiar inputs del modal
        const modal = document.getElementById(`filter-modal-${columna}`);
        if (modal) {
            const inputs = modal.querySelectorAll('input[type="text"], select');
            inputs.forEach(input => {
                if (input.type === 'checkbox') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
        }

        // Actualizar botón
        this.actualizarBotonFiltro(columna);

        // Aplicar filtro
        this.filtrarTabla();
    }

    // Limpiar todos los filtros
    limpiarTodosFiltros() {
        this.filtrosActivos = {};

        // Limpiar todos los inputs
        document.querySelectorAll('.filter-input, .filter-select').forEach(input => {
            if (input.type === 'checkbox') {
                input.checked = false;
            } else {
                input.value = '';
            }
        });

        // Actualizar todos los botones
        document.querySelectorAll('.filter-funnel-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Mostrar todas las filas
        this.filtrarTabla();
    }

    // Actualizar estado del botón de filtro
    actualizarBotonFiltro(columna) {
        const btn = document.querySelector(`[data-filter-column="${columna}"]`);
        if (btn) {
            if (this.filtrosActivos[columna]) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        }
    }

    // Filtrar tabla
    filtrarTabla() {
        const tabla = document.querySelector(`#vista-tabla-${this.tablaActual} table`);
        if (!tabla) return;

        const filas = tabla.querySelectorAll('tbody tr');
        let filasVisibles = 0;

        filas.forEach(fila => {
            let mostrar = true;

            // Verificar cada filtro activo
            for (const [columna, filtro] of Object.entries(this.filtrosActivos)) {
                const celda = fila.querySelector(`[data-filter-column="${columna}"]`);
                if (!celda) continue;

                const texto = celda.textContent.toLowerCase().trim();

                if (filtro.tipo === 'text') {
                    // Búsqueda parcial
                    const valor = filtro.valor.toLowerCase();
                    if (!texto.includes(valor)) {
                        mostrar = false;
                        break;
                    }
                } else if (filtro.tipo === 'exact') {
                    // Búsqueda exacta (un valor)
                    const valor = filtro.valor.toLowerCase();
                    if (texto !== valor) {
                        mostrar = false;
                        break;
                    }
                } else if (filtro.tipo === 'multiple') {
                    // Búsqueda múltiple (varios valores)
                    const valoresLower = filtro.valor.map(v => v.toLowerCase());
                    if (!valoresLower.includes(texto)) {
                        mostrar = false;
                        break;
                    }
                }
            }

            fila.style.display = mostrar ? '' : 'none';
            if (mostrar) filasVisibles++;
        });

        // Mostrar mensaje si no hay resultados
        this.mostrarMensajeResultados(tabla, filasVisibles);
    }

    // Alias para filtrarTabla (para compatibilidad)
    filtrarTablaMultiple() {
        this.filtrarTabla();
    }

    // Mostrar mensaje de resultados
    mostrarMensajeResultados(tabla, cantidad) {
        let mensajeDiv = tabla.parentElement.querySelector('.filter-no-results');
        
        if (cantidad === 0) {
            if (!mensajeDiv) {
                mensajeDiv = document.createElement('div');
                mensajeDiv.className = 'filter-no-results';
                mensajeDiv.style.cssText = `
                    background: #fef3c7;
                    border: 1px solid #fcd34d;
                    border-radius: 6px;
                    padding: 16px;
                    text-align: center;
                    color: #92400e;
                    margin-top: 12px;
                    font-weight: 500;
                `;
                tabla.parentElement.appendChild(mensajeDiv);
            }
            mensajeDiv.textContent = ' No se encontraron resultados con los filtros aplicados';
        } else {
            if (mensajeDiv) {
                mensajeDiv.remove();
            }
        }
    }

    // Cambiar tabla activa
    cambiarTabla(nombreTabla) {
        this.tablaActual = nombreTabla;
        this.limpiarTodosFiltros();
    }

    // Exportar filtros a URL
    exportarFiltrosURL() {
        const params = new URLSearchParams();
        for (const [columna, filtro] of Object.entries(this.filtrosActivos)) {
            params.append(`filter_${columna}`, filtro.valor);
        }
        return params.toString();
    }

    // Importar filtros desde URL
    importarFiltrosURL() {
        const params = new URLSearchParams(window.location.search);
        params.forEach((valor, clave) => {
            if (clave.startsWith('filter_')) {
                const columna = clave.replace('filter_', '');
                this.aplicarFiltro(columna, valor);
            }
        });
    }
}

// Instancia global
let filtroEmbudo = null;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    filtroEmbudo = new FiltroEmbudo();
    filtroEmbudo.importarFiltrosURL();
});

// Funciones globales para usar en HTML

function abrirFiltro(columna) {
    console.log(' abrirFiltro() llamado con columna:', columna);
    console.log(' filtroEmbudo existe:', !!filtroEmbudo);
    if (filtroEmbudo) {
        console.log(' Llamando a abrirModal()');
        filtroEmbudo.abrirModal(columna);
    } else {
        console.error(' filtroEmbudo no está inicializado');
    }
}

function cerrarFiltro(columna) {
    if (filtroEmbudo) {
        filtroEmbudo.cerrarModal(`filter-modal-${columna}`);
    }
}

function aplicarFiltroColumna(columna) {
    if (!filtroEmbudo) return;

    const modal = document.getElementById(`filter-modal-${columna}`);
    if (!modal) return;

    // Obtener todos los checkboxes marcados
    const checkboxes = modal.querySelectorAll('input[type="checkbox"]:checked');
    const valoresSeleccionados = Array.from(checkboxes).map(cb => cb.value);

    if (valoresSeleccionados.length === 0) {
        // Si no hay nada seleccionado, limpiar el filtro
        filtroEmbudo.limpiarFiltro(columna);
    } else {
        // Guardar los valores seleccionados
        filtroEmbudo.filtrosActivos[columna] = {
            valor: valoresSeleccionados,
            tipo: 'multiple'
        };

        // Actualizar botón de filtro
        filtroEmbudo.actualizarBotonFiltro(columna);

        // Aplicar filtro a la tabla
        filtroEmbudo.filtrarTablaMultiple();

        // Cerrar modal
        filtroEmbudo.cerrarModal(`filter-modal-${columna}`);
    }
}

function limpiarFiltroColumna(columna) {
    if (filtroEmbudo) {
        filtroEmbudo.limpiarFiltro(columna);
    }
}

function limpiarTodosFiltros() {
    if (filtroEmbudo) {
        filtroEmbudo.limpiarTodosFiltros();
    }
}

function cambiarTablaFiltro(nombreTabla) {
    if (filtroEmbudo) {
        filtroEmbudo.cambiarTabla(nombreTabla);
    }
}

function seleccionarTodos(columna) {
    const modal = document.getElementById(`filter-modal-${columna}`);
    if (modal) {
        const checkboxes = modal.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = true);
    }
}

function deseleccionarTodos(columna) {
    const modal = document.getElementById(`filter-modal-${columna}`);
    if (modal) {
        const checkboxes = modal.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = false);
    }
}

// Permitir Enter en inputs de filtro
document.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && e.target.classList.contains('filter-input')) {
        const modal = e.target.closest('.filter-modal');
        if (modal) {
            const columna = modal.id.replace('filter-modal-', '');
            aplicarFiltroColumna(columna, 'text');
        }
    }
});
