# ⚠️ ERROR SSL - SOLUCIÓN INMEDIATA

## 🔴 El Problema

```
cURL error 60: SSL certificate problem: unable to get local issuer certificate
```

**Causa:** PHP en Windows no tiene certificados SSL.

---

## ✅ SOLUCIÓN (Elige UNA):

### **Opción A: Arreglo Permanente (2 minutos)** ⭐ RECOMENDADO

1. **Descargar certificado:**
   - Ve a: https://curl.se/ca/cacert.pem
   - Guarda el archivo

2. **Mover archivo:**
   - Copia `cacert.pem` a `C:\php\cacert.pem`

3. **Editar php.ini:**
   ```bash
   # Encontrar php.ini:
   php --ini
   ```
   
   Abre el archivo y agrega:
   ```ini
   curl.cainfo = "C:\php\cacert.pem"
   openssl.cafile="C:\php\cacert.pem"
   ```

4. **Reiniciar:**
   ```bash
   php artisan serve
   ```

**✅ Funciona para siempre**

---

### **Opción B: Script Automático** ⚡ MÁS RÁPIDO

1. **Ejecutar:**
   ```bash
   php descargar-certificado.php
   ```

2. **Seguir instrucciones** que muestra el script

3. **Reiniciar servidor**

**✅ Descarga el certificado automáticamente**

---

### **Opción C: Deshabilitar SSL** ⚠️ SOLO DESARROLLO

**Ya está configurado en el código**, solo:

1. Verifica `.env`:
   ```env
   APP_ENV=local
   FIREBASE_VERIFY_SSL=false
   ```

2. Reinicia servidor:
   ```bash
   php artisan serve
   ```

**⚠️ Menos seguro, solo para desarrollo**

---

## 🎯 ¿Cuál Elegir?

| Opción | Tiempo | Seguridad | Permanente |
|--------|--------|-----------|------------|
| **A - Certificado** | 2 min | ✅ Alta | ✅ Sí |
| **B - Script** | 1 min | ✅ Alta | ✅ Sí |
| **C - Deshabilitar** | 10 seg | ⚠️ Baja | ❌ No |

**Recomendación:** Usa **Opción A** o **B**

---

## 📝 Verificar que Funcionó

```bash
# Test 1: Ver configuración
php -i | findstr "curl.cainfo"

# Test 2: Probar Firebase
php test-firebase.php

# Test 3: Subir imagen
# Ve a: http://localhost:8000/balanceo/prenda/create
```

---

## 🆘 Si Nada Funciona

1. **Limpiar caché:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Reiniciar TODO:**
   - Cierra terminal
   - Abre nueva terminal
   - Inicia servidor

3. **Verificar .env:**
   ```bash
   php artisan tinker
   >>> config('app.env')
   => "local"
   >>> config('firebase.verify_ssl')
   => false
   ```

---

## 📚 Documentación Detallada

- `ARREGLAR_SSL_AHORA.md` - Instrucciones paso a paso
- `SOLUCION_DEFINITIVA_SSL.md` - Explicación completa
- `FIREBASE_LISTO_PARA_USAR.md` - Guía de uso

---

## ✨ Después de Arreglar

Una vez que funcione:

1. ✅ Sube imágenes a Firebase
2. ✅ URLs se guardan en DB
3. ✅ Imágenes se muestran en galería
4. ✅ Sin más errores SSL

**¡A trabajar!** 🚀
