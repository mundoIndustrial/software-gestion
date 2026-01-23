# 🗑️ PLAN DE ELIMINACIÓN DE ARCHIVOS LEGACY

**Objetivo:** Eliminar archivos innecesarios poco a poco de forma segura.

---

## ✅ ARCHIVOS SEGUROS PARA ELIMINAR (Sin referencias activas)

### Nivel 1: SIN REFERENCIAS EN RUTAS (SEGURO)

```
❌ app/Http/Controllers/Asesores/PedidoLogoAreaController.php
   Razón: No tiene rutas activas
   Referencias en código: 0
   Riesgo: BAJO
   Estado: LISTO PARA ELIMINAR

❌ app/Modules/Pedidos/Infrastructure/Http/Controllers/PedidoEppController.php
   Razón: No está en rutas, posible duplicado
   Referencias en código: 0
   Riesgo: BAJO
   Estado: VERIFICAR PRIMERO
```

### Nivel 2: VERIFICAR ANTES DE ELIMINAR

```
⚠️ app/Services/Pedidos/ (carpeta completa)
   Razón: Posibles servicios legacy duplicados
   Referencias: Verificar si se usan
   Riesgo: MEDIO
   Estado: ANALIZAR
```

---

## 📋 PLAN PASO A PASO

### PASO 1: Eliminar PedidoLogoAreaController.php
```bash
1. Verificar que no hay rutas
2. Verificar que no hay imports en otros archivos
3. Eliminar archivo
4. Commit: "Chore: Eliminar PedidoLogoAreaController (sin referencias)"
```

### PASO 2: Verificar PedidoEppController.php
```bash
1. Revisar contenido
2. Verificar si PedidoEppService lo usa
3. Decidir si eliminar o mantener
```

### PASO 3: Limpiar servicios legacy
```bash
1. Buscar servicios duplicados
2. Verificar uso
3. Eliminar si no se usan
```

---

## 🔍 VERIFICACIONES ANTES DE ELIMINAR

Cada archivo que eliminemos debe pasar:

- [ ] **Búsqueda en rutas:** No hay referencias en `routes/`
- [ ] **Búsqueda en código:** No hay imports en `.php` files
- [ ] **Búsqueda en vistas:** No hay referencias en `.blade.php`
- [ ] **Búsqueda en JavaScript:** No hay referencias en `.js`

---

## EMPEZAR CON SEGURIDAD

**Primera eliminación:** `PedidoLogoAreaController.php`

Razones:
1. ✅ No está en rutas
2. ✅ No hay imports
3. ✅ Bajo riesgo
4. ✅ Despeja código

---

**¿Empezamos a eliminar?**
