<!-- PASO 2 -->
<div class="form-step" data-step="2">
    <div class="step-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-size: 0.8rem !important; margin: 0 0 0.2rem 0 !important;">PASO 2: PRENDAS DEL PEDIDO</h2>
            <p style="font-size: 0.45rem !important; margin: 0 !important; color: #666 !important;">AGREGA LAS PRENDAS QUE TU CLIENTE QUIERE</p>
        </div>
        
        <!-- Selector de tipo de cotización en la esquina derecha -->
        <div style="display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #0066cc, #0052a3); border: 2px solid #0052a3; border-radius: 8px; padding: 0.8rem 1.2rem; box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);">
            <label for="tipo_cotizacion" style="font-weight: 700; font-size: 0.85rem; color: white; white-space: nowrap; display: flex; align-items: center; gap: 6px; margin: 0;">
                <i class="fas fa-tag"></i> Tipo
            </label>
            <select id="tipo_cotizacion" name="tipo_cotizacion" style="padding: 0.5rem 0.6rem; border: 2px solid white; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; text-align: center; color: #0066cc; font-weight: 600; min-width: 80px;">
                <option value="">Selecciona</option>
                <option value="M">M</option>
                <option value="D">D</option>
                <option value="X">X</option>
            </select>
        </div>
    </div>

    <div class="form-section">
        <div class="productos-container" id="productosContainer">
            @if(isset($esEdicion) && $esEdicion && isset($cotizacion) && $cotizacion->productos)
                <!-- Cargar productos guardados -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const productos = {!! json_encode($cotizacion->productos) !!};
                        console.log('📦 Productos a cargar:', productos);
                        
                        productos.forEach((producto, idx) => {
                            agregarProductoFriendly();
                            
                            // Esperar a que se cree el elemento
                            setTimeout(() => {
                                const ultimoProducto = document.querySelectorAll('.producto-card')[document.querySelectorAll('.producto-card').length - 1];
                                
                                if (ultimoProducto) {
                                    // Nombre
                                    const inputNombre = ultimoProducto.querySelector('input[name*="nombre_producto"]');
                                    if (inputNombre) inputNombre.value = producto.nombre_producto || '';
                                    
                                    // Descripción
                                    const textareaDesc = ultimoProducto.querySelector('textarea[name*="descripcion"]');
                                    if (textareaDesc) textareaDesc.value = producto.descripcion || '';
                                    
                                    // Tallas
                                    if (producto.tallas && Array.isArray(producto.tallas)) {
                                        producto.tallas.forEach(talla => {
                                            const tallaBtn = ultimoProducto.querySelector(`.talla-btn[data-talla="${talla}"]`);
                                            if (tallaBtn) tallaBtn.click();
                                        });
                                    }
                                    
                                    console.log('✅ Producto cargado:', producto.nombre_producto);
                                }
                            }, 500);
                        });
                    });
                </script>
            @endif
        </div>
    </div>

    <!-- Botón flotante para agregar prenda -->
    <div style="position: fixed; bottom: 30px; right: 30px; z-index: 1000;">
        <!-- Menú flotante -->
        <div id="menuFlotante" style="display: none; position: absolute; bottom: 70px; right: 0; background: white; border-radius: 12px; box-shadow: 0 5px 40px rgba(0,0,0,0.16); overflow: hidden; min-width: 200px;">
            <button type="button" onclick="agregarProductoFriendly(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 14px 18px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.95rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s; border-bottom: 1px solid #f0f0f0;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                <i class="fas fa-plus" style="color: #1e40af; font-size: 1.2rem;"></i>
                <span>Agregar Prenda</span>
            </button>
            <button type="button" onclick="abrirModalEspecificaciones(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 14px 18px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.95rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                <i class="fas fa-sliders-h" style="color: #ff9800; font-size: 1.2rem;"></i>
                <span>Especificaciones</span>
            </button>
        </div>
        
        <!-- Botón principal flotante -->
        <button type="button" id="btnFlotante" onclick="const menu = document.getElementById('menuFlotante'); menu.style.display = menu.style.display === 'none' ? 'block' : 'none'; this.style.transform = menu.style.display === 'block' ? 'scale(1) rotate(45deg)' : 'scale(1) rotate(0deg)'" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #0ea5e9); color: white; border: none; cursor: pointer; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.4); transition: all 0.3s ease; position: relative;" onmouseover="this.style.boxShadow='0 6px 20px rgba(30, 64, 175, 0.5)'; this.style.transform='scale(1.1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')" onmouseout="this.style.boxShadow='0 4px 12px rgba(30, 64, 175, 0.4)'; this.style.transform='scale(1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')">
            <i class="fas fa-plus"></i>
        </button>
    </div>

    <div class="form-actions">
        <button type="button" class="btn-prev" onclick="irAlPaso(1)">
            <i class="fas fa-arrow-left"></i> ANTERIOR
        </button>
        <button type="button" class="btn-next" onclick="irAlPaso(3)">
            SIGUIENTE <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>
