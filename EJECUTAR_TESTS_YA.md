# 🚀 INSTRUCCIONES RÁPIDAS - EJECUTAR TESTS

---

## En Windows - Opción 1 (MÁS FÁCIL)

Ejecuta esto en PowerShell (en la carpeta raíz del proyecto):

```powershell
php artisan test tests/Feature/Cotizacion/ --verbose
```

Luego espera 7-13 minutos. ✅

---

## En Windows - Opción 2 (MENÚ INTERACTIVO)

Ejecuta:

```powershell
./run-tests-cotizaciones.bat
```

Selecciona una opción del menú.

---

## En Linux/macOS

```bash
php artisan test tests/Feature/Cotizacion/ --verbose
```

O con menú:

```bash
bash run-tests-cotizaciones.sh
```

---

## ✅ Qué Pasará

1. Se crearán 260+ cotizaciones con campos completos
2. Se validarán todos los campos
3. Se verificará numero_cotizacion secuencial
4. Se probará concurrencia con 3 asesores
5. No se eliminarán datos existentes
6. Los datos nuevos quedarán en la BD

---

## ✨ Resultado Esperado

```
✅ PASSED tests\Feature\Cotizacion\CotizacionesCompleteTest.php (6 tests)
✅ PASSED tests\Feature\Cotizacion\CotizacionesIntegrityTest.php (12 tests)  
✅ PASSED tests\Feature\Cotizacion\CotizacionesConcurrencyTest.php (8 tests)

OK (26 tests)
```

---

## 📊 Ver Resultados

Después de ejecutar, ver cotizaciones creadas:

```bash
php artisan tinker
> Cotizacion::where('numero_cotizacion', 'like', 'COT-%')->count()
> Cotizacion::where('numero_cotizacion', 'like', 'COT-%')->latest()->first()
```

---

**¡Listo! Ejecuta: `php artisan test tests/Feature/Cotizacion/ --verbose`** 🎉

