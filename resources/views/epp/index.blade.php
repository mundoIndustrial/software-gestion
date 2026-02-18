<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de EPPs</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container-fluid {
            max-width: 1400px;
        }
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .table th {
            border-top: none;
        }
        .btn-group .btn {
            border-radius: 0.375rem;
        }
        .pagination .page-link {
            border-radius: 0.375rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Gestión de EPPs</h1>
                <p class="text-muted mb-0">Administrar todos los Equipos de Protección Personal</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearEppModal">
                    <i class="fas fa-plus me-2"></i>Nuevo EPP
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="buscar" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="buscar" placeholder="Buscar por nombre, marca...">
                    </div>
                    <div class="col-md-3">
                        <label for="filtroCategoria" class="form-label">Categoría</label>
                        <select class="form-select" id="filtroCategoria">
                            <option value="">Todas las categorías</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filtroTipo" class="form-label">Tipo</label>
                        <select class="form-select" id="filtroTipo">
                            <option value="">Todos los tipos</option>
                            <option value="PRODUCTO">Producto</option>
                            <option value="SERVICIO">Servicio</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filtroActivo" class="form-label">Estado</label>
                        <select class="form-select" id="filtroActivo">
                            <option value="">Todos</option>
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de EPPs -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaEpps">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre Completo</th>
                                <th>Marca</th>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th>Talla</th>
                                <th>Color</th>
                                <th>Estado</th>
                                <th>Descripción</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaEppsBody">
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <nav aria-label="Paginación de EPPs">
                    <ul class="pagination justify-content-center" id="paginacionEpps">
                        <!-- Se generará dinámicamente -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script de EPPs -->
    <script>
    class GestorEpps {
        constructor() {
            this.epps = [];
            this.categorias = [];
            this.paginaActual = 1;
            this.totalPorPagina = 20;
            this.totalRegistros = 0;
            this.filtros = {
                buscar: '',
                categoria_id: '',
                tipo: '',
                activo: ''
            };
            
            this.init();
        }

        async init() {
            await this.cargarCategorias();
            await this.cargarEpps();
            this.configurarEventListeners();
            this.cargarCategoriasEnSelects();
        }

        configurarEventListeners() {
            // Búsqueda en tiempo real
            document.getElementById('buscar').addEventListener('input', (e) => {
                this.filtros.buscar = e.target.value;
                this.paginaActual = 1;
                this.cargarEpps();
            });

            // Filtros
            document.getElementById('filtroCategoria').addEventListener('change', (e) => {
                this.filtros.categoria_id = e.target.value;
                this.paginaActual = 1;
                this.cargarEpps();
            });

            document.getElementById('filtroTipo').addEventListener('change', (e) => {
                this.filtros.tipo = e.target.value;
                this.paginaActual = 1;
                this.cargarEpps();
            });

            document.getElementById('filtroActivo').addEventListener('change', (e) => {
                this.filtros.activo = e.target.value;
                this.paginaActual = 1;
                this.cargarEpps();
            });
        }

        async cargarCategorias() {
            try {
                const response = await fetch('/api/epp/categorias/simple');
                const result = await response.json();
                
                if (result.success) {
                    this.categorias = result.data;
                }
            } catch (error) {
                console.error('Error cargando categorías:', error);
            }
        }

        cargarCategoriasEnSelects() {
            const selects = [
                document.getElementById('categoria_id'),
                document.getElementById('editar_categoria_id'),
                document.getElementById('filtroCategoria')
            ];

            selects.forEach(select => {
                if (select) {
                    select.innerHTML = '<option value="">Seleccione una categoría</option>';
                    this.categorias.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.nombre || cat.codigo || cat.descripcion || `Categoría ${cat.id}`;
                        select.appendChild(option);
                    });
                }
            });
        }

        async cargarEpps() {
            try {
                console.log('[GestorEpps] Iniciando carga de EPPs');
                
                const params = new URLSearchParams();
                
                if (this.filtros.buscar) {
                    params.append('q', this.filtros.buscar);
                }
                
                if (this.filtros.categoria_id) {
                    params.append('categoria', this.filtros.categoria_id);
                }

                params.append('page', this.paginaActual);
                params.append('per_page', this.totalPorPagina);

                const url = `/api/epp/gestion?${params}`;
                console.log('[GestorEpps] URL de consulta:', url);

                const response = await fetch(url);
                const result = await response.json();
                console.log('[GestorEpps] Response data:', result);
                
                if (result.success) {
                    this.epps = result.data;
                    this.totalRegistros = result.total || this.epps.length;
                    
                    let eppsFiltrados = this.epps;
                    
                    if (this.filtros.tipo) {
                        eppsFiltrados = eppsFiltrados.filter(epp => epp.tipo === this.filtros.tipo);
                    }
                    
                    if (this.filtros.activo !== '') {
                        const activoBool = this.filtros.activo === '1';
                        eppsFiltrados = eppsFiltrados.filter(epp => epp.activo === activoBool);
                    }
                    
                    console.log('[GestorEpps] EPPs filtrados:', eppsFiltrados.length);
                    this.renderizarTabla(eppsFiltrados);
                    this.renderizarPaginacion(eppsFiltrados.length);
                } else {
                    console.error('[GestorEpps] Error en respuesta:', result);
                    this.renderizarTablaVacia('Error al cargar datos');
                }
            } catch (error) {
                console.error('[GestorEpps] Error en carga:', error);
                this.renderizarTablaVacia('Error de conexión');
                this.mostrarDatosPrueba();
            }
        }

        renderizarTabla(epps) {
            const tbody = document.getElementById('tablaEppsBody');
            
            if (epps.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-search fa-2x mb-2"></i>
                                <p>No se encontraron EPPs con los filtros actuales</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = epps.map(epp => `
                <tr>
                    <td>${epp.id}</td>
                    <td>
                        <strong>${epp.nombre_completo || ''}</strong>
                    </td>
                    <td>${epp.marca || '-'}</td>
                    <td>
                        <span class="badge bg-secondary">
                            ${this.getNombreCategoria(epp)}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-${epp.tipo === 'PRODUCTO' ? 'primary' : 'info'}">
                            ${epp.tipo || '-'}
                        </span>
                    </td>
                    <td>${epp.talla || '-'}</td>
                    <td>${epp.color || '-'}</td>
                    <td>
                        <span class="badge bg-${epp.activo ? 'success' : 'danger'}">
                            ${epp.activo ? 'Activo' : 'Inactivo'}
                        </span>
                    </td>
                    <td>
                        <small class="text-muted">
                            ${epp.descripcion ? (epp.descripcion.length > 50 ? epp.descripcion.substring(0, 50) + '...' : epp.descripcion) : '-'}
                        </small>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.gestorEpps.editarEpp(${epp.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="window.gestorEpps.confirmarEliminar(${epp.id}, '${epp.nombre_completo || ''}')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        renderizarPaginacion(totalItems) {
            const totalPages = Math.ceil(totalItems / this.totalPorPagina);
            const pagination = document.getElementById('paginacionEpps');
            
            if (totalPages <= 1) {
                pagination.innerHTML = '';
                return;
            }

            let html = '';
            
            // Botón anterior
            html += `
                <li class="page-item ${this.paginaActual === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="window.gestorEpps.cambiarPagina(${this.paginaActual - 1})">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;

            // Páginas
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= this.paginaActual - 2 && i <= this.paginaActual + 2)) {
                    html += `
                        <li class="page-item ${i === this.paginaActual ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="window.gestorEpps.cambiarPagina(${i})">${i}</a>
                        </li>
                    `;
                } else if (i === this.paginaActual - 3 || i === this.paginaActual + 3) {
                    html += `
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    `;
                }
            }

            // Botón siguiente
            html += `
                <li class="page-item ${this.paginaActual === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="window.gestorEpps.cambiarPagina(${this.paginaActual + 1})">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;

            pagination.innerHTML = html;
        }

        cambiarPagina(pagina) {
            this.paginaActual = pagina;
            this.cargarEpps();
            return false;
        }

        getNombreCategoria(epp) {
            if (!epp.categoria_id) {
                return 'Sin categoría';
            }
            
            if (epp.categoria && epp.categoria.nombre) {
                return epp.categoria.nombre;
            }
            
            const categoria = this.categorias.find(cat => cat.id == epp.categoria_id);
            if (categoria) {
                return categoria.nombre || categoria.codigo || `Cat ${categoria.id}`;
            }
            
            return `Categoría ${epp.categoria_id}`;
        }

        mostrarDatosPrueba() {
            const datosPrueba = [
                {
                    id: 1,
                    nombre_completo: 'ALCOHOL 1000ML',
                    marca: 'Marca Test',
                    categoria_id: 1,
                    tipo: 'PRODUCTO',
                    talla: 'Única',
                    color: 'Transparente',
                    activo: true,
                    descripcion: 'Alcohol desinfectante de 1000ml'
                },
                {
                    id: 2,
                    nombre_completo: 'CASQUETE PORTAVISOR AMARILLO',
                    marca: 'SteelPro',
                    categoria_id: 2,
                    tipo: 'PRODUCTO',
                    talla: 'Única',
                    color: 'Amarillo',
                    activo: true,
                    descripcion: 'Casco de seguridad con portavisor amarillo'
                },
                {
                    id: 3,
                    nombre_completo: 'BOTAS DE SEGURIDAD',
                    marca: 'SafetyBoot',
                    categoria_id: 1,
                    tipo: 'PRODUCTO',
                    talla: '42',
                    color: 'Negro',
                    activo: true,
                    descripcion: 'Botas antideslizantes con puntera de acero'
                }
            ];
            
            this.epps = datosPrueba;
            this.totalRegistros = datosPrueba.length;
            this.renderizarTabla(datosPrueba);
            this.renderizarPaginacion(datosPrueba.length);
            
            console.log('[GestorEpps] Datos de prueba cargados:', datosPrueba.length);
        }

        renderizarTablaVacia(mensaje = 'No se encontraron EPPs') {
            const tbody = document.getElementById('tablaEppsBody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center py-4">
                        <div class="text-muted">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <p>${mensaje}</p>
                            <small>Revisa la consola para más detalles</small>
                        </div>
                    </td>
                </tr>
            `;
        }

        // Métodos CRUD (placeholder)
        editarEpp(id) {
            console.log('Editar EPP:', id);
            // Implementar lógica de edición
        }

        confirmarEliminar(id, nombre) {
            console.log('Eliminar EPP:', id, nombre);
            // Implementar lógica de eliminación
        }
    }

    // Inicialización directa
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[EPP] DOM cargado, iniciando...');
        
        setTimeout(function() {
            window.gestorEpps = new GestorEpps();
            console.log('[EPP] GestorEpps inicializado');
        }, 500);
    });
    </script>
</body>
</html>
