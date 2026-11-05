// Cuando se vuelve a esta página con el botón "Atrás" (o "Adelante") del navegador,
// el navegador suele mostrarla tal como estaba, sin volver a pedir los datos, y los
// cambios hechos en otra pantalla (por ejemplo un contenedor nuevo) no aparecen.
// Este módulo detecta ese caso y recarga la página automáticamente.
window.addEventListener("pageshow", function (evento) {
    var navegacion = performance.getEntriesByType("navigation")[0];
    var vieneDelHistorial = evento.persisted || (navegacion && navegacion.type === "back_forward");

    if (vieneDelHistorial) {
        window.location.reload();
    }
});
