import { apiRequest } from "./apiConfig.js";

const RUTA = "/APIPoligonos/ApiPoligonos.php";

export function getPoligonos() {
    return apiRequest(`${RUTA}/poligonos`, "GET");
}

export function crearPoligono(vertices) {
    return apiRequest(`${RUTA}/poligonos`, "POST", { vertices });
}

export function eliminarPoligono(id) {
    return apiRequest(`${RUTA}/poligonos/${id}`, "DELETE");
}
