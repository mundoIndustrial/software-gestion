<x-modal name="order-detail" :show="false" maxWidth="2xl">
    <div class="order-detail-modal-container">
        <div class="order-detail-card">
            <img src="{{ asset('images/logo.png') }}" alt="Mundo Industrial Logo" class="order-logo">
            <div id="order-date" class="order-date">
                <div class="fec-label">FECHA</div>
                <div class="date-boxes">
                    <div class="date-box day-box"></div>
                    <div class="date-box month-box"></div>
                    <div class="date-box year-box"></div>
                </div>
            </div>
            <div id="order-asesora" class="order-asesora">ASESORA: <span id="asesora-value"></span></div>
            <div id="order-forma-pago" class="order-forma-pago">FORMA DE PAGO: <span id="forma-pago-value"></span></div>
            <div id="order-cliente" class="order-cliente">CLIENTE: <span id="cliente-value"></span></div>
            <div id="order-descripcion" class="order-descripcion">
                <div id="descripcion-text"></div>
            </div>
            <h2 class="receipt-title">RECIBO DE COSTURA</h2>
            <div class="arrow-container">
                <button id="prev-arrow" class="arrow-btn" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button id="next-arrow" class="arrow-btn" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
            <div id="order-pedido" class="pedido-number"></div>

            <div class="separator-line"></div>

            <div class="signature-section">
                <div class="signature-field">
                    <span>ENCARGADO DE ORDEN:</span>
                    <span id="encargado-value"></span>
                </div>
                <div class="vertical-separator"></div>
                <div class="signature-field">
                    <span>PRENDAS ENTREGADAS:</span>
                    <span id="prendas-entregadas-value"></span>
                    <a href="#" id="ver-entregas" style="color: red; font-weight: bold;">VER ENTREGAS</a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .order-detail-modal-container {
            background: white;
            min-height: 760px;
            max-height: 90vh;
            max-width: 90vw;
            width: 90%;
            overflow-y: auto;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            padding: 1.5cm;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 9999;
            transform: translateY(15px) scale(0.75);
            transform-origin: top center;
        }

        .order-detail-card {
            width: calc(110% - 3cm);
            height: calc(110% - 3cm);
            min-height: 600px;
            border: 4px solid #000000;
            border-radius: 20px;
            padding: 30px;
            background: white;
            position: absolute;
        }

        .order-content {
            width: 100%;
            height: 100%;
        }

        .order-content h2 {
            margin: 0 0 20px 0;
            font-size: 24px;
            font-weight: 600;
        }

        .order-logo {
            display: block;
            margin: -70px auto 20px auto;
            width: 200px;
            height: auto;
        }

        .order-date {
            position: absolute;
            top: 110px;
            left: 20px;
            background: black;
            border-radius: 10px;
            padding: 8px;
            color: white;
            text-align: center;
            width: 180px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }

        .fec-label {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .date-boxes {
            display: flex;
            justify-content: space-between;
            gap: 4px;
        }

        .date-box {
            background: white;
            color: black;
            border-radius: 6px;
            width: 53px;
            height: 32px;
            line-height: 28px;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
        }

        .receipt-title {
            position: absolute;
            top: 110px;
            right: 20px;
            font-weight: 800;
            font-size: 20px;
            text-transform: uppercase;
            font-family: Arial, sans-serif;
            margin: 20px 0;
            color: #000;
        }

        .pedido-number {
            position: absolute;
            top: 150px;
            right: 130px;
            font-weight: 800;
            font-size: 20px;
            text-transform: uppercase;
            font-family: Arial, sans-serif;
            margin: 10px 0 20px 0;
            color: #ff0000ff;
        }

        .order-asesora {
            position: absolute;
            top: 190px;
            left: 20px;
            font-weight: bold;
            font-size: 14px;
            color: #000;
            max-width: 45%;
            word-wrap: break-word;
            white-space: normal;
            line-height: 1.3;
        }

        .order-forma-pago {
            position: absolute;
            top: 210px;
            left: 20px;
            font-weight: bold;
            font-size: 14px;
            color: #000;
            max-width: 45%;
            word-wrap: break-word;
            white-space: normal;
            line-height: 1.3;
        }

        .order-cliente {
            position: absolute;
            top: 210px;
            right: 90px;
            font-weight: bold;
            font-size: 14px;
            color: #000;
            max-width: 45%;
            word-wrap: break-word;
            white-space: normal;
            line-height: 1.3;
            text-align: right;
        }

        .order-descripcion {
            position: absolute;
            top: 240px;
            left: 50%;
            transform: translateX(-50%);
            text-align: flex;
            font-size: 14px;
            color: #000;
            width: 80%;
            white-space: pre-line;
        }

        .arrow-btn {
            background: none;
            border: none;
            color: red;
            cursor: pointer;
            padding: 5px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .arrow-btn:hover {
            transform: scale(1.15);
            background-color: rgba(255, 0, 0, 0.1);
        }

        .arrow-btn svg {
            width: 24px;
            height: 24px;
        }

        #descripcion-text {
            width: 100%;
            text-align: left;
        }

        .arrow-container {
            position: absolute;
            top: 85px;
            right: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .signature-section {
            position: absolute;
            bottom: 0px;
            left: 20px;
            right: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .vertical-separator {
            width: 3px;
            background-color: #000;
            align-self: stretch;
            margin: 0 20px;
            height: 60px;
        }

        .separator-line {
            position: absolute;
            bottom: 60px;
            left: 0;
            right: 0;
            height: 4px;
            background-color: #000000;
        }

        .signature-field {
            font-weight: bold;
            font-size: 14px;
            color: #000;
            text-align: left;
            width: 45%;
            line-height: 1.4;
        }

        .signature-field span:first-child {
            display: block;
            margin-bottom: 2px;
        }
    </style>
</x-modal>