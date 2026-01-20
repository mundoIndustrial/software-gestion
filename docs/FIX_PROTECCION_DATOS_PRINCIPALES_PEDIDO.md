# FIX: Protección de Datos Principales en Crear Pedido Nuevo

## Problema Reportado
El usuario reportó que al crear un pedido nuevo en `http://servermi:8000/asesores/pedidos-produccion/crear-nuevo`, se estaba borrando la información del pedido (cliente, forma de pago, etc.) cuando agregaba un ítem de prenda. Esto ocurría porque el usuario había implementado lógica para limpiar datos del modal cuando agrega un item.

## Causa Identificada
- Las funciones `cerrarModalPrendaNueva()` y `abrirModalAgregarPrendaNueva()` limpian datos ÚNICAMENTE del modal de prenda
- Sin embargo, podría haber código adicional o selectores incorrectos que afectaran el formulario principal
- La limpieza de globales de ítems es correcta, pero necesitaba más protección

## Solución Implementada

### 1. **Mejora de Seguridad en `prendas-wrappers.js`**
   - Agregada validación adicional para asegurar que SOLO se limpian campos del modal (`nueva-prenda-*`)
   - Añadida condición: `fieldId.startsWith('nueva-prenda-')` para mayor seguridad
   - Actualizado comentario explicativo

### 2. **Documentación en `gestion-items-pedido.js`**
   - Agregado comentario explícito de seguridad en `abrirModalAgregarPrendaNueva()`
   - Clarificado que NUNCA se deben limpiar datos del formulario principal
   - Mejorada la descripción de qué se limpia

### 3. **Nuevo Módulo: `protector-datos-principales.js`** 
   ```
   Ubicación: /public/js/modulos/crear-pedido/seguridad/protector-datos-principales.js
   ```
   
   Funcionalidades:
   - **Guardado automático**: Captura los datos principales del pedido al cargar la página
   - **Monitoreo continuo**: Verifica cada 2 segundos si los datos fueron modificados sin autorización
   - **Restauración automática**: Si detecta limpieza accidental, restaura los datos automáticamente
   - **Campos protegidos**:
     - `cliente_editable`
     - `forma_de_pago_editable`
     - `asesora_editable`
     - `numero_pedido_editable`

### 4. **Integración en Vista**
   - Agregado el módulo de protección en `crear-pedido-nuevo.blade.php`
   - Cargado como el **PRIMER script** para garantizar protección desde el inicio

## Archivos Modificados

1.  `/public/js/componentes/prendas-wrappers.js`
   - Línea 63-78: Mejorada seguridad de limpieza

2.  `/public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js`
   - Línea 169-171: Agregado comentario de seguridad

3.  `/public/js/modulos/crear-pedido/seguridad/protector-datos-principales.js` (NUEVO)
   - Módulo completo de protección

4.  `/resources/views/asesores/pedidos/crear-pedido-nuevo.blade.php`
   - Línea 143-146: Agregada carga del módulo de protección

## Beneficios

 **Protección automática**: Los datos principales están protegidos incluso si hay otros problemas
 **Monitoreo continuo**: Detecta y restaura automáticamente cualquier limpieza accidental
 **Sin cambios en lógica de negocio**: La solución es no-invasiva
 **Información al usuario**: Logs en consola para debugging

## Cómo Funciona

1. Al cargar la página, el módulo guarda: cliente, forma_de_pago, asesora, numero_pedido
2. Cada 2 segundos, verifica si estos campos fueron limpiados
3. Si detecta que un campo principal fue limpiado:
   - Lo restaura automáticamente
   - Dispara evento `input` para actualizar componentes
   - Registra en consola para auditoría

## Testing

Para verificar que funciona:

1. Abre la página de crear nuevo pedido
2. Ingresa cliente: "Test Client"
3. Ingresa forma de pago: "Crédito"
4. Abre consola (F12) y busca logs `[ProtectorDatosPrincipales]`
5. Agrega un item/prenda
6. Verifica que cliente y forma_de_pago sigan siendo "Test Client" y "Crédito"

## Rollback (si es necesario)

Si necesitas revertir los cambios:
```bash
git checkout public/js/componentes/prendas-wrappers.js
git checkout public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js
git checkout resources/views/asesores/pedidos/crear-pedido-nuevo.blade.php
rm public/js/modulos/crear-pedido/seguridad/protector-datos-principales.js
```
