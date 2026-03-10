<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gestión de EPPs</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Material Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        .material-symbols-rounded {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .text-pre-wrap {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <span class="material-symbols-rounded text-blue-600" style="font-size: 36px;">engineering</span>
                        Gestión de EPPs
                    </h1>
                    <p class="text-gray-600 mt-1">Administrar Equipos de Protección Personal</p>
                </div>
                <button type="button" onclick="gestorEpps.abrirModalCrear()" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg font-medium flex items-center gap-2 hover:bg-blue-700 transition shadow-md hover:shadow-lg">
                    <span class="material-symbols-rounded">add_circle</span>
                    Nuevo EPP
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="buscar" class="block text-sm font-medium text-gray-700 mb-2">
                        <span class="material-symbols-rounded inline text-base align-middle mr-1">search</span>
                        Buscar
                    </label>
                    <input type="text" id="buscar" placeholder="Buscar por nombre..." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                </div>
                <div class="flex items-end">
                    <button type="button" onclick="gestorEpps.limpiarFiltros()" class="w-full px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition flex items-center justify-center gap-2">
                        <span class="material-symbols-rounded">filter_alt_off</span>
                        Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de EPPs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Header de la tabla -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">
                    Lista de EPPs 
                    <span class="text-sm font-normal text-gray-500 ml-2">(<span id="totalRegistros">0</span> registros)</span>
                </h2>
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Nombre Completo</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider w-32">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaEppsBody" class="divide-y divide-gray-200">
                        <!-- Los datos se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Mostrando <span id="registrosDesde">0</span> a <span id="registrosHasta">0</span> de <span id="registrosTotales">0</span> registros
                    </div>
                    <div class="flex gap-2" id="paginacionEpps">
                        <!-- Se generará dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear EPP -->
    <div id="modalCrearEPP" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" style="display: none;">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
            <div class="bg-blue-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 id="tituloModal" class="text-xl font-bold text-white">Crear Nuevo EPP</h3>
                <button onclick="gestorEpps.cerrarModal()" class="text-white hover:bg-blue-700 p-1 rounded transition">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="p-6">
                <form id="formCrearEPP" onsubmit="gestorEpps.guardarEPP(event)">
                    <input type="hidden" id="epp_id" value="">
                    <div class="space-y-4">
                        <div>
                            <label for="nombre_completo" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre Completo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="nombre_completo" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button type="button" onclick="gestorEpps.cerrarModal()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Eliminación -->
    <div id="modalConfirmacionEliminar" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full mx-4">
            <div class="bg-red-600 px-6 py-6 rounded-t-xl">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                        <span class="material-symbols-rounded text-4xl text-red-600">delete_outline</span>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-white text-center">¿Eliminar EPP?</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-6 text-center">¿Estás seguro de que deseas eliminar este EPP? Esta acción no se puede deshacer.</p>
                <div class="flex gap-3">
                    <button onclick="gestorEpps.cerrarConfirmacionEliminar()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button onclick="gestorEpps.confirmarEliminar()" class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de EPP Duplicado -->
    <div id="modalEppDuplicado" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full mx-4">
            <div class="bg-purple-600 px-6 py-6 rounded-t-xl">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center">
                        <span class="material-symbols-rounded text-4xl text-purple-600">info</span>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-white text-center">EPP Ya Existe</h3>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-4"><strong id="eppDuplicadoNombre"></strong> ya existe en el sistema.</p>
                <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg mb-6">
                    <strong>¿Qué hacer?</strong> Puedes usar este EPP existente en tus pedidos o crear uno con un nombre diferente.
                </div>
                <div class="flex gap-3">
                    <button onclick="gestorEpps.cerrarModalEppDuplicado()" class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition">
                        Crear Otro
                    </button>
                    <button onclick="gestorEpps.cerrarModalEppDuplicado(); gestorEpps.cerrarModal();" class="flex-1 px-4 py-2.5 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 transition">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Éxito -->
    <div id="modalExito" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full mx-4 text-center">
            <div class="bg-green-600 px-6 py-6 rounded-t-xl">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                        <span class="material-symbols-rounded text-4xl text-green-600">check_circle</span>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-white">¡Éxito!</h3>
            </div>
            <div class="p-6">
                <p id="mensajeExito" class="text-gray-700 text-lg mb-6">La operación se completó correctamente</p>
                <button onclick="gestorEpps.cerrarModalExito()" class="w-full px-4 py-2.5 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition">
                    Aceptar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Error -->
    <div id="modalError" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full mx-4 text-center">
            <div class="bg-red-600 px-6 py-6 rounded-t-xl">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                        <span class="material-symbols-rounded text-4xl text-red-600">error</span>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-white">Error</h3>
            </div>
            <div class="p-6">
                <p id="mensajeError" class="text-gray-700 text-lg mb-6">Ocurrió un error al procesar la operación</p>
                <button onclick="gestorEpps.cerrarModalError()" class="w-full px-4 py-2.5 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <script>
    class GestorEpps {
        constructor() {
            this.epps = [];
            this.paginaActual = 1;
            this.totalPorPagina = 20;
            this.totalRegistros = 0;
            this.filtros = {
                buscar: ''
            };
            this.eppAEliminar = null; // Para almacenar el ID del EPP a eliminar
            
            this.init();
        }

        async init() {
            await this.cargarEpps();
            this.configurarEventListeners();
        }

        configurarEventListeners() {
            // Búsqueda en tiempo real
            const buscarInput = document.getElementById('buscar');
            if (buscarInput) {
                buscarInput.addEventListener('input', (e) => {
                    this.filtros.buscar = e.target.value;
                    this.paginaActual = 1;
                    this.cargarEpps();
                });
            }
        }

        async cargarEpps() {
            try {
                console.log('[GestorEpps] Cargando EPPs...');
                
                const params = new URLSearchParams();
                
                if (this.filtros.buscar) {
                    params.append('q', this.filtros.buscar);
                }

                params.append('page', this.paginaActual);
                params.append('per_page', this.totalPorPagina);

                const url = `/api/epp/gestion?${params}`;
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    this.epps = result.data;
                    this.totalRegistros = result.total || this.epps.length;
                    
                    console.log('[GestorEpps] EPPs cargados:', this.epps.length);
                    this.renderizarTabla(this.epps);
                    this.renderizarPaginacion();
                    this.actualizarContadores();
                } else {
                    console.error('[GestorEpps] Error en respuesta:', result);
                    this.renderizarTablaVacia('Error al cargar datos');
                }
            } catch (error) {
                console.error('[GestorEpps] Error en carga:', error);
                this.renderizarTablaVacia('Error de conexión');
            }
        }

        renderizarTabla(epps) {
            const tbody = document.getElementById('tablaEppsBody');
            
            if (epps.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="2" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-400">
                                <span class="material-symbols-rounded text-5xl mb-3">search_off</span>
                                <p class="text-lg font-medium">No se encontraron EPPs</p>
                                <p class="text-sm">Intenta con otros filtros de búsqueda</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = epps.map(epp => {
                const tieneAsociaciones = epp.tiene_asociaciones || false;
                const pedidosAsociados = epp.pedidos_asociados || 0;
                return `
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="text-sm font-semibold text-gray-900">${epp.nombre_completo || '-'}</div>
                        ${epp.marca ? `<div class="text-xs text-gray-500 mt-1">${epp.marca}</div>` : ''}
                        ${tieneAsociaciones ? `<div class="text-xs text-red-600 mt-2 flex items-center gap-1"><span class="material-symbols-rounded text-base">lock</span> ${pedidosAsociados} pedido(s) asociado(s)</div>` : ''}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button 
                                onclick="gestorEpps.editarEpp(${epp.id})" 
                                class="p-2 ${tieneAsociaciones ? 'text-gray-400 cursor-not-allowed' : 'text-blue-600 hover:bg-blue-50'} rounded-lg transition" 
                                title="${tieneAsociaciones ? 'No se puede editar: EPP con pedidos asociados' : 'Editar'}"
                                ${tieneAsociaciones ? 'disabled' : ''}>
                                <span class="material-symbols-rounded text-base">edit</span>
                            </button>
                            <button 
                                onclick="gestorEpps.eliminarEpp(${epp.id})" 
                                class="p-2 ${tieneAsociaciones ? 'text-gray-400 cursor-not-allowed' : 'text-red-600 hover:bg-red-50'} rounded-lg transition" 
                                title="${tieneAsociaciones ? 'No se puede eliminar: EPP con pedidos asociados' : 'Eliminar'}"
                                ${tieneAsociaciones ? 'disabled' : ''}>
                                <span class="material-symbols-rounded text-base">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            `}).join('');
        }

        renderizarPaginacion() {
            const totalPages = Math.ceil(this.totalRegistros / this.totalPorPagina);
            const pagination = document.getElementById('paginacionEpps');
            
            if (totalPages <= 1) {
                pagination.innerHTML = '';
                return;
            }

            let html = '';
            
            // Botón ir al inicio (<<)
            html += `
                <button ${this.paginaActual === 1 ? 'disabled' : ''} 
                    onclick="gestorEpps.cambiarPagina(1)" 
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium ${this.paginaActual === 1 ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50'} transition"
                    title="Primera página">
                    <span class="material-symbols-rounded text-base">keyboard_double_arrow_left</span>
                </button>
            `;
            
            // Botón anterior
            html += `
                <button ${this.paginaActual === 1 ? 'disabled' : ''} 
                    onclick="gestorEpps.cambiarPagina(${this.paginaActual - 1})" 
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium ${this.paginaActual === 1 ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50'} transition"
                    title="Página anterior">
                    <span class="material-symbols-rounded text-base">chevron_left</span>
                </button>
            `;

            // Páginas
            const maxPagesToShow = 5;
            let startPage = Math.max(1, this.paginaActual - Math.floor(maxPagesToShow / 2));
            let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

            if (endPage - startPage < maxPagesToShow - 1) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <button onclick="gestorEpps.cambiarPagina(${i})" 
                        class="px-4 py-2 border ${i === this.paginaActual ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-50'} rounded-lg text-sm font-medium transition">
                        ${i}
                    </button>
                `;
            }

            // Botón siguiente
            html += `
                <button ${this.paginaActual === totalPages ? 'disabled' : ''} 
                    onclick="gestorEpps.cambiarPagina(${this.paginaActual + 1})" 
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium ${this.paginaActual === totalPages ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50'} transition"
                    title="Página siguiente">
                    <span class="material-symbols-rounded text-base">chevron_right</span>
                </button>
            `;
            
            // Botón ir al final (>>)
            html += `
                <button ${this.paginaActual === totalPages ? 'disabled' : ''} 
                    onclick="gestorEpps.cambiarPagina(${totalPages})" 
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium ${this.paginaActual === totalPages ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50'} transition"
                    title="Última página">
                    <span class="material-symbols-rounded text-base">keyboard_double_arrow_right</span>
                </button>
            `;

            pagination.innerHTML = html;
        }

        cambiarPagina(pagina) {
            if (pagina < 1 || pagina > Math.ceil(this.totalRegistros / this.totalPorPagina)) {
                return;
            }
            this.paginaActual = pagina;
            this.cargarEpps();
        }

        actualizarContadores() {
            const inicio = (this.paginaActual - 1) * this.totalPorPagina + 1;
            const fin = Math.min(this.paginaActual * this.totalPorPagina, this.totalRegistros);
            
            document.getElementById('totalRegistros').textContent = this.totalRegistros;
            document.getElementById('registrosDesde').textContent = inicio;
            document.getElementById('registrosHasta').textContent = fin;
            document.getElementById('registrosTotales').textContent = this.totalRegistros;
        }

        renderizarTablaVacia(mensaje = 'No se encontraron EPPs') {
            const tbody = document.getElementById('tablaEppsBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="2" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-400">
                            <span class="material-symbols-rounded text-5xl mb-3">info</span>
                            <p class="text-lg font-medium">${mensaje}</p>
                        </div>
                    </td>
                </tr>
            `;
        }

        limpiarFiltros() {
            document.getElementById('buscar').value = '';
            this.filtros = {
                buscar: ''
            };
            this.paginaActual = 1;
            this.cargarEpps();
        }

        abrirModalCrear() {
            document.getElementById('tituloModal').textContent = 'Crear Nuevo EPP';
            document.getElementById('epp_id').value = '';
            document.getElementById('nombre_completo').value = '';
            document.getElementById('modalCrearEPP').style.display = 'flex';
        }

        cerrarModal() {
            document.getElementById('modalCrearEPP').style.display = 'none';
        }

        mostrarModalExito(mensaje = 'La operación se completó correctamente') {
            document.getElementById('mensajeExito').textContent = mensaje;
            document.getElementById('modalExito').classList.remove('hidden');
        }

        cerrarModalExito() {
            document.getElementById('modalExito').classList.add('hidden');
        }

        mostrarModalError(mensaje = 'Ocurrió un error al procesar la operación') {
            document.getElementById('mensajeError').textContent = mensaje;
            document.getElementById('modalError').classList.remove('hidden');
        }

        cerrarModalError() {
            document.getElementById('modalError').classList.add('hidden');
        }

        async guardarEPP(event) {
            event.preventDefault();
            
            const eppId = document.getElementById('epp_id').value;
            const nombreCompleto = document.getElementById('nombre_completo').value;

            if (!nombreCompleto.trim()) {
                this.mostrarModalError('Por favor, ingresa el nombre completo del EPP');
                return;
            }

            try {
                const isEditing = eppId !== '';
                const url = isEditing ? `/api/epp/${eppId}` : '/api/epp';
                const method = isEditing ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        nombre_completo: nombreCompleto,
                        categoria_id: 1110,
                        activo: true,
                        tipo: 'PRODUCTO'
                    })
                });

                const result = await response.json();

                if (result.success || response.ok) {
                    const mensaje = isEditing ? 'EPP actualizado exitosamente' : 'EPP creado exitosamente';
                    this.mostrarModalExito(mensaje);
                    this.cerrarModal();
                    setTimeout(() => this.cargarEpps(), 1500);
                } else if (result.epp_existente) {
                    // Mostrar modal para EPP duplicado
                    document.getElementById('eppDuplicadoNombre').textContent = result.epp_nombre;
                    document.getElementById('modalEppDuplicado').classList.remove('hidden');
                } else {
                    this.mostrarModalError('Error al guardar el EPP: ' + (result.message || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error guardando EPP:', error);
                this.mostrarModalError('Error de conexión al guardar el EPP');
            }
        }

        cerrarModalEppDuplicado() {
            document.getElementById('modalEppDuplicado').classList.add('hidden');
        }

        async editarEpp(id) {
            try {
                // Cargar datos del EPP
                const response = await fetch(`/api/epp/${id}`);
                const result = await response.json();

                if (result.success && result.data) {
                    const epp = result.data;
                    
                    // Verificar si tiene asociaciones
                    if (epp.tiene_asociaciones) {
                        const pedidosAsociados = epp.pedidos_asociados || 0;
                        const modeloAsociaciones = document.createElement('div');
                        modeloAsociaciones.id = 'modalAsociacionesEdicion';
                        modeloAsociaciones.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                        modeloAsociaciones.innerHTML = `
                            <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full mx-4">
                                <div class="bg-orange-600 px-6 py-6 rounded-t-xl">
                                    <div class="flex justify-center mb-4">
                                        <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center">
                                            <span class="material-symbols-rounded text-4xl text-orange-600">lock</span>
                                        </div>
                                    </div>
                                    <h3 class="text-xl font-bold text-white text-center">No se puede editar</h3>
                                </div>
                                <div class="p-6">
                                    <p class="text-gray-700 mb-4"><strong>${epp.nombre_completo}</strong> no puede ser editado porque está vinculado a ${pedidosAsociados} pedido(s).</p>
                                    <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg mb-6">
                                        <strong>Razón:</strong> Este EPP forma parte de órdenes activas en el sistema. Para mantener la integridad de los datos, no se permite su modificación.
                                    </div>
                                    <button onclick="this.closest('#modalAsociacionesEdicion').remove()" class="w-full px-4 py-2.5 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700 transition">
                                        Entendido
                                    </button>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modeloAsociaciones);
                        return;
                    }
                    
                    // Cambiar título del modal
                    document.getElementById('tituloModal').textContent = 'Editar EPP';
                    
                    // Cargar datos en el formulario
                    document.getElementById('epp_id').value = epp.id;
                    document.getElementById('nombre_completo').value = epp.nombre_completo || '';
                    
                    // Mostrar modal
                    document.getElementById('modalCrearEPP').style.display = 'flex';
                } else {
                    this.mostrarModalError('Error al cargar los datos del EPP');
                }
            } catch (error) {
                console.error('Error cargando EPP:', error);
                this.mostrarModalError('Error de conexión al cargar el EPP');
            }
        }

        async eliminarEpp(id) {
            // Mostrar modal de confirmación en lugar de confirm()
            this.eppAEliminar = id;
            document.getElementById('modalConfirmacionEliminar').classList.remove('hidden');
        }

        cerrarConfirmacionEliminar() {
            document.getElementById('modalConfirmacionEliminar').classList.add('hidden');
            this.eppAEliminar = null;
        }

        async confirmarEliminar() {
            const id = this.eppAEliminar;
            
            if (!id) {
                this.mostrarModalError('Error: No se pudo identificar el EPP a eliminar');
                return;
            }

            try {
                const response = await fetch(`/api/epp/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const result = await response.json();

                // Cerrar modal de confirmación
                this.cerrarConfirmacionEliminar();

                if (result.success || response.ok) {
                    this.mostrarModalExito('EPP eliminado exitosamente');
                    setTimeout(() => this.cargarEpps(), 1500);
                } else if (result.tiene_asociaciones) {
                    // Mostrar modal específico para asociaciones
                    const modeloAsociaciones = document.createElement('div');
                    modeloAsociaciones.id = 'modalAsociaciones';
                    modeloAsociaciones.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                    modeloAsociaciones.innerHTML = `
                        <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full mx-4">
                            <div class="bg-yellow-600 px-6 py-6 rounded-t-xl">
                                <div class="flex justify-center mb-4">
                                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center">
                                        <span class="material-symbols-rounded text-4xl text-yellow-600">warning</span>
                                    </div>
                                </div>
                                <h3 class="text-xl font-bold text-white text-center">No se puede eliminar</h3>
                            </div>
                            <div class="p-6">
                                <p class="text-gray-700 mb-4">${result.message}</p>
                                <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg mb-6">
                                    <strong>Razón:</strong> Este EPP está vinculado a ${result.asociaciones?.pedidos || 'uno o más'} pedido(s) en el sistema.
                                </div>
                                <button onclick="this.closest('#modalAsociaciones').remove()" class="w-full px-4 py-2.5 bg-yellow-600 text-white rounded-lg font-medium hover:bg-yellow-700 transition">
                                    Entendido
                                </button>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modeloAsociaciones);
                } else {
                    this.mostrarModalError(result.message || 'Error desconocido al eliminar el EPP');
                }
            } catch (error) {
                console.error('Error eliminando EPP:', error);
                this.cerrarConfirmacionEliminar();
                this.mostrarModalError('Error de conexión al eliminar el EPP');
            }
        }
    }

    // Iniciar gestor cuando el DOM esté listo
    let gestorEpps;
    document.addEventListener('DOMContentLoaded', () => {
        gestorEpps = new GestorEpps();
        window.gestorEpps = gestorEpps;
    });
    </script>
</body>
</html>
