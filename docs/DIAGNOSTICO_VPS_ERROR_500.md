# Guía de Diagnóstico - Error 500 en VPS Producción

## 🔍 Pasos para Diagnosticar el Error en tu VPS

### 1. **Conectar a tu VPS via SSH**
```bash
ssh usuario@tu-vps-ip
```

### 2. **Revisar Logs de Laravel (Método Principal)**

#### Logs de Aplicación:
```bash
# Ver logs en tiempo real
tail -f /ruta/a/tu/proyecto/storage/logs/laravel.log

# Buscar errores específicos de cartera
grep -n "\[CARTERA\]" /ruta/a/tu/proyecto/storage/logs/laravel.log | tail -20

# Buscar errores 500 recientes
grep -n "500\|Error\|Exception" /ruta/a/tu/proyecto/storage/logs/laravel.log | tail -20

# Ver últimos 100 errores
tail -100 /ruta/a/tu/proyecto/storage/logs/laravel.log | grep -i error
```

#### Logs de Nginx/Apache:
```bash
# Si usas Nginx
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# Si usas Apache
tail -f /var/log/apache2/error.log
tail -f /var/log/apache2/access.log

# Buscar errores 500
grep " 500 " /var/log/nginx/access.log | tail -10
```

### 3. **Revisar Logs de PHP-FPM**
```bash
# Logs de PHP-FPM
tail -f /var/log/php5-fpm.log
# o
tail -f /var/log/php7.4-fpm.log
# o
tail -f /var/log/php8.1-fpm.log
```

### 4. **Verificar Configuración del Servidor**

#### Permisos de Archivos:
```bash
# Verificar permisos del proyecto
ls -la /ruta/a/tu/proyecto/storage/logs/
ls -la /ruta/a/tu/proyecto/storage/framework/

# Corregir permisos si es necesario
sudo chown -R www-data:www-data /ruta/a/tu/proyecto/storage
sudo chmod -R 775 /ruta/a/tu/proyecto/storage
```

#### Espacio en Disco:
```bash
# Verificar espacio disponible
df -h

# Verificar espacio en el directorio del proyecto
du -sh /ruta/a/tu/proyecto/storage/logs/
```

### 5. **Probar la API Directamente desde VPS**
```bash
# Probar el endpoint con curl
curl -X POST "https://sistemamundoindustrial.online/api/cartera/pedidos/1/aprobar" \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -H "X-CSRF-TOKEN: tu-token" \
     -d '{}' \
     -v

# Ver respuesta detallada
curl -X POST "https://sistemamundoindustrial.online/api/cartera/pedidos/1/aprobar" \
     -H "Content-Type: application/json" \
     -w "\nHTTP Code: %{http_code}\nTime: %{time_total}s\n"
```

### 6. **Verificar Servicios Activos**
```bash
# Verificar Nginx
sudo systemctl status nginx

# Verificar PHP-FPM
sudo systemctl status php7.4-fpm  # o tu versión

# Verificar MySQL/MariaDB
sudo systemctl status mysql

# Reiniciar servicios si es necesario
sudo systemctl restart nginx
sudo systemctl restart php7.4-fpm
```

### 7. **Habilitar Modo Debug Temporalmente**

#### En tu VPS, edita el .env:
```bash
cd /ruta/a/tu/proyecto
nano .env
```

Cambia:
```env
APP_DEBUG=false
APP_LOG_LEVEL=error
```

A:
```env
APP_DEBUG=true
APP_LOG_LEVEL=debug
```

Luego reinicia:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 8. **Crear Script de Diagnóstico**

Crea un archivo `diagnostico.php` en tu VPS:

```php
<?php
// /ruta/a/tu/proyecto/diagnostico.php

echo "=== DIAGNÓSTICO DEL SISTEMA ===\n";

// 1. Verificar conexión a BD
try {
    $pdo = new PDO('mysql:host=localhost;dbname=nombre_db', 'usuario', 'password');
    echo "✅ Conexión a BD: OK\n";
} catch (Exception $e) {
    echo "❌ Conexión a BD: " . $e->getMessage() . "\n";
}

// 2. Verificar tabla numero_secuencias
try {
    $stmt = $pdo->query("SELECT * FROM numero_secuencias WHERE tipo = 'pedido_produccion'");
    $result = $stmt->fetch();
    if ($result) {
        echo "✅ Tabla numero_secuencias: OK (siguiente: {$result['siguiente']})\n";
    } else {
        echo "❌ Tabla numero_secuencias: No encontrado\n";
    }
} catch (Exception $e) {
    echo "❌ Tabla numero_secuencias: " . $e->getMessage() . "\n";
}

// 3. Verificar pedido específico
try {
    $stmt = $pdo->prepare("SELECT id, estado, numero_pedido FROM pedidos_produccion WHERE id = ?");
    $stmt->execute([1]);
    $pedido = $stmt->fetch();
    if ($pedido) {
        echo "✅ Pedido 1: OK (estado: {$pedido['estado']}, numero_pedido: {$pedido['numero_pedido']})\n";
    } else {
        echo "❌ Pedido 1: No encontrado\n";
    }
} catch (Exception $e) {
    echo "❌ Pedido 1: " . $e->getMessage() . "\n";
}

// 4. Verificar permisos de escritura
$logFile = __DIR__ . '/storage/logs/laravel.log';
if (is_writable($logFile)) {
    echo "✅ Logs escribibles: OK\n";
} else {
    echo "❌ Logs escribibles: NO\n";
}

echo "=== FIN DEL DIAGNÓSTICO ===\n";
```

Ejecuta:
```bash
php diagnostico.php
```

### 9. **Revisar Configuración de Broadcasting**

Verifica si Reverb está corriendo:
```bash
# Verificar si Reverb está activo
ps aux | grep reverb

# Iniciar Reverb si no está corriendo
php artisan reverb:start --no-interaction

# Verificar configuración
php artisan config:show broadcasting
```

### 10. **Monitoreo en Tiempo Real**

Para monitorear mientras pruebas:
```bash
# Terminal 1: Logs de Laravel
tail -f /ruta/a/tu/proyecto/storage/logs/laravel.log | grep -E "(CARTERA|ERROR|Exception)"

# Terminal 2: Logs de Nginx  
tail -f /var/log/nginx/error.log

# Terminal 3: Logs de acceso
tail -f /var/log/nginx/access.log | grep "cartera/pedidos"
```

## 🚨 Errores Comunes y Soluciones

### Error: "Permission denied"
```bash
# Solución: Corregir permisos
sudo chown -R www-data:www-data /ruta/a/tu/proyecto/storage
sudo chmod -R 775 /ruta/a/tu/proyecto/storage
```

### Error: "Table doesn't exist"
```bash
# Solución: Ejecutar migraciones faltantes
php artisan migrate --force
```

### Error: "Connection refused" (WebSocket)
```bash
# Solución: Iniciar Reverb o deshabilitar broadcasting temporalmente
php artisan reverb:start --no-interaction
# o
# En .env cambiar BROADCAST_DRIVER=log
```

### Error: "Out of memory"
```bash
# Solución: Aumentar memoria de PHP
# En php.ini
memory_limit = 512M
```

## 📞 Si nada funciona, ejecuta esto:

```bash
# Comando completo para diagnóstico rápido
echo "=== INICIO DIAGNÓSTICO RÁPIDO ===" && \
echo "1. Espacio en disco:" && df -h | grep -E "/$|/var" && \
echo "2. Servicios:" && \
sudo systemctl status nginx --no-pager -l && \
echo "3. Logs recientes:" && \
tail -20 /ruta/a/tu/proyecto/storage/logs/laravel.log && \
echo "=== FIN DIAGNÓSTICO ==="
```

## 🎯 Próximos Pasos

1. **Ejecuta los comandos de diagnóstico**
2. **Comparte la salida de los logs**
3. **Identifica el error específico**
4. **Aplica la solución correspondiente**

Con esta información podremos identificar exactamente qué está causando el error 500 en producción.
