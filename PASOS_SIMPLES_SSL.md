# 🎯 PASOS SUPER SIMPLES - ARREGLAR SSL

## ✅ PASO 1: Descargar Certificado

1. **Abre esta URL en tu navegador:**
   ```
   https://curl.se/ca/cacert.pem
   ```

2. **Verás un montón de texto** que empieza con:
   ```
   -----BEGIN CERTIFICATE-----
   ```

3. **Haz clic derecho** en cualquier parte de la página

4. **Selecciona:** "Guardar como..." o "Save as..."

5. **Guarda el archivo como:** `cacert.pem` en tu carpeta **Descargas**

---

## ✅ PASO 2: Ejecutar Script Automático

1. **Abre PowerShell o CMD** en la carpeta del proyecto

2. **Ejecuta:**
   ```bash
   configurar-ssl.bat
   ```

3. **El script copiará** el archivo a `C:\php\cacert.pem`

---

## ✅ PASO 3: Encontrar php.ini

1. **En la terminal, ejecuta:**
   ```bash
   php --ini
   ```

2. **Verás algo como:**
   ```
   Loaded Configuration File: C:\php\8.2\php-8.2.29-nts-Win32-vs16-x64\php.ini
   ```

3. **Copia esa ruta** (es la ubicación de tu php.ini)

---

## ✅ PASO 4: Editar php.ini

1. **Abre el archivo php.ini** con Notepad:
   - Haz clic derecho en el archivo
   - "Abrir con" > "Notepad" o "Bloc de notas"

2. **Presiona Ctrl+F** para buscar

3. **Busca:** `curl.cainfo`

4. **Encontrarás una línea como:**
   ```ini
   ;curl.cainfo =
   ```

5. **Cámbiala a:**
   ```ini
   curl.cainfo = "C:\php\cacert.pem"
   ```
   (Quita el `;` del inicio)

6. **Busca también:** `openssl.cafile`

7. **Encontrarás:**
   ```ini
   ;openssl.cafile=
   ```

8. **Cámbiala a:**
   ```ini
   openssl.cafile="C:\php\cacert.pem"
   ```

9. **GUARDA EL ARCHIVO** (Ctrl+S)

---

## ✅ PASO 5: Reiniciar Servidor

1. **En tu terminal:**
   - Presiona **Ctrl+C** para detener el servidor

2. **Inicia de nuevo:**
   ```bash
   php artisan serve
   ```

---

## ✅ PASO 6: Probar

1. **Ve a:**
   ```
   http://localhost:8000/balanceo/prenda/create
   ```

2. **Crea una prenda con imagen**

3. **¡DEBERÍA FUNCIONAR!** ✅

---

## 🎉 Verificar que Funcionó

**Ejecuta:**
```bash
php -i | findstr "curl.cainfo"
```

**Deberías ver:**
```
curl.cainfo => C:\php\cacert.pem => C:\php\cacert.pem
```

---

## 📝 Resumen Visual

```
1. Descargar cacert.pem
   ↓
2. Ejecutar configurar-ssl.bat
   ↓
3. Encontrar php.ini (php --ini)
   ↓
4. Editar php.ini
   - curl.cainfo = "C:\php\cacert.pem"
   - openssl.cafile="C:\php\cacert.pem"
   ↓
5. Guardar archivo
   ↓
6. Reiniciar servidor
   ↓
7. ¡FUNCIONA! ✅
```

---

## ⏱️ Tiempo Total: 3 MINUTOS

- Descargar: 30 segundos
- Script: 10 segundos
- Editar php.ini: 1 minuto
- Reiniciar: 20 segundos
- Probar: 1 minuto

**¡LISTO!** 🚀

---

## 🆘 Si Tienes Problemas

1. **El archivo no está en Descargas:**
   - Descárgalo de nuevo
   - Asegúrate de guardarlo como `cacert.pem`

2. **No encuentras php.ini:**
   - Ejecuta: `php --ini`
   - Usa la ruta que te muestra

3. **No puedes editar php.ini:**
   - Abre Notepad como Administrador
   - Luego abre el archivo php.ini

4. **Sigue sin funcionar:**
   - Cierra TODO (terminal, navegador)
   - Abre nueva terminal
   - Inicia servidor de nuevo

---

## ✨ Después de Esto

- ✅ Firebase funcionará perfectamente
- ✅ No más errores SSL
- ✅ Funciona para siempre
- ✅ Todas las APIs HTTPS funcionarán

**¡A trabajar!** 🎉
