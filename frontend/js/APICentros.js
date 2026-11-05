import { apiRequest } from "./apiConfig.js";

const RUTA = "/APICentros/ApiCentros.php";

export function getCentros() {
    return apiRequest(`${RUTA}/centros`, "GET");
}

export function crearCentro(datos) {
    return apiRequest(`${RUTA}/centros`, "POST", datos);
}

export function eliminarCentro(idCentro) {
    return apiRequest(`${RUTA}/centros`, "DELETE", { id_centro: idCentro });
}

export function getOperarios() {
    return apiRequest(`${RUTA}/operarios`, "GET");
}

export function getMiCentro() {
    return apiRequest(`${RUTA}/mi-centro`, "GET");
}

export function asociarOperarioCentro(ci, idCentro) {
    return apiRequest(`${RUTA}/operario-centro`, "POST", { ci, id_centro: idCentro });
}
