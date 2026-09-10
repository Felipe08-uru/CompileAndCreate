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

    let data = {};
    try {
        data = await response.json();
    } catch (e) {
        data = {};
    }

    if (response.status === 401) {
        localStorage.removeItem("token");
        localStorage.removeItem("rol");
    }

    return { ok: response.ok, status: response.status, data };
}
