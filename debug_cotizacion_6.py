#!/usr/bin/env python3
"""
Script para depurar datos de Cotización ID 6
"""
import os
import sys
import json
from pathlib import Path

# Agregar el directorio al path para importar módulos
sys.path.insert(0, str(Path(__file__).parent))

# Cargar variables de .env
from dotenv import load_dotenv
load_dotenv()

import mysql.connector

def get_db_connection():
    """Crear conexión a la BD"""
    return mysql.connector.connect(
        host=os.getenv('DB_HOST', 'localhost'),
        user=os.getenv('DB_USERNAME', 'root'),
        password=os.getenv('DB_PASSWORD', ''),
        database=os.getenv('DB_DATABASE', 'mundoindustrial'),
        charset='utf8mb4'
    )

def query_dict(sql, params=None):
    """Ejecutar query y retornar diccionarios"""
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)
    try:
        cursor.execute(sql, params or ())
        results = cursor.fetchall()
        return results
    finally:
        cursor.close()
        conn.close()

def main():
    print("\n" + "="*80)
    print("DEPURACIÓN - COTIZACIÓN ID 6")
    print("="*80)
    
    # 1. Datos de la cotización
    print("\n1️⃣ COTIZACIÓN #6")
    print("-" * 80)
    cotizaciones = query_dict("SELECT * FROM cotizaciones WHERE id = 6")
    if cotizaciones:
        for key, value in cotizaciones[0].items():
            print(f"   {key}: {value}")
    else:
        print("   ❌ No encontrada")
    
    # 2. Prendas de la cotización
    print("\n2️⃣ PRENDAS DE LA COTIZACIÓN #6")
    print("-" * 80)
    prendas = query_dict("SELECT id, nombre_producto, descripcion FROM prendas_cot WHERE cotizacion_id = 6")
    for prenda in prendas:
        print(f"   Prenda ID {prenda['id']}: {prenda['nombre_producto']}")
    
    # 3. Reflectivo Cotización
    print("\n3️⃣ REFLECTIVO COTIZACIÓN (para prenda 6)")
    print("-" * 80)
    reflectivos = query_dict("SELECT * FROM reflectivo_cotizacion WHERE prenda_cot_id = 6")
    if reflectivos:
        for ref in reflectivos:
            for key, value in ref.items():
                print(f"   {key}: {value}")
    else:
        print("   ❌ No hay reflectivo para esta prenda")
    
    # 4. Logo Cotización Técnica Prenda
    print("\n4️⃣ LOGO COTIZACIÓN TÉCNICA PRENDA (para prenda 6)")
    print("-" * 80)
    logos = query_dict("""
        SELECT 
            id, 
            prenda_cot_id, 
            tipo_logo_id, 
            ubicaciones, 
            observaciones,
            variaciones_prenda,
            talla_cantidad
        FROM logo_cotizacion_tecnica_prendas 
        WHERE prenda_cot_id = 6
    """)
    if logos:
        print(f"   Total: {len(logos)} técnicas de logo")
        for logo in logos:
            print(f"\n   Logo Técnica ID {logo['id']}:")
            print(f"      tipo_logo_id: {logo['tipo_logo_id']}")
            print(f"      ubicaciones: {logo['ubicaciones']}")
            print(f"      observaciones: {logo['observaciones']}")
            print(f"      variaciones_prenda: {logo['variaciones_prenda']}")
            print(f"      talla_cantidad: {logo['talla_cantidad']}")
    else:
        print("   ❌ No hay técnicas de logo para esta prenda")
    
    # 5. Tipo Logo (para obtener nombres)
    print("\n5️⃣ TIPOS DE LOGO DISPONIBLES")
    print("-" * 80)
    tipos = query_dict("SELECT id, nombre FROM tipo_logo_cotizaciones")
    for tipo in tipos:
        print(f"   ID {tipo['id']}: {tipo['nombre']}")
    
    # 6. Prenda Telas Cot
    print("\n6️⃣ PRENDA TELAS COT (para prenda 6)")
    print("-" * 80)
    telas = query_dict("SELECT * FROM prenda_telas_cot WHERE prenda_cot_id = 6")
    if telas:
        print(f"   Total: {len(telas)} telas")
        for tela in telas:
            for key, value in tela.items():
                print(f"      {key}: {value}")
    else:
        print("   ❌ No hay telas en prenda_telas_cot")
    
    # 7. Prenda Tela Foto Cot
    print("\n7️⃣ PRENDA TELA FOTO COT (para prenda 6)")
    print("-" * 80)
    fotos = query_dict("SELECT * FROM prenda_tela_fotos_cot WHERE prenda_cot_id = 6")
    if fotos:
        print(f"   Total: {len(fotos)} fotos")
        for foto in fotos:
            for key, value in foto.items():
                if value and len(str(value)) > 100:
                    print(f"      {key}: {str(value)[:100]}...")
                else:
                    print(f"      {key}: {value}")
    else:
        print("   ❌ No hay fotos en prenda_tela_fotos_cot")
    
    # 8. Variantes Prenda Cot
    print("\n8️⃣ VARIANTES PRENDA COT (para prenda 6)")
    print("-" * 80)
    variantes = query_dict("SELECT * FROM prenda_variantes_cot WHERE prenda_cot_id = 6")
    if variantes:
        print(f"   Total: {len(variantes)} variantes")
        for var in variantes:
            print(f"\n   Variante ID {var['id']}:")
            for key, value in var.items():
                if value and len(str(value)) > 100:
                    print(f"      {key}: {str(value)[:100]}...")
                else:
                    print(f"      {key}: {value}")
    else:
        print("   ❌ No hay variantes")
    
    # 9. Resumen
    print("\n" + "="*80)
    print("RESUMEN")
    print("="*80)
    print(f"✓ Cotización encontrada: {'Sí' if cotizaciones else 'No'}")
    print(f"✓ Prendas: {len(prendas)}")
    print(f"✓ Reflectivos: {len(reflectivos)}")
    print(f"✓ Técnicas Logo: {len(logos)}")
    print(f"✓ Telas en prenda_telas_cot: {len(telas)}")
    print(f"✓ Fotos de telas: {len(fotos)}")
    print(f"✓ Variantes: {len(variantes)}")
    
    if not logos and not reflectivos:
        print("\n⚠️ IMPORTANTE: NO HAY PROCESOS DEFINIDOS (ni reflectivo ni logo técnicas)")
        print("   Esto explica por qué no se cargan en el modal")

if __name__ == '__main__':
    try:
        main()
    except Exception as e:
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
