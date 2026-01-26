# 🧪 TEST: Flujo Completo de Imágenes (26 Enero 2026)

## Objetivo
Validar que el flujo DOM → JSON + FormData → Backend funciona correctamente de punta a punta.

---

## PASO 1: Preparar Navegador

Abre el navegador en: `https://mundoindustrial.local/asesores/pedidos-editable`

Abre **Consola del Navegador** (F12 → Console):
```
Ctrl + Shift + I
```

---

## PASO 2: Crear Pedido CON IMÁGENES

### 2.1 Agregar Prenda
- Botón: `➕ Agregar Prenda`
- Selecciona tipo de prenda (ej: "Camiseta")

### 2.2 Agregar Tela CON IMAGEN
- Click: `Agregar Tela`
- Selecciona tela y color
- **🔴 IMPORTANTE**: Click en `📷 Imágenes` → Selecciona **al menos 1 imagen**
- Verifica que aparezca en preview

### 2.3 Agregar Proceso CON IMAGEN
- Click: `Agregar Reflectivo` (o proceso disponible)
- **🔴 IMPORTANTE**: Click en `📷 Imágenes` → Selecciona **al menos 1 imagen**
- Verifica que aparezca en preview

### 2.4 Enviar Pedido
- Click: `✅ Crear Pedido`
- **NO cierres la consola** - veremos los logs

---

## PASO 3: Verificar Logs en Navegador (Console)

Busca estos logs en ORDEN:

### ✅ ItemFormCollector: Generando UIDs
```
🔍 ItemFormCollector - Estructura pedidoFinal:
  Prenda 0:
    uid: 'uid-xxxxxxxx-xxxxxxxx'
    tipo: 'prenda_nueva'
    nombre: 'CAMISA'
```

### ✅ ItemFormCollector: Datos recolectados
```
📦 crearPedido] PASO 1: Extrayendo files...
[crearPedido] PASO 1 completo: {
  prendas: 1,
  archivos_totales: 2  ← Debe ser > 0 si hay imágenes
}
```

**Si `archivos_totales: 0` → Las imágenes NO se están seleccionando. Verifica PASO 2.2 y 2.3**

### ✅ PayloadNormalizer: Normalizando
```
[crearPedido] PASO 2: Normalizando...
[crearPedido] PASO 2 completo - Prendas: 1 - EPPs: 0
```

### ✅ FormDataBuilder: Agregando archivos
```
[crearPedido] PASO 3: Construyendo FormData...
[crearPedido] PASO 3 completo
```

### ✅ Enviando FormData
```
[crearPedido] PASO 4: Enviando POST a /crear
```

---

## PASO 4: Verificar Logs en Backend (Laravel)

Abre: `storage/logs/laravel.log` o cola el tail:

```bash
tail -f storage/logs/laravel.log
```

### ✅ FormData recibido CON archivos
```
[CrearPedidoEditableController] Archivos en FormData {
  "archivos": [
    {"key": "files_prenda_0_0", "name": "imagen.jpg", "size": 45678},
    {"key": "files_tela_0_0_0", "name": "tela.jpg", "size": 87654}
  ]
}
```

**Si `"archivos": []` → FormData está vacío. El problema es en el frontend.**

### ✅ ResolutorImagenesService: Procesando
```
[ResolutorImagenesService] Iniciando extracción de imágenes {
  "pedido_id": 2728,
  "prendas_count": 1,
  "archivos_en_request": 2  ← Debe ser > 0
}
```

### ✅ Imágenes guardadas
```
[ResolutorImagenesService] Imagen procesada {
  "imagen_uid": "uid-xxxxx",
  "archivo_nombre": "imagen.jpg",
  "ruta_webp": "storage/pedidos/2728/telas/imagen.webp"
}
```

### ✅ Mapeo de imágenes
```
[MapeoImagenesService] Mapeo UID→Ruta completado {
  "imagenes_mapeadas": 2
}
```

### ✅ Pedido creado exitosamente
```
[CrearPedidoEditableController] TRANSACCIÓN EXITOSA {
  "pedido_id": 2728,
  "numero_pedido": 100009,
  "cantidad_total": XX
}
```

---

## 🔍 TROUBLESHOOTING

### Problema 1: Console.log show `archivos_totales: 0`

**Causa**: Las imágenes no se están seleccionando en el modal

**Solución**:
1. En PASO 2.2: Click explícito en la sección `📷 Imágenes`
2. Selecciona archivo (drag & drop o click input)
3. Verifica que aparezca en preview ANTES de enviar

### Problema 2: Backend recibe `"archivos": []`

**Causa**: FormDataBuilder.buildFormData() no está agregando archivos

**Solución**:
1. Verifica que `filesExtraidos` no está vacío en console
2. Abre DevTools → Network → POST /crear → Preview → Form Data
3. Debe mostrar keys como `files_prenda_0_0`, `files_tela_0_0_0`

### Problema 3: Imágenes no se guardan en storage/

**Causa**: ImageUploadService.guardarImagenDirecta() está fallando

**Solución**:
1. Verifica permisos: `chmod -R 755 storage/`
2. Verifica que ImageUploadService tiene conversión a WEBP
3. Revisa logs de Laravel para errores específicos

### Problema 4: Base64 en JSON (como antes)

**Causa**: PayloadNormalizer no está limpiando Files

**Solución**:
1. Verifica que `limpiarFiles()` se llama correctamente
2. Comprueba que normalizarItem() retorna `imagenes: []`

---

## 📋 Checklist de Validación

- [ ] Console muestra UIDs generados en ItemFormCollector
- [ ] Console muestra `archivos_totales: X` (X > 0)
- [ ] Backend recibe FormData con archivos
- [ ] Backend crea registros en BD (prendas_pedido, prendas_pedido_colores_telas)
- [ ] Archivos guardados en `storage/app/public/pedidos/{id}/{tipo}/`
- [ ] Conversión a WEBP completada
- [ ] BD tiene registros en prenda_foto_pedido, prenda_foto_tela_pedido, etc.
- [ ] NO hay duplicados de imagenes

---

## 🚀 Resultado Esperado

**Cuando TODO funciona:**

1. Navegador → Console muestra logs detallados sin errores
2. Backend → Archivos en FormData procesados
3. Storage → Imágenes en WEBP guardadas
4. BD → Registros creados correctamente
5. Sin duplicados → Una copia por imagen

**Reporte de Usuario**: "Imágenes se suben, se convierten a WEBP y se guardan correctamente"
