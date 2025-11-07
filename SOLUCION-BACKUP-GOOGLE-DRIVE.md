# Solución al Error de Backup a Google Drive

## Problema
El backup a Google Drive fallaba con el error `ERR_CONNECTION_RESET` debido a timeouts en el proceso.

## Causas
1. **Timeout de PHP**: El proceso de generar el backup SQL tarda mucho tiempo
2. **Timeout de conexión HTTP**: La petición AJAX se cierra antes de completarse
3. **Límites de memoria**: Procesar toda la base de datos en memoria

## Soluciones Implementadas

### 1. Optimizaciones en el Backend (`ConfiguracionController.php`)

#### a) Aumentar límites de tiempo y memoria
```php
set_time_limit(600); // 10 minutos
ini_set('memory_limit', '512M');
```

#### b) Procesamiento en chunks
Se cambió el procesamiento de datos para usar chunks de 500 registros a la vez:
```php
DB::table($tableName)->orderBy(DB::raw('1'))->chunk($chunkSize, function($rows) use ($handle, $tableName) {
    // Procesar cada chunk
});
```

#### c) Mejor logging
Se agregaron logs detallados para monitorear el progreso:
- Inicio del backup
- Procesamiento de cada tabla
- Tamaño del archivo generado
- Respuesta de Google Drive

#### d) Timeout de cURL aumentado
```php
curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutos para la subida
```

### 2. Mejoras en el Frontend (`configuracion.blade.php`)

- Mensaje informativo sobre el tiempo de espera
- Mejor feedback visual durante el proceso

## Configuración Adicional (Si el problema persiste)

### Opción 1: Configurar php.ini

Si aún experimentas timeouts, edita el archivo `php.ini`:

```ini
max_execution_time = 600
max_input_time = 600
memory_limit = 512M
post_max_size = 100M
upload_max_filesize = 100M
```

**Ubicación de php.ini:**
- XAMPP: `C:\xampp\php\php.ini`
- Laragon: `C:\laragon\bin\php\php8.x\php.ini`

Después de editar, reinicia Apache/servidor web.

### Opción 2: Configurar .htaccess

Agrega estas líneas al archivo `.htaccess` en la raíz del proyecto:

```apache
php_value max_execution_time 600
php_value max_input_time 600
php_value memory_limit 512M
```

### Opción 3: Configurar Nginx (si usas Nginx)

Edita tu configuración de Nginx:

```nginx
location / {
    fastcgi_read_timeout 600;
    fastcgi_send_timeout 600;
}
```

## Verificar la Configuración

Para verificar que los cambios se aplicaron correctamente:

1. Crea un archivo `info.php` en la carpeta `public`:
```php
<?php
phpinfo();
?>
```

2. Accede a `http://tu-servidor/info.php`

3. Busca las siguientes configuraciones:
   - `max_execution_time` → debe ser 600 o más
   - `memory_limit` → debe ser 512M o más
   - `max_input_time` → debe ser 600 o más

4. **IMPORTANTE**: Elimina el archivo `info.php` después de verificar (por seguridad)

## Monitorear el Proceso

Para ver el progreso del backup en tiempo real:

1. Abre el archivo de logs de Laravel:
   ```
   storage/logs/laravel.log
   ```

2. Durante el backup verás mensajes como:
   ```
   [timestamp] local.INFO: Iniciando backup a Google Drive
   [timestamp] local.INFO: Generando backup de la base de datos: nombre_db
   [timestamp] local.INFO: Procesando tabla 1/30: users
   [timestamp] local.INFO: Procesando tabla 2/30: roles
   ...
   [timestamp] local.INFO: Backup SQL generado, preparando subida a Google Drive
   [timestamp] local.INFO: Tamaño del archivo: 15.3 MB
   [timestamp] local.INFO: Subiendo archivo a Google Drive...
   [timestamp] local.INFO: Backup subido exitosamente a Google Drive
   ```

## Troubleshooting

### Error: "Maximum execution time exceeded"
- Aumenta `max_execution_time` en php.ini
- Verifica que `set_time_limit(600)` esté en el código

### Error: "Allowed memory size exhausted"
- Aumenta `memory_limit` en php.ini
- El código ya procesa en chunks, pero bases de datos muy grandes pueden necesitar más memoria

### Error: "cURL timeout"
- Verifica tu conexión a internet
- Aumenta `CURLOPT_TIMEOUT` en el código si tu conexión es lenta

### Error: "Invalid credentials" o "Access token expired"
- Verifica que `GOOGLE_DRIVE_REFRESH_TOKEN` esté configurado en `.env`
- El sistema renueva automáticamente el access token, pero verifica los logs

## Recomendaciones

1. **Programar backups automáticos**: Considera usar cron jobs para backups nocturnos
2. **Monitorear tamaño de BD**: Bases de datos muy grandes (>500MB) pueden necesitar soluciones más avanzadas
3. **Verificar espacio en Google Drive**: Asegúrate de tener suficiente espacio disponible
4. **Mantener logs limpios**: Revisa y limpia `storage/logs/laravel.log` periódicamente

## Notas Técnicas

- El backup se genera en `storage/app/temp/` y se elimina automáticamente después de subirlo
- El proceso usa multipart upload de Google Drive API v3
- Los tokens de acceso se renuevan automáticamente usando el refresh token
- El sistema acepta códigos HTTP 200 y 201 como exitosos
