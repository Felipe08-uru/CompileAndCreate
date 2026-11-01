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
