# 🔥 ARREGLAR ERROR SSL AHORA (2 MINUTOS)

## ⚡ SOLUCIÓN MÁS RÁPIDA

### **Paso 1: Descargar Certificado**

Haz clic aquí y descarga el archivo:  
👉 **https://curl.se/ca/cacert.pem** 👈

(Haz clic derecho > "Guardar enlace como..." > Guarda como `cacert.pem`)

### **Paso 2: Mover el Archivo**

Mueve `cacert.pem` a:
```
C:\php\cacert.pem
```

Si no existe la carpeta `C:\php\`, créala.

### **Paso 3: Editar php.ini**

1. Abre este archivo en Notepad:
   ```
   C:\php\8.2\php-8.2.29-nts-Win32-vs16-x64\php.ini
   ```

2. Presiona `Ctrl+F` y busca: `curl.cainfo`

3. Cambia esta línea:
   ```ini
   ;curl.cainfo =
   ```
   
   Por esta:
   ```ini
   curl.cainfo = "C:\php\cacert.pem"
   ```

4. Busca también: `openssl.cafile`

5. Cambia:
   ```ini
   ;openssl.cafile=
   ```
   
   Por:
   ```ini
   openssl.cafile="C:\php\cacert.pem"
   ```

6. **GUARDA EL ARCHIVO** (Ctrl+S)

### **Paso 4: Reiniciar**

En tu terminal:
```bash
# Detener el servidor (Ctrl+C)
# Luego iniciar de nuevo:
php artisan serve
```

### **Paso 5: Probar**

Ve a: http://localhost:8000/balanceo/prenda/create

Sube una imagen.

**¡DEBERÍA FUNCIONAR!** ✅

---

## 🎯 Si No Sabes Dónde Está php.ini

Ejecuta en terminal:
```bash
php --ini
```

Te mostrará la ruta exacta. Usa esa.

---

## 📝 Verificar que Funcionó

Ejecuta:
```bash
php -i | findstr "curl.cainfo"
```

Debería mostrar:
```
curl.cainfo => C:\php\cacert.pem => C:\php\cacert.pem
```

---

## ✅ Resultado

Después de esto:
- ✅ Firebase funcionará
- ✅ No más errores SSL
- ✅ Funciona para siempre
- ✅ Solución correcta y segura

---

## ⏱️ Tiempo Total: 2 MINUTOS

1. Descargar archivo (30 seg)
2. Mover a C:\php\ (10 seg)
3. Editar php.ini (1 min)
4. Reiniciar servidor (20 seg)

**¡LISTO!** 🎉
