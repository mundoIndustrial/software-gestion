# Plan para Rediseñar la Barra de Opciones en Tableros

## Información Recopilada
- **tableros.blade.php**: Incluye `@include('components.action-buttons')` y `@include('components.date-selector')` por separado para cada tab (produccion, polos, corte). El orden varía, pero actualmente están separados.
- **action-buttons.blade.php**: Contiene botones con texto ("Mostrar Registros", "Registro Nuevo") y un spacer.
- **date-selector.blade.php**: Selector de fechas completo con tipos de filtro (rango, día, mes, específicos), inputs dinámicos, calendario para días específicos, estilos y script.
- **top-controls.blade.php**: Ya combina actions como iconos a la izquierda (mostrar/ocultar y nuevo registro) y filtro a la derecha, con `x-show="!showRecords"` para ocultar cuando se muestran registros. Tiene tooltips básicos, pero le falta el calendario completo y el script para filtrar.
- **tableros.css**: Tiene estilos para `.top-controls`, `.action-icons`, `.icon-btn`, etc., pero necesita ajustes para el calendario.
- **TODO_date_selector.md**: Indica que date-selector tiene calendario y funcionalidades avanzadas.

## Plan Detallado
1. **Reemplazar inclusiones en tableros.blade.php**:
   - Quitar `@include('components.action-buttons')` y `@include('components.date-selector')`.
   - Agregar `@include('components.top-controls')` para cada tab, asegurando consistencia.

2. **Actualizar top-controls.blade.php**:
   - Mantener actions como iconos a la izquierda con tooltips ("Mostrar / Ocultar registros", "Nuevo registro").
   - Integrar el date-selector completo a la derecha, incluyendo el calendario para "Días específicos".
   - Copiar estilos y script de date-selector.blade.php para funcionalidad completa (calendario, filtrarPorFechas).
   - Asegurar que el filtro se oculte con `x-show="!showRecords"`.
   - Cambiar icono de mostrar/ocultar dinámicamente (ya implementado con templates).

3. **Actualizar estilos en tableros.css**:
   - Ajustar `.top-controls` para layout horizontal (actions izquierda, filtro derecha).
   - Agregar estilos para calendario si no están (copiar de date-selector).

4. **Funcionalidad JavaScript**:
   - Integrar script de calendario y filtrarPorFechas en top-controls o tableros.blade.php.
   - Asegurar que el botón "Aplicar Filtro" funcione y actualice URL.

## Archivos a Editar
- resources/views/tableros.blade.php
- resources/views/components/top-controls.blade.php
- resources/css/tableros.css

## Archivos Dependientes
- Ninguno nuevo, reutilizar existentes.

## Pasos de Seguimiento
- Probar la barra unificada en cada tab.
- Verificar que el filtro se oculte al mostrar registros.
- Confirmar tooltips y cambio de iconos.
- Probar calendario y filtros.
- Ajustar responsive si necesario.
