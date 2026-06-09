var cex = document.getElementById("CollapsingNavbar");
var cbt = document.getElementById("submenuButton");
function rClass(x) {
    if (!cex || !cbt) {
        return;
    }
    if (x.matches) {
        cex.classList.remove("show");
        cbt.classList.add("showButton");
        cbt.classList.remove("hideButton");
    } else {
        cex.classList.add("show");
        cbt.classList.remove("showButton");
        cbt.classList.add("hideButton");
    }
}
var x = window.matchMedia("(max-width: 991px)")
rClass(x)
if (typeof x.addEventListener === "function") {
    x.addEventListener("change", rClass);
} else if (typeof x.addListener === "function") {
    x.addListener(rClass);
}
