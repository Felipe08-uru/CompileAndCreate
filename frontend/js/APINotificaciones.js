import { apiRequest } from "./apiConfig.js";

const RUTA = "/APINotificaciones/ApiNotificaciones.php";

// Devuelve { no_leidas, notificaciones: [...] } con las notificaciones de quien tiene la sesión.
export function getNotificaciones() {
    return apiRequest(`${RUTA}/notificaciones`, "GET");
}

export function marcarNotificacionLeida(id) {
    return apiRequest(`${RUTA}/notificaciones/${id}/leida`, "POST", {});
}

export function marcarTodasLeidas() {
    return apiRequest(`${RUTA}/notificaciones/leidas`, "POST", {});
}
