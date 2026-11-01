import { apiRequest } from "./apiConfig.js";

const RUTA = "/APIMaquinaria/ApiMaquinaria.php";

export function getCentrosDeAcopio() {
    return apiRequest(`${RUTA}/centros`, "GET");
}

export function getMaquinaria(idCentro = null) {
    const query = idCentro !== null ? `?id_centro=${idCentro}` : "";
    return apiRequest(`${RUTA}/maquinaria${query}`, "GET");
}

export function crearMaquinaria(datos) {
    return apiRequest(`${RUTA}/maquinaria`, "POST", datos);
}

export function actualizarMaquinaria(datos) {
    return apiRequest(`${RUTA}/maquinaria`, "POST", { ...datos, accion: "editar" });
}

export function eliminarMaquinaria(idMaquinaria) {
    return apiRequest(`${RUTA}/maquinaria`, "DELETE", { id_maquinaria: idMaquinaria });
}
