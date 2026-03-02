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
            const tipoUsuario = (data.usuario.tipo_usuario || "").toLowerCase();
            const tipoEmpleado = (data.usuario.tipo_empleado || "").toLowerCase();

            let enlacePanel = "perfil_pasajero.php";
            let textoPanel = "Mi perfil";

            const adminEmailsRaw = (window.TRAINWEB_ADMIN_EMAILS || "").toLowerCase();
            const adminEmails = adminEmailsRaw
                .split(",")
                .map((v) => v.trim())
                .filter(Boolean);
            const email = (data.usuario.email || "").toLowerCase();
            const esAdmin = adminEmails.length === 0 || adminEmails.includes(email);

            if (esAdmin) {
                enlacePanel = "registro_empleado.php";
                textoPanel = "Panel admin";
            } else if (tipoUsuario === "empleado") {
                if (tipoEmpleado === "vendedor") {
                    enlacePanel = "vendedor.php";
                    textoPanel = "Panel vendedor";
                } else if (tipoEmpleado === "mantenimiento") {
                    enlacePanel = "mantenimiento.php";
                    textoPanel = "Panel mantenimiento";
                } else {
                    enlacePanel = "index.php";
                    textoPanel = "Panel";
                }
            }

            userActions.innerHTML = `
                <div class="account-dropdown" id="accountDropdown">
                    <button type="button" class="account-toggle" id="accountToggle" aria-haspopup="true" aria-expanded="false">
                        <span class="account-avatar">${nombre.charAt(0).toUpperCase()}</span>
                        <span class="account-name">${nombre}</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </button>
                    <div class="account-menu" id="accountMenu">
                        <a href="${enlacePanel}"><i class="fa-solid fa-user"></i> ${textoPanel}</a>
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
