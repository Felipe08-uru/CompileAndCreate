import { apiRequest } from "./apiConfig.js";

const RUTA = "/APIAsignaciones/ApiAsignaciones.php";

// Administrador: todos los camiones con su zona, tipo de ruta, cuadrilla y ruta activa.
export function getAsignaciones() {
    return apiRequest(`${RUTA}/asignaciones`, "GET");
}

// Cuadrilla: su camión, su zona, su ruta actual y su historial.
export function getMiAsignacion() {
    return apiRequest(`${RUTA}/asignaciones/mia`, "GET");
}

/**
 * @param {{matricula: string, id_poligono: (string|number|null), tipo_ruta: (string|null), ci_cuadrilla: (string|null)}} datos
 */
export function guardarAsignacion(datos) {
    return apiRequest(`${RUTA}/asignaciones`, "POST", datos);
}

export function eliminarAsignacion(matricula) {
    return apiRequest(`${RUTA}/asignaciones`, "DELETE", { matricula });
}

// Administrador: asigna (o quita, con matricula = null) el camión de una cuadrilla.
export function asignarCamionACuadrilla(ciCuadrilla, matricula) {
    return apiRequest(`${RUTA}/asignaciones/cuadrilla`, "POST", {
        ci_cuadrilla: ciCuadrilla,
        matricula
    });
}
