# FLUJO DE CARGA DE IMÁGENES PARA EDICIÓN DE PRENDAS

## Situación General
El usuario quiere que cuando se abra el modal para editar una prenda existente, **se carguen TODAS las imágenes y datos guardados en la BD**.

## Arquitectura Implementada

### 1. BACKEND - Carga Inicial de Datos (`/datos-edicion`)

**Endpoint:** `GET /asesores/pedidos-produccion/{id}/datos-edicion`

**Código:** `PedidosProduccionViewController.php::obtenerDatosEdicion()`

**Flujo:**
```
GET /datos-edicion
    ↓
ObtenerDatosFacturaService::obtener()
    ↓
PedidoProduccionRepository::obtenerDatosRecibos()
    ↓
Consulta `prenda_fotos_pedido` tabla
    ↓
Retorna cada prenda con:
  - imagenes: ['/storage/ruta1.webp', '/storage/ruta2.webp', ...]
  - telasAgregadas: [{tela, color, referencia, imagenes: [...]}]
  - generosConTallas: {dama: {tallas: [...], cantidades: {...}}}
    ↓
Response JSON
```

**Estructura Retornada:**
```json
{
  "success": true,
  "datos": {
    "numero_pedido": "2700",
    "prendas": [
      {
        "id": 123,
        "prenda_pedido_id": 123,
        "nombre_prenda": "Camiseta Deportiva",
        "imagenes": ["/storage/prendas/foto1.webp", "/storage/prendas/foto2.webp"],
        "telasAgregadas": [
          {
            "tela": "Poliester 100%",
            "color": "Rojo",
            "referencia": "POL-001",
            "imagenes": ["/storage/telas/tela1.webp"]
          }
        ],
        "generosConTallas": {
          "dama": {
            "tallas": ["S", "M", "L"],
            "cantidades": {"S": 10, "M": 20, "L": 15}
          }
        }
      }
    ]
  }
}
```

### 2. FRONTEND - Almacenamiento en Global

**Ubicación:** `window.datosEdicionPedido`

**Código:** `prenda-editor-modal.js::abrirEditarPrendas()`

**Flujo:**
```
fetch('/datos-edicion')
    ↓
window.datosEdicionPedido = response.datos
    ↓
window.prendasEdicion = {
  pedidoId: ...,
  prendas: response.datos.prendas  // ← Aquí están las imágenes
}
```

### 3. FRONTEND - Abrir Lista de Prendas

**Función:** `abrirEditarPrendas()`

**Flujo:**
```
Abre SweetAlert con lista de prendas
    ↓
Para cada prenda en window.datosEdicionPedido.prendas:
  - Muestra nombre, descripción
  - Prepara botón onclick="abrirEditarPrendaEspecifica(idx)"
```

### 4. FRONTEND - Abrir Modal de Edición

**Función:** `abrirEditarPrendaEspecifica(prendasIndex)`

**Flujo:**
```
const prenda = window.prendasEdicion.prendas[prendasIndex]
    ↓
Prepara prendaParaEditar:
  - nombre_prenda: prenda.nombre_prenda
  - imagenes: prenda.imagenes  // ← AQUÍ TRAE LAS IMÁGENES
  - telasAgregadas: prenda.telasAgregadas
  - generosConTallas: prenda.generosConTallas
    ↓
window.prendaEnEdicion = {
  pedidoId: ...,
  prendasIndex: ...,
  prendaOriginal: prenda
}
    ↓
window.gestionItemsUI.cargarItemEnModal(prendaParaEditar, prendasIndex)
```

### 5. FRONTEND - Cargar en PrendaEditor

**Clase:** `PrendaEditor` (`prenda-editor.js`)

**Método:** `cargarPrendaEnModal(prenda, prendaIndex)`

**Flujo:**
```
PrendaEditor.cargarPrendaEnModal(prenda)
    ├─ abrirModal(true, prendaIndex)
    ├─ llenarCamposBasicos(prenda)
    ├─ cargarImagenes(prenda)  // ← CARGA IMÁGENES EN window.imagenesPrendaStorage
    ├─ cargarTelas(prenda)     // ← CARGA TELAS EN window.telasAgregadas
    ├─ cargarTallasYCantidades(prenda)
    ├─ cargarVariaciones(prenda)
    ├─ cargarProcesos(prenda)
    └─ cambiarBotonAGuardarCambios()
```

### 6. FRONTEND - Procesar Imágenes

**Método:** `cargarImagenes(prenda)` → `procesarImagen(img)`

**Flujo:**
```
Para cada imagen en prenda.imagenes:
  ├─ Si es File object → imagenesPrendaStorage.agregarImagen(file)
  ├─ Si es string URL (ej: "/storage/...") →
  │   imagenesPrendaStorage.images.push({
  │     previewUrl: img,
  │     nombre: "imagen_X.webp",
  │     file: null,
  │     urlDesdeDB: true
  │   })
  └─ Actualiza preview del modal
```

**Resultado:**
```javascript
window.imagenesPrendaStorage.images = [
  {
    previewUrl: "/storage/prendas/foto1.webp",
    nombre: "imagen_0.webp",
    tamaño: 0,
    file: null,
    urlDesdeDB: true
  },
  {
    previewUrl: "/storage/prendas/foto2.webp",
    nombre: "imagen_1.webp",
    tamaño: 0,
    file: null,
    urlDesdeDB: true
  }
]
```

### 7. MODAL ABIERTO - Visualización

**Ubicación:** `#modal-agregar-prenda-nueva`

**Elementos:**
- Preview: `#nueva-prenda-foto-preview` → `background-image: url('/storage/prendas/foto1.webp')`
- Contador: `#nueva-prenda-foto-contador` → Muestra cantidad de imágenes
- Tabla de telas: `#tbody-telas` → Muestra telas de `window.telasAgregadas`
- Campos: Nombre, descripción, tallas se precargan con valores existentes

### 8. USUARIO EDITA Y GUARDA

**Flujo:**
```
Usuario modifica prenda en modal
    ↓
Clickea "Guardar Cambios"
    ↓
Se abre modal de novedad
    ↓
Usuario ingresa novedad y clickea "Guardar"
    ↓
POST /asesores/pedidos/{id}/actualizar-prenda
```

### 9. BACKEND - Actualizar Prenda

**Código:** `PedidosProduccionController.php::actualizarPrendaCompleta()`

**Flujo:**
```
Valida datos
    ↓
Actualiza prendas_pedido tabla
    ↓
Procesa imágenes nuevas si existen
    ↓
Consulta prenda_fotos_pedido para obtener imágenes actuales
    ↓
Retorna:
{
  "success": true,
  "prenda": {
    "id": 123,
    "nombre_prenda": "...",
    "imagenes": ["/storage/prendas/foto1.webp", ...],
    "telasAgregadas": []
  }
}
```

### 10. FRONTEND - Recarga Automática

**Código:** `modal-novedad-edicion.js::actualizarPrendaConNovedad()`

**Flujo:**
```
Recibe respuesta de actualización
    ↓
Detecta que hay window.prendaEnEdicion
    ↓
Recarga automáticamente GET /datos-edicion
    ↓
Obtiene datos COMPLETOS y frescos
    ↓
window.datosEdicionPedido = datos nuevos
    ↓
window.prendasEdicion.prendas = datos nuevos
    ↓
Muestra modal de éxito
    ↓
Usuario hace click en "Ver lista de prendas"
    ↓
abrirEditarPrendas() abre con DATOS ACTUALIZADOS
```

## Puntos Críticos Implementados

1.  **BD → Backend:** `prenda_fotos_pedido` se consulta y formatea con rutas `/storage/`
2.  **Backend → Frontend:** `/datos-edicion` retorna `imagenes` como array de strings
3.  **Frontend Storage:** `window.imagenesPrendaStorage` se llena con imágenes desde BD
4.  **Modal Preview:** Se actualiza con la primera imagen del array
5.  **Actualización:** Después de guardar, se recarga `/datos-edicion` automáticamente
6.  **Sincronización:** `window.datosEdicionPedido` siempre tiene datos frescos

## Debugging

Para verificar que funciona:

1. Abre la consola del navegador (F12)
2. Edita un pedido existente
3. Busca logs que muestren:
   ```
   [PrendaEditor.cargarImagenes] Procesando X imágenes...
   [PrendaEditor.cargarImagenes] Procesando imagen [0]: /storage/...
   [PrendaEditor.actualizarPreviewImagenes] Configurando preview con URL: /storage/...
   ```

4. Si no aparecen imágenes:
   - Verifica `window.datosEdicionPedido.prendas[0].imagenes` en consola
   - Verifica `window.imagenesPrendaStorage.images` 
   - Verifica que las rutas en BD están correctas

## Resumen

**Flujo Completo:**
```
BD (prenda_fotos_pedido)
    ↓ [/datos-edicion]
Backend (formatea rutas)
    ↓ [JSON]
Frontend (window.datosEdicionPedido)
    ↓ [abrirEditarPrendaEspecifica]
PrendaEditor (cargarImagenes)
    ↓ [window.imagenesPrendaStorage]
Modal (preview y tabla)
    ↓ [usuario edita]
Backend (actualiza y retorna)
    ↓ [recarga automática /datos-edicion]
Frontend (sincroniza datos)
    ↓ [datos frescos listos]
```

**Total: 10 pasos garantizados para que las imágenes se carguen correctamente**
