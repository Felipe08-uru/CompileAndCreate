import { apiRequest } from "./apiConfig.js";

const RUTA = "/APIContenedores/ApiContenedores.php";

export function getContenedores() {
    return apiRequest(`${RUTA}/contenedores`, "GET");
}

export function getContenedorPorId(id) {
    return apiRequest(`${RUTA}/contenedores/${id}`, "GET");
}

export function crearContenedor(datos) {
    return apiRequest(`${RUTA}/contenedores`, "POST", datos);
}

export function eliminarContenedor(idContenedor) {
    return apiRequest(`${RUTA}/contenedores`, "DELETE", { id_contenedor: idContenedor });
}
