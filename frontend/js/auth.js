import { getUsuarios } from "./APIUsuarios.js";
import { getContenedores } from "./APIContenedores.js";
import { getRegistros } from "./APIRegistroCamion.js";

export async function verificarSesion(rolEsperado, redirigirA = "index.html") {
    const token = localStorage.getItem("token");
    const rol = localStorage.getItem("rol");

    if (!token || rol !== rolEsperado) {
        cerrarSesion(redirigirA);
        return;
    }

    let comprobacion;

    if (rolEsperado === "Administrador") {
        comprobacion = await getUsuarios();
    } else if (rolEsperado === "Operario") {
        comprobacion = await getRegistros();
    } else {
        comprobacion = await getContenedores();
    }

    if (!comprobacion.ok) {
        cerrarSesion(redirigirA);
    }
}

export function cerrarSesion(redirigirA = "index.html") {
    localStorage.removeItem("token");
    localStorage.removeItem("rol");
    window.location.href = redirigirA;
}
