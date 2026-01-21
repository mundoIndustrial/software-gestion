#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Analizador profesional de artículos con detección de duplicaciones
Soporta: PDF, CSV, Excel, listados desordenados
"""

import sys
import re
import json
import csv
from pathlib import Path
from collections import defaultdict
from typing import List, Dict, Tuple

try:
    import pandas as pd
except ImportError:
    print("❌ Instalando pandas...")
    import subprocess
    subprocess.check_call([sys.executable, "-m", "pip", "install", "pandas", "-q"])
    import pandas as pd

try:
    import pdfplumber
except ImportError:
    print("❌ Instalando pdfplumber...")
    import subprocess
    subprocess.check_call([sys.executable, "-m", "pip", "install", "pdfplumber", "-q"])
    import pdfplumber


class AnalizadorArticulos:
    """Analizador inteligente de artículos"""
    
    # Patrones a detectar
    COLORES = ['NEGRO', 'BLANCO', 'ROJO', 'AZUL', 'AMARILLO', 'VERDE', 'GRIS', 'NARANJA', 
               'CAFE', 'MARRÓN', 'ROSA', 'VINOTINTO', 'TURQUEZA', 'ORO', 'CHOCOLATE', 'MARINO',
               'CELESTE', 'MORADO', 'PLATA']
    
    MARCAS = ['STEELPRO', 'NIKE', 'SAGA', 'KONDOR', 'GRULLA', 'WARRIOR', 'ARMADURA', 'BRAHMA',
              'WELDER', 'NORSEG', 'INDIANA', 'ROGER', 'ROBUSTA', 'TROOPER', 'TOLEDO', 'ANSELL',
              'HONEYWELL', 'MSA', '3M', 'PETZL']
    
    MATERIALES = ['ALGODÓN', 'VAQUETA', 'CUERO', 'CAUCHO', 'LATEX', 'NITRILO', 'NEOPRENO',
                  'POLIURETANO', 'DRIL', 'LONA', 'CARNAZA', 'ACERO', 'POLYCARBONATO', 'NYLON',
                  'FIBERGLASS', 'GOMA', 'PVC', 'TELA']
    
    def __init__(self):
        self.articulos = []
        self.duplicaciones = {}
    
    def procesar_pdf(self, ruta_pdf: str) -> List[Dict]:
        """Extrae texto de PDF y lo procesa"""
        print(f"📖 Abriendo PDF: {ruta_pdf}")
        
        texto_completo = ""
        try:
            with pdfplumber.open(ruta_pdf) as pdf:
                print(f"📄 Total de páginas: {len(pdf.pages)}")
                
                for num_pagina, page in enumerate(pdf.pages, 1):
                    print(f"  Página {num_pagina}...", end='\r')
                    texto = page.extract_text() or ""
                    texto_completo += texto + "\n"
            
            print(f"✓ Texto extraído: {len(texto_completo)} caracteres")
        except Exception as e:
            print(f"❌ Error leyendo PDF: {e}")
            return []
        
        return self.procesar_listado_desordenado(texto_completo)
    
    def procesar_csv(self, ruta_csv: str) -> List[Dict]:
        """Lee archivo CSV"""
        print(f"📊 Leyendo CSV: {ruta_csv}")
        
        try:
            df = pd.read_csv(ruta_csv)
            print(f"✓ Leído: {len(df)} filas, {len(df.columns)} columnas")
            return df.to_dict('records')
        except Exception as e:
            print(f"❌ Error leyendo CSV: {e}")
            return []
    
    def procesar_excel(self, ruta_excel: str) -> List[Dict]:
        """Lee archivo Excel"""
        print(f"📊 Leyendo Excel: {ruta_excel}")
        
        try:
            df = pd.read_excel(ruta_excel)
            print(f"✓ Leído: {len(df)} filas, {len(df.columns)} columnas")
            return df.to_dict('records')
        except Exception as e:
            print(f"❌ Error leyendo Excel: {e}")
            return []
    
    def procesar_listado_desordenado(self, texto: str) -> List[Dict]:
        """Procesa listado desordenado sin estructura"""
        print("📝 Procesando listado desordenado...")
        
        lineas = [l.strip().strip('"') for l in texto.split('\n') if l.strip() and l.strip() != '""']
        
        articulos = []
        articulo_actual = {'nombre': '', 'detalles': []}
        
        for linea in lineas:
            if not linea or len(linea) < 2:
                continue
            
            # Detectar si es nombre o detalle
            es_nombre = self._es_nombre_articulo(linea)
            es_detalle = self._es_detalle_articulo(linea)
            
            if es_nombre and linea not in ['', 'NOMBRE', 'ARTICULO']:
                # Guardar anterior si existe
                if articulo_actual['nombre']:
                    articulos.append(self._estructurar_articulo(articulo_actual))
                
                articulo_actual = {'nombre': linea, 'detalles': []}
            
            elif es_detalle and articulo_actual['nombre']:
                articulo_actual['detalles'].append(linea)
        
        # Guardar último
        if articulo_actual['nombre']:
            articulos.append(self._estructurar_articulo(articulo_actual))
        
        print(f"✓ Detectados {len(articulos)} artículos")
        return articulos
    
    def _es_nombre_articulo(self, linea: str) -> bool:
        """Detecta si es nombre de artículo"""
        # Nombres tienen más de 15 caracteres
        if len(linea) < 15:
            return False
        
        # No son solo mayúsculas cortas
        if re.match(r'^[A-Z\s]{5,20}$', linea):
            return False
        
        # No empiezan con patrones de marca/referencia
        if re.match(r'^(T:|TALLA|REF|RFC|C/P|S/P|M/L)', linea, re.I):
            return False
        
        return True
    
    def _es_detalle_articulo(self, linea: str) -> bool:
        """Detecta si es detalle (marca, ref, talla, etc)"""
        # Patrones conocidos
        patrones = [
            r'^(STEELPRO|NIKE|SAGA|KONDOR|GRULLA|WARRIOR|ARMADURA|BRAHMA|WELDER|NORSEG|INDIANA|ROGER)$',
            r'^REF[:\.\s]',
            r'^T[:\s]?\d+',
            r'^TALLA',
            r'^C/P|^S/P|^M/L',
            r'^\d+\s*(cm|mm|"|pulgadas)',
            r'^[A-Z]{2,5}:?\s*\d+',
        ]
        
        for patron in patrones:
            if re.match(patron, linea, re.I):
                return True
        
        # Líneas cortas con números son probablemente detalles
        if len(linea) < 30 and re.search(r'\d', linea):
            return True
        
        return False
    
    def _estructurar_articulo(self, item: Dict) -> Dict:
        """Estructura un artículo extrayendo atributos"""
        nombre = item['nombre']
        detalles = ' '.join(item['detalles'])
        texto_completo = f"{nombre} {detalles}".upper()
        
        return {
            'Nombre': nombre,
            'Marca': self._extraer_marca(texto_completo),
            'Color': self._extraer_color(texto_completo),
            'Material': self._extraer_material(texto_completo),
            'Talla': self._extraer_talla(texto_completo),
            'Medida': self._extraer_medida(nombre),
            'Referencia': self._extraer_referencia(texto_completo),
            'Detalles': detalles
        }
    
    def _extraer_marca(self, texto: str) -> str:
        for marca in self.MARCAS:
            if marca in texto:
                return marca
        return ''
    
    def _extraer_color(self, texto: str) -> str:
        for color in self.COLORES:
            if color in texto:
                return color
        return ''
    
    def _extraer_material(self, texto: str) -> str:
        for material in self.MATERIALES:
            if material in texto:
                return material
        return ''
    
    def _extraer_talla(self, texto: str) -> str:
        match = re.search(r'T[:\s]*(\d+|S|M|L|XL|XXL|XXXL)', texto)
        return match.group(1) if match else ''
    
    def _extraer_medida(self, texto: str) -> str:
        match = re.search(r'(\d+[\*x]\d+)|(\d+")|(\d+\s*cm)', texto, re.I)
        return match.group(0) if match else ''
    
    def _extraer_referencia(self, texto: str) -> str:
        match = re.search(r'REF[:\.\s]+([A-Z0-9\-\.]+)', texto)
        return match.group(1).strip() if match else ''
    
    def analizar_duplicaciones(self, datos: List[Dict]) -> Dict:
        """Analiza duplicaciones agrupadas por atributos"""
        print("\n🔍 Analizando duplicaciones...")
        
        self.articulos = datos
        
        # Campos para agrupar
        campos_agrupacion = ['Color', 'Marca', 'Material', 'Talla', 'Medida']
        
        # Agrupar
        grupos = defaultdict(list)
        
        for idx, articulo in enumerate(datos):
            clave = tuple(
                articulo.get(campo, 'N/A') 
                for campo in campos_agrupacion
                if articulo.get(campo, '').strip()
            )
            
            if clave:
                grupos[clave].append({**articulo, '_indice': idx})
        
        # Filtrar solo duplicados
        self.duplicaciones = {
            k: v for k, v in grupos.items() 
            if len(v) > 1
        }
        
        # Ordenar por cantidad
        self.duplicaciones = dict(
            sorted(self.duplicaciones.items(), key=lambda x: len(x[1]), reverse=True)
        )
        
        print(f"✓ Encontrados {len(self.duplicaciones)} grupos de duplicados")
        return self.duplicaciones
    
    def mostrar_resumen(self, datos: List[Dict]):
        """Muestra resumen en consola"""
        print("\n" + "="*80)
        print("📊 RESUMEN")
        print("="*80)
        print(f"Total de artículos: {len(datos)}")
        print(f"Campos detectados: {len(datos[0]) if datos else 0}")
        
        if datos:
            print(f"\nPrimeros 5 artículos:")
            for i, art in enumerate(datos[:5], 1):
                print(f"\n{i}. {art.get('Nombre', 'Sin nombre')}")
                print(f"   Marca: {art.get('Marca', 'N/A')}")
                print(f"   Color: {art.get('Color', 'N/A')}")
                print(f"   Material: {art.get('Material', 'N/A')}")
                print(f"   Talla: {art.get('Talla', 'N/A')}")
    
    def mostrar_duplicados(self):
        """Muestra duplicaciones encontradas"""
        if not self.duplicaciones:
            print("\n✓ No se encontraron duplicaciones")
            return
        
        print("\n" + "="*80)
        print("⚠️  DUPLICACIONES DETECTADAS")
        print("="*80)
        
        for idx, (criterios, articulos) in enumerate(self.duplicaciones.items(), 1):
            print(f"\n🔴 DUPLICACIÓN #{idx} - {len(articulos)} artículos")
            print(f"   Criterios: {' | '.join(f'{c}' for c in criterios if c != 'N/A')}")
            
            for i, art in enumerate(articulos, 1):
                print(f"   {i}. {art.get('Nombre', 'Sin nombre')}")
    
    def exportar_csv(self, ruta_salida: str):
        """Exporta a CSV"""
        if not self.articulos:
            print("❌ No hay datos para exportar")
            return
        
        df = pd.DataFrame(self.articulos)
        df.to_csv(ruta_salida, index=False, encoding='utf-8')
        print(f"✓ CSV guardado: {ruta_salida}")
    
    def exportar_excel(self, ruta_salida: str):
        """Exporta a Excel"""
        if not self.articulos:
            print("❌ No hay datos para exportar")
            return
        
        df = pd.DataFrame(self.articulos)
        df.to_excel(ruta_salida, index=False, engine='openpyxl')
        print(f"✓ Excel guardado: {ruta_salida}")
    
    def exportar_reporte_html(self, ruta_salida: str):
        """Genera reporte HTML interactivo"""
        print(f"\n📄 Generando reporte HTML...")
        
        html = f"""
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Análisis de Artículos</title>
    <style>
        body {{ font-family: 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; }}
        .container {{ max-width: 1200px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 30px; }}
        .header {{ background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; }}
        .header h1 {{ margin: 0; font-size: 28px; }}
        .header p {{ margin: 5px 0 0 0; opacity: 0.9; }}
        .stats {{ display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }}
        .stat-card {{ background: #f0f4ff; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; }}
        .stat-label {{ font-size: 13px; color: #999; margin-bottom: 8px; }}
        .stat-value {{ font-size: 24px; font-weight: 700; color: #333; }}
        table {{ width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 30px; }}
        th {{ background: #667eea; color: white; padding: 12px; text-align: left; font-weight: 600; }}
        td {{ padding: 12px; border-bottom: 1px solid #e0e0e0; }}
        tr:hover {{ background: #f9f9f9; }}
        .duplicate-group {{ background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 15px; border-radius: 4px; }}
        .duplicate-group h3 {{ margin-top: 0; color: #856404; }}
        .criteria {{ display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 15px; }}
        .criteria-item {{ background: white; padding: 10px; border-radius: 4px; font-size: 12px; }}
        .criteria-label {{ font-weight: 600; color: #667eea; }}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Reporte de Análisis de Artículos</h1>
            <p>Análisis automático de duplicaciones y atributos</p>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-label">Total de Artículos</div>
                <div class="stat-value">{len(self.articulos)}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Duplicaciones Encontradas</div>
                <div class="stat-value">{len(self.duplicaciones)}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Artículos Duplicados</div>
                <div class="stat-value">{sum(len(v) for v in self.duplicaciones.values())}</div>
            </div>
        </div>
        
        <h2>📋 Tabla de Todos los Artículos</h2>
        <table>
            <thead>
                <tr>
"""
        
        # Headers
        if self.articulos:
            for col in self.articulos[0].keys():
                if col != '_indice':
                    html += f"<th>{col}</th>"
        
        html += "</tr></thead><tbody>"
        
        # Datos
        for art in self.articulos:
            html += "<tr>"
            for col, val in art.items():
                if col != '_indice':
                    html += f"<td>{val or ''}</td>"
            html += "</tr>"
        
        html += """
            </tbody>
        </table>
        
        <h2>⚠️ Duplicaciones Detectadas</h2>
"""
        
        if not self.duplicaciones:
            html += "<p style='color: green;'>✓ No se encontraron duplicaciones</p>"
        else:
            for idx, (criterios, articulos) in enumerate(self.duplicaciones.items(), 1):
                html += f"""
        <div class="duplicate-group">
            <h3>Duplicación #{idx} - {len(articulos)} artículos</h3>
            <div class="criteria">
"""
                for crit in criterios:
                    if crit != 'N/A':
                        html += f'<div class="criteria-item"><span class="criteria-label">Criterio:</span> {crit}</div>'
                
                html += """
            </div>
            <table>
                <thead><tr>
"""
                if articulos:
                    for col in articulos[0].keys():
                        if col != '_indice':
                            html += f"<th>{col}</th>"
                
                html += """
                </tr></thead>
                <tbody>
"""
                
                for art in articulos:
                    html += "<tr>"
                    for col, val in art.items():
                        if col != '_indice':
                            html += f"<td>{val or ''}</td>"
                    html += "</tr>"
                
                html += """
                </tbody>
            </table>
        </div>
"""
        
        html += """
    </div>
</body>
</html>
"""
        
        with open(ruta_salida, 'w', encoding='utf-8') as f:
            f.write(html)
        
        print(f"✓ HTML guardado: {ruta_salida}")


def main():
    print("="*80)
    print("🔍 ANALIZADOR PROFESIONAL DE ARTÍCULOS")
    print("="*80)
    
    # Rutas
    carpeta_trabajo = Path(r"C:\Users\Usuario\Documents\mundoindustrial")
    ruta_pdf = carpeta_trabajo / "LISTADO ARTICULOS 1 00.pdf"
    ruta_csv_salida = carpeta_trabajo / "articulos_analizados.csv"
    ruta_excel_salida = carpeta_trabajo / "articulos_analizados.xlsx"
    ruta_html_salida = carpeta_trabajo / "reporte_articulos.html"
    
    # Crear analizador
    analizador = AnalizadorArticulos()
    
    # Procesar según el tipo de archivo
    if ruta_pdf.exists():
        articulos = analizador.procesar_pdf(str(ruta_pdf))
    else:
        print(f"❌ No se encontró: {ruta_pdf}")
        return
    
    if not articulos:
        print("❌ No se pudieron extraer artículos")
        return
    
    # Mostrar resumen
    analizador.mostrar_resumen(articulos)
    
    # Analizar duplicaciones
    analizador.analizar_duplicaciones(articulos)
    analizador.mostrar_duplicados()
    
    # Exportar
    print("\n💾 Exportando resultados...")
    analizador.exportar_csv(str(ruta_csv_salida))
    analizador.exportar_excel(str(ruta_excel_salida))
    analizador.exportar_reporte_html(str(ruta_html_salida))
    
    print("\n" + "="*80)
    print("✅ ANÁLISIS COMPLETADO")
    print("="*80)
    print(f"📁 Archivos generados en: {carpeta_trabajo}")
    print(f"  • articulos_analizados.csv")
    print(f"  • articulos_analizados.xlsx")
    print(f"  • reporte_articulos.html")


if __name__ == "__main__":
    main()
