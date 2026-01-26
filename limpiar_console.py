#!/usr/bin/env python3
import re
import os
from pathlib import Path

def limpiar_console_logs(archivo_path):
    """Remover todas las líneas con console.log, console.warn, console.error"""
    try:
        with open(archivo_path, 'r', encoding='utf-8') as f:
            contenido = f.read()
        
        lineas_originales = len(contenido.split('\n'))
        
        # Remover líneas completas que contengan console.log/warn/error
        # Patrón: espacios + console.log/warn/error(...); + espacios + salto de línea
        contenido_limpio = re.sub(
            r'^\s*console\.(log|warn|error)\([^)]*(?:\([^)]*\))*[^)]*\);?\s*\n',
            '',
            contenido,
            flags=re.MULTILINE
        )
        
        # Segunda pasada para líneas que no terminan en salto de línea (última línea)
        contenido_limpio = re.sub(
            r'^\s*console\.(log|warn|error)\([^)]*(?:\([^)]*\))*[^)]*\);?\s*$',
            '',
            contenido_limpio,
            flags=re.MULTILINE
        )
        
        # Tercera pasada para console.log con strings multilínea o argumentos complejos
        contenido_limpio = re.sub(
            r'^\s*console\.(log|warn|error)\(.*?\);?[ \t]*(?:\n|$)',
            '',
            contenido_limpio,
            flags=re.MULTILINE | re.DOTALL
        )
        
        lineas_finales = len(contenido_limpio.split('\n'))
        lineas_removidas = lineas_originales - lineas_finales
        
        with open(archivo_path, 'w', encoding='utf-8') as f:
            f.write(contenido_limpio)
        
        return lineas_removidas
    except Exception as e:
        return f"Error: {str(e)}"

# Lista de archivos a limpiar
archivos = [
    r'c:\Users\Usuario\Documents\mundoindustrial\public\js\invoice-preview-live.js',
    r'c:\Users\Usuario\Documents\mundoindustrial\resources\views\asesores\pedidos\index.blade.php',
    r'c:\Users\Usuario\Documents\mundoindustrial\public\js\modulos\crear-pedido\epp\services\epp-imagen-manager.js'
]

print("=" * 70)
print("LIMPIADOR DE CONSOLE.LOG")
print("=" * 70)

for archivo in archivos:
    if os.path.exists(archivo):
        resultado = limpiar_console_logs(archivo)
        print(f"\n✓ {archivo}")
        if isinstance(resultado, int):
            print(f"  → {resultado} líneas removidas")
        else:
            print(f"  → {resultado}")
    else:
        print(f"\n✗ NO ENCONTRADO: {archivo}")

print("\n" + "=" * 70)
print("PROCESO COMPLETADO")
print("=" * 70)
