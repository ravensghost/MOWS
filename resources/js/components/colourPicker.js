// resources/js/components/colourPicker.js

export default function colourPicker(initialValue = '#1a1a1aFF') {
    return {
        value: initialValue || '#1a1a1aFF',

        get currentVal() {
            return this.value || '#1a1a1aFF';
        },

        get colourOnly() {
            const v = this.currentVal;
            if (v.length >= 7 && v.startsWith('#')) {
                return v.substring(0, 7);
            }
            return '#1a1a1a';
        },

        get colorOnly() {
            return this.colourOnly;
        },

        get alphaOnly() {
            const v = this.currentVal;
            let hexAlpha = 'FF';
            if (v.length === 9) {
                hexAlpha = v.substring(7, 9);
            } else if (v.length === 8) {
                hexAlpha = v.substring(6, 8);
            }
            const parsed = parseInt(hexAlpha, 16);
            return Number.isNaN(parsed) ? 255 : parsed;
        },

        updateColour(e) {
            const pickerVal = e.target.value; // #RRGGBB
            const currentHex = this.currentVal;
            let alpha = 'FF';

            if (currentHex.length === 9) {
                alpha = currentHex.substring(7, 9);
            } else if (currentHex.length === 8) {
                alpha = currentHex.substring(6, 8);
            }

            this.value = `${pickerVal}${alpha.toUpperCase()}`;
        },

        updateColor(e) {
            this.updateColour(e);
        },

        updateAlpha(e) {
            const intVal = parseInt(e.target.value, 10);
            const hexAlpha = (Number.isNaN(intVal) ? 255 : intVal)
                .toString(16)
                .toUpperCase()
                .padStart(2, '0');
            this.value = `${this.colourOnly}${hexAlpha}`;
        }
    };
}