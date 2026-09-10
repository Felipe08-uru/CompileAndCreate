import { apiRequest } from "./apiConfig.js";

const RUTA = "/APICamiones/ApiCamiones.php";

export function getCamiones() {
    return apiRequest(`${RUTA}/camiones`, "GET");
}

export function getCamionPorMatricula(matricula) {
    return apiRequest(`${RUTA}/camiones/${matricula}`, "GET");
}

export function crearCamion(datos) {
    return apiRequest(`${RUTA}/camiones`, "POST", datos);
}

export function eliminarCamion(matricula) {
    return apiRequest(`${RUTA}/camiones`, "DELETE", { matricula });
}
