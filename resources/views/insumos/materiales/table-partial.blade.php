{{-- Respuesta parcial para búsqueda AJAX - Solo tabla y paginación --}}

{{-- Mensaje de búsqueda activa --}}
@if(request('search'))
    <div class="mt-4 p-4 bg-blue-50 border-l-4 border-blue-500 rounded-lg">
        <p class="text-blue-800 text-sm flex items-center gap-2">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            <strong>Busqueda activa:</strong> Mostrando <strong>{{ $ordenes->total() }}</strong> resultado(s) para "<strong>{{ request('search') }}</strong>"
        </p>
    </div>
@endif

{{-- Tabla Principal de Órdenes --}}
<div class="bg-white" style="margin: 0; border-radius: 0; box-shadow: none; width: 100%; overflow-x: auto; overflow-y: visible; padding: 0 0.5rem;">
    <div style="width: 100%; margin: 0; padding: 0;">
        <table class="w-full" style="font-size: 0.75em; width: 100%; margin: 0; padding: 0;">
            <thead>
                <tr class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                    <th class="text-center py-4 px-6 font-bold whitespace-nowrap" style="min-width: 200px;">Acciones</th>
                    <th class="text-left py-4 px-6 font-bold">
                        <div class="flex items-center justify-between gap-2">
                            <span>N° Recibo</span>
                            <button
                                type="button"
                                class="filter-btn-insumos p-1 rounded hover:bg-blue-500 transition"
                                data-column="consecutivo_actual"
                                title="Filtrar N° Recibo"
                            >
                                <i class="fas fa-filter text-xs"></i>
                            </button>
                        </div>
                    </th>
                    <th class="text-left py-4 px-6 font-bold">
                        <div class="flex items-center justify-between gap-2">
                            <span>N° Pedido</span>
                            <button
                                type="button"
                                class="filter-btn-insumos p-1 rounded hover:bg-blue-500 transition"
                                data-column="numero_pedido"
                                title="Filtrar N° Pedido"
                            >
                                <i class="fas fa-filter text-xs"></i>
                            </button>
                        </div>
                    </th>
                    <th class="text-left py-4 px-6 font-bold">
                        <div class="flex items-center justify-between gap-2">
                            <span>Cliente</span>
                            <button
                                type="button"
                                class="filter-btn-insumos p-1 rounded hover:bg-blue-500 transition"
                                data-column="cliente"
                                title="Filtrar Cliente"
                            >
                                <i class="fas fa-filter text-xs"></i>
                            </button>
                        </div>
                    </th>
                    <th class="text-center py-4 px-6 font-bold">
                        <div class="flex items-center justify-center gap-2">
                            <span>Estado</span>
                            <button
                                type="button"
                                class="filter-btn-insumos p-1 rounded hover:bg-blue-500 transition"
                                data-column="estado"
                                title="Filtrar Estado"
                            >
                                <i class="fas fa-filter text-xs"></i>
                            </button>
                        </div>
                    </th>
                    <th class="text-center py-4 px-6 font-bold">
                        <div class="flex items-center justify-center gap-2">
                            <span>Area</span>
                            <button
                                type="button"
                                class="filter-btn-insumos p-1 rounded hover:bg-blue-500 transition"
                                data-column="area"
                                title="Filtrar Area"
                            >
                                <i class="fas fa-filter text-xs"></i>
                            </button>
                        </div>
                    </th>
                    <th class="text-center py-4 px-6 font-bold">
                        Novedades
                    </th>
                    <th class="text-center py-4 px-6 font-bold">
                        <div class="flex items-center justify-center gap-2">
                            <span>Fecha de Inicio</span>
                            <button
                                type="button"
                                class="filter-btn-insumos p-1 rounded hover:bg-blue-500 transition"
                                data-column="created_at"
                                title="Filtrar Fecha de Inicio"
                            >
                                <i class="fas fa-filter text-xs"></i>
                            </button>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($ordenes ?? [] as $orden)
                    <tr class="border-b border-gray-200 hover:bg-gray-50 transition @if(isset($orden->dias_calculados) && $orden->dias_calculados > 0)
                        @if($orden->dias_calculados >= 14) dias-mayor-15
                        @elseif($orden->dias_calculados >= 10) dias-10-15
                        @elseif($orden->dias_calculados >= 5) dias-5-9
                        @else dias-0-4 @endif
                    @endif @if(isset($orden->marcar_plooter) && $orden->marcar_plooter) row-checked @endif" 
                    data-pedido="{{ strtoupper($orden->numero_pedido ?? '') }}" 
                    data-cliente="{{ strtoupper($orden->cliente ?? '') }}" 
                    data-orden-pedido="{{ $orden->numero_pedido }}"
                    data-recibo="{{ $orden->id ?? '' }}"
                    data-material-id="{{ $orden->id ?? '' }}"
                    data-pedido-produccion-id="{{ $orden->pedido_produccion_id ?? '' }}">
                        <td class="py-4 px-6 text-center" style="min-width: 250px; overflow: visible; background: white; position: relative; z-index: 5;">
                            {{-- Indicador de materiales (punto rojo en esquina izquierda) --}}
                            @if(isset($orden->tiene_materiales) && $orden->tiene_materiales)
                                <div 
                                    class="btn-tooltip"
                                    data-tooltip="Contiene {{ $orden->cantidad_materiales }} material(es)"
                                    title="Contiene {{ $orden->cantidad_materiales }} material(es)"
                                    style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center;"
                                >
                                    <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse" style="box-shadow: 0 0 8px rgba(239, 68, 68, 0.6);"></div>
                                </div>
                            @endif

                            <div class="flex items-center justify-center gap-3" style="display: flex !important; flex-wrap: wrap; overflow: visible;">
                                {{-- Definir variables primero --}}
                                @php
                                    $userRole = auth()->user()->role;
                                    $roleName = is_object($userRole) ? $userRole->name : $userRole;
                                    $isPatronista = $roleName === 'patronista';
                                    $isInsumos = $roleName === 'insumos';
                                    $reciboId = $orden->id;
                                    $pedidoProduccionId = $orden->pedido_produccion_id;
                                @endphp
                                
                                {{-- Boton Check (marca) en purple --}}
                                <button 
                                    class="btn-check-row btn-tooltip p-2 text-purple-600 hover:bg-purple-50 rounded transition @if(isset($orden->marcar_plooter) && $orden->marcar_plooter) checked @endif"
                                    data-insumos-action="toggle-row-check"
                                    data-tooltip="Marcar fila"
                                    title="Marcar fila"
                                >
                                    <i class="fas fa-check text-lg"></i>
                                </button>

                                {{-- Dropdown Ver Recibo / Seguimiento --}}
                                <button 
                                    class="btn-ver-insumos-dropdown btn-tooltip p-2 text-blue-600 hover:bg-blue-50 rounded transition relative"
                                    data-insumos-action="ver-recibo-dropdown"
                                    data-pedido-id="{{ $pedidoProduccionId }}"
                                    data-pedido-produccion-id="{{ $pedidoProduccionId }}"
                                    data-prenda-id="{{ $orden->prenda_id ?? '' }}"
                                    data-es-parcial="{{ !empty($orden->es_parcial) ? '1' : '0' }}"
                                    data-pedido-parcial-id="{{ $orden->pedido_parcial_id ?? '' }}"
                                    data-tooltip="Ver recibo o seguimiento"
                                    title="Ver recibo o seguimiento"
                                >
                                    <i class="fas fa-eye text-lg"></i>
                                </button>

                                {{-- Dropdown de Acciones (solo para no-patronistas) --}}
                                @if(!$isPatronista)
                                    {{-- Boton Enviar a Produccion (visible en la fila) --}}
                                    @if(in_array($orden->estado, ['PENDIENTE_INSUMOS', 'Pendiente_Insumos', 'PENDIENTE_TELA', 'Pendiente Tela', 'PENDIENTE_PLOTTER', 'Pendiente Plotter', 'INSUMOS_PEDIDOS', 'Insumos Pedidos']))
                                        <button 
                                            class="btn-enviar-produccion btn-tooltip p-2 text-blue-600 hover:bg-blue-50 rounded transition"
                                            data-insumos-action="enviar-produccion"
                                            data-recibo-id="{{ $reciboId }}"
                                            data-consecutivo="{{ $orden->consecutivo_actual }}"
                                            data-tooltip="Enviar a produccion"
                                            title="Enviar a produccion"
                                        >
                                            <i class="fas fa-paper-plane text-lg"></i>
                                        </button>
                                    @endif
                                    
                                    <button 
                                        class="btn-acciones p-2 text-gray-600 hover:bg-gray-100 rounded transition"
                                        data-insumos-action="acciones-dropdown"
                                        data-pedido-produccion-id="{{ $pedidoProduccionId }}"
                                        data-prenda-id="{{ $orden->prenda_id ?? '' }}"
                                        data-recibo-id="{{ $reciboId }}"
                                        data-consecutivo="{{ $orden->consecutivo_actual }}"
                                        data-estado="{{ $orden->estado ?? '' }}"
                                        data-tipo-recibo="{{ $orden->tipo_recibo ?? 'COSTURA' }}"
                                        title="Mas opciones"
                                    >
                                        <i class="fas fa-ellipsis-v text-lg"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <span class="font-bold text-blue-600 text-lg">{{ $orden->numero_pedido ?? 'N/A' }}</span>
                        </td>
                        <td class="py-4 px-6">
                            <span class="font-medium text-gray-800">{{ $orden->numero_pedido_original ?? 'N/A' }}</span>
                        </td>
                        <td class="py-4 px-6">
                            <span class="font-medium text-gray-800">{{ $orden->cliente ?? 'N/A' }}</span>
                        </td>
                        <td class="py-6 px-6 text-center min-h-20">
                            @php
                                $estadoValor = $orden->estado ?? $orden->recibo_estado;
                                $estadoClass = '';
                                $estadoDisplay = '';

                                if ($estadoValor === 'No iniciado') {
                                    $estadoClass = 'bg-gray-400 text-white';
                                    $estadoDisplay = 'No iniciado';
                                } elseif ($estadoValor === 'En Ejecución' || $estadoValor === 'En Ejecucion') {
                                    $estadoClass = 'bg-blue-100 text-blue-800';
                                    $estadoDisplay = 'En Ejecución';
                                } elseif ($estadoValor === 'Anulada') {
                                    $estadoClass = 'bg-amber-100 text-amber-800';
                                    $estadoDisplay = 'Anulada';
                                } elseif ($estadoValor === 'PENDIENTE_INSUMOS' || $estadoValor === 'Pendiente_Insumos') {
                                    $estadoClass = 'bg-amber-500 text-white';
                                    $estadoDisplay = 'Pendiente Insumos';
                                } elseif ($estadoValor === 'PENDIENTE_TELA' || $estadoValor === 'Pendiente Tela') {
                                    $estadoClass = 'bg-yellow-400 text-gray-900';
                                    $estadoDisplay = 'Pendiente Tela';
                                } elseif ($estadoValor === 'PENDIENTE_PLOTTER' || $estadoValor === 'Pendiente Plotter') {
                                    $estadoClass = 'bg-yellow-400 text-gray-900';
                                    $estadoDisplay = 'Pendiente Plotter';
                                } elseif ($estadoValor === 'DEVUELTO_ASESOR') {
                                    $estadoClass = 'bg-red-500 text-white';
                                    $estadoDisplay = 'Devuelto Asesor';
                                } elseif ($estadoValor === 'Insumos Pedidos' || $estadoValor === 'INSUMOS_PEDIDOS') {
                                    $estadoClass = 'bg-green-500 text-white';
                                    $estadoDisplay = 'Insumos Pedidos';
                                } else {
                                    $estadoDisplay = str_replace('_', ' ', $estadoValor ?? 'N/A');
                                }

                                $estadosEditablesInsumos = ['PENDIENTE_INSUMOS', 'Pendiente_Insumos', 'PENDIENTE_TELA', 'Pendiente Tela', 'PENDIENTE_PLOTTER', 'Pendiente Plotter', 'Insumos Pedidos', 'INSUMOS_PEDIDOS'];
                                $puedeEditarInsumos = in_array($estadoValor, $estadosEditablesInsumos, true);
                                $mostrarSelector = ($roleName !== 'insumos') || ($roleName === 'insumos' && $puedeEditarInsumos);
                            @endphp

                            @if($mostrarSelector)
                                <div class="relative block w-full flex items-center justify-center">
                                    <select
                                        class="estado-select px-2 py-2 rounded-lg text-xs font-semibold border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer w-20 leading-tight whitespace-normal {{ $estadoClass }}"
                                        style="min-height: 3rem; line-height: 1.2; white-space: pre-line;"
                                        data-recibo-id="{{ $orden->id }}"
                                        data-estado-actual="{{ $estadoValor }}"
                                        data-rol="{{ $roleName }}"
                                        onchange="cambiarEstadoDesdeSelector(this); aplicarEstiloEstadoSelect(this);"
                                    >
                                        @if($roleName === 'insumos')
                                            <option value="PENDIENTE_INSUMOS" {{ in_array($estadoValor, ['PENDIENTE_INSUMOS', 'Pendiente_Insumos']) ? 'selected' : '' }}>Pendiente&#10;Insumos</option>
                                            <option value="PENDIENTE_TELA" {{ in_array($estadoValor, ['Pendiente Tela', 'PENDIENTE_TELA']) ? 'selected' : '' }}>Pendiente&#10;Tela</option>
                                            <option value="PENDIENTE_PLOTTER" {{ in_array($estadoValor, ['Pendiente Plotter', 'PENDIENTE_PLOTTER']) ? 'selected' : '' }}>Pendiente&#10;Plotter</option>
                                            <option value="INSUMOS_PEDIDOS" {{ in_array($estadoValor, ['Insumos Pedidos', 'INSUMOS_PEDIDOS']) ? 'selected' : '' }}>Insumos&#10;Pedidos</option>
                                        @else
                                            <option value="No iniciado" {{ $estadoValor === 'No iniciado' ? 'selected' : '' }}>No iniciado</option>
                                            <option value="En Ejecución" {{ $estadoValor === 'En Ejecución' || $estadoValor === 'En Ejecucion' ? 'selected' : '' }}>En Ejecución</option>
                                            <option value="PENDIENTE_INSUMOS" {{ in_array($estadoValor, ['PENDIENTE_INSUMOS', 'Pendiente_Insumos']) ? 'selected' : '' }}>Pendiente&#10;Insumos</option>
                                            <option value="PENDIENTE_TELA" {{ in_array($estadoValor, ['Pendiente Tela', 'PENDIENTE_TELA']) ? 'selected' : '' }}>Pendiente&#10;Tela</option>
                                            <option value="PENDIENTE_PLOTTER" {{ in_array($estadoValor, ['Pendiente Plotter', 'PENDIENTE_PLOTTER']) ? 'selected' : '' }}>Pendiente&#10;Plotter</option>
                                            <option value="INSUMOS_PEDIDOS" {{ in_array($estadoValor, ['Insumos Pedidos', 'INSUMOS_PEDIDOS']) ? 'selected' : '' }}>Insumos&#10;Pedidos</option>
                                            <option value="DEVUELTO_ASESOR" {{ $estadoValor === 'DEVUELTO_ASESOR' ? 'selected' : '' }}>Devuelto Asesor</option>
                                            <option value="Anulada" {{ $estadoValor === 'Anulada' ? 'selected' : '' }}>Anulada</option>
                                        @endif
                                    </select>
                                </div>
                            @else
                                <span class="inline-block px-3 py-2 rounded-lg text-sm font-semibold {{ $estadoClass }} break-words hover:text-white">
                                    {{ $estadoDisplay }}
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-center">
                            @php
                                $areaClass = '';
                                // Usar RECIBO_AREA que es lo que se filtra
                                $areaText = $orden->recibo_area ?? 'N/A';
                                if ($orden->recibo_area === 'Corte') {
                                    $areaClass = 'bg-purple-100 text-purple-800';
                                } elseif ($orden->recibo_area === 'Creación de Orden' || $orden->recibo_area === 'Creación de orden') {
                                    $areaClass = 'bg-green-100 text-green-800';
                                    $areaText = 'Creación de Orden';
                                }
                            @endphp
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold {{ $areaClass }}">
                                {{ $areaText }}
                            </span>
                        </td>
                        <td class="py-4 px-6 text-center">
                            @php
                                $motivoDevolucion = trim((string) ($orden->motivo_devolucion ?? ''));
                                $ultimaNovedadAsesora = trim((string) ($orden->ultima_novedad_asesora ?? ''));
                                $previewNovedad = $motivoDevolucion !== ''
                                    ? $motivoDevolucion
                                    : ($ultimaNovedadAsesora !== '' ? $ultimaNovedadAsesora : '');
                            @endphp
                            @if($previewNovedad !== '')
                                <button
                                    type="button"
                                    class="text-blue-700 text-xs font-semibold underline hover:text-blue-900 transition"
                                    data-insumos-action="open-novedades-modal"
                                    data-numero-recibo="{{ $orden->consecutivo_actual ?? '' }}"
                                    data-numero-pedido="{{ $orden->numero_pedido_original ?? '' }}"
                                    data-estado-recibo="{{ $orden->estado ?? '' }}"
                                    data-motivo-devolucion="{{ e($motivoDevolucion) }}"
                                    data-ultima-novedad-asesora="{{ e($ultimaNovedadAsesora) }}"
                                    title="Ver detalle de novedades"
                                >
                                    Dale click
                                </button>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-center">
                            <span class="text-gray-600 text-sm">
                                {{ $orden->created_at ? \Carbon\Carbon::parse($orden->created_at)->subHours(5)->format('d/m/Y') : 'N/A' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-12 px-6 text-center">
                            <p class="text-xl text-gray-500">No hay Órdenes disponibles</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Paginacion --}}
@if($ordenes instanceof \Illuminate\Pagination\Paginator || $ordenes instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="table-pagination" id="tablePagination">
        <div class="pagination-info">
            <span id="paginationInfo">Mostrando {{ $ordenes->firstItem() }}-{{ $ordenes->lastItem() }} de {{ $ordenes->total() }} registros</span>
        </div>
        <div class="pagination-controls" id="paginationControls">
            @if($ordenes->hasPages())
                <button class="pagination-btn" data-page="1" {{ $ordenes->currentPage() == 1 ? 'disabled' : '' }}>
                    <i class="fas fa-angle-double-left"></i>
                </button>
                <button class="pagination-btn" data-page="{{ $ordenes->currentPage() - 1 }}" {{ $ordenes->currentPage() == 1 ? 'disabled' : '' }}>
                    <i class="fas fa-angle-left"></i>
                </button>
                
                @php
                    $start = max(1, $ordenes->currentPage() - 2);
                    $end = min($ordenes->lastPage(), $ordenes->currentPage() + 2);
                @endphp
                
                @for($i = $start; $i <= $end; $i++)
                    <button class="pagination-btn page-number {{ $i == $ordenes->currentPage() ? 'active' : '' }}" data-page="{{ $i }}">
                        {{ $i }}
                    </button>
                @endfor
                
                <button class="pagination-btn" data-page="{{ $ordenes->currentPage() + 1 }}" {{ $ordenes->currentPage() == $ordenes->lastPage() ? 'disabled' : '' }}>
                    <i class="fas fa-angle-right"></i>
                </button>
                <button class="pagination-btn" data-page="{{ $ordenes->lastPage() }}" {{ $ordenes->currentPage() == $ordenes->lastPage() ? 'disabled' : '' }}>
                    <i class="fas fa-angle-double-right"></i>
                </button>
            @endif
        </div>
    </div>
@endif
