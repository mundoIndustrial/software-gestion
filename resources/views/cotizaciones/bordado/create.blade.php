OR
@extends('layouts.asesores')

@push('styles')
<style>
    /* Desactivar navbar */
    header {
        display: none !important;
    }

    .page-wrapper {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: 100vh;
        padding: 0.5rem;
    }

    .form-container {
        max-width: 1400px;
        margin: 0 auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 1.25rem 1.5rem;
    }

    .form-header {
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .form-header h1 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .form-header p {
        color: #64748b;
        font-size: 0.8rem;
    }

    .form-section {
        margin-bottom: 1.25rem;
    }

    .form-section-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #1e40af;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.3rem;
        font-size: 0.8rem;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.85rem;
        transition: all 0.2s ease;
        font-family: inherit;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #1e40af;
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
    }

    .form-actions {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.25rem;
        padding-top: 0.75rem;
        border-top: 2px solid #e2e8f0;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: 6px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.2s ease;
    }

    .btn-primary {
        background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%);
        color: white;
        flex: 1;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #64748b;
        flex: 1;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
    }

    /* Estilos del Paso 3 */
    .form-group-large {
        margin-bottom: 1rem;
    }

    .form-group-large label {
        display: block;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
        font-size: 0.8rem;
    }

    .input-large {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.85rem;
        font-family: inherit;
    }

    .input-large:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    /* Técnicas */
    .tecnicas-box {
        background: #f9f9f9;
        border: 2px solid #3498db;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 1rem;
    }

    .tecnicas-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .tecnicas-header label {
        font-weight: bold;
        font-size: 0.9rem;
        margin: 0;
    }

    .btn-add {
        background: #3498db;
        color: white;
        border: none;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        cursor: pointer;
        font-size: 1.2rem;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .btn-add:hover {
        background: #2980b9;
    }

    .tecnicas-seleccionadas {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 10px;
        min-height: 25px;
    }

    .tecnica-badge {
        background: #3498db;
        color: white;
        padding: 4px 8px;
        border-radius: 20px;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .tecnica-badge .remove {
        cursor: pointer;
        font-weight: bold;
    }

    /* Ubicación */
    .ubicacion-box {
        background: #f9f9f9;
        border: 2px solid #3498db;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 1rem;
    }

    .ubicacion-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .ubicacion-header label {
        font-weight: bold;
        font-size: 0.9rem;
        margin: 0;
    }

    .secciones-agregadas {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .seccion-item {
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.85rem;
    }

    .seccion-item .remove {
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Observaciones Generales */
    .obs-box {
        background: #f9f9f9;
        border: 2px solid #3498db;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 1rem;
    }

    .obs-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .obs-header label {
        font-weight: bold;
        font-size: 0.9rem;
        margin: 0;
    }

    .obs-lista {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .obs-item {
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .obs-item input {
        flex: 1;
        border: none;
        padding: 0;
        font-size: 0.85rem;
    }

    .obs-item input:focus {
        outline: none;
    }

    .obs-item .remove {
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Animación de temblor */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    .shake {
        animation: shake 0.5s ease-in-out;
    }

    /* Imágenes */
    .drop-zone {
        border: 2px dashed #3498db;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        background: #f0f7ff;
        cursor: pointer;
        margin-bottom: 10px;
    }

    .drop-zone i {
        font-size: 2.5rem;
        color: #3498db;
        margin-bottom: 10px;
        display: block;
    }

    .drop-zone p {
        margin: 10px 0;
        color: #3498db;
        font-weight: 600;
    }

    .drop-zone-small {
        margin: 5px 0;
        color: #666;
        font-size: 0.9rem;
    }

    .galeria-imagenes {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .imagen-item {
        position: relative;
        width: 100%;
        aspect-ratio: 1;
        border-radius: 6px;
        overflow: hidden;
        background: #f0f0f0;
    }

    .imagen-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .imagen-item .remove {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <!-- Header Moderno -->
    <div style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); border-radius: 12px; padding: 1.25rem 1.75rem; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 1.5rem; align-items: start;">
            <!-- Título y descripción -->
            <div style="display: flex; align-items: center; gap: 0.75rem; grid-column: 1 / -1;">
                <span class="material-symbols-rounded" style="font-size: 1.75rem; color: white;">brush</span>
                <div>
                    <h2 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 700;">Cotización de Logo</h2>
                    <p style="margin: 0.2rem 0 0 0; color: rgba(255,255,255,0.85); font-size: 0.8rem;">Completa los datos de la cotización</p>
                </div>
            </div>
            
            <!-- Campos del Header en una fila -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; grid-column: 1 / -1;">
                <!-- Cliente -->
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Cliente</label>
                    <input type="text" id="header-cliente" placeholder="Nombre del cliente" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>
                
                <!-- Asesor -->
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Asesor</label>
                    <input type="text" id="header-asesor" value="{{ auth()->user()->name }}" readonly style="width: 100%; background: rgba(255,255,255,0.9); border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; cursor: not-allowed;">
                </div>
                
                <!-- Fecha -->
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Fecha</label>
                    <input type="date" id="header-fecha" value="{{ date('Y-m-d') }}" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s;">
                </div>
                
                <!-- Tipo para Cotizar -->
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Tipo para Cotizar</label>
                    <select id="header-tipo-venta" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s; cursor: pointer;">
                        <option value="">-- SELECCIONA --</option>
                        <option value="M">M</option>
                        <option value="D">D</option>
                        <option value="X">X</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="form-container">
        <form id="cotizacionBordadoForm">
            @csrf

            <!-- Campos ocultos para sincronizar con el header -->
            <input type="text" id="cliente" name="cliente" style="display: none;">
            <input type="text" id="asesora" name="asesora" value="{{ auth()->user()->name }}" readonly style="display: none;">
            <input type="date" id="fecha" name="fecha" style="display: none;">
            <input type="text" id="tipo_venta_bordado" name="tipo_venta_bordado" style="display: none;">
            <textarea id="especificaciones" name="especificaciones" style="display: none;"></textarea>

            <!-- INFORMACIÓN DE TELAS, COLORES Y REFERENCIAS POR PRENDA -->
            <div style="display: none;">
                <input type="text" id="telas_prendas_json" name="telas_prendas_json" value="[]">
            </div>

            <!-- TÉCNICAS -->
            <div class="form-section">
                <div class="tecnicas-box">
                    <h3 style="margin-bottom: 20px; color: #1e40af; font-weight: 600;">Técnicas</h3>
                    
                    <!-- Selector de Técnicas (Checkboxes) -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333;">Selecciona las técnicas a aplicar:</label>
                        <div id="tecnicas-checkboxes" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 15px;">
                            <!-- Se llenan dinámicamente con renderizarCheckboxesTecnicas() -->
                        </div>
                        <button type="button" id="btnAgregarPrendas" onclick="abrirModalAgregarTecnica()" style="background: #1e40af; color: white; border: none; cursor: pointer; padding: 10px 20px; border-radius: 4px; font-weight: 600; transition: background 0.2s ease;" title="Agregar prendas para las técnicas seleccionadas">
                            <i class="fas fa-plus"></i> Agregar Prendas
                        </button>
                    </div>
                    
                    <!-- Lista de Prendas Agregadas por Técnica -->
                    <div id="tecnicas_agregadas" style="margin-top: 15px;"></div>
                    
                    <!-- Sin Técnicas -->
                    <div id="sin_tecnicas" style="padding: 20px; text-align: center; background: #f5f5f5; border-radius: 8px; color: #999; display: block;">
                        <p>Selecciona técnicas y agrega prendas</p>
                    </div>
                </div>
            </div>

            <!-- OBSERVACIONES GENERALES -->
            <div class="form-section">
                <div class="obs-box">
                    <div class="obs-header">
                        <label>Observaciones Generales</label>
                        <button type="button" class="btn-add" onclick="agregarObservacion()">+</button>
                    </div>
                    
                    <div class="obs-lista" id="observaciones_lista"></div>
                </div>
            </div>

            <!-- Botones -->
            <div class="form-actions">
                <a href="{{ route('asesores.cotizaciones-bordado.lista') }}" class="btn btn-secondary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%); border: 2px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 600; color: #333; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none;" onmouseover="this.style.background='linear-gradient(135deg, #e8e8e8 0%, #d5d5d5 100%)'; this.style.borderColor='#999'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)';" onmouseout="this.style.background='linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%)'; this.style.borderColor='#ddd'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <i class="fas fa-times" style="font-size: 0.9rem;"></i> Cancelar
                </a>
                <div style="display: flex; gap: 0.5rem; flex: 1; justify-content: flex-end;">
                    <button type="submit" name="action" value="borrador" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); border: 2px solid #3d8b40; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #45a049 0%, #3d8b40 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(76, 175, 80, 0.2)';" onmouseout="this.style.background='linear-gradient(135deg, #4CAF50 0%, #45a049 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="fas fa-save" style="font-size: 0.9rem;"></i> Guardar Borrador
                    </button>
                    <button type="submit" name="action" value="enviar" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); border: 2px solid #003d7a; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #0052a3 0%, #003d7a 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 12px rgba(0, 102, 204, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0066cc 0%, #0052a3 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="fas fa-paper-plane" style="font-size: 0.9rem;"></i> Enviar
                    </button>
                </div>
            </div>
        </form>

        <!-- MENÚ FLOTANTE PARA ESPECIFICACIONES -->
        <div style="position: fixed; bottom: 30px; right: 30px; z-index: 1000;">
            <!-- Menú flotante -->
            <div id="menuFlotante" style="display: none; position: absolute; bottom: 70px; right: 0; background: white; border-radius: 12px; box-shadow: 0 5px 40px rgba(0,0,0,0.16); overflow: hidden; min-width: 200px;">
                <button type="button" onclick="abrirModalEspecificaciones(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 12px 16px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.9rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                    <i class="fas fa-sliders-h" style="color: #3B82F6; font-size: 1.1rem;"></i>
                    <span>Especificaciones</span>
                </button>
            </div>
            
            <!-- Botón principal flotante -->
            <button type="button" id="btnFlotante" onclick="const menu = document.getElementById('menuFlotante'); menu.style.display = menu.style.display === 'none' ? 'block' : 'none'; this.style.transform = menu.style.display === 'block' ? 'scale(1) rotate(45deg)' : 'scale(1) rotate(0deg)'" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #0ea5e9); color: white; border: none; cursor: pointer; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.4); transition: all 0.3s ease; position: relative;" onmouseover="this.style.boxShadow='0 6px 20px rgba(30, 64, 175, 0.5)'; this.style.transform='scale(1.1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')" onmouseout="this.style.boxShadow='0 4px 12px rgba(30, 64, 175, 0.4)'; this.style.transform='scale(1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>

    <!-- MODAL: ESPECIFICACIONES DEL LOGO -->
    <div id="modalEspecificaciones" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 950px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 3px solid #0284c7; padding-bottom: 1.5rem;">
                <h3 style="margin: 0; color: #0284c7; font-size: 1.4rem; font-weight: 700;"><i class="fas fa-cog" style="margin-right: 10px;"></i>ESPECIFICACIONES GENERALES</h3>
                <button type="button" onclick="cerrarModalEspecificaciones()" style="background: #f0f0f0; border: none; font-size: 1.5rem; cursor: pointer; color: #666; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <table class="tabla-control-compacta" style="width: 100%; border-collapse: collapse; background: white;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #0284c7, #0166a0); color: white;">
                        <th style="width: 20%; text-align: left; padding: 12px; font-weight: 600; border: none;">CONCEPTO</th>
                        <th style="width: 10%; text-align: center; padding: 12px; font-weight: 600; border: none;">APLICA</th>
                        <th style="width: 60%; text-align: left; padding: 12px; font-weight: 600; border: none;">OBSERVACIONES</th>
                        <th style="width: 10%; text-align: center; padding: 12px; font-weight: 600; border: none;">ACCIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- DISPONIBILIDAD -->
                    <tr class="fila-grupo">
                        <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0284c7;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #0284c7;"><i class="fas fa-warehouse" style="margin-right: 8px;"></i>DISPONIBILIDAD</span>
                                <button type="button" onclick="agregarFilaEspecificacion('disponibilidad')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                    <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tbody id="tbody_disponibilidad">
                        <tr>
                            <td><label style="margin: 0; font-size: 0.8rem;">Bodega</label></td>
                            <td style="text-align: center;">
                                <input type="checkbox" name="tabla_orden[bodega]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                            </td>
                            <td style="padding: 10px;">
                                <input type="text" name="tabla_orden[bodega_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                            </td>
                            <td style="text-align: center;">
                                <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tbody id="tbody_disponibilidad_adicional"></tbody>
                </tbody>

                <!-- FORMA DE PAGO -->
                <tr class="fila-grupo">
                    <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0284c7;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #0284c7;"><i class="fas fa-credit-card" style="margin-right: 8px;"></i>FORMA DE PAGO</span>
                            <button type="button" onclick="agregarFilaEspecificacion('forma_pago')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                            </button>
                        </div>
                    </td>
                </tr>
                <tbody>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Contado</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" name="tabla_orden[contado]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[pago_contado_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Crédito</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" name="tabla_orden[credito]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[pago_credito_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>

                <!-- RÉGIMEN -->
                <tr class="fila-grupo">
                    <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0284c7;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #0284c7;"><i class="fas fa-balance-scale" style="margin-right: 8px;"></i>RÉGIMEN</span>
                            <button type="button" onclick="agregarFilaEspecificacion('regimen')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                            </button>
                        </div>
                    </td>
                </tr>
                <tbody>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Común</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" name="tabla_orden[comun]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[regimen_comun_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><label style="margin: 0; font-size: 0.8rem;">Simplificado</label></td>
                        <td style="text-align: center;">
                            <input type="checkbox" name="tabla_orden[simplificado]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[regimen_simp_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>

                <!-- SE HA VENDIDO -->
                <tr class="fila-grupo">
                    <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0284c7;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #0284c7;"><i class="fas fa-check-circle" style="margin-right: 8px;"></i>SE HA VENDIDO</span>
                            <button type="button" onclick="agregarFilaEspecificacion('se_ha_vendido')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                            </button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_vendido">
                    <tr>
                        <td><input type="text" name="tabla_orden[vendido_item]" class="input-compact" placeholder="Especificar..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;"></td>
                        <td style="text-align: center;">
                            <input type="checkbox" name="tabla_orden[vendido]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[vendido_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>

                <!-- ÚLTIMA VENTA -->
                <tr class="fila-grupo">
                    <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0284c7;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #0284c7;"><i class="fas fa-history" style="margin-right: 8px;"></i>ÚLTIMA VENTA</span>
                            <button type="button" onclick="agregarFilaEspecificacion('ultima_venta')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                            </button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_ultima_venta">
                    <tr>
                        <td><input type="text" name="tabla_orden[ultima_venta_item]" class="input-compact" placeholder="Fecha..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;"></td>
                        <td style="text-align: center;">
                            <input type="checkbox" name="tabla_orden[ultima_venta]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[ultima_venta_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>

                <!-- FLETE DE ENVÍO -->
                <tr class="fila-grupo">
                    <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0284c7;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #0284c7;"><i class="fas fa-truck" style="margin-right: 8px;"></i>FLETE DE ENVÍO</span>
                            <button type="button" onclick="agregarFilaEspecificacion('flete')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                            </button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_flete">
                    <tr>
                        <td><input type="text" name="tabla_orden[flete_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
                        <td style="text-align: center;">
                            <input type="checkbox" name="tabla_orden[flete]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px;">
                            <input type="text" name="tabla_orden[flete_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" onclick="cerrarModalEspecificaciones()" style="padding: 0.65rem 1.5rem; background: #f1f5f9; border: 2px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-weight: 600; color: #64748b; font-size: 0.9rem; transition: all 0.2s ease;">Cancelar</button>
                <button type="button" onclick="guardarEspecificacionesReflectivo()" style="padding: 0.65rem 1.5rem; background: linear-gradient(135deg, #0284c7 0%, #0166a0 100%); border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.9rem; transition: all 0.2s ease;">Guardar Especificaciones</button>
            </div>
        </div>
    </div>
</div>

@if(!isset($cotizacion) || !$cotizacion)
<script src="{{ asset('js/asesores/cotizaciones/persistencia.js') }}"></script>
@endif

<script>
// Arrays para almacenar datos
let tecnicasSeleccionadas = [];
let observacionesGenerales = [];
let imagenesSeleccionadas = [];
let imagenesABorrar = [];  // Rastrear IDs de imágenes a borrar
let tempUbicaciones = []; // Almacenar ubicaciones personalizadas temporalmente

// Crear un Proxy para rastrear cambios en tecnicasSeleccionadas
const originalTecnicas = tecnicasSeleccionadas;
tecnicasSeleccionadas = new Proxy(originalTecnicas, {
    set(target, property, value) {
        console.trace(' Stack trace:');
        target[property] = value;
        return true;
    }
});

// Lista unificada de ubicaciones
let todasLasUbicaciones = [
    'PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA', 'CUELLO',
    'PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO',
    'FRENTE', 'LATERAL', 'TRASERA'
];

// Drag and drop para imágenes - ELIMINADO (imágenes manejadas en modal de técnicas)
/*
const dropZone = document.getElementById('drop_zone_imagenes');
const inputImagenes = document.getElementById('imagenes_bordado');

dropZone.addEventListener('click', () => inputImagenes.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.background = '#e8f4f8';
});

dropZone.addEventListener('dragleave', () => {
    dropZone.style.background = '#f0f7ff';
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.style.background = '#f0f7ff';
    manejarImagenes(e.dataTransfer.files);
});

inputImagenes.addEventListener('change', (e) => {
    manejarImagenes(e.target.files);
});

function manejarImagenes(files) {
    if (imagenesSeleccionadas.length + files.length > 5) {
        alert('Máximo 5 imágenes permitidas');
        return;
    }

    Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                imagenesSeleccionadas.push({
                    file: file,
                    preview: e.target.result
                });
                renderizarImagenes();
            };
            reader.readAsDataURL(file);
        }
    });
}

    // Si es una imagen existente (tiene ID), borrarla inmediatamente de la BD
    if (imagenAEliminar.existing && imagenAEliminar.id) {
        // Obtener cotizacion_id de la URL
        const urlParams = new URLSearchParams(window.location.search);
        const cotizacionId = urlParams.get('editar');
        
        if (cotizacionId) {
            // Hacer petición AJAX para borrar la imagen
            fetch(`/cotizaciones-bordado/${cotizacionId}/borrar-imagen`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                                   document.querySelector('input[name="_token"]')?.value
                },
                body: JSON.stringify({
                    foto_id: imagenAEliminar.id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                } else {
                }
            })
            .catch(error => console.error(' Error en petición:', error));
        }
    }
    
    // SIEMPRE quitar la imagen del array (sea existente o nueva)
    imagenesSeleccionadas.splice(index, 1);
    renderizarImagenes();
}
*/

// Técnicas
function agregarTecnica() {
    const selector = document.getElementById('selector_tecnicas');
    const tecnica = selector.value;


    console.log(' Es array?', Array.isArray(tecnicasSeleccionadas));
    
    if (!tecnica) {
        Swal.fire({
            icon: 'warning',
            title: 'Técnica Requerida',
            text: 'Selecciona una técnica de la lista',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
        return;
    }
    
    if (tecnicasSeleccionadas.includes(tecnica)) {
        Swal.fire({
            icon: 'info',
            title: 'Ya Agregada',
            text: `La técnica "${tecnica}" ya está en la lista`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
        return;
    }
    
    tecnicasSeleccionadas.push(tecnica);

    selector.value = '';
    renderizarTecnicas();
}

function renderizarTecnicas() {
    const container = document.getElementById('tecnicas_seleccionadas');
    
    // Si el contenedor no existe, no hacer nada (no es crítico)
    if (!container) {
        return;
    }
    
    container.innerHTML = '';
    
    tecnicasSeleccionadas.forEach((tecnica, index) => {
        const badge = document.createElement('span');
        badge.className = 'tecnica-badge';
        badge.innerHTML = `
            ${tecnica}
            <span class="remove" onclick="eliminarTecnica(${index})">×</span>
        `;
        container.appendChild(badge);
    });
}

function eliminarTecnica(index) {
    tecnicasSeleccionadas.splice(index, 1);
    renderizarTecnicas();
}

// ============ OBSERVACIONES ============

function agregarObservacion() {
    const contenedor = document.getElementById('observaciones_lista');
    const fila = document.createElement('div');
    fila.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
    fila.innerHTML = `
        <input type="text" name="observaciones_generales[]" class="input-large" placeholder="Escribe una observación..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
        <div style="display: flex; gap: 5px; align-items: center; flex-shrink: 0;">
            <div class="obs-checkbox-mode" style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="observaciones_check[]" style="width: 20px; height: 20px; cursor: pointer;">
            </div>
            <div class="obs-text-mode" style="display: none; flex: 1;">
                <input type="text" name="observaciones_valor[]" placeholder="Valor..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
            </div>
            <button type="button" class="obs-toggle-btn" style="background: #3498db; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">✓/✎</button>
        </div>
        <button type="button" onclick="this.closest('div').remove()" style="background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 1rem; flex-shrink: 0;">✕</button>
    `;
    contenedor.appendChild(fila);
    
    const toggleBtn = fila.querySelector('.obs-toggle-btn');
    const checkboxMode = fila.querySelector('.obs-checkbox-mode');
    const textMode = fila.querySelector('.obs-text-mode');
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (checkboxMode.style.display === 'none') {
            checkboxMode.style.display = 'block';
            textMode.style.display = 'none';
            toggleBtn.style.background = '#3498db';
        } else {
            checkboxMode.style.display = 'none';
            textMode.style.display = 'block';
            toggleBtn.style.background = '#ff9800';
        }
    });
}


// Sincronizar valores del header con el formulario
document.getElementById('header-cliente').addEventListener('input', function() {
    document.getElementById('cliente').value = this.value;
});

document.getElementById('header-fecha').addEventListener('change', function() {
    document.getElementById('fecha').value = this.value;
});

// Envío del formulario
document.getElementById('cotizacionBordadoForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    //  NO LLAMAR guardarTecnicasEnBD() AQUÍ
    // Las técnicas se guardarán DESPUÉS de crear la cotización en el servidor
    // Esto evita crear una cotización vacía de borrador
    // Detectar cuál botón se presionó PRIMERO
    const submitButton = e.submitter;
    if (!submitButton) {
        return;
    }

    // ✅ GUARDAR ESPECIFICACIONES SI EL MODAL TIENE DATOS
    const modalEspecificaciones = document.getElementById('modalEspecificaciones');
    if (modalEspecificaciones && modalEspecificaciones.style.display !== 'none') {
        // El modal está abierto, guardar los datos antes de enviar
        if (typeof guardarEspecificacionesReflectivo === 'function') {
            guardarEspecificacionesReflectivo();
        }
    }

    // Desactivar botones durante el envío
    document.querySelectorAll('button[type="submit"]').forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.style.cursor = 'not-allowed';
    });

    // Sincronizar valores del header antes de enviar (con verificación)
    const headerCliente = document.getElementById('header-cliente');
    const headerFecha = document.getElementById('header-fecha');
    const clienteInput = document.getElementById('cliente');
    const fechaInput = document.getElementById('fecha');

    if (headerCliente && clienteInput) {
        clienteInput.value = headerCliente.value;
    }
    if (headerFecha && fechaInput) {
        fechaInput.value = headerFecha.value;
    }

    const cliente = clienteInput?.value || '';
    const asesora = document.getElementById('asesora')?.value || '';
    const observacionesTecnicas = document.getElementById('observaciones_tecnicas')?.value || '';
    if (!cliente || !asesora) {
        Swal.fire(' Campos Incompletos', 'Completa el cliente y otros campos obligatorios', 'warning');
        document.querySelectorAll('button[type="submit"]').forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
        });
        return;
    }

    
    const action = submitButton.value;
    
    console.log('🔵 Botón presionado:', submitButton?.textContent?.trim());



    console.log(' Es array?', Array.isArray(tecnicasSeleccionadas));

    // Determinar si es edición o creación
    let url, method;
    if (window.location.search.includes('editar=')) {
        // Editando borrador
        const cotizacionId = new URLSearchParams(window.location.search).get('editar');
        url = `/cotizaciones-bordado/${cotizacionId}/borrador`;
        method = 'PUT';
    } else {
        // Creando nueva cotización
        url = `/cotizaciones-bordado`;
        method = 'POST';
    }

    // Leer observaciones generales del DOM con TODA la información
    const observacionesDelDOM = [];
    document.querySelectorAll('#observaciones_lista > div').forEach((div) => {
        const inputTexto = div.querySelector('input[name="observaciones_generales[]"]');
        const inputCheck = div.querySelector('input[name="observaciones_check[]"]');
        const inputValor = div.querySelector('input[name="observaciones_valor[]"]');
        
        if (inputTexto && inputTexto.value.trim()) {
            const esCheckbox = inputCheck && inputCheck.checked;
            const esTexto = inputValor && inputValor.style.display !== 'none';
            
            const obs = {
                texto: inputTexto.value.trim(),
                tipo: esCheckbox ? 'checkbox' : 'texto',
                valor: esCheckbox ? inputCheck.checked : (inputValor ? inputValor.value : '')
            };
            
            observacionesDelDOM.push(obs);
        }
    });

    // Preparar datos como JSON
    const tokenInput = document.querySelector('input[name="_token"]');
    const headerFechaElement = document.getElementById('header-fecha');
    const headerTipoVentaElement = document.getElementById('header-tipo-venta');
    
    //  Usar window.tecnicasAgregadas si está disponible (viene de logo-cotizacion-tecnicas.js)
    // Si no está disponible, usar array vacío
    const tecnicasAEnviar = typeof window.tecnicasAgregadas !== 'undefined' ? window.tecnicasAgregadas : [];
    
    // OBTENER DATOS DE TELAS, COLORES Y REFERENCIAS
    const telasPrendas = [];
    
    // Las telas están dentro de las prendas que ya fueron extraídas por el sistema
    // Buscar todas las filas de tela en el modal (directamente por clases CSS)
    const modal = document.getElementById('modalAgregarTecnica');
    if (modal) {
        // Buscar TODAS las filas de tela con clase .fila-tela-logo
        const filasTelaLogo = modal.querySelectorAll('.fila-tela-logo');
        
        console.log('🔍 DEBUG: Buscando filas de tela. Encontradas:', filasTelaLogo.length);
        
        // Para cada fila de tela encontrada
        let telaCounter = 0;
        filasTelaLogo.forEach((filaTela, index) => {
            // Buscar inputs por sus clases CSS
            const colorInput = filaTela.querySelector('.input-color-logo');
            const telaInput = filaTela.querySelector('.input-tela-logo');
            const refInput = filaTela.querySelector('.input-referencia-logo');
            const imagenInput = filaTela.querySelector('.input-file-tela-logo');
            
            const color = colorInput?.value?.trim() || null;
            const tela = telaInput?.value?.trim() || null;
            const ref = refInput?.value?.trim() || null;
            const imagen = imagenInput?.files[0] || null;
            
            console.log(`    Fila ${index}: Color="${color}", Tela="${tela}", Ref="${ref}", Imagen=${!!imagen}`);
            
            // Si hay al menos un dato, agregar a la lista
            if (color || tela || ref || imagen) {
                // Obtener info del nombre de prenda (usando índice)
                const prendaItem = filaTela.closest('.prenda-item');
                const nombrePrenda = prendaItem?.querySelector('.nombre_prenda')?.value?.trim() || `Prenda ${index}`;
                
                // Usar un ID único secuencial
                telaCounter++;
                const prendaCotId = telaCounter;  // ID simple incremental
                
                telasPrendas.push({
                    prenda_cot_id: prendaCotId,
                    nombre_prenda: nombrePrenda,
                    color: color,
                    tela: tela,
                    ref: ref,
                    imagen: imagen
                });
                
                console.log(`    ✅ Tela ${telaCounter} agregada:`, { nombre: nombrePrenda, color, tela, ref });
            }
        });
        
        console.log('📋 DEBUG: Total telas procesadas:', telasPrendas.length);
        if (telasPrendas.length > 0) {
            console.log('📋 DEBUG: Contenido telasPrendas:', JSON.stringify(
                telasPrendas.map(t => ({ 
                    prenda_cot_id: t.prenda_cot_id, 
                    color: t.color, 
                    tela: t.tela, 
                    ref: t.ref,
                    tieneImagen: !!t.imagen
                }))
            ));
        }
    }
    
    const data = {
        _token: tokenInput?.value || '',
        cliente: cliente,
        asesora: asesora,
        fecha: headerFechaElement?.value || '',
        action: action,
        observaciones_tecnicas: observacionesTecnicas,
        tecnicas: tecnicasAEnviar,
        observaciones_generales: observacionesDelDOM,
        tipo_venta_bordado: headerTipoVentaElement?.value || '',
        telas_prendas_json: JSON.stringify(telasPrendas),
        especificaciones: document.getElementById('especificaciones')?.value || ''
    };


    // Verificar si hay imágenes nuevas EN EL LOGO O EN LAS TÉCNICAS
    const tieneImagenesNuevas = imagenesSeleccionadas.some(img => !img.existing);
    const tieneImagenesEnTecnicas = (data.tecnicas || []).some(tecnica => {
        return (tecnica.prendas || []).some(prenda => {
            return prenda.imagenes_files && prenda.imagenes_files.length > 0;
        });
    });
    const tieneLogosCompartidos = (data.tecnicas || []).some(tecnica => {
        return tecnica.logosCompartidos && Object.keys(tecnica.logosCompartidos).length > 0;
    });
    const debeUsarFormData = tieneImagenesNuevas || tieneImagenesEnTecnicas || tieneLogosCompartidos;



    if (debeUsarFormData) {
        // Si hay imágenes nuevas, usar FormData (un solo fetch)
        const formData = new FormData();
        
        // Si es PUT, agregar _method para que Laravel lo reconozca
        if (method === 'PUT') {
            formData.append('_method', 'PUT');
        }

        //  EXTRAER Y PROCESAR ARCHIVOS DE TÉCNICAS ANTES DE SERIALIZARLAS

        let totalArchivosEnTecnicas = 0;
        let totalLogosCompartidos = 0;
        
        // Crear versión sin archivos para JSON
        const tecnicasParaJSON = (data.tecnicas || []).map((tecnica, tecnicaIdx) => {

            return {
                ...tecnica,
                prendas: (tecnica.prendas || []).map((prenda, prendaIdx) => {

                    console.log(`      imagenes_files es array:`, Array.isArray(prenda.imagenes_files));
                    // Extraer archivos si existen
                    if (prenda.imagenes_files && Array.isArray(prenda.imagenes_files)) {
                        prenda.imagenes_files.forEach((archivo, imgIdx) => {
                            if (archivo instanceof File) {
                                const fieldName = `tecnica_${tecnicaIdx}_prenda_${prendaIdx}_img_${imgIdx}`;
                                formData.append(fieldName, archivo);
                                totalArchivosEnTecnicas++;
                                console.log(`        ✓ Archivo agregado: ${fieldName} (${archivo.name})`);
                            }
                        });
                    }
                    
                    // 🆕 PROCESAR ARCHIVOS DE TELAS DE ESTA PRENDA
                    if (prenda.telas && Array.isArray(prenda.telas)) {
                        prenda.telas.forEach((tela, telaIdx) => {
                            if (tela.archivo && tela.archivo instanceof File) {
                                const fieldName = `tecnica_${tecnicaIdx}_prenda_${prendaIdx}_tela_${telaIdx}`;
                                formData.append(fieldName, tela.archivo);
                                totalArchivosEnTecnicas++;
                                console.log(`        ✓ Archivo de tela agregado: ${fieldName} (${tela.archivo.name})`);
                            }
                        });
                    }
                    
                    // Retornar prenda sin archivos para JSON
                    // IMPORTANTE: Incluir telas para que se procesen en el backend
                    const telasParaJSON = prenda.telas ? (prenda.telas).map(tela => ({
                        color: tela.color || null,
                        tela: tela.tela || null,
                        referencia: tela.referencia || null,
                        // El archivo se procesa por separado en FormData
                    })) : null;
                    
                    return {
                        nombre_prenda: prenda.nombre_prenda,
                        observaciones: prenda.observaciones,
                        ubicaciones: prenda.ubicaciones,
                        talla_cantidad: prenda.talla_cantidad,
                        variaciones_prenda: prenda.variaciones_prenda || null,
                        telas: telasParaJSON,  // ← CRUCIAL: Incluir telas
                        imagenes_files: [] // Vacío - los archivos ya están en FormData
                    };
                }),
                logosCompartidos: null // Se procesarán por separado
            };
        });
        
        // EXTRAER LOGOS COMPARTIDOS Y METADATOS
        let logosCompartidosMetadata = {}; // Para almacenar metadatos por clave
        let metadataIdx = 0;
        
        (data.tecnicas || []).forEach((tecnica, tecnicaIdx) => {
            if (tecnica.logosCompartidos && typeof tecnica.logosCompartidos === 'object') {
                for (let clave in tecnica.logosCompartidos) {
                    const archivo = tecnica.logosCompartidos[clave];
                    if (archivo instanceof File) {
                        // Agregar archivo
                        const fieldName = `tecnica_${tecnicaIdx}_logo_compartido_${clave}`;
                        formData.append(fieldName, archivo);
                        totalLogosCompartidos++;
                        console.log(`        ✓ Logo compartido agregado: ${fieldName} (${archivo.name})`);
                        
                        // Agregar metadatos SOLO UNA VEZ por clave (evitar duplicados)
                        if (!logosCompartidosMetadata[clave]) {
                            logosCompartidosMetadata[clave] = {
                                nombreCompartido: clave,
                                tecnicasCompartidas: [],
                                archivoNombre: archivo.name,
                                tamaño: archivo.size
                            };
                        }
                    }
                }
            }
        });
        
        // Ahora recorrer las técnicas nuevamente para llenar qué técnicas tienen cada logo
        (data.tecnicas || []).forEach((tecnica, tecnicaIdx) => {
            if (tecnica.logosCompartidos && typeof tecnica.logosCompartidos === 'object') {
                for (let clave in tecnica.logosCompartidos) {
                    const archivo = tecnica.logosCompartidos[clave];
                    if (archivo instanceof File && logosCompartidosMetadata[clave]) {
                        // Agregar el nombre de la técnica si no está ya
                        const nombreTecnica = tecnica.tipo_logo?.nombre || 'DESCONOCIDA';
                        if (!logosCompartidosMetadata[clave].tecnicasCompartidas.includes(nombreTecnica)) {
                            logosCompartidosMetadata[clave].tecnicasCompartidas.push(nombreTecnica);
                        }
                    }
                }
            }
        });
        
        // Agregar metadatos al FormData
        Object.keys(logosCompartidosMetadata).forEach((clave, idx) => {
            const metadata = logosCompartidosMetadata[clave];
            formData.append(`logo_compartido_metadata_${idx}`, JSON.stringify(metadata));
            console.log(`        ✓ Metadata agregado: logo_compartido_metadata_${idx}`, metadata);
        });

        data.tecnicas = tecnicasParaJSON;

        // Agregar datos JSON al FormData
        Object.keys(data).forEach(key => {
            if (Array.isArray(data[key]) || typeof data[key] === 'object') {
                formData.append(key, JSON.stringify(data[key]));
            } else {
                formData.append(key, data[key]);
            }
        });

        // Agregar solo imágenes nuevas (no existentes)
        imagenesSeleccionadas.forEach((img) => {
            if (!img.existing) {
                formData.append('imagenes[]', img.file);
            }
        });

        // AGREGAR IMÁGENES DE TELAS
        telasPrendas.forEach((tela) => {
            if (tela.imagen) {
                formData.append(`img_tela_${tela.prenda_cot_id}`, tela.imagen);
            }
        });
        
        // Agregar IDs de imágenes a borrar DIRECTAMENTE al FormData
        formData.append('imagenes_a_borrar', JSON.stringify(imagenesABorrar));
        console.log('📤 FormData enviado (imagenes_a_borrar):', formData.get('imagenes_a_borrar'));

        // Enviar IDs de imágenes existentes para preservarlas
        const imagenesExistentesIds = imagenesSeleccionadas
            .filter(img => img.existing)
            .map(img => img.id);
        // IMPORTANTE: Siempre enviar imagenes_existentes, aunque sea vacío
        formData.append('imagenes_existentes', JSON.stringify(imagenesExistentesIds));

        // DEBUG: Ver EXACTAMENTE qué hay en FormData
        console.log('=== DEBUG: FormData Contents Before POST ===');
        for (let [key, value] of formData.entries()) {
            if (value instanceof File) {
                console.log(`  ${key}: File(${value.name}, ${value.size} bytes)`);
            } else if (typeof value === 'string' && value.length > 200) {
                console.log(`  ${key}: String (${value.length} chars)`);
            } else {
                console.log(`  ${key}: ${typeof value === 'string' ? value : JSON.stringify(value)}`);
            }
        }
        console.log('=== END DEBUG ===');

        try {
            response = await fetch(url, {
                method: 'POST', // Siempre usar POST para FormData con archivos
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });
        } catch (error) {
            throw error;
        }
    } else {
        // Si NO hay imágenes nuevas, enviar como JSON
        console.log('📤 Enviando como JSON (sin imágenes nuevas)');
        
        // Agregar datos adicionales al objeto data
        data.imagenes_a_borrar = imagenesABorrar;
        data.imagenes_existentes = imagenesSeleccionadas
            .filter(img => img.existing)
            .map(img => img.id);
        try {
            response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });
        } catch (error) {
            throw error;
        }
    }

    try {

        const result = await response.json();

        if (result.success) {
            // Limpiar localStorage después del guardado exitoso
            if (typeof limpiarStorage === 'function') {
                limpiarStorage();
            }
            
            Swal.fire({
                title: ' Éxito',
                text: result.message || 'Cotización guardada exitosamente',
                icon: 'success',
                confirmButtonText: 'Continuar'
            }).then(() => {
                window.location.href = result.redirect;
            });
        } else {
            Swal.fire({
                title: ' Error al Guardar',
                text: result.message || 'No se pudo guardar la cotización',
                icon: 'error'
            });
        }
    } catch (error) {
        Swal.fire({
            title: ' Error en la Conexión',
            text: error.message || 'No se pudo conectar con el servidor',
            icon: 'error'
        });
    } finally {
        // Re-habilitar botones
        document.querySelectorAll('button[type="submit"]').forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.cursor = 'pointer';
        });
    }
});

// ============ AUTO-GUARDADO EN BORDADO ============

// Cargar datos al iniciar
document.addEventListener('DOMContentLoaded', function() {
    // Cargar datos del borrador si existe (antes de cualquier limpieza)
    @if(isset($cotizacion) && $cotizacion)
        cargarDatosBorrador(@json($cotizacion));
    @endif

    // Crear función de guardado para bordado
    function guardarBordadoEnStorage() {
        try {
            const datos = {
                cliente: document.querySelector('[name="cliente"]')?.value || '',
                asesora: document.querySelector('[name="asesora"]')?.value || '',
                observaciones_tecnicas: document.querySelector('[name="observaciones_tecnicas"]')?.value || '',
                tecnicas: tecnicasSeleccionadas,
                observaciones_generales: observacionesGenerales,
                timestamp: new Date().toISOString()
            };

            localStorage.setItem('cotizacion_bordado_datos', JSON.stringify(datos));
        } catch (error) {
        }
    }

    // Auto-guardar cada 5 segundos
    setInterval(guardarBordadoEnStorage, 5000);

    // Guardar antes de cerrar la página
    window.addEventListener('beforeunload', function() {
        guardarBordadoEnStorage();
    });
});

// Función para cargar datos del borrador
function cargarDatosBorrador(cotizacion) {
    try {
        // Cargar cliente
        let nombreCliente = null;
        
        // Manejar si cliente es un objeto con propiedad nombre
        if (cotizacion.cliente && typeof cotizacion.cliente === 'object' && cotizacion.cliente.nombre) {
            nombreCliente = cotizacion.cliente.nombre;
        } 
        // Manejar si cliente es directamente un string
        else if (typeof cotizacion.cliente === 'string') {
            nombreCliente = cotizacion.cliente;
        }
        
        if (nombreCliente) {
            document.getElementById('header-cliente').value = nombreCliente;
            document.getElementById('cliente').value = nombreCliente;
        } else {
        }


        // Cargar técnicas
        if (cotizacion.logo_cotizacion && cotizacion.logo_cotizacion.tecnicas) {
            const tecnicas = typeof cotizacion.logo_cotizacion.tecnicas === 'string'
                ? JSON.parse(cotizacion.logo_cotizacion.tecnicas)
                : cotizacion.logo_cotizacion.tecnicas;

            if (Array.isArray(tecnicas)) {
                tecnicasSeleccionadas = tecnicas;
                // Renderizar las técnicas seleccionadas
                renderizarTecnicas();
            } else {
            }
        } else {
        }

        // Cargar observaciones técnicas
        if (cotizacion.logo_cotizacion && cotizacion.logo_cotizacion.observaciones_tecnicas) {
            document.getElementById('observaciones_tecnicas').value = cotizacion.logo_cotizacion.observaciones_tecnicas;
        }

        // Cargar tipo_venta
        if (cotizacion.logo_cotizacion && cotizacion.logo_cotizacion.tipo_venta) {
            document.getElementById('header-tipo-venta').value = cotizacion.logo_cotizacion.tipo_venta;
            document.getElementById('tipo_venta_bordado').value = cotizacion.logo_cotizacion.tipo_venta;
        }

        // Cargar observaciones generales
        if (cotizacion.logo_cotizacion && cotizacion.logo_cotizacion.observaciones_generales) {
            const observaciones = typeof cotizacion.logo_cotizacion.observaciones_generales === 'string'
                ? JSON.parse(cotizacion.logo_cotizacion.observaciones_generales)
                : cotizacion.logo_cotizacion.observaciones_generales;

            if (Array.isArray(observaciones)) {
                observaciones.forEach(obs => {
                    agregarObservacionDesdeBorrador(obs);
                });
            }
        }

        // Cargar imágenes si existen
        if (cotizacion.logo_cotizacion && cotizacion.logo_cotizacion.fotos && Array.isArray(cotizacion.logo_cotizacion.fotos)) {
            // Cargar imágenes existentes
            const imagenesNuevas = [];
            
            cotizacion.logo_cotizacion.fotos.forEach(foto => {
                if (foto.ruta_original) {
                    // Crear preview de imagen existente - usar el accessor 'url' si existe
                    const previewUrl = foto.url || ('/storage/' + (foto.ruta_miniatura || foto.ruta_original));
                    imagenesNuevas.push({
                        preview: previewUrl,
                        existing: true,
                        id: foto.id,
                        file: null  // No hay archivo para imágenes existentes
                    });
                }
            });
            
            // Reemplazar imagenesSeleccionadas con las imágenes existentes
            imagenesSeleccionadas = imagenesNuevas;
            // IMPORTANTE: NO limpiar imagenesABorrar aquí
            // Se mantiene para rastrear imágenes que el usuario quiera borrar
            renderizarImagenes();
        }

    } catch (error) {
    }
}

// Función auxiliar para agregar observaciones desde borrador
function agregarObservacionDesdeBorrador(obs) {
    const contenedor = document.getElementById('observaciones_lista');
    const fila = document.createElement('div');
    fila.style.cssText = 'display: flex; gap: 10px; align-items: center; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
    fila.innerHTML = `
        <input type="text" name="observaciones_generales[]" class="input-large" value="${obs.texto || ''}" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
        <div style="display: flex; gap: 5px; align-items: center; flex-shrink: 0;">
            <div class="obs-checkbox-mode" style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="observaciones_check[]" ${obs.tipo === 'checkbox' && obs.valor ? 'checked' : ''} style="width: 20px; height: 20px; cursor: pointer;">
            </div>
            <div class="obs-text-mode" style="${obs.tipo === 'texto' ? 'display: flex;' : 'display: none;'} flex: 1;">
                <input type="text" name="observaciones_valor[]" value="${obs.tipo === 'texto' ? obs.valor || '' : ''}" placeholder="Valor..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
            </div>
            <button type="button" class="obs-toggle-btn" style="background: ${obs.tipo === 'texto' ? '#ff9800' : '#3498db'}; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: bold; flex-shrink: 0;">${obs.tipo === 'texto' ? '✎' : '✓'}</button>
        </div>
        <button type="button" onclick="this.closest('div').remove()" style="background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 1rem; flex-shrink: 0;">✕</button>
    `;
    contenedor.appendChild(fila);

    const toggleBtn = fila.querySelector('.obs-toggle-btn');
    const checkboxMode = fila.querySelector('.obs-checkbox-mode');
    const textMode = fila.querySelector('.obs-text-mode');
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (checkboxMode.style.display === 'none') {
            checkboxMode.style.display = 'block';
            textMode.style.display = 'none';
            toggleBtn.style.background = '#3498db';
            toggleBtn.textContent = '✓';
        } else {
            checkboxMode.style.display = 'none';
            textMode.style.display = 'block';
            toggleBtn.style.background = '#ff9800';
            toggleBtn.textContent = '✎';
        }
    });
}
</script>

<!-- MODAL PARA AGREGAR PRENDAS CON TÉCNICA SELECCIONADA -->
<div id="modalAgregarTecnica" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 24px; max-width: 1000px; width: 98%; max-height: 95vh; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        
        <!-- Header del Modal -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0; font-size: 1.2rem; font-weight: 600; color: #333;">Agregar Prendas</h2>
                <p style="margin: 8px 0 0 0; color: #666; font-size: 0.85rem;">Técnica: <strong id="tecnicaSeleccionadaNombre" style="color: #333;">--</strong></p>
            </div>
            <button type="button" onclick="cerrarModalAgregarTecnica()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #ccc; padding: 0; line-height: 1; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>
        
        <!-- Lista de Prendas -->
        <div id="listaPrendas" style="margin-bottom: 16px;">
            <!-- Prendas dinámicas aquí -->
        </div>
        
        <!-- Sin prendas -->
        <div id="noPrendasMsg" style="padding: 16px; text-align: center; background: #f9f9f9; border-radius: 4px; color: #999; margin-bottom: 16px; display: block; font-size: 0.9rem;">
            <p style="margin: 0;">Agrega prendas con el botón de abajo</p>
        </div>
        
        <!-- Botón agregar prenda -->
        <button type="button" onclick="agregarFilaPrenda()" style="width: 100%; background: #f0f0f0; color: #333; border: 1px solid #ddd; font-size: 0.9rem; cursor: pointer; padding: 10px 12px; border-radius: 4px; font-weight: 500; margin-bottom: 16px; transition: background 0.2s;">
            + Agregar prenda
        </button>
        
        <!-- Botones de acción -->
        <div style="display: flex; gap: 8px; justify-content: flex-end; border-top: 1px solid #eee; padding-top: 16px;">
            <button type="button" onclick="cerrarModalAgregarTecnica()" style="background: white; color: #333; border: 1px solid #ddd; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.9rem;">
                Cancelar
            </button>
            <button type="button" onclick="guardarTecnica()" style="background: #333; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.9rem;">
                Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Validación - Seleccionar Técnica -->
<div id="modalValidacionTecnica" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1001; align-items: center; justify-content: center; flex-direction: column;">
    <div style="background: white; border-radius: 8px; padding: 40px; width: 90%; max-width: 400px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
        <div style="font-size: 3rem; margin-bottom: 20px; color: #ff9800;">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <p style="color: #333; margin-bottom: 30px; font-size: 1.1rem; font-weight: 600;">Debes seleccionar una técnica antes de agregar prendas.</p>
        <button type="button" onclick="cerrarModalValidacionTecnica()" style="background: #1e40af; color: white; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; width: 100%;">
            Entendido
        </button>
    </div>
</div>

<!-- Script de integración de técnicas -->
<script src="{{ asset('js/logo-cotizacion-tecnicas.js') }}"></script>

@endsection


