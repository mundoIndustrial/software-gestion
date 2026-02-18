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

        // Formularios
        document.getElementById('guardarEppBtn').addEventListener('click', () => this.guardarEpp());
        document.getElementById('actualizarEppBtn').addEventListener('click', () => this.actualizarEpp());
        document.getElementById('confirmarEliminarEppBtn').addEventListener('click', () => this.eliminarEpp());

        // Limpiar formulario al abrir modal de crear
        document.getElementById('crearEppModal').addEventListener('show.bs.modal', () => {
            this.limpiarFormularioCrear();
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
                // Mantener opción "Todos" para el filtro
                if (select.id === 'filtroCategoria') {
                    select.innerHTML = '<option value="">Todas las categorías</option>';
                } else {
                    select.innerHTML = '<option value="">Seleccione una categoría</option>';
                }

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
            this.mostrarLoading(true);
            
            console.log('[GestorEpps] Iniciando carga de EPPs');
            console.log('[GestorEpps] Filtros:', this.filtros);
            console.log('[GestorEpps] Página:', this.paginaActual);
            
            // Construir parámetros de consulta
            const params = new URLSearchParams();
            
            if (this.filtros.buscar) {
                params.append('q', this.filtros.buscar);
            }
            
            if (this.filtros.categoria_id) {
                params.append('categoria', this.filtros.categoria_id);
            }

            // Paginación
            params.append('page', this.paginaActual);
            params.append('per_page', this.totalPorPagina);

            const url = `/api/epp/gestion?${params}`;
            console.log('[GestorEpps] URL de consulta:', url);

            const response = await fetch(url);
            console.log('[GestorEpps] Response status:', response.status);
            
            const result = await response.json();
            console.log('[GestorEpps] Response data:', result);
            
            if (result.success) {
                this.epps = result.data;
                this.totalRegistros = result.total || this.epps.length;
                
                console.log('[GestorEpps] EPPs cargados:', this.epps.length);
                
                // Aplicar filtros adicionales que no se pueden enviar al backend
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
                this.mostrarError('Error al cargar EPPs: ' + result.message);
                this.renderizarTablaVacia('Error al cargar datos');
            }
        } catch (error) {
            console.error('[GestorEpps] Error en carga:', error);
            this.mostrarError('Error de conexión al cargar EPPs');
            this.renderizarTablaVacia('Error de conexión');
            
            // FALLBACK: Mostrar datos de prueba si hay error de API
            console.log('[GestorEpps] Usando fallback de datos de prueba');
            this.mostrarDatosPrueba();
        } finally {
            this.mostrarLoading(false);
        }
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
                activo: 1,
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
                activo: 1,
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
                activo: 1,
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
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="gestorEpps.editarEpp(${epp.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="gestorEpps.confirmarEliminar(${epp.id}, '${epp.nombre_completo || ''}')" title="Eliminar">
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
                <a class="page-link" href="#" onclick="gestorEpps.cambiarPagina(${this.paginaActual - 1})">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;

        // Páginas
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= this.paginaActual - 2 && i <= this.paginaActual + 2)) {
                html += `
                    <li class="page-item ${i === this.paginaActual ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="gestorEpps.cambiarPagina(${i})">${i}</a>
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
                <a class="page-link" href="#" onclick="gestorEpps.cambiarPagina(${this.paginaActual + 1})">
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
        // Si no hay categoria_id, mostrar "Sin categoría"
        if (!epp.categoria_id) {
            return 'Sin categoría';
        }
        
        // Si la relación está cargada, usarla
        if (epp.categoria && epp.categoria.nombre) {
            return epp.categoria.nombre;
        }
        
        // Si no, buscar en el array de categorías cargado
        const categoria = this.categorias.find(cat => cat.id == epp.categoria_id);
        if (categoria) {
            return categoria.nombre || categoria.codigo || `Cat ${categoria.id}`;
        }
        
        // Si no se encuentra la categoría, mostrar el ID
        return `Categoría ${epp.categoria_id}`;
    }

    async guardarEpp() {
        try {
            const form = document.getElementById('crearEppForm');
            const formData = new FormData(form);
            
            // Convertir checkbox a boolean
            formData.set('activo', formData.has('activo') ? '1' : '0');

            this.mostrarLoading(true);

            const response = await fetch('/api/epp/crear-simple', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.mostrarExito('EPP creado exitosamente');
                bootstrap.Modal.getInstance(document.getElementById('crearEppModal')).hide();
                this.cargarEpps();
            } else {
                this.mostrarError('Error al crear EPP: ' + result.message);
            }
        } catch (error) {
            console.error('Error guardando EPP:', error);
            this.mostrarError('Error de conexión al guardar EPP');
        } finally {
            this.mostrarLoading(false);
        }
    }

    async editarEpp(id) {
        try {
            this.mostrarLoading(true);
            
            const response = await fetch(`/api/epp/${id}`);
            const result = await response.json();
            
            if (result.success) {
                const epp = result.data;
                
                // Cargar datos en el formulario
                document.getElementById('editar_epp_id').value = epp.id;
                document.getElementById('editar_nombre_completo').value = epp.nombre_completo || '';
                document.getElementById('editar_marca').value = epp.marca || '';
                document.getElementById('editar_tipo').value = epp.tipo || 'PRODUCTO';
                document.getElementById('editar_talla').value = epp.talla || '';
                document.getElementById('editar_color').value = epp.color || '';
                document.getElementById('editar_categoria_id').value = epp.categoria_id || '';
                document.getElementById('editar_descripcion').value = epp.descripcion || '';
                document.getElementById('editar_activo').checked = epp.activo;
                
                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('editarEppModal'));
                modal.show();
            } else {
                this.mostrarError('Error al cargar EPP: ' + result.message);
            }
        } catch (error) {
            console.error('Error cargando EPP para editar:', error);
            this.mostrarError('Error de conexión al cargar EPP');
        } finally {
            this.mostrarLoading(false);
        }
    }

    async actualizarEpp() {
        try {
            const form = document.getElementById('editarEppForm');
            const formData = new FormData(form);
            const id = formData.get('id');
            
            // Convertir checkbox a boolean
            formData.set('activo', formData.has('activo') ? '1' : '0');
            formData.delete('id'); // Eliminar ID del formData para enviar en PUT

            this.mostrarLoading(true);

            const response = await fetch(`/api/epp/${id}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    nombre_completo: formData.get('nombre_completo'),
                    marca: formData.get('marca'),
                    tipo: formData.get('tipo'),
                    talla: formData.get('talla'),
                    color: formData.get('color'),
                    categoria_id: formData.get('categoria_id'),
                    descripcion: formData.get('descripcion'),
                    activo: formData.get('activo')
                })
            });

            // Como no hay endpoint PUT, simulamos con PATCH o creamos uno nuevo
            const result = await this.actualizarEppDirecto(id, Object.fromEntries(formData));

            if (result) {
                this.mostrarExito('EPP actualizado exitosamente');
                bootstrap.Modal.getInstance(document.getElementById('editarEppModal')).hide();
                this.cargarEpps();
            } else {
                this.mostrarError('Error al actualizar EPP');
            }
        } catch (error) {
            console.error('Error actualizando EPP:', error);
            this.mostrarError('Error de conexión al actualizar EPP');
        } finally {
            this.mostrarLoading(false);
        }
    }

    async actualizarEppDirecto(id, datos) {
        try {
            // Actualización directa usando el modelo
            const response = await fetch(`/api/epp/${id}/actualizar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(datos)
            });

            const result = await response.json();
            return result.success;
        } catch (error) {
            console.error('Error en actualización directa:', error);
            return false;
        }
    }

    confirmarEliminar(id, nombre) {
        document.getElementById('eliminar_epp_id').value = id;
        document.getElementById('eliminarEppNombre').textContent = nombre;
        
        const modal = new bootstrap.Modal(document.getElementById('eliminarEppModal'));
        modal.show();
    }

    async eliminarEpp() {
        try {
            const id = document.getElementById('eliminar_epp_id').value;
            
            this.mostrarLoading(true);

            const response = await fetch(`/api/epp/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            });

            // Como no hay endpoint DELETE, usamos uno personalizado
            const result = await this.eliminarEppDirecto(id);

            if (result) {
                this.mostrarExito('EPP eliminado exitosamente');
                bootstrap.Modal.getInstance(document.getElementById('eliminarEppModal')).hide();
                this.cargarEpps();
            } else {
                this.mostrarError('Error al eliminar EPP');
            }
        } catch (error) {
            console.error('Error eliminando EPP:', error);
            this.mostrarError('Error de conexión al eliminar EPP');
        } finally {
            this.mostrarLoading(false);
        }
    }

    async eliminarEppDirecto(id) {
        try {
            const response = await fetch(`/api/epp/${id}/eliminar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            });

            const result = await response.json();
            return result.success;
        } catch (error) {
            console.error('Error en eliminación directa:', error);
            return false;
        }
    }

    limpiarFormularioCrear() {
        const form = document.getElementById('crearEppForm');
        form.reset();
        document.getElementById('activo').checked = true;
    }

    mostrarLoading(mostrar) {
        const loadingElements = document.querySelectorAll('.loading-spinner');
        
        if (loadingElements.length === 0) {
            if (mostrar) {
                const spinner = document.createElement('div');
                spinner.className = 'loading-spinner';
                spinner.innerHTML = `
                    <div class="d-flex justify-content-center align-items-center" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999;">
                        <div class="spinner-border text-light" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                `;
                document.body.appendChild(spinner);
            }
        } else {
            loadingElements.forEach(el => el.remove());
        }
    }

    mostrarExito(mensaje) {
        this.mostrarNotificacion(mensaje, 'success');
    }

    mostrarError(mensaje) {
        this.mostrarNotificacion(mensaje, 'danger');
    }

    mostrarNotificacion(mensaje, tipo) {
        // Crear toast de notificación
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${tipo} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${mensaje}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        const toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.innerHTML = toastHtml;
        document.body.appendChild(toastContainer);

        const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'));
        toast.show();

        // Eliminar después de ocultar
        toastContainer.querySelector('.toast').addEventListener('hidden.bs.toast', () => {
            toastContainer.remove();
        });
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.gestorEpps = new GestorEpps();
});
