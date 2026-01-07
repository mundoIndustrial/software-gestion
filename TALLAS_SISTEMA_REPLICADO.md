# Sistema de Tallas - Replicación Exacta de Cotizaciones

## Estado: ✅ COMPLETADO

### Cambios Realizados

#### 1. Archivo Nuevo: `gestor-tallas-sin-cotizacion.js`
- **Ubicación**: `/public/js/modulos/crear-pedido/gestor-tallas-sin-cotizacion.js`
- **Propósito**: Gestionar toda la lógica de tallas para prendas sin cotización
- **Característica clave**: Replicación pixel-por-pixel del sistema de cotizaciones

#### 2. Estructura HTML Replicada
Exactamente como en `/resources/views/cotizaciones/prenda/create.blade.php` (líneas 850-920):

```html
<div class="tipo-prenda-row" data-prenda-index="${index}">
  <!-- Fila 1: Selectores tipo, género, modo -->
  <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
    <select class="talla-tipo-select" onchange="actualizarSelectTallasSinCot(this)">
    <select class="talla-genero-select" style="display: none;">
    <select class="talla-modo-select" style="display: none;">
    <div class="talla-rango-selectors" style="display: none;">
      <select class="talla-desde"></select>
      <select class="talla-hasta"></select>
      <button onclick="agregarTallasRangoSinCot(this)">+</button>
    </div>
  </div>
  
  <!-- Fila 2: Botones de tallas (Manual) -->
  <div class="talla-botones" style="display: none;">
    <div class="talla-botones-container"></div>
    <button onclick="agregarTallasSeleccionadasSinCot(this)">+</button>
  </div>
  
  <!-- Fila 3: Tallas agregadas -->
  <div class="tallas-section">
    <div class="tallas-agregadas"></div>
    <input type="hidden" class="tallas-hidden">
  </div>
</div>
```

### Funciones Principales

| Función | Propósito | Sincroniza |
|---------|-----------|-----------|
| `actualizarSelectTallasSinCot(select)` | Detecta tipo (LETRAS/NÚMEROS) | ✅ Sí |
| `actualizarBotonesPorGeneroSinCot(container, genero)` | Filtra tallas por género | ✅ Sí |
| `actualizarModoLetrasSinCot(container, modo)` | Cambia entre Manual/Rango | ✅ Sí |
| `actualizarModoNumerosSinCot(container, modo)` | Cambia entre Manual/Rango | ✅ Sí |
| `agregarTallasSeleccionadasSinCot(btn)` | Agrega tallas del modo Manual | ✅ Sí |
| `agregarTallasRangoSinCot(btn)` | Agrega tallas del rango | ✅ Sí |
| `crearTagTallaSinCot(container, talla, prenda)` | Crea tag visual de talla | ✅ Sí |
| `eliminarTallaDeLaTablaSinCot(talla, container)` | Elimina talla del tag | ✅ Sí |

### Sincronización con Gestor

**En `crearTagTallaSinCot()`:**
```javascript
if (prendaIndex >= 0 && window.gestorPrendaSinCotizacion) {
    window.gestorPrendaSinCotizacion.agregarTalla(prendaIndex, talla);
}
```

**En `eliminarTallaDeLaTablaSinCot()`:**
```javascript
if (prendaIndex >= 0 && window.gestorPrendaSinCotizacion) {
    window.gestorPrendaSinCotizacion.eliminarTalla(prendaIndex, talla);
}
```

### Arrays de Tallas

Idénticos a cotizaciones:
- **Letras**: XS, S, M, L, XL, XXL, XXXL, XXXXL
- **Dama**: 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26
- **Caballero**: 28, 30, 32, 34, 36, 38, 40, 42, 44, 46, 48, 50

### Flujo de Usuario

1. **Paso 1**: Selecciona tipo de talla
   - LETRAS → Muestra género y modo
   - NÚMEROS → Muestra género y modo

2. **Paso 2**: Si NÚMEROS, selecciona género
   - DAMA → Filtra tallas Dama
   - CABALLERO → Filtra tallas Caballero

3. **Paso 3**: Selecciona modo
   - MANUAL → Muestra botones de tallas individuales
   - RANGO → Muestra selectores "Desde" y "Hasta"

4. **Paso 4**: Agrega tallas
   - Manual: Hace click en botones y luego presiona "+"
   - Rango: Selecciona desde y hasta, presiona "+"

5. **Resultado**: Tallas aparecen como tags con opción de eliminar

### Integración

**Cargado en**: `/resources/views/asesores/pedidos/crear-desde-cotizacion-editable.blade.php` (línea 728)

```php
<script src="{{ asset('js/modulos/crear-pedido/gestor-tallas-sin-cotizacion.js') }}?v={{ time() }}"></script>
```

### Testing

Para verificar que funciona:
1. Abrir formulario de crear pedido tipo PRENDA
2. Agregar una prenda
3. En sección de tallas:
   - Seleccionar "LETRAS"
   - Ver que aparecen género y modo
   - Seleccionar género (Dama/Caballero)
   - Seleccionar modo (Manual/Rango)
   - Verificar que se muestren las opciones correctas
   - Agregar tallas
   - Verificar que aparezcan como tags
   - Eliminar talla y verificar que se sincronice

### Notas Importantes

- **Clase `.tipo-prenda-row`**: Usado para encontrar container correcto
- **Atributo `data-prenda-index`**: Usado para sincronizar con gestor
- **Event listeners dinámicos**: Agregados cuando tipo de talla cambia
- **Limpieza automática**: Los listeners anteriores se remueven antes de agregar nuevos
- **Validaciones**: Incluidas para rangos inválidos y tallas duplicadas

### Compatibilidad

- ✅ Funciona con tallas preexistentes (las muestra como tags)
- ✅ Sincroniza automáticamente con el gestor de prendas
- ✅ Compatible con el envío de formulario
- ✅ Mantiene datos cuando se re-renderiza
