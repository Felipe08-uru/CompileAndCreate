import { getUsuarios } from "./APIUsuarios.js";
import { getContenedores } from "./APIContenedores.js";

export async function verificarSesion(rolEsperado, redirigirA = "index.html") {
    const token = localStorage.getItem("token");
    const rol = localStorage.getItem("rol");

    if (!token || rol !== rolEsperado) {
        cerrarSesion(redirigirA);
        return;
    }

    const comprobacion =
        rolEsperado === "Administrador" ? await getUsuarios() : await getContenedores();

    if (!comprobacion.ok) {
        cerrarSesion(redirigirA);
    }
}

export function cerrarSesion(redirigirA = "index.html") {
    localStorage.removeItem("token");
    localStorage.removeItem("rol");
    window.location.href = redirigirA;
}
