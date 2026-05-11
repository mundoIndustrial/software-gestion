@extends('layouts.base')

@section('title', 'Gestión de Talleres')
@section('page-title', 'Gestión de Talleres')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/top-nav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-spa.css') }}">
@endpush

@section('body')
    <!-- Dashboard Top Nav -->
    @include('components.top-nav')

    <!-- Main Content -->
    <main class="main-container">
        <!-- Vista 1: Grid de Talleres -->
        <div id="viewTalleres" class="view-container">
            <div class="page-header">
                <div class="page-title-group">
                    <div class="subtitle">TALLERES ACTIVOS</div>
                    <h1>Selección de Responsable</h1>
                </div>
                <div class="page-actions">
                    <div class="gooey-search-wrapper">
                        <span class="material-symbols-rounded gooey-search-icon">search</span>
                        <input type="text" class="gooey-search-input" placeholder="Buscar taller..." id="searchInput">
                        <button class="gooey-search-clear" id="clearSearch" type="button">
                            <span class="material-symbols-rounded">close</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="cards-grid" id="talleresGrid">
                @forelse($talleres as $taller)
                    <div class="taller-card" data-name="{{ strtolower($taller->name) }}" data-taller-id="{{ $taller->id }}">
                        <div class="card-header-info">
                            <h2 class="taller-name">{{ $taller->name }}</h2>
                            <span class="badge-active">ACTIVO</span>
                        </div>
                        <p class="taller-role">RESPONSABLE DE TALLER</p>
                        
                        <div class="stats-container">
                            <div class="stat-row">
                                <span>Completados:</span>
                                <span class="stat-value stat-completed">0</span>
                            </div>
                            <div class="stat-row">
                                <span>Pendientes:</span>
                                <span class="stat-value stat-pending">0</span>
                            </div>
                        </div>
                        
                        <button class="btn-view btn-view-recibos" data-taller-id="{{ $taller->id }}">
                            Ver Recibos <span style="font-size: 10px; margin-left: 5px;">&#10095;</span>
                        </button>
                    </div>
                @empty
                    <div style="width: 100%; padding: 40px; text-align: center; color: #64748b; background: white; border-radius: 12px; border: 1px dashed #cbd5e1;">
                        <span class="material-symbols-rounded" style="font-size: 40px; color: #cbd5e1; margin-bottom: 10px;">inbox</span>
                        <p>No hay talleres disponibles en este momento.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Vista 2: Recibos -->
        <div id="viewRecibos" class="view-container" style="display: none;">
            <div class="page-header-recibos">
                <div class="header-left">
                    <button class="btn-back" id="backFromRecibos" title="Volver a Talleres">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                    <div class="title-group">
                        <span class="subtitle">Recibos Asignados a:</span>
                        <h1 id="recibosTitle">Taller</h1>
                    </div>
                </div>
                
                <div class="header-stats">
                    <div class="stat-box blue">
                        <span class="stat-label">TOTAL CARGA</span>
                        <span class="stat-number" id="totalCargaValue">0</span>
                    </div>
                    <div class="stat-box green">
                        <span class="stat-label">COMPLETADOS</span>
                        <span class="stat-number" id="completadosValue">0</span>
                    </div>
                </div>
            </div>

            <div class="recibos-card">
                <div class="card-header">
                    <div class="icon">
                        <span class="material-symbols-rounded" style="font-size: 18px;">receipt_long</span>
                    </div>
                    <h2>Listado de Recibos Asignados</h2>
                </div>
                
                <div id="recibosContent">
                    <div class="loading">
                        <div class="loading-spinner"></div>
                        <p>Cargando recibos...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista 3: Entregas -->
        <div id="viewEntregas" class="view-container" style="display: none;">
            <div class="page-header-recibos">
                <div class="header-left">
                    <button class="btn-back" id="backFromEntregas" title="Volver a Recibos">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                    <div class="title-group">
                        <span class="subtitle">Detalle de Entregas</span>
                        <h1 id="entregasTitle">Entregas</h1>
                    </div>
                </div>
                
                <div class="header-stats">
                    <div class="stat-box blue">
                        <span class="stat-label">TOTAL</span>
                        <span class="stat-number" id="entregasTotalValue">0</span>
                    </div>
                </div>
            </div>

            <div class="recibos-card">
                <div class="card-header">
                    <div class="icon">
                        <span class="material-symbols-rounded" style="font-size: 18px;">inventory_2</span>
                    </div>
                    <h2 id="entregasCardTitle">Historial de Entregas Semanales</h2>
                </div>
                
                <div id="entregasContent" style="padding: 20px;">
                    <div class="loading">
                        <div class="loading-spinner"></div>
                        <p>Cargando entregas...</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        let currentState = {
            view: 'talleres', // talleres, recibos, entregas
            selectedTaller: null,
            selectedRecibo: null
        };

        document.addEventListener('DOMContentLoaded', function() {
            initTalleresSearch();
            initViewHandlers();
        });

        function initTalleresSearch() {
            const searchInput = document.getElementById('searchInput');
            const clearButton = document.getElementById('clearSearch');
            const cards = document.querySelectorAll('.taller-card');
            
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const term = e.target.value.toLowerCase().trim();
                    
                    cards.forEach(card => {
                        const name = card.getAttribute('data-name');
                        if (name && name.includes(term)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });

                if (clearButton) {
                    clearButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        searchInput.value = '';
                        searchInput.focus();
                        
                        cards.forEach(card => {
                            card.style.display = 'block';
                        });
                    });
                }
            }
        }

        function initViewHandlers() {
            const viewRecibosButtons = document.querySelectorAll('.btn-view-recibos');
            const backFromRecibos = document.getElementById('backFromRecibos');
            const backFromEntregas = document.getElementById('backFromEntregas');

            viewRecibosButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tallerId = this.getAttribute('data-taller-id');
                    const tallerName = this.closest('.taller-card').querySelector('.taller-name').textContent;
                    showRecibos(tallerId, tallerName);
                });
            });

            backFromRecibos.addEventListener('click', function() {
                showTalleres();
            });

            backFromEntregas.addEventListener('click', function() {
                showRecibos(currentState.selectedTaller.id, currentState.selectedTaller.name);
            });
        }

        function switchView(newView) {
            const viewTalleres = document.getElementById('viewTalleres');
            const viewRecibos = document.getElementById('viewRecibos');
            const viewEntregas = document.getElementById('viewEntregas');

            // Ocultar todas las vistas
            viewTalleres.style.display = 'none';
            viewRecibos.style.display = 'none';
            viewEntregas.style.display = 'none';

            // Mostrar la nueva vista
            if (newView === 'talleres') {
                viewTalleres.style.display = 'block';
            } else if (newView === 'recibos') {
                viewRecibos.style.display = 'block';
            } else if (newView === 'entregas') {
                viewEntregas.style.display = 'block';
            }

            currentState.view = newView;
        }

        function showTalleres() {
            switchView('talleres');
            currentState.selectedTaller = null;
            currentState.selectedRecibo = null;
        }

        function showRecibos(tallerId, tallerName) {
            currentState.selectedTaller = { id: tallerId, name: tallerName };
            const recibosContent = document.getElementById('recibosContent');
            const recibosTitle = document.getElementById('recibosTitle');
            const totalCargaValue = document.getElementById('totalCargaValue');
            const completadosValue = document.getElementById('completadosValue');

            recibosTitle.textContent = tallerName;
            recibosContent.innerHTML = '<div class="loading"><div class="loading-spinner"></div><p>Cargando recibos...</p></div>';
            switchView('recibos');

            fetch(`/talleres/api/${tallerId}/recibos`)
                .then(response => response.json())
                .then(data => {
                    totalCargaValue.textContent = data.total;
                    completadosValue.textContent = data.completados;

                    if (data.recibos.length === 0) {
                        recibosContent.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📦</div><p>No hay recibos asignados a este taller.</p></div>';
                        return;
                    }

                    let html = '<div class="table-container"><table class="table-recibos"><thead><tr><th>Nº RECIBO</th><th>CLIENTE</th><th>DESCRIPCIÓN PRENDA</th><th>ESTADO</th><th>ACCIONES</th></tr></thead><tbody>';

                    data.recibos.forEach(recibo => {
                        html += `
                            <tr>
                                <td class="col-recibo">${recibo.numero_recibo}</td>
                                <td class="col-cliente">${recibo.cliente}</td>
                                <td>
                                    <div class="prenda-nombre">${recibo.nombre_prenda}</div>
                                    <p class="prenda-desc">${recibo.descripcion_prenda || ''}</p>
                                </td>
                                <td>-</td>
                                <td>
                                    <button class="btn-action btn-ver-entregas" data-taller-id="${data.taller_id}" data-recibo-id="${recibo.id}" data-es-parcial="${recibo.es_parcial}" data-recibo-numero="${recibo.numero_recibo}" data-cliente="${recibo.cliente}" data-prenda="${recibo.nombre_prenda}">
                                        Ver Entregas <span style="font-size: 10px;">&#10095;</span>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });

                    html += '</tbody></table></div>';
                    recibosContent.innerHTML = html;

                    // Agregar event listeners a los botones de entregas
                    document.querySelectorAll('.btn-ver-entregas').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const tallerId = this.getAttribute('data-taller-id');
                            const reciboId = this.getAttribute('data-recibo-id');
                            const esParcial = this.getAttribute('data-es-parcial');
                            const reciboNumero = this.getAttribute('data-recibo-numero');
                            const cliente = this.getAttribute('data-cliente');
                            const prenda = this.getAttribute('data-prenda');
                            showEntregas(tallerId, reciboId, esParcial, reciboNumero, cliente, prenda);
                        });
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    recibosContent.innerHTML = '<div class="empty-state"><p>Error al cargar los recibos.</p></div>';
                });
        }

        function showEntregas(tallerId, reciboId, esParcial, reciboNumero, cliente, prenda) {
            currentState.selectedRecibo = { id: reciboId, numero: reciboNumero, cliente: cliente, prenda: prenda };
            const entregasContent = document.getElementById('entregasContent');
            const entregasTitle = document.getElementById('entregasTitle');
            const entregasCardTitle = document.getElementById('entregasCardTitle');
            const entregasTotalValue = document.getElementById('entregasTotalValue');

            entregasTitle.textContent = `Recibo: ${reciboNumero} — ${cliente}`;
            entregasCardTitle.textContent = `Historial de Entregas Semanales - ${prenda}`;
            entregasContent.innerHTML = '<div class="loading"><div class="loading-spinner"></div><p>Cargando entregas...</p></div>';
            switchView('entregas');

            fetch(`/talleres/api/${tallerId}/recibos/${reciboId}/${esParcial}/entregas`)
                .then(response => response.json())
                .then(data => {
                    entregasTotalValue.textContent = data.total + ' UND';

                    if (!data.entregas || data.entregas.length === 0) {
                        entregasContent.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📦</div><p>No hay entregas registradas para este recibo.</p></div>';
                        return;
                    }

                    let html = '<div class="entregas-header"><div class="entregas-title"></div><div class="entregas-total"></div></div>';

                    data.entregas.forEach(semanaGroup => {
                        if (!semanaGroup || semanaGroup.length === 0) return;
                        
                        const semana = semanaGroup[0].grupo;
                        html += '<div class="semana-group">';
                        html += '<div class="semana-header"><span class="material-symbols-rounded">calendar_month</span>' + semana + '</div>';
                        html += '<table class="table-entregas"><thead><tr><th>FECHA</th><th>DESCRIPCIÓN</th><th>TALLA</th><th>CANTIDAD</th></tr></thead><tbody>';

                        semanaGroup.forEach(entrega => {
                            html += `
                                <tr>
                                    <td>${entrega.fecha_formateada}</td>
                                    <td>${entrega.descripcion}</td>
                                    <td><span class="badge-talla">${entrega.talla}</span></td>
                                    <td class="col-cantidad">${entrega.cantidad}<small>UND</small></td>
                                </tr>
                            `;
                        });

                        html += '</tbody></table></div>';
                    });

                    entregasContent.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    entregasContent.innerHTML = '<div class="empty-state"><p>Error al cargar las entregas.</p></div>';
                });
        }
    </script>
@endpush
