#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script para extraer datos de PDF y convertir a CSV
"""

import os
import sys
import csv
import json
from pathlib import Path

try:
    import pdfplumber
except ImportError:
    print("❌ Instalando librería requerida: pdfplumber")
    os.system(f"{sys.executable} -m pip install pdfplumber -q")
    import pdfplumber

try:
    import pandas as pd
except ImportError:
    print("❌ Instalando librería requerida: pandas")
    os.system(f"{sys.executable} -m pip install pandas -q")
    import pandas as pd


def extraer_del_pdf(ruta_pdf):
    """Extrae tablas del PDF"""
    print(f"📖 Abriendo PDF: {ruta_pdf}")
    
    datos = []
    
    try:
        with pdfplumber.open(ruta_pdf) as pdf:
            print(f"📄 Total de páginas: {len(pdf.pages)}")
            
            for num_pagina, page in enumerate(pdf.pages, 1):
                print(f"📄 Procesando página {num_pagina}...")
                
                # Intentar extraer tablas
                tablas = page.extract_tables()
                
                if tablas:
                    for tabla in tablas:
                        for fila in tabla:
                            if fila and any(fila):  # Si la fila no está vacía
                                datos.append(fila)
                
                # Si no hay tablas, intentar extraer texto
                if not tablas:
                    texto = page.extract_text()
                    if texto:
                        lineas = texto.strip().split('\n')
                        for linea in lineas:
                            if linea.strip():
                                datos.append([linea])
    
    except Exception as e:
        print(f"❌ Error leyendo PDF: {e}")
        return None
    
    return datos


def procesar_datos(datos):
    """Procesa los datos extraídos"""
    if not datos:
        print("⚠️ No se encontraron datos en el PDF")
        return None
    
    # Si hay encabezados, usarlos
    encabezados = None
    datos_procesados = []
    
    for i, fila in enumerate(datos):
        # Limpiar valores None y espacios
        fila_limpia = [str(val).strip() if val else '' for val in fila]
        
        # La primera fila con múltiples columnas probablemente sea encabezado
        if i == 0 and len(fila_limpia) > 1:
            encabezados = fila_limpia
        else:
            datos_procesados.append(fila_limpia)
    
    # Si no hay encabezados, generar automáticos
    if not encabezados:
        max_cols = max(len(fila) for fila in datos_procesados) if datos_procesados else 1
        encabezados = [f'Columna_{i+1}' for i in range(max_cols)]
    
    return encabezados, datos_procesados


def guardar_csv(ruta_salida, encabezados, datos):
    """Guarda los datos en CSV"""
    try:
        with open(ruta_salida, 'w', newline='', encoding='utf-8-sig') as f:
            writer = csv.writer(f)
            writer.writerow(encabezados)
            writer.writerows(datos)
        print(f"✅ Archivo CSV guardado: {ruta_salida}")
        return True
    except Exception as e:
        print(f"❌ Error guardando CSV: {e}")
        return False


def guardar_json(ruta_salida, encabezados, datos):
    """Guarda los datos en JSON"""
    try:
        datos_json = []
        for fila in datos:
            obj = {}
            for i, encabezado in enumerate(encabezados):
                obj[encabezado] = fila[i] if i < len(fila) else ''
            datos_json.append(obj)
        
        with open(ruta_salida, 'w', encoding='utf-8') as f:
            json.dump(datos_json, f, ensure_ascii=False, indent=2)
        print(f"✅ Archivo JSON guardado: {ruta_salida}")
        return True
    except Exception as e:
        print(f"❌ Error guardando JSON: {e}")
        return False


def main():
    print("=" * 60)
    print("🔄 EXTRACTOR DE DATOS PDF → CSV/JSON")
    print("=" * 60)
    
    # Ruta del PDF descargado
    ruta_pdf = r"C:\Users\Usuario\Downloads\LISTADO ARTICULOS 1 00.pdf"
    
    # Verificar que el PDF existe
    if not os.path.exists(ruta_pdf):
        print(f"❌ No se encontró el archivo: {ruta_pdf}")
        print("📌 Verifica la ubicación del PDF descargado")
        return
    
    # Extraer datos
    datos = extraer_del_pdf(ruta_pdf)
    if not datos:
        print("❌ No se pudieron extraer datos del PDF")
        return
    
    print(f"📊 Se extrajeron {len(datos)} filas")
    
    # Procesar datos
    encabezados, datos_procesados = procesar_datos(datos)
    
    if not encabezados:
        print("❌ No se pudieron procesar los datos")
        return
    
    print(f"📋 Encabezados detectados: {', '.join(encabezados)}")
    print(f"📊 Total de registros: {len(datos_procesados)}")
    
    # Crear directorio de salida
    dir_salida = Path(r"C:\Users\Usuario\Documents\mundoindustrial\datos_extraidos")
    dir_salida.mkdir(exist_ok=True)
    
    # Guardar en CSV
    ruta_csv = dir_salida / "articulos.csv"
    if guardar_csv(ruta_csv, encabezados, datos_procesados):
        print(f"📂 Ruta completa: {ruta_csv}")
    
    # Guardar en JSON
    ruta_json = dir_salida / "articulos.json"
    if guardar_json(ruta_json, encabezados, datos_procesados):
        print(f"📂 Ruta completa: {ruta_json}")
    
    print("\n" + "=" * 60)
    print("✅ ¡EXTRACCIÓN COMPLETADA!")
    print("=" * 60)
    print("\n📌 Próximos pasos:")
    print(f"1. Abre el archivo CSV/JSON generado")
    print(f"2. Cópialo y pégalo en el analizador (analizador-articulos.html)")
    print(f"3. Haz clic en 'Procesar Datos'")


if __name__ == "__main__":
    main()
