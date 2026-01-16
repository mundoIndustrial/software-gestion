# 🚀 SCRIPTS DE ANÁLISIS - GUÍA RÁPIDA

## 📦 Scripts Disponibles

```
analizar_datos_prendas.php         ← VER qué datos están guardados
debug_flujo_prendas.php            ← ANÁLISIS COMPLETO del flujo
validar_integridad_prendas.php     ← VALIDAR campos y relaciones FK
monitorear_requests_frontend.php   ← ESTRUCTURA esperada
capturar_requests.php              ← INSTRUCCIONES para debugging avanzado
```

---

## 🎯 FLUJO RECOMENDADO DE DEBUGGING

### 1️⃣ Verificar que se guardó (2 minutos)
```bash
php analizar_datos_prendas.php 50001
```
**Responde:** ¿Qué datos se guardaron en la BD?

---

### 2️⃣ Debug completo (3 minutos)
```bash
php debug_flujo_prendas.php 50001
```
**Responde:** ¿Qué está mal exactamente?

---

### 3️⃣ Validar integridad (2 minutos)
```bash
php validar_integridad_prendas.php 50001
```
**Responde:** ¿Qué campos están faltando?

---

### 4️⃣ Si nada del anterior ayuda, capturar requests (15 minutos)
```bash
php capturar_requests.php
```
**Pasos:**
1. Agrega logging al controlador (copiar-pegar el código)
2. Crea un pedido de prueba
3. Ve los logs
4. Compara con estructura esperada

---

## 🔍 QUICK REFERENCE

| Script | Para | Comando | Tiempo |
|--------|------|---------|--------|
| `analizar_datos_prendas.php` | Ver datos guardados | `php analizar_datos_prendas.php 50001` | 2 min |
| `debug_flujo_prendas.php` | Debug completo | `php debug_flujo_prendas.php 50001` | 3 min |
| `validar_integridad_prendas.php` | Validar campos | `php validar_integridad_prendas.php 50001` | 2 min |
| `monitorear_requests_frontend.php` | Ver estructura esperada | `php monitorear_requests_frontend.php 10` | 1 min |
| `capturar_requests.php` | Instrucc. avanzadas | `php capturar_requests.php` | 15 min |

---

## 📊 TABLA DE SÍNTOMAS Y SOLUCIONES

| Síntoma | Causa Probable | Solución |
|---------|----------------|----------|
| Campos vacíos (talla, cantidad) | Frontend no envía datos | Ver `capturar_requests.php` |
| color_id = 0 o NULL | Usuario no selecciona color | Validar formulario frontend |
| tipo_broche_boton_id = 0 | Campo renombrado mal sincronizado | Revisar `gestion-items-pedido.js` |
| Datos incompletos | Problema en `recolectarDatosPedido()` | Ejecutar `debug_flujo_prendas.php` |
| Todo está vacío | Prenda no se crea | Revisar controlador |

---

## 🛠️ COMANDOS DIRECTOS EN TERMINAL

```bash
# Ver últimos logs de prendas
tail -100 storage/logs/laravel.log | grep -i 'prenda'

# Ver errores recientes
grep -i 'error\|exception' storage/logs/laravel.log | tail -20

# Monitorear logs en tiempo real
tail -f storage/logs/laravel.log

# Buscar requests del backend
grep -i 'REQUEST RECIBIDO' storage/logs/laravel.log

# Ver específicamente una prenda
grep -i 'GUARDANDO PRENDAS' storage/logs/laravel.log

# Consultar BD directamente
mysql -u root -p mundoindustrial -e "SELECT * FROM prenda_pedido_variantes WHERE id > 1 LIMIT 5;"
```

---

## 🎓 CONCEPTOS CLAVE

### Flujo de Datos
```
Frontend (formulario)
    ↓
JavaScript (gestion-items-pedido.js)
    ↓
API (POST /pedidos-produccion/crear-sin-cotizacion)
    ↓
Controlador (PedidosProduccionViewController)
    ↓
Servicio (PedidoPrendaService)
    ↓
Modelo (PrendaPedido, PrendaVariante)
    ↓
Base de Datos (prenda_pedido_variantes)
```

### Tablas Involucradas
```
pedidos_produccion
    ↓ (1:N)
prendas_pedido
    ↓ (1:N)
prenda_pedido_variantes
    ↓ (N:1)
colores, telas, tipos_manga, tipos_broche_boton
```

### Campos Críticos en prenda_pedido_variantes
```
✅ OBLIGATORIOS:
- talla (varchar)
- cantidad (int)
- color_id (int > 0)
- tela_id (int > 0)
- tipo_manga_id (int > 0)
- tipo_broche_boton_id (int > 0)

❌ OPCIONALES:
- manga_obs (longtext)
- broche_boton_obs (longtext)
- tiene_bolsillos (tinyint)
- bolsillos_obs (longtext)
```

---

## 📞 TROUBLESHOOTING

### ¿No funciona ningún script?
1. Verifica que estés en el directorio raíz: `ls artisan`
2. Verifica permisos: `chmod +x *.php`
3. Verifica que el pedido exista: `php debug_flujo_prendas.php 50001`

### ¿El log está muy grande?
```bash
# Vaciar logs
echo "" > storage/logs/laravel.log

# Luego crear un nuevo pedido
# Y revisar logs limpios
```

### ¿No ves cambios aunque modifiques código?
```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Luego vuelve a intentar
```

---

## 📝 NOTAS IMPORTANTES

1. **Números de pedido:** Usa el número real que ves en la interfaz (ej: 50001)
2. **Bases de datos:** Los scripts asumen que tienes `.env` configurado
3. **Logs:** Revisa `storage/logs/laravel.log` para debugging avanzado
4. **Timestamps:** Los scripts usan timezone del sistema

---

**Última actualización:** 16 de Enero de 2026

Creado para debugging del flujo de prendas en pedidos de producción.
