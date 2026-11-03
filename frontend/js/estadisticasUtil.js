// Colores y textos del panel de estadísticas (sin dependencias del navegador,
// así se pueden probar con node --test).

// Un mismo color para la misma categoría en los gráficos y en los mapas.
export const COLORES = {
    bajo: "#2e8b57",
    medio: "#d4a900",
    alto: "#e07b00",
    critico: "#c0392b",
    sin_datos: "#9a9a9a",
    reciente: "#2e8b57",
    normal: "#d4a900",
    atrasado: "#e07b00",
    nunca: "#555555"
};

export const NOMBRES_LLENADO = {
    bajo: "Bajo (menos de 40%)",
    medio: "Medio (40% a 65%)",
    alto: "Alto (65% a 85%)",
    critico: "Crítico (85% o más)",
    sin_datos: "Sin recolecciones"
};

export const NOMBRES_LIMPIEZA = {
    reciente: "Hasta 2 días",
    normal: "3 a 6 días",
    atrasado: "7 a 13 días",
    critico: "14 días o más",
    nunca: "Sin registro de recolección"
};

export function colorDe(categoria) {
    return COLORES[categoria] || COLORES.sin_datos;
}

export function textoDias(dias) {
    if (dias === null || dias === undefined) return "Sin registro";
    if (dias === 0) return "Hoy";
    if (dias === 1) return "Hace 1 día";

    return "Hace " + dias + " días";
}

export function textoPorcentaje(valor) {
    if (valor === null || valor === undefined) return "-";

    return Number(valor).toFixed(1).replace(".", ",") + "%";
}

// "2026-11" del mes de una fecha (para el selector de mes).
export function mesDe(fecha) {
    const año = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, "0");

    return año + "-" + mes;
}

// Ancho de una barra (0 a 100) para un valor respecto del máximo.
export function anchoBarra(valor, maximo) {
    if (!maximo || maximo <= 0 || valor === null || valor === undefined) return 0;

    return Math.max(0, Math.min(100, Math.round((valor / maximo) * 100)));
}
