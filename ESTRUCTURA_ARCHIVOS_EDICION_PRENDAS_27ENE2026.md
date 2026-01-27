# 📁 ESTRUCTURA DE ARCHIVOS: EDICIÓN SEGURA DE PRENDAS

**Fecha:** 27 de enero de 2026  
**Propósito:** Referencia rápida de ubicación de archivos

---

## 📂 Árbol de Directorios

```
app/
├── DTOs/
│   └── Edit/                                          [✅ NUEVO]
│       ├── EditPrendaPedidoDTO.php                   [✅ NUEVO]
│       └── EditPrendaVariantePedidoDTO.php           [✅ NUEVO]
│
├── Infrastructure/
│   ├── Http/
│   │   └── Controllers/
│   │       └── API/
│   │           └── PrendaPedidoEditController.php    [✅ NUEVO]
│   │
│   └── Services/
│       ├── Edit/                                     [✅ NUEVO]
│       │   ├── PrendaPedidoEditService.php           [✅ NUEVO]
│       │   └── PrendaVariantePedidoEditService.php   [✅ NUEVO]
│       │
│       ├── Strategies/                               [✅ NUEVO]
│       │   └── MergeRelationshipStrategy.php         [✅ NUEVO]
│       │
│       └── Validators/                               [✅ NUEVO]
│           └── PrendaEditSecurityValidator.php       [✅ NUEVO]
│
└── Models/
    ├── PrendaPedido.php                              [⏳ EXISTENTE]
    └── PrendaVariantePed.php                         [⏳ EXISTENTE]

routes/
└── web.php                                           [✏️ MODIFICADO]
    └── Líneas 592-638: Rutas de edición

docs/
└── (no generados)

ARQUITECTURA_EDICION_SEGURA_PRENDAS_27ENE2026.md      [✅ NUEVO]
GUIA_RAPIDA_EDICION_PRENDAS_27ENE2026.md              [✅ NUEVO]
RESUMEN_IMPLEMENTACION_EDICION_PRENDAS_27ENE2026.md   [✅ NUEVO]
```

---

## 📋 LISTA COMPLETA DE ARCHIVOS

### DTOs

| Archivo | Líneas | Responsabilidad |
|---------|--------|-----------------|
| `app/DTOs/Edit/EditPrendaPedidoDTO.php` | ~180 | DTO para edición de prenda |
| `app/DTOs/Edit/EditPrendaVariantePedidoDTO.php` | ~160 | DTO para edición de variante |

### Services

| Archivo | Líneas | Responsabilidad |
|---------|--------|-----------------|
| `app/Infrastructure/Services/Edit/PrendaPedidoEditService.php` | ~250 | Lógica edición de prenda |
| `app/Infrastructure/Services/Edit/PrendaVariantePedidoEditService.php` | ~200 | Lógica edición variante |

### Strategies

| Archivo | Líneas | Responsabilidad |
|---------|--------|-----------------|
| `app/Infrastructure/Services/Strategies/MergeRelationshipStrategy.php` | ~140 | MERGE de relaciones |

### Validators

| Archivo | Líneas | Responsabilidad |
|---------|--------|-----------------|
| `app/Infrastructure/Services/Validators/PrendaEditSecurityValidator.php` | ~130 | Validación restricciones |

### Controllers

| Archivo | Líneas | Responsabilidad |
|---------|--------|-----------------|
| `app/Infrastructure/Http/Controllers/API/PrendaPedidoEditController.php` | ~300 | Endpoints PATCH/GET |

### Routes

| Archivo | Líneas | Responsabilidad |
|---------|--------|-----------------|
| `routes/web.php` | 592-638 | 10 rutas de edición |

### Documentación

| Archivo | Tamaño | Contenido |
|---------|--------|----------|
| `ARQUITECTURA_EDICION_SEGURA_PRENDAS_27ENE2026.md` | ~600 líneas | Arquitectura completa |
| `GUIA_RAPIDA_EDICION_PRENDAS_27ENE2026.md` | ~500 líneas | Ejemplos prácticos |
| `RESUMEN_IMPLEMENTACION_EDICION_PRENDAS_27ENE2026.md` | ~300 líneas | Resumen implementación |

---

## 🔍 CÓMO NAVEGAR

### Entender la Arquitectura
1. Leer: `ARQUITECTURA_EDICION_SEGURA_PRENDAS_27ENE2026.md`
2. Revisar: Diagramas y flujos en ese documento

### Usar Rápidamente
1. Consultar: `GUIA_RAPIDA_EDICION_PRENDAS_27ENE2026.md`
2. Copiar ejemplos de esa guía

### Entender Implementación
1. Revisar: `RESUMEN_IMPLEMENTACION_EDICION_PRENDAS_27ENE2026.md`
2. Ubicar archivos en este documento

### Implementar en Código
1. **Frontend:** Revisar ejemplos en GUIA_RAPIDA (sección "Inicio Rápido")
2. **Backend:** Revisar Services en carpeta `app/Infrastructure/Services/Edit/`
3. **Rutas:** Ver `routes/web.php` líneas 592-638

---

## 💾 IMPORTANCIA DE CADA ARCHIVO

### Críticos (SRP Core)
- `EditPrendaPedidoDTO.php` - Entrada de datos separada
- `PrendaPedidoEditService.php` - Lógica central
- `MergeRelationshipStrategy.php` - MERGE sin borrado
- `PrendaEditSecurityValidator.php` - Restricciones negocio

### Importantes (Complementarios)
- `PrendaPedidoEditController.php` - Endpoints HTTP
- `routes/web.php` - Rutas registradas
- `EditPrendaVariantePedidoDTO.php` - Variantes
- `PrendaVariantePedidoEditService.php` - Servicio variantes

### Documentación
- Arquitectura: referencia completa
- Guía Rápida: ejemplos prácticos
- Resumen: overview ejecutivo

---

## 🚀 INSTALACIÓN / ACTIVACIÓN

### 1. Verificar archivos están en lugar

```bash
# Ejecutar desde workspace root
ls -la app/DTOs/Edit/
ls -la app/Infrastructure/Services/Edit/
ls -la app/Infrastructure/Services/Strategies/
ls -la app/Infrastructure/Services/Validators/
ls -la app/Infrastructure/Http/Controllers/API/
```

### 2. Verificar rutas registradas

```bash
# Ejecutar desde workspace root
php artisan route:list | grep prendas-pedido
```

Deberías ver:
```
PATCH   /api/prendas-pedido/{id}/editar
PATCH   /api/prendas-pedido/{id}/editar/campos
PATCH   /api/prendas-pedido/{id}/editar/tallas
...
```

### 3. Verificar servicios inyectable

```php
// En cualquier controlador
public function __construct(PrendaPedidoEditService $service) {
    // Si no hay error, está bien configurado
}
```

---

## 🔗 DEPENDENCIAS ENTRE ARCHIVOS

```
EditPrendaPedidoDTO.php
    ↓
    ├→ PrendaPedidoEditService.php
    │   ├→ MergeRelationshipStrategy.php
    │   ├→ PrendaEditSecurityValidator.php
    │   └→ (DB Transactions)
    │
    ├→ PrendaEditSecurityValidator.php
    │   └→ ProcesoPrenda.php (Modelo)
    │
    └→ PrendaPedidoEditController.php
        └→ routes/web.php

EditPrendaVariantePedidoDTO.php
    ↓
    ├→ PrendaVariantePedidoEditService.php
    │   └→ MergeRelationshipStrategy.php
    │
    └→ PrendaPedidoEditController.php
```

---

## 📊 ESTADÍSTICAS DE ARCHIVOS

| Tipo | Cantidad | Total Líneas |
|------|----------|-------------|
| DTOs | 2 | ~340 |
| Services | 2 | ~450 |
| Strategies | 1 | ~140 |
| Validators | 1 | ~130 |
| Controllers | 1 | ~300 |
| Documentación | 3 | ~1400 |
| **TOTAL** | **10** | **~2760** |

---

## ⚙️ CONFIGURACIÓN REQUERIDA

### Middleware
✅ Ya configurado en `routes/web.php`:
- `auth` - Usuario autenticado
- `role:asesor,admin` - Rol específico

### Inyección de Dependencias
✅ Laravel service container lo maneja automáticamente:
```php
public function __construct(
    PrendaPedidoEditService $service,
    PrendaVariantePedidoEditService $varianteService
)
```

### Database
✅ Requiere tablas existentes:
- `prendas_pedido`
- `prenda_pedido_tallas`
- `prenda_pedido_variantes`
- `prenda_pedido_colores_telas`

---

## 🧪 TESTING

### Archivos a Crear (Fase 2)

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── PrendaPedidoEditServiceTest.php
│   │   └── PrendaVariantePedidoEditServiceTest.php
│   ├── Strategies/
│   │   └── MergeRelationshipStrategyTest.php
│   └── Validators/
│       └── PrendaEditSecurityValidatorTest.php
│
├── Feature/
│   └── Http/
│       └── Controllers/
│           └── API/
│               └── PrendaPedidoEditControllerTest.php
│
└── Fixtures/
    └── prendas_pedido.json
```

---

## 🔐 BACKUPS RECOMENDADOS

Antes de activar en producción:

```bash
# Backup BD
mysqldump mundoindustrial > backup_pre_edicion_27ENE2026.sql

# Backup código
git commit -m "Pre-edicion-segura-prendas"

# Backup rutas
cp routes/web.php routes/web.php.backup
```

---

## 📝 VERSIONADO

```
v1.0.0 - 27/01/2026
├─ DTOs separados
├─ Strategy MERGE
├─ Validator restricciones
├─ Services edición
├─ Controller + Rutas
└─ Documentación

v1.1.0 - (Próxima)
├─ Tests automatizados
├─ Frontend integration
└─ Auditoría/Logging

v2.0.0 - (Futuro)
├─ Event sourcing
├─ Rate limiting
├─ Webhooks
└─ Optimizaciones
```

---

## 🚨 CHECKLIST PRE-PRODUCCIÓN

- [ ] Todos los archivos en lugar correcto
- [ ] Rutas registradas (verificar con `php artisan route:list`)
- [ ] Tests locales pasando
- [ ] Documentación leída y entendida
- [ ] BD backup realizado
- [ ] Code review completado
- [ ] Performance testado
- [ ] Error handling probado
- [ ] Rollback plan documentado
- [ ] Team capacity para mantenimiento

---

## 🔄 ACTUALIZACIÓN DE ARCHIVOS

### Si necesitas actualizar DTOs:
```
app/DTOs/Edit/EditPrendaPedidoDTO.php
app/DTOs/Edit/EditPrendaVariantePedidoDTO.php
→ Solo cambiar campos, no lógica de getExplicitFields()
```

### Si necesitas agregar restricción:
```
app/Infrastructure/Services/Validators/PrendaEditSecurityValidator.php
→ Agregar validateXXX() method
```

### Si necesitas nuevo tipo de merge:
```
app/Infrastructure/Services/Strategies/MergeRelationshipStrategy.php
→ Agregar mergeXXX() method
```

---

**Fin de Referencia de Estructura**

Última actualización: 27 de Enero de 2026
