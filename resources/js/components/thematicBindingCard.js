// resources/js/components/thematicBindingCard.js
export default function themeVarCard(initialValue = '', varKey = '') {
    return {
        val: String(initialValue ?? '').trim(),
        varKey: String(varKey ?? ''),
        isStructured: false,
        isFontStack: false,
        parts: [],
        units: ['px', 'rem', 'em', '%', 'vh', 'vw', 'ch', 's', 'ms', 'deg'],
        easings: ['linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out'],
        transitionProps: ['all', 'none', 'color', 'background-color', 'border-color', 'opacity', 'transform', 'box-shadow', 'filter'],
        commonFonts: [
            'system-ui', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', 'Inter',
            'ui-sans-serif', 'sans-serif', 'Georgia', 'Cambria', '"Times New Roman"', 'Times', 'serif',
            'ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', '"Liberation Mono"', '"Courier New"', 'monospace'
        ],

        init() {
            if (!this.val) return;

            if (this.varKey.includes('font') && (this.val.includes(',') || /^(sans-serif|serif|monospace|system-ui|Inter|Roboto)/i.test(this.val))) {
                this.isFontStack = true;
                return;
            }

            const tokens = this.val.split(/\s+/);

            if (tokens.length >= 1 && tokens.length <= 4) {
                const parsed = [];
                const dimRegex = /^(-?\d*\.?\d+)(px|rem|em|%|vh|vw|ch|s|ms|deg)?$/i;
                const easingRegex = /^(linear|ease|ease-in|ease-out|ease-in-out)$/i;
                const transPropRegex = /^(all|none|color|background-color|border-color|opacity|transform|box-shadow|filter)$/i;

                for (const token of tokens) {
                    const dimMatch = token.match(dimRegex);
                    const easeMatch = token.match(easingRegex);
                    const propMatch = token.match(transPropRegex);

                    if (dimMatch && (dimMatch[2] || Number(dimMatch[1]) === 0)) {
                        parsed.push({
                            type: 'dimension',
                            num: dimMatch[1],
                            unit: (dimMatch[2] || 'px').toLowerCase()
                        });
                    } else if (easeMatch) {
                        parsed.push({
                            type: 'easing',
                            easing: easeMatch[1].toLowerCase()
                        });
                    } else if (propMatch) {
                        parsed.push({
                            type: 'transitionProp',
                            prop: propMatch[1].toLowerCase()
                        });
                    } else {
                        this.isStructured = false;
                        return;
                    }
                }

                const hasExplicitUnit = tokens.some(t => dimRegex.test(t) && /[a-z%]/i.test(t));
                if (hasExplicitUnit) {
                    this.isStructured = true;
                    this.parts = parsed;
                }
            }
        },

        isPureDimensions() {
            return this.isStructured && this.parts.length > 0 && this.parts.every(p => p.type === 'dimension');
        },

        getSideLabel(index) {
            const len = this.parts.length;
            if (len === 1) return 'All';
            if (len === 2) {
                return index === 0 ? 'Top / Bottom' : 'Left / Right';
            }
            if (len === 3) {
                return ['Top', 'Right / Left', 'Bottom'][index] || `Value ${index + 1}`;
            }
            return ['Top', 'Right', 'Bottom', 'Left'][index] || `Value ${index + 1}`;
        },

        addDimensionPart() {
            if (this.parts.length < 4) {
                const lastPart = this.parts[this.parts.length - 1] || { num: '0', unit: 'px' };
                this.parts.push({
                    type: 'dimension',
                    num: lastPart.num || '0',
                    unit: lastPart.unit || 'px'
                });
                this.updateValue();
            }
        },

        removePart(index) {
            if (this.parts.length > 1) {
                this.parts.splice(index, 1);
                this.updateValue();
            }
        },

        updateValue() {
            if (!this.isStructured) return;
            this.val = this.parts
                .map(p => {
                    if (p.type === 'dimension') {
                        return (p.num !== '' && p.num !== null) ? `${p.num}${p.unit}` : '';
                    }
                    if (p.type === 'transitionProp') {
                        return p.prop || '';
                    }
                    return p.easing || '';
                })
                .filter(Boolean)
                .join(' ');
        },

        initStructuralTomSelect(el) {
            let ts = window.Select.init(el, {
                create: true,
                controlInput: null,
                onChange: (v) => { this.val = v; }
            });

            this.$watch('val', (v) => {
                if (ts && ts.getValue() !== v) ts.setValue(v, true);
            });

            this.$cleanup(() => window.Select.destroy(el));
        },

        initUnitTomSelect(el, index) {
            const currentUnit = this.parts[index]?.unit || 'px';

            el.innerHTML = this.units
                .map(u => `<option value="${u}" ${u === currentUnit ? 'selected' : ''}>${u}</option>`)
                .join('');

            let ts = window.Select.init(el, {
                create: false,
                controlInput: null,
                onChange: (v) => {
                    if (this.parts[index]) {
                        this.parts[index].unit = v;
                        this.updateValue();
                    }
                }
            });

            this.$watch(() => this.parts[index]?.unit, (v) => {
                if (v && ts && ts.getValue() !== v) ts.setValue(v, true);
            });

            this.$cleanup(() => window.Select.destroy(el));
        },

        initEasingTomSelect(el, index) {
            const currentEasing = this.parts[index]?.easing || 'ease';

            el.innerHTML = this.easings
                .map(e => `<option value="${e}" ${e === currentEasing ? 'selected' : ''}>${e}</option>`)
                .join('');

            let ts = window.Select.init(el, {
                create: false,
                controlInput: null,
                onChange: (v) => {
                    if (this.parts[index]) {
                        this.parts[index].easing = v;
                        this.updateValue();
                    }
                }
            });

            this.$watch(() => this.parts[index]?.easing, (v) => {
                if (v && ts && ts.getValue() !== v) ts.setValue(v, true);
            });

            this.$cleanup(() => window.Select.destroy(el));
        },

        initTransitionPropTomSelect(el, index) {
            const currentProp = this.parts[index]?.prop || 'all';

            el.innerHTML = this.transitionProps
                .map(p => `<option value="${p}" ${p === currentProp ? 'selected' : ''}>${p}</option>`)
                .join('');

            let ts = window.Select.init(el, {
                create: true,
                controlInput: null,
                onChange: (v) => {
                    if (this.parts[index]) {
                        this.parts[index].prop = v;
                        this.updateValue();
                    }
                }
            });

            this.$watch(() => this.parts[index]?.prop, (v) => {
                if (v && ts && ts.getValue() !== v) ts.setValue(v, true);
            });

            this.$cleanup(() => window.Select.destroy(el));
        },

        initFontStackTomSelect(el) {
            const currentFonts = this.val
                .split(',')
                .map(f => f.trim())
                .filter(Boolean);

            const allOptions = Array.from(new Set([...currentFonts, ...this.commonFonts]));

            let ts = window.Select.init(el, {
                options: allOptions.map(f => ({ value: f, text: f })),
                items: currentFonts,
                multiple: true,
                create: true,
                persist: false,
                delimiter: ',',
                onChange: (values) => {
                    const arr = Array.isArray(values) ? values : (values ? String(values).split(',') : []);
                    this.val = arr.map(f => f.trim()).filter(Boolean).join(', ');
                }
            });

            this.$cleanup(() => window.Select.destroy(el));
        }
    };
}