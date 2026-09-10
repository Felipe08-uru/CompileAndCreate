import { apiRequest } from "./apiConfig.js";

const RUTA = "/APIUsuarios/ApiUsuarios.php";

export function iniciarSesion(datos) {
    return apiRequest(`${RUTA}/login`, "POST", datos);
}

export function registrarUsuario(datos) {
    return apiRequest(`${RUTA}/registro`, "POST", datos);
}

export function getUsuarios() {
    return apiRequest(`${RUTA}/usuarios`, "GET");
}

export function getUsuarioPorCi(ci) {
    return apiRequest(`${RUTA}/usuarios/${ci}`, "GET");
}

export function crearUsuario(datos) {
    return apiRequest(`${RUTA}/usuarios`, "POST", datos);
}

export function actualizarUsuario(datos) {
    return apiRequest(`${RUTA}/usuarios`, "POST", { ...datos, accion: "editar" });
}

export function eliminarUsuario(ci) {
    return apiRequest(`${RUTA}/usuarios`, "DELETE", { ci });
}
