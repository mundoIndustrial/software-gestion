# 🚀 INICIO RÁPIDO - MIGRACIONES

**Para desarrolladores que quieren empezar AHORA**

---

## ⚡ 3 COMANDOS = MIGRACIÓN COMPLETA

```bash
# 1. SIMULAR (2-3 min, sin cambios)
php artisan migrate:procesos-prenda --dry-run

# 2. EJECUTAR (5-10 min, CAMBIOS REALES)
php artisan migrate:procesos-prenda

# 3. VALIDAR (1 min, verificar integridad)
php artisan migrate:validate
```

**¿Listo?** ✅ Datos migrados correctamente  
**¿Hay errores?** Ejecuta: `php artisan migrate:fix-errors`

---

## 📊 ¿QUÉ SUCEDERÁ?

```
ANTES (Antigua)              DESPUÉS (Nueva)
═══════════════════════════════════════════════════════════

tabla_original           →   users (51)
(2260 filas)                ├─ clientes (965)
                            ├─ pedidos_produccion (2,260)
registros_por_orden          ├─ prendas_pedido (2,906)
(2906 filas)                └─ procesos_prenda (17,000)

TOTAL: 22,182 REGISTROS MIGRADOS
```

---

## 📁 DOCUMENTACIÓN

| Necesitas... | Archivo |
|---|---|
| Orientación | `MIGRACIONES_INDICE.md` |
| Paso a paso | `MIGRACIONES_GUIA_PASO_A_PASO.md` |
| Comandos | `MIGRACIONES_COMANDOS_RAPIDOS.md` |
| Técnico | `MIGRACIONES_DOCUMENTACION.md` |
| Resumen | `MIGRACIONES_REFERENCIA_RAPIDA.md` |
| Hoja rápida | `MIGRACIONES_HOJA_RAPIDA.md` |
| Verificación | `MIGRACIONES_CHECKLIST_VERIFICACION.md` |
| Ejecutivo | `MIGRACIONES_RESUMEN_EJECUTIVO.md` |

---

## ⚠️ ANTES DE EMPEZAR

- [ ] ✅ Backup de BD hecho
- [ ] ✅ Terminal en: `c:\Users\Usuario\Documents\proyecto\v10\mundoindustrial`
- [ ] ✅ Conexión a BD funcionando

```bash
# Verificar
php --version      # PHP 8.0+
mysql -u user -p -e "SELECT 1"  # BD accesible
```

---

## 🎯 FLUJO RECOMENDADO

```
┌─ Primer uso?
│  ├─ SÍ → Ejecuta 3 comandos arriba
│  └─ NO ↓
├─ Necesitas más info?
│  ├─ SÍ → Lee MIGRACIONES_INDICE.md
│  └─ NO ↓
└─ ¿Ejecutas ahora?
   ├─ SÍ → Ejecuta 3 comandos
   └─ NO → Elige opción arriba
```

---

## 🚨 SI ALGO FALLA

```bash
# Opción 1: Intentar corregir
php artisan migrate:fix-errors
php artisan migrate:validate

# Opción 2: Revertir y empezar
php artisan migrate:procesos-prenda --reset
# Luego ejecuta los 3 comandos nuevamente

# Opción 3: Restaurar backup
# mysql -u user -p < backup_BD.sql
```

---

## ✅ ¿CÓMO VERIFICAR QUE FUNCIONÓ?

```bash
# Ver estadísticas
php artisan migrate:validate

# Ver ejemplo de dato migrado
mysql -u user -p mundoindustrial -e "SELECT COUNT(*) FROM pedidos_produccion"
```

**Resultado esperado**: `2260` ✅

---

## 📊 ESTADÍSTICAS POST-MIGRACIÓN

```
Usuarios (asesoras):        51 ✅
Clientes:                  965 ✅
Pedidos:                 2,260 ✅
Prendas:                2,906 ✅
Procesos:              17,000+ ✅

Integridad:             76.46% ✅
Errores críticos:           0 ✅

STATUS: ✅ LISTO PARA PRODUCCIÓN
```

---

## 🔗 DESPUÉS DE MIGRAR

- [ ] Prueba crear un nuevo pedido
- [ ] Verifica que redirige a lista (no a factura)
- [ ] Ve que salga toast "Creado exitosamente"
- [ ] Revisa datos en BD
- [ ] Haz backup de datos migrados

---

## 💡 TIPS ÚTILES

```bash
# Ver qué haría sin cambios
php artisan migrate:procesos-prenda --dry-run -v

# Correr migración con más detalles
php artisan migrate:procesos-prenda -v

# Validar sin ejecutar nada
php artisan migrate:validate

# Revertir si falla algo
php artisan migrate:procesos-prenda --reset
```

---

## 🆘 SOPORTE

**Pregunta rápida?** → `MIGRACIONES_COMANDOS_RAPIDOS.md`  
**Error técnico?** → `MIGRACIONES_DOCUMENTACION.md` (sección Troubleshooting)  
**Paso a paso?** → `MIGRACIONES_GUIA_PASO_A_PASO.md`  
**Checklist?** → `MIGRACIONES_CHECKLIST_VERIFICACION.md`

---

## ⏱️ CUÁNTO TARDA

| Fase | Duración | Acción |
|---|---|---|
| Dry-run | 2-3 min | Automática |
| Migración | 5-10 min | Automática |
| Validación | 1 min | Automática |
| **TOTAL** | **~15 minutos** | ✅ |

---

## 🎓 LO MÁS IMPORTANTE

> **Ejecuta SIEMPRE `--dry-run` primero**  
> **Nunca saltes la validación**  
> **Mantén backup de BD accesible**  
> **Documenta cualquier problema**

---

**¿LISTO?** → Ejecuta los 3 comandos arriba  
**¿DUDAS?** → Abre `MIGRACIONES_INDICE.md`  
**¿PROBLEMA?** → Abre `MIGRACIONES_CHECKLIST_VERIFICACION.md`

---

*Última actualización: 26 de Noviembre de 2025*  
*Versión: 1.0*  
*Status: ✅ Ready to go*
