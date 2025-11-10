# ✅ PRUEBAS DE PAGINACIÓN - Tableros

## Checklist de Verificación Manual

### 1. ✅ Apariencia Visual
- [ ] Los botones tienen iconos de flechas (<<, <, >, >>)
- [ ] Los números de página se muestran correctamente
- [ ] El botón activo está en color naranja
- [ ] Los botones deshabilitados se ven grises
- [ ] La barra de progreso se muestra arriba de la paginación
- [ ] El texto "Mostrando X-Y de Z registros" es visible

### 2. ✅ Funcionalidad de Navegación

#### Página 1 (Primera página)
- [ ] Los botones "<<" y "<" están deshabilitados
- [ ] Los botones ">" y ">>" están habilitados
- [ ] El número "1" está resaltado en naranja
- [ ] Se muestran 50 registros (o menos si hay menos de 50 totales)
- [ ] Los registros más recientes (ID más alto) aparecen primero

#### Navegación a Página 2
- [ ] Click en ">" navega a la página 2
- [ ] Click en "2" navega a la página 2
- [ ] La URL cambia a `?page=2`
- [ ] El número "2" se resalta en naranja
- [ ] Se muestran los siguientes 50 registros
- [ ] La tabla se actualiza SIN recargar toda la página
- [ ] La barra de progreso se actualiza correctamente

#### Navegación a Última Página
- [ ] Click en ">>" navega a la última página
- [ ] Los botones ">" y ">>" se deshabilitan
- [ ] Los botones "<<" y "<" están habilitados
- [ ] Se muestran los registros restantes

#### Navegación a Primera Página
- [ ] Click en "<<" navega a la página 1
- [ ] Los botones "<<" y "<" se deshabilitan
- [ ] El número "1" se resalta en naranja

### 3. ✅ AJAX (Sin Recarga Completa)
- [ ] Al cambiar de página, solo la tabla se actualiza
- [ ] No hay parpadeo de toda la página
- [ ] La barra de navegación superior NO se recarga
- [ ] Los filtros y controles superiores permanecen intactos
- [ ] La transición es suave (opacity 0.3 durante carga)

### 4. ✅ Números de Página Dinámicos
Si tienes 10+ páginas:
- [ ] En página 1: muestra 1, 2, 3, 4, 5, ..., última
- [ ] En página 5: muestra 1, ..., 3, 4, 5, 6, 7, ..., última
- [ ] En página 10: muestra 1, ..., 8, 9, 10, 11, 12, ..., última
- [ ] En última página: muestra 1, ..., antepenúltima, penúltima, última

### 5. ✅ Tres Secciones
Repetir pruebas 1-4 para:
- [ ] Producción
- [ ] Polos
- [ ] Corte

### 6. ✅ Tiempo Real + Paginación
- [ ] Crear un nuevo registro en Producción
- [ ] El registro aparece en la página 1 (más reciente primero)
- [ ] Si estás en página 2, NO se recarga automáticamente
- [ ] Si estás en página 1, el registro aparece inmediatamente

### 7. ✅ Rendimiento
- [ ] El cambio de página toma menos de 1 segundo
- [ ] No hay errores en la consola del navegador (F12)
- [ ] No hay warnings de JavaScript
- [ ] La memoria del navegador no aumenta excesivamente

### 8. ✅ Casos Extremos

#### Sin registros
- [ ] No se muestra paginación
- [ ] Se muestra mensaje "No hay registros"

#### Exactamente 50 registros
- [ ] No se muestra paginación (solo 1 página)
- [ ] Se muestran todos los 50 registros

#### 51 registros
- [ ] Se muestra paginación con 2 páginas
- [ ] Página 1: 50 registros
- [ ] Página 2: 1 registro

## 🔍 Cómo Ejecutar las Pruebas

1. **Abrir el navegador**
   - Ir a: http://localhost:8000/tableros
   - Abrir DevTools (F12) → Pestaña Console

2. **Verificar que hay suficientes datos**
   ```
   - Producción: Mínimo 100 registros
   - Polos: Mínimo 100 registros
   - Corte: Mínimo 100 registros
   ```

3. **Ejecutar cada checklist**
   - Marcar cada ítem como completado
   - Anotar cualquier error encontrado

4. **Verificar logs en consola**
   ```javascript
   // Deberías ver:
   ✅ Paginación inicializada para produccion
   ✅ Paginación inicializada para polos
   ✅ Paginación inicializada para corte
   ✅ Página 2 cargada para produccion
   ```

## 🐛 Errores Comunes y Soluciones

### Error: "Paginación no funciona"
**Solución**: 
- Verificar que Font Awesome está cargado
- Verificar que `tableros-pagination.js` está cargado
- Limpiar caché del navegador (Ctrl+F5)

### Error: "Los números no se actualizan"
**Solución**:
- Verificar que el servidor devuelve el HTML correcto
- Verificar en Network tab que la petición es AJAX

### Error: "Los iconos no se ven"
**Solución**:
- Verificar que Font Awesome está cargado
- Verificar en Network tab que el CSS se descargó

## 📊 Resultados Esperados

✅ **TODAS las pruebas deben pasar**

Si alguna falla:
1. Anotar el número de la prueba
2. Describir el comportamiento esperado vs actual
3. Capturar screenshot si es posible
4. Revisar logs de consola

## 🎯 Criterios de Éxito

- ✅ Paginación funciona en las 3 secciones
- ✅ AJAX funciona sin recargar la página
- ✅ Los números se actualizan dinámicamente
- ✅ Los iconos se muestran correctamente
- ✅ El orden es descendente (más recientes primero)
- ✅ No hay errores en consola
- ✅ El rendimiento es aceptable (<1s por cambio)
