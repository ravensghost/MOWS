window.getThemeColour = function (varName) {
    const resolved = getComputedStyle(document.documentElement).getPropertyValue(varName).trim();
    return resolved || varName; // Falls back safely if variable is missing
};