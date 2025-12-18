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
        <form id="cotizacionReflectivoForm">
            @csrf

            <!-- Campos ocultos para sincronizar con el header -->
            <input type="text" id="cliente" name="cliente" style="display: none;">
            <input type="text" id="asesora" name="asesora" value="{{ auth()->user()->name }}" readonly style="display: none;">
            <input type="date" id="fecha" name="fecha" style="display: none;">
            <input type="text" id="tipo_venta_reflectivo" name="tipo_venta_reflectivo" style="display: none;">
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

    <!-- MODAL DE CONFIRMACIÓN DE ELIMINACIÓN -->
    <div id="modalConfirmarEliminar" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10002; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; padding: 2.5rem; max-width: 450px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); text-align: center; animation: slideIn 0.3s ease;">
            <div style="font-size: 3.5rem; margin-bottom: 1.5rem; color: #ef4444;">⚠️</div>
            <h2 style="margin: 0 0 1rem 0; font-size: 1.4rem; color: #1e293b; font-weight: 700;">¿Eliminar esta foto?</h2>
            <p style="margin: 0 0 2rem 0; color: #64748b; font-size: 0.95rem; line-height: 1.6;">Esta acción no se puede deshacer. La foto será eliminada definitivamente de todos lados.</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button type="button" onclick="cerrarModalConfirmarEliminar()" style="padding: 0.75rem 1.5rem; background: #f1f5f9; border: 2px solid #cbd5e1; border-radius: 8px; cursor: pointer; font-weight: 600; color: #64748b; font-size: 0.95rem; transition: all 0.2s ease;">Cancelar</button>
                <button type="button" id="btnConfirmarEliminar" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; border-radius: 8px; cursor: pointer; font-weight: 600; color: white; font-size: 0.95rem; transition: all 0.2s ease;">Eliminar</button>
            </div>
        </div>
    </div>

    <!-- MODAL DE ÉXITO -->
    <div id="modalExito" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10001; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; padding: 3rem; max-width: 500px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); text-align: center; animation: slideIn 0.3s ease;">
            <div style="font-size: 4rem; margin-bottom: 1.5rem; color: #10b981;">✓</div>
            <h2 id="modalExitoTitulo" style="margin: 0 0 1rem 0; font-size: 1.5rem; color: #1e293b; font-weight: 700;">Cotización guardada exitosamente</h2>
            <p id="modalExitoMensaje" style="margin: 0 0 1.5rem 0; color: #64748b; font-size: 1rem; line-height: 1.6;"></p>
            <div id="modalExitoNumero" style="display: none; margin: 1.5rem 0; padding: 1rem; background: #f0fdf4; border: 2px solid #10b981; border-radius: 8px;">
                <p style="margin: 0 0 0.5rem 0; color: #64748b; font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Número de Cotización:</p>
                <p id="modalExitoNumeroCotizacion" style="margin: 0; font-size: 1.8rem; color: #10b981; font-weight: 700;"></p>
            </div>
            <button type="button" onclick="cerrarModalExito()" style="padding: 0.75rem 2rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 8px; cursor: pointer; font-weight: 600; color: white; font-size: 1rem; transition: all 0.2s ease;">Aceptar</button>
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
                    <input type="file" class="input-file-reflectivo" accept="image/*" multiple onchange="agregarFotosAlProductoReflectivo(this)" style="display: none;">
                    <div class="drop-zone-content" style="font-size: 0.75rem;">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 0.9rem; color: #0ea5e9; display: block; margin-bottom: 0.2rem;"></i>
                        <p style="margin: 0.1rem 0; color: #0ea5e9; font-weight: 500; font-size: 0.8rem;">ARRASTRA IMÁGENES AQUÍ O HAZ CLIC</p>
                        <small style="color: #666; font-size: 0.7rem;">(Máx. 3)</small>
                    </div>
                </label>
                <div class="fotos-preview-reflectivo" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-top: 0.75rem;"></div>
            </div>

            <!-- TALLAS A COTIZAR -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-ruler"></i> TALLAS A COTIZAR</div>
                <div class="form-row">
                    <div class="form-col full">
                        <!-- Input oculto para guardar el género seleccionado -->
                        <input type="hidden" name="productos_reflectivo[][variantes][genero_id]" class="genero-id-hidden-reflectivo" value="">
                        
                        <!-- Fila 1: Selectores de tipo, género y modo -->
                        <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
                            <select class="talla-tipo-select-reflectivo" onchange="actualizarSelectTallasReflectivo(this)" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 300px;">
                                <option value="">Selecciona tipo de talla</option>
                                <option value="letra">LETRAS (XS, S, M, L, XL...)</option>
                                <option value="numero">NÚMEROS (DAMA/CABALLERO)</option>
                            </select>
                            
                            <select class="talla-genero-select-reflectivo" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 200px; display: none;">
                                <option value="">Selecciona género</option>
                                <option value="dama">Dama</option>
                                <option value="caballero">Caballero</option>
                            </select>
                            
                            <select class="talla-modo-select-reflectivo" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 200px; display: none;">
                                <option value="">Selecciona modo</option>
                                <option value="manual">Manual</option>
                                <option value="rango">Rango (Desde-Hasta)</option>
                            </select>
                            
                            <!-- Selectores de rango (aparecen cuando se selecciona Rango) -->
                            <div class="talla-rango-selectors-reflectivo" style="display: none; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                                <select class="talla-desde-reflectivo" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 150px;">
                                    <option value="">Desde</option>
                                </select>
                                <span style="color: #0066cc; font-weight: 600;">hasta</span>
                                <select class="talla-hasta-reflectivo" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 150px;">
                                    <option value="">Hasta</option>
                                </select>
                                <button type="button" class="btn-agregar-rango-reflectivo" onclick="agregarTallasRangoReflectivo(this)" style="padding: 0.6rem 1rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; white-space: nowrap;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Fila 2: Botones de tallas (Modo Manual) -->
                        <div class="talla-botones-reflectivo" style="display: none; margin-bottom: 1.5rem;">
                            <p style="margin: 0 0 0.75rem 0; font-size: 0.85rem; font-weight: 600; color: #0066cc;">Selecciona tallas:</p>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                                <div class="talla-botones-container-reflectivo" style="display: flex; flex-wrap: wrap; gap: 0.5rem; flex: 1;">
                                </div>
                                <button type="button" class="btn-agregar-tallas-seleccionadas-reflectivo" onclick="agregarTallasSeleccionadasReflectivo(this)" style="padding: 0.6rem 1rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; white-space: nowrap; flex-shrink: 0;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Fila 3: Tallas agregadas -->
                        <div class="tallas-section-reflectivo" style="display: none; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 0.75rem 0; font-size: 0.85rem; font-weight: 600; color: #0066cc;">Tallas seleccionadas:</p>
                            <div class="tallas-agregadas-reflectivo" style="display: flex; flex-wrap: wrap; gap: 0.5rem; min-height: 35px;">
                            </div>
                            <input type="hidden" name="productos_reflectivo[][tallas]" class="tallas-hidden-reflectivo" value="">
                        </div>
                    </div>
                </div>
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
    
    console.log('🔓 Abriendo modal de especificaciones');
    console.log('📋 Especificaciones guardadas en campo:', especificacionesGuardadas);
    
    // Si hay especificaciones guardadas, cargarlas en los checkboxes y observaciones
    if (especificacionesGuardadas && especificacionesGuardadas !== '{}' && especificacionesGuardadas !== '[]' && especificacionesGuardadas !== '') {
        try {
            const datos = JSON.parse(especificacionesGuardadas);
            console.log('✅ Datos parseados:', datos);
            console.log('📊 Estructura de datos:', Object.keys(datos));
            
            // FORMATO 1: Estructura con forma_pago, disponibilidad, etc (desde cotizaciones.especificaciones)
            // FORMATO 2: Estructura tabla_orden[field] (desde modal anterior)
            
            // Si tiene estructura de array (forma_pago, disponibilidad, etc)
            if (datos.forma_pago || datos.disponibilidad || datos.regimen) {
                console.log('📦 Detectado FORMATO COTIZACIONES - JSON estructurado');
                
                // Procesar FORMA_PAGO
                if (datos.forma_pago && Array.isArray(datos.forma_pago)) {
                    console.log('💳 Procesando forma_pago:', datos.forma_pago);
                    datos.forma_pago.forEach((pago) => {
                        // Normalizar el valor para buscar checkbox
                        let valorNormalizado = pago.valor.toLowerCase();
                        if (valorNormalizado === 'crédito' || valorNormalizado === 'credito') {
                            valorNormalizado = 'credito';
                        }
                        
                        const checkboxName = `tabla_orden[${valorNormalizado}]`;
                        let checkbox = document.querySelector(`[name="${checkboxName}"]`);
                        
                        console.log(`  🔍 Buscando checkbox con nombre: "${checkboxName}" → ${checkbox ? 'ENCONTRADO' : 'NO ENCONTRADO'}`);
                        
                        if (checkbox) {
                            checkbox.checked = true;
                            console.log(`  ✓ Checkbox forma_pago "${pago.valor}" marcado`);
                            
                            // Cargar observación si existe
                            if (pago.observacion) {
                                let obsName;
                                if (valorNormalizado === 'contado') {
                                    obsName = 'tabla_orden[pago_contado_obs]';
                                } else if (valorNormalizado === 'credito') {
                                    obsName = 'tabla_orden[pago_credito_obs]';
                                }
                                
                                const obsInput = document.querySelector(`[name="${obsName}"]`);
                                if (obsInput) {
                                    obsInput.value = pago.observacion;
                                    console.log(`  ✓ Observación "${pago.valor}" cargada: "${pago.observacion}"`);
                                }
                            }
                        }
                    });
                }
                
                // Procesar DISPONIBILIDAD
                if (datos.disponibilidad && Array.isArray(datos.disponibilidad)) {
                    console.log('📦 Procesando disponibilidad:', datos.disponibilidad);
                    datos.disponibilidad.forEach((disp) => {
                        const valorNormalizado = disp.valor.toLowerCase();
                        const checkboxName = `tabla_orden[${valorNormalizado}]`;
                        const checkbox = document.querySelector(`[name="${checkboxName}"]`);
                        
                        console.log(`  🔍 Buscando checkbox con nombre: "${checkboxName}" → ${checkbox ? 'ENCONTRADO' : 'NO ENCONTRADO'}`);
                        
                        if (checkbox) {
                            checkbox.checked = true;
                            console.log(`  ✓ Checkbox disponibilidad "${disp.valor}" marcado`);
                            
                            if (disp.observacion) {
                                const obsName = `tabla_orden[${valorNormalizado}_obs]`;
                                const obsInput = document.querySelector(`[name="${obsName}"]`);
                                if (obsInput) {
                                    obsInput.value = disp.observacion;
                                    console.log(`  ✓ Observación "${disp.valor}" cargada: "${disp.observacion}"`);
                                }
                            }
                        }
                    });
                }
                
                // Procesar RÉGIMEN
                if (datos.regimen && Array.isArray(datos.regimen)) {
                    console.log('🏢 Procesando régimen:', datos.regimen);
                    datos.regimen.forEach((reg) => {
                        const valorNormalizado = reg.valor.toLowerCase();
                        const checkboxName = `tabla_orden[${valorNormalizado}]`;
                        const checkbox = document.querySelector(`[name="${checkboxName}"]`);
                        
                        console.log(`  🔍 Buscando checkbox con nombre: "${checkboxName}" → ${checkbox ? 'ENCONTRADO' : 'NO ENCONTRADO'}`);
                        
                        if (checkbox) {
                            checkbox.checked = true;
                            console.log(`  ✓ Checkbox régimen "${reg.valor}" marcado`);
                            
                            if (reg.observacion) {
                                let obsName;
                                if (valorNormalizado === 'común' || valorNormalizado === 'comun') {
                                    obsName = 'tabla_orden[regimen_comun_obs]';
                                } else if (valorNormalizado === 'simplificado') {
                                    obsName = 'tabla_orden[regimen_simp_obs]';
                                }
                                
                                const obsInput = document.querySelector(`[name="${obsName}"]`);
                                if (obsInput) {
                                    obsInput.value = reg.observacion;
                                    console.log(`  ✓ Observación régimen "${reg.valor}" cargada: "${reg.observacion}"`);
                                }
                            }
                        }
                    });
                }
                
                // Procesar SE HA VENDIDO
                if (datos.se_ha_vendido && Array.isArray(datos.se_ha_vendido)) {
                    console.log('📊 Procesando se_ha_vendido:', datos.se_ha_vendido);
                    const tbodyVendido = document.querySelector('#tbody_vendido');
                    if (tbodyVendido) {
                        datos.se_ha_vendido.forEach((vendido) => {
                            const firstRow = tbodyVendido.querySelector('tr');
                            if (firstRow) {
                                const valorInput = firstRow.querySelector('input[name*="vendido_item"]');
                                const checkbox = firstRow.querySelector('input[type="checkbox"][name*="vendido"]');
                                const obsInput = firstRow.querySelector('input[name*="vendido_obs"]');
                                
                                if (valorInput) {
                                    valorInput.value = vendido.valor;
                                    console.log(`  ✓ Valor se_ha_vendido cargado: "${vendido.valor}"`);
                                }
                                if (checkbox) {
                                    checkbox.checked = true;
                                    console.log(`  ✓ Checkbox se_ha_vendido marcado`);
                                }
                                if (obsInput) {
                                    obsInput.value = vendido.observacion || '';
                                    console.log(`  ✓ Observación se_ha_vendido cargada: "${vendido.observacion}"`);
                                }
                            }
                        });
                    }
                }
                
                // Procesar ÚLTIMA VENTA
                if (datos.ultima_venta && Array.isArray(datos.ultima_venta)) {
                    console.log('💰 Procesando ultima_venta:', datos.ultima_venta);
                    const tbodyUltimaVenta = document.querySelector('#tbody_ultima_venta');
                    if (tbodyUltimaVenta) {
                        datos.ultima_venta.forEach((ultimaVenta) => {
                            const firstRow = tbodyUltimaVenta.querySelector('tr');
                            if (firstRow) {
                                const valorInput = firstRow.querySelector('input[name*="ultima_venta_item"]');
                                const checkbox = firstRow.querySelector('input[type="checkbox"][name*="ultima_venta"]');
                                const obsInput = firstRow.querySelector('input[name*="ultima_venta_obs"]');
                                
                                if (valorInput) {
                                    valorInput.value = ultimaVenta.valor;
                                    console.log(`  ✓ Valor ultima_venta cargado: "${ultimaVenta.valor}"`);
                                }
                                if (checkbox) {
                                    checkbox.checked = true;
                                    console.log(`  ✓ Checkbox ultima_venta marcado`);
                                }
                                if (obsInput) {
                                    obsInput.value = ultimaVenta.observacion || '';
                                    console.log(`  ✓ Observación ultima_venta cargada: "${ultimaVenta.observacion}"`);
                                }
                            }
                        });
                    }
                }
                
                // Procesar FLETE
                if (datos.flete && Array.isArray(datos.flete)) {
                    console.log('🚚 Procesando flete:', datos.flete);
                    const tbodyFlete = document.querySelector('#tbody_flete');
                    if (tbodyFlete) {
                        datos.flete.forEach((flete) => {
                            const firstRow = tbodyFlete.querySelector('tr');
                            if (firstRow) {
                                const valorInput = firstRow.querySelector('input[name*="flete_item"]');
                                const checkbox = firstRow.querySelector('input[type="checkbox"][name*="flete"]');
                                const obsInput = firstRow.querySelector('input[name*="flete_obs"]');
                                
                                if (valorInput) {
                                    valorInput.value = flete.valor;
                                    console.log(`  ✓ Valor flete cargado: "${flete.valor}"`);
                                }
                                if (checkbox) {
                                    checkbox.checked = true;
                                    console.log(`  ✓ Checkbox flete marcado`);
                                }
                                if (obsInput) {
                                    obsInput.value = flete.observacion || '';
                                    console.log(`  ✓ Observación flete cargada: "${flete.observacion}"`);
                                }
                            }
                        });
                    }
                }
            } else {
                // FORMATO 2: Estructura tabla_orden[field] (anterior)
                console.log('📋 Detectado FORMATO ANTERIOR - tabla_orden[field]');
                
                Object.keys(datos).forEach((key) => {
                    const element = document.querySelector(`[name="${key}"]`);
                    if (element) {
                        if (element.type === 'checkbox') {
                            element.checked = datos[key] === '1' || datos[key] === true;
                            console.log(`  ✓ Checkbox ${key}: ${element.checked}`);
                        } else {
                            element.value = datos[key] || '';
                            console.log(`  ✓ Input ${key}: ${element.value}`);
                        }
                    }
                });
            }
        } catch (e) {
            console.error('❌ Error al cargar especificaciones:', e);
        }
    } else {
        console.log('ℹ️ No hay especificaciones guardadas, limpiando checkboxes');
        // Limpiar todos los checkboxes si no hay especificaciones guardadas
        document.querySelectorAll('[name^="tabla_orden"]').forEach((element) => {
            if (element.type === 'checkbox') {
                element.checked = false;
            } else if (element.type === 'text') {
                element.value = '';
            }
        });
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
    console.log('💾 Guardando especificaciones del modal...');
    
    // Estructura final en formato cotizaciones.especificaciones
    const especificaciones = {
        forma_pago: [],
        disponibilidad: [],
        regimen: [],
        se_ha_vendido: [],
        ultima_venta: [],
        flete: []
    };
    
    const modal = document.getElementById('modalEspecificaciones');
    if (!modal) {
        console.error('❌ Modal no encontrado');
        return;
    }
    
    // PROCESAR FORMA_PAGO
    console.log('💳 Procesando FORMA_PAGO...');
    const formaPagoCheckboxes = [
        { checkbox: 'contado', label: 'Contado', obsField: 'pago_contado_obs' },
        { checkbox: 'credito', label: 'Crédito', obsField: 'pago_credito_obs' }
    ];
    
    formaPagoCheckboxes.forEach(item => {
        const checkbox = modal.querySelector(`[name="tabla_orden[${item.checkbox}]"]`);
        if (checkbox && checkbox.checked) {
            const obsInput = modal.querySelector(`[name="tabla_orden[${item.obsField}]"]`);
            especificaciones.forma_pago.push({
                valor: item.label,
                observacion: obsInput ? obsInput.value : ''
            });
            console.log(`  ✓ ${item.label} agregado`);
        }
    });
    
    // PROCESAR DISPONIBILIDAD
    console.log('📦 Procesando DISPONIBILIDAD...');
    const disponibilidadCheckboxes = [
        { checkbox: 'bodega', label: 'Bodega', obsField: 'bodega_obs' },
        { checkbox: 'cucuta', label: 'Cúcuta', obsField: 'cucuta_obs' },
        { checkbox: 'lafayette', label: 'Lafayette', obsField: 'lafayette_obs' },
        { checkbox: 'fabrica', label: 'Fábrica', obsField: 'fabrica_obs' }
    ];
    
    disponibilidadCheckboxes.forEach(item => {
        const checkbox = modal.querySelector(`[name="tabla_orden[${item.checkbox}]"]`);
        if (checkbox && checkbox.checked) {
            const obsInput = modal.querySelector(`[name="tabla_orden[${item.obsField}]"]`);
            especificaciones.disponibilidad.push({
                valor: item.label,
                observacion: obsInput ? obsInput.value : ''
            });
            console.log(`  ✓ ${item.label} agregado`);
        }
    });
    
    // PROCESAR RÉGIMEN
    console.log('🏢 Procesando RÉGIMEN...');
    const regimenCheckboxes = [
        { checkbox: 'comun', label: 'Común', obsField: 'regimen_comun_obs' },
        { checkbox: 'simplificado', label: 'Simplificado', obsField: 'regimen_simp_obs' }
    ];
    
    regimenCheckboxes.forEach(item => {
        const checkbox = modal.querySelector(`[name="tabla_orden[${item.checkbox}]"]`);
        if (checkbox && checkbox.checked) {
            const obsInput = modal.querySelector(`[name="tabla_orden[${item.obsField}]"]`);
            especificaciones.regimen.push({
                valor: item.label,
                observacion: obsInput ? obsInput.value : ''
            });
            console.log(`  ✓ ${item.label} agregado`);
        }
    });
    
    // PROCESAR SE HA VENDIDO
    console.log('📊 Procesando SE HA VENDIDO...');
    const tbodySeHaVendido = modal.querySelector('#tbody_vendido');
    if (tbodySeHaVendido) {
        const rows = tbodySeHaVendido.querySelectorAll('tr');
        rows.forEach(row => {
            const valorInput = row.querySelector('input[name*="vendido_item"]');
            const checkbox = row.querySelector('input[type="checkbox"][name*="tabla_orden[vendido]"]');
            const obsInput = row.querySelector('input[name*="vendido_obs"]');
            
            if (valorInput && valorInput.value.trim() && checkbox && checkbox.checked) {
                especificaciones.se_ha_vendido.push({
                    valor: valorInput.value.trim(),
                    observacion: obsInput ? obsInput.value.trim() : ''
                });
                console.log(`  ✓ ${valorInput.value.trim()} agregado`);
            }
        });
    }
    
    // PROCESAR ÚLTIMA VENTA
    console.log('💰 Procesando ÚLTIMA VENTA...');
    const tbodyUltimaVenta = modal.querySelector('#tbody_ultima_venta');
    if (tbodyUltimaVenta) {
        const rows = tbodyUltimaVenta.querySelectorAll('tr');
        rows.forEach(row => {
            const valorInput = row.querySelector('input[name*="ultima_venta_item"]');
            const checkbox = row.querySelector('input[type="checkbox"][name*="ultima_venta"]');
            const obsInput = row.querySelector('input[name*="ultima_venta_obs"]');
            
            if (valorInput && valorInput.value.trim() && checkbox && checkbox.checked) {
                especificaciones.ultima_venta.push({
                    valor: valorInput.value.trim(),
                    observacion: obsInput ? obsInput.value.trim() : ''
                });
                console.log(`  ✓ ${valorInput.value.trim()} agregado`);
            }
        });
    }
    
    // PROCESAR FLETE
    console.log('🚚 Procesando FLETE...');
    const tbodyFlete = modal.querySelector('#tbody_flete');
    if (tbodyFlete) {
        const rows = tbodyFlete.querySelectorAll('tr');
        rows.forEach(row => {
            const valorInput = row.querySelector('input[name*="flete_item"]');
            const checkbox = row.querySelector('input[type="checkbox"][name*="flete"]');
            const obsInput = row.querySelector('input[name*="flete_obs"]');
            
            if (valorInput && valorInput.value.trim() && checkbox && checkbox.checked) {
                especificaciones.flete.push({
                    valor: valorInput.value.trim(),
                    observacion: obsInput ? obsInput.value.trim() : ''
                });
                console.log(`  ✓ ${valorInput.value.trim()} agregado`);
            }
        });
    }
    
    // Convertir a JSON string y guardar en campo oculto
    const especificacionesJSON = JSON.stringify(especificaciones);
    document.getElementById('especificaciones').value = especificacionesJSON;
    
    console.log('✅ Especificaciones guardadas en campo oculto');
    console.log('📊 Estructura final:', especificaciones);
    console.log('📋 JSON guardado:', especificacionesJSON);
    
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
        input.value = '';
        return;
    }
    
    // Obtener archivos existentes del input (si los hay)
    const existingFiles = input._storedFiles || [];
    const newFiles = Array.from(files);
    
    // Combinar archivos existentes con nuevos
    const allFiles = [...existingFiles, ...newFiles];
    
    // Crear previews solo para los nuevos archivos
    newFiles.forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.style.cssText = 'position: relative; border-radius: 6px; overflow: hidden; aspect-ratio: 1;';
                div.setAttribute('data-file-index', existingFiles.length + index);
                div.innerHTML = `
                    <img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
                    <button type="button" onclick="eliminarImagenReflectivo(this)" style="position: absolute; top: 2px; right: 2px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; justify-content: center;">×</button>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Guardar todos los archivos en el input usando DataTransfer
    const dataTransfer = new DataTransfer();
    allFiles.forEach(file => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
    
    // Almacenar referencia para futuras adiciones
    input._storedFiles = allFiles;
    
    console.log(`📸 Archivos guardados en input: ${input.files.length}`);
}

function eliminarImagenReflectivo(button) {
    const div = button.parentElement;
    const fileIndex = parseInt(div.getAttribute('data-file-index'));
    const preview = div.parentElement;
    const input = preview.closest('.producto-section').querySelector('.input-file-reflectivo');
    
    // Obtener archivos actuales
    const currentFiles = input._storedFiles || Array.from(input.files);
    
    // Eliminar el archivo del índice especificado
    currentFiles.splice(fileIndex, 1);
    
    // Actualizar el input con los archivos restantes
    const dataTransfer = new DataTransfer();
    currentFiles.forEach(file => dataTransfer.items.add(file));
    input.files = dataTransfer.files;
    input._storedFiles = currentFiles;
    
    // Eliminar preview del DOM
    div.remove();
    
    // Renumerar los índices de los divs restantes
    preview.querySelectorAll('[data-file-index]').forEach((d, idx) => {
        d.setAttribute('data-file-index', idx);
    });
    
    console.log(`🗑️ Imagen eliminada. Archivos restantes: ${input.files.length}`);
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

    // ✅ RECOPILAR PRENDAS CON SUS TALLAS Y UBICACIONES (POR PRENDA)
    const prendas = [];
    document.querySelectorAll('.producto-card').forEach((prenda, index) => {
        const tipo = prenda.querySelector('[name*="tipo_prenda"]')?.value || '';
        const descripcion = prenda.querySelector('[name*="descripcion"]')?.value || '';
        
        // ✅ RECOPILAR GÉNERO DE ESTA PRENDA
        const genero = prenda.querySelector('.talla-genero-select-reflectivo')?.value || '';
        
        // ✅ RECOPILAR TALLAS Y CANTIDADES
        const tallas = [];
        const cantidades = {};
        const tallasContainer = prenda.querySelector('.tallas-agregadas-reflectivo');
        if (tallasContainer) {
            tallasContainer.querySelectorAll('div > span:first-child').forEach(span => {
                const tallaText = span.textContent.trim();
                if (tallaText) {
                    tallas.push(tallaText);
                    cantidades[tallaText] = 1; // Valor por defecto
                }
            });
        }

        // ✅ RECOPILAR UBICACIONES DE ESTA PRENDA ESPECÍFICA
        const ubicacionesDePrenda = [];
        const ubicacionesContainer = prenda.querySelector('.ubicaciones-agregadas-reflectivo');
        if (ubicacionesContainer) {
            ubicacionesContainer.querySelectorAll('.ubicacion-item-reflectivo').forEach((item) => {
                const nombreSpan = item.querySelector('.ubicacion-nombre-reflectivo');
                const descripcionP = item.querySelector('.ubicacion-descripcion-reflectivo');
                
                if (nombreSpan && descripcionP) {
                    const ubicacionText = nombreSpan.textContent.trim();
                    const descripcionUbi = descripcionP.textContent.trim();
                    
                    if (ubicacionText && ubicacionText !== 'Sin descripción adicional') {
                        ubicacionesDePrenda.push({
                            ubicacion: ubicacionText,
                            descripcion: descripcionUbi
                        });
                    }
                }
            });
        }

        if (tipo.trim()) {
            prendas.push({
                tipo: tipo,
                descripcion: descripcion,
                tallas: tallas,
                genero: genero,  // ✅ AGREGAR GÉNERO
                cantidades: cantidades,  // ✅ AGREGAR CANTIDADES POR TALLA
                ubicaciones: ubicacionesDePrenda  // ✅ Ubicaciones específicas de esta prenda
            });
            
            console.log(`✅ Prenda ${index + 1}: ${tipo}`);
            console.log(`   📍 Ubicaciones: ${ubicacionesDePrenda.length}`);
            console.log(`   👤 Género: ${genero || 'No especificado'}`);
            console.log(`   📏 Tallas: ${tallas.length > 0 ? tallas.join(', ') : 'Ninguna'}`);
        }
    });

    if (prendas.length === 0) {
        alert('⚠️ Debes agregar al menos una PRENDA con TIPO');
        return;
    }

    // ✅ Las ubicaciones ya están incluidas en cada objeto de prenda
    // Ya no necesitamos recopilarlas por separado

    const submitButton = e.submitter;
    const action = submitButton ? submitButton.value : 'borrador';

    // Preparar FormData
    const formData = new FormData();
    formData.append('cliente', cliente);
    formData.append('asesora', document.getElementById('asesora').value);
    formData.append('fecha', fecha);
    formData.append('action', action);
    formData.append('tipo', 'RF');
    formData.append('tipo_venta_reflectivo', document.getElementById('header-tipo-venta').value);
    
    // DEBUG: Log de datos que se envían
    // DEBUG: Log de datos que se envían
    console.log('📦 DATOS QUE SE ENVIARÁN:');
    console.log('   cliente:', cliente);
    console.log('   fecha:', fecha);
    console.log('   action:', action);
    console.log('   tipo:', 'RF');
    console.log('   tipo_venta:', document.getElementById('header-tipo-venta').value);
    console.log('   prendas completas:', JSON.stringify(prendas, null, 2));
    
    formData.append('prendas', JSON.stringify(prendas)); // ✅ Enviar prendas con ubicaciones incluidas
    formData.append('especificaciones', document.getElementById('especificaciones').value || '');
    formData.append('descripcion_reflectivo', document.getElementById('descripcion_reflectivo')?.value || 'Reflectivo');
    formData.append('observaciones_generales', JSON.stringify([]));

    // DEBUG: Log de prendas con ubicaciones
    console.log('🚀 ENVIAR FORMULARIO - Prendas con ubicaciones:');
    prendas.forEach((p, i) => {
        console.log(`   Prenda ${i + 1}: ${p.tipo} - ${p.ubicaciones.length} ubicaciones`);
    });

    // ✅ AGREGAR IMÁGENES POR PRENDA CON SU ÍNDICE
    console.log('🔵 PROCESANDO IMÁGENES POR PRENDA:');
    document.querySelectorAll('.producto-card').forEach((prenda, prendaIndex) => {
        const input = prenda.querySelector('.input-file-reflectivo');
        const filesLength = input?.files.length ?? 'N/A';
        console.log('  Prenda ' + prendaIndex + ': input existe=' + !!input + ', files.length=' + filesLength);
        if (input && input.files.length > 0) {
            Array.from(input.files).forEach((file, fileIdx) => {
                // Agregar imagen con índice de prenda
                const campoNombre = 'imagenes_reflectivo_prenda_' + prendaIndex + '[]';
                formData.append(campoNombre, file);
                console.log('    ✅ Imagen ' + (fileIdx + 1) + ': "' + file.name + '" → "' + campoNombre + '"');
            });
            console.log('📸 Prenda ' + prendaIndex + ': ' + input.files.length + ' imágenes agregadas');
        } else {
            console.log('⚠️ Prenda ' + prendaIndex + ': Sin imágenes');
        }
    });

    // Agregar fotos eliminadas
    if (fotosEliminadas.length > 0) {
        console.log('🗑️ Fotos a eliminar:', fotosEliminadas);
        formData.append('imagenes_a_eliminar', JSON.stringify(fotosEliminadas));
    }

    try {
        // Determinar ruta y método según si es edición o creación
        let url, metodo, bodyData;
        
        if (window.esEdicion && window.cotizacionIdActual) {
            // EDICIÓN: Usar POST con _method=PUT para compatibilidad con FormData
            url = '/asesores/cotizaciones/reflectivo/' + window.cotizacionIdActual;
            metodo = 'POST'; // ✅ Cambiar a POST
            console.log('✏️ EDICIÓN - Enviando a:', url);
            
            // Limpiar FormData anterior y reconstruir con datos de edición
            const editFormData = new FormData();
            editFormData.append('_method', 'PUT'); // ✅ Simular PUT con POST
            editFormData.append('cliente', cliente);
            editFormData.append('asesora', document.getElementById('asesora').value);
            editFormData.append('fecha', fecha);
            editFormData.append('action', action);
            editFormData.append('tipo', 'RF');
            editFormData.append('tipo_venta_reflectivo', document.getElementById('header-tipo-venta').value);
            editFormData.append('prendas', JSON.stringify(prendas.length > 0 ? prendas : []));
            editFormData.append('especificaciones', document.getElementById('especificaciones').value || '');
            editFormData.append('descripcion_reflectivo', document.getElementById('descripcion_reflectivo')?.value || 'Reflectivo');
            editFormData.append('observaciones_generales', JSON.stringify([]));
            
            // ✅ AGREGAR IMÁGENES POR PRENDA (IGUAL QUE EN CREACIÓN)
            document.querySelectorAll('.producto-card').forEach((prenda, prendaIndex) => {
                const input = prenda.querySelector('.input-file-reflectivo');
                if (input && input.files.length > 0) {
                    Array.from(input.files).forEach((file) => {
                        editFormData.append(`imagenes_reflectivo_prenda_${prendaIndex}[]`, file);
                    });
                    console.log(`📸 EDICIÓN Prenda ${prendaIndex}: ${input.files.length} imágenes agregadas`);
                }
            });
            
            // Agregar fotos eliminadas
            if (fotosEliminadas.length > 0) {
                console.log('🗑️ Fotos a eliminar:', fotosEliminadas);
                editFormData.append('imagenes_a_eliminar', JSON.stringify(fotosEliminadas));
            }
            
            bodyData = editFormData;
            console.log('📦 FormData para edición construido');
        } else {
            // CREACIÓN: Usar POST storeReflectivo con FormData
            url = '/asesores/cotizaciones/reflectivo/guardar';
            metodo = 'POST';
            console.log('➕ CREACIÓN - Enviando a:', url);
            bodyData = formData;
        }
        
        const response = await fetch(url, {
            method: metodo, // Siempre POST ahora
            body: bodyData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            // Mostrar modal de éxito
            const titulo = action === 'borrador' ? 'Cotización guardada como borrador ✓' : 'Cotización enviada al contador ✓';
            const mensaje = action === 'borrador' 
                ? 'Tu cotización ha sido guardada correctamente como borrador. Podrás seguir editándola cuando lo necesites.'
                : 'Tu cotización ha sido enviada al contador para su revisión y aprobación.';
            
            const numeroCot = result.data?.cotizacion?.numero_cotizacion || result.numero_cotizacion;
            console.log('✅ Respuesta exitosa:', {
                success: true,
                action: action,
                numeroCotizacion: numeroCot,
                data: result.data
            });
            
            mostrarModalExito(titulo, mensaje, numeroCot, action === 'enviar');
        } else {
            console.error('❌ Error en respuesta:', result);
            let mensajeError = result.message || 'Error al guardar';
            
            if (result.errors) {
                console.log('📋 Campos con error (errors):');
                const errores = [];
                for (const [campo, msgs] of Object.entries(result.errors)) {
                    const mensaje = Array.isArray(msgs) ? msgs[0] : msgs;
                    console.log(`   - ${campo}: ${mensaje}`);
                    errores.push(`${campo}: ${mensaje}`);
                }
                mensajeError = 'Errores de validación:\n' + errores.join('\n');
            } else if (result.errores) {
                console.log('📋 Campos con error (errores):');
                const errores = [];
                for (const [campo, msgs] of Object.entries(result.errores)) {
                    const mensaje = Array.isArray(msgs) ? msgs[0] : msgs;
                    console.log(`   - ${campo}: ${mensaje}`);
                    errores.push(`${campo}: ${mensaje}`);
                }
                mensajeError = 'Errores:\n' + errores.join('\n');
            }
            
            console.error('❌ Error completo:', {
                success: false,
                message: result.message,
                errors: result.errors,
                errores: result.errores,
                status: response.status
            });
            
            // Mostrar error de forma más legible
            alert(`❌ ${mensajeError}`);
        }
    } catch (error) {
        console.error('❌ Error de conexión:', error);
        alert(`❌ Error de conexión: ${error.message}\n\nVerifica la consola para más detalles.`);
    }
});

// Variable global para rastrear fotos eliminadas
let fotosEliminadas = [];

/**
 * Mostrar modal de éxito
 */
function mostrarModalExito(titulo, mensaje, numeroCotizacion, mostrarNumero) {
    const modal = document.getElementById('modalExito');
    const modalTitulo = document.getElementById('modalExitoTitulo');
    const modalMensaje = document.getElementById('modalExitoMensaje');
    const modalNumero = document.getElementById('modalExitoNumero');
    const modalNumeroCotizacion = document.getElementById('modalExitoNumeroCotizacion');
    
    // Establecer contenido
    modalTitulo.textContent = titulo;
    modalMensaje.textContent = mensaje;
    
    // Mostrar número de cotización si se envía
    if (mostrarNumero && numeroCotizacion) {
        modalNumero.style.display = 'block';
        modalNumeroCotizacion.textContent = numeroCotizacion;
    } else {
        modalNumero.style.display = 'none';
    }
    
    // Mostrar modal
    if (modal) {
        modal.style.display = 'flex';
    }
}

/**
 * Cerrar modal de éxito
 */
function cerrarModalExito() {
    const modal = document.getElementById('modalExito');
    if (modal) {
        modal.style.display = 'none';
    }
    // Redirigir a cotizaciones después de cerrar
    window.location.href = '{{ route("asesores.cotizaciones.index") }}';
}

/**
 * Función para eliminar una foto del reflectivo INMEDIATAMENTE
 */
function eliminarFotoReflectivo(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const boton = event.target;
    const fotoId = boton.getAttribute('data-foto-id');
    const contenedor = boton.closest('div[data-foto-id]');
    
    if (!fotoId || !contenedor) {
        console.warn('⚠️ No se pudo obtener ID de foto');
        return;
    }
    
    // Obtener la URL de la imagen para enviarla al backend
    const img = contenedor.querySelector('img');
    const fotoUrl = img ? img.src : '';
    
    console.log('🗑️ Solicitando eliminación de foto:', fotoId);
    console.log('   URL:', fotoUrl);
    
    // Mostrar modal de confirmación
    mostrarModalConfirmarEliminar(fotoId, fotoUrl, contenedor);
}

/**
 * Mostrar modal de confirmación de eliminación
 */
function mostrarModalConfirmarEliminar(fotoId, fotoUrl, contenedor) {
    const modal = document.getElementById('modalConfirmarEliminar');
    const btnConfirmar = document.getElementById('btnConfirmarEliminar');
    
    if (!modal || !btnConfirmar) {
        console.error('❌ Modal de confirmación no encontrado');
        return;
    }
    
    // Mostrar modal
    modal.style.display = 'flex';
    
    // Configurar botón de confirmación
    btnConfirmar.onclick = async function() {
        // Cerrar modal
        modal.style.display = 'none';
        
        // Proceder con la eliminación
        try {
            console.log('🗑️ Eliminando foto del servidor...');
            
            const response = await fetch('/asesores/fotos/eliminar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    foto_id: fotoId,
                    ruta: fotoUrl,
                    cotizacion_id: window.cotizacionIdActual || null
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                console.log('✅ Foto eliminada del servidor:', result);
                // Remover del DOM
                contenedor.remove();
                console.log('✅ Foto eliminada del DOM');
            } else {
                console.error('❌ Error al eliminar foto:', result.message);
                alert('Error al eliminar la foto: ' + result.message);
            }
        } catch (error) {
            console.error('❌ Error de conexión al eliminar foto:', error);
            alert('Error de conexión al eliminar la foto. Por favor, intenta de nuevo.');
        }
    };
}

/**
 * Cerrar modal de confirmación de eliminación
 */
function cerrarModalConfirmarEliminar() {
    const modal = document.getElementById('modalConfirmarEliminar');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Agregar PRENDA 1 por default al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Capturar ID de cotización si existe (para edición)
    window.cotizacionIdActual = {!! isset($cotizacionId) ? $cotizacionId : 'null' !!};
    window.esEdicion = !!window.cotizacionIdActual;
    
    console.log('🔑 Información de cotización:');
    console.log('   ID:', window.cotizacionIdActual);
    console.log('   Es edición:', window.esEdicion);
    
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
            
            // Cargar tipo_venta
            if (datosIniciales.tipo_venta) {
                console.log('💰 Cargando tipo_venta desde cotizacion:', datosIniciales.tipo_venta);
                document.getElementById('header-tipo-venta').value = datosIniciales.tipo_venta;
                document.getElementById('tipo_venta_reflectivo').value = datosIniciales.tipo_venta;
            }
            
            // También cargar desde reflectivo_cotizacion si existe (tiene prioridad)
            if (datosIniciales.reflectivo_cotizacion && datosIniciales.reflectivo_cotizacion.tipo_venta) {
                console.log('💰 Cargando tipo_venta desde reflectivo_cotizacion:', datosIniciales.reflectivo_cotizacion.tipo_venta);
                document.getElementById('header-tipo-venta').value = datosIniciales.reflectivo_cotizacion.tipo_venta;
                document.getElementById('tipo_venta_reflectivo').value = datosIniciales.reflectivo_cotizacion.tipo_venta;
            }
            
            // Cargar especificaciones
            if (datosIniciales.especificaciones) {
                console.log('⚙️ Cargando especificaciones:', datosIniciales.especificaciones);
                let especificacionesValue = '';
                
                if (typeof datosIniciales.especificaciones === 'string') {
                    // Si es string, parsearlo para verificar si tiene datos
                    try {
                        const parsed = JSON.parse(datosIniciales.especificaciones);
                        // Si es un objeto con propiedades, guardar el string original
                        if (Object.keys(parsed).length > 0) {
                            especificacionesValue = datosIniciales.especificaciones;
                        } else {
                            especificacionesValue = '{}';
                        }
                    } catch (e) {
                        especificacionesValue = datosIniciales.especificaciones;
                    }
                } else if (typeof datosIniciales.especificaciones === 'object') {
                    // Si es objeto, convertir a JSON string
                    especificacionesValue = JSON.stringify(datosIniciales.especificaciones);
                }
                
                console.log('⚙️ Especificaciones a cargar en campo:', especificacionesValue);
                document.getElementById('especificaciones').value = especificacionesValue;
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
                    
                    // ✅ CARGAR GÉNERO DE LA PRENDA
                    const generoSelect = clone.querySelector('.talla-genero-select-reflectivo');
                    if (generoSelect && prenda.genero) {
                        // Mostrar el select de género
                        generoSelect.style.display = 'block';
                        generoSelect.value = prenda.genero;
                        console.log('    ✓ Género:', prenda.genero);
                    }
                    
                    // ✅ CARGAR TALLAS DE LA PRENDA
                    if (prenda.tallas && prenda.tallas.length > 0) {
                        console.log('    ✓ Tallas:', prenda.tallas);
                        const prendaCard = clone;
                        const tallasAgregadas = prendaCard.querySelector('.tallas-agregadas-reflectivo');
                        const tallasHidden = prendaCard.querySelector('.tallas-hidden-reflectivo');
                        const tallasSection = prendaCard.querySelector('.tallas-section-reflectivo');
                        
                        if (tallasAgregadas) {
                            // Limpiar tallas previas si existen
                            tallasAgregadas.innerHTML = '';
                            
                            // Agregar cada talla como tag
                            prenda.tallas.forEach(talla => {
                                const tag = document.createElement('div');
                                tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
                                tag.innerHTML = `
                                    <span>${talla}</span>
                                    <button type="button" onclick="this.closest('div').remove(); actualizarTallasHiddenReflectivo(this.closest('.producto-card'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
                                `;
                                tallasAgregadas.appendChild(tag);
                            });
                            
                            // Actualizar hidden input
                            if (tallasHidden) {
                                tallasHidden.value = prenda.tallas.join(', ');
                            }
                            
                            // Mostrar sección
                            if (tallasSection) {
                                tallasSection.style.display = 'block';
                            }
                        }
                    }
                    
                    // Agregar el clone al DOM primero
                    contenedor.appendChild(clone);
                    
                    // ✅ CARGAR FOTOS - Después de agregar al DOM para evitar duplicación
                    const fotosParaCargar = prenda.reflectivo?.fotos || prenda.fotos || [];
                    if (fotosParaCargar && fotosParaCargar.length > 0) {
                        console.log('    ✓ Fotos a cargar:', fotosParaCargar.length);
                        // Buscar el contenedor en el DOM, no en el clone
                        const prendaCard = contenedor.lastElementChild;
                        const fotosContainer = prendaCard.querySelector('.fotos-preview-reflectivo');
                        
                        if (fotosContainer) {
                            // ✅ LIMPIAR el contenedor antes de agregar fotos
                            const fotosExistentes = fotosContainer.children.length;
                            console.log('    📸 Fotos existentes en contenedor:', fotosExistentes);
                            fotosContainer.innerHTML = '';
                            
                            fotosParaCargar.forEach((foto, idx) => {
                                console.log(`    📷 Agregando foto ${idx + 1}:`, foto.id);
                                const imgDiv = document.createElement('div');
                                imgDiv.style.cssText = 'position: relative; border-radius: 6px; overflow: hidden; aspect-ratio: 1;';
                                imgDiv.setAttribute('data-foto-id', foto.id);
                                imgDiv.innerHTML = `
                                    <img src="${foto.url}" style="width: 100%; height: 100%; object-fit: cover;">
                                    <button type="button" data-foto-id="${foto.id}" onclick="eliminarFotoReflectivo(event)" style="position: absolute; top: 2px; right: 2px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; justify-content: center;">×</button>
                                `;
                                fotosContainer.appendChild(imgDiv);
                            });
                            console.log('    ✅ Total fotos en contenedor después de cargar:', fotosContainer.children.length);
                        }
                    } else {
                        console.log('    ⚠️ No hay fotos para esta prenda');
                    }
                    
                    // ✅ CARGAR UBICACIONES DE ESTA PRENDA (después de agregar al DOM)
                    if (prenda.reflectivo && prenda.reflectivo.ubicacion) {
                        console.log('📍 Cargando ubicaciones para prenda', index + 1);
                        const prendaCard = contenedor.lastElementChild;
                        const ubicacionesContainer = prendaCard.querySelector('.ubicaciones-agregadas-reflectivo');
                        
                        if (ubicacionesContainer) {
                            const ubicaciones = Array.isArray(prenda.reflectivo.ubicacion) 
                                ? prenda.reflectivo.ubicacion 
                                : (typeof prenda.reflectivo.ubicacion === 'string' ? JSON.parse(prenda.reflectivo.ubicacion) : []);
                            
                            ubicaciones.forEach(ubi => {
                                if (ubi && ubi.ubicacion) {
                                    const item = document.createElement('div');
                                    item.className = 'ubicacion-item-reflectivo';
                                    item.style.cssText = 'background: white; border: 2px solid #0ea5e9; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; width: 100%; box-shadow: 0 2px 4px rgba(14, 165, 233, 0.15); position: relative;';
                                    
                                    const header = document.createElement('div');
                                    header.className = 'ubicacion-header-reflectivo';
                                    header.style.cssText = 'display: flex; justify-content: space-between; align-items: center; cursor: pointer;';
                                    header.innerHTML = `
                                        <div style="display: flex; align-items: center; gap: 0.5rem; flex: 1;">
                                            <span style="color: #0ea5e9; font-weight: 700; font-size: 1rem;">📍</span>
                                            <span class="ubicacion-nombre-reflectivo" style="font-weight: 700; color: #1e40af; font-size: 0.95rem;">${ubi.ubicacion}</span>
                                        </div>
                                        <span style="color: #0ea5e9; font-size: 1.2rem; transition: transform 0.3s ease;" class="ubicacion-toggle">▼</span>
                                    `;
                                    
                                    const body = document.createElement('div');
                                    body.className = 'ubicacion-body-reflectivo';
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
                                    
                                    let expanded = true;
                                    header.addEventListener('click', () => {
                                        expanded = !expanded;
                                        body.style.display = expanded ? 'block' : 'none';
                                        header.querySelector('.ubicacion-toggle').style.transform = expanded ? 'rotate(0deg)' : 'rotate(-90deg)';
                                    });
                                    
                                    ubicacionesContainer.appendChild(item);
                                }
                            });
                            console.log('    ✓ Ubicaciones cargadas:', ubicaciones.length);
                        }
                    }
                });
                console.log('✅ Prendas cargadas correctamente');
            } else {
                console.log('⚠️ No hay prendas, agregando una por defecto');
                agregarProductoPrenda();
            }
            
            // ✅ FOTOS YA SE CARGAN POR PRENDA (líneas 2229-2258)
            // No cargar fotos globalmente para evitar duplicación
            console.log('🔍 Buscando reflectivo en datosIniciales...');
            console.log('   reflectivo_cotizacion:', datosIniciales.reflectivo_cotizacion ? 'EXISTE' : 'NO');
            console.log('   reflectivo:', datosIniciales.reflectivo ? 'EXISTE' : 'NO');
            
            const reflectivo = datosIniciales.reflectivo_cotizacion || datosIniciales.reflectivo;
            console.log('ℹ️ Fotos cargadas por prenda (no globalmente para evitar duplicaciones)');
            
            // Cargar descripción del reflectivo (si existe)
            if (reflectivo && reflectivo.descripcion) {
                console.log('📝 REFLECTIVO - Cargando descripción');
                const descInput = document.getElementById('descripcion_reflectivo');
                if (descInput) {
                    descInput.value = reflectivo.descripcion;
                    console.log('    ✓ Descripción:', reflectivo.descripcion);
                }
            }
            
            // ✅ NO CARGAR UBICACIÓN GLOBAL - Ya se cargan por PRENDA (línea ~2108)
            // Las ubicaciones deben cargarse dentro del contexto de cada prenda, no globalmente
            // Esto previene duplicación en la primera prenda
            console.log('ℹ️ Ubicaciones cargadas por prenda (no globalmente para evitar duplicaciones)');
        } catch (e) {
            console.error('❌ Error cargando datos iniciales:', e);
            console.error('Stack:', e.stack);
            agregarProductoPrenda();
        }
    } else {
        console.log('ℹ️ No hay datos iniciales, agregando prenda por defecto');
        agregarProductoPrenda();
    }

    // ============ FUNCIONES PARA TALLAS EN REFLECTIVO ============

    /**
     * Actualiza el input oculto genero_id con el género seleccionado
     */
    window.actualizarGeneroSeleccionadoReflectivo = function(select) {
        const productoSection = select.closest('.producto-section');
        if (!productoSection) {
            console.warn('⚠️ No se encontró .producto-section para actualizar genero_id');
            return;
        }
        
        const generoInput = productoSection.querySelector('.genero-id-hidden-reflectivo');
        if (!generoInput) {
            console.warn('⚠️ No se encontró .genero-id-hidden-reflectivo');
            return;
        }
        
        const generoValue = select.value;
        console.log('🔵 Género seleccionado:', generoValue);
        
        // Mapear valores de género a IDs
        let generoId = '';
        if (generoValue === 'dama') {
            generoId = '1';
        } else if (generoValue === 'caballero') {
            generoId = '2';
        }
        
        generoInput.value = generoId;
        console.log('✅ genero_id actualizado a:', generoId);
    };

    // Mapeos de tallas por tipo y género (copiado de tallas.js)
    const TALLAS_LETRAS = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
    const TALLAS_NUMEROS_DAMA = ['2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28'];
    const TALLAS_NUMEROS_CABALLERO = ['30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50', '52', '54', '56'];

    window.actualizarSelectTallasReflectivo = function(select) {
        console.log('🔵 actualizarSelectTallasReflectivo() llamado');
        
        const container = select.closest('.producto-section');
        const tallaBotones = container.querySelector('.talla-botones-reflectivo');
        const botonesDiv = container.querySelector('.talla-botones-container-reflectivo');
        const generoSelect = container.querySelector('.talla-genero-select-reflectivo');
        const modoSelect = container.querySelector('.talla-modo-select-reflectivo');
        const tallaRangoSelectors = container.querySelector('.talla-rango-selectors-reflectivo');
        const tipo = select.value;
        
        console.log('📋 Tipo seleccionado:', tipo);
        
        // LIMPIAR COMPLETAMENTE TODO
        botonesDiv.innerHTML = '';
        tallaBotones.style.display = 'none';
        tallaRangoSelectors.style.display = 'none';
        modoSelect.style.display = 'none';
        generoSelect.style.display = 'none';
        generoSelect.value = '';
        modoSelect.value = '';
        
        // Remover event listeners anteriores
        if (modoSelect._handlerLetras) {
            modoSelect.removeEventListener('change', modoSelect._handlerLetras);
            modoSelect._handlerLetras = null;
        }
        if (modoSelect._handlerNumeros) {
            modoSelect.removeEventListener('change', modoSelect._handlerNumeros);
            modoSelect._handlerNumeros = null;
        }
        if (modoSelect._handler) {
            modoSelect.removeEventListener('change', modoSelect._handler);
            modoSelect._handler = null;
        }
        if (generoSelect._handlerLetras) {
            generoSelect.removeEventListener('change', generoSelect._handlerLetras);
            generoSelect._handlerLetras = null;
        }
        if (generoSelect._handler) {
            generoSelect.removeEventListener('change', generoSelect._handler);
            generoSelect._handler = null;
        }
        
        if (tipo === 'letra') {
            console.log('📝 Configurando LETRAS');
            // LETRAS muestra género y modo
            generoSelect.style.display = 'block';
            modoSelect.style.display = 'block';
            modoSelect.value = 'manual';
            
            // Event listener para modo
            modoSelect._handlerLetras = function() {
                console.log('📝 Modo cambiado para LETRAS:', this.value);
                actualizarModoLetrasReflectivo(container, this.value);
            };
            modoSelect.addEventListener('change', modoSelect._handlerLetras);
            
            // Mostrar botones de LETRAS en manual
            tallaBotones.style.display = 'block';
            tallaRangoSelectors.style.display = 'none';
            
            TALLAS_LETRAS.forEach(talla => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = talla;
                btn.className = 'talla-btn';
                btn.dataset.talla = talla;
                btn.style.cssText = 'padding: 0.5rem 1rem; background: white; color: #0066cc; border: 2px solid #0066cc; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease;';
                btn.onmouseover = function() { if (!this.classList.contains('activo')) this.style.background = '#e6f0ff'; };
                btn.onmouseout = function() { if (!this.classList.contains('activo')) this.style.background = 'white'; };
                btn.onclick = function(e) {
                    e.preventDefault();
                    this.classList.toggle('activo');
                    if (this.classList.contains('activo')) {
                        this.style.background = '#0066cc';
                        this.style.color = 'white';
                    } else {
                        this.style.background = 'white';
                        this.style.color = '#0066cc';
                    }
                };
                botonesDiv.appendChild(btn);
            });
            console.log('✅ Botones de LETRAS creados');
            
        } else if (tipo === 'numero') {
            console.log('🔢 Configurando NÚMEROS');
            generoSelect.style.display = 'block';
            
            generoSelect._handler = function() {
                console.log('🔢 Género seleccionado (NÚMEROS):', this.value);
                actualizarBotonesPorGeneroReflectivo(container, this.value);
            };
            generoSelect.addEventListener('change', generoSelect._handler);
        }
    };
    
    window.actualizarModoLetrasReflectivo = function(container, modo) {
        const tallaBotones = container.querySelector('.talla-botones-reflectivo');
        const tallaRangoSelectors = container.querySelector('.talla-rango-selectors-reflectivo');
        const botonesDiv = container.querySelector('.talla-botones-container-reflectivo');
        
        botonesDiv.innerHTML = '';
        
        if (modo === 'manual') {
            tallaBotones.style.display = 'block';
            tallaRangoSelectors.style.display = 'none';
            
            TALLAS_LETRAS.forEach(talla => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = talla;
                btn.className = 'talla-btn';
                btn.dataset.talla = talla;
                btn.style.cssText = 'padding: 0.5rem 1rem; background: white; color: #0066cc; border: 2px solid #0066cc; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease;';
                btn.onmouseover = function() { if (!this.classList.contains('activo')) this.style.background = '#e6f0ff'; };
                btn.onmouseout = function() { if (!this.classList.contains('activo')) this.style.background = 'white'; };
                btn.onclick = function(e) {
                    e.preventDefault();
                    this.classList.toggle('activo');
                    if (this.classList.contains('activo')) {
                        this.style.background = '#0066cc';
                        this.style.color = 'white';
                    } else {
                        this.style.background = 'white';
                        this.style.color = '#0066cc';
                    }
                };
                botonesDiv.appendChild(btn);
            });
        } else if (modo === 'rango') {
            tallaBotones.style.display = 'none';
            tallaRangoSelectors.style.display = 'flex';
            actualizarSelectoresRangoLetrasReflectivo(container);
        } else {
            tallaBotones.style.display = 'none';
            tallaRangoSelectors.style.display = 'none';
        }
    };
    
    window.actualizarSelectoresRangoLetrasReflectivo = function(container) {
        const desdeSelect = container.querySelector('.talla-desde-reflectivo');
        const hastaSelect = container.querySelector('.talla-hasta-reflectivo');
        
        desdeSelect.innerHTML = '<option value="">Desde</option>';
        hastaSelect.innerHTML = '<option value="">Hasta</option>';
        
        TALLAS_LETRAS.forEach(talla => {
            const optDesde = document.createElement('option');
            optDesde.value = talla;
            optDesde.textContent = talla;
            desdeSelect.appendChild(optDesde);
            
            const optHasta = document.createElement('option');
            optHasta.value = talla;
            optHasta.textContent = talla;
            hastaSelect.appendChild(optHasta);
        });
    };
    
    window.actualizarBotonesPorGeneroReflectivo = function(container, genero) {
        console.log('🔢 actualizarBotonesPorGeneroReflectivo:', genero);
        
        if (!genero) {
            container.querySelector('.talla-botones-reflectivo').style.display = 'none';
            container.querySelector('.talla-rango-selectors-reflectivo').style.display = 'none';
            container.querySelector('.talla-modo-select-reflectivo').style.display = 'none';
            return;
        }
        
        // Mostrar modo
        const modoSelect = container.querySelector('.talla-modo-select-reflectivo');
        modoSelect.style.display = 'block';
        modoSelect.value = 'manual';
        
        // Remover event listener anterior
        if (modoSelect._handlerNumeros) {
            modoSelect.removeEventListener('change', modoSelect._handlerNumeros);
        }
        
        // Agregar nuevo event listener
        modoSelect._handlerNumeros = function() {
            console.log('🔢 Modo cambiado para NÚMEROS:', this.value);
            actualizarModoNumerosReflectivo(container, this.value, genero);
        };
        modoSelect.addEventListener('change', modoSelect._handlerNumeros);
        
        // Mostrar botones en manual
        actualizarModoNumerosReflectivo(container, 'manual', genero);
    };
    
    window.actualizarModoNumerosReflectivo = function(container, modo, genero) {
        const tallaBotones = container.querySelector('.talla-botones-reflectivo');
        const tallaRangoSelectors = container.querySelector('.talla-rango-selectors-reflectivo');
        const botonesDiv = container.querySelector('.talla-botones-container-reflectivo');
        
        botonesDiv.innerHTML = '';
        
        const tallas = genero === 'dama' ? TALLAS_NUMEROS_DAMA : TALLAS_NUMEROS_CABALLERO;
        
        if (modo === 'manual') {
            tallaBotones.style.display = 'block';
            tallaRangoSelectors.style.display = 'none';
            
            tallas.forEach(talla => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = talla;
                btn.className = 'talla-btn';
                btn.dataset.talla = talla;
                btn.style.cssText = 'padding: 0.5rem 1rem; background: white; color: #0066cc; border: 2px solid #0066cc; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease;';
                btn.onmouseover = function() { if (!this.classList.contains('activo')) this.style.background = '#e6f0ff'; };
                btn.onmouseout = function() { if (!this.classList.contains('activo')) this.style.background = 'white'; };
                btn.onclick = function(e) {
                    e.preventDefault();
                    this.classList.toggle('activo');
                    if (this.classList.contains('activo')) {
                        this.style.background = '#0066cc';
                        this.style.color = 'white';
                    } else {
                        this.style.background = 'white';
                        this.style.color = '#0066cc';
                    }
                };
                botonesDiv.appendChild(btn);
            });
        } else if (modo === 'rango') {
            tallaBotones.style.display = 'none';
            tallaRangoSelectors.style.display = 'flex';
            actualizarSelectoresRangoNumerosReflectivo(container, tallas);
        } else {
            tallaBotones.style.display = 'none';
            tallaRangoSelectors.style.display = 'none';
        }
    };
    
    window.actualizarSelectoresRangoNumerosReflectivo = function(container, tallas) {
        const desdeSelect = container.querySelector('.talla-desde-reflectivo');
        const hastaSelect = container.querySelector('.talla-hasta-reflectivo');
        
        desdeSelect.innerHTML = '<option value="">Desde</option>';
        hastaSelect.innerHTML = '<option value="">Hasta</option>';
        
        tallas.forEach(talla => {
            const optDesde = document.createElement('option');
            optDesde.value = talla;
            optDesde.textContent = talla;
            desdeSelect.appendChild(optDesde);
            
            const optHasta = document.createElement('option');
            optHasta.value = talla;
            optHasta.textContent = talla;
            hastaSelect.appendChild(optHasta);
        });
    };

    window.agregarTallasSeleccionadasReflectivo = function(btn) {
        const card = btn.closest('.producto-card');
        const botonesActivos = card.querySelectorAll('.talla-btn.activo');
        const tallasAgregadas = card.querySelector('.tallas-agregadas-reflectivo');
        const tallasSection = card.querySelector('.tallas-section-reflectivo');
        
        if (botonesActivos.length === 0) {
            alert('Por favor selecciona al menos una talla');
            return;
        }
        
        botonesActivos.forEach(boton => {
            const talla = boton.dataset.talla;
            
            const existe = Array.from(tallasAgregadas.querySelectorAll('div')).some(tag =>
                tag.querySelector('span').textContent === talla
            );
            
            if (!existe) {
                const tag = document.createElement('div');
                tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
                tag.innerHTML = `
                    <span>${talla}</span>
                    <button type="button" onclick="this.closest('div').remove(); actualizarTallasHiddenReflectivo(this.closest('.producto-card'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
                `;
                
                tallasAgregadas.appendChild(tag);
            }
        });
        
        tallasSection.style.display = 'block';
        actualizarTallasHiddenReflectivo(card);
        
        botonesActivos.forEach(boton => {
            boton.classList.remove('activo');
            boton.style.background = 'white';
            boton.style.color = '#0066cc';
        });
    };

    window.agregarTallasRangoReflectivo = function(btn) {
        const card = btn.closest('.producto-card');
        const tallaDesde = card.querySelector('.talla-desde-reflectivo').value;
        const tallaHasta = card.querySelector('.talla-hasta-reflectivo').value;
        const tallasAgregadas = card.querySelector('.tallas-agregadas-reflectivo');
        const tallasSection = card.querySelector('.tallas-section-reflectivo');
        const tipoSelect = card.querySelector('.talla-tipo-select-reflectivo');
        const generoSelect = card.querySelector('.talla-genero-select-reflectivo');
        
        console.log('🔢 Agregando rango - Desde:', tallaDesde, 'Hasta:', tallaHasta);
        console.log('📋 Tipo seleccionado:', tipoSelect.value);
        console.log('👥 Género select value:', generoSelect.value);
        
        if (!tallaDesde || !tallaHasta) {
            alert('Por favor selecciona un rango completo (Desde y Hasta)');
            return;
        }
        
        let tallas;
        
        if (tipoSelect.value === 'letra') {
            console.log('📝 Usando LETRAS para rango');
            tallas = TALLAS_LETRAS;
        } else if (tipoSelect.value === 'numero') {
            if (!generoSelect.value) {
                alert('Por favor selecciona un género primero');
                return;
            }
            console.log('🔢 Usando NÚMEROS para rango - Género:', generoSelect.value);
            tallas = generoSelect.value === 'dama' ? TALLAS_NUMEROS_DAMA : TALLAS_NUMEROS_CABALLERO;
        } else {
            alert('Por favor selecciona un tipo de talla primero');
            return;
        }
        
        console.log('📋 Array de tallas a usar:', tallas);
        console.log('🔍 Buscando en array:', tallaDesde, 'y', tallaHasta);
        
        const indexDesde = tallas.indexOf(tallaDesde);
        const indexHasta = tallas.indexOf(tallaHasta);
        
        console.log('📍 Índices encontrados - Desde:', indexDesde, 'Hasta:', indexHasta);
        
        if (indexDesde === -1 || indexHasta === -1) {
            console.error('❌ Tallas no encontradas en el array');
            console.error('Disponibles:', tallas);
            console.error('Buscando:', tallaDesde, tallaHasta);
            alert('Las tallas seleccionadas no son válidas');
            return;
        }
        
        if (indexDesde > indexHasta) {
            alert('La talla "Desde" no puede ser mayor que "Hasta"');
            return;
        }
        
        const tallasRango = tallas.slice(indexDesde, indexHasta + 1);
        console.log('✅ Tallas en rango:', tallasRango);
        
        tallasRango.forEach(talla => {
            const existe = Array.from(tallasAgregadas.querySelectorAll('div')).some(tag =>
                tag.querySelector('span').textContent === talla
            );
            
            if (!existe) {
                const tag = document.createElement('div');
                tag.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600;';
                tag.innerHTML = `
                    <span>${talla}</span>
                    <button type="button" onclick="this.closest('div').remove(); actualizarTallasHiddenReflectivo(this.closest('.producto-card'))" style="background: none; border: none; color: white; cursor: pointer; font-size: 1rem; padding: 0; line-height: 1;">✕</button>
                `;
                
                tallasAgregadas.appendChild(tag);
            }
        });
        
        tallasSection.style.display = 'block';
        actualizarTallasHiddenReflectivo(card);
        console.log('✅ Rango agregado correctamente');
    };

    window.actualizarTallasHiddenReflectivo = function(container) {
        if (!container) return;
        
        const tallasAgregadas = container.querySelector('.tallas-agregadas-reflectivo');
        const tallasHidden = container.querySelector('.tallas-hidden-reflectivo');
        
        if (!tallasAgregadas || !tallasHidden) return;
        
        const tallas = [];
        
        tallasAgregadas.querySelectorAll('div > span:first-child').forEach(span => {
            if (span.textContent) {
                tallas.push(span.textContent);
            }
        });
        
        tallasHidden.value = tallas.join(', ');
    };
});
</script>

@endsection
