// resources/js/utility/themeColour.js

/**
 * Global Theme Colour Utility
 * Resolves active CSS custom properties from the root document element.
 */
window.getThemeColour = function (varName) {
    const resolved = getComputedStyle(document.documentElement).getPropertyValue(varName).trim();
    return resolved || varName; // Falls back safely if the custom property is missing
};

export default window.getThemeColour;