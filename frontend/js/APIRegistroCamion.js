import { apiRequest } from "./apiConfig.js";

const RUTA = "/APIRegistroCamion/ApiRegistroCamion.php";

export function getRegistros() {
    return apiRequest(`${RUTA}/registros`, "GET");
}

export function crearRegistro(datos) {
    return apiRequest(`${RUTA}/registros`, "POST", datos);
}

export function actualizarRegistro(datos) {
    return apiRequest(`${RUTA}/registros`, "POST", { ...datos, accion: "editar" });
}

export function eliminarRegistro(idRegistro) {
    return apiRequest(`${RUTA}/registros`, "DELETE", { id_registro: idRegistro });
}
