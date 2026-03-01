document.addEventListener("DOMContentLoaded", function () {
    const userActions = document.getElementById("userActions");
    if (!userActions) return;

    fetch("estado_sesion.php?t=" + Date.now(), { credentials: "same-origin" })
        .then((res) => {
            if (!res.ok) throw new Error("No se pudo consultar la sesion");
            return res.json();
        })
        .then((data) => {
            if (!data || !data.logeado || !data.usuario) return;

            const nombre = data.usuario.nombre ? data.usuario.nombre : "Mi cuenta";

            userActions.innerHTML = `
                <div class="account-dropdown" id="accountDropdown">
                    <button type="button" class="account-toggle" id="accountToggle" aria-haspopup="true" aria-expanded="false">
                        <span class="account-avatar">${nombre.charAt(0).toUpperCase()}</span>
                        <span class="account-name">${nombre}</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </button>
                    <div class="account-menu" id="accountMenu">
                        <a href="perfil_pasajero.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
                        <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesion</a>
                    </div>
                </div>
            `;

            const dropdown = document.getElementById("accountDropdown");
            const toggle = document.getElementById("accountToggle");
            const menu = document.getElementById("accountMenu");

            if (!dropdown || !toggle || !menu) return;

            toggle.addEventListener("click", function (event) {
                event.preventDefault();
                dropdown.classList.toggle("open");
                toggle.setAttribute("aria-expanded", dropdown.classList.contains("open") ? "true" : "false");
            });

            document.addEventListener("click", function (event) {
                if (!dropdown.contains(event.target)) {
                    dropdown.classList.remove("open");
                    toggle.setAttribute("aria-expanded", "false");
                }
            });
        })
        .catch(function () {
            // Si falla, se mantiene el boton de iniciar sesion.
        });
});
