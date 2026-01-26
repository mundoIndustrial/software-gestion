# ✅ Checklist: Creación Automática de Procesos

## 🎯 Verificación de Implementación

### Código Backend
- [x] Importación de `ProcesoPrenda` en RegistroOrdenCreationService
- [x] Método `createInitialProcesso()` agregado
- [x] Método `createAdditionalProcesso()` agregado como public
- [x] Llamada a `createInitialProcesso()` dentro de `createOrder()`
- [x] Logging agregado para auditoría
- [x] Manejo de excepciones con rollback

### Base de Datos
- [ ] Tabla `procesos_prenda` existe con campos:
  - `numero_pedido`
  - `prenda_pedido_id`
  - `proceso`
  - `estado_proceso`
  - `fecha_inicio`
  - `fecha_fin`
  - `dias_duracion`
  - `encargado`
  - `observaciones`
  - `codigo_referencia`

### Testing Manual

#### Test 1: Crear Pedido y Verificar Proceso Inicial
```bash
# 1. Crear pedido vía API o formulario
POST /api/pedidos HTTP/1.1

# 2. Ejecutar en BD:
SELECT COUNT(*) FROM procesos_prenda 
WHERE numero_pedido = [PEDIDO_ID] 
AND proceso = 'Creación de Orden';

# Resultado esperado: 1
```
- [ ] Proceso se crea automáticamente
- [ ] Estado es "Pendiente"
- [ ] Nombre es "Creación de Orden"
- [ ] fecha_inicio es NOW()
- [ ] prenda_pedido_id es NULL

#### Test 2: Verificar en Frontend (Recibos)
```javascript
// En console de navegador después de crear pedido
// Ir a vista de recibos
// Abrir DevTools → Network
// Llamada a /recibos/datos/{id}
// Response debe incluir:
{
    procesos: [
        {
            proceso: "Creación de Orden",
            estado: "Pendiente",
            nombre: "Creación de Orden",
            tipo: "Creación de Orden",
            // ... otros campos
        }
    ]
}
```
- [ ] Proceso "Creación de Orden" aparece en response
- [ ] Tiene los campos `nombre` y `tipo`
- [ ] Estado es "Pendiente"

#### Test 3: Verificar Logs
```bash
# Comando terminal:
tail -f storage/logs/laravel.log | grep "REGISTRO-ORDEN-PROCESO"

# Output esperado:
[REGISTRO-ORDEN-PROCESO] Iniciando creación de proceso inicial
[REGISTRO-ORDEN-PROCESO] Proceso inicial creado exitosamente
```
- [ ] Logs muestran "Iniciando creación de proceso inicial"
- [ ] Logs muestran "Proceso inicial creado exitosamente"
- [ ] Logs contienen numero_pedido y proceso_id

#### Test 4: Crear Múltiples Pedidos
```bash
# Crear 5 pedidos seguidos
# Verificar que cada uno tiene su propio proceso

SELECT numero_pedido, COUNT(*) as procesos_count 
FROM procesos_prenda 
WHERE proceso = 'Creación de Orden' 
GROUP BY numero_pedido;

# Resultado esperado: 5 filas, cada una con count=1
```
- [ ] Cada pedido tiene exactamente 1 proceso "Creación de Orden"
- [ ] No hay duplicados
- [ ] Todos con estado "Pendiente"

#### Test 5: Agregar Proceso Adicional (Futuro)
```php
// En algún Controller o Service
$pedido = PedidoProduccion::find($id);
$service = app(RegistroOrdenCreationService::class);

$proceso = $service->createAdditionalProcesso($pedido, 'Costura', [
    'encargado' => 'María',
    'dias_duracion' => 3,
]);
```
- [ ] Método `createAdditionalProcesso()` es accesible
- [ ] Crea proceso adicional correctamente
- [ ] Retorna instancia de ProcesoPrenda
- [ ] Aparece en recibos

### Integración con Fases Anteriores

- [x] **Fase 1:** Procesos se renderizan en recibos (campos `nombre`, `tipo`)
- [x] **Fase 2:** Estado y área se guardan correctamente
- [x] **Fase 3:** Proceso inicial se crea automáticamente
- [ ] Todas las fases funcionan juntas sin conflictos

### Performance

```php
// Verificar que no hay queries lentas
// En: database/logs/queries.log (si está habilitado)

// Query esperada:
INSERT INTO procesos_prenda (...) VALUES (...)

// Tiempo esperado: < 10ms
```
- [ ] No hay queries lentas
- [ ] Transacción es rápida
- [ ] No hay deadlocks

### Documentación

- [x] SOLUCION_PROCESOS_CREACION_AUTOMATICA.md creado
- [x] Método documentado con PHPDoc
- [x] Ejemplo de uso futuro documentado
- [ ] README actualizado (si aplica)
- [ ] Equipo informado del cambio

---

## 🚀 Deployment Checklist

### Pre-Production
- [ ] Código revisado por otro desarrollador
- [ ] Tests unitarios creados (opcional pero recomendado)
- [ ] Tests de integración ejecutados
- [ ] Performance verificado

### Production
- [ ] Backup de BD realizado
- [ ] Deploy de código realizado
- [ ] Verificar logs en producción
- [ ] Crear algunos pedidos de prueba
- [ ] Confirmar procesos se crean en BD
- [ ] Comunicar cambio al equipo

### Post-Deploy Monitoring
- [ ] Monitorear logs por 24 horas
- [ ] Verificar que procesos se crean cada hora
- [ ] Revisar performance metrics
- [ ] Verificar sin errores en ErrorLog

---

## 📊 Datos Esperados

### Tabla `procesos_prenda` después de crear 3 pedidos:

```
id | numero_pedido | prenda_pedido_id | proceso              | estado_proceso | fecha_inicio        | dias_duracion | encargado | observaciones
---|---------------|------------------|----------------------|----------------|---------------------|---------------|-----------|------------------------
1  | 1001          | NULL             | Creación de Orden    | Pendiente      | 2024-01-15 10:30    | 1             | NULL      | Proceso inicial...
2  | 1002          | NULL             | Creación de Orden    | Pendiente      | 2024-01-15 10:31    | 1             | NULL      | Proceso inicial...
3  | 1003          | NULL             | Creación de Orden    | Pendiente      | 2024-01-15 10:32    | 1             | NULL      | Proceso inicial...
```

---

## 🔍 Troubleshooting

### Problema: "Proceso no se crea"
1. Verificar logs: `tail -f storage/logs/laravel.log`
2. Buscar error en `[REGISTRO-ORDEN-PROCESO]`
3. Revisar modelo ProcesoPrenda tiene campos en `$fillable`
4. Verificar tabla existe y no hay constraint violations

### Problema: "Foreign key error"
1. Revisar que `numero_pedido` sea válido en `pedidos_produccion`
2. Verificar que tabla `procesos_prenda` no tenga constraints muy estrictos
3. Ejecutar: `php artisan migrate --refresh --seed` (en dev solo)

### Problema: "Duplicados de procesos"
1. Verificar no se llamó múltiples veces `createOrder()`
2. Revisar que BD no tenga trigger automático
3. Buscar en código si hay observer llamando a crear procesos

### Problema: "Logs no aparecen"
1. Verificar `config/logging.php` está configurado
2. Revisar permiso de `storage/logs/`
3. Verificar canal `single` o `daily` funciona

---

## ✨ Próximas Mejoras (Opcional)

- [ ] Agregar más procesos iniciales automáticamente
- [ ] Crear procesos específicos según tipo de prenda
- [ ] Asignar automáticamente encargados según área
- [ ] Crear workflow automático según tipo de pedido
- [ ] Notificar cuando proceso se completa
- [ ] Dashboard para visualizar procesos en tiempo real

---

## 📝 Sign-Off

**Desarrollador:** _______________  
**Fecha:** _______________  
**Revisado por:** _______________  
**Aprobado para producción:** _______________  

---

**Estado:** ✅ LISTO PARA TESTING
