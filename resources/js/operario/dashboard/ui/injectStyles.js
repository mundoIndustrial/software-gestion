export function injectDashboardStyles() {
    if (document.getElementById('operario-dashboard-injected-styles')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'operario-dashboard-injected-styles';
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes cardRemove {
            0% { opacity: 1; transform: scale(1); max-height: 800px; margin-bottom: 1.5rem; }
            100% { opacity: 0; transform: translateX(100px) scale(0.9); max-height: 0; padding-top: 0; padding-bottom: 0; margin-top: 0; margin-bottom: 0; overflow: hidden; }
        }

        @keyframes fadeInCard {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .orden-card-simple:not(.page-hidden) {
            animation: fadeInCard 0.4s ease-out forwards;
        }

        .card-animate-remove {
            animation: cardRemove 0.6s forwards ease-in-out;
            pointer-events: none;
        }

        /* Estilos para cards completados por costurero */
        .card-completado-costura {
            background-color: #e3f2fd !important;
            border-left: 4px solid #2196f3 !important;
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.1) !important;
        }

        /* Borde verde para recibos reflectivos */
        .borde-reflectivo {
            border-left: 4px solid #4caf50 !important;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2) !important;
        }

        /* Botones para costureros */
        .btn-completar-costura {
            background: linear-gradient(135deg, #2196f3, #1976d2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-completar-costura:hover {
            background: linear-gradient(135deg, #1976d2, #1565c0);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }

        .btn-deshacer-costura {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-deshacer-costura:hover {
            background: linear-gradient(135deg, #f57c00, #ef6c00);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }

        /* Badge para estado completado de costura */
        .badge-completado-costura {
            background: #2196f3;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-completado-costura.is-on {
            background: #1976d2;
            box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
        }

        .badge-estado-completado {
            background: #e3f2fd !important;
            color: #0d47a1 !important;
            border: 1px solid #90caf9 !important;
        }

        /* Posicionamiento especial para mobile */
        .badge-completado-costura.mobile-top-right {
            position: absolute;
            top: 8px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            font-size: 0.65rem;
            padding: 0.2rem 0.6rem;
            display: none;
        }

        /* Sección del botón ver recibo para mobile */
        .mobile-ver-recibo-section {
            display: none;
            margin: 8px 0;
            text-align: center;
        }

        .mobile-ver-recibo-section .btn-ver-recibos {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Responsive para mobile */
        @media (max-width: 768px) {
            .orden-top {
                position: relative;
            }

            .mobile-ver-recibo-section {
                display: block;
            }

            .badge-completado-costura.mobile-top-right {
                display: inline-block;
            }

            .orden-numero-section .badge-completado-costura:not(.mobile-top-right) {
                display: none;
            }

            .mobile-ver-recibo-section .btn-ver-recibos:not(.vista-costura-mobile) {
                border-radius: 12px !important;
                border: 1px solid #e0e0e0 !important;
                box-shadow: none !important;
            }

            .orden-buttons .btn-ver-recibos:not(.mobile-under-state):not(.vista-costura-mobile) {
                display: none;
            }

            body[data-user-role="vista-costura"] .mobile-ver-recibo-section {
                display: none !important;
            }

            body[data-user-role="vista-costura"] .orden-buttons .btn-ver-recibos {
                display: inline-flex !important;
            }

            .btn-completar-costura,
            .btn-deshacer-costura {
                padding: 0.75rem;
                font-size: 0.8rem;
                min-height: 44px;
                justify-content: center;
            }

            .btn-completar-costura span,
            .btn-deshacer-costura span {
                font-size: 1.2rem;
            }
        }

        /* Paginación */
        .page-hidden {
            display: none !important;
        }

        .dashboard-pagination-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            margin: 2rem 0;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(8px);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .pagination-info {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .pagination-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .pagination-btn {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background: white;
            color: #1e293b;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .pagination-btn:hover:not(.disabled):not(.active) {
            background: #f1f5f9;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

        .pagination-btn.active {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .pagination-btn.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: #f8fafc;
        }
    `;

    document.head.appendChild(style);
}

