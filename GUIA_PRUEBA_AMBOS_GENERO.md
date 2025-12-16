# Guía de Prueba - Género "AMBOS" con Variaciones

## ¿QUÉ DEBES HACER PARA VER LAS VARIACIONES?

Este es el flujo completo para crear una cotización con género "AMBOS" y que salgan las variaciones correctamente.

---

## PASO 1: CREAR NUEVA COTIZACIÓN
1. Ve a **Crear Cotización**
2. Selecciona **Tipo de Venta** (M, D, o X)
3. Selecciona un **Cliente**
4. Agrega una **Prenda** (haz clic en "Agregar Prenda")

---

## PASO 2: LLENAR DATOS DE LA PRENDA
Dentro de la tarjeta de la prenda, completa:
- ✅ **Nombre de Producto**: Ej. "Camiseta Deportiva"
- ✅ **Descripción**: Ej. "Camiseta de algodón"
- ✅ **Cantidad**: Ej. "100"

---

## PASO 3: AGREGAR FOTOS (OPCIONAL PERO IMPORTANTE)
- Haz clic en el área de **"Arrastra fotos aquí"** bajo la sección PRENDA
- Selecciona 2-3 fotos de tu computadora
- **Espera a que veas el mensaje en la consola** del navegador (F12):
  ```
  ✅ Foto 1 de prenda guardada: ...
  ✅ Foto 2 de prenda guardada: ...
  ```

---

## PASO 4: SELECCIONAR GÉNERO
### Punto crítico: Aquí es donde sale "Sin variaciones"

1. Debajo de "TALLAS A COTIZAR", verás dos selectores:
   - **Selector 1**: "Selecciona tipo de talla"
   - **Selector 2**: "Selecciona género" (aparece después de elegir tipo)

2. **Primero**, selecciona el tipo de talla:
   - `NÚMEROS (DAMA/CABALLERO)` ← Elige esta opción

3. **Luego**, aparecerá el selector de género. Selecciona:
   - `Ambos (Dama y Caballero)` ← **ESTA ES LA CLAVE**

---

## PASO 5: VERIFICA QUE APAREZCAN DOS TABS
Después de seleccionar "Ambos", deberías ver:

```
┌─────────────────────────────────────┐
│  👩 DAMA    |    👨 CABALLERO       │
├─────────────────────────────────────┤
│  [6]  [8]  [10]  [12]  [14]  ...    │
│  (las tallas de DAMA)               │
└─────────────────────────────────────┘
```

### ¿Qué está pasando?
- Cada tab (DAMA / CABALLERO) es **independiente**
- Cuando haces clic en el tab de DAMA, ves tallas: 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26
- Cuando haces clic en el tab de CABALLERO, ves tallas: 28, 30, 32, 34, 36, 38, 40, 42, 44, 46, 48, 50

---

## PASO 6: SELECCIONA TALLAS DE AMBOS GÉNEROS
1. **Haz clic en el tab 👩 DAMA**
   - Selecciona 2-3 tallas (ej: 10, 14, 18)
   - **Cada talla que selecciones se marcará con color AZUL**

2. **Luego haz clic en el tab 👨 CABALLERO**
   - Selecciona 2-3 tallas (ej: 32, 38, 44)
   - **Cada talla se marcará con color AZUL**

---

## PASO 7: AGREGA VARIACIONES (OPCIONAL)
En la sección de "COLOR & TELA":
- Selecciona un **Color** (ej: Rojo)
- Selecciona una **Tela** (ej: Algodón)
- Agrega **Observaciones** si lo necesitas

---

## PASO 8: ABRE LA CONSOLA DEL NAVEGADOR
Presiona **F12** en tu navegador y ve a la pestaña **Console**.

Cuando hagas clic en **Guardar**, deberías ver mensajes como:

```javascript
✅ genero_id capturado: 4
✅ PRODUCTO AGREGADO: {
    nombre: "Camiseta Deportiva",
    tallas: 6,
    fotos: 3,
    telas: 1,
    variantes_keys: 8
}
```

### Importante:
- **genero_id = 4** significa "AMBOS"
- genero_id = 1 significa "DAMA"
- genero_id = 2 significa "CABALLERO"

---

## PASO 9: GUARDA LA COTIZACIÓN
Haz clic en el botón **"Guardar"**.

En la consola deberías ver:
```
🚀 INICIANDO GUARDADO DE COTIZACIÓN
📦 Datos recopilados: { ... }
✅ genero_id actualizado a: 4
...
✅ Respuesta del servidor: { success: true, ... }
```

---

## PASO 10: VERIFICA EN LA BASE DE DATOS
Ejecuta este comando en PowerShell:

```powershell
php -r "
\$conexion = new mysqli('localhost', 'root', '', 'mundoindustrial');
\$sql = 'SELECT id, genero_id, color, tela FROM prenda_variantes_cot WHERE genero_id IS NOT NULL ORDER BY id DESC LIMIT 3';
\$resultado = \$conexion->query(\$sql);
while (\$row = \$resultado->fetch_assoc()) {
    echo 'ID: ' . \$row['id'] . ', Género ID: ' . \$row['genero_id'] . ', Color: ' . \$row['color'] . ', Tela: ' . \$row['tela'] . PHP_EOL;
}
"
```

Si todo funciona, deberías ver:
```
ID: 123, Género ID: 4, Color: Rojo, Tela: Algodón
ID: 122, Género ID: 4, Color: Rojo, Tela: Algodón
```

---

## PASO 11: VER LA COTIZACIÓN
1. Ve a **Ver Cotización**
2. Busca la cotización que acabas de crear
3. Abre el modal de "Variaciones"
4. **Deberías ver las tallas que seleccionaste** sin "Sin variaciones"

---

## ¿QUÉ CAMBIAMOS PARA ARREGLARLO?

### 1. En `template-producto.blade.php` (línea 321):
```html
<input type="hidden" name="productos_friendly[][variantes][genero_id]" class="genero-id-hidden" value="">
<select class="talla-genero-select" onchange="actualizarGeneroSeleccionado(this)">
```

### 2. En `tallas.js`:
- Agregamos función `actualizarGeneroSeleccionado()` que mapea:
  - "dama" → genero_id = 1
  - "caballero" → genero_id = 2
  - "ambos" → genero_id = 4

- Agregamos soporte en `actualizarBotonesPorGenero()` para crear dos tabs cuando género es "ambos"

### 3. En `cotizaciones.js`:
- Agregamos captura del `genero_id` desde el input hidden
- Ahora se envía al backend correctamente en las variantes

---

## CHECKLIST DE VERIFICACIÓN

- [ ] Veo dos tabs (DAMA y CABALLERO) cuando selecciono "Ambos"
- [ ] Puedo hacer clic en cada tab y cambian las tallas
- [ ] Selecciono tallas de DAMA y se marcan en azul
- [ ] Selecciono tallas de CABALLERO y se marcan en azul
- [ ] Cuando guardo, veo "✅ genero_id capturado: 4" en la consola
- [ ] En base de datos, genero_id = 4 para las variantes creadas
- [ ] Cuando veo la cotización, aparecen las tallas (no dice "Sin variaciones")

---

## TROUBLESHOOTING

### Problema: No aparecen los tabs de DAMA/CABALLERO
**Solución:**
1. Abre la consola (F12)
2. Busca mensajes de error (rojo)
3. Verifica que seleccionaste "Números (DAMA/CABALLERO)" primero
4. Recarga la página (Ctrl+F5)

### Problema: Las tallas no se seleccionan
**Solución:**
1. Verifica que los botones tengan la clase `.talla-btn`
2. En la consola, verifica que se mostró "Actualizando botones para género: ambos"
3. Haz clic directamente en los números

### Problema: Dice "Sin variaciones" en el view
**Solución:**
1. Verifica que en la base de datos, genero_id = 4 (no NULL)
2. Verifica que las tallas están en `prenda_tallas_cot`
3. Ejecuta: `php artisan tinker` y verifica la variante:
   ```
   \App\Models\PrendaVarianteCot::latest()->first()
   ```

