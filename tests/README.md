# 🧪 Tests de Captura de Información de Tallas

Este directorio contiene tests para validar que la información de tallas se captura correctamente en todo el flujo de creación de pedidos.

## 📋 Archivos de Test

### 1. `TestGenerosConTallasCapture.js` - Test de Node.js
**Propósito:** Test unitario que valida toda la lógica de construcción de estructuras de datos

**Cómo ejecutar:**
```bash
node tests/Unit/TestGenerosConTallasCapture.js
```

**Qué valida:**
- ✅ Construcción correcta de `generosConTallas` desde `tallasPorGenero` y `cantidadesPorTalla`
- ✅ Derivación correcta de `cantidadTalla` para el API
- ✅ Construcción del array `tallas` para validación del backend
- ✅ Flujo completo end-to-end
- ✅ Casos edge cases (una sola talla, múltiples géneros, cantidades grandes)

**Salida esperada:**
```
╔════════════════════════════════════════════════════════════════╗
║   TEST SUITE: Captura de Información de Tallas y generosConTallas
╚════════════════════════════════════════════════════════════════╝

[Múltiples tests con resultados ✅ PASS]

📊 Tests ejecutados: 5
✅ Tests pasados: 21
❌ Tests fallados: 0

📈 Porcentaje de éxito: 100.00%
```

---

### 2. `browser-integration-test.js` - Test de Navegador
**Propósito:** Test de integración que simula el flujo real en el navegador

**Cómo ejecutar:**

#### Opción A: Copiar y pegar en la consola del navegador
1. Abre el formulario de crear pedido en: `http://localhost/asesores/pedidos-editable/crear`
2. Abre la consola del navegador (F12 → Console)
3. Copia el contenido de `tests/browser-integration-test.js`
4. Pega en la consola y presiona Enter

#### Opción B: Cargar como script en el HTML (para desarrollo)
```html
<script src="{{ asset('tests/browser-integration-test.js') }}"></script>
```

**Qué valida:**
- ✅ Simulación completa del flujo de usuario (seleccionar tallas, crear prenda)
- ✅ Construcción de `generosConTallas` en contexto real
- ✅ Generación correcta del payload para enviar al backend
- ✅ Verificación de que pasaría la validación del backend

**Salida esperada en la consola:**
```
🧪 INICIANDO TEST DE INTEGRACIÓN COMPLETO

1️⃣  SIMULANDO SELECCIÓN DE USUARIO EN FORMULARIO
✅ Usuario seleccionó: ...

2️⃣  CONSTRUYENDO generosConTallas
✅ generosConTallas construido:
   dama: { S: 230, M: 230, L: 230 }

3️⃣  CREANDO OBJETO PRENDA
✅ Prenda creada:
   ...

[Más validaciones...]

📊 RESUMEN FINAL DEL TEST
📈 Validaciones pasadas: 7/7
📦 Estructura de datos: ✅ VÁLIDA
🚀 Listo para enviar a backend: ✅ SÍ

🎉 ¡TODOS LOS TESTS PASARON!
```

Los datos de prueba se guardan en: `window._testData`

---

## 🔍 Estructura de Datos Validada

### Entrada del Usuario
```javascript
window.tallasPorGenero = [
    { genero: 'dama', tallas: ['S', 'M', 'L'], tipo: 'letra' }
];

window.cantidadesPorTalla = {
    'S': 230,
    'M': 230,
    'L': 230
};
```

### Paso 1: Construcción de `generosConTallas`
```javascript
generosConTallas = {
    dama: {
        S: 230,
        M: 230,
        L: 230
    }
}
```

### Paso 2: Derivación de `cantidadTalla`
```javascript
cantidadTalla = {
    'dama-S': 230,
    'dama-M': 230,
    'dama-L': 230
}
```

### Paso 3: Array `tallas` para el Backend
```javascript
tallas = [
    { genero: 'dama', talla: 'S', cantidad: 230 },
    { genero: 'dama', talla: 'M', cantidad: 230 },
    { genero: 'dama', talla: 'L', cantidad: 230 }
]
```

### Paso 4: Payload Final para API
```javascript
{
    items: [
        {
            nombre: "Polo corporativo",
            descripcion: "Polo gris corporativo",
            cantidad_total: 690,
            tallas: [
                { genero: 'dama', talla: 'S', cantidad: 230 },
                { genero: 'dama', talla: 'M', cantidad: 230 },
                { genero: 'dama', talla: 'L', cantidad: 230 }
            ]
        }
    ]
}
```

---

## ✅ Checklist de Validación

Este test valida que:

- [x] `generosConTallas` NO está vacío
- [x] `cantidadTalla` tiene todos los datos mapeados
- [x] Array `tallas` NO está vacío (requisito del backend)
- [x] Cada elemento del array tiene: genero, talla, cantidad
- [x] Cantidad total correcta (suma de todas las tallas)
- [x] Cada talla tiene cantidad > 0
- [x] Estructura es válida para enviar al API

---

## 🐛 Debugging

### Si los tests fallan:

1. **Verifica que `generosConTallas` no esté vacío:**
   ```javascript
   console.log(window._testData.generosConTallas);
   // Debe mostrar: {dama: {S: 230, M: 230, L: 230}}
   ```

2. **Verifica que `cantidadTalla` tenga datos:**
   ```javascript
   console.log(window._testData.cantidadTalla);
   // Debe mostrar: {'dama-S': 230, 'dama-M': 230, 'dama-L': 230}
   ```

3. **Verifica el array `tallas`:**
   ```javascript
   console.log(window._testData.tallasArray);
   // Debe ser un array con 3 elementos
   ```

4. **Verifica el payload completo:**
   ```javascript
   console.log(JSON.stringify(window._testData.payload, null, 2));
   // Debe mostrar estructura válida con tallas no vacío
   ```

---

## 🚀 Casos de Prueba Incluidos

### Test 1: Caso Simple (Una Talla)
```javascript
tallasPorGenero: [{ genero: 'dama', tallas: ['M'] }]
cantidadesPorTalla: { 'M': 500 }
// Resultado: { 'dama-M': 500 }
```

### Test 2: Caso Múltiple (Dos Géneros)
```javascript
tallasPorGenero: [
    { genero: 'dama', tallas: ['S', 'M'] },
    { genero: 'caballero', tallas: ['30', '32'] }
]
cantidadesPorTalla: { 'S': 100, 'M': 100, '30': 100, '32': 100 }
// Resultado: { 'dama-S': 100, 'dama-M': 100, 'caballero-30': 100, 'caballero-32': 100 }
```

### Test 3: Cantidades Grandes
```javascript
tallasPorGenero: [{ genero: 'dama', tallas: ['L'] }]
cantidadesPorTalla: { 'L': 99999 }
// Resultado: { 'dama-L': 99999 }
```

---

## 📊 Resultados Esperados

Cuando todos los tests pasan:

```
📈 Validaciones pasadas: 21/21
✅ Todos los tests pasaron
✅ La información se captura correctamente
✅ Listo para enviar al backend sin errores 422
```

---

## 🔗 Archivos Relacionados

- **Frontend:** `/public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js`
  - Líneas 790-823: Construcción de `generosConTallas`
  
- **Frontend:** `/public/js/invoice-preview-live.js`
  - Usa `window.gestorPrendaSinCotizacion` con datos probados
  
- **Backend:** `/app/Http/Controllers/Asesores/CrearPedidoEditableController.php`
  - Línea 144: Validación que requiere `tallas` array no vacío

---

## 🎯 Objetivo de los Tests

Asegurar que:
1. **Los datos se capturan correctamente** desde el formulario
2. **Las estructuras se transforman correctamente** entre formatos
3. **El backend recibe datos válidos** que pasan la validación
4. **No hay errores 422** por estructura de datos inválida
5. **El flujo completo funciona** de principio a fin
