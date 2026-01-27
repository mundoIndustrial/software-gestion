<!-- Modal de Cotización para Vista de Entregas -->
<div id="cotizacionModal" class="cotizacion-modal" style="display: none;">
    <link rel="stylesheet" href="{{ asset('css/cotizaciones/modal-cotizaciones.css') }}">
    <div class="cotizacion-modal-content">
        <!-- Header -->
        <div class="cotizacion-modal-header">
            <div class="cotizacion-modal-header-left">
                <img src="{{ asset('images/logo2.png') }}" alt="Logo" class="cotizacion-modal-logo" width="150" height="60">
                <div>
                    <h2 id="modalClienteName">Cliente</h2>
                    <p id="modalCotizacionNumber">COT-00000</p>
                </div>
            </div>
            <button class="cotizacion-modal-close" onclick="closeCotizacionModal()">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>

        <!-- Body -->
        <div class="cotizacion-modal-body" id="modalBody">
            <!-- Información Principal -->
            <div class="info-section">
                <div class="info-grid">
                    <div class="info-item">
                        <label for="modalAsesora">Asesora</label>
                        <p id="modalAsesora">-</p>
                    </div>
                    <div class="info-item">
                        <label for="modalFecha">Fecha</label>
                        <p id="modalFecha">-</p>
                    </div>
                    <div class="info-item">
                        <label for="modalEstado">Estado</label>
                        <p id="modalEstado">-</p>
                    </div>
                </div>
            </div>

            <!-- Productos -->
            <div class="products-section">
                <h3 class="section-title"> Productos</h3>
                <div id="modalProductos" class="products-container">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>

            <!-- Especificaciones -->
            <div class="specs-section">
                <h3 class="section-title"> Especificaciones de la Orden</h3>
                <table class="specs-table">
                    <thead>
                        <tr>
                            <th>Especificación</th>
                            <th>Opciones Seleccionadas</th>
                        </tr>
                    </thead>
                    <tbody id="modalEspecificaciones">
                        <!-- Se llenará dinámicamente -->
                    </tbody>
                </table>
            </div>

            <!-- Técnicas -->
            <div class="tecnicas-section" id="tecnicasSection" style="display: none;">
                <h3 class="section-title"> Técnicas</h3>
                <div id="modalTecnicas" class="tecnicas-container">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>

            <!-- Observaciones Técnicas -->
            <div class="obs-tecnicas-section" id="obsTecnicasSection" style="display: none;">
                <h3 class="section-title"> Observaciones Técnicas</h3>
                <div id="modalObsTecnicas" class="obs-box">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>

            <!-- Observaciones Generales -->
            <div class="obs-generales-section" id="obsGeneralesSection" style="display: none;">
                <h3 class="section-title">💬 Observaciones Generales</h3>
                <ul id="modalObsGenerales" class="obs-list">
                    <!-- Se llenará dinámicamente -->
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="cotizacion-modal-footer">
            <button class="btn-close" onclick="closeCotizacionModal()">Cerrar</button>
        </div>
    </div>
</div>
