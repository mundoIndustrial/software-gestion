<!-- Modal de Registro de Pedido -->
<div id="modalRegistroPedido" class="modal-overlay-registro" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
    <div class="modal-content-registro" style="background: white; border-radius: 12px; max-width: 1000px; width: 95%; margin: 2rem auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
        
        <!-- Header del Modal -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 2rem; border-bottom: 1px solid #e5e7eb; sticky top: 0; background: white; z-index: 10;">
            <h2 style="font-size: 1.5rem; font-weight: 700; color: #1f2937; margin: 0;">
                <i class="fas fa-plus-circle" style="margin-right: 0.75rem; color: #10b981;"></i>Registrar Nuevo Pedido
            </h2>
            <button onclick="cerrarModalRegistroPedido()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">×</button>
        </div>

        <!-- Formulario -->
        <form id="formRegistroPedido" style="padding: 2rem;" onsubmit="enviarRegistroPedido(event)">
            @csrf

            <!-- Información Básica -->
            <div style="margin-bottom: 2rem;">
                <h3 style="font-weight: 700; color: #1f2937; margin-bottom: 1rem; font-size: 1.1rem;">
                    <i class="fas fa-info-circle" style="color: #3b82f6; margin-right: 0.5rem;"></i>Información Básica
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <!-- Cliente -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Cliente *</label>
                        <input type="text" 
                               id="registro_cliente" 
                               name="cliente"
                               placeholder="Nombre del cliente"
                               required
                               style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem;">
                    </div>

                    <!-- Asesora -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Asesor/a</label>
                        <input type="text" 
                               id="registro_asesora" 
                               name="asesora"
                               placeholder="Nombre del asesor/a"
                               style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem;">
                    </div>

                    <!-- Forma de Pago -->
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Forma de Pago</label>
                        <select id="registro_forma_pago" 
                                name="forma_de_pago"
                                style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem;">
                            <option value="">-- Seleccionar --</option>
                            <option value="Contado">Contado</option>
                            <option value="Crédito">Crédito</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Tarjeta">Tarjeta</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Contenedor de Prendas -->
            <div style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-weight: 700; color: #1f2937; margin: 0; font-size: 1.1rem;">
                        <i class="fas fa-tshirt" style="color: #8b5cf6; margin-right: 0.5rem;"></i>Prendas
                    </h3>
                    <button type="button" 
                            onclick="agregarPrendaAlRegistro()"
                            style="background: #3b82f6; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer;">
                        + Agregar Prenda
                    </button>
                </div>
                
                <div id="contenedorPrendasRegistro" style="display: flex; flex-direction: column; gap: 2rem;">
                    <!-- Las prendas se agregarán aquí dinámicamente -->
                </div>
            </div>

            <!-- Botones de Acción -->
            <div style="display: flex; gap: 1rem; justify-content: flex-end; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                <button type="button" 
                        onclick="cerrarModalRegistroPedido()"
                        style="padding: 0.75rem 1.5rem; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 6px; font-weight: 600; cursor: pointer;">
                    Cancelar
                </button>
                <button type="submit" 
                        style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-check" style="margin-right: 0.5rem;"></i>Registrar Pedido
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .modal-overlay-registro {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .modal-content-registro {
        animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .btn-talla-registro {
        padding: 0.45rem 0.75rem;
        border: 2px solid #dbeafe;
        background: white;
        color: #0066cc;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .btn-talla-registro:hover {
        background: #dbeafe;
        border-color: #0066cc;
    }

    .btn-talla-registro.selected {
        background: #0066cc;
        color: white;
        border-color: #0066cc;
    }

    .talla-tag-registro {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .talla-tag-registro button {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        font-size: 1rem;
        padding: 0;
        display: flex;
        align-items: center;
    }
</style>

<script>
/**
 * CONSTANTES DE TALLAS - MISMO SISTEMA QUE EN MÓDULO ASESOR
 */
const registroTallasLetras = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
const registroTallasDama = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
const registroTallasCaballero = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];

/**
 * GESTIÓN DE MODAL DE REGISTRO DE PEDIDO
 */

// Almacenar prendas en registro
let prendaRegistro = [];

/**
 * Abrir modal de registro de pedido
 */
function abrirModalRegistroPedido() {
    const modal = document.getElementById('modalRegistroPedido');
    if (modal) {
        modal.style.display = 'block';
        prendaRegistro = [];
        document.getElementById('formRegistroPedido').reset();
        document.getElementById('contenedorPrendasRegistro').innerHTML = '';
        
        // Agregar primera prenda
        agregarPrendaAlRegistro();
    }
}

/**
 * Cerrar modal de registro de pedido
 */
function cerrarModalRegistroPedido() {
    const modal = document.getElementById('modalRegistroPedido');
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Agregar una prenda al registro
 */
function agregarPrendaAlRegistro() {
    const index = prendaRegistro.length;
    prendaRegistro.push({
        nombre_producto: '',
        descripcion: '',
        genero: '',
        telas: [],
        tipo_manga: 'No aplica',
        obs_manga: '',
        tipo_broche: 'No aplica',
        obs_broche: '',
        tiene_bolsillos: false,
        obs_bolsillos: '',
        tiene_reflectivo: false,
        obs_reflectivo: '',
        encargado_orden: '',
        tallas: [],
        tallasSeleccionadas: {}
    });

    renderizarPrendaRegistro(index);
}

/**
 * Renderizar tarjeta de prenda en registro
 */
function renderizarPrendaRegistro(index) {
    const contenedor = document.getElementById('contenedorPrendasRegistro');
    const prenda = prendaRegistro[index];

    const html = `
        <div class="prenda-registro-card" data-index="${index}" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; background: #f9fafb;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #e5e7eb;">
                <h4 style="font-weight: 700; color: #1f2937; margin: 0; font-size: 1rem;">Prenda ${index + 1}</h4>
                <button type="button" 
                        onclick="eliminarPrendaRegistro(${index})"
                        style="background: #dc2626; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-weight: 600;">
                    Eliminar
                </button>
            </div>

            <!-- Nombre, Género, Tela y Color -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Nombre del Producto *</label>
                    <input type="text" 
                           name="prendas[${index}][nombre_producto]"
                           class="prenda-registro-nombre"
                           data-index="${index}"
                           placeholder="POLO, CAMISA..."
                           value="${prenda.nombre_producto || ''}"
                           onchange="actualizarSelectTallasRegistro(this)"
                           required
                           style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Género *</label>
                    <select name="prendas[${index}][genero]"
                            class="prenda-registro-genero"
                            data-index="${index}"
                            onchange="actualizarSelectTallasRegistro(this)"
                            required
                            style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem;">
                        <option value="">-- Seleccionar --</option>
                        <option value="Dama" ${prenda.genero === 'Dama' ? 'selected' : ''}>Dama</option>
                        <option value="Caballero" ${prenda.genero === 'Caballero' ? 'selected' : ''}>Caballero</option>
                        <option value="Unisex" ${prenda.genero === 'Unisex' ? 'selected' : ''}>Unisex</option>
                    </select>
                </div>
            </div>

            <!-- Tabla de Telas -->
            <div style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; color: #374151; margin: 0; font-size: 1rem;">
                        <i class="fas fa-palette" style="color: #ec4899; margin-right: 0.5rem;"></i>Telas
                    </label>
                    <button type="button"
                            onclick="agregarTelaPrenda(${index})"
                            style="background: #ec4899; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.9rem;">
                        + Agregar Tela
                    </button>
                </div>
                <div id="tabla-telas-${index}" class="tabla-telas-contenedor" data-index="${index}">
                    <table style="width: 100%; border-collapse: collapse; background: white;">
                        <thead>
                            <tr style="background: #f3f4f6; border-bottom: 2px solid #d1d5db;">
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151; width: 45%;">Tela</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: #374151; width: 45%;">Color</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #374151; width: 10%;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-telas-${index}" style="border: 1px solid #d1d5db;">
                            <!-- Las telas se agregarán aquí dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Descripción -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Descripción (Opcional)</label>
                <textarea name="prendas[${index}][descripcion]"
                          class="prenda-registro-descripcion"
                          data-index="${index}"
                          placeholder="Describe la prenda (ej: Logo, ubicación)..."
                          style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; min-height: 70px; font-size: 0.95rem; font-family: inherit;">${prenda.descripcion || ''}</textarea>
            </div>

            <!-- SISTEMA DE TALLAS EXACTO COMO EN MÓDULO ASESOR -->
            <div style="margin-bottom: 1.5rem; padding: 1rem; background: white; border: 1px solid #dbeafe; border-radius: 6px;">
                <label style="display: block; font-weight: 700; color: #1f2937; margin-bottom: 1rem; font-size: 1rem;">
                    <i class="fas fa-ruler" style="color: #0066cc; margin-right: 0.5rem;"></i>Tallas y Cantidades *
                </label>

                <!-- Selector de Tipo de Talla -->
                <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
                    <select class="talla-tipo-select-registro" 
                            data-index="${index}"
                            onchange="actualizarSelectTallasRegistro(this)"
                            style="padding: 0.4rem 0.6rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 300px;">
                        <option value="">Selecciona tipo de talla</option>
                        <option value="letra">LETRAS (XS, S, M, L, XL...)</option>
                        <option value="numero">NÚMEROS (DAMA/CABALLERO)</option>
                    </select>

                    <!-- Selector de Modo -->
                    <select class="talla-modo-select-registro" 
                            data-index="${index}"
                            style="padding: 0.4rem 0.6rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 180px; display: none;">
                        <option value="manual">Manual</option>
                        <option value="rango">Rango (Desde-Hasta)</option>
                    </select>

                    <!-- Selectores de Rango -->
                    <div class="talla-rango-selectors-registro" data-index="${index}" style="display: none; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
                        <select class="talla-desde-registro" data-index="${index}" style="padding: 0.4rem 0.6rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 150px;">
                            <option value="">Desde</option>
                        </select>
                        <span style="color: #0066cc; font-weight: 600;">hasta</span>
                        <select class="talla-hasta-registro" data-index="${index}" style="padding: 0.4rem 0.6rem; border: 2px solid #0066cc; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; color: #0066cc; font-weight: 600; max-width: 150px;">
                            <option value="">Hasta</option>
                        </select>
                        <button type="button" class="btn-agregar-rango-registro" data-index="${index}" onclick="agregarTallasRangoRegistro(this)" style="padding: 0.4rem 0.8rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem;"><i class="fas fa-plus"></i></button>
                    </div>
                </div>

                <!-- Botones de Tallas (Modo Manual) -->
                <div class="talla-botones-registro" data-index="${index}" style="display: none; margin-bottom: 1rem;">
                    <p style="margin: 0 0 0.75rem 0; font-size: 0.85rem; font-weight: 600; color: #0066cc;">Selecciona tallas:</p>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                        <div class="talla-botones-container-registro" data-index="${index}" style="display: flex; flex-wrap: wrap; gap: 0.5rem; flex: 1;">
                        </div>
                        <button type="button" class="btn-agregar-tallas-seleccionadas-registro" data-index="${index}" onclick="agregarTallasSeleccionadasRegistro(this)" style="padding: 0.4rem 0.8rem; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem; white-space: nowrap; flex-shrink: 0;"><i class="fas fa-plus"></i></button>
                    </div>
                </div>

                <!-- Tallas Agregadas (Tags) -->
                <div class="tallas-agregadas-registro" data-index="${index}" style="display: flex; flex-wrap: wrap; gap: 0.5rem; min-height: 35px; margin-bottom: 1rem;">
                </div>

                <!-- Tabla de Tallas Agregadas -->
                <div class="tallas-tabla-registro" data-index="${index}" style="display: none;">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #d0d0d0; border-radius: 6px;">
                            <thead style="background: linear-gradient(135deg, #0066cc, #0052a3); color: white;">
                                <tr>
                                    <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 700; font-size: 0.85rem;">Talla</th>
                                    <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 700; font-size: 0.85rem;">Cantidad</th>
                                    <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 700; font-size: 0.85rem;">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="tabla-tallas-body-registro" data-index="${index}">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Variaciones -->
            <div style="background: white; padding: 1rem; border-radius: 6px;">
                <details style="cursor: pointer;">
                    <summary style="font-weight: 600; color: #374151; user-select: none;">
                        <i class="fas fa-cog" style="margin-right: 0.5rem;"></i>Variaciones (Opcional)
                    </summary>
                    <div style="margin-top: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Tipo de Manga</label>
                            <select name="prendas[${index}][tipo_manga]" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                                <option value="No aplica" ${prenda.tipo_manga === 'No aplica' ? 'selected' : ''}>No aplica</option>
                                <option value="Corta" ${prenda.tipo_manga === 'Corta' ? 'selected' : ''}>Corta</option>
                                <option value="Larga" ${prenda.tipo_manga === 'Larga' ? 'selected' : ''}>Larga</option>
                                <option value="Sin manga" ${prenda.tipo_manga === 'Sin manga' ? 'selected' : ''}>Sin manga</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Obs. Manga</label>
                            <input type="text" name="prendas[${index}][obs_manga]" value="${prenda.obs_manga || ''}" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Tipo de Broche</label>
                            <select name="prendas[${index}][tipo_broche]" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                                <option value="No aplica" ${prenda.tipo_broche === 'No aplica' ? 'selected' : ''}>No aplica</option>
                                <option value="Botones" ${prenda.tipo_broche === 'Botones' ? 'selected' : ''}>Botones</option>
                                <option value="Cremallera" ${prenda.tipo_broche === 'Cremallera' ? 'selected' : ''}>Cremallera</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Obs. Broche</label>
                            <input type="text" name="prendas[${index}][obs_broche]" value="${prenda.obs_broche || ''}" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: flex; align-items: center; font-weight: 600; color: #374151;">
                                <input type="checkbox" name="prendas[${index}][tiene_bolsillos]" ${prenda.tiene_bolsillos ? 'checked' : ''} style="width: 18px; height: 18px; margin-right: 0.5rem; cursor: pointer;">
                                ¿Tiene bolsillos?
                            </label>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Obs. Bolsillos</label>
                            <input type="text" name="prendas[${index}][obs_bolsillos]" value="${prenda.obs_bolsillos || ''}" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: flex; align-items: center; font-weight: 600; color: #374151;">
                                <input type="checkbox" name="prendas[${index}][tiene_reflectivo]" ${prenda.tiene_reflectivo ? 'checked' : ''} style="width: 18px; height: 18px; margin-right: 0.5rem; cursor: pointer;">
                                ¿Tiene reflectivo?
                            </label>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Obs. Reflectivo</label>
                            <input type="text" name="prendas[${index}][obs_reflectivo]" value="${prenda.obs_reflectivo || ''}" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px;">
                        </div>
                    </div>
                </details>
            </div>

            <!-- Encargado de Orden -->
            <div style="background: white; padding: 1rem; border-radius: 6px; margin-top: 1rem; border: 1px solid #fbbf24;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                    <i class="fas fa-user-check" style="color: #f59e0b; margin-right: 0.5rem;"></i>Encargado de Orden (Creación)
                </label>
                <input type="text" 
                       name="prendas[${index}][encargado_orden]"
                       class="prenda-registro-encargado"
                       data-index="${index}"
                       placeholder="Nombre del encargado del área de creación de orden"
                       value="${prenda.encargado_orden || ''}"
                       style="width: 100%; padding: 0.75rem; border: 1px solid #fbbf24; border-radius: 6px; font-size: 0.95rem; background: #fffbeb;">
            </div>
        </div>
    `;

    if (index === 0) {
        contenedor.innerHTML = html;
    } else {
        contenedor.insertAdjacentHTML('beforeend', html);
    }
}

/**
 * Actualizar selector de tallas según tipo seleccionado
 */
function actualizarSelectTallasRegistro(element) {
    const card = element.closest('.prenda-registro-card');
    if (!card) return;

    const index = card.dataset.index;
    const tipoSelect = card.querySelector(`.talla-tipo-select-registro[data-index="${index}"]`);
    const tipo = tipoSelect?.value || '';
    const generoSelect = card.querySelector(`.prenda-registro-genero[data-index="${index}"]`);
    const genero = generoSelect?.value || '';

    const botonesCont = card.querySelector(`.talla-botones-container-registro[data-index="${index}"]`);
    const modoSelect = card.querySelector(`.talla-modo-select-registro[data-index="${index}"]`);
    const rangoSelectors = card.querySelector(`.talla-rango-selectors-registro[data-index="${index}"]`);
    const botones = card.querySelector(`.talla-botones-registro[data-index="${index}"]`);

    // Limpiar
    if (botonesCont) botonesCont.innerHTML = '';
    if (modoSelect) modoSelect.style.display = 'none';
    if (rangoSelectors) rangoSelectors.style.display = 'none';
    if (botones) botones.style.display = 'none';

    if (!tipo) return;

    // Determinar tallas según tipo
    let tallas = [];
    if (tipo === 'letra') {
        tallas = registroTallasLetras;
    } else if (tipo === 'numero') {
        tallas = genero === 'Dama' ? registroTallasDama : registroTallasCaballero;
    }

    // Mostrar selector de modo y botones
    if (modoSelect) modoSelect.style.display = 'inline-block';
    if (botones) botones.style.display = 'block';

    // Renderizar botones de tallas
    if (botonesCont) {
        botonesCont.innerHTML = tallas.map(talla => `
            <button type="button" class="btn-talla-registro" data-talla="${talla}" onclick="toggleTallaRegistro(this, ${index})" style="padding: 0.4rem 0.8rem;">
                ${talla}
            </button>
        `).join('');
    }

    // Actualizar selectores de rango
    if (rangoSelectors) {
        const desde = rangoSelectors.querySelector(`.talla-desde-registro[data-index="${index}"]`);
        const hasta = rangoSelectors.querySelector(`.talla-hasta-registro[data-index="${index}"]`);
        
        if (desde && hasta) {
            desde.innerHTML = '<option value="">Desde</option>' + tallas.map(t => `<option value="${t}">${t}</option>`).join('');
            hasta.innerHTML = '<option value="">Hasta</option>' + tallas.map(t => `<option value="${t}">${t}</option>`).join('');
        }
    }
}

/**
 * Toggle de talla
 */
function toggleTallaRegistro(btn, index) {
    btn.classList.toggle('selected');
}

/**
 * Agregar tallas seleccionadas
 */
function agregarTallasSeleccionadasRegistro(btn) {
    const index = btn.dataset.index;
    const card = document.querySelector(`.prenda-registro-card[data-index="${index}"]`);
    const botones = card.querySelectorAll('.btn-talla-registro.selected');
    
    if (botones.length === 0) {
        Swal.fire('Aviso', 'Selecciona al menos una talla', 'warning');
        return;
    }

    botones.forEach(boton => {
        const talla = boton.dataset.talla;
        agregarTallaAlListadoRegistro(index, talla);
        boton.classList.remove('selected');
    });
}

/**
 * Agregar talla al listado
 */
function agregarTallaAlListadoRegistro(index, talla) {
    const card = document.querySelector(`.prenda-registro-card[data-index="${index}"]`);
    const agregadas = card.querySelector(`.tallas-agregadas-registro[data-index="${index}"]`);
    const tabla = card.querySelector(`.tallas-tabla-registro[data-index="${index}"]`);
    const tbody = card.querySelector(`.tabla-tallas-body-registro[data-index="${index}"]`);

    // Evitar duplicados
    if (prendaRegistro[index].tallas.includes(talla)) {
        Swal.fire('Aviso', `La talla ${talla} ya fue agregada`, 'warning');
        return;
    }

    prendaRegistro[index].tallas.push(talla);

    // Agregar tag
    const tag = document.createElement('div');
    tag.className = 'talla-tag-registro';
    tag.dataset.talla = talla;
    tag.innerHTML = `${talla} <button type="button" onclick="eliminarTallaRegistro(${index}, '${talla}')" style="background: none; border: none; color: white; cursor: pointer; font-size: 0.9rem; padding: 0; margin: 0;">×</button>`;
    agregadas.appendChild(tag);

    // Agregar fila a tabla
    if (tbody) {
        const fila = document.createElement('tr');
        fila.style.borderBottom = '1px solid #e0e0e0';
        fila.innerHTML = `
            <td style="padding: 0.75rem 1rem; font-weight: 600; color: #0066cc;">${talla}</td>
            <td style="padding: 0.75rem 1rem; text-align: center;">
                <input type="number" min="0" class="talla-cantidad-input-registro" data-talla="${talla}" data-index="${index}" placeholder="0" style="width: 80px; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; text-align: center;">
            </td>
            <td style="padding: 0.75rem 1rem; text-align: center;">
                <button type="button" onclick="eliminarTallaRegistro(${index}, '${talla}')" style="background: #dc2626; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">Quitar</button>
            </td>
        </tr>`;
        tbody.appendChild(fila);
    }

    if (tabla) tabla.style.display = 'block';
}

/**
 * Eliminar talla del listado
 */
function eliminarTallaRegistro(index, talla) {
    const card = document.querySelector(`.prenda-registro-card[data-index="${index}"]`);
    const tag = card.querySelector(`.talla-tag-registro[data-talla="${talla}"]`);
    const fila = card.querySelector(`.tabla-tallas-body-registro[data-index="${index}"] tr`);

    if (tag) tag.remove();
    
    const filas = card.querySelectorAll(`.tabla-tallas-body-registro[data-index="${index}"] tr`);
    filas.forEach(f => {
        if (f.textContent.includes(talla)) f.remove();
    });

    prendaRegistro[index].tallas = prendaRegistro[index].tallas.filter(t => t !== talla);

    // Ocultar tabla si no hay tallas
    if (prendaRegistro[index].tallas.length === 0) {
        const tabla = card.querySelector(`.tallas-tabla-registro[data-index="${index}"]`);
        if (tabla) tabla.style.display = 'none';
    }
}

/**
 * Agregar tallas por rango
 */
function agregarTallasRangoRegistro(btn) {
    const index = btn.dataset.index;
    const card = document.querySelector(`.prenda-registro-card[data-index="${index}"]`);
    const desdeSelect = card.querySelector(`.talla-desde-registro[data-index="${index}"]`);
    const hastaSelect = card.querySelector(`.talla-hasta-registro[data-index="${index}"]`);

    const desde = desdeSelect.value;
    const hasta = hastaSelect.value;

    if (!desde || !hasta) {
        Swal.fire('Aviso', 'Selecciona talla inicial y final', 'warning');
        return;
    }

    // Obtener tallas según tipo
    const tipoSelect = card.querySelector(`.talla-tipo-select-registro[data-index="${index}"]`);
    const tipo = tipoSelect.value;
    const generoSelect = card.querySelector(`.prenda-registro-genero[data-index="${index}"]`);
    const genero = generoSelect.value;

    let tallas = tipo === 'letra' ? registroTallasLetras : (genero === 'Dama' ? registroTallasDama : registroTallasCaballero);

    const desdeIdx = tallas.indexOf(desde);
    const hastaIdx = tallas.indexOf(hasta);

    if (desdeIdx === -1 || hastaIdx === -1 || desdeIdx > hastaIdx) {
        Swal.fire('Error', 'Rango inválido', 'error');
        return;
    }

    const tallasRango = tallas.slice(desdeIdx, hastaIdx + 1);
    tallasRango.forEach(talla => {
        if (!prendaRegistro[index].tallas.includes(talla)) {
            agregarTallaAlListadoRegistro(index, talla);
        }
    });

    desdeSelect.value = '';
    hastaSelect.value = '';
}

/**
 * Eliminar prenda del registro
 */
function eliminarPrendaRegistro(index) {
    if (prendaRegistro.length <= 1) {
        Swal.fire('Aviso', 'Debes tener al menos una prenda', 'warning');
        return;
    }

    prendaRegistro.splice(index, 1);
    const contenedor = document.getElementById('contenedorPrendasRegistro');
    contenedor.innerHTML = '';
    prendaRegistro.forEach((_, i) => renderizarPrendaRegistro(i));
}

/**
 * Enviar formulario de registro de pedido
 */
function enviarRegistroPedido(event) {
    event.preventDefault();

    if (prendaRegistro.length === 0) {
        Swal.fire('Error', 'Debes agregar al menos una prenda', 'error');
        return;
    }

    const prendas = [];
    let valido = true;

    prendaRegistro.forEach((_, index) => {
        const card = document.querySelector(`.prenda-registro-card[data-index="${index}"]`);
        const nombreProducto = card.querySelector(`.prenda-registro-nombre`).value;
        const descripcion = card.querySelector(`.prenda-registro-descripcion`).value || '';
        const genero = card.querySelector(`.prenda-registro-genero`).value;
        const telas = prendaRegistro[index].telas || [];

        if (!nombreProducto || !genero || prendaRegistro[index].tallas.length === 0) {
            Swal.fire('Error', `Prenda ${index + 1}: Completa todos los campos requeridos y agrega tallas`, 'error');
            valido = false;
            return;
        }

        // Recopilar cantidades por talla
        const cantidadesTallas = {};
        const inputs = card.querySelectorAll(`.talla-cantidad-input-registro[data-index="${index}"]`);
        inputs.forEach(input => {
            const cantidad = parseInt(input.value) || 0;
            if (cantidad > 0) {
                cantidadesTallas[input.dataset.talla] = cantidad;
            }
        });

        prendas.push({
            nombre_producto: nombreProducto,
            descripcion: descripcion,
            genero: genero,
            telas: telas,
            tipo_manga: card.querySelector(`input[name="prendas[${index}][tipo_manga]"]`)?.value || 'No aplica',
            obs_manga: card.querySelector(`input[name="prendas[${index}][obs_manga]"]`)?.value || '',
            tipo_broche: card.querySelector(`input[name="prendas[${index}][tipo_broche]"]`)?.value || 'No aplica',
            obs_broche: card.querySelector(`input[name="prendas[${index}][obs_broche]"]`)?.value || '',
            tiene_bolsillos: card.querySelector(`input[name="prendas[${index}][tiene_bolsillos]"]`)?.checked || false,
            obs_bolsillos: card.querySelector(`input[name="prendas[${index}][obs_bolsillos]"]`)?.value || '',
            tiene_reflectivo: card.querySelector(`input[name="prendas[${index}][tiene_reflectivo]"]`)?.checked || false,
            obs_reflectivo: card.querySelector(`input[name="prendas[${index}][obs_reflectivo]"]`)?.value || '',
            encargado_orden: card.querySelector(`.prenda-registro-encargado[data-index="${index}"]`)?.value || '',
            cantidad_talla: cantidadesTallas
        });
    });

    if (!valido) return;

    const datos = {
        cliente: document.getElementById('registro_cliente').value,
        asesora: document.getElementById('registro_asesora').value || '',
        forma_de_pago: document.getElementById('registro_forma_pago').value || '',
        prendas: prendas,
        estado: 'No iniciado',
        _token: document.querySelector('input[name="_token"]').value
    };

    Swal.fire({
        title: 'Registrando Pedido...',
        didOpen: () => Swal.showLoading(),
        allowOutsideClick: false,
        allowEscapeKey: false
    });

    fetch('{{ route("orders.store-from-modal") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: JSON.stringify(datos)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            Swal.fire({
                title: '✅ Pedido Registrado',
                html: `<p><strong>Pedido:</strong> ${result.numero_pedido}</p><p><strong>Cliente:</strong> ${result.cliente}</p>`,
                icon: 'success'
            }).then(() => {
                cerrarModalRegistroPedido();
                location.reload();
            });
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    })
    .catch(e => Swal.fire('Error', 'Error de conexión', 'error'));
}

/**
 * Agregar tela a la lista de una prenda
 */
function agregarTelaPrenda(index) {
    const nuevaTela = {
        nombre_tela: '',
        color_tela: ''
    };
    
    prendaRegistro[index].telas.push(nuevaTela);
    renderizarTelasPrenda(index);
}

/**
 * Eliminar tela de la lista de una prenda
 */
function eliminarTelaPrenda(index, telaIndex) {
    prendaRegistro[index].telas.splice(telaIndex, 1);
    renderizarTelasPrenda(index);
}

/**
 * Actualizar el nombre de tela
 */
function actualizarNombreTela(index, telaIndex, valor) {
    prendaRegistro[index].telas[telaIndex].nombre_tela = valor;
}

/**
 * Actualizar el color de tela
 */
function actualizarColorTela(index, telaIndex, valor) {
    prendaRegistro[index].telas[telaIndex].color_tela = valor;
}

/**
 * Renderizar todas las telas de una prenda
 */
function renderizarTelasPrenda(index) {
    const tbody = document.getElementById(`tbody-telas-${index}`);
    if (!tbody) return;
    
    const telas = prendaRegistro[index].telas || [];
    
    tbody.innerHTML = telas.map((tela, telaIndex) => `
        <tr style="border-bottom: 1px solid #e5e7eb; transition: background-color 0.2s;">
            <td style="padding: 0.75rem;">
                <input type="text"
                       placeholder="Algodón, Polyester, Lino..."
                       value="${tela.nombre_tela || ''}"
                       onchange="actualizarNombreTela(${index}, ${telaIndex}, this.value)"
                       style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;">
            </td>
            <td style="padding: 0.75rem;">
                <input type="text"
                       placeholder="Ej: Rojo, Azul marino, Blanco..."
                       value="${tela.color_tela || ''}"
                       onchange="actualizarColorTela(${index}, ${telaIndex}, this.value)"
                       style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.9rem;">
            </td>
            <td style="padding: 0.75rem; text-align: center;">
                <button type="button"
                        onclick="eliminarTelaPrenda(${index}, ${telaIndex})"
                        style="background: #ef4444; color: white; border: none; padding: 0.4rem 0.7rem; border-radius: 4px; cursor: pointer; font-weight: 600; transition: background 0.2s;">
                    <i class="fas fa-trash" style="margin-right: 0.25rem;"></i>Eliminar
                </button>
            </td>
        </tr>
    `).join('');
}

document.addEventListener('click', function(event) {
    const modal = document.getElementById('modalRegistroPedido');
    if (modal && event.target === modal) cerrarModalRegistroPedido();
});
</script>

