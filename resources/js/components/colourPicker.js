export default function colourPicker(initialValue) {
    return {
        value: initialValue || '#1a1a1a',

        get currentVal() {
            return this.value || '#1a1a1a';
        },

        get colorOnly() {
            let v = this.currentVal;
            if (v.length >= 8) {
                return v.substring(0, 7);
            }
            return v.length === 7 ? v : '#1a1a1a';
        },

        get alphaOnly() {
            let v = this.currentVal;
            let hexAlpha = 'FF';
            if (v.length === 9) {
                hexAlpha = v.substring(7, 9);
            } else if (v.length === 8) {
                hexAlpha = v.substring(6, 8);
            }
            let parsed = parseInt(hexAlpha, 16);
            return isNaN(parsed) ? 255 : parsed;
        },

        updateColor(e) {
            let pickerVal = e.target.value; // #RRGGBB
            let currentHex = this.currentVal;
            let alpha = 'FF';

            if (currentHex.length === 9) {
                alpha = currentHex.substring(7, 9);
            } else if (currentHex.length === 8) {
                alpha = currentHex.substring(6, 8);
            }

            this.value = pickerVal + alpha;
        },

        updateAlpha(e) {
            let intVal = parseInt(e.target.value);
            let hexAlpha = intVal.toString(16).toUpperCase().padStart(2, '0');
            let pickerVal = this.colorOnly;

            this.value = pickerVal + hexAlpha;
        }
    };
}