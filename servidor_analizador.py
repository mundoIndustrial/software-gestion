#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Servidor Flask para Analizador de Artículos v2
Análisis profesional con estructura mejorada
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
from werkzeug.utils import secure_filename
import json
import re
import requests
from collections import defaultdict
from pathlib import Path

import pandas as pd

try:
    from PyPDF2 import PdfReader
    PDF_AVAILABLE = True
except:
    PDF_AVAILABLE = False

app = Flask(__name__, static_folder=str(Path(__file__).parent), static_url_path='')
CORS(app)
app.config['MAX_CONTENT_LENGTH'] = 50 * 1024 * 1024
app.config['UPLOAD_FOLDER'] = Path(__file__).parent / 'uploads'
app.config['UPLOAD_FOLDER'].mkdir(exist_ok=True)


class AnalizadorArticulosV2:
    """Analizador inteligente de artículos con estructura mejorada"""
    
    COLORES = ['NEGRO', 'NEGRA', 'BLANCO', 'BLANCA', 'ROJO', 'ROJA', 'AZUL', 'AMARILLO', 'AMARILLA', 
               'VERDE', 'GRIS', 'NARANJA', 'CAFE', 'MARRÓN', 'ROSA', 'VINOTINTO', 'TURQUEZA', 
               'ORO', 'CHOCOLATE', 'MARINO', 'CELESTE', 'MORADO', 'PLATA', 'PLATEADO', 'DORADO']
    
    MARCAS = ['STEELPRO', 'NIKE', 'SAGA', 'KONDOR', 'GRULLA', 'WARRIOR', 'ARMADURA', 'BRAHMA',
              'WELDER', 'NORSEG', 'INDIANA', 'ROGER', 'ROBUSTA', 'TROOPER', 'TOLEDO', 'ANSELL',
              'HONEYWELL', 'MSA', '3M', 'PETZL', 'MOLDEX', 'UVEX', 'NORTH', 'JSP', 'PATROL',
              'RAYDER', 'PETROL', 'EVACOL', 'TORINO', 'WARRIOR']
    
    CATEGORIAS = {
        # Calzado -> PIES
        'BOTA': 'PIES',
        'ZAPATO': 'PIES',
        'CALZADO': 'PIES',
        'TENIS': 'PIES',
        'CROCS': 'PIES',
        'CHANCLETA': 'PIES',
        
        # Cabeza -> CABEZA
        'CASCO': 'CABEZA',
        'GORRO': 'CABEZA',
        'BONETE': 'CABEZA',
        'BANDANA': 'CABEZA',
        
        # Ojos -> PROTECCION_VISUAL
        'LENTE': 'PROTECCION_VISUAL',
        'GAFA': 'PROTECCION_VISUAL',
        'VISOR': 'PROTECCION_VISUAL',
        'MONOGAFA': 'PROTECCION_VISUAL',
        'MONOGAFAS': 'PROTECCION_VISUAL',
        'CARETA': 'PROTECCION_VISUAL',
        'PROTECTOR OCULAR': 'PROTECCION_VISUAL',
        
        # Guantes y manos -> MANOS
        'GUANTE': 'MANOS',
        'MANOPLA': 'MANOS',
        'MANGA': 'MANOS',
        'MITÓN': 'MANOS',
        
        # Protección auditiva -> PROTECCION_AUDITIVA
        'OÍDO': 'PROTECCION_AUDITIVA',
        'AURICULAR': 'PROTECCION_AUDITIVA',
        'TAPÓN': 'PROTECCION_AUDITIVA',
        'FONO': 'PROTECCION_AUDITIVA',
        'TAPON': 'PROTECCION_AUDITIVA',
        
        # Protección respiratoria -> RESPIRATORIA
        'MASCARILLA': 'RESPIRATORIA',
        'MÁSCARA': 'RESPIRATORIA',
        'FILTRO': 'RESPIRATORIA',
        'RESPIRADOR': 'RESPIRATORIA',
        'CARTUCHOS': 'RESPIRATORIA',
        
        # Protección de cuerpo -> CUERPO
        'CHALECO': 'CUERPO',
        'OVEROL': 'CUERPO',
        'TRAJE': 'CUERPO',
        'DELANTAL': 'CUERPO',
        'BATA': 'CUERPO',
        'PECHERA': 'CUERPO',
        'CHAQUETA': 'CUERPO',
        'GABACHA': 'CUERPO',
        
        # Protección caída -> OTRA (no existe PROTECCION_CAIDA como en BD original)
        'ARNÉS': 'OTRA',
        'ESLINGA': 'OTRA',
        'MOSQUETÓN': 'OTRA',
        'CINTURÓN SEGURIDAD': 'OTRA',
        'AMORTIGUADOR': 'OTRA',
        'LÍNEA VIDA': 'OTRA',
        
        # Seguridad vial y señalización -> OTROS
        'SEÑALIZACIÓN': 'OTROS',
        'CONO': 'OTROS',
        'REFLECTIVO': 'OTROS',
        'CHALECO REFLECTIVO': 'OTROS',
        'LINTERNA': 'OTROS',
        
        # Desinfectantes y primeros auxilios -> OTROS
        'ALCOHOL': 'OTROS',
        'ANTISÉPTICO': 'OTROS',
        'BOTIQUÍN': 'OTROS',
        'GASA': 'OTROS',
        'APÓSITO': 'OTROS',
        'VENDAJE': 'OTROS',
        'TORNIQUETE': 'OTROS',
        'JERINGA': 'OTROS',
        'SUERO': 'OTROS',
        'ESPARADRAPO': 'OTROS',
        'PRIMEROS AUXILIOS': 'OTROS',
        
        # Otros
        'EXTINTOR': 'OTROS',
        'LÁMPARA': 'OTROS',
        'LAMPARA': 'OTROS',
        'BOTELLA': 'OTROS',
        'TERMO': 'OTROS',
        'PUNTO ECOLOGICO': 'OTROS',
    }
    
    def __init__(self):
        self.articulos = []
        self.duplicaciones = {}
    
    def procesar_pdf(self, ruta_pdf: str) -> list:
        """Extrae texto de PDF"""
        if not PDF_AVAILABLE:
            return []
        
        texto_completo = ""
        try:
            with open(ruta_pdf, 'rb') as file:
                pdf_reader = PdfReader(file)
                for page in pdf_reader.pages:
                    texto = page.extract_text() or ""
                    texto_completo += texto + "\n"
        except Exception as e:
            print(f"Error leyendo PDF: {e}")
            return []
        
        if not texto_completo.strip():
            return []
        
        return self.procesar_listado(texto_completo)
    
    def procesar_csv(self, ruta_csv: str) -> list:
        """Lee archivo CSV o Excel"""
        try:
            if ruta_csv.endswith(('.xlsx', '.xls')):
                df = pd.read_excel(ruta_csv)
            else:
                df = pd.read_csv(ruta_csv)
            
            return df.to_dict('records')
        except Exception as e:
            print(f"Error: {e}")
            return []
    
    def procesar_listado(self, texto: str) -> list:
        """Procesa listado de artículos"""
        lineas = texto.split('\n')
        
        # Si hay pocas líneas, probablemente está pegado
        if len(lineas) <= 3:
            lineas = self._dividir_articulos_pegados(texto)
        
        lineas = [l.strip() for l in lineas if l.strip() and l.strip() != '""']
        
        articulos = []
        for linea in lineas:
            if linea and len(linea) > 2:
                articulos.append(self._estructurar_articulo(linea))
        
        return articulos
    
    def _dividir_articulos_pegados(self, texto: str) -> list:
        """Divide artículos pegados"""
        inicios = ['SEÑAL', 'ALCOHOL', 'BOTA ', 'BOT ', 'CASQUETE', 'ESCOBAS', 'ESPONJA', 
                   'JABON', 'RECOGEDOR', 'TRAPERO', 'DUCHA', 'LENTE', 'ESLINGA', 'ZAPATO',
                   'GUANTE', 'VALVULA', 'ARAÑA', 'ARNES', 'YODO', 'CASCO', 'MASCARILLA']
        
        texto_upper = texto.upper()
        divisiones = [0]
        
        for inicio in inicios:
            pos = 0
            while True:
                pos = texto_upper.find(inicio, pos)
                if pos == -1:
                    break
                if pos == 0 or texto_upper[pos-1] == ' ':
                    if pos not in divisiones and pos > 0:
                        divisiones.append(pos)
                pos += 1
        
        divisiones.sort()
        divisiones.append(len(texto))
        
        resultado = []
        for i in range(len(divisiones)-1):
            articulo = texto[divisiones[i]:divisiones[i+1]].strip()
            if articulo:
                resultado.append(articulo)
        
        return resultado if resultado else [texto]
    
    def _estructurar_articulo(self, linea: str) -> dict:
        """Estructura un artículo con todos los campos solicitados"""
        texto_upper = linea.upper()
        
        return {
            'nombre_completo': linea,
            'marca': self._extraer_marca(texto_upper),
            'categoria': self._extraer_categoria(texto_upper),
            'tipo': self._extraer_tipo(texto_upper),
            'talla': self._extraer_talla(texto_upper),
            'color': self._extraer_color(texto_upper),
        }
    
    def _extraer_marca(self, texto: str):
        """Extrae marca si existe"""
        for marca in self.MARCAS:
            if marca in texto:
                return marca
        return None
    
    def _extraer_color(self, texto: str):
        """Extrae color si existe"""
        for color in self.COLORES:
            if color in texto:
                return color
        return None
    
    def _extraer_categoria(self, texto: str):
        """Extrae categoría del producto con fallback a OTROS"""
        # Buscar palabras clave exactas primero (orden importa)
        for palabra_clave, categoria in self.CATEGORIAS.items():
            if palabra_clave in texto:
                return categoria
        
        # Si nada coincide, asignar categoría por defecto OTROS
        return 'OTROS'
    
    def _extraer_talla(self, texto: str):
        """Extrae talla en varios formatos"""
        # Formatos: T:40, TALLA 9, TALLA M, 38, 39, T 42, etc
        patterns = [
            r'T[:\s]*(\d+)',           # T:40, T:42, T 40
            r'TALLA\s*[:=]?\s*(\w+)',  # TALLA 9, TALLA M
            r'TALLA\s+(\d+)',          # TALLA 42
            r'SIZE\s+(\w+)',           # SIZE M, SIZE 9
        ]
        
        for pattern in patterns:
            match = re.search(pattern, texto, re.IGNORECASE)
            if match:
                return match.group(1)
        
        return None
    
    def _extraer_tipo(self, texto: str) -> str:
        """Determina si es PRODUCTO o SERVICIO"""
        palabras_servicio = ['SERVICIO', 'RECARGA', 'INSPECCIÓN', 'REVISIÓN', 'REPARACIÓN', 
                             'MANTENIMIENTO', 'INSTALACIÓN', 'CAPACITACIÓN', 'CONSULTORÍA']
        
        for palabra in palabras_servicio:
            if palabra in texto:
                return 'SERVICIO'
        
        return 'PRODUCTO'
    
    def analizar(self, articulos: list) -> dict:
        """Procesa y analiza artículos"""
        self.articulos = articulos
        
        return {
            'total_articulos': len(articulos),
            'articulos': articulos,
            'duplicaciones': self._encontrar_duplicaciones()
        }
    
    def _encontrar_duplicaciones(self) -> list:
        """Encuentra artículos duplicados por marca, color, talla"""
        campos = ['marca', 'color', 'talla']
        
        grupos = defaultdict(list)
        
        for idx, art in enumerate(self.articulos):
            # Crear clave de agrupación
            clave = tuple(
                str(art.get(campo, 'None'))
                for campo in campos
                if art.get(campo) is not None
            )
            
            if clave:
                grupos[clave].append({**art, '_indice': idx})
        
        # Solo duplicados
        duplicados = []
        for clave, articulos in grupos.items():
            if len(articulos) > 1:
                duplicados.append({
                    'criterios': {
                        'marca': articulos[0].get('marca'),
                        'color': articulos[0].get('color'),
                        'talla': articulos[0].get('talla'),
                    },
                    'cantidad': len(articulos),
                    'articulos': articulos
                })
        
        return sorted(duplicados, key=lambda x: x['cantidad'], reverse=True)


@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok'})


@app.route('/', methods=['GET'])
@app.route('/analizador-articulos.html', methods=['GET'])
def servir_html():
    html_path = Path(__file__).parent / 'analizador-articulos.html'
    if html_path.exists():
        with open(html_path, 'r', encoding='utf-8') as f:
            return f.read(), 200, {'Content-Type': 'text/html; charset=utf-8'}
    return 'No encontrado', 404


@app.route('/api/procesar', methods=['POST'])
def procesar():
    """Procesa datos de artículos"""
    try:
        data = request.get_json()
        if not data:
            return jsonify({'error': 'No hay datos'}), 400
        
        analizador = AnalizadorArticulosV2()
        
        # Procesar según tipo de entrada
        if 'texto' in data and data['texto'].strip():
            articulos = analizador.procesar_listado(data['texto'])
        elif 'datos' in data:
            articulos = data['datos']
            if not isinstance(articulos, list):
                articulos = [articulos]
        else:
            return jsonify({'error': 'No hay datos válidos'}), 400
        
        if not articulos:
            return jsonify({'error': 'No se pudieron procesar los datos'}), 400
        
        resultado = analizador.analizar(articulos)
        return jsonify(resultado)
    
    except Exception as e:
        import traceback
        print(f"Error: {e}\n{traceback.format_exc()}")
        return jsonify({'error': str(e)}), 500


@app.route('/api/cargar-archivo', methods=['POST'])
def cargar_archivo():
    """Carga y procesa archivo (PDF, Excel, TXT)"""
    try:
        if 'archivo' not in request.files:
            return jsonify({'error': 'No hay archivo'}), 400
        
        archivo = request.files['archivo']
        if archivo.filename == '':
            return jsonify({'error': 'Archivo vacío'}), 400
        
        filename = secure_filename(archivo.filename)
        filepath = app.config['UPLOAD_FOLDER'] / filename
        archivo.save(str(filepath))
        
        lineas = []
        
        # Procesar según tipo de archivo
        if filename.lower().endswith('.pdf'):
            if PDF_AVAILABLE:
                with open(filepath, 'rb') as file:
                    pdf_reader = PdfReader(file)
                    for page in pdf_reader.pages:
                        texto = page.extract_text() or ""
                        lineas.extend([l.strip() for l in texto.split('\n') if l.strip()])
        
        elif filename.lower().endswith(('.xlsx', '.xls')):
            df = pd.read_excel(str(filepath))
            primera_columna = df.iloc[:, 0]
            lineas = [str(val).strip() for val in primera_columna.values if pd.notna(val) and str(val).strip()]
        
        elif filename.lower().endswith('.txt'):
            with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                lineas = [l.strip() for l in f.readlines() if l.strip()]
        
        else:
            return jsonify({'error': 'Formato de archivo no soportado'}), 400
        
        if not lineas:
            return jsonify({'error': 'No se encontraron datos en el archivo'}), 400
        
        print(f"📁 Procesado {filename} con {len(lineas)} líneas")
        
        analizador = AnalizadorArticulosV2()
        articulos = []
        
        for linea in lineas:
            if linea and len(linea) > 2:
                articulos.append(analizador._estructurar_articulo(linea))
        
        resultado = analizador.analizar(articulos)
        return jsonify(resultado)
    
    except Exception as e:
        import traceback
        print(f"Error: {e}\n{traceback.format_exc()}")
        return jsonify({'error': str(e)}), 500


@app.route('/api/cargar-excel-local', methods=['GET'])
def cargar_excel_local():
    """Carga el Excel local del servidor"""
    try:
        excel_path = Path(__file__).parent / 'LISTADO_ARTICULOS.xlsx'
        
        if not excel_path.exists():
            return jsonify({'error': 'Archivo no encontrado'}), 404
        
        df = pd.read_excel(str(excel_path))
        primera_columna = df.iloc[:, 0]
        lineas = [str(val).strip() for val in primera_columna.values if pd.notna(val) and str(val).strip()]
        
        print(f"[INFO] Cargado Excel con {len(lineas)} articulos")
        
        analizador = AnalizadorArticulosV2()
        articulos = []
        
        for linea in lineas:
            if linea and len(linea) > 2:
                articulos.append(analizador._estructurar_articulo(linea))
        
        resultado = analizador.analizar(articulos)
        return jsonify(resultado)
    
    except Exception as e:
        import traceback
        print(f"Error: {e}\n{traceback.format_exc()}")
        return jsonify({'error': str(e)}), 500


@app.route('/api/guardar-articulos', methods=['POST'])
def guardar_articulos():
    """Guarda los artículos procesados en la BD Laravel"""
    try:
        data = request.get_json()
        if not data:
            return jsonify({'error': 'No hay datos'}), 400
        
        articulos = data.get('articulos', [])
        
        if not articulos:
            return jsonify({'error': 'No hay artículos para guardar'}), 400
        
        # URL del endpoint de Laravel
        laravel_url = 'http://localhost:8000/api/articulos/guardar'
        
        # Enviar a Laravel para guardar en BD
        response = requests.post(laravel_url, json={'articulos': articulos})
        
        print(f"[INFO] {len(articulos)} articulos enviados a Laravel")
        print(f"[DEBUG] Respuesta Laravel: {response.status_code}")
        
        if response.status_code == 200:
            return jsonify(response.json())
        else:
            return jsonify({'error': f'Error al guardar en BD: {response.text}'}), 500
    
    except Exception as e:
        import traceback
        print(f"Error: {e}\n{traceback.format_exc()}")
        return jsonify({'error': str(e)}), 500


if __name__ == '__main__':
    print("[INFO] Servidor Analizador v2 iniciando...")
    print("[INFO] http://localhost:5000")
    app.run(debug=False, host='127.0.0.1', port=5000)
