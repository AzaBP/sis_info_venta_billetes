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
            let i18nKey = "mi_perfil";

            if (tipoUsuario === "empleado") {
                if (tipoEmpleado === "vendedor") {
                    enlacePanel = "vendedor.php";
                    textoPanel = "Panel vendedor";
                    i18nKey = "panel_vendedor";
                } else if (tipoEmpleado === "mantenimiento") {
                    enlacePanel = "mantenimiento.php";
                    textoPanel = "Panel mantenimiento";
                    i18nKey = "panel_mantenimiento";
                } else {
                    enlacePanel = "index.php";
                    textoPanel = "Panel";
                    i18nKey = "panel";
                }
            }

            // Traducir si i18n está disponible
            if (window.trainwebI18n) {
                textoPanel = window.trainwebI18n.t(i18nKey) || textoPanel;
            }

            userActions.innerHTML = `
                <div class="account-dropdown" id="accountDropdown">
                    <button type="button" class="account-toggle" id="accountToggle" aria-haspopup="true" aria-expanded="false">
                        <span class="account-avatar">${nombre.charAt(0).toUpperCase()}</span>
                        <span class="account-name">${nombre}</span>
                        <i class="fa-solid fa-caret-down"></i>
                    </button>
                    <div class="account-menu" id="accountMenu">
                        <a href="${enlacePanel}"><i class="fa-solid fa-user"></i> <span data-i18n="${i18nKey}">${textoPanel}</span></a>
                        <a href="cerrar_sesion.php"><i class="fa-solid fa-right-from-bracket"></i> <span data-i18n="cerrar_sesion">${window.trainwebI18n ? window.trainwebI18n.t('cerrar_sesion') : 'Cerrar sesión'}</span></a>
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


