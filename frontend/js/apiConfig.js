const baseUrl = "../backend";

export async function apiRequest(endpoint, method = "GET", body = null) {
    const headers = {};
    const token = localStorage.getItem("token");

    if (token) {
        headers["Authorization"] = "Bearer " + token;
    }

    const config = { method, headers };

    if (body instanceof FormData) {
        config.body = body;
    } else if (body !== null) {
        headers["Content-Type"] = "application/json";
        config.body = JSON.stringify(body);
    }

    const response = await fetch(`${baseUrl}${endpoint}`, config);

    // Se lee como texto para poder informar qué devolvió el servidor si no es JSON
    // (por ejemplo un error de PHP o de Apache), en vez de mostrar un objeto vacío.
    const texto = await response.text();
    let data = {};

    try {
        data = JSON.parse(texto);
    } catch (e) {
        console.error("Respuesta no válida del servidor (HTTP " + response.status + "):", texto);

        data = {
            error: "El servidor devolvió una respuesta inválida (HTTP " + response.status + "). Revise el log de errores de PHP.",
            respuesta: texto.slice(0, 500)
        };
    }

    if (data === null || typeof data !== "object") {
        data = {};
    }

    if (response.status === 401) {
        localStorage.removeItem("token");
        localStorage.removeItem("rol");
    }

    return { ok: response.ok, status: response.status, data };
}
