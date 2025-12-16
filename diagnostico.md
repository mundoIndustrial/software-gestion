# Diagnóstico de Problemas - Cotización Bordado

## Problema 1: Técnicas vacías
**Síntoma**: `🎨 Técnicas seleccionadas: []`
**Causa**: `tecnicasSeleccionadas` está vacío cuando se presiona "Guardar Borrador"

**Análisis**:
- Frontend envía: `tecnicas: []` (array vacío)
- Backend recibe: `[]` 
- Se guarda como: `[]` en la BD

**Solución aplicada**:
- Arreglado `persistencia.js` para NO limpiar `tecnicasSeleccionadas` al cargar la página
- Ahora las técnicas que agrega el usuario se preservan

## Problema 2: Cliente no se guarda
**Síntoma**: Cliente se envía pero no aparece en la BD después de recargar
**Causa**: Posible problema con cómo se actualiza la relación cliente_id

**Análisis**:
- Frontend envía: `cliente: 'MINCIVIL'` (nombre del cliente)
- Backend recibe: `cliente_id: null, cliente: 'MINCIVIL'`
- Backend busca/crea cliente por nombre
- Se asigna `cliente_id` correctamente
- Se guarda en Cotizacion

**Verificación necesaria**:
- Revisar si la cotización se está guardando con `cliente_id` correcto
- Revisar si hay un problema con cómo se devuelven los datos en la respuesta

## Próximos pasos:
1. Prueba crear una nueva cotización con técnicas
2. Verifica que se guarden correctamente
3. Si aún hay problemas, revisar los logs del servidor
