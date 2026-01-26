# 📋 Guía Completa: Actualización de Prendas sin Pérdida de Datos

## Flujo General

```
Frontend (envia datos parciales)
          ↓
ActualizarPrendaCompletaDTO (mapea datos)
          ↓
ActualizarPrendaCompletaUseCase::ejecutar() (MERGE pattern)
          ↓
PrendaTransformerService (transforma para respuesta)
          ↓
Frontend (recibe prenda completa con relaciones)
```

---

## 🔄 Patrón MERGE (No Sobrescribir)

### Variantes (Manga, Broche, Bolsillos)

**Si llega:**
```json
{
  "variantes": [{
    "tipo_manga_id": null,
    "tipo_broche_boton_id": 2,
    "manga_obs": "Nueva obs"
  }]
}
```

**Resultado:**
- `tipo_manga_id` = **PRESERVADO** (null se ignora)
- `tipo_broche_boton_id` = **ACTUALIZADO** a 2
- `manga_obs` = **ACTUALIZADO**
- Otros campos = **NO TOCADOS**

### Código Clave (líneas 228-241):
```php
$varianteExistente = $prenda->variantes()->first();
if ($varianteExistente) {
    foreach ($dto->variantes as $variante) {
        $upd = [];
        // SOLO actualizar si NO es null
        if (array_key_exists("tipo_manga_id", $variante) && $variante["tipo_manga_id"] !== null) 
            $upd["tipo_manga_id"] = $variante["tipo_manga_id"];
        if (!empty($upd)) $varianteExistente->update($upd);
    }
}
```

---

## 📸 Procesos (NUNCA se eliminan automáticamente)

**Comportamiento:**
- Si `dto->procesos` es `null` → NO TOCAR procesos existentes
- Si `dto->procesos` es array vacío `[]` → NO TOCAR (previamente eliminaba)
- Si `dto->procesos` es array con datos → CREAR NUEVOS (sin eliminar existentes)

**Código:**
```php
private function actualizarProcesos(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
{
    // PATTERN MERGE: No eliminar procesos automáticamente
    if (is_null($dto->procesos) || empty($dto->procesos)) {
        return;  // NO TOCAR
    }

    // Crear NUEVOS procesos si se envían (sin eliminar los existentes)
    foreach ($dto->procesos as $proceso) {
        $prenda->procesos()->create([...]);
    }
}
```

---

## 📦 Respuesta Siempre Completa

### Endpoint: `POST /asesores/pedidos/{id}/actualizar-prenda`

**Respuesta garantizada:**
```json
{
  "success": true,
  "prenda": {
    "id": 3477,
    "nombre_prenda": "CAMISA DRILL",
    "variantes": [
      {
        "id": 7440,
        "tipo_manga_id": 1,
        "tipo_manga_nombre": "Corta",
        "tipo_broche_boton_id": 2,
        "tipo_broche_boton_nombre": "Botón",
        "manga_obs": "RWEr",
        "broche_boton_obs": "WERw",
        "tiene_bolsillos": true,
        "bolsillos_obs": "Wer"
      }
    ],
    "tallas": {
      "DAMA": {"XS": 2, "S": 3},
      "CABALLERO": {}
    },
    "procesos": [
      {
        "id": 112,
        "tipo_proceso": "Reflectivo",
        "estado": "PENDIENTE",
        "ubicaciones": [],
        "observaciones": null,
        "imagenes": [],
        "tallas": {}
      }
    ],
    "fotos": [],
    "colores_telas": [],
    "fotos_telas": []
  }
}
```

### Garantía: `procesos` SIEMPRE es array (nunca undefined)

```php
// En ActualizarPrendaCompletaUseCase::ejecutar()
$prenda->refresh();

// Garantizar que procesos sea siempre un array
if (!$prenda->relationLoaded('procesos')) {
    $prenda->load('procesos');
}

return $prenda;  // ← procesos siempre cargado como Collection
```

---

## 🎨 Para Facturas/Resúmenes

### Usar `PrendaTransformerService`

```php
use App\Application\Pedidos\Services\PrendaTransformerService;

$transformer = new PrendaTransformerService();

// Opción 1: Prenda completa con relaciones
$prendaCompleta = $transformer->transformarPrendaCompleta($prenda);

// Opción 2: Prenda para factura (solo lo necesario)
$prendaFactura = $transformer->transformarPrendaParaFactura($prenda);
```

### Resultado para Factura:
```json
{
  "nombre": "CAMISA DRILL",
  "manga": "Corta",
  "broche_boton": "Botón",
  "tiene_bolsillos": true,
  "observaciones": [
    "Manga: RWEr",
    "Broche/Botón: WERw",
    "Bolsillos: Wer"
  ],
  "tallas": "DAMA: XS (2), S (3)",
  "colores_telas": ["Ytr - Rtyrtyrt"]
}
```

---

## 🛠️ Casos de Uso

### Caso 1: Actualizar solo observaciones
```json
{
  "variantes": [{
    "manga_obs": "Nueva observación",
    "broche_boton_obs": "Otra obs"
  }]
}
```
**Resultado:** Manga y broche se PRESERVAN, solo cambian las observaciones.

### Caso 2: Actualizar manga pero preservar broche
```json
{
  "variantes": [{
    "tipo_manga_id": 3,
    "tipo_broche_boton_id": null
  }]
}
```
**Resultado:** Manga = 3, broche = PRESERVADO.

### Caso 3: Sin enviar procesos
```json
{
  "nombre_prenda": "Nueva descripción"
}
```
**Resultado:** Procesos existentes INTACTOS, no se eliminan.

### Caso 4: Agregar nuevo proceso
```json
{
  "procesos": [{
    "tipo_proceso_id": 5,
    "observaciones": "Nuevo proceso"
  }]
}
```
**Resultado:** NUEVO proceso creado, procesos existentes PRESERVADOS.

---

## 🔐 Transacciones & Integridad

Aunque la versión actual es buena, para producción se recomienda:

```php
DB::transaction(function() {
    // Actualizar campos básicos
    $this->actualizarCamposBasicos($prenda, $dto);
    
    // Actualizar relaciones
    $this->actualizarVariantes($prenda, $dto);
    $this->actualizarTallas($prenda, $dto);
    $this->actualizarFotos($prenda, $dto);
    
    // Si algo falla, todo se revierte
});
```

---

## 📊 Flujo Completo de Actualización

```
1. Frontend: POST /asesores/pedidos/2765/actualizar-prenda
   ├─ Body: { variantes: [...], tallas: {...}, ... }
   
2. Controller: PedidosProduccionController::actualizarPrenda()
   ├─ Mapea datos → ActualizarPrendaCompletaDTO
   
3. UseCase: ActualizarPrendaCompletaUseCase::ejecutar()
   ├─ Valida prenda existe
   ├─ actualizarCamposBasicos() → MERGE
   ├─ actualizarVariantes() → MERGE (ignora null)
   ├─ actualizarTallas() → MERGE
   ├─ actualizarFotos() → MERGE
   ├─ actualizarColoresTelas() → MERGE
   ├─ actualizarFotosTelas() → MERGE
   ├─ actualizarProcesos() → NO ELIMINA
   └─ return $prenda->refresh() + load('procesos')
   
4. Transformer: PrendaTransformerService::transformarPrendaCompleta()
   ├─ Carga todas las relaciones
   ├─ Traduce IDs a nombres (manga → "Corta", etc.)
   ├─ Asegura procesos[] es array
   └─ Retorna array completo
   
5. Controller: Response JSON
   ├─ success: true
   ├─ prenda: { id, nombre, variantes[], tallas, procesos[], ... }
   └─ Status 200
   
6. Frontend: Recibe prenda completa
   ├─ Actualiza UI
   └─ prenda.procesos.map() FUNCIONA (siempre es array)
```

---

##  Uso en Controller

```php
use App\Application\Pedidos\Services\PrendaTransformerService;
use App\Application\Pedidos\UseCases\ActualizarPrendaCompletaUseCase;

public function actualizarPrenda(Request $request, int $id)
{
    $dto = ActualizarPrendaCompletaDTO::fromRequest($request, $id);
    
    $useCase = app(ActualizarPrendaCompletaUseCase::class);
    $prenda = $useCase->ejecutar($dto);
    
    // Transformar para respuesta
    $transformer = new PrendaTransformerService();
    $prendaTransformada = $transformer->transformarPrendaCompleta($prenda);
    
    return response()->json([
        'success' => true,
        'prenda' => $prendaTransformada,
        'message' => 'Prenda actualizada correctamente',
    ]);
}
```

---

##  Beneficios del Sistema

✅ **No pierde datos** - MERGE pattern preserva lo no enviado  
✅ **Null-safe** - Ignora valores null, no sobrescribe  
✅ **Procesos seguros** - Nunca se eliminan automáticamente  
✅ **Frontend robusto** - procesos siempre es array, no .map() errors  
✅ **Facturas correctas** - Traduce IDs a nombres legibles  
✅ **Escalable** - Fácil agregar nuevos campos sin perder datos  
✅ **Observable** - Logs en cada paso para debugging  

---

## 📝 Tabla Resumen: ¿Qué se Actualiza?

| Campo | Llega null | Llega vacío | Llega valor |
|-------|-----------|-----------|-----------|
| tipo_manga_id | Preserva | N/A | Actualiza |
| tipo_broche_boton_id | Preserva | N/A | Actualiza |
| manga_obs | Preserva | Actualiza a "" | Actualiza |
| variantes[] | No toca | Crea | Crea |
| procesos[] | No toca | No toca | Crea (sin borrar) |
| tallas | No toca | Borra todas | MERGE |
| fotos | No toca | Borra todas | MERGE |

---

**Última actualización:** 2026-01-25  
**Versión UseCase:** 3.0 (MERGE Pattern Final)
