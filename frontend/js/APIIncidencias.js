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
