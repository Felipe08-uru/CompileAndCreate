import { apiRequest } from "./apiConfig.js";

const RUTA = "/APIEstadisticas/ApiEstadisticas.php";

// Todas son solo para el administrador. El llenado se calcula con el peso que retira
// la cuadrilla en cada recolección: 300 kg equivalen a 100 %.

// Contenedores con más desbordes en un mes (mes = "AAAA-MM", vacío = mes actual).
export function getDesbordados(mes = "", limite = 10) {
    const parametros = new URLSearchParams({ limite });
    if (mes) parametros.set("mes", mes);

    return apiRequest(`${RUTA}/estadisticas/desbordados?${parametros}`, "GET");
}

// Contenedores con mayor llenado promedio en los últimos N días.
export function getPromedio(dias = 30, limite = 10) {
    return apiRequest(`${RUTA}/estadisticas/promedio?dias=${dias}&limite=${limite}`, "GET");
}

// Llenado promedio por zona en los últimos N días.
export function getZonasLlenado(dias = 30) {
    return apiRequest(`${RUTA}/estadisticas/zonas?dias=${dias}`, "GET");
}

// Días desde la última recolección de cada contenedor.
export function getLimpieza() {
    return apiRequest(`${RUTA}/estadisticas/limpieza`, "GET");
}
