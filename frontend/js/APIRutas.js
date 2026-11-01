import { apiRequest } from "./apiConfig.js";

const RUTA = "/APIRutas/ApiRutas.php";

// Administrador: todas las rutas.
export function getRutas() {
    return apiRequest(`${RUTA}/rutas`, "GET");
}

// Cuadrilla: ruta sin finalizar de su camión ({ ruta: null } si no tiene).
export function getRutaActiva() {
    return apiRequest(`${RUTA}/rutas/activa`, "GET");
}

export function getRuta(idRuta) {
    return apiRequest(`${RUTA}/rutas/${idRuta}`, "GET");
}

// Administrador: crea una ruta de una zona (el camión es opcional).
export function crearRuta(idPoligono, tipo, matricula = null) {
    return apiRequest(`${RUTA}/rutas`, "POST", { id_poligono: idPoligono, tipo, matricula });
}

// Administrador: asigna (o quita, con matricula = null) el camión de una ruta pendiente.
export function asignarCamionARuta(idRuta, matricula) {
    return apiRequest(`${RUTA}/rutas/${idRuta}/camion`, "POST", { matricula });
}

// Cuadrilla: informa el peso (kg) del contenedor de la parada indicada.
export function registrarPeso(idRuta, orden, peso) {
    return apiRequest(`${RUTA}/rutas/${idRuta}/peso`, "POST", { orden, peso });
}

export function eliminarRuta(idRuta) {
    return apiRequest(`${RUTA}/rutas/${idRuta}`, "DELETE");
}
