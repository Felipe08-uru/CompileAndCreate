import { apiRequest } from "./apiConfig.js";

const RUTA = "/APIIncidencias/ApiIncidencias.php";

export function getIncidencias() {
    return apiRequest(`${RUTA}/incidencias`, "GET");
}

export function getIncidenciaPorId(id) {
    return apiRequest(`${RUTA}/incidencias/${id}`, "GET");
}

export function crearIncidencia(idContenedor, tipoIncidencia, foto) {
    const formulario = new FormData();
    formulario.append("idContenedor", idContenedor);
    formulario.append("tipoIncidencia", tipoIncidencia);
    formulario.append("foto", foto);

    return apiRequest(`${RUTA}/incidencias`, "POST", formulario);
}

export function eliminarIncidencia(id) {
    return apiRequest(`${RUTA}/incidencias/${id}`, "DELETE");
}

// Devuelve la foto como imagen (blob) para mostrarla; el token va en el encabezado.
export async function getFotoIncidencia(id) {
    const token = localStorage.getItem("token");
    const headers = token ? { Authorization: "Bearer " + token } : {};
    const response = await fetch(`../backend${RUTA}/incidencias/${id}/foto`, { headers });

    if (!response.ok) {
        let error = "No se pudo cargar la foto.";

        try {
            error = (await response.json()).error || error;
        } catch (e) { /* respuesta sin JSON */ }

        return { ok: false, status: response.status, error };
    }

    return { ok: true, status: response.status, url: URL.createObjectURL(await response.blob()) };
}
