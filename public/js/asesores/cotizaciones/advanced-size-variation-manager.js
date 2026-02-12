(function() {
    const LETTER_SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
    const NUMBER_SIZES_DAMA = ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26'];
    const NUMBER_SIZES_CABALLERO = ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'];

    const AssignmentType = {
        GENDER: 'Género',
        CUSTOM: 'Sobremedida'
    };

    const Gender = {
        FEMALE: 'DAMA',
        MALE: 'CABALLERO'
    };

    const SizeSystem = {
        LETTERS: 'LETRAS',
        NUMBERS: 'NÚMEROS'
    };

    const SelectionMode = {
        MANUAL: 'manual',
        RANGE: 'rango'
    };

    function getProductoId(productoCard) {
        return productoCard?.dataset?.productoId || '';
    }

    function ensureStore() {
        if (!window.advancedVariationsByProductoId) {
            window.advancedVariationsByProductoId = {};
        }
    }

    function getVariations(productoCard) {
        ensureStore();
        const id = getProductoId(productoCard);
        if (!window.advancedVariationsByProductoId[id]) {
            window.advancedVariationsByProductoId[id] = [];
        }
        return window.advancedVariationsByProductoId[id];
    }

    function setVariations(productoCard, variations) {
        ensureStore();
        window.advancedVariationsByProductoId[getProductoId(productoCard)] = variations;
    }

    function readFabricsFromTable(productoCard) {
        const tbody = productoCard.querySelector('.telas-tbody');
        if (!tbody) return [];

        const rows = Array.from(tbody.querySelectorAll('.fila-tela'));
        return rows.map((row, idx) => {
            const color = (row.querySelector('.color-input')?.value || '').trim();
            const tela = (row.querySelector('.tela-input')?.value || '').trim();
            const referencia = (row.querySelector('.referencia-input')?.value || '').trim();
            return {
                idx,
                color,
                tela,
                referencia,
                isValid: !!(color || tela || referencia)
            };
        }).filter(f => f.isValid);
    }

    function disableOriginalTallasFlow(productoCard) {
        const section = productoCard.querySelector('.talla-tipo-select')?.closest('.producto-section');
        if (!section) return;

        section.dataset.advancedFlowDisabled = '1';
        section.style.opacity = '0.55';
        section.style.pointerEvents = 'none';

        section.querySelectorAll('input, select, button, textarea').forEach(el => {
            el.disabled = true;
        });
    }

    function createModalIfNeeded() {
        if (document.getElementById('advancedSizeVariationModal')) return;

        const overlay = document.createElement('div');
        overlay.id = 'advancedSizeVariationModal';
        overlay.style.cssText = [
            'position: fixed',
            'inset: 0',
            'z-index: 99999',
            'display: none',
            'align-items: center',
            'justify-content: center',
            // Fondo claro + desenfoque del contenido detrás del modal
            'background: rgba(15, 23, 42, 0.52)',
            'backdrop-filter: blur(8px)',
            '-webkit-backdrop-filter: blur(8px)',
            'padding: 16px'
        ].join(';');

        overlay.innerHTML = `
            <div class="advanced-modal" style="background: white; width: 100%; max-width: 860px; border-radius: 28px; overflow: hidden; box-shadow: 0 22px 60px rgba(0,0,0,0.28); transform: scale(0.96); transition: transform 0.18s ease; border: 1px solid rgba(2, 6, 23, 0.06);">
                <div class="advanced-modal-header" style="background: #0055a4; padding: 16px 18px; color: white; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div class="advanced-step-badge" style="width: 38px; height: 38px; border-radius: 14px; background: rgba(255,255,255,0.18); display:flex; align-items:center; justify-content:center; font-weight: 900; font-size: 16px; border: 1px solid rgba(255,255,255,0.35); box-shadow: inset 0 0 0 1px rgba(0,0,0,0.06);">1</div>
                        <div>
                            <div style="font-weight: 900; letter-spacing: -0.4px; text-transform: uppercase; font-size: 16px;">Asistente de Variaciones</div>
                            <div class="advanced-steps" style="display:flex; gap: 6px; margin-top: 8px;"></div>
                        </div>
                    </div>
                    <button type="button" class="advanced-close" style="width: 36px; height: 36px; border-radius: 999px; border: none; background: rgba(255,255,255,0.12); cursor: pointer; color: white; font-size: 20px; font-weight: 900;">×</button>
                </div>

                <div class="advanced-modal-content" style="padding: 16px 16px 10px 16px; min-height: 360px;"></div>

                <div class="advanced-modal-footer" style="padding: 12px 16px 16px 16px; border-top: 1px solid #eef2f7; display:flex; justify-content: space-between; gap: 12px;">
                    <button type="button" class="advanced-back" style="padding: 10px 14px; border-radius: 16px; border: 2px solid #e2e8f0; background: white; color: #334155; font-weight: 900; text-transform: uppercase; font-size: 11px; cursor: pointer;">Atrás</button>
                    <div style="display:flex; gap: 10px;">
                        <button type="button" class="advanced-cancel" style="padding: 10px 14px; border-radius: 16px; border: 2px solid #e2e8f0; background: #f8fafc; color: #334155; font-weight: 900; text-transform: uppercase; font-size: 11px; cursor: pointer;">Cancelar</button>
                        <button type="button" class="advanced-next" style="padding: 10px 14px; border-radius: 16px; border: none; background: #5c56f6; color: white; font-weight: 900; text-transform: uppercase; font-size: 11px; cursor: pointer; box-shadow: 0 12px 24px rgba(92,86,246,0.22);">Siguiente</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeWizard();
            }
        });

        overlay.querySelector('.advanced-close').addEventListener('click', closeWizard);
        overlay.querySelector('.advanced-cancel').addEventListener('click', closeWizard);
    }

    const wizardState = {
        productoCard: null,
        step: 1,
        selectedFabric: null,
        assignmentType: AssignmentType.GENDER,
        selectedGenders: [],
        system: SizeSystem.LETTERS,
        selectionMode: SelectionMode.PREDEFINED,
        selectedSizes: [],
        sizesByGender: {},
        rangeFrom: null,
        rangeTo: null,
        rangeFromByGender: {},
        rangeToByGender: {},
        colors: [],
        currentColor: ''
    };

    function getPrevStep(step) {
        // Flujo Sobremedida: 1 -> 2 -> 6
        if (wizardState.assignmentType === AssignmentType.CUSTOM) {
            if (step === 6) return 2;
            if (step === 2) return 1;
            return Math.max(1, step - 1);
        }

        // Flujo por Género: 1 -> 2 -> 3 -> 4 -> 5 -> 6
        return Math.max(1, step - 1);
    }

    function getNextStep(step) {
        // Flujo Sobremedida: 1 -> 2 -> 6
        if (wizardState.assignmentType === AssignmentType.CUSTOM) {
            if (step === 1) return 2;
            if (step === 2) return 6;
            return Math.min(6, step + 1);
        }

        // Flujo por Género: 1 -> 2 -> 3 -> 4 -> 5 -> 6
        return Math.min(6, step + 1);
    }

    function openWizard(productoCard) {
        createModalIfNeeded();

        const fabrics = readFabricsFromTable(productoCard);
        if (fabrics.length === 0) {
            alert('Por favor completa al menos una fila de color/tela/referencia antes de asignar.');
            return;
        }

        wizardState.productoCard = productoCard;
        wizardState.fabrics = fabrics;
        wizardState.step = fabrics.length === 1 ? 2 : 1;
        wizardState.selectedFabricIdx = fabrics.length === 1 ? fabrics[0].idx : null;
        wizardState.assignmentType = AssignmentType.GENDER;
        wizardState.selectedGenders = [];
        wizardState.activeGender = Gender.FEMALE;
        wizardState.system = SizeSystem.LETTERS;
        wizardState.selectionMode = null;
        wizardState.manualSizes = [];
        wizardState.manualSizesByGender = {};
        wizardState.rangeFrom = '';
        wizardState.rangeTo = '';
        wizardState.rangeFromByGender = {};
        wizardState.rangeToByGender = {};
        wizardState.colors = [];
        wizardState.currentColor = '';

        const overlay = document.getElementById('advancedSizeVariationModal');
        overlay.style.display = 'flex';
        requestAnimationFrame(() => {
            const modal = overlay.querySelector('.advanced-modal');
            if (modal) modal.style.transform = 'scale(1)';
        });

        renderWizard();
    }

    function closeWizard() {
        const overlay = document.getElementById('advancedSizeVariationModal');
        if (!overlay) return;
        const modal = overlay.querySelector('.advanced-modal');
        if (modal) modal.style.transform = 'scale(0.95)';
        overlay.style.display = 'none';
    }

    function renderStepsBar(step, total) {
        const overlay = document.getElementById('advancedSizeVariationModal');
        const steps = overlay.querySelector('.advanced-steps');
        steps.innerHTML = '';

        for (let i = 1; i <= total; i++) {
            const el = document.createElement('div');
            el.style.cssText = [
                'height: 6px',
                'border-radius: 999px',
                'transition: all 0.25s ease',
                i <= step ? 'width: 28px; background: white' : 'width: 8px; background: rgba(255,255,255,0.25)'
            ].join(';');
            steps.appendChild(el);
        }
    }

    function renderWizard() {
        const overlay = document.getElementById('advancedSizeVariationModal');
        if (!overlay) return;

        const badge = overlay.querySelector('.advanced-step-badge');
        const content = overlay.querySelector('.advanced-modal-content');
        const btnBack = overlay.querySelector('.advanced-back');
        const btnNext = overlay.querySelector('.advanced-next');

        const totalSteps = 6;
        badge.textContent = String(wizardState.step);
        renderStepsBar(wizardState.step, totalSteps);

        btnBack.style.visibility = wizardState.step === 1 ? 'hidden' : 'visible';
        btnNext.textContent = wizardState.step === 6 ? 'Agregar' : 'Siguiente';

        btnBack.onclick = () => {
            const prev = getPrevStep(wizardState.step);
            if (prev !== wizardState.step) {
                wizardState.step = prev;
                renderWizard();
            }
        };

        btnNext.onclick = () => {
            if (!validateStep()) return;

            if (wizardState.step < 6) {
                wizardState.step = getNextStep(wizardState.step);
                renderWizard();
                return;
            }

            addVariationsToProducto();
        };

        content.innerHTML = '';
        if (wizardState.step === 1) {
            content.appendChild(renderStepSelectFabric());
        } else if (wizardState.step === 2) {
            content.appendChild(renderStepAssignmentType());
        } else if (wizardState.step === 3) {
            content.appendChild(renderStepGenders());
        } else if (wizardState.step === 4) {
            content.appendChild(renderStepSystemAndSizes());
        } else if (wizardState.step === 5) {
            content.appendChild(renderStepSelectionMode());
        } else if (wizardState.step === 6) {
            content.appendChild(renderStepColors());
        }
    }

    function titleBlock(icon, title, subtitle) {
        const wrap = document.createElement('div');
        wrap.style.cssText = 'margin-bottom: 16px;';
        wrap.innerHTML = `
            <div style="display:flex; align-items:center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 14px; background: #eef6ff; color: #0055a4; display:flex; align-items:center; justify-content:center; font-weight: 900;">
                    <i class="${icon}" style="font-size: 16px;"></i>
                </div>
                <div>
                    <div style="font-size: 20px; font-weight: 900; color: #0f172a; letter-spacing: -0.6px;">${title}</div>
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8; font-weight: 800; margin-top: 3px;">${subtitle}</div>
                </div>
            </div>
        `;
        return wrap;
    }

    function renderStepSelectFabric() {
        const div = document.createElement('div');
        div.appendChild(titleBlock('fas fa-palette', 'Variación Base', 'Paso 1: Selecciona color/tela para asignar variaciones'));

        const grid = document.createElement('div');
        grid.style.cssText = 'display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;';

        wizardState.fabrics.forEach(f => {
            const selected = wizardState.selectedFabricIdx === f.idx;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.style.cssText = [
                'padding: 18px',
                'border-radius: 26px',
                'border: 4px solid ' + (selected ? '#2563eb' : '#f1f5f9'),
                'background: ' + (selected ? '#eff6ff' : '#f8fafc'),
                'cursor: pointer',
                'text-align: left',
                'transition: all 0.15s ease',
                selected ? 'box-shadow: 0 20px 50px rgba(37,99,235,0.12)' : ''
            ].join(';');
            btn.innerHTML = `
                <div style="font-weight: 900; color: #0b3b73; text-transform: uppercase; font-size: 14px;">${(f.tela || '---')}</div>
                <div style="font-weight: 800; color: #64748b; text-transform: uppercase; font-size: 12px; margin-top: 2px;">${(f.color || '---')}</div>
                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(148,163,184,0.25); font-size: 10px; font-weight: 900; color: rgba(0,85,164,0.55); text-transform: uppercase; letter-spacing: 2px;">Ref: ${(f.referencia || 'N/A')}</div>
            `;
            btn.addEventListener('click', () => {
                wizardState.selectedFabricIdx = f.idx;
                renderWizard();
            });
            grid.appendChild(btn);
        });

        div.appendChild(grid);
        return div;
    }

    function renderStepAssignmentType() {
        const div = document.createElement('div');
        div.appendChild(titleBlock('fas fa-sliders-h', 'Tipo de Asignación', 'Paso 2: Género o sobremedida'));

        const row = document.createElement('div');
        row.style.cssText = 'display:flex; gap: 12px; flex-wrap: wrap;';

        [AssignmentType.GENDER, AssignmentType.CUSTOM].forEach(type => {
            const active = wizardState.assignmentType === type;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.style.cssText = [
                'flex: 1',
                'min-width: 280px',
                'padding: 26px 18px',
                'border-radius: 34px',
                'border: 4px solid ' + (active ? '#2563eb' : '#f1f5f9'),
                'background: ' + (active ? '#eff6ff' : '#f8fafc'),
                'cursor: pointer',
                'transition: all 0.15s ease',
                'text-align: center'
            ].join(';');
            btn.innerHTML = `
                <div style="width: 58px; height: 58px; border-radius: 20px; margin: 0 auto 12px auto; display:flex; align-items:center; justify-content:center; border: 4px solid ${active ? '#2563eb' : '#e2e8f0'}; background: ${active ? '#2563eb' : '#ffffff'};">
                    <i class="${type === AssignmentType.GENDER ? 'fas fa-users' : 'fas fa-ruler'}" style="font-size: 20px; color: ${active ? '#ffffff' : '#cbd5e1'};"></i>
                </div>
                <div style="font-weight: 900; font-size: 18px; text-transform: uppercase; color: ${active ? '#1e3a8a' : '#94a3b8'};">${type}</div>
                <div style="font-weight: 800; margin-top: 8px; font-size: 10px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.7; color: #64748b;">${type === AssignmentType.GENDER ? 'Dama/Caballero con tallas' : 'Cantidad fija por color'}</div>
            `;
            btn.addEventListener('click', () => {
                wizardState.assignmentType = type;
                if (type === AssignmentType.GENDER) {
                    wizardState.selectedGenders = [];
                    // Si venimos desde sobremedida y el usuario vuelve a género, llevamos al paso 3
                    if (wizardState.step > 2) {
                        wizardState.step = 3;
                    }
                } else {
                    // Sobremedida NO usa géneros ni tallas: saltar directo a Cantidad + Colores
                    wizardState.step = 6;
                }
                renderWizard();
            });
            row.appendChild(btn);
        });

        div.appendChild(row);
        return div;
    }

    function renderStepGenders() {
        const div = document.createElement('div');
        div.appendChild(titleBlock('fas fa-users', 'Géneros', 'Paso 3: Puedes marcar ambos si comparten tallas'));

        const row = document.createElement('div');
        row.style.cssText = 'display:flex; gap: 12px; flex-wrap: wrap;';

        [Gender.FEMALE, Gender.MALE].forEach(g => {
            const active = wizardState.selectedGenders.includes(g);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.style.cssText = [
                'flex: 1',
                'min-width: 280px',
                'padding: 26px 18px',
                'border-radius: 34px',
                'border: 4px solid ' + (active ? '#2563eb' : '#f1f5f9'),
                'background: ' + (active ? '#eff6ff' : '#f8fafc'),
                'cursor: pointer',
                'transition: all 0.15s ease',
                'text-align: center'
            ].join(';');
            btn.innerHTML = `
                <div style="width: 58px; height: 58px; border-radius: 20px; margin: 0 auto 12px auto; display:flex; align-items:center; justify-content:center; border: 4px solid ${active ? '#2563eb' : '#e2e8f0'}; background: ${active ? '#2563eb' : '#ffffff'};">
                    <i class="${g === Gender.FEMALE ? 'fas fa-female' : 'fas fa-male'}" style="font-size: 22px; color: ${active ? '#ffffff' : '#cbd5e1'};"></i>
                </div>
                <div style="font-weight: 900; font-size: 18px; text-transform: uppercase; color: ${active ? '#1e3a8a' : '#94a3b8'};">${g}</div>
            `;
            btn.addEventListener('click', () => {
                wizardState.selectedGenders = wizardState.selectedGenders.includes(g)
                    ? wizardState.selectedGenders.filter(x => x !== g)
                    : wizardState.selectedGenders.concat([g]);

                // Definir tab activo para selección de tallas
                if (wizardState.selectedGenders.length === 1) {
                    wizardState.activeGender = wizardState.selectedGenders[0];
                } else if (wizardState.selectedGenders.length > 1) {
                    if (!wizardState.selectedGenders.includes(wizardState.activeGender)) {
                        wizardState.activeGender = wizardState.selectedGenders[0];
                    }
                }
                renderWizard();
            });
            row.appendChild(btn);
        });

        div.appendChild(row);
        return div;
    }

    function renderStepSystemAndSizes() {
        const div = document.createElement('div');
        div.appendChild(titleBlock('fas fa-font', 'Sistema de Tallas', 'Paso 4: Selecciona letras o números'));

        const row = document.createElement('div');
        row.style.cssText = 'display:flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;';

        [SizeSystem.LETTERS, SizeSystem.NUMBERS].forEach(sys => {
            const active = wizardState.system === sys;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.style.cssText = [
                'flex: 1',
                'min-width: 280px',
                'padding: 20px 18px',
                'border-radius: 26px',
                'border: 4px solid ' + (active ? '#2563eb' : '#f1f5f9'),
                'background: ' + (active ? '#eff6ff' : '#f8fafc'),
                'cursor: pointer',
                'transition: all 0.15s ease',
                'text-align: left'
            ].join(';');
            btn.innerHTML = `
                <div style="display:flex; align-items:center; gap: 10px;">
                    <div style="width: 44px; height: 44px; border-radius: 16px; display:flex; align-items:center; justify-content:center; background:${active ? '#2563eb' : '#ffffff'}; border: 4px solid ${active ? '#2563eb' : '#e2e8f0'};">
                        <span style="font-weight: 900; color: ${active ? 'white' : '#94a3b8'};">${sys === SizeSystem.LETTERS ? 'ABC' : '123'}</span>
                    </div>
                    <div>
                        <div style="font-weight: 900; text-transform: uppercase; color: ${active ? '#1e3a8a' : '#64748b'};">${sys}</div>
                        <div style="font-weight: 800; font-size: 10px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.7; color: #64748b; margin-top: 4px;">${sys === SizeSystem.LETTERS ? 'XS a XXXXL' : 'Dama y Caballero'}</div>
                    </div>
                </div>
            `;
            btn.addEventListener('click', () => {
                wizardState.system = sys;
                wizardState.selectionMode = null;
                wizardState.manualSizes = [];
                wizardState.manualSizesByGender = {};
                wizardState.rangeFrom = '';
                wizardState.rangeTo = '';
                wizardState.rangeFromByGender = {};
                wizardState.rangeToByGender = {};
                renderWizard();
            });
            row.appendChild(btn);
        });

        div.appendChild(row);

        const hint = document.createElement('div');
        hint.style.cssText = 'padding: 14px 16px; border-radius: 18px; background: #f8fafc; border: 2px solid #f1f5f9; color: #64748b; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; font-size: 10px;';
        hint.textContent = 'En el siguiente paso eliges manual o rango.';
        div.appendChild(hint);

        return div;
    }

    function renderStepSelectionMode() {
        const div = document.createElement('div');
        div.appendChild(titleBlock('fas fa-hand-pointer', 'Selección de Tallas', 'Paso 5: Manual o rango'));

        const container = document.createElement('div');
        container.style.cssText = 'display: grid; grid-template-columns: 1fr; gap: 14px;';

        const modeRow = document.createElement('div');
        modeRow.style.cssText = 'display:flex; gap: 12px; flex-wrap: wrap;';

        [SelectionMode.MANUAL, SelectionMode.RANGE].forEach(m => {
            const active = wizardState.selectionMode === m;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.style.cssText = [
                'flex: 1',
                'min-width: 280px',
                'padding: 18px',
                'border-radius: 26px',
                'border: 4px solid ' + (active ? '#2563eb' : '#f1f5f9'),
                'background: ' + (active ? '#eff6ff' : '#f8fafc'),
                'cursor: pointer',
                'transition: all 0.15s ease',
                'text-align: left'
            ].join(';');
            btn.innerHTML = `
                <div style="font-weight: 900; text-transform: uppercase; color: ${active ? '#1e3a8a' : '#64748b'};">${m === SelectionMode.MANUAL ? 'Manual' : 'Rango (Desde - Hasta)'}</div>
                <div style="font-weight: 800; font-size: 10px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.7; color: #64748b; margin-top: 6px;">${m === SelectionMode.MANUAL ? 'Selecciona tallas una por una' : 'Selecciona un rango de tallas'}</div>
            `;
            btn.addEventListener('click', () => {
                wizardState.selectionMode = m;
                wizardState.manualSizes = [];
                wizardState.rangeFrom = '';
                wizardState.rangeTo = '';
                renderWizard();
            });
            modeRow.appendChild(btn);
        });

        container.appendChild(modeRow);

        if (wizardState.selectionMode === SelectionMode.MANUAL) {
            container.appendChild(renderManualSelector());
        } else if (wizardState.selectionMode === SelectionMode.RANGE) {
            container.appendChild(renderRangeSelector());
        }

        div.appendChild(container);
        return div;
    }

    function getAvailableSizesForWizard(gender) {
        if (wizardState.system === SizeSystem.LETTERS) return LETTER_SIZES;

        // NUMEROS: retornar por género
        if (gender === Gender.FEMALE) return NUMBER_SIZES_DAMA;
        if (gender === Gender.MALE) return NUMBER_SIZES_CABALLERO;
        return [];
    }

    function shouldUseGenderTabsForSizes() {
        return wizardState.assignmentType === AssignmentType.GENDER && wizardState.selectedGenders.length > 1;
    }

    function renderGenderTabs() {
        const tabs = document.createElement('div');
        tabs.style.cssText = 'display:flex; gap: 10px; margin-bottom: 12px; border-bottom: 2px solid #2563eb;';

        wizardState.selectedGenders.forEach((g, idx) => {
            const active = wizardState.activeGender === g;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = g;
            btn.style.cssText = `padding: 8px 12px; background: white; color: #2563eb; border: none; border-bottom: 3px solid ${active ? '#2563eb' : 'white'}; cursor: pointer; font-weight: 900; font-size: 11px; text-transform: uppercase;`;
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                wizardState.activeGender = g;
                renderWizard();
            });
            tabs.appendChild(btn);
        });

        // Asegurar tab activo válido
        if (!wizardState.selectedGenders.includes(wizardState.activeGender)) {
            wizardState.activeGender = wizardState.selectedGenders[0] || Gender.FEMALE;
        }

        return tabs;
    }

    function renderManualSelector() {
        const box = document.createElement('div');
        box.style.cssText = 'padding: 16px; border-radius: 22px; border: 2px solid #e2e8f0; background: white;';

        if (shouldUseGenderTabsForSizes()) {
            box.appendChild(renderGenderTabs());
        }

        const activeGender = shouldUseGenderTabsForSizes() ? wizardState.activeGender : (wizardState.selectedGenders[0] || Gender.FEMALE);
        const sizes = wizardState.system === SizeSystem.LETTERS
            ? getAvailableSizesForWizard(activeGender)
            : getAvailableSizesForWizard(activeGender);

        const grid = document.createElement('div');
        grid.style.cssText = 'display:flex; flex-wrap: wrap; gap: 10px;';

        sizes.forEach(s => {
            const selectedArr = shouldUseGenderTabsForSizes()
                ? (wizardState.manualSizesByGender[activeGender] || [])
                : wizardState.manualSizes;
            const active = selectedArr.includes(s);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = s;
            btn.style.cssText = [
                'padding: 10px 14px',
                'border-radius: 999px',
                'border: 2px solid #2563eb',
                'font-weight: 900',
                'font-size: 12px',
                'cursor: pointer',
                'transition: all 0.15s ease',
                active ? 'background: #2563eb; color: white' : 'background: white; color: #2563eb'
            ].join(';');
            btn.addEventListener('click', () => {
                if (shouldUseGenderTabsForSizes()) {
                    const current = wizardState.manualSizesByGender[activeGender] || [];
                    wizardState.manualSizesByGender[activeGender] = current.includes(s)
                        ? current.filter(x => x !== s)
                        : current.concat([s]);
                } else {
                    wizardState.manualSizes = wizardState.manualSizes.includes(s)
                        ? wizardState.manualSizes.filter(x => x !== s)
                        : wizardState.manualSizes.concat([s]);
                }
                renderWizard();
            });
            grid.appendChild(btn);
        });

        box.appendChild(grid);
        return box;
    }

    function renderRangeSelector() {
        const box = document.createElement('div');
        box.style.cssText = 'padding: 16px; border-radius: 22px; border: 2px solid #e2e8f0; background: white; display:flex; flex-wrap: wrap; gap: 10px; align-items: center;';

        if (shouldUseGenderTabsForSizes()) {
            const tabsWrap = document.createElement('div');
            tabsWrap.style.cssText = 'flex-basis: 100%;';
            tabsWrap.appendChild(renderGenderTabs());
            box.appendChild(tabsWrap);
        }

        const activeGender = shouldUseGenderTabsForSizes() ? wizardState.activeGender : (wizardState.selectedGenders[0] || Gender.FEMALE);
        const sizes = getAvailableSizesForWizard(activeGender);

        const selFrom = document.createElement('select');
        selFrom.style.cssText = 'padding: 10px 12px; border-radius: 14px; border: 2px solid #2563eb; font-weight: 900; color: #2563eb;';
        selFrom.innerHTML = '<option value="">Desde</option>' + sizes.map(s => `<option value="${s}">${s}</option>`).join('');
        selFrom.value = shouldUseGenderTabsForSizes() ? (wizardState.rangeFromByGender[activeGender] || '') : wizardState.rangeFrom;
        selFrom.addEventListener('change', () => {
            if (shouldUseGenderTabsForSizes()) {
                wizardState.rangeFromByGender[activeGender] = selFrom.value;
            } else {
                wizardState.rangeFrom = selFrom.value;
            }
        });

        const sep = document.createElement('span');
        sep.textContent = 'hasta';
        sep.style.cssText = 'font-weight: 900; color: #2563eb; text-transform: uppercase; font-size: 12px;';

        const selTo = document.createElement('select');
        selTo.style.cssText = 'padding: 10px 12px; border-radius: 14px; border: 2px solid #2563eb; font-weight: 900; color: #2563eb;';
        selTo.innerHTML = '<option value="">Hasta</option>' + sizes.map(s => `<option value="${s}">${s}</option>`).join('');
        selTo.value = shouldUseGenderTabsForSizes() ? (wizardState.rangeToByGender[activeGender] || '') : wizardState.rangeTo;
        selTo.addEventListener('change', () => {
            if (shouldUseGenderTabsForSizes()) {
                wizardState.rangeToByGender[activeGender] = selTo.value;
            } else {
                wizardState.rangeTo = selTo.value;
            }
        });

        const hint = document.createElement('div');
        hint.style.cssText = 'flex-basis: 100%; margin-top: 10px; font-weight: 800; font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8;';
        hint.textContent = 'Al agregar, se tomará el rango completo.';

        box.appendChild(selFrom);
        box.appendChild(sep);
        box.appendChild(selTo);
        box.appendChild(hint);

        return box;
    }

    function renderStepColors() {
        const div = document.createElement('div');
        div.appendChild(titleBlock('fas fa-layer-group', 'Colores', 'Paso 6: Agrega colores para crear variaciones'));

        const wrapper = document.createElement('div');
        wrapper.style.cssText = 'display:grid; grid-template-columns: 1fr; gap: 14px;';

        const box = document.createElement('div');
        box.style.cssText = 'padding: 16px; border-radius: 22px; border: 2px solid #e2e8f0; background: white;';

        const row = document.createElement('div');
        row.style.cssText = 'display:flex; gap: 10px; flex-wrap: wrap; align-items:center;';

        const input = document.createElement('input');
        input.type = 'text';
        input.placeholder = 'COLOR...';
        input.value = wizardState.currentColor;
        input.style.cssText = 'flex: 1; min-width: 240px; padding: 12px 14px; border-radius: 16px; border: 2px solid #2563eb; font-weight: 900; text-transform: uppercase; color: #0f172a; outline: none;';
        input.addEventListener('input', () => {
            wizardState.currentColor = input.value;
        });
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                addColor();
            }
        });

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'Agregar color';
        btn.style.cssText = 'padding: 12px 14px; border-radius: 16px; border: none; background: #0055a4; color: white; font-weight: 900; text-transform: uppercase; font-size: 12px; cursor:pointer;';
        btn.addEventListener('click', addColor);

        row.appendChild(input);
        row.appendChild(btn);

        const tags = document.createElement('div');
        tags.style.cssText = 'display:flex; flex-wrap: wrap; gap: 10px; margin-top: 14px;';

        wizardState.colors.forEach(c => {
            const tag = document.createElement('div');
            tag.style.cssText = 'display:inline-flex; align-items:center; gap: 10px; padding: 10px 12px; border-radius: 16px; background: #f8fafc; border: 2px solid #e2e8f0; font-weight: 900; text-transform: uppercase; color: #0f172a;';
            tag.innerHTML = `
                <span style="font-size: 12px;">${c}</span>
                <button type="button" style="border:none; background: transparent; cursor:pointer; font-weight: 900; color:#ef4444; font-size: 16px; line-height: 1;">×</button>
            `;
            tag.querySelector('button').addEventListener('click', () => {
                wizardState.colors = wizardState.colors.filter(x => x !== c);
                renderWizard();
            });
            tags.appendChild(tag);
        });

        box.appendChild(row);
        box.appendChild(tags);
        wrapper.appendChild(box);

        div.appendChild(wrapper);
        return div;

        function addColor() {
            const c = (wizardState.currentColor || '').trim().toUpperCase();
            if (!c) return;
            if (wizardState.colors.includes(c)) {
                wizardState.currentColor = '';
                renderWizard();
                return;
            }
            wizardState.colors = wizardState.colors.concat([c]);
            wizardState.currentColor = '';
            renderWizard();
        }
    }

    function validateStep() {
        if (wizardState.step === 1) {
            if (wizardState.selectedFabricIdx === null) {
                alert('Selecciona una base (color/tela) para continuar.');
                return false;
            }
        }

        if (wizardState.step === 3 && wizardState.assignmentType === AssignmentType.GENDER) {
            if (wizardState.selectedGenders.length === 0) {
                alert('Selecciona al menos un género para continuar.');
                return false;
            }
        }

        if (wizardState.step === 5 && wizardState.assignmentType === AssignmentType.GENDER) {
            if (!wizardState.selectionMode) {
                alert('Selecciona el modo (manual o rango).');
                return false;
            }

            if (wizardState.selectionMode === SelectionMode.MANUAL) {
                if (shouldUseGenderTabsForSizes()) {
                    const faltantes = wizardState.selectedGenders.filter(g => (wizardState.manualSizesByGender[g] || []).length === 0);
                    if (faltantes.length > 0) {
                        alert('Selecciona al menos una talla para cada género.');
                        return false;
                    }
                } else if (wizardState.manualSizes.length === 0) {
                    alert('Selecciona al menos una talla (manual).');
                    return false;
                }
            }

            if (wizardState.selectionMode === SelectionMode.RANGE) {
                if (shouldUseGenderTabsForSizes()) {
                    const invalid = wizardState.selectedGenders.some(g => {
                        const rf = wizardState.rangeFromByGender[g] || '';
                        const rt = wizardState.rangeToByGender[g] || '';
                        if (!rf || !rt) return true;
                        const avail = getAvailableSizesForWizard(g);
                        return avail.indexOf(rf) === -1 || avail.indexOf(rt) === -1;
                    });
                    if (invalid) {
                        alert('Completa un rango válido para cada género.');
                        return false;
                    }
                } else {
                    if (!wizardState.rangeFrom || !wizardState.rangeTo) {
                        alert('Selecciona rango desde y hasta.');
                        return false;
                    }

                    const sizes = getAvailableSizesForWizard(wizardState.selectedGenders[0] || Gender.FEMALE);
                    const fromIdx = sizes.indexOf(wizardState.rangeFrom);
                    const toIdx = sizes.indexOf(wizardState.rangeTo);
                    if (fromIdx === -1 || toIdx === -1) {
                        alert('Rango inválido.');
                        return false;
                    }
                }
            }
        }

        if (wizardState.step === 6) {
            if (wizardState.colors.length === 0) {
                alert('Agrega al menos un color.');
                return false;
            }
        }

        return true;
    }

    function addVariationsToProducto() {
        const productoCard = wizardState.productoCard;
        if (!productoCard) return;

        const fabric = wizardState.fabrics.find(f => f.idx === wizardState.selectedFabricIdx);
        if (!fabric) {
            alert('No se encontró la base seleccionada.');
            return;
        }

        const variations = getVariations(productoCard);

        let sizes = [];
        let sizesByGender = null;
        if (wizardState.assignmentType === AssignmentType.GENDER) {
            if (shouldUseGenderTabsForSizes()) {
                sizesByGender = {};
                wizardState.selectedGenders.forEach(g => {
                    const available = getAvailableSizesForWizard(g);
                    if (wizardState.selectionMode === SelectionMode.MANUAL) {
                        sizesByGender[g] = (wizardState.manualSizesByGender[g] || []).slice();
                    } else if (wizardState.selectionMode === SelectionMode.RANGE) {
                        const rf = wizardState.rangeFromByGender[g];
                        const rt = wizardState.rangeToByGender[g];
                        const fromIdx = available.indexOf(rf);
                        const toIdx = available.indexOf(rt);
                        const min = Math.min(fromIdx, toIdx);
                        const max = Math.max(fromIdx, toIdx);
                        sizesByGender[g] = available.slice(min, max + 1);
                    }
                });
            } else {
                const g = wizardState.selectedGenders[0] || Gender.FEMALE;
                const available = getAvailableSizesForWizard(g);
                if (wizardState.selectionMode === SelectionMode.MANUAL) {
                    sizes = wizardState.manualSizes.slice();
                } else if (wizardState.selectionMode === SelectionMode.RANGE) {
                    const fromIdx = available.indexOf(wizardState.rangeFrom);
                    const toIdx = available.indexOf(wizardState.rangeTo);
                    const min = Math.min(fromIdx, toIdx);
                    const max = Math.max(fromIdx, toIdx);
                    sizes = available.slice(min, max + 1);
                }
            }
        }

        const newItems = wizardState.colors.map(color => {
            return {
                id: Math.random().toString(36).substr(2, 9),
                fabricIdx: fabric.idx,
                fabricColor: fabric.color,
                fabricTela: fabric.tela,
                fabricReferencia: fabric.referencia,
                assignmentType: wizardState.assignmentType,
                genders: wizardState.assignmentType === AssignmentType.GENDER ? wizardState.selectedGenders.slice() : null,
                system: wizardState.assignmentType === AssignmentType.GENDER ? wizardState.system : null,
                sizes: wizardState.assignmentType === AssignmentType.GENDER ? sizes : null,
                sizesByGender: wizardState.assignmentType === AssignmentType.GENDER ? sizesByGender : null,
                color
            };
        });

        const updated = variations.concat(newItems);
        setVariations(productoCard, updated);
        renderVariationsBelowTable(productoCard);

        disableOriginalTallasFlow(productoCard);

        closeWizard();
    }

    function renderVariationsBelowTable(productoCard) {
        const container = productoCard.querySelector('.advanced-variations-container');
        const list = productoCard.querySelector('.advanced-variations-list');
        if (!container || !list) return;

        const items = getVariations(productoCard);
        list.innerHTML = '';

        if (!items.length) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'block';

        items.forEach(v => {
            const card = document.createElement('div');
            card.style.cssText = 'background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 18px; padding: 14px; position: relative; overflow: hidden; transition: border-color 0.15s ease;';

            const top = document.createElement('div');
            top.style.cssText = 'display:flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;';
            top.innerHTML = `
                <span style="font-size: 10px; font-weight: 900; text-transform: uppercase; background: #0055a4; color: white; padding: 6px 10px; border-radius: 10px;">${v.color || 'Sin Color'}</span>
                <span style="font-size: 10px; font-weight: 900; text-transform: uppercase; background: white; border: 1px solid #e2e8f0; color: #475569; padding: 6px 10px; border-radius: 10px;">${v.fabricTela || 'Sin Tela'}</span>
                ${v.assignmentType === AssignmentType.CUSTOM ? '<span style="font-size: 10px; font-weight: 900; text-transform: uppercase; background: #ffedd5; border: 1px solid #fed7aa; color: #c2410c; padding: 6px 10px; border-radius: 10px;">Sobremedida</span>' : ''}
            `;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = '×';
            remove.style.cssText = 'position:absolute; top: 10px; right: 10px; width: 32px; height: 32px; border-radius: 999px; border:none; background: white; cursor:pointer; font-weight: 900; color: #94a3b8; box-shadow: 0 6px 16px rgba(0,0,0,0.08);';
            remove.addEventListener('click', () => {
                const remaining = getVariations(productoCard).filter(x => x.id !== v.id);
                setVariations(productoCard, remaining);
                renderVariationsBelowTable(productoCard);
            });

            const mid = document.createElement('div');
            if (v.assignmentType === AssignmentType.GENDER) {
                mid.style.cssText = 'margin-bottom: 10px;';
                const tieneAgrupacionPorGenero = !!(v.sizesByGender && Object.keys(v.sizesByGender).length);
                if (!tieneAgrupacionPorGenero) {
                    mid.innerHTML = `
                        <div style="display:flex; align-items:center; gap: 8px; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; color:#64748b; margin-bottom: 10px;">
                            <i class="fas fa-users" style="color:#60a5fa;"></i>
                            ${(v.genders || []).join(' / ')}
                        </div>
                    `;
                }

                if (tieneAgrupacionPorGenero) {
                    (v.genders || []).forEach(g => {
                        const group = document.createElement('div');
                        group.style.cssText = 'margin-bottom: 10px;';

                        const title = document.createElement('div');
                        title.style.cssText = 'font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; color: #2563eb; margin-bottom: 8px;';
                        title.textContent = g;
                        group.appendChild(title);

                        const sizesGrid = document.createElement('div');
                        sizesGrid.style.cssText = 'display:grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 8px;';
                        ((v.sizesByGender[g] || [])).forEach(s => {
                            const pill = document.createElement('div');
                            pill.textContent = s;
                            pill.style.cssText = 'padding: 8px 0; background: white; border: 1px solid #e2e8f0; border-radius: 14px; text-align:center; font-weight: 900; color:#1d4ed8; font-size: 11px;';
                            sizesGrid.appendChild(pill);
                        });
                        group.appendChild(sizesGrid);
                        mid.appendChild(group);
                    });
                } else {
                    const sizesGrid = document.createElement('div');
                    sizesGrid.style.cssText = 'display:grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 8px;';
                    (v.sizes || []).forEach(s => {
                        const pill = document.createElement('div');
                        pill.textContent = s;
                        pill.style.cssText = 'padding: 8px 0; background: white; border: 1px solid #e2e8f0; border-radius: 14px; text-align:center; font-weight: 900; color:#1d4ed8; font-size: 11px;';
                        sizesGrid.appendChild(pill);
                    });
                    mid.appendChild(sizesGrid);
                }
            } else {
                mid.style.cssText = 'margin-bottom: 10px; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; color:#64748b;';
                mid.innerHTML = `<div style="display:flex; align-items:center; gap: 8px;"><i class="fas fa-ruler-combined" style="color:#f97316;"></i> Sobremedida</div>`;
            }

            const bottom = document.createElement('div');
            bottom.style.cssText = 'margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(148,163,184,0.30); display:flex; justify-content: space-between; align-items:center; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8;';
            bottom.innerHTML = `
                <span>Ref: ${v.fabricReferencia || 'N/A'}</span>
                ${v.assignmentType === AssignmentType.GENDER ? `<span style="background:#e2e8f0; color:#475569; padding: 2px 8px; border-radius: 8px;">${v.system === SizeSystem.LETTERS ? 'ABC' : '123'}</span>` : '<span></span>'}
            `;

            card.appendChild(remove);
            card.appendChild(top);
            card.appendChild(mid);
            card.appendChild(bottom);

            list.appendChild(card);
        });
    }

    function handleAsignarClick(e) {
        const btn = e.target.closest('.btn-asignar-colores-tallas');
        if (!btn) return;

        const productoCard = btn.closest('.producto-card');
        if (!productoCard) return;

        openWizard(productoCard);
    }

    function init() {
        document.addEventListener('click', handleAsignarClick);

        // Re-render si ya existen variaciones (ej: re-hidratar por alguna razón)
        document.querySelectorAll('.producto-card').forEach(card => {
            renderVariationsBelowTable(card);
        });
    }

    window.AdvancedSizeVariationManager = {
        setVariationsByProductoId: function(productoId, variations) {
            ensureStore();
            window.advancedVariationsByProductoId[String(productoId)] = Array.isArray(variations) ? variations : [];
        },
        renderForProductoCard: function(productoCard) {
            renderVariationsBelowTable(productoCard);
        },
        disableOriginalTallasFlow: function(productoCard) {
            disableOriginalTallasFlow(productoCard);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
