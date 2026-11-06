// Menú hamburguesa (arriba a la izquierda). Toma las opciones del banner
// principal (.nav) y, en las páginas con sesión, agrega el botón de cerrar sesión.
(function () {
    var header = document.querySelector(".header");
    var nav = header ? header.querySelector(".nav") : null;

    if (!header || !nav) return;

    var enlaces = Array.prototype.slice.call(nav.querySelectorAll("a"));
    var tieneSesion = nav.querySelector("button") !== null;
    var pagina = location.pathname.split("/").pop() || "index.html";

    // --- Botón hamburguesa ---
    var boton = document.createElement("button");
    boton.type = "button";
    boton.className = "menu-toggle";
    boton.setAttribute("aria-label", "Abrir menú");
    boton.setAttribute("aria-expanded", "false");
    boton.setAttribute("aria-controls", "menuLateral");
    boton.innerHTML = "<span></span><span></span><span></span>";
    header.insertBefore(boton, header.firstChild);

    // --- Panel lateral ---
    var fondo = document.createElement("div");
    fondo.className = "menu-fondo";

    var panel = document.createElement("aside");
    panel.className = "menu-lateral";
    panel.id = "menuLateral";
    panel.setAttribute("aria-hidden", "true");

    var html =
        "<div class='menu-cabecera'><strong>SiGeRU</strong>" +
        "<button type='button' class='menu-cerrar' aria-label='Cerrar menú'>&times;</button></div>" +
        "<nav class='menu-enlaces'>";

    enlaces.forEach(function (a) {
        var destino = a.getAttribute("href") || "#";
        var actual = destino.split("#")[0] === pagina ? " class='activo'" : "";
        html += "<a href='" + destino + "'" + actual + ">" + a.textContent + "</a>";
    });

    html += "</nav><div class='menu-opciones'></div>";

    if (tieneSesion) {
        html +=
            "<div class='menu-pie'>" +
            "<button type='button' class='menu-salir'>" +
            "<svg viewBox='0 0 24 24' width='20' height='20' aria-hidden='true'>" +
            "<path fill='currentColor' d='M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3zm6.6 4.6L15.2 9l2.6 2.6H9v2h8.8L15.2 16l1.4 1.4L21.9 12l-5.3-5.4z'/></svg>" +
            "<span>Cerrar sesión</span></button></div>";
    }

    panel.innerHTML = html;

    document.body.appendChild(fondo);
    document.body.appendChild(panel);

    // Opciones propias de la página (según el rol): secciones y acciones marcadas con data-menu.
    function llenarOpciones() {
        var cont = panel.querySelector(".menu-opciones");
        var elementos = Array.prototype.slice.call(document.querySelectorAll("[data-menu]"));
        var html = "";
        var grupoActual = null;

        cont.innerHTML = "";
        cont._items = [];

        elementos.forEach(function (el) {
            var grupo = el.getAttribute("data-menu-grupo");

            if (grupo !== null && grupo !== grupoActual) {
                grupoActual = grupo;
                html += "<h4>" + grupo + "</h4>";
            }

            html += "<a href='#' data-i='" + cont._items.length + "'>" + el.getAttribute("data-menu") + "</a>";
            cont._items.push(el);
        });

        cont.innerHTML = html;

        cont.querySelectorAll("a").forEach(function (a) {
            a.addEventListener("click", function (e) {
                e.preventDefault();
                var el = cont._items[parseInt(a.getAttribute("data-i"), 10)];
                cerrar();
                ejecutar(el);
            });
        });
    }

    function ejecutar(el) {
        var tag = el.tagName;

        if (tag === "OPTION") {
            var select = el.parentNode;
            select.value = el.value;
            select.dispatchEvent(new Event("change", { bubbles: true }));
            select.scrollIntoView({ behavior: "smooth", block: "start" });
        } else if (tag === "A" || tag === "BUTTON") {
            el.click();
        } else {
            el.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }

    function abrir() {
        llenarOpciones();
        panel.classList.add("abierto");
        fondo.classList.add("abierto");
        panel.setAttribute("aria-hidden", "false");
        boton.setAttribute("aria-expanded", "true");
        document.body.classList.add("menu-abierto");
    }

    function cerrar() {
        panel.classList.remove("abierto");
        fondo.classList.remove("abierto");
        panel.setAttribute("aria-hidden", "true");
        boton.setAttribute("aria-expanded", "false");
        document.body.classList.remove("menu-abierto");
    }

    boton.addEventListener("click", function () {
        if (panel.classList.contains("abierto")) cerrar(); else abrir();
    });

    fondo.addEventListener("click", cerrar);
    panel.querySelector(".menu-cerrar").addEventListener("click", cerrar);

    panel.querySelectorAll(".menu-enlaces a").forEach(function (a) {
        a.addEventListener("click", cerrar);
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") cerrar();
    });

    var salir = panel.querySelector(".menu-salir");

    if (salir) {
        salir.addEventListener("click", function () {
            cerrar();

            if (typeof window.cerrarSesion === "function") {
                window.cerrarSesion();
            } else {
                try { localStorage.clear(); } catch (e) { }
                location.href = "index.html";
            }
        });
    }
})();
