<div class="cotizar-prendas-container" style="background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);">
    <!-- Prendas Tabs Modernos -->
    <div style="display: flex; gap: 0.75rem; margin-bottom: 2rem; overflow-x: auto; padding-bottom: 0.5rem;">
        @forelse($cotizacion->prendas as $index => $prenda)
        <button type="button" class="prenda-tab" data-prenda-id="{{ $prenda->id }}" onclick="cambiarPrendaTab({{ $prenda->id }})" style="padding: 0.75rem 1.5rem; background-color: {{ $index === 0 ? '#1e5ba8' : '#ffffff' }}; color: {{ $index === 0 ? 'white' : '#666' }}; border: 2px solid {{ $index === 0 ? '#1e5ba8' : '#e0e0e0' }}; border-radius: 8px; cursor: pointer; font-weight: 600; white-space: nowrap; transition: all 0.3s ease; box-shadow: {{ $index === 0 ? '0 4px 12px rgba(30, 91, 168, 0.2)' : 'none' }};">
            📦 Prenda {{ $index + 1 }}
        </button>
        @empty
        <p style="color: #999;">No hay prendas en esta cotización</p>
        @endforelse
    </div>

    <!-- Contenido de Prendas -->
    @forelse($cotizacion->prendas as $prenda)
    <div class="prenda-content" id="prenda-content-{{ $prenda->id }}" style="display: none;">
        <!-- Tarjeta Principal -->
        <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 2rem;">
            <!-- Grid de Imagen y Descripción -->
            <div style="display: grid; grid-template-columns: 320px 1fr; gap: 2rem; padding: 2rem; align-items: start;">
                <!-- Imagen -->
                <div style="text-align: center;">
                    @if($prenda->imagen_url)
                    <img src="{{ $prenda->imagen_url }}" alt="Prenda" style="max-width: 100%; height: auto; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); object-fit: cover;">
                    @else
                    <div style="width: 100%; height: 320px; background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 0.9rem;">
                        📷 Sin imagen
                    </div>
                    @endif
                </div>

                <!-- Descripción -->
                <div>
                    <h3 style="color: #1e5ba8; font-size: 1.3rem; margin: 0 0 1rem 0; font-weight: 700;">Descripción de la Prenda</h3>
                    <p style="color: #333; line-height: 1.8; font-size: 0.95rem; margin: 0;">
                        {{ $prenda->descripcion }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Componentes -->
        <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 2rem;">
            <div style="padding: 2rem;">
                <h3 style="color: #1e5ba8; font-size: 1.2rem; margin: 0 0 1.5rem 0; font-weight: 700;">⚙️ Componentes y Costos</h3>
                
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%);">
                                <th style="padding: 1rem; text-align: left; border: none; font-weight: 700; color: white;">Componente</th>
                                <th style="padding: 1rem; text-align: right; border: none; font-weight: 700; color: white; width: 120px;">Costo</th>
                                <th style="padding: 1rem; text-align: center; border: none; font-weight: 700; color: white; width: 60px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="costos-tbody-{{ $prenda->id }}">
                            <!-- Se cargará dinámicamente -->
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #f8f9fa; border-top: 2px solid #e0e0e0;">
                                <td style="padding: 1rem; color: #333; font-weight: 700;">TOTAL COSTO</td>
                                <td style="padding: 1rem; text-align: right; color: #1e5ba8; font-size: 1.2rem; font-weight: 700;" id="total-costo-{{ $prenda->id }}">$ 0.00</td>
                                <td style="padding: 1rem;"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tarjeta Agregar Componente -->
        <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
            <div style="padding: 2rem;">
                <h4 style="color: #1e5ba8; margin: 0 0 1.5rem 0; font-weight: 700; font-size: 1.1rem;">➕ Agregar Componente</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 110px; gap: 1rem; align-items: end;">
                    <div style="position: relative;">
                        <label style="display: block; font-weight: 600; color: #333; margin-bottom: 0.75rem; font-size: 0.9rem;">Componente</label>
                        <input type="text" id="componente-search-{{ $prenda->id }}" placeholder="Buscar o escribir..." style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95rem; color: #000; font-weight: 500; transition: all 0.3s ease;" oninput="filtrarComponentes({{ $prenda->id }})" onfocus="this.style.borderColor='#1e5ba8'; this.style.boxShadow='0 0 0 3px rgba(30, 91, 168, 0.1)'" onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'">
                        <div id="componentes-dropdown-{{ $prenda->id }}" style="position: absolute; top: 100%; left: 0; background: white; border: 2px solid #1e5ba8; border-radius: 8px; max-height: 250px; overflow-y: auto; width: 100%; display: none; z-index: 1000; box-shadow: 0 8px 24px rgba(30, 91, 168, 0.15); margin-top: 0.5rem;">
                            <!-- Se cargará dinámicamente -->
                        </div>
                    </div>
                    
                    <div>
                        <label style="display: block; font-weight: 600; color: #333; margin-bottom: 0.75rem; font-size: 0.9rem;">Costo ($)</label>
                        <input type="number" id="costo-input-{{ $prenda->id }}" placeholder="0.00" step="0.01" min="0" style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95rem; color: #000; font-weight: 500; transition: all 0.3s ease;" onfocus="this.style.borderColor='#1e5ba8'; this.style.boxShadow='0 0 0 3px rgba(30, 91, 168, 0.1)'" onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'">
                    </div>
                    
                    <button type="button" onclick="agregarCostoPrenda({{ $prenda->id }})" style="padding: 0.75rem 1rem; background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(30, 91, 168, 0.2);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(30, 91, 168, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(30, 91, 168, 0.2)'">
                        Agregar
                    </button>
                </div>
                
                <div style="margin-top: 1rem; padding: 0.75rem; background: #e8f4f8; border-left: 4px solid #1e5ba8; border-radius: 4px;">
                    <small style="color: #1e5ba8; font-weight: 500;">💡 Escribe para buscar componentes. Si no existe, aparecerá la opción de crear uno nuevo.</small>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div style="padding: 3rem; text-align: center; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        <p style="color: #999; font-size: 1.1rem;">📦 No hay prendas en esta cotización</p>
    </div>
    @endforelse

    <!-- Botones de Acción -->
    <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end; padding-top: 1.5rem; border-top: 2px solid #e0e0e0;">
        <button type="button" onclick="cerrarModalCotizarPrendas()" style="padding: 0.75rem 1.5rem; background-color: #e0e0e0; color: #333; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#d0d0d0'" onmouseout="this.style.backgroundColor='#e0e0e0'">
            ✕ Cancelar
        </button>
        <button type="button" onclick="guardarFormatoCotizacion({{ $cotizacion->id }})" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 12px rgba(39, 174, 96, 0.2); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(39, 174, 96, 0.3)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(39, 174, 96, 0.2)'">
            ✓ Guardar Formato
        </button>
    </div>
</div>

<script>
    // Mostrar primera prenda por defecto
    document.addEventListener('DOMContentLoaded', function() {
        const firstPrendaTab = document.querySelector('.prenda-tab');
        if (firstPrendaTab) {
            const prendaId = firstPrendaTab.getAttribute('data-prenda-id');
            cambiarPrendaTab(prendaId);
            cargarComponentes(prendaId);
            cargarCostos(prendaId);
        }
    });
</script>
