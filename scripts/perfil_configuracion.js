document.addEventListener('DOMContentLoaded', () => {
    const formDatos = document.getElementById('form-datos-perfil');
    const formPassword = document.getElementById('form-cambiar-contrasena');
    const btnNotificaciones = document.getElementById('btn-guardar-notificaciones');
    const btnEliminarCuenta = document.querySelector('.btn-danger');

    const statusDatos = document.getElementById('config-status-datos');
    const statusPass = document.getElementById('config-status-password');
    const statusNotif = document.getElementById('config-status-notificaciones');

    function pintarEstado(el, ok, mensaje) {
        if (!el) {
            return;
        }
        el.textContent = mensaje;
        el.classList.remove('is-success', 'is-error');
        el.classList.add(ok ? 'is-success' : 'is-error');
    }

    async function llamarConfiguracion(payload) {
        const res = await fetch('php/api_configuracion_pasajero.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json().catch(() => ({ ok: false, mensaje: 'Respuesta invalida del servidor' }));
        return { ok: !!data.ok, mensaje: data.mensaje || 'Operacion completada' };
    }

    async function eliminarCuenta() {
        const res = await fetch('php/api_eliminar_cuenta_pasajero.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'eliminar_cuenta' })
        });

        const data = await res.json().catch(() => ({ ok: false, mensaje: 'Respuesta invalida del servidor' }));
        return { ok: !!data.ok, mensaje: data.mensaje || 'Operacion completada', redirect: data.redirect || 'inicio_sesion.html' };
    }

    if (btnNotificaciones) {
        btnNotificaciones.addEventListener('click', async () => {
            btnNotificaciones.disabled = true;
            pintarEstado(statusNotif, true, 'Guardando preferencias...');

            const payload = {
                accion: 'guardar_notificaciones',
                notificaciones_viaje: !!document.getElementById('notif_viaje')?.checked,
                notificaciones_ofertas: !!document.getElementById('notif_ofertas')?.checked
            };

            try {
                const r = await llamarConfiguracion(payload);
                pintarEstado(statusNotif, r.ok, r.mensaje);
            } catch (e) {
                pintarEstado(statusNotif, false, 'No se pudo guardar la configuracion');
            } finally {
                btnNotificaciones.disabled = false;
            }
        });
    }

    if (formDatos) {
        formDatos.addEventListener('submit', async (e) => {
            e.preventDefault();
            const boton = document.getElementById('btn-guardar-datos');
            if (boton) {
                boton.disabled = true;
            }

            pintarEstado(statusDatos, true, 'Guardando datos...');
            const fd = new FormData(formDatos);
            const payload = {
                accion: 'actualizar_datos',
                nombre: String(fd.get('nombre') || '').trim(),
                apellido: String(fd.get('apellido') || '').trim(),
                email: String(fd.get('email') || '').trim(),
                telefono: String(fd.get('telefono') || '').trim(),
                fecha_nacimiento: String(fd.get('fecha_nacimiento') || '').trim(),
                calle: String(fd.get('calle') || '').trim(),
                ciudad: String(fd.get('ciudad') || '').trim(),
                codigo_postal: String(fd.get('codigo_postal') || '').trim(),
                pais: String(fd.get('pais') || '').trim()
            };

            try {
                const r = await llamarConfiguracion(payload);
                pintarEstado(statusDatos, r.ok, r.mensaje);
            } catch (err) {
                pintarEstado(statusDatos, false, 'No se pudieron actualizar los datos');
            } finally {
                if (boton) {
                    boton.disabled = false;
                }
            }
        });
    }

    if (formPassword) {
        formPassword.addEventListener('submit', async (e) => {
            e.preventDefault();
            const boton = document.getElementById('btn-cambiar-contrasena');
            if (boton) {
                boton.disabled = true;
            }

            pintarEstado(statusPass, true, 'Actualizando contrasena...');
            const fd = new FormData(formPassword);
            const payload = {
                accion: 'cambiar_password',
                password_actual: String(fd.get('password_actual') || ''),
                password_nueva: String(fd.get('password_nueva') || ''),
                password_repetida: String(fd.get('password_repetida') || '')
            };

            if (payload.password_nueva !== payload.password_repetida) {
                pintarEstado(statusPass, false, 'La nueva contrasena no coincide');
                if (boton) {
                    boton.disabled = false;
                }
                return;
            }

            try {
                const r = await llamarConfiguracion(payload);
                pintarEstado(statusPass, r.ok, r.mensaje);
                if (r.ok) {
                    formPassword.reset();
                }
            } catch (err) {
                pintarEstado(statusPass, false, 'No se pudo cambiar la contrasena');
            } finally {
                if (boton) {
                    boton.disabled = false;
                }
            }
        });
    }

    if (btnEliminarCuenta) {
        btnEliminarCuenta.addEventListener('click', async () => {
            const confirmado = window.confirm('Vas a eliminar tu cuenta de forma permanente. Esta accion borrara tus datos, abonos y billetes. ¿Deseas continuar?');
            if (!confirmado) {
                return;
            }

            btnEliminarCuenta.disabled = true;
            btnEliminarCuenta.textContent = 'Eliminando cuenta...';

            try {
                const r = await eliminarCuenta();
                if (r.ok) {
                    window.location.href = r.redirect || 'inicio_sesion.html';
                    return;
                }
                alert(r.mensaje);
            } catch (err) {
                alert('No se pudo eliminar la cuenta');
            } finally {
                btnEliminarCuenta.disabled = false;
                btnEliminarCuenta.textContent = 'Eliminar mi cuenta';
            }
        });
    }
});
