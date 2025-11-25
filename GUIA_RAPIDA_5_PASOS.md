# 🎯 GUÍA RÁPIDA: 5 PASOS PARA MIGRAR

## PASO 1️⃣ : BACKUP (5 minutos)

```bash
# Abre terminal en la carpeta del proyecto
cd c:\Users\Usuario\Documents\proyecto\v10\mundoindustrial

# Haz backup
mysqldump -u root -p mundo_bd > backup_pre_migracion.sql

# Verifica que se creó
dir backup_pre_migracion.sql
```

✅ **Si ves el archivo creado, continúa**

---

## PASO 2️⃣ : DRY-RUN (10 minutos)

```bash
# Simula la migración sin cambiar nada
php artisan migrate:tabla-original-to-pedidos-produccion --dry-run
```

**Esperado:** Ver algo como esto:

```
╔════════════════════════════════════════════════════════╗
║  Migración: tabla_original → pedidos_produccion       ║
╚════════════════════════════════════════════════════════╝

📊 Analizando datos...

Total de órdenes en tabla_original: 45150
Total de registros en registros_por_orden: 156230

¿Deseas continuar con la migración? (yes/no) [no]:
 > no
```

(Escribe "no" porque es simulación)

```
Procesando... 45150/45150 [████████████] 100%

═══════════════════════════════════════════════════════
✅ Migración completada
═══════════════════════════════════════════════════════
Órdenes migradas: 45150
Errores: 0

⚠️  Modo DRY-RUN: No se realizaron cambios
```

✅ **Si ves "0 Errores", todo está OK**

---

## PASO 3️⃣ : MIGRACIÓN REAL (20 minutos)

```bash
# Ejecuta la migración real
php artisan migrate:tabla-original-to-pedidos-produccion
```

**Esperado:**

```
╔════════════════════════════════════════════════════════╗
║  Migración: tabla_original → pedidos_produccion       ║
╚════════════════════════════════════════════════════════╝

📊 Analizando datos...

Total de órdenes en tabla_original: 45150
Total de registros en registros_por_orden: 156230

¿Deseas continuar con la migración? (yes/no) [no]:
 > yes   ← Escribe "yes" ESTA VEZ
```

⏳ **Espera mientras se procesa (15-20 minutos)**

```
Procesando... 45150/45150 [████████████] 100%

═══════════════════════════════════════════════════════
✅ Migración completada
═══════════════════════════════════════════════════════
Órdenes migradas: 45150
Errores: 0

✅ Cambios confirmados en la base de datos
```

✅ **Si ves "Cambios confirmados", ¡ÉXITO!**

---

## PASO 4️⃣ : VALIDACIÓN (2 minutos)

```bash
# Valida que la migración fue correcta
php artisan validate:tabla-original-migration
```

**Esperado:**

```
╔═══════════════════════════════════════════════════════╗
║  Validación de Migración: tabla_original             ║
╚═══════════════════════════════════════════════════════╝

───────────────────────────────────────────────────────
📊 Conteo de registros
───────────────────────────────────────────────────────
Tabla original:          45150
Pedidos migrados:        45150
Prendas creadas:         156230
Procesos creados:        512340
✅ Cantidad de pedidos coincide
✅ Prendas fueron creadas
✅ Procesos fueron creados

───────────────────────────────────────────────────────
🔗 Integridad referencial
───────────────────────────────────────────────────────
✅ Todas las prendas tienen pedido válido
✅ Todos los procesos tienen prenda válida

───────────────────────────────────────────────────────
✓ Validación de datos
───────────────────────────────────────────────────────
✅ Todos los numero_pedido son únicos
✅ Todos los pedidos tienen cliente
✅ Todos los pedidos tienen estado
```

✅ **Si ves todos los checkmarks, ¡PERFECTO!**

---

## PASO 5️⃣ : VERIFICACIÓN EN LA APP (5 minutos)

### En el navegador:

1. **Ir a Asesores**
   ```
   http://localhost:8000/asesores/pedidos
   ```
   ✅ Debe mostrar los pedidos históricos migrados

2. **Verifica que muestre el ÁREA ACTUAL**
   - Columna "Área" debe mostrar (Corte, Costura, etc.)
   - Viene de `procesos_prenda` automáticamente

3. **Click en un pedido**
   - Debe mostrar detalles correctamente
   - Prendas deben estar listadas
   - Procesos deben estar en historial

4. **Ir a Dashboard**
   ```
   http://localhost:8000/dashboard
   ```
   ✅ Estadísticas deben mostrar números correctos

---

## 🎁 BONUS: Verificación en Tinker

```bash
# Abre la consola interactiva
php artisan tinker
```

```php
# Contar datos migrados
>>> PedidoProduccion::count()
=> 45150

>>> PrendaPedido::count()
=> 156230

>>> ProcesoPrenda::count()
=> 512340

# Ver un pedido específico
>>> $pedido = PedidoProduccion::first()
>>> $pedido->numero_pedido
=> 1

>>> $pedido->prendas->count()
=> 3

>>> $pedido->prendas->first()->procesos->count()
=> 4

# Salir
>>> exit
```

✅ **Si todo muestra datos, ¡MIGRACIÓN EXITOSA!**

---

## 📞 SI ALGO FALLA

### Opción 1: Restaurar desde backup
```bash
mysql -u root -p mundo_bd < backup_pre_migracion.sql
```

### Opción 2: Ver logs de error
```bash
tail -f storage/logs/laravel.log
```

### Opción 3: Ejecutar de nuevo
La migración es segura (verifica duplicados)
```bash
php artisan migrate:tabla-original-to-pedidos-produccion
```

---

## ✨ RESUMEN EJECUTIVO

| Paso | Comando | Tiempo | Resultado |
|------|---------|--------|-----------|
| 1 | `mysqldump` | 5 min | Backup creado ✅ |
| 2 | `--dry-run` | 10 min | Validación simulada ✅ |
| 3 | Migración real | 20 min | 45,150 pedidos migrados ✅ |
| 4 | Validación | 2 min | Integridad OK ✅ |
| 5 | Verificación | 5 min | App funciona ✅ |
| **TOTAL** | - | **42 min** | **¡HECHO!** ✅ |

---

## 🎯 SIGUIENTES PASOS (Después de Migración)

### A CORTO PLAZO:
1. [ ] Actualizar `AsesoresController`
2. [ ] Actualizar `DashboardController`
3. [ ] Comentar Observers de `TablaOriginal`

### A MEDIANO PLAZO:
4. [ ] Actualizar `VistasController`
5. [ ] Actualizar `RegistroOrdenController` (el grande)
6. [ ] Testing completo

### A LARGO PLAZO:
7. [ ] Hacer `tabla_original` read-only o eliminar
8. [ ] Migrar sistema de bodega igual
9. [ ] Optimizar queries con índices

---

**¿Listo para ejecutar?**

```bash
# Comienza aquí:
cd c:\Users\Usuario\Documents\proyecto\v10\mundoindustrial
php artisan migrate:tabla-original-to-pedidos-produccion --dry-run
```

🚀 **¡Adelante!**
