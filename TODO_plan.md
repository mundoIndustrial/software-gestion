# Plan para reorganizar top-controls

## Información Recopilada
- El componente `top-controls.blade.php` contiene tanto los botones de acción (action-icons) como el selector de fechas (date-selector-section).
- Actualmente, el layout usa `justify-content: space-between` en `.top-controls`, pero el `.date-selector-section` tiene `margin: 10px auto` que lo centra y lo separa verticalmente.
- Los estilos inline en `top-controls.blade.php` sobrescriben los estilos globales y causan que el selector de fechas aparezca como un bloque separado debajo.
- El selector de fechas tiene `flex-direction: column`, lo que apila los elementos verticalmente.

## Plan de Cambios
- Editar `resources/views/components/top-controls.blade.php` para ajustar los estilos CSS inline del `.date-selector-section`:
  - Quitar `margin: 10px auto` y reemplazar con `margin: 0`.
  - Quitar `background: rgba(255, 255, 255, 0.03)` para que no tenga fondo separado.
  - Cambiar `align-items: center` a `align-items: flex-start` para alinear a la izquierda dentro del contenedor derecho.
  - Reducir `padding: 15px 20px` a `padding: 0` para compactar.
  - Cambiar `width: 100%` a `width: auto` para que no ocupe todo el ancho.
  - Quitar `border-radius: 12px` para que no tenga bordes redondeados separados.
- Ajustar `.filters-row` para que los filtros se alineen a la izquierda: cambiar `justify-content: center` a `justify-content: flex-start`.
- Reducir gaps para compactar: `gap: 20px` a `gap: 10px` en `.filters-row`, y `gap: 15px` a `gap: 10px` en `.date-inputs-inline`.

## Archivos a Editar
- `resources/views/components/top-controls.blade.php`

## Pasos de Seguimiento
- Aplicar los cambios CSS.
- Verificar que los action-buttons estén a la izquierda y el date-selector a la derecha en la misma barra.
- Probar en diferentes tamaños de pantalla para asegurar responsividad.

## Cambios Aplicados
- [x] Ajustado `.date-selector-section`: quitado margin, padding, background, border-radius; cambiado align-items a flex-start, width a auto.
- [x] Ajustado `.filters-row`: cambiado justify-content a flex-start, reducido gap a 10px.
- [x] Ajustado `.date-inputs-inline`: reducido gap a 10px.
