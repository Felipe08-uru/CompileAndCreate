export function activarFlechaScroll() {
    const flechaScroll = document.getElementById("flechaScroll");

    if (!flechaScroll) {
        return;
    }

    flechaScroll.addEventListener("click", function () {
        window.scrollBy({ top: window.innerHeight, behavior: "smooth" });
    });

    function actualizarFlecha() {
        const llegoAlFinal =
            window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 10;

        flechaScroll.style.display = llegoAlFinal ? "none" : "flex";
    }

    window.addEventListener("scroll", actualizarFlecha);
    window.addEventListener("resize", actualizarFlecha);
    actualizarFlecha();
}
