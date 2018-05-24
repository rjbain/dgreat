var cex = document.getElementById("CollapsingNavbar");
var cbt = document.getElementById("submenuButton");
function rClass(x) {
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
var x = window.matchMedia("(max-width: 767px)")
rClass(x)
x.addListener(rClass)
