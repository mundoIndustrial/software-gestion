{{-- 
  Componente: Item Card para Pedidos
  
  Props esperadas:
  - $item: objeto con datos del item
  - $index: índice del item en la lista
  
  Estructura del item:
  {
    tipo: 'prenda_nueva' | 'cotizacion' | 'reflectivo',
    prenda: nombre,
    descripcion: string,
    ref: string,
    color: string,
    tela: string,
    imagenes: array,
    imagenTela: string,
    generosConTallas: { genero: { talla: cantidad } },
    variaciones: {
      manga: { tipo, observacion },
      bolsillos: { tiene, observacion },
      broche: { tipo, observacion },
      reflectivo: { tiene, observacion }
    },
    procesos: array,
    origen: string
  }
--}}

<div class="item-card" data-item-index="{{ $index }}">
  <!-- HEADER: Información Principal -->
  <div class="card-header">
    <!-- Imagen de la Prenda -->
    <div class="card-imagen-contenedor">
      @if(isset($item['imagenes']) && count($item['imagenes']) > 0)
        <img src="{{ $item['imagenes'][0]['url'] ?? '' }}" 
             alt="{{ $item['prenda'] ?? 'Prenda' }}" 
             class="card-imagen-prenda"
             onerror="this.src='{{ asset('images/placeholder.png') }}'">
      @else
        <div class="card-imagen-prenda card-imagen-placeholder">
          <span class="material-symbols-rounded">image_not_supported</span>
        </div>
      @endif
    </div>

    <!-- Datos Principales -->
    <div class="card-datos-principales">
      <h3 class="card-titulo">{{ $item['prenda'] ?? 'Sin nombre' }}</h3>
      <p class="card-descripcion">{{ $item['descripcion'] ?? 'Sin descripción' }}</p>
      
      <div class="card-meta-grid">
        <!-- REF -->
        @if(isset($item['ref']))
          <div class="meta-item">
            <span class="meta-label">REF:</span>
            <span class="meta-valor">{{ $item['ref'] }}</span>
          </div>
        @endif

        <!-- COLOR -->
        @if(isset($item['color']))
          <div class="meta-item">
            <span class="meta-label">Color:</span>
            <span class="meta-valor">{{ $item['color'] }}</span>
          </div>
        @endif

        <!-- TELA con imagen -->
        @if(isset($item['tela']))
          <div class="meta-item">
            <span class="meta-label">Tela:</span>
            <span class="meta-valor">{{ $item['tela'] }}</span>
            @if(isset($item['imagenTela']))
              <img src="{{ $item['imagenTela'] }}" 
                   alt="{{ $item['tela'] }}" 
                   class="mini-imagen-tela"
                   onerror="this.style.display='none'">
            @endif
          </div>
        @endif
      </div>
    </div>

    <!-- Menú de Opciones (Editar/Eliminar) -->
    <div class="btn-menu-wrapper">
      <button type="button" class="btn-menu-expandible" title="Más opciones">
        <span class="material-symbols-rounded">more_vert</span>
      </button>
      <div class="menu-dropdown" style="display: none;">
        <button type="button" class="menu-item btn-editar-item" data-item-index="{{ $index }}" title="Editar item">
          <span class="material-symbols-rounded">edit</span> Editar
        </button>
        <button type="button" class="menu-item btn-eliminar-item" data-item-index="{{ $index }}" title="Eliminar item">
          <span class="material-symbols-rounded">delete</span> Eliminar
        </button>
      </div>
    </div>
  </div>

  <!-- SECCIÓN: VARIACIONES -->
  @if(isset($item['variaciones']) && is_array($item['variaciones']))
    <div class="card-section expandible" data-section="variaciones">
      <div class="section-header" onclick="toggleSection(this)">
        <span class="section-titulo">
          Variaciones
        </span>
        <span class="section-toggle">▼</span>
      </div>
      
      <div class="section-content" style="display: none;">
        <table class="variaciones-tabla">
          <!-- Manga -->
          @if(isset($item['variaciones']['manga']) && isset($item['variaciones']['manga']['tipo']))
            <tr>
              <td class="var-label">Manga:</td>
              <td class="var-valor">{{ ucfirst(strtolower($item['variaciones']['manga']['tipo'])) }} @if(isset($item['variaciones']['manga']['observacion']) && $item['variaciones']['manga']['observacion'])— {{ $item['variaciones']['manga']['observacion'] }}@endif</td>
            </tr>
          @endif

          <!-- Bolsillos -->
          @if(isset($item['variaciones']['bolsillos']) && $item['variaciones']['bolsillos']['tiene'])
            <tr>
              <td class="var-label">Bolsillos:</td>
              <td class="var-valor">@if(isset($item['variaciones']['bolsillos']['observacion']) && $item['variaciones']['bolsillos']['observacion']){{ $item['variaciones']['bolsillos']['observacion'] }}@else Con bolsillos @endif</td>
            </tr>
          @endif

          <!-- Broche/Botón (título dinámico según lo seleccionado) -->
          @if(isset($item['variaciones']['broche']) && isset($item['variaciones']['broche']['tipo']))
            <tr>
              <td class="var-label">{{ ucfirst($item['variaciones']['broche']['tipo']) }}:</td>
              <td class="var-valor">@if(isset($item['variaciones']['broche']['observacion']) && $item['variaciones']['broche']['observacion']){{ $item['variaciones']['broche']['observacion'] }}@endif</td>
            </tr>
          @endif

          <!-- Reflectivo -->
          @if(isset($item['variaciones']['reflectivo']) && $item['variaciones']['reflectivo']['tiene'])
            <tr>
              <td class="var-label">Reflectivo:</td>
              <td class="var-valor">@if(isset($item['variaciones']['reflectivo']['observacion']) && $item['variaciones']['reflectivo']['observacion']){{ $item['variaciones']['reflectivo']['observacion'] }}@else Incluido @endif</td>
            </tr>
          @endif
        </table>
      </div>
    </div>
  @endif

  <!-- SECCIÓN: TALLAS POR GÉNERO -->
  @if(isset($item['generosConTallas']) && is_array($item['generosConTallas']))
    @php
      // Calcular total de unidades
      $totalUnidades = 0;
      foreach($item['generosConTallas'] as $genero => $tallas) {
        foreach($tallas as $talla => $cantidad) {
          $totalUnidades += (int)$cantidad;
        }
      }
    @endphp
    
    <div class="card-section expandible" data-section="tallas">
      <div class="section-header" onclick="toggleSection(this)">
        <span class="section-titulo">
          <span class="icon"></span> Tallas (Total: {{ $totalUnidades }} unidades)
        </span>
        <span class="section-toggle">▼</span>
      </div>
      
      <div class="section-content" style="display: none;">
        <div class="tallas-por-genero">
          @foreach($item['generosConTallas'] as $genero => $tallas)
            <div class="genero-grupo">
              <h5 class="genero-titulo">{{ ucfirst($genero) }}</h5>
              <div class="tallas-grid">
                @foreach($tallas as $talla => $cantidad)
                  @if((int)$cantidad > 0)
                    <div class="talla-item">
                      <span class="talla-valor">{{ $talla }}</span>
                      <span class="talla-cantidad">{{ $cantidad }}</span>
                    </div>
                  @endif
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  @endif

  <!-- SECCIÓN: PROCESOS -->
  @if(isset($item['procesos']) && is_array($item['procesos']) && count($item['procesos']) > 0)
    <div class="card-section expandible" data-section="procesos">
      <div class="section-header" onclick="toggleSection(this)">
        <span class="section-titulo">
          <span class="icon">⚙️</span> Procesos ({{ count($item['procesos']) }} procesos)
        </span>
        <span class="section-toggle">▼</span>
      </div>
      
      <div class="section-content" style="display: none;">
        <div class="procesos-lista">
          @foreach($item['procesos'] as $proceso)
            <span class="proceso-badge">{{ $proceso }}</span>
          @endforeach
        </div>
      </div>
    </div>
  @endif


</div>
