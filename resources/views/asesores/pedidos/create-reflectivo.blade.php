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

    /* Input para tipo de prenda */
    .tipo-prenda-input {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.95rem;
        background: white;
        font-family: inherit;
    }

    .tipo-prenda-input:focus {
        outline: none;
        border-color: #1e40af;
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
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

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    .shake {
        animation: shake 0.5s ease-in-out;
    }

    /* Estilos para template de prendas */
    .producto-card {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }

    .producto-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .producto-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .producto-header h4 {
        margin: 0;
        font-size: 1rem;
        color: #1e40af;
        font-weight: 700;
    }

    .producto-header-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .producto-header button {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        padding: 0.4rem 0.6rem;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .producto-header button:hover {
        background: #e2e8f0;
        border-color: #94a3b8;
    }

    .btn-eliminar-producto {
        background: #fee2e2 !important;
        color: #dc2626 !important;
        border: 1px solid #fca5a5 !important;
    }

    .btn-eliminar-producto:hover {
        background: #fecaca !important;
        border-color: #ef4444 !important;
    }

    .producto-body {
        display: block;
    }

    .producto-section {
        margin-bottom: 1.25rem;
    }

    .producto-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e40af;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .ubicacion-seccion-reflectivo,
    .observaciones-seccion-reflectivo {
        background: #f9fafb;
        padding: 0.75rem;
        border-radius: 6px;
        border: 1px solid #f1f5f9;
    }

    .ubicaciones-agregadas-reflectivo {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        width: 100%;
    }

    .observaciones-agregadas-reflectivo {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .ubicaciones-agregadas-reflectivo span,
    .observaciones-agregadas-reflectivo span {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.9rem;
        white-space: nowrap;
    }

    .fotos-preview-reflectivo {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
    }

    .foto-item-reflectivo {
        position: relative;
        aspect-ratio: 1;
        border-radius: 6px;
        overflow: hidden;
        background: #f1f5f9;
    }

    .foto-item-reflectivo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .foto-item-reflectivo .remove-foto {
        position: absolute;
        top: 4px;
        right: 4px;
        background: #dc2626;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
    }

    #prendas-contenedor {
        margin-bottom: 2rem;
    }

    /* Floating button */
    .floating-btn-reflectivo {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        color: white;
        border: none;
        cursor: pointer;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .floating-btn-reflectivo:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(14, 165, 233, 0.6);
    }

    .floating-btn-reflectivo:active {
        transform: scale(0.95);
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
                <span class="material-symbols-rounded" style="font-size: 1.75rem; color: white;">light_mode</span>
                <div>
                    <h2 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 700;">Cotización de Reflectivo</h2>
                    <p style="margin: 0.2rem 0 0 0; color: rgba(255,255,255,0.85); font-size: 0.8rem;">Completa los datos de la cotización</p>
                </div>
            </div>
            
            <!-- Campos del Header en una fila -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; grid-column: 1 / -1;">
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
            </div>
        </div>
    </div>

    <div class="form-container">
        <form id="cotizacionReflectivoForm">
            @csrf

            <!-- Campos ocultos para sincronizar con el header -->
            <input type="text" id="cliente" name="cliente" style="display: none;">
            <input type="text" id="asesora" name="asesora" value="{{ auth()->user()->name }}" readonly style="display: none;">
            <input type="date" id="fecha" name="fecha" style="display: none;">
            <textarea id="especificaciones" name="especificaciones" style="display: none;"></textarea>
            <!-- CONTENEDOR DE PRENDAS -->
            <div id="prendas-contenedor">
                <!-- Las prendas se agregan aquí dinámicamente -->
            </div>

            <!-- Botones -->
            <div class="form-actions">
                <a href="{{ route('asesores.cotizaciones.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%); border: 2px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 600; color: #333; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                    <i class="fas fa-times" style="font-size: 0.9rem;"></i> Cancelar
                </a>
                <div style="display: flex; gap: 0.5rem; flex: 1; justify-content: flex-end;">
                    <button type="submit" name="action" value="borrador" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); border: 2px solid #3d8b40; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-save" style="font-size: 0.9rem;"></i> Guardar Borrador
                    </button>
                    <button type="submit" name="action" value="enviar" class="btn btn-primary" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); border: 2px solid #003d7a; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-paper-plane" style="font-size: 0.9rem;"></i> Enviar
                    </button>
                </div>
            </div>

            <!-- MENÚ FLOTANTE PARA AGREGAR PRENDA O ESPECIFICACIONES -->
            <div style="position: fixed; bottom: 30px; right: 30px; z-index: 1000;">
                <!-- Menú flotante -->
                <div id="menuFlotante" style="display: none; position: absolute; bottom: 70px; right: 0; background: white; border-radius: 12px; box-shadow: 0 5px 40px rgba(0,0,0,0.16); overflow: hidden; min-width: 200px;">
                    <button type="button" onclick="agregarProductoPrenda(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 12px 16px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.9rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s; border-bottom: 1px solid #f0f0f0;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                        <i class="fas fa-plus" style="color: #1e40af; font-size: 1.1rem;"></i>
                        <span>Agregar Prenda</span>
                    </button>
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
        </form>
    </div>

    <!-- MODAL: ESPECIFICACIONES DEL REFLECTIVO -->
    <div id="modalEspecificaciones" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 950px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 3px solid #0284c7; padding-bottom: 1.5rem;">
                <h3 style="margin: 0; color: #0284c7; font-size: 1.4rem; font-weight: 700;"><i class="fas fa-cog" style="margin-right: 10px;"></i>ESPECIFICACIONES DEL REFLECTIVO</h3>
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
                        <tr>
                            <td><label style="margin: 0; font-size: 0.8rem;">Cúcuta</label></td>
                            <td style="text-align: center;">
                                <input type="checkbox" name="tabla_orden[cucuta]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                            </td>
                            <td style="padding: 10px;">
                                <input type="text" name="tabla_orden[cucuta_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                            </td>
                            <td style="text-align: center;">
                                <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td><label style="margin: 0; font-size: 0.8rem;">Lafayette</label></td>
                            <td style="text-align: center;">
                                <input type="checkbox" name="tabla_orden[lafayette]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                            </td>
                            <td style="padding: 10px;">
                                <input type="text" name="tabla_orden[lafayette_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                            </td>
                            <td style="text-align: center;">
                                <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td><label style="margin: 0; font-size: 0.8rem;">Fábrica</label></td>
                            <td style="text-align: center;">
                                <input type="checkbox" name="tabla_orden[fabrica]" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                            </td>
                            <td style="padding: 10px;">
                                <input type="text" name="tabla_orden[fabrica_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box;">
                            </td>
                            <td style="text-align: center;">
                                <button type="button" onclick="this.closest('tr').remove()" style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s;" title="Eliminar">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>

                    <!-- PAGO -->
                    <tr class="fila-grupo">
                        <td colspan="4" style="font-weight: 700; background: linear-gradient(135deg, #e8f4f8, #d4e9f2); padding: 12px 15px; border-left: 4px solid #0284c7;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: #0284c7;"><i class="fas fa-credit-card" style="margin-right: 8px;"></i>FORMA DE PAGO</span>
                                <button type="button" onclick="agregarFilaEspecificacion('pago')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                    <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tbody id="tbody_pago">
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
                                <span style="color: #0284c7;"><i class="fas fa-building" style="margin-right: 8px;"></i>RÉGIMEN</span>
                                <button type="button" onclick="agregarFilaEspecificacion('regimen')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                    <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tbody id="tbody_regimen">
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
                                <span style="color: #0284c7;"><i class="fas fa-chart-bar" style="margin-right: 8px;"></i>SE HA VENDIDO</span>
                                <button type="button" onclick="agregarFilaEspecificacion('vendido')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                    <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tbody id="tbody_vendido">
                        <tr>
                            <td><input type="text" name="tabla_orden[vendido_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
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
                                <span style="color: #0284c7;"><i class="fas fa-money-bill-wave" style="margin-right: 8px;"></i>ÚLTIMA VENTA</span>
                                <button type="button" onclick="agregarFilaEspecificacion('ultima_venta')" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s;">
                                    <i class="fas fa-plus" style="margin-right: 4px;"></i>AGREGAR
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tbody id="tbody_ultima_venta">
                        <tr>
                            <td><input type="text" name="tabla_orden[ultima_venta_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
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
                </tbody>
            </table>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" onclick="cerrarModalEspecificaciones()" style="padding: 0.65rem 1.5rem; background: #f1f5f9; border: 2px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-weight: 600; color: #64748b; font-size: 0.9rem; transition: all 0.2s ease;">Cancelar</button>
                <button type="button" onclick="guardarEspecificacionesReflectivo()" style="padding: 0.65rem 1.5rem; background: linear-gradient(135deg, #0284c7 0%, #0166a0 100%); border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.9rem; transition: all 0.2s ease;">Guardar Especificaciones</button>
            </div>
        </div>
    </div>

    <!-- MODAL GLOBAL PARA UBICACIÓN -->
    <div id="modalUbicacionReflectivo" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideIn 0.3s ease;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.2rem; color: #1e40af; font-weight: 700;">Agregar Ubicación</h3>
                <button type="button" onclick="cerrarModalUbicacion()" style="background: none; border: none; font-size: 1.8rem; cursor: pointer; color: #999; line-height: 1; padding: 0;">×</button>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 700; color: #334155; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">Ubicación:</label>
                <div id="modalUbicacionNombre" style="padding: 0.75rem; background: #f1f5f9; border: 2px solid #0ea5e9; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 1rem;">PECHO</div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 700; color: #334155; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">Observación/Detalles:</label>
                <textarea id="modalUbicacionTextarea" placeholder="Ej: Franja vertical de 10cm, color plateado, lado izquierdo..." style="width: 100%; padding: 0.75rem; border: 2px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; resize: vertical; min-height: 120px; font-family: inherit; transition: all 0.2s ease;" onkeydown="if(event.key==='Escape') cerrarModalUbicacion()"></textarea>
            </div>
            
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" onclick="cerrarModalUbicacion()" style="padding: 0.65rem 1.5rem; background: #f1f5f9; border: 2px solid #cbd5e1; border-radius: 6px; cursor: pointer; font-weight: 600; color: #64748b; font-size: 0.9rem; transition: all 0.2s ease;">Cancelar</button>
                <button type="button" onclick="guardarUbicacionReflectivo()" style="padding: 0.65rem 1.5rem; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.9rem; transition: all 0.2s ease;">Guardar Ubicación</button>
            </div>
        </div>
    </div>

    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</div>
</div>

<!-- TEMPLATE PARA PRODUCTO DE REFLECTIVO -->
<template id="productoReflectivoTemplate">
    <div class="producto-card" data-producto-id="">
        <div class="producto-header">
            <h4 class="producto-titulo">PRENDA <span class="numero-producto">1</span></h4>
            <div class="producto-header-buttons">
                <button type="button" class="btn-toggle-product" onclick="toggleProductoBody(this)" title="Expandir/Contraer" style="font-size: 1rem; padding: 0.4rem 0.6rem;">▼</button>
                <button type="button" class="btn-remove-product btn-eliminar-producto" onclick="eliminarProductoPrenda(this)" title="Eliminar prenda">✕</button>
            </div>
        </div>
        <div class="producto-body" style="display: block;">
            <!-- TIPO DE PRENDA -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-tshirt"></i> TIPO DE PRENDA</div>
                <input type="text" name="productos_reflectivo[][tipo_prenda]" class="tipo-prenda-input" placeholder="Ej: Camiseta, Pantalón, Chaqueta...">
            </div>

            <!-- DESCRIPCIÓN -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-sticky-note"></i> DESCRIPCIÓN</div>
                <textarea name="productos_reflectivo[][descripcion]" class="input-large" placeholder="Describe el reflectivo para esta prenda (tipo, tamaño, color, ubicación, etc.)..." rows="3" style="font-size: 0.9rem; width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; resize: vertical;"></textarea>
            </div>

            <!-- IMÁGENES -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-images"></i> IMÁGENES (MÁXIMO 3)</div>
                <label style="display: block; min-height: 50px; padding: 0.75rem; border: 2px dashed #0ea5e9; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="event.preventDefault(); if(event.dataTransfer.files) this.querySelector('input').files = event.dataTransfer.files; this.querySelector('input').onchange && this.querySelector('input').onchange();" ondragover="event.preventDefault(); this.style.background='#e8f4f8';" ondragleave="this.style.background='#f0f7ff';">
                    <input type="file" name="productos_reflectivo[][imagenes][]" class="input-file-reflectivo" accept="image/*" multiple onchange="agregarFotosAlProductoReflectivo(this)" style="display: none;">
                    <div class="drop-zone-content" style="font-size: 0.75rem;">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 0.9rem; color: #0ea5e9; display: block; margin-bottom: 0.2rem;"></i>
                        <p style="margin: 0.1rem 0; color: #0ea5e9; font-weight: 500; font-size: 0.8rem;">ARRASTRA IMÁGENES AQUÍ O HAZ CLIC</p>
                        <small style="color: #666; font-size: 0.7rem;">(Máx. 3)</small>
                    </div>
                </label>
                <div class="fotos-preview-reflectivo" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-top: 0.75rem;"></div>
            </div>

            <!-- UBICACIÓN -->
            <div class="producto-section">
                <div class="section-title" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleSeccionReflectivo(this)">
                    <span><i class="fas fa-map-marker-alt"></i> UBICACIÓN</span>
                    <i class="fas fa-chevron-down" style="transition: transform 0.3s ease;"></i>
                </div>
                <div class="ubicacion-seccion-reflectivo" style="display: block;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #1e40af; font-size: 0.9rem;">Selecciona la sección a agregar:</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <input type="text" class="ubicacion-input-reflectivo" placeholder="Ej: PECHO, ESPALDA, MANGA, etc." style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                        <button type="button" class="btn-add-ubicacion-reflectivo" onclick="abrirModalUbicacion(this)" style="background: #0ea5e9; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-weight: 600;">+</button>
                    </div>
                    <div class="ubicaciones-agregadas-reflectivo" style="display: flex; flex-wrap: wrap; gap: 0.5rem;"></div>
                </div>
            </div>


        </div>
    </div>
</template>

<script>
// Arrays y variables globales
let contadorProductosReflectivo = 0;

// FUNCIÓN PARA ABRIR MODAL ESPECIFICACIONES
function abrirModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    const especificacionesGuardadas = document.getElementById('especificaciones').value;
    
    // Si hay especificaciones guardadas, cargarlas en los checkboxes y observaciones
    if (especificacionesGuardadas) {
        try {
            const datos = JSON.parse(especificacionesGuardadas);
            // Cargar checkboxes
            Object.keys(datos).forEach((key) => {
                const element = document.querySelector(`[name="${key}"]`);
                if (element) {
                    if (element.type === 'checkbox') {
                        element.checked = datos[key] === '1' || datos[key] === true;
                    } else {
                        element.value = datos[key] || '';
                    }
                }
            });
        } catch (e) {
            console.error('Error al cargar especificaciones:', e);
        }
    }
    
    if (modal) {
        modal.style.display = 'flex';
    }
}

// FUNCIÓN PARA CERRAR MODAL ESPECIFICACIONES
function cerrarModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    if (modal) {
        modal.style.display = 'none';
    }
}

// FUNCIÓN PARA GUARDAR ESPECIFICACIONES
function guardarEspecificacionesReflectivo() {
    const especificaciones = {};
    
    // Recopilar datos de checkboxes y observaciones
    document.querySelectorAll('[name^="reflectivo_"]').forEach((element) => {
        if (element.type === 'checkbox') {
            especificaciones[element.name] = element.checked ? '1' : '0';
        } else if (element.type === 'text') {
            especificaciones[element.name] = element.value || '';
        }
    });
    
    // Guardar como JSON en el campo oculto
    document.getElementById('especificaciones').value = JSON.stringify(especificaciones);
    cerrarModalEspecificaciones();
}

// FUNCIÓN PARA AGREGAR FILA DE ESPECIFICACIÓN
function agregarFilaEspecificacion(seccion) {
    console.log('Agregar fila:', seccion);
}

// FUNCIONES PARA AGREGAR/ELIMINAR PRODUCTOS DE REFLECTIVO
function agregarProductoPrenda() {
    contadorProductosReflectivo++;
    const template = document.getElementById('productoReflectivoTemplate');
    const clone = template.content.cloneNode(true);
    
    // Actualizar el número de prenda
    clone.querySelector('.numero-producto').textContent = contadorProductosReflectivo;
    
    // Agregar al contenedor
    document.getElementById('prendas-contenedor').appendChild(clone);
}

function eliminarProductoPrenda(button) {
    const card = button.closest('.producto-card');
    card.remove();
    renumerarPrendas();
}

function renumerarPrendas() {
    const prendas = document.querySelectorAll('.producto-card');
    prendas.forEach((prenda, index) => {
        prenda.querySelector('.numero-producto').textContent = index + 1;
    });
}

function toggleProductoBody(button) {
    const body = button.closest('.producto-card').querySelector('.producto-body');
    body.style.display = body.style.display === 'none' ? 'block' : 'none';
    button.textContent = body.style.display === 'none' ? '▶' : '▼';
}

function toggleSeccionReflectivo(titleElement) {
    const icon = titleElement.querySelector('.fa-chevron-down');
    const secciones = titleElement.parentElement.nextElementSibling;
    
    if (secciones) {
        secciones.style.display = secciones.style.display === 'none' ? 'block' : 'none';
        if (icon) {
            icon.style.transform = secciones.style.display === 'none' ? 'rotate(0deg)' : 'rotate(180deg)';
        }
    }
}

function agregarFotosAlProductoReflectivo(input) {
    const files = input.files;
    const preview = input.closest('.producto-section').querySelector('.fotos-preview-reflectivo');
    const previewCount = preview.querySelectorAll('img').length;
    
    if (previewCount + files.length > 3) {
        alert('Máximo 3 imágenes permitidas');
        return;
    }
    
    Array.from(files).forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.style.cssText = 'position: relative; border-radius: 6px; overflow: hidden; aspect-ratio: 1;';
                div.innerHTML = `
                    <img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
                    <button type="button" onclick="this.parentElement.remove()" style="position: absolute; top: 2px; right: 2px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; justify-content: center;">×</button>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        }
    });
}

function abrirModalUbicacion(button) {
    const input = button.previousElementSibling;
    const ubicacion = input.value.trim();
    
    if (!ubicacion) {
        input.style.border = '2px solid #ef4444';
        setTimeout(() => input.style.border = '', 1500);
        return;
    }
    
    // Guardar el botón e input para usar después
    window.ubicacionModalData = {
        button: button,
        input: input,
        ubicacion: ubicacion,
        prenda: button.closest('.producto-card')
    };
    
    // Mostrar modal con la ubicación escrita
    document.getElementById('modalUbicacionNombre').textContent = ubicacion;
    document.getElementById('modalUbicacionTextarea').value = '';
    document.getElementById('modalUbicacionReflectivo').style.display = 'flex';
    
    // Focus en el textarea
    setTimeout(() => document.getElementById('modalUbicacionTextarea').focus(), 100);
}

function cerrarModalUbicacion() {
    document.getElementById('modalUbicacionReflectivo').style.display = 'none';
}

function guardarUbicacionReflectivo() {
    const textarea = document.getElementById('modalUbicacionTextarea');
    const observacion = textarea.value.trim();
    
    if (!window.ubicacionModalData) return;
    
    const { ubicacion, prenda } = window.ubicacionModalData;
    const container = prenda.querySelector('.ubicaciones-agregadas-reflectivo');
    
    if (!container) return;
    
    // Crear elemento de ubicación como componente expandible
    const item = document.createElement('div');
    item.className = 'ubicacion-item-reflectivo'; // ADD CLASS FOR EASY IDENTIFICATION
    item.style.cssText = 'background: white; border: 2px solid #0ea5e9; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; width: 100%; box-shadow: 0 2px 4px rgba(14, 165, 233, 0.15); position: relative;';
    
    const header = document.createElement('div');
    header.className = 'ubicacion-header-reflectivo'; // ADD CLASS
    header.style.cssText = 'display: flex; justify-content: space-between; align-items: center; cursor: pointer;';
    header.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
            <span style="color: #0ea5e9; font-weight: 700; font-size: 1rem;">📍</span>
            <span class="ubicacion-nombre-reflectivo" style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">${ubicacion}</span>
        </div>
        <span style="color: #0ea5e9; font-size: 1.2rem; transition: transform 0.3s ease;" class="ubicacion-toggle">▼</span>
    `;
    
    const body = document.createElement('div');
    body.className = 'ubicacion-body-reflectivo'; // ADD CLASS
    body.style.cssText = 'display: block; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0;';
    body.innerHTML = `
        <p style="margin: 0 0 0.5rem 0; color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Descripción:</p>
        <p class="ubicacion-descripcion-reflectivo" style="margin: 0; color: #334155; font-size: 0.9rem; line-height: 1.5;">${observacion || 'Sin descripción adicional'}</p>
    `;
    
    const deleteBtn = document.createElement('button');
    deleteBtn.type = 'button';
    deleteBtn.style.cssText = 'position: absolute; top: 0.5rem; right: 0.5rem; background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: bold;';
    deleteBtn.textContent = '×';
    deleteBtn.onclick = (e) => {
        e.stopPropagation();
        item.remove();
    };
    
    item.appendChild(header);
    header.appendChild(deleteBtn);
    item.appendChild(body);
    
    // Toggle para expandir/contraer
    let expanded = true;
    header.addEventListener('click', () => {
        expanded = !expanded;
        body.style.display = expanded ? 'block' : 'none';
        header.querySelector('.ubicacion-toggle').style.transform = expanded ? 'rotate(0deg)' : 'rotate(-90deg)';
    });
    
    container.appendChild(item);
    
    // Limpiar y cerrar modal
    window.ubicacionModalData.input.value = '';
    cerrarModalUbicacion();
}

// Sincronizar valores del header con el formulario
document.getElementById('header-cliente').addEventListener('input', function() {
    document.getElementById('cliente').value = this.value;
});

document.getElementById('header-fecha').addEventListener('change', function() {
    document.getElementById('fecha').value = this.value;
});

// Envío del formulario
document.getElementById('cotizacionReflectivoForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Sincronizar valores del header
    const cliente = document.getElementById('header-cliente').value.trim();
    const fecha = document.getElementById('header-fecha').value;

    if (!cliente || !fecha) {
        alert('⚠️ Completa el Cliente y la Fecha');
        return;
    }

    // Recopilar prendas del DOM
    const prendas = [];
    document.querySelectorAll('.producto-card').forEach((card) => {
        const tipoPrenda = card.querySelector('input[name="productos_reflectivo[][tipo_prenda]"]')?.value.trim() || '';
        const descripcion = card.querySelector('textarea[name="productos_reflectivo[][descripcion]"]')?.value.trim() || '';
        
        if (tipoPrenda) {
            prendas.push({
                tipo: tipoPrenda,
                descripcion: descripcion
            });
        }
    });

    if (prendas.length === 0) {
        alert('⚠️ Debes agregar al menos una PRENDA con TIPO');
        return;
    }

    // Recopilar ubicaciones del DOM usando las clases CSS
    const ubicaciones = [];
    const ubicacionesContainer = document.querySelector('.ubicaciones-agregadas-reflectivo');
    if (ubicacionesContainer) {
        // Buscar todos los items de ubicación usando la clase CSS
        ubicacionesContainer.querySelectorAll('.ubicacion-item-reflectivo').forEach((item) => {
            const nombreSpan = item.querySelector('.ubicacion-nombre-reflectivo');
            const descripcionP = item.querySelector('.ubicacion-descripcion-reflectivo');
            
            if (nombreSpan && descripcionP) {
                const ubicacionText = nombreSpan.textContent.trim();
                const descripcion = descripcionP.textContent.trim();
                
                console.log('🔍 RECOPILANDO - Ubicación encontrada:', {
                    ubicacion: ubicacionText,
                    descripcion: descripcion
                });
                
                if (ubicacionText && ubicacionText !== 'Sin descripción adicional') {
                    ubicaciones.push({
                        ubicacion: ubicacionText,
                        descripcion: descripcion
                    });
                }
            }
        });
        console.log('📦 Total de ubicaciones recopiladas:', ubicaciones.length);
    }

    const submitButton = e.submitter;
    const action = submitButton ? submitButton.value : 'borrador';

    // Preparar FormData
    const formData = new FormData();
    formData.append('cliente', cliente);
    formData.append('asesora', document.getElementById('asesora').value);
    formData.append('fecha', fecha);
    formData.append('action', action);
    formData.append('tipo', 'RF');
    formData.append('prendas', JSON.stringify(prendas)); // Enviar como JSON string
    formData.append('especificaciones', document.getElementById('especificaciones').value || '');
    formData.append('descripcion_reflectivo', document.getElementById('descripcion_reflectivo')?.value || 'Reflectivo');
    formData.append('ubicaciones_reflectivo', JSON.stringify(ubicaciones)); // Enviar ubicaciones recopiladas
    formData.append('observaciones_generales', JSON.stringify([]));

    // DEBUG: Log de ubicaciones que se van a enviar
    console.log('🚀 ENVIAR FORMULARIO - Ubicaciones que se enviarán:');
    console.log('   Cantidad:', ubicaciones.length);
    console.log('   Data:', JSON.stringify(ubicaciones, null, 2));
    console.log('   FormData value:', formData.get('ubicaciones_reflectivo'));

    // Agregar imágenes por prenda (si las hay)
    document.querySelectorAll('.input-file-reflectivo').forEach((input, index) => {
        if (input.files.length > 0) {
            Array.from(input.files).forEach((file) => {
                formData.append('imagenes_reflectivo[]', file);
            });
        }
    });

    try {
        const response = await fetch('{{ route("asesores.cotizaciones.reflectivo.guardar") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ Cotización guardada exitosamente');
            window.location.href = '{{ route("asesores.cotizaciones.index") }}';
        } else {
            alert('❌ Error: ' + (result.message || 'Error al guardar'));
            console.error('Errores:', result.errores || result);
        }
    } catch (error) {
        alert('❌ Error de conexión: ' + error.message);
        console.error('Error:', error);
    }
});

// Agregar PRENDA 1 por default al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Si hay datos iniciales (edición), cargarlos
    const datosIniciales = {!! isset($datosIniciales) ? $datosIniciales : 'null' !!};
    
    console.log('📋 Datos iniciales recibidos:', datosIniciales);
    
    if (datosIniciales) {
        try {
            // Cargar cliente
            if (datosIniciales.cliente) {
                const nombreCliente = datosIniciales.cliente.nombre || datosIniciales.cliente;
                console.log('👤 Cargando cliente:', nombreCliente);
                document.getElementById('header-cliente').value = nombreCliente;
                document.getElementById('cliente').value = nombreCliente;
            }
            
            // Cargar fecha
            if (datosIniciales.fecha_inicio) {
                const fecha = new Date(datosIniciales.fecha_inicio);
                const fechaFormato = fecha.toISOString().split('T')[0];
                console.log('📅 Cargando fecha:', fechaFormato);
                document.getElementById('header-fecha').value = fechaFormato;
                document.getElementById('fecha').value = fechaFormato;
            }
            
            // Cargar especificaciones
            if (datosIniciales.especificaciones) {
                console.log('⚙️ Cargando especificaciones:', datosIniciales.especificaciones);
                document.getElementById('especificaciones').value = JSON.stringify(datosIniciales.especificaciones);
            }
            
            // Cargar prendas (reflectivo)
            if (datosIniciales.prendas && datosIniciales.prendas.length > 0) {
                console.log('👔 Cargando', datosIniciales.prendas.length, 'prendas');
                // Limpiar la prenda por defecto
                const contenedor = document.getElementById('prendas-contenedor');
                contenedor.innerHTML = '';
                
                // Agregar cada prenda
                datosIniciales.prendas.forEach((prenda, index) => {
                    console.log('  - Prenda', index + 1, ':', prenda);
                    contadorProductosReflectivo++;
                    const template = document.getElementById('productoReflectivoTemplate');
                    const clone = template.content.cloneNode(true);
                    
                    // Actualizar número
                    clone.querySelector('.numero-producto').textContent = contadorProductosReflectivo;
                    
                    // Cargar tipo de prenda
                    const tipoInput = clone.querySelector('[name*="tipo_prenda"]');
                    if (tipoInput && prenda.nombre_producto) {
                        tipoInput.value = prenda.nombre_producto;
                        console.log('    ✓ Tipo:', prenda.nombre_producto);
                    }
                    
                    // Cargar descripción
                    const descInput = clone.querySelector('[name*="descripcion"]');
                    if (descInput && prenda.descripcion) {
                        descInput.value = prenda.descripcion;
                        console.log('    ✓ Descripción:', prenda.descripcion);
                    }
                    
                    // Cargar fotos
                    if (prenda.fotos && prenda.fotos.length > 0) {
                        console.log('    ✓ Fotos:', prenda.fotos.length);
                        const fotosContainer = clone.querySelector('.fotos-preview-reflectivo');
                        const fileInput = clone.querySelector('.input-file-reflectivo');
                        
                        prenda.fotos.forEach((foto) => {
                            const imgDiv = document.createElement('div');
                            imgDiv.style.position = 'relative';
                            imgDiv.innerHTML = `
                                <img src="${foto.url}" style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px;">
                                <button type="button" class="btn-eliminar-foto" onclick="this.parentElement.remove()" style="position: absolute; top: 2px; right: 2px; background: red; color: white; border: none; padding: 2px 6px; border-radius: 3px; cursor: pointer; font-size: 0.7rem;">✕</button>
                            `;
                            fotosContainer.appendChild(imgDiv);
                        });
                    }
                    
                    contenedor.appendChild(clone);
                });
                console.log('✅ Prendas cargadas correctamente');
            } else {
                console.log('⚠️ No hay prendas, agregando una por defecto');
                agregarProductoPrenda();
            }
            
            // Cargar fotos del reflectivo (si existe)
            console.log('🔍 Buscando reflectivo en datosIniciales...');
            console.log('   reflectivo_cotizacion:', datosIniciales.reflectivo_cotizacion ? 'EXISTE' : 'NO');
            console.log('   reflectivo:', datosIniciales.reflectivo ? 'EXISTE' : 'NO');
            
            // Intentar ambas formas: reflectivo_cotizacion o reflectivo
            const reflectivo = datosIniciales.reflectivo_cotizacion || datosIniciales.reflectivo;
            
            if (reflectivo && reflectivo.fotos && reflectivo.fotos.length > 0) {
                console.log('📸 REFLECTIVO - Cargando', reflectivo.fotos.length, 'fotos');
                const fotosContainer = document.querySelector('.fotos-preview-reflectivo');
                
                if (fotosContainer) {
                    reflectivo.fotos.forEach((foto, index) => {
                        console.log(`    ✓ Foto ${index + 1}:`, foto);
                        const imgDiv = document.createElement('div');
                        imgDiv.style.position = 'relative';
                        imgDiv.innerHTML = `
                            <img src="${foto.url}" style="width: 100%; height: 100px; object-fit: cover; border-radius: 4px;">
                            <button type="button" class="btn-eliminar-foto" onclick="this.parentElement.remove()" style="position: absolute; top: 2px; right: 2px; background: red; color: white; border: none; padding: 2px 6px; border-radius: 3px; cursor: pointer; font-size: 0.7rem;">✕</button>
                        `;
                        fotosContainer.appendChild(imgDiv);
                    });
                    console.log('✅ Fotos del reflectivo cargadas correctamente');
                } else {
                    console.warn('⚠️ Contenedor .fotos-preview-reflectivo no encontrado');
                }
            } else {
                console.log('ℹ️ No hay fotos de reflectivo para cargar');
                if (!reflectivo) {
                    console.warn('  Reflectivo no encontrado en datosIniciales');
                } else if (!reflectivo.fotos) {
                    console.warn('  reflectivo.fotos no existe');
                } else {
                    console.warn('  reflectivo.fotos está vacío, length:', reflectivo.fotos.length);
                }
            }
            
            // Cargar descripción del reflectivo (si existe)
            if (reflectivo && reflectivo.descripcion) {
                console.log('📝 REFLECTIVO - Cargando descripción');
                const descInput = document.getElementById('descripcion_reflectivo');
                if (descInput) {
                    descInput.value = reflectivo.descripcion;
                    console.log('    ✓ Descripción:', reflectivo.descripcion);
                }
            }
            
            // Cargar ubicación del reflectivo (si existe)
            if (reflectivo && reflectivo.ubicacion) {
                console.log('📍 REFLECTIVO - Cargando ubicación');
                console.log('   Type:', typeof reflectivo.ubicacion);
                console.log('   Value:', reflectivo.ubicacion);
                try {
                    const ubicacionData = typeof reflectivo.ubicacion === 'string' 
                        ? JSON.parse(reflectivo.ubicacion)
                        : reflectivo.ubicacion;
                    
                    console.log('   Después de parsear:', ubicacionData);
                    console.log('   Es Array?:', Array.isArray(ubicacionData));
                    console.log('   Length:', Array.isArray(ubicacionData) ? ubicacionData.length : 'N/A');
                    
                    if (Array.isArray(ubicacionData) && ubicacionData.length > 0) {
                        const contenedor = document.querySelector('.ubicaciones-agregadas-reflectivo');
                        if (!contenedor) {
                            console.warn('⚠️ Contenedor .ubicaciones-agregadas-reflectivo no encontrado');
                        } else {
                            ubicacionData.forEach(ubi => {
                                if (ubi && ubi.ubicacion) {
                                    // Crear elemento con la MISMA estructura que guardarUbicacionReflectivo()
                                    const item = document.createElement('div');
                                    item.className = 'ubicacion-item-reflectivo'; // USE CLASS
                                    item.style.cssText = 'background: white; border: 2px solid #0ea5e9; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; width: 100%; box-shadow: 0 2px 4px rgba(14, 165, 233, 0.15); position: relative;';
                                    
                                    const header = document.createElement('div');
                                    header.className = 'ubicacion-header-reflectivo'; // USE CLASS
                                    header.style.cssText = 'display: flex; justify-content: space-between; align-items: center; cursor: pointer;';
                                    header.innerHTML = `
                                        <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                                            <span style="color: #0ea5e9; font-weight: 700; font-size: 1rem;">📍</span>
                                            <span class="ubicacion-nombre-reflectivo" style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">${ubi.ubicacion}</span>
                                        </div>
                                        <span style="color: #0ea5e9; font-size: 1.2rem; transition: transform 0.3s ease;" class="ubicacion-toggle">▼</span>
                                    `;
                                    
                                    const body = document.createElement('div');
                                    body.className = 'ubicacion-body-reflectivo'; // USE CLASS
                                    body.style.cssText = 'display: block; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0;';
                                    body.innerHTML = `
                                        <p style="margin: 0 0 0.5rem 0; color: #64748b; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Descripción:</p>
                                        <p class="ubicacion-descripcion-reflectivo" style="margin: 0; color: #334155; font-size: 0.9rem; line-height: 1.5;">${ubi.descripcion || 'Sin descripción adicional'}</p>
                                    `;
                                    
                                    const deleteBtn = document.createElement('button');
                                    deleteBtn.type = 'button';
                                    deleteBtn.style.cssText = 'position: absolute; top: 0.5rem; right: 0.5rem; background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: bold;';
                                    deleteBtn.textContent = '×';
                                    deleteBtn.onclick = (e) => {
                                        e.stopPropagation();
                                        item.remove();
                                    };
                                    
                                    item.appendChild(header);
                                    header.appendChild(deleteBtn);
                                    item.appendChild(body);
                                    
                                    // Toggle para expandir/contraer
                                    let expanded = true;
                                    header.addEventListener('click', () => {
                                        expanded = !expanded;
                                        body.style.display = expanded ? 'block' : 'none';
                                        header.querySelector('.ubicacion-toggle').style.transform = expanded ? 'rotate(0deg)' : 'rotate(-90deg)';
                                    });
                                    
                                    contenedor.appendChild(item);
                                }
                            });
                            console.log('    ✓ Ubicaciones cargadas:', ubicacionData.length);
                        }
                    } else {
                        console.log('   ℹ️ ubicacionData está vacío o no es array');
                    }
                } catch (e) {
                    console.warn('⚠️ Error al cargar ubicación:', e);
                }
            } else {
                console.log('ℹ️ No hay ubicación en reflectivo');
            }
        } catch (e) {
            console.error('❌ Error cargando datos iniciales:', e);
            console.error('Stack:', e.stack);
            agregarProductoPrenda();
        }
    } else {
        console.log('ℹ️ No hay datos iniciales, agregando prenda por defecto');
        agregarProductoPrenda();
    }
});
</script>

@endsection
