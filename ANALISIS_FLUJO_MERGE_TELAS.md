# Análisis: MERGE Pattern para Telas en Edición

## 🔍 Flujo Completo: Editar Prenda con Telas

### Escenario: 
Prenda existente en BD tiene:
- Tela 1: Color "ROJO", Tela "DRILL", ID=5
- Referencia: "REF-001"

Usuario edita prenda y:
1. **Agrega una tela nueva** (sin ID)
2. **Modifica la existente** (con ID)
3. **O elimina la existente** (no la envía)

---

## 📤 FRONTEND: Cómo se Envía

### En `modal-novedad-edicion.js` línea 181-213:

```javascript
// FLUJO EDICIÓN: usar window.telasEdicion
if (window.telasEdicion && window.telasEdicion.length > 0) {
    const telasArray = window.telasEdicion.map((tela, idx) => {
        const obj = {
            nombre: tela.nombre || '',
            color: tela.color || '',
        };
        
        // ✅ SI tiene ID = existente (MERGE pattern)
        if (tela.id) {
            obj.id = tela.id;  // ID = 5 (tela existente)
        }
        
        // Procesar imágenes
        if (tela.imagenes && tela.imagenes.length > 0) {
            obj.imagenes = [];
            tela.imagenes.forEach((img, imgIdx) => {
                if (img instanceof File) {
                    // Imagen nueva
                    formData.append(`telas[${idx}][imagenes][${imgIdx}]`, img);
                } else if (img.urlDesdeDB || img.url) {
                    // Imagen existente
                    obj.imagenes.push({ url: img.url || img.urlDesdeDB });
                }
            });
        }
        
        return obj;
    });
    
    // Enviar JSON
    formData.append('colores_telas', JSON.stringify(telasArray));
}
```

### Ejemplo de Payload Enviado:

**Caso 1: Usuario modifica tela existente + agrega nueva**
```json
{
    "colores_telas": [
        {
            "id": 5,
            "nombre": "ROJO",
            "color": "ROJO",
            "imagenes": [{"url": "/storage/..."}]  // Preservada
        },
        {
            "nombre": "AZUL",
            "color": "AZUL"
            // SIN id = nueva tela
        }
    ]
}
```

**Caso 2: Usuario NO envía la tela existente (la "elimina")**
```json
{
    "colores_telas": [
        {
            "nombre": "AZUL",
            "color": "AZUL"
            // NO viene la tela con id: 5
        }
    ]
}
```

---

## 🔙 BACKEND: Cómo se Procesa

### 1. Controller recibe y valida (PedidosProduccionController.php línea 809)

```php
$validated = $request->validate([
    'colores_telas' => 'nullable|json',  // ← Viene como JSON string
    ...
]);
```

### 2. DTO parsea (ActualizarPrendaCompletaDTO.php línea 83-88)

```php
$coloresTelas = null;
if (isset($data['colores_telas'])) {
    $coloresTelas = json_decode($data['colores_telas'], true);
    // Ahora es array: [{'id': 5, 'nombre': '...', 'color': '...'}, {'nombre': 'AZUL', ...}]
}
```

### 3. UseCase procesa MERGE (ActualizarPrendaCompletaUseCase.php línea 65)

```php
$this->actualizarColoresTelas($prenda, $dto);
```

### 4. La función actualiza (línea 267-310)

```php
private function actualizarColoresTelas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
{
    if (is_null($dto->coloresTelas)) {
        return;  // ← Si no viene, NO tocar (prenda conserva sus telas)
    }

    if (empty($dto->coloresTelas)) {
        // Array vacío = intención explícita de eliminar TODO
        $prenda->coloresTelas()->delete();
        return;
    }

    // ✅ MERGE LOGIC
    $coloresTelaExistentes = $prenda->coloresTelas()->get()->keyBy(function($ct) {
        return "{$ct->color_id}_{$ct->tela_id}";
    });

    foreach ($dto->coloresTelas as $colorTela) {
        $colorId = $colorTela['color_id'] ?? null;
        $telaId = $colorTela['tela_id'] ?? null;
        
        // Si vienen nombres (no IDs), buscar o crear
        if (isset($colorTela['color_nombre']) && !$colorId) {
            $colorId = $this->obtenerOCrearColor($colorTela['color_nombre']);
        }
        if (isset($colorTela['tela_nombre']) && !$telaId) {
            $telaId = $this->obtenerOCrearTela($colorTela['tela_nombre']);
        }
        
        // Guardar si tenemos ambos IDs
        if ($colorId && $telaId) {
            $key = "{$colorId}_{$telaId}";
            $coloresTelaNovas[$key] = [
                'color_id' => $colorId,
                'tela_id' => $telaId,
            ];
        }
    }
}
```

---

## ⚠️ PROBLEMA IDENTIFICADO

### El Backend NO Recibe `tela_id` ni `color_id`

**Frontend envía:**
```json
{
    "colores_telas": [
        {
            "id": 5,
            "nombre": "ROJO",
            "color": "ROJO"
        }
    ]
}
```

**Backend espera:**
```php
$colorTela['color_id']  // ← NO EXISTE
$colorTela['tela_id']   // ← NO EXISTE
$colorTela['color_nombre']  // ← SÍ EXISTE
```

### Resultado:
✗ Backend busca por `color_id` y `tela_id` pero recibe `nombre` y `color`
✗ No puede hacer UPDATE de relaciones existentes
✗ Crea relaciones nuevas inútiles

---

## 🔧 Solución: Cambiar Frontend

### Opción A: Enviar IDs desde Frontend (Recomendado)

Cuando carga telas existentes, guardar los IDs:

**Antes (Actual - INCORRECTO):**
```javascript
const telaObj = {
    nombre: prenda.nombre_tela,
    color: prenda.color,
    tela: prenda.tela,
    referencia: prenda.ref
};
```

**Después (CORRECTO):**
```javascript
const telaObj = {
    id: prenda.prenda_pedido_colores_telas_id,  // ← ID de la relación
    color_id: prenda.color_id,                  // ← ID del color
    tela_id: prenda.tela_id,                    // ← ID de la tela
    nombre: prenda.nombre_tela,
    color: prenda.color,
    tela: prenda.tela,
    referencia: prenda.ref
};
```

Luego en envío:
```javascript
// Convertir a nombres si faltan IDs
const obj = {
    id: tela.id,  // Para MERGE
    color_id: tela.color_id,  // Para búsqueda
    tela_id: tela.tela_id,    // Para búsqueda
    color_nombre: tela.color,  // Fallback si no hay IDs
    tela_nombre: tela.tela
};
```

---

## 📋 Casos de Uso Después del Fix

### Caso 1: User Modifica Tela Existente
```
BD: id=5, color_id=1, tela_id=3 → {rojo, drill}
User: cambia a {azul, poliéster}

Payload:
{
    "id": 5,
    "color_id": 1,
    "tela_id": 3,
    "color_nombre": "AZUL",
    "tela_nombre": "POLIÉSTER"
}

Backend:
- Busca relación id=5
- Encuentra: color_id=1, tela_id=3
- Busca si existe AZUL → Si no, crea
- Busca si existe POLIÉSTER → Si no, crea
- UPDATE: color_id=2, tela_id=4 (nuevos IDs)
```

### Caso 2: User Agrega Tela Nueva
```
Payload:
{
    "color_nombre": "VERDE",
    "tela_nombre": "LINO"
}

Backend:
- SIN id = crear nuevo
- Busca si existe VERDE → Si no, crea
- Busca si existe LINO → Si no, crea
- CREATE: nueva relación con color_id=3, tela_id=5
```

### Caso 3: User No Envía Tela (Implícita Eliminación)
```
Frontend tenía: [tela con id=5, tela sin id (nueva)]
User decide eliminar la existente y solo guarda la nueva

Payload:
{
    "colores_telas": [
        {
            "color_nombre": "AZUL",
            "tela_nombre": "POLIÉSTER"
        }
    ]
}

Backend:
- CREATE: nueva tela AZUL/POLIÉSTER
- Tela id=5 NO aparece en payload
- Pero: ⚠️ NO se elimina (MERGE conserva)
- Resultado: 2 telas en prenda
```

---

## 🎯 Acciones Requeridas

### 1. Frontend: modal-novedad-edicion.js
- [ ] Capturar `color_id` y `tela_id` de telas existentes
- [ ] Enviar en payload junto con `color_nombre` y `tela_nombre`

### 2. Frontend: tela-processor.js
- [ ] Al cargar desde BD, guardar IDs
- [ ] Estructura completa: `{id, color_id, tela_id, color, tela, ...}`

### 3. Backend: ActualizarPrendaCompletaUseCase.php
- [ ] Verificar que parsea correctamente `color_id` y `tela_id`
- [ ] Si NO vienen IDs, usar `color_nombre` y `tela_nombre` (fallback)

### 4. Testing
- [ ] Caso 1: Editar tela existente
- [ ] Caso 2: Agregar tela nueva
- [ ] Caso 3: Eliminar tela existente (no enviarla)
- [ ] Caso 4: Mezcla de los anteriores

---

## 🚀 Resumen Respuesta Original

**Pregunta:** Si edito prenda con tela en BD, ¿agrego nueva o elimino la existente genera problema?

**Respuesta:** 
- ✅ **Agregar nueva**: Funciona (CREATE logic)
- ⚠️ **Editar existente**: PROBLEMA - Backend no recibe IDs
- ⚠️ **Eliminar existente**: PROBLEMA - No se elimina (MERGE conserva)

**Root Cause:** Frontend envía `color`/`tela` (nombres) pero backend espera `color_id`/`tela_id` (IDs)

**Fix:** Enviar IDs desde frontend junto con nombres (como fallback)
