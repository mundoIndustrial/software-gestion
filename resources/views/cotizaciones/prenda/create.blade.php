@extends('layouts.asesores')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/create-friendly-refactored.css') }}">
<link rel="stylesheet" href="{{ asset('css/asesores/create-friendly.css') }}">
<style>
    /* Desactivar navbar */
    header {
        display: none !important;
    }

    /* Solo hacer más estrecho el main-content en esta vista */
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

    /* Contenedor externo para centrar todo */
    .content-wrapper {
        width: 100%;
        margin: 0 auto;
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
        flex: 1;
    }

    .btn-primary {
        background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #64748b;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
    }

    .prenda-search-container {
        position: relative;
    }

    .prenda-suggestions {
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        min-width: 100%;
        display: none;
        margin-top: 2px;
        top: 100%;
        left: 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .prenda-suggestion-item {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.9rem;
        transition: background 0.2s;
    }

    .prenda-suggestion-item:hover {
        background: #f0f7ff;
        color: #0066cc;
    }

    .prenda-search-input:focus + .prenda-suggestions {
        display: block;
    }

    .prenda-search-container:has(.prenda-search-input:focus) .prenda-suggestions {
        display: block;
    }

    /* Contenedor centrado para productos */
    #productosContainer {
        width: 100%;
        margin: 0 auto;
    }

    /* Hacer que todas las secciones usen el mismo ancho */
    .producto-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .producto-header {
        background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%);
        color: white;
        padding: 0.75rem 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .producto-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
    }

    .producto-body {
        padding: 1.5rem;
    }

    .producto-section {
        margin-bottom: 1.5rem;
    }

    .section-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e40af;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Estilos para validación */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    .campo-invalido {
        border-color: #dc2626 !important;
        background-color: #fef2f2 !important;
        animation: shake 0.4s ease-in-out;
    }

    .error-message {
        color: #dc2626;
        font-size: 0.8rem;
        margin-top: 0.3rem;
        font-weight: 600;
    }
</style>

@endpush

@section('content')
<div class="page-wrapper">
    <div class="content-wrapper">
        <!-- Header Moderno -->
        <div style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); border-radius: 12px; padding: 1.25rem 1.75rem; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 1.5rem; align-items: start;">
            <!-- Título y descripción -->
            <div style="display: flex; align-items: center; gap: 0.75rem; grid-column: 1 / -1;">
                <span class="material-symbols-rounded" style="font-size: 1.75rem; color: white;">checkroom</span>
                <div>
                    <h2 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 700;">Cotización de Prenda</h2>
                    <p style="margin: 0.2rem 0 0 0; color: rgba(255,255,255,0.85); font-size: 0.8rem;">Completa los datos de la cotización de prendas</p>
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
                
                <!-- Tipo de Cotización -->
                <div>
                    <label style="display: block; color: rgba(255,255,255,0.8); font-size: 0.7rem; font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.4px;">Tipo</label>
                    <div>
                        <select id="header-tipo-cotizacion" name="tipo_cotizacion" style="width: 100%; background: white; border: 2px solid transparent; padding: 0.6rem 0.75rem; border-radius: 6px; font-weight: 600; color: #1e40af; font-size: 0.9rem; transition: all 0.2s; cursor: pointer;">
                            <option value="">Selecciona</option>
                            <option value="M">M</option>
                            <option value="D">D</option>
                            <option value="X">X</option>
                        </select>
                        <div id="error-tipo-cotizacion" class="error-message" style="display: none; color: white; margin-top: 0.3rem;">Campo requerido</div>
                    </div>
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
        <form id="cotizacionPrendaForm">
            @csrf

            <!-- Campos ocultos para sincronizar con el header -->
            <input type="text" id="cliente" name="cliente" style="display: none;">
            <input type="text" id="asesora" name="asesora" value="{{ auth()->user()->name }}" readonly style="display: none;">
            <input type="date" id="fecha" name="fecha" style="display: none;">
            <input type="text" id="tipo_cotizacion" name="tipo_cotizacion" style="display: none;">

            <!-- TIPO DE COTIZACIÓN -->
            <!-- Ahora está en el header -->

            <!-- CONTENEDOR DE PRENDAS -->
            <div class="form-section">
                <div class="productos-container" id="productosContainer">
                    <!-- Los productos se agregan aquí dinámicamente -->
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="location.href='{{ route('asesores.pedidos.index') }}'\" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%); border: 2px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 600; color: #333; font-size: 0.85rem; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #e8e8e8 0%, #d5d5d5 100%)'; this.style.borderColor='#999'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)';" onmouseout="this.style.background='linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%)'; this.style.borderColor='#ddd'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <i class="fas fa-times" style="font-size: 0.9rem;"></i> Cancelar
                </button>
                <div style="display: flex; gap: 0.5rem; flex: 1; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" id="btnGuardarBorrador" onclick="guardarCotizacionPrenda('borrador')" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); border: 2px solid #3d8b40; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #45a049 0%, #3d8b40 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(76, 175, 80, 0.2)';" onmouseout="this.style.background='linear-gradient(135deg, #4CAF50 0%, #45a049 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="fas fa-save" style="font-size: 0.9rem;"></i> Guardar Borrador
                    </button>
                    <button type="button" class="btn btn-primary" id="btnEnviar" onclick="guardarCotizacionPrenda('enviar')" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); border: 2px solid #003d7a; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #0052a3 0%, #003d7a 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 12px rgba(0, 102, 204, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0066cc 0%, #0052a3 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="fas fa-paper-plane" style="font-size: 0.9rem;"></i> Enviar
                    </button>
                </div>
            </div>
        </form>
    </div>
    </div>

    <!-- Botón flotante para agregar prenda -->
    <div style="position: fixed; bottom: 30px; right: 30px; z-index: 1000;">
        <!-- Menú flotante -->
        <div id="menuFlotante" style="display: none; position: absolute; bottom: 70px; right: 0; background: white; border-radius: 12px; box-shadow: 0 5px 40px rgba(0,0,0,0.16); overflow: hidden; min-width: 200px;">
            <button type="button" onclick="agregarProductoPrenda(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 12px 16px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.9rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s; border-bottom: 1px solid #f0f0f0;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                <i class="fas fa-plus" style="color: #1e40af; font-size: 1.1rem;"></i>
                <span>Agregar Prenda</span>
            </button>
            <button type="button" onclick="abrirModalEspecificaciones(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 12px 16px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.9rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                <i class="fas fa-sliders-h" style="color: #ff9800; font-size: 1.1rem;"></i>
                <span>Especificaciones</span>
            </button>
        </div>
        
        <!-- Botón principal flotante -->
        <button type="button" id="btnFlotante" onclick="const menu = document.getElementById('menuFlotante'); menu.style.display = menu.style.display === 'none' ? 'block' : 'none'; this.style.transform = menu.style.display === 'block' ? 'scale(1) rotate(45deg)' : 'scale(1) rotate(0deg)'" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #0ea5e9); color: white; border: none; cursor: pointer; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.4); transition: all 0.3s ease; position: relative;" onmouseover="this.style.boxShadow='0 6px 20px rgba(30, 64, 175, 0.5)'; this.style.transform='scale(1.1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')" onmouseout="this.style.boxShadow='0 4px 12px rgba(30, 64, 175, 0.4)'; this.style.transform='scale(1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')">
            <i class="fas fa-plus"></i>
        </button>
    </div>
</div>

<!-- MODAL: ESPECIFICACIONES -->
<div id="modalEspecificaciones" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 900px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 2px solid #ffc107; padding-bottom: 1rem;">
            <h3 style="margin: 0; color: #333; font-size: 1.3rem;"><i class="fas fa-clipboard-check"></i> ESPECIFICACIONES DE LA COTIZACIÓN</h3>
            <button type="button" onclick="cerrarModalEspecificaciones()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <table class="tabla-control-compacta" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f0f0f0;">
                    <th style="width: 30%; text-align: left; padding: 10px; border: 1px solid #ddd;"></th>
                    <th style="width: 15%; text-align: center; padding: 10px; border: 1px solid #ddd;">SELECCIONAR</th>
                    <th style="width: 55%; text-align: left; padding: 10px; border: 1px solid #ddd;">OBSERVACIONES</th>
                </tr>
            </thead>
            <tbody id="tbody_especificaciones">
                <!-- DISPONIBILIDAD -->
                <tr class="fila-grupo">
                    <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px; border: 1px solid #ddd;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>📦 DISPONIBILIDAD</span>
                            <button type="button" onclick="agregarFilaEspecificacion('disponibilidad')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_disponibilidad">
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><label style="margin: 0; font-size: 0.8rem;">Bodega</label></td>
                        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="text" name="tabla_orden[bodega_obs]" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><label style="margin: 0; font-size: 0.8rem;">Cúcuta</label></td>
                        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="text" name="tabla_orden[cucuta_obs]" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        </td>
                    </tr>
                </tbody>

                <!-- PAGO -->
                <tr class="fila-grupo">
                    <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px; border: 1px solid #ddd;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>💳 FORMA DE PAGO</span>
                            <button type="button" onclick="agregarFilaEspecificacion('pago')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_pago">
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><label style="margin: 0; font-size: 0.8rem;">Contado</label></td>
                        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="text" name="tabla_orden[pago_contado_obs]" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><label style="margin: 0; font-size: 0.8rem;">Crédito</label></td>
                        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="text" name="tabla_orden[pago_credito_obs]" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        </td>
                    </tr>
                </tbody>

                <!-- RÉGIMEN -->
                <tr class="fila-grupo">
                    <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px; border: 1px solid #ddd;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>📋 RÉGIMEN</span>
                            <button type="button" onclick="agregarFilaEspecificacion('regimen')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_regimen">
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><label style="margin: 0; font-size: 0.8rem;">Común</label></td>
                        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="text" name="tabla_orden[regimen_comun_obs]" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><label style="margin: 0; font-size: 0.8rem;">Simplificado</label></td>
                        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="text" name="tabla_orden[regimen_simplificado_obs]" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        </td>
                    </tr>
                </tbody>

                <!-- SE HA VENDIDO -->
                <tr class="fila-grupo">
                    <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px; border: 1px solid #ddd;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>✅ SE HA VENDIDO</span>
                            <button type="button" onclick="agregarFilaEspecificacion('vendido')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_vendido">
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><label style="margin: 0; font-size: 0.8rem;">Sí</label></td>
                        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="text" name="tabla_orden[vendido_si_obs]" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><label style="margin: 0; font-size: 0.8rem;">No</label></td>
                        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="text" name="tabla_orden[vendido_no_obs]" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        </td>
                    </tr>
                </tbody>

                <!-- ÚLTIMA VENTA -->
                <tr class="fila-grupo">
                    <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px; border: 1px solid #ddd;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>📅 ÚLTIMA VENTA</span>
                            <button type="button" onclick="agregarFilaEspecificacion('ultima_venta')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_ultima_venta">
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><label style="margin: 0; font-size: 0.8rem;">Mes pasado</label></td>
                        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="text" name="tabla_orden[ultima_venta_mes_obs]" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><label style="margin: 0; font-size: 0.8rem;">Hace 2 meses</label></td>
                        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="text" name="tabla_orden[ultima_venta_dos_meses_obs]" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><label style="margin: 0; font-size: 0.8rem;">Más de 2 meses</label></td>
                        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="text" name="tabla_orden[ultima_venta_mas_meses_obs]" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        </td>
                    </tr>
                </tbody>

                <!-- FLETE DE ENVÍO -->
                <tr class="fila-grupo">
                    <td colspan="3" style="font-weight: 600; background: #ffc107; padding: 10px; border: 1px solid #ddd;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>🚚 FLETE DE ENVÍO</span>
                            <button type="button" onclick="agregarFilaEspecificacion('flete')" style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">+</button>
                        </div>
                    </td>
                </tr>
                <tbody id="tbody_flete">
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><label style="margin: 0; font-size: 0.8rem;">Incluido</label></td>
                        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="text" name="tabla_orden[flete_incluido_obs]" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><label style="margin: 0; font-size: 0.8rem;">No incluido</label></td>
                        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
                            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <input type="text" name="tabla_orden[flete_no_incluido_obs]" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                        </td>
                    </tr>
                </tbody>
            </tbody>
        </table>

        <!-- Footer -->
        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 2px solid #ffc107; display: flex; gap: 0.75rem; justify-content: flex-end;">
            <button type="button" onclick="cerrarModalEspecificaciones()" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%); border: 2px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 600; color: #333; font-size: 0.85rem; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #e8e8e8 0%, #d5d5d5 100%)'; this.style.borderColor='#999'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)';" onmouseout="this.style.background='linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%)'; this.style.borderColor='#ddd'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <i class="fas fa-times" style="font-size: 0.85rem;"></i> CANCELAR
            </button>
            <button type="button" onclick="guardarEspecificaciones()" style="padding: 0.5rem 1.2rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); border: 2px solid #003d7a; border-radius: 6px; cursor: pointer; font-weight: 600; color: white; font-size: 0.85rem; transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;" onmouseover="this.style.background='linear-gradient(135deg, #0052a3 0%, #003d7a 100%)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(0, 102, 204, 0.2)';" onmouseout="this.style.background='linear-gradient(135deg, #0066cc 0%, #0052a3 100%)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                <i class="fas fa-check" style="font-size: 0.85rem;"></i> GUARDAR
            </button>
        </div>
    </div>
</div>

<!-- TEMPLATE PARA PRODUCTO DE PRENDA -->
<template id="productoTemplate">
    <div class="producto-card" data-producto-id="">
        <div class="producto-header">
            <h4 class="producto-titulo">PRENDA <span class="numero-producto">1</span></h4>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn-toggle-product" onclick="toggleProductoBody(this)" title="Expandir/Contraer" style="font-size: 1.5rem; line-height: 1; font-weight: bold;">▼</button>
                <button type="button" class="btn-remove-product" onclick="eliminarProductoPrenda(this)" title="Eliminar prenda">&times;</button>
            </div>
        </div>
        <div class="producto-body" style="display: block;">
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-shirt"></i> TIPO DE PRENDA</div>
                <div class="form-row tipo-prenda-row" style="display: flex; gap: 12px; align-items: flex-start;">
                    <div class="form-col full" style="flex: 1;">
                        <label><i class="fas fa-list"></i> SELECCIONA O ESCRIBE EL TIPO *</label>
                        <div class="prenda-search-container">
                            <input type="text" name="productos_prenda[][nombre_producto]" class="prenda-search-input input-large" placeholder="BUSCA O ESCRIBE (CAMISA, CAMISETA, POLO...)" required onkeyup="buscarPrendas(this); mostrarSelectorVariantes(this);" onchange="actualizarResumenPrenda(); mostrarSelectorVariantes(this);">
                            <div class="prenda-suggestions">
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👔 CAMISA', this)">👔 CAMISA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👕 CAMISETA', this)">👕 CAMISETA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🎽 POLO', this)">🎽 POLO</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👖 PANTALÓN', this)">👖 PANTALÓN</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('👗 FALDA', this)">👗 FALDA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🧥 CHAQUETA', this)">🧥 CHAQUETA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('🧢 SUDADERA', this)">🧢 SUDADERA</div>
                                <div class="prenda-suggestion-item" onclick="seleccionarPrenda('❓ OTRO', this)">❓ OTRO</div>
                            </div>
                        </div>
                        <small class="help-text">PUEDES BUSCAR, SELECCIONAR O ESCRIBIR UNA PRENDA PERSONALIZADA</small>
                    </div>
                    <!-- Selector de Tipo de JEAN/PANTALÓN - Oculto por defecto -->
                    <div class="tipo-jean-pantalon-inline" style="display: none; width: 280px; padding: 0; background: transparent; border: none; border-radius: 0; margin-left: 12px; flex-shrink: 0;">
                        <div class="tipo-jean-pantalon-inline-container" style="display: flex; flex-direction: column; gap: 4px;">
                            <!-- El selector se inserta aquí dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE DESCRIPCIÓN -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-sticky-note"></i> DESCRIPCIÓN</div>
                <div class="form-row">
                    <div class="form-col full">
                        <label><i class="fas fa-pen"></i> DESCRIPCIÓN</label>
                        <textarea name="productos_prenda[][descripcion]" class="input-large" placeholder="DESCRIPCIÓN DE LA PRENDA..." rows="2"></textarea>
                        <small class="help-text">DESCRIBE LA PRENDA, DETALLES ESPECIALES, LOGO, BORDADO, ESTAMPADO, ETC.</small>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE COLOR, TELA Y REFERENCIA -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-palette"></i> COLOR, TELA Y REFERENCIA</div>
                <div class="form-row">
                    <div class="form-col full">
                        <table style="width: 100%; border-collapse: collapse; background: white;">
                            <thead>
                                <tr style="background-color: #f0f0f0; border-bottom: 2px solid #0066cc;">
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd;">
                                        <i class="fas fa-palette"></i> Color
                                    </th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd;">
                                        <i class="fas fa-cloth"></i> Tela
                                    </th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #0066cc; border-right: 1px solid #ddd;">
                                        <i class="fas fa-barcode"></i> Referencia
                                    </th>
                                    <th style="padding: 12px; text-align: center; font-weight: 600; color: #0066cc;">
                                        <i class="fas fa-image"></i> Imagen Tela
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="border-bottom: 1px solid #ddd;">
                                    <td style="padding: 12px; border-right: 1px solid #ddd;">
                                        <div style="position: relative;">
                                            <input type="text" name="productos_prenda[][variantes][color]" class="color-input" placeholder="Buscar o crear color..." style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.9rem;">
                                        </div>
                                    </td>
                                    <td style="padding: 12px; border-right: 1px solid #ddd;">
                                        <div style="position: relative;">
                                            <input type="text" name="productos_prenda[][variantes][tela]" class="tela-input" placeholder="Buscar o crear tela..." style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.9rem;">
                                        </div>
                                    </td>
                                    <td style="padding: 12px; border-right: 1px solid #ddd;">
                                        <input type="text" name="productos_prenda[][variantes][referencia]" class="referencia-input" placeholder="Ej: REF-NAP-001" style="width: 100%; padding: 8px; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.9rem;">
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <label style="display: block; min-height: 60px; padding: 0.5rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="event.preventDefault(); if(event.dataTransfer.files) this.querySelector('input').files = event.dataTransfer.files; this.querySelector('input').onchange && this.querySelector('input').onchange();" ondragover="event.preventDefault(); this.style.background='#e8f4f8';" ondragleave="this.style.background='#f0f7ff';">
                                            <input type="file" name="productos_prenda[][telas][]" class="input-file-tela" accept="image/*" multiple onchange="agregarFotosAlProducto(this)" style="display: none;">
                                            <div class="drop-zone-content" style="font-size: 0.7rem;">
                                                <i class="fas fa-cloud-upload-alt" style="font-size: 0.9rem; color: #0066cc;"></i>
                                                <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">ARRASTRA O CLIC</p>
                                                <small style="color: #666;">(Máx. 3)</small>
                                            </div>
                                        </label>
                                        <div class="foto-tela-preview" style="display: grid; grid-template-columns: repeat(3, 50px); gap: 0.4rem; margin-top: 0.5rem; justify-content: center;"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE FOTOS DE LA PRENDA -->
            <div class="producto-section">
                <button type="button" class="section-title-btn" onclick="toggleSeccion(this)" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer; padding: 0;">
                    <div class="section-title">
                        <i class="fas fa-images"></i> FOTOS DE LA PRENDA (MÁX. 3)
                        <i class="fas fa-chevron-down" style="margin-left: auto; transition: transform 0.3s ease;"></i>
                    </div>
                </button>
                <div class="section-content">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem; color: #0066cc; font-size: 0.85rem;">
                            <i class="fas fa-image"></i> FOTOS PRENDA
                        </label>
                        <label style="display: block; min-height: 80px; padding: 0.75rem; border: 2px dashed #0066cc; border-radius: 6px; cursor: pointer; text-align: center; background: #f0f7ff;" ondrop="event.preventDefault(); if(event.dataTransfer.files) this.querySelector('input').files = event.dataTransfer.files; this.querySelector('input').onchange && this.querySelector('input').onchange();" ondragover="event.preventDefault(); this.style.background='#e8f4f8';" ondragleave="this.style.background='#f0f7ff';">
                            <input type="file" name="productos_prenda[][fotos][]" class="input-file-single" accept="image/*" multiple onchange="agregarFotosAlProducto(this)" style="display: none;">
                            <div class="drop-zone-content" style="font-size: 0.75rem;">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 1rem; color: #0066cc;"></i>
                                <p style="margin: 0.25rem 0; color: #0066cc; font-weight: 500;">ARRASTRA O CLIC</p>
                            </div>
                        </label>
                        <div class="fotos-preview" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.4rem; margin-top: 0.5rem;"></div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE VARIACIONES ESPECÍFICAS -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-sliders-h"></i> VARIACIONES ESPECÍFICAS</div>
                <div class="form-row">
                    <div class="form-col full">
                        <table style="width: 100%; border-collapse: collapse; background: white; margin: 0; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #0066cc, #0052a3); border-bottom: 2px solid #0066cc;">
                                    <th style="padding: 14px 12px; text-align: center; font-weight: 600; color: white; border-right: 1px solid #0052a3; width: 60px;">
                                        <i class="fas fa-check-circle"></i>
                                    </th>
                                    <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: white; border-right: 1px solid #0052a3; width: 160px;">
                                        <i class="fas fa-list"></i> Variación
                                    </th>
                                    <th style="padding: 14px 12px; text-align: left; font-weight: 600; color: white;">
                                        <i class="fas fa-comment"></i> Observación
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- MANGA -->
                                <tr style="border-bottom: 1px solid #eee; background-color: #fafafa;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" name="productos_prenda[][variantes][aplica_manga]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-shirt"></i> Manga
                                    </td>
                                    <td style="padding: 14px 12px; display: flex; gap: 8px;">
                                        <select name="productos_prenda[][variantes][tipo_manga]" style="flex: 1; padding: 8px 12px; border: 1px solid #0066cc; border-radius: 4px; font-size: 0.9rem; background-color: white; color: #0066cc; font-weight: 600; cursor: pointer;">
                                            <option value="">Selecciona tipo...</option>
                                            <option value="CORTA">CORTA</option>
                                            <option value="LARGA">LARGA</option>
                                            <option value="3/4">3/4</option>
                                            <option value="RAGLAN">RAGLAN</option>
                                            <option value="CAMPANA">CAMPANA</option>
                                            <option value="OTRA">OTRA</option>
                                        </select>
                                        <input type="text" name="productos_prenda[][variantes][obs_manga]" placeholder="Observaciones..." style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box;">
                                    </td>
                                </tr>
                                
                                <!-- BOLSILLOS -->
                                <tr style="border-bottom: 1px solid #eee; background-color: white;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" name="productos_prenda[][variantes][aplica_bolsillos]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-square"></i> Bolsillos
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="text" name="productos_prenda[][variantes][obs_bolsillos]" placeholder="Ej: 4 bolsillos, con cierre..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box;">
                                    </td>
                                </tr>
                                
                                <!-- BROCHE/BOTÓN -->
                                <tr style="border-bottom: 1px solid #eee; background-color: #fafafa;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" name="productos_prenda[][variantes][aplica_broche]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-link"></i> Broche/Botón
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="text" name="productos_prenda[][variantes][obs_broche]" placeholder="Ej: Botones de madera..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box;">
                                    </td>
                                </tr>
                                
                                <!-- REFLECTIVO -->
                                <tr style="border-bottom: 1px solid #eee; background-color: white;">
                                    <td style="padding: 14px 12px; text-align: center; border-right: 1px solid #eee;">
                                        <input type="checkbox" name="productos_prenda[][variantes][aplica_reflectivo]" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;">
                                    </td>
                                    <td style="padding: 14px 12px; border-right: 1px solid #eee; font-weight: 600; color: #0066cc; white-space: nowrap;">
                                        <i class="fas fa-star"></i> Reflectivo
                                    </td>
                                    <td style="padding: 14px 12px;">
                                        <input type="text" name="productos_prenda[][variantes][obs_reflectivo]" placeholder="Ej: En brazos y espalda..." style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; box-sizing: border-box;">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DE TALLAS -->
            <div class="producto-section">
                <div class="section-title"><i class="fas fa-ruler"></i> TALLAS A COTIZAR</div>
                <div class="form-row">
                    <div class="form-col full">
                        <!-- Fila 1: Selectores de tipo, género y modo -->
                        <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
                            <select class="talla-tipo-select" onchange="actualizarSelectTallas(this)" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 300px;">
                                <option value="">Selecciona tipo de talla</option>
                                <option value="letra">LETRAS (XS, S, M, L, XL...)</option>
                                <option value="numero">NÚMEROS (DAMA/CABALLERO)</option>
                            </select>
                            
                            <select class="talla-genero-select" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 200px; display: none;">
                                <option value="">Selecciona género</option>
                                <option value="dama">DAMA</option>
                                <option value="caballero">CABALLERO</option>
                            </select>
                            
                            <select class="talla-modo-select" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 200px; display: none;">
                                <option value="">Selecciona modo</option>
                                <option value="manual">Manual</option>
                                <option value="rango">Rango (Desde-Hasta)</option>
                            </select>
                            
                            <!-- Selectores de rango (aparecen cuando se selecciona Rango) -->
                            <div class="talla-rango-selectors" style="display: none; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                                <select class="talla-desde" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 150px;">
                                    <option value="">Desde</option>
                                </select>
                                <span style="color: #0066cc; font-weight: 600;">hasta</span>
                                <select class="talla-hasta" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 150px;">
                                    <option value="">Hasta</option>
                                </select>
                                <button type="button" class="btn-agregar-rango" onclick="agregarTallasRango(this)" style="padding: 0.6rem 1rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; white-space: nowrap;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Fila 2: Botones de tallas (Modo Manual) -->
                        <div class="talla-botones" style="display: none; margin-bottom: 1.5rem;">
                            <p style="margin: 0 0 0.75rem 0; font-size: 0.85rem; font-weight: 600; color: #0066cc;">Selecciona tallas:</p>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                                <div class="talla-botones-container" style="display: flex; flex-wrap: wrap; gap: 0.5rem; flex: 1;">
                                </div>
                                <button type="button" class="btn-agregar-tallas-seleccionadas" onclick="agregarTallasSeleccionadas(this)" style="padding: 0.6rem 1rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 1rem; white-space: nowrap; flex-shrink: 0;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Fila 3: Tallas agregadas -->
                        <div class="tallas-section" style="display: none; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 0.75rem 0; font-size: 0.85rem; font-weight: 600; color: #0066cc;">Tallas seleccionadas:</p>
                            <div class="tallas-agregadas" style="display: flex; flex-wrap: wrap; gap: 0.5rem; min-height: 35px;">
                                <input type="hidden" name="productos_prenda[][tallas]" class="tallas-hidden" value="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
// Inicializar fecha
document.addEventListener('DOMContentLoaded', function() {
    // Agregar primer producto
    agregarProductoPrenda();
});

// Agregar producto
function agregarProductoPrenda() {
    const template = document.getElementById('productoTemplate');
    const clone = template.content.cloneNode(true);
    const contenedor = document.getElementById('productosContainer');
    const numeroProducto = contenedor.querySelectorAll('.producto-card').length + 1;
    
    const productoCard = clone.querySelector('.producto-card');
    const productoId = 'producto-' + Date.now() + '-' + numeroProducto;
    productoCard.dataset.productoId = productoId;
    
    // Inicializar arrays de fotos y telas para este producto
    fotosSeleccionadas[productoId] = [];
    telasSeleccionadas[productoId] = [];
    
    clone.querySelector('.numero-producto').textContent = numeroProducto;
    contenedor.appendChild(clone);
}

// Eliminar producto
function eliminarProductoPrenda(btn) {
    const card = btn.closest('.producto-card');
    card.remove();

    // Actualizar números
    document.querySelectorAll('.numero-producto').forEach((el, idx) => {
        el.textContent = idx + 1;
    });
}

// Toggle producto body
function toggleProductoBody(btn) {
    const body = btn.closest('.producto-card').querySelector('.producto-body');
    body.style.display = body.style.display === 'none' ? 'block' : 'none';
    btn.textContent = body.style.display === 'none' ? '▶' : '▼';
}

// Actualizar selector de tallas
function actualizarSelectTallas(select) {
    const tipoSeleccionado = select.value;
    const generoSelect = select.closest('.form-col').querySelector('.talla-genero-select');
    const modoSelect = select.closest('.form-col').querySelector('.talla-modo-select');
    const tallaBotones = select.closest('.form-col').querySelector('.talla-botones');
    const tallasSection = select.closest('.form-col').querySelector('.tallas-section');
    const rangoSelectors = select.closest('.form-col').querySelector('.talla-rango-selectors');

    if (tipoSeleccionado === 'letra') {
        generoSelect.style.display = 'none';
        modoSelect.style.display = 'block';
        modoSelect.value = 'manual'; // Por defecto manual
        
        // Crear botones de tallas
        const container = select.closest('.form-col').querySelector('.talla-botones-container');
        container.innerHTML = '';
        const tallasPorLetra = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        tallasPorLetra.forEach(talla => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'talla-btn';
            btn.textContent = talla;
            btn.setAttribute('data-talla', talla);
            btn.style.cssText = 'padding: 8px 16px; border: 2px solid #0066cc; border-radius: 6px; background: white; color: #0066cc; cursor: pointer; font-weight: 600; transition: all 0.2s;';
            btn.onclick = function(e) {
                e.preventDefault();
                this.style.background = this.style.background === 'rgb(0, 102, 204)' ? 'white' : '#0066cc';
                this.style.color = this.style.color === 'rgb(255, 255, 255)' ? '#0066cc' : 'white';
            };
            container.appendChild(btn);
        });
        
        tallaBotones.style.display = 'block';
        tallasSection.style.display = 'block';
        rangoSelectors.style.display = 'none';
    } else if (tipoSeleccionado === 'numero') {
        generoSelect.style.display = 'block';
        modoSelect.style.display = 'block';
        tallaBotones.style.display = 'none';
        tallasSection.style.display = 'none';
        rangoSelectors.style.display = 'none';
        
        // Listener para modo
        modoSelect.onchange = function() {
            if (this.value === 'rango') {
                rangoSelectors.style.display = 'flex';
                tallaBotones.style.display = 'none';
                
                // Llenar selectores de rango según el género
                const genero = generoSelect.value;
                const tallasDama = ['32', '34', '36', '38', '40', '42', '44'];
                const tallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42'];
                const tallas = genero === 'dama' ? tallasDama : tallasCaballero;
                
                // Llenar selectors desde y hasta
                const desdeSelect = rangoSelectors.querySelector('.talla-desde');
                const hastaSelect = rangoSelectors.querySelector('.talla-hasta');
                
                desdeSelect.innerHTML = '<option value="">Desde</option>';
                hastaSelect.innerHTML = '<option value="">Hasta</option>';
                
                tallas.forEach(talla => {
                    const optionDesde = document.createElement('option');
                    optionDesde.value = talla;
                    optionDesde.textContent = talla;
                    desdeSelect.appendChild(optionDesde);
                    
                    const optionHasta = document.createElement('option');
                    optionHasta.value = talla;
                    optionHasta.textContent = talla;
                    hastaSelect.appendChild(optionHasta);
                });
            } else {
                rangoSelectors.style.display = 'none';
                tallaBotones.style.display = 'none';
            }
        };
    } else {
        generoSelect.style.display = 'none';
        modoSelect.style.display = 'none';
        tallaBotones.style.display = 'none';
        tallasSection.style.display = 'none';
        rangoSelectors.style.display = 'none';
    }
}

// Agregar tallas desde rango
function agregarTallasRango(btn) {
    const form = btn.closest('.form-col');
    const desdeSelect = form.querySelector('.talla-desde');
    const hastaSelect = form.querySelector('.talla-hasta');
    const tallasAgregadas = form.querySelector('.tallas-agregadas');
    const tallasHidden = form.querySelector('.tallas-hidden');
    
    const desde = parseInt(desdeSelect.value);
    const hasta = parseInt(hastaSelect.value);
    
    if (!desde || !hasta) {
        alert('Selecciona ambos valores');
        return;
    }
    
    if (desde > hasta) {
        alert('El valor "Desde" no puede ser mayor que "Hasta"');
        return;
    }
    
    // Generar tallas en el rango
    const tallasSeleccionadas = [];
    for (let i = desde; i <= hasta; i++) {
        tallasSeleccionadas.push(i.toString());
    }
    
    // Mostrar tallas
    tallasAgregadas.innerHTML = '';
    tallasSeleccionadas.forEach(talla => {
        const badge = document.createElement('span');
        badge.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;';
        badge.innerHTML = `${talla} <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.1rem; padding: 0; line-height: 1;">×</button>`;
        tallasAgregadas.appendChild(badge);
    });
    
    form.querySelector('.tallas-section').style.display = 'block';
    tallasHidden.value = JSON.stringify(tallasSeleccionadas);
}

// Agregar tallas seleccionadas
function agregarTallasSeleccionadas(btn) {
    const form = btn.closest('.form-col');
    const container = form.querySelector('.talla-botones-container');
    const tallasAgregadas = form.querySelector('.tallas-agregadas');
    const tallasHidden = form.querySelector('.tallas-hidden');
    
    const tallasBotones = container.querySelectorAll('.talla-btn');
    const tallasSeleccionadas = [];
    
    tallasBotones.forEach(btn => {
        if (btn.style.background === 'rgb(0, 102, 204)') {
            tallasSeleccionadas.push(btn.getAttribute('data-talla'));
        }
    });

    if (tallasSeleccionadas.length === 0) {
        alert('Selecciona al menos una talla');
        return;
    }

    // Limpiar y agregar nuevas tallas
    tallasAgregadas.innerHTML = '';
    tallasSeleccionadas.forEach(talla => {
        const badge = document.createElement('span');
        badge.style.cssText = 'background: #0066cc; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;';
        badge.innerHTML = `${talla} <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: white; cursor: pointer; font-size: 1.1rem; padding: 0; line-height: 1;">×</button>`;
        tallasAgregadas.appendChild(badge);
    });

    tallasHidden.value = JSON.stringify(tallasSeleccionadas);
}

// Sincronizar header
document.addEventListener('DOMContentLoaded', function() {
    const headerCliente = document.getElementById('header-cliente');
    const clienteInput = document.getElementById('cliente');
    const headerTipoCotizacion = document.getElementById('header-tipo-cotizacion');
    const tipoCotizacionInput = document.getElementById('tipo_cotizacion');
    const btnGuardarBorrador = document.getElementById('btnGuardarBorrador');
    const btnEnviar = document.getElementById('btnEnviar');
    
    // Función para actualizar estado de botones
    function actualizarEstadoBotones() {
        const tipoSeleccionado = headerTipoCotizacion && headerTipoCotizacion.value;
        const deshabilitado = !tipoSeleccionado;
        
        if (btnGuardarBorrador) {
            btnGuardarBorrador.disabled = deshabilitado;
            btnGuardarBorrador.style.opacity = deshabilitado ? '0.5' : '1';
            btnGuardarBorrador.style.cursor = deshabilitado ? 'not-allowed' : 'pointer';
            btnGuardarBorrador.title = deshabilitado ? 'Selecciona un tipo de cotización (M, D, X) para continuar' : '';
        }
        
        if (btnEnviar) {
            btnEnviar.disabled = deshabilitado;
            btnEnviar.style.opacity = deshabilitado ? '0.5' : '1';
            btnEnviar.style.cursor = deshabilitado ? 'not-allowed' : 'pointer';
            btnEnviar.title = deshabilitado ? 'Selecciona un tipo de cotización (M, D, X) para continuar' : '';
        }
    }
    
    if (headerCliente) {
        headerCliente.addEventListener('input', function() {
            clienteInput.value = this.value;
        });
    }
    
    if (headerTipoCotizacion) {
        // Deshabilitar botones inicialmente
        actualizarEstadoBotones();
        
        // Escuchar cambios en el select
        headerTipoCotizacion.addEventListener('change', function() {
            tipoCotizacionInput.value = this.value;
            actualizarEstadoBotones();
        });
    }
});

// Guardar cotización
function guardarCotizacionPrenda(action) {
    // Obtener valores del HEADER (no del formulario oculto)
    const clienteHeader = document.getElementById('header-cliente').value;
    const tipoCotizacion = document.getElementById('header-tipo-cotizacion').value;
    const selectTipo = document.getElementById('header-tipo-cotizacion');
    const errorTipo = document.getElementById('error-tipo-cotizacion');
    const inputHeaderCliente = document.getElementById('header-cliente');

    // Validar cliente
    if (!clienteHeader || !clienteHeader.trim()) {
        inputHeaderCliente.classList.add('campo-invalido');
        inputHeaderCliente.style.borderColor = '#ff4444';
        alert('⚠️ Por favor escribe el NOMBRE DEL CLIENTE en el encabezado');
        return;
    } else {
        inputHeaderCliente.classList.remove('campo-invalido');
        inputHeaderCliente.style.borderColor = '';
    }

    // Validar que el tipo de cotización esté seleccionado
    if (!tipoCotizacion) {
        selectTipo.classList.add('campo-invalido');
        errorTipo.style.display = 'block';
        alert('⚠️ Por favor selecciona el TIPO DE COTIZACIÓN (M, D o X)');
        return;
    } else {
        selectTipo.classList.remove('campo-invalido');
        errorTipo.style.display = 'none';
    }

    // Validar que haya al menos un producto con nombre
    const productos = document.querySelectorAll('[name*="nombre_producto"]');
    let tieneAlgunProducto = false;

    productos.forEach(input => {
        if (input.value.trim()) {
            tieneAlgunProducto = true;
        }
    });

    if (!tieneAlgunProducto) {
        alert('⚠️ Por favor agrega al menos una prenda');
        return;
    }

    const form = document.getElementById('cotizacionPrendaForm');
    
    // ✅ ARMAR PRODUCTOS CORRECTAMENTE - MÉTODO 2: Iterar sobre cada producto-card
    const productosFinales = [];
    
    // Encontrar todos los contenedores de producto (producto-card)
    const productCards = form.querySelectorAll('.producto-card');
    
    console.log(`=== REORGANIZANDO ${productCards.length} PRODUCTOS ===`);
    
    productCards.forEach((card, cardIndex) => {
        console.log(`\n--- PRODUCTO ${cardIndex} ---`);
        
        // Obtener productoId del data attribute
        const productoId = card.dataset.productoId || '';
        console.log(`📦 Producto ID: ${productoId}`);
        
        // Crear producto vacío
        const producto = {
            nombre_producto: '',
            descripcion: '',
            tallas: [],
            fotos: [],
            telas: [],
            variantes: {}
        };
        
        // === BUSCAR NOMBRE ===
        const inputNombre = card.querySelector('input[name*="nombre_producto"]');
        if (inputNombre) {
            producto.nombre_producto = inputNombre.value.trim();
            console.log(`✓ Nombre: ${producto.nombre_producto}`);
        }
        
        // === BUSCAR DESCRIPCIÓN ===
        const inputDesc = card.querySelector('textarea[name*="descripcion"]');
        if (inputDesc) {
            producto.descripcion = inputDesc.value.trim();
            console.log(`✓ Descripción: ${producto.descripcion}`);
        }
        
        // === BUSCAR TALLAS (input hidden con valor "S, M") ===
        const inputTallas = card.querySelector('input[name*="tallas"]');
        if (inputTallas && inputTallas.value) {
            const tallasStr = inputTallas.value;
            // Si es string con comas, dividir
            if (typeof tallasStr === 'string' && tallasStr.includes(',')) {
                producto.tallas = tallasStr.split(',').map(t => t.trim()).filter(t => t);
            } else if (typeof tallasStr === 'string') {
                producto.tallas = [tallasStr.trim()];
            }
            console.log(`✓ Tallas: ${JSON.stringify(producto.tallas)}`);
        }
        
        // === BUSCAR FOTOS (desde fotosSeleccionadas en memoria) ===
        // Las fotos se guardan en memoria por agregarFotos, no en el input HTML
        if (fotosSeleccionadas[productoId] && fotosSeleccionadas[productoId].length > 0) {
            fotosSeleccionadas[productoId].forEach(foto => {
                producto.fotos.push(foto); // ← Agregar FILE OBJECT desde memoria
            });
            console.log(`✓ Fotos (${producto.fotos.length}): ${JSON.stringify(producto.fotos.map(f => f.name))}`);
        } else {
            console.log(`ℹ️ No hay fotos guardadas para este producto`);
        }
        
        // === BUSCAR TELAS (desde telasSeleccionadas en memoria) ===
        // Las telas se guardan en memoria por agregarFotoTela, no en el input HTML
        if (telasSeleccionadas[productoId] && telasSeleccionadas[productoId].length > 0) {
            telasSeleccionadas[productoId].forEach(tela => {
                producto.telas.push(tela); // ← Agregar FILE OBJECT desde memoria
            });
            console.log(`✓ Telas (${producto.telas.length}): ${JSON.stringify(producto.telas.map(t => t.name))}`);
        } else {
            console.log(`ℹ️ No hay telas guardadas para este producto`);
        }
        
        // === BUSCAR VARIANTES (todos los inputs dentro de este card) ===
        const allInputs = card.querySelectorAll('input, select');
        allInputs.forEach(input => {
            const name = input.name;
            if (!name || !name.includes('variantes')) return;
            
            // Extraer el nombre del campo de variante
            const match = name.match(/\[variantes\]\[([^\]]+)\]/);
            if (match) {
                const campo = match[1];
                // Solo agregar si tiene valor y no es vacío
                if (input.value !== null && input.value !== undefined && input.value !== '') {
                    producto.variantes[campo] = input.value;
                }
            }
        });
        console.log(`✓ Variantes (${Object.keys(producto.variantes).length}): ${JSON.stringify(producto.variantes)}`);
        
        // === AGREGAR SOLO SI TIENE NOMBRE ===
        if (producto.nombre_producto) {
            productosFinales.push(producto);
            console.log(`✅ PRODUCTO AGREGADO`);
        } else {
            console.log(`⚠️ PRODUCTO SIN NOMBRE - IGNORADO`);
        }
    });
    
    console.log('\n=== PRODUCTOS FINALES ===');
    console.log(JSON.stringify(productosFinales, null, 2));
    
    // === CREAR FormData LIMPIO ===
    const formData = new FormData();
    
    // Agregar datos básicos
    formData.append('cliente', document.getElementById('header-cliente').value);
    formData.append('asesora', document.getElementById('header-asesor').value);
    formData.append('fecha', document.getElementById('header-fecha').value);
    formData.append('tipo_cotizacion', document.getElementById('header-tipo-cotizacion').value);
    formData.append('action', action);
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    
    // ✅ AGREGAR ESPECIFICACIONES AL FormData
    if (window.especificacionesSeleccionadas && Object.keys(window.especificacionesSeleccionadas).length > 0) {
        formData.append('especificaciones', JSON.stringify(window.especificacionesSeleccionadas));
        console.log('📋 Especificaciones agregadas al FormData:', window.especificacionesSeleccionadas);
    } else {
        console.log('⚠️ Sin especificaciones para agregar');
    }
    
    // Agregar productos reorganizados
    productosFinales.forEach((producto, index) => {
        // Datos básicos
        formData.append(`productos_prenda[${index}][nombre_producto]`, producto.nombre_producto);
        formData.append(`productos_prenda[${index}][descripcion]`, producto.descripcion || '');
        
        // Tallas como array
        producto.tallas.forEach((talla, tallaIdx) => {
            formData.append(`productos_prenda[${index}][tallas][${tallaIdx}]`, talla);
        });
        
        // Fotos como array - AGREGAR FILE OBJECTS
        producto.fotos.forEach((foto, fotoIdx) => {
            if (foto instanceof File) {
                formData.append(`productos_prenda[${index}][fotos][${fotoIdx}]`, foto, foto.name);
            } else {
                formData.append(`productos_prenda[${index}][fotos][${fotoIdx}]`, foto);
            }
        });
        
        // Telas como array - AGREGAR FILE OBJECTS
        producto.telas.forEach((tela, telaIdx) => {
            if (tela instanceof File) {
                formData.append(`productos_prenda[${index}][telas][${telaIdx}]`, tela, tela.name);
            } else {
                formData.append(`productos_prenda[${index}][telas][${telaIdx}]`, tela);
            }
        });
        
        // Variantes
        Object.keys(producto.variantes).forEach(campo => {
            formData.append(`productos_prenda[${index}][variantes][${campo}]`, producto.variantes[campo]);
        });
    });
    
    console.log('=== FormData FINAL A ENVIAR ===');
    for (let pair of formData.entries()) {
        console.log(`${pair[0]}: ${pair[1]}`);
    }

    fetch('{{ route("asesores.cotizaciones-prenda.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            alert('Error: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}

function actualizarResumenPrenda() {
    console.log('Resumen actualizado');
}

// Buscar prendas
function buscarPrendas(input) {
    const valor = input.value.toLowerCase();
    const suggestions = input.closest('.prenda-search-container').querySelector('.prenda-suggestions');
    const items = suggestions.querySelectorAll('.prenda-suggestion-item');
    
    items.forEach(item => {
        if (item.textContent.toLowerCase().includes(valor)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Seleccionar prenda
function seleccionarPrenda(prenda, element) {
    const input = element.closest('.prenda-search-container').querySelector('.prenda-search-input');
    input.value = prenda;
    element.closest('.prenda-search-container').querySelector('.prenda-suggestions').style.display = 'none';
    mostrarSelectorVariantes(input);
    actualizarResumenPrenda();
}

// Mostrar selector de variantes (JEAN/PANTALÓN)
function mostrarSelectorVariantes(input) {
    const valor = input.value.toUpperCase();
    const container = input.closest('.tipo-prenda-row').querySelector('.tipo-jean-pantalon-inline');
    const innerContainer = container.querySelector('.tipo-jean-pantalon-inline-container');
    
    // Detectar JEAN, PANTALÓN, PANTALONES
    if (valor.includes('JEAN') || valor.includes('PANTALÓN') || valor.includes('PANTALONES') || valor.includes('PANTALON')) {
        container.style.display = 'block';
        
        // Si no tiene contenido, crear los selectores
        if (innerContainer.innerHTML === '') {
            innerContainer.innerHTML = `
                <label style="font-weight: 600; color: #0066cc; font-size: 0.85rem;">TIPO DE PRENDA</label>
                <select name="productos_prenda[][tipo_jean]" style="padding: 0.6rem 0.8rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600;">
                    <option value="">Selecciona</option>
                    <option value="SKINNY">SKINNY</option>
                    <option value="SLIM">SLIM</option>
                    <option value="RECTO">RECTO</option>
                    <option value="BOOTCUT">BOOTCUT</option>
                    <option value="FLARE">FLARE</option>
                    <option value="MOM">MOM</option>
                    <option value="OVERSIZE">OVERSIZE</option>
                    <option value="OTRO">OTRO</option>
                    <option value="NO APLICA">NO APLICA</option>
                </select>
            `;
        }
    } else {
        container.style.display = 'none';
        innerContainer.innerHTML = '';
    }
}

// ============ GESTIÓN DE IMÁGENES - DELEGADO A productos.js ============

// Wrapper para compatibilidad con productos.js
function agregarFotosAlProducto(input) {
    // Detectar si es una imagen de tela o una foto de prenda
    const isTela = input.classList.contains('input-file-tela');
    const isFoto = input.classList.contains('input-file-single');
    
    console.log('🖼️ Imagen detectada:', { isTela, isFoto, className: input.className });
    
    if (isTela && typeof agregarFotoTela === 'function') {
        // Es una tela - llamar función específica para telas
        console.log('✓ Procesando como TELA');
        agregarFotoTela(input);
    } else if (isFoto && typeof agregarFotos === 'function') {
        // Es una foto de prenda - llamar función específica para fotos
        console.log('✓ Procesando como FOTO DE PRENDA');
        const dropZone = input.closest('label');
        if (dropZone) {
            agregarFotos(input.files, dropZone);
        }
    } else {
        console.warn('⚠️ No se identificó tipo de imagen o función no disponible');
        console.log('   Funciones disponibles:', {
            agregarFotos: typeof agregarFotos,
            agregarFotoTela: typeof agregarFotoTela
        });
    }
}

// Abrir modal de especificaciones
function abrirModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    if (modal) modal.style.display = 'flex';
}

// Cerrar modal de especificaciones
function cerrarModalEspecificaciones() {
    const modal = document.getElementById('modalEspecificaciones');
    if (modal) modal.style.display = 'none';
}

// Guardar especificaciones
function guardarEspecificaciones() {
    const especificaciones = {};
    const modal = document.getElementById('modalEspecificaciones');
    if (!modal) return;
    
    // Mapeo de categorías
    const categoriasMap = {
        'tbody_disponibilidad': 'disponibilidad',
        'tbody_pago': 'forma_pago',
        'tbody_regimen': 'regimen',
        'tbody_vendido': 'se_ha_vendido',
        'tbody_ultima_venta': 'ultima_venta',
        'tbody_flete': 'flete'
    };
    
    console.log('🔍 Buscando especificaciones en modal...');
    
    // Procesar cada categoría
    Object.entries(categoriasMap).forEach(([tbodyId, categoriaKey]) => {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) {
            console.warn(`⚠️ No encontrado: ${tbodyId}`);
            return;
        }
        
        console.log(`📋 Procesando ${categoriaKey} (${tbodyId})`);
        
        const filas = tbody.querySelectorAll('tr');
        const valoresSeleccionados = [];
        
        console.log(`   Encontradas ${filas.length} filas`);
        
        filas.forEach((fila, filaIndex) => {
            const checkbox = fila.querySelector('input[type="checkbox"]');
            const itemInput = fila.querySelector('input[type="text"]');
            const label = fila.querySelector('label');
            
            console.log(`   Fila ${filaIndex}: checkbox=${checkbox ? checkbox.checked : 'no'}, input=${itemInput ? 'sí' : 'no'}, label=${label ? label.textContent : 'no'}`);
            
            // Si está marcado, guardar el valor
            if (checkbox && checkbox.checked) {
                let valor = '';
                
                // Prioridad: label (para items fijos) > input value (para items personalizados) > "✓" (si solo está marcado)
                if (label) {
                    // Si es un label fijo (para categorías como DISPONIBILIDAD, FORMA DE PAGO, etc.)
                    valor = label.textContent.trim();
                } else if (itemInput && itemInput.value.trim()) {
                    // Si hay input con valor (para categorías como SE HA VENDIDO, ÚLTIMA VENTA)
                    valor = itemInput.value.trim();
                } else {
                    // Si solo está marcado sin valor, guardar "✓"
                    valor = '✓';
                }
                
                if (valor) {
                    valoresSeleccionados.push(valor);
                    console.log(`      ✅ Valor guardado: ${valor}`);
                }
            }
        });
        
        // Solo guardar la categoría si tiene valores seleccionados
        if (valoresSeleccionados.length > 0) {
            especificaciones[categoriaKey] = valoresSeleccionados;
            console.log(`✅ ${categoriaKey}: ${valoresSeleccionados.join(', ')}`);
        }
    });
    
    window.especificacionesSeleccionadas = especificaciones;
    console.log('✅ Especificaciones guardadas:', especificaciones);
    console.log('📊 Total categorías:', Object.keys(especificaciones).length);
    cerrarModalEspecificaciones();
}

// Agregar fila de especificación
function agregarFilaEspecificacion(categoria) {
    const tbodyId = 'tbody_' + categoria;
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    
    const fila = document.createElement('tr');
    fila.innerHTML = `
        <td style="padding: 10px; border: 1px solid #ddd;"><input type="text" name="tabla_orden[${categoria}_item]" class="input-compact" placeholder="Escribe aquí" style="width: 100%;"></td>
        <td style="text-align: center; padding: 10px; border: 1px solid #ddd;">
            <input type="checkbox" class="checkbox-guardar" style="width: 20px; height: 20px; cursor: pointer; accent-color: #10b981;">
        </td>
        <td style="padding: 10px; border: 1px solid #ddd;">
            <input type="text" name="tabla_orden[${categoria}_obs]" class="input-compact" placeholder="Observaciones" style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
        </td>
    `;
    tbody.appendChild(fila);
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('modalEspecificaciones');
    if (e.target === modal) {
        cerrarModalEspecificaciones();
    }
});
</script>

@push('scripts')
<script src="{{ asset('js/asesores/cotizaciones/tallas.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/persistencia.js') }}"></script>

<!-- Módulos del sistema de cotizaciones -->
<script src="{{ asset('js/asesores/cotizaciones/rutas.js') }}"></script>
<script>
    // Asignar rutas después de cargar rutas.js
    window.routes.guardarCotizacion = '{{ route("asesores.cotizaciones.guardar") }}';
    window.routes.cotizacionesIndex = '{{ route("asesores.cotizaciones.index") }}';
</script>
<script src="{{ asset('js/asesores/cotizaciones/cotizaciones.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/productos.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/imagenes.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/especificaciones.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/guardado.js') }}"></script>
<script src="{{ asset('js/asesores/cotizaciones/cargar-borrador.js') }}"></script>

<!-- Script de Variantes de Prendas -->
<script src="{{ asset('js/asesores/variantes-prendas.js') }}"></script>

<!-- Script de Color, Tela y Referencia (Find/Create) -->
<script src="{{ asset('js/asesores/color-tela-referencia.js') }}"></script>

<!-- Integración de Variantes en Paso 2 -->
<script src="{{ asset('js/asesores/cotizaciones/integracion-variantes-inline.js') }}"></script>

<script>
    // Asegurar que el sidebar está expandido en esta página
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            // Remover la clase collapsed para que empiece expandido
            sidebar.classList.remove('collapsed');
            console.log('✓ Sidebar expandido');
        }
    });
</script>
@endpush
@endsection