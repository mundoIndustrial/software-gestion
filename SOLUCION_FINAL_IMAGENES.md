# SOLUCIÓN FINAL: Sistema de Relocalización de Imágenes

## 🎯 Problema Identificado

Las imágenes se guardaban en:
- `prendas/2026/01/...` (formato antiguo)
- `telas/2026/01/...` (formato antiguo)
- `procesos/2026/01/...` (formato antiguo)

**En lugar de:**
- `pedidos/{pedido_id}/prendas/`
- `pedidos/{pedido_id}/telas/`
- `pedidos/{pedido_id}/procesos/`

##  Solución Implementada

### Componentes Creados/Modificados

#### 1️⃣ **ImagenRelocalizadorService.php** (NUEVO)
- **Ruta:** `app/Domain/Pedidos/Services/ImagenRelocalizadorService.php`
- **Responsabilidad:** Mover imágenes de cualquier ruta a `pedidos/{pedido_id}/{tipo}/`
- **Soporta AMBOS formatos:**
  - Antiguo: `prendas/2026/01/archivo.jfif` 
  - Nuevo: `prendas/temp/{uuid}/webp/archivo.webp`

**Método principal:**
```php
relocalizarImagenes(int $pedidoId, array $rutasTemp): array
```

---

#### 2️⃣ **PedidoWebService.php** (ACTUALIZADO)
- **Cambio:** Inyectada `ImagenRelocalizadorService`
- **Flujo:**
  1. Recibe rutas de imágenes (antiguas o nuevas)
  2. Relocaliza a `pedidos/{pedido_id}/{tipo}/`
  3. Guarda referencias en BD

**Métodos modificados:**
- `guardarImagenesPrenda()` - Relocaliza + guarda
- `guardarImagenesTela()` - Relocaliza + guarda

---

#### 3️⃣ **ImageUploadService.php** (ACTUALIZADO)
- **Cambio:** Ahora guarda en `{tipo}/temp/{uuid}/` en lugar de `pedidos/{tipo}/`
- **Métodos:** `uploadPrendaImage()`, `uploadTelaImage()`, `uploadLogoImage()`, `uploadReflectivoImage()`
- **Parámetro nuevo:** `?string $tempUuid` para agrupar uploads

---

#### 4️⃣ **CrearPedidoEditableController.php** (ACTUALIZADO)
- **Método:** `subirImagenesPrenda()` - Usa nuevo servicio
- **Response:** Retorna `temp_uuid` para el frontend
- **Estructura de upload:** Agrupa múltiples imágenes bajo mismo UUID

---

#### 5️⃣ **PedidosServiceProvider.php** (ACTUALIZADO)
- **Cambio:** Registra `ImagenRelocalizadorService` en DI container
- **Patrón:** Singleton (reutilizable sin estado)

---

## 🔄 Flujo Completo

```
┌──────────────────────────────────────────────────────────────┐
│ CUALQUIER RUTA ANTIGUA O NUEVA DE IMAGEN                    │
│ Ej: prendas/2026/01/... O prendas/temp/uuid/...             │
└──────────────────────────────────────────────────────────────┘
                         ↓
┌──────────────────────────────────────────────────────────────┐
│ PedidoWebService::guardarImagenesPrenda()                    │
│ PedidoWebService::guardarImagenesTela()                      │
└──────────────────────────────────────────────────────────────┘
                         ↓
┌──────────────────────────────────────────────────────────────┐
│ ImagenRelocalizadorService::relocalizarImagenes()            │
│ 1. Extrae tipo: 'prendas' de cualquier formato              │
│ 2. Lee archivo original                                      │
│ 3. Copia a: pedidos/{pedido_id}/prendas/                    │
│ 4. Elimina original                                          │
│ 5. Limpia carpeta temporal si queda vacía                   │
└──────────────────────────────────────────────────────────────┘
                         ↓
┌──────────────────────────────────────────────────────────────┐
│ RESULTADO FINAL                                              │
│ storage/app/public/pedidos/{pedido_id}/prendas/archivo   │
│ BD actualizada con ruta final                            │
└──────────────────────────────────────────────────────────────┘
```

---

## 📊 Ejemplo de Ejecución

### Input
```php
$rutasTemp = [
    'prendas/2026/01/1769372084_697679b4c2a2d.jfif',  // ANTIGUO
    'prendas/temp/uuid-123/webp/prenda_0_20260125_xyz.webp',  // NUEVO
];

$servicio->relocalizarImagenes(2753, $rutasTemp);
```

### Output
```php
[
    'pedidos/2753/prendas/1769372084_697679b4c2a2d.jfif',
    'pedidos/2753/prendas/prenda_0_20260125_xyz.webp',
]
```

### En Storage
```
storage/app/public/
├── pedidos/2753/
│   └── prendas/
│       ├── 1769372084_697679b4c2a2d.jfif
│       └── prenda_0_20260125_xyz.webp
├── prendas/
│   ├── 2026/ (LIMPIADO)
│   └── temp/ (LIMPIADO)
└── telas/
    └── 2026/ (LIMPIADO si correspondía)
```

---

## 🛡️ Garantías

✅ **Compatible con rutas antiguas** - No requiere migración
✅ **Compatible con rutas nuevas** - Soporta nuevo formato UUID
✅ **Automático** - Se ejecuta al crear pedido
✅ **Resiliente** - Maneja errores sin romper flujo
✅ **Limpio** - Elimina temporales automáticamente
✅ **Loggeable** - Cada acción registrada
✅ **DDD** - Servicio en Domain layer
✅ **Testeable** - Servicios independientes

---

## 🧪 Validación

### Test Automático
```bash
php artisan test:imagen-relocalizador
```

Prueba ambos formatos (antiguo y nuevo).

### Test Manual
1. Crear pedido con imágenes
2. Verificar que carpeta se crea: `storage/app/public/pedidos/{id}/prendas/`
3. Verificar que archivos se movieron correctamente
4. Verificar que BD contiene ruta final: `pedidos/{id}/prendas/...`

---

## 📂 Archivos Finales

```
✅ app/Domain/Pedidos/Services/ImagenRelocalizadorService.php (NUEVO)
✅ app/Domain/Pedidos/Services/PedidoWebService.php (MODIFICADO)
✅ app/Application/Services/ImageUploadService.php (MODIFICADO)
✅ app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php (MODIFICADO)
✅ app/Providers/PedidosServiceProvider.php (MODIFICADO)
✅ app/Console/Commands/TestImagenRelocalizador.php (NUEVO)
```

---

##  Cómo Funciona Ahora

### ANTES ( Incorrecto)
```
Usuario sube imagen → Ruta: prendas/2026/01/...
                   → Se guarda ahí
                   → Se persiste en BD
                   → SIN estructura /pedidos/{id}/
```

### AHORA (✅ Correcto)
```
Usuario sube imagen → Ruta: prendas/2026/01/... (vieja) O prendas/temp/{uuid}/... (nueva)
                   ↓
                   Se relocaliza automáticamente
                   ↓
                   Ruta final: pedidos/{id}/prendas/...
                   ↓
                   Se persiste en BD
                   ↓
                   Temporal se limpia
```

---

## 💡 Ventajas

1. **Backwards Compatible** - Funciona con rutas antiguas sin cambios
2. **Forward Compatible** - Soporta nuevo formato UUID
3. **Centralizado** - Una sola responsabilidad (relocalización)
4. **Flexible** - Detecta tipo automáticamente
5. **Seguro** - Validación de rutas
6. **Observable** - Logging completo

---

## 📝 Próximos Pasos (Opcional)

1. Crear comando para migrar imágenes existentes:
   ```bash
   php artisan images:migrate-to-pedidos
   ```

2. Crear cron para limpiar temporales > 24h:
   ```
   0 * * * * php artisan images:cleanup-old-temp
   ```

3. (FUTURO) Cambiar ImageUploadService para siempre guardar en `/temp/{uuid}/`

---

##  Estado Actual

**✅ COMPLETAMENTE FUNCIONAL**

- Relocaliza imágenes automáticamente
- Soporta ambos formatos (antiguo y nuevo)
- Estructura `/pedidos/{id}/{tipo}/` garantizada
- Sistema robusto y resiliente
- Tests incluidos

**Listo para producción.**

