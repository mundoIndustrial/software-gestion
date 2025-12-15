# Implementación: Prendas con Reflectivo Individual

## Descripción General
Se ha restructurado completamente el sistema de cotizaciones reflectivas (tipo RF) para permitir que cada prenda tenga su propia descripción de reflectivo, mientras que ubicaciones, observaciones e imágenes se mantienen de manera general para toda la cotización.

## Cambios Realizados

### 1. Vista de Creación: `create-reflectivo.blade.php`

#### Cambios en el HTML
- **Sección PRENDAS CON REFLECTIVO**: Nueva estructura que incluye:
  - Dropdown select para tipo de prenda (Camiseta, Pantalón, Chaqueta, etc.)
  - Textarea para descripción del reflectivo específica de esa prenda
  - Botón "+ Agregar Prenda" 
  - Grid visual que muestra las prendas agregadas con su tipo y descripción

#### Cambios en el JavaScript
- **Nueva función `agregarPrendaConReflectivo()`**:
  - Obtiene tanto el tipo de prenda como la descripción del reflectivo
  - Valida que ambos campos estén completos
  - Agrega a `prendasSeleccionadas` como objeto: `{tipo, descripcion}`
  - Limpia los campos después de agregar

- **Función `renderizarPrendas()` actualizada**:
  - Ahora muestra cada prenda con su descripción en un card visual
  - Estructura: "Prenda 1: [Tipo]" + "Descripción Reflectivo: [descripcion]"
  - Botón X para eliminar prendas

- **Event listener removido**:
  - Se eliminó listener para "nombre_prenda" que ya no existe

- **Corrección de ID**:
  - El campo de descripción general ahora correctamente usa ID `descripcion_general_reflectivo`

#### Estructura de Datos Enviada
```javascript
{
  prendas: [
    {tipo: "Camiseta", descripcion: "Reflectivo 3M de 5cm en pecho..."},
    {tipo: "Pantalón", descripcion: "Reflectivo en costados..."}
  ],
  descripcion_reflectivo: "Descripción general",
  ubicaciones_reflectivo: [...],
  observaciones_generales: [...],
  imagenes_reflectivo: [...]
}
```

### 2. Controlador: `CotizacionController.php`

#### `storeReflectivo()` - Actualizado
- **Cambio de validación**:
  - Removido validador `prendas.*` (string)
  - Ahora acepta array con objetos de prendas

- **Procesamiento de Prendas**:
  - Cada prenda viene como JSON string o array
  - Se decodifica si es string
  - Se crea `PrendaCot` con:
    - `nombre_producto`: tipo de prenda
    - `descripcion`: descripción del reflectivo específica
  - Se utiliza modelo correcto `PrendaCot` (no `Prenda`)

- **Campos guardados en PrendaCot**:
  ```php
  'nombre_producto' => $prenda['tipo'],
  'cantidad' => 1,
  'descripcion' => $prenda['descripcion']
  ```

#### `updateReflectivo()` - Actualizado
- **Cambios similares a `storeReflectivo()`**:
  - Actualizada validación para aceptar prendas array
  - Nuevo bloque que:
    - Elimina prendas existentes
    - Crea nuevas prendas con los datos actualizados
  - Mantiene la lógica de actualización de reflectivo, ubicaciones, observaciones e imágenes

#### `getReflectivoForEdit()` - Actualizado
- **Nuevas relaciones cargadas**:
  - Ahora carga `prendas` además de `reflectivoCotizacion`

- **Procesamiento de prendas antes de devolver**:
  ```php
  $prendasProcesadas[] = [
    'id' => $prenda->id,
    'tipo' => $prenda->nombre_producto,
    'descripcion' => $prenda->descripcion
  ];
  ```

- **Response incluye prendas procesadas**:
  ```json
  {
    "prendas": [
      {"id": 1, "tipo": "Camiseta", "descripcion": "..."},
      {"id": 2, "tipo": "Pantalón", "descripcion": "..."}
    ]
  }
  ```

### 3. Vista de Detalle: `reflectivo-tab-direct.blade.php`

#### Nueva Sección: "Prendas con Reflectivo"
- **Ubicación**: Justo después del header, antes de la descripción general
- **Contenido**: Grid de cards con:
  - Nombre de la prenda (tipo)
  - Descripción individual del reflectivo en box destacado
  - Estilo visual consistente con blue theme

- **Reordenamiento**:
  - "Descripción del Reflectivo" renombrado a "Descripción General"
  - Ahora aparece después de las prendas individuales

#### HTML de la Sección Prendas
```blade
@foreach($cotizacion->prendas as $prenda)
    <div style="border: 1px solid #e2e8f0; border-left: 4px solid #0ea5e9; ...">
        <h4>{{ $prenda->nombre_producto }}</h4>
        <div style="background: #f0f7ff; border-left: 3px solid #0284c7; ...">
            {{ $prenda->descripcion }}
        </div>
    </div>
@endforeach
```

## Flujo Completo

### 1. Crear Nueva Cotización RF
1. Usuario accede a `create-reflectivo.blade.php`
2. Selecciona tipo de prenda (ej: "Camiseta")
3. Escribe descripción del reflectivo para esa prenda
4. Hace clic "+ Agregar Prenda"
5. La prenda aparece en el grid visual
6. Repite pasos 2-5 para más prendas
7. Completa ubicaciones, observaciones e imágenes (GENERALES)
8. Completa descripción general (aplica a todas)
9. Guarda como borrador o envía

### 2. Editar Cotización RF (Borrador)
1. Usuario accede a botón "Editar" en un borrador
2. Se carga `getReflectivoForEdit()` que devuelve prendas procesadas
3. El formulario (en modal o página) se precarga con:
   - Lista de prendas con sus descripciones
   - Ubicaciones, observaciones, imágenes generales
4. Puede modificar prendas, eliminar, agregar nuevas
5. Puede cambiar ubicaciones, observaciones, imágenes
6. Guarda cambios

### 3. Ver Cotización Enviada (tipo RF)
1. Usuario accede al show view
2. Se muestran prendas con sus descripciones individuales
3. Se muestra descripción general
4. Se muestran ubicaciones, observaciones, imágenes

## Ventajas de la Arquitectura

✅ **Claridad**: Cada prenda tiene su propia descripción visible  
✅ **Flexibilidad**: Ubicaciones/observaciones/imágenes reutilizables entre prendas  
✅ **Mantenimiento**: Cambios en la descripción de una prenda no afectan otras  
✅ **Consistencia**: Sigue el patrón de cotizaciones de prendas normales (tipo P, L, PL)  
✅ **UX**: Visual claro en formulario y en visualización

## Base de Datos

### Tabla `prendas_cot`
La tabla `PrendaCot` ahora almacena:
- `nombre_producto`: Tipo de prenda (Camiseta, Pantalón, etc.)
- `descripcion`: Descripción del reflectivo específica
- `cantidad`: 1 (para reflectivo)

### Tabla `reflectivo_cotizacion`
Mantiene su estructura:
- `descripcion`: Descripción general (aplica a todas las prendas)
- `ubicacion`: Ubicaciones en JSON (generales)
- `observaciones_generales`: Observaciones en JSON (generales)

## Testing

Para validar el flujo:
1. Crear una cotización RF con 2+ prendas
2. Verificar que se guardaron correctamente en `prendas_cot`
3. Editar el borrador y cambiar descripciones
4. Ver la cotización y confirmar prendas con descripciones aparecen
5. Enviar la cotización y validar que numero_cotizacion y fecha_envio se asignen correctamente

## Próximos Pasos (Opcionales)

- [ ] Agregar modal de edición rápida de prendas desde el show view
- [ ] Implementar drag-and-drop para reordenar prendas
- [ ] Agregar validaciones más robustas en frontend
- [ ] Crear vista de comparación entre versiones de borradores
