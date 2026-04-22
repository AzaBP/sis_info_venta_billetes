let clienteBuscado = null;
document.addEventListener('DOMContentLoaded', () => {
    const btnBuscar = document.getElementById('btnBuscar');
    const inputDni = document.getElementById('dniInput');
    // Elementos del DOM a manipular
    const clientInfo = document.getElementById('clientInfo');
    const clientError = document.getElementById('clientError');
    const operationsPanel = document.getElementById('operationsPanel');
    const clientDniValue = document.getElementById('clientDniValue');
    const clientActionsBox = document.getElementById('clientActionsBox');
    // Elementos de datos
    const elName = document.getElementById('clientName');
    const elEmail = document.getElementById('clientEmail');
    const elPhone = document.getElementById('clientPhone');
    const elCard = document.getElementById('clientCard');
    const listTrips = document.getElementById('recentTrips');

    // Función de búsqueda
    const realizarBusqueda = () => {
        const busqueda = inputDni.value.trim();
        // Resetear vista
        clientInfo.classList.add('hidden');
        clientError.classList.add('hidden');
        operationsPanel.classList.add('disabled');
        clientActionsBox.classList.add('disabled');
        listTrips.innerHTML = '';
        clienteBuscado = null;
        if (!busqueda) {
            clientError.textContent = 'Introduce un DNI o correo válido.';
            clientError.classList.remove('hidden');
            return;
        }
        fetch('php/api_buscar_usuario_tramites.php?dni=' + encodeURIComponent(busqueda))
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    clientError.textContent = data.error;
                    clientError.classList.remove('hidden');
                } else {
                    elName.textContent = data.usuario.nombre;
                    clientDniValue.textContent = data.usuario.dni;
                    elEmail.textContent = data.usuario.email || '---';
                    elPhone.textContent = data.usuario.telefono || '---';
                    elCard.textContent = data.usuario.tarjeta || '****';
                    // Viajes
                    if (data.viajes && data.viajes.length > 0) {
                        data.viajes.forEach(viaje => {
                            const li = document.createElement('li');
                            li.innerHTML = `
                                <span>${viaje.ruta} <br><small>${viaje.fecha}</small></span>
                                <span class="status-ok">${viaje.estado}</span>
                            `;
                            listTrips.appendChild(li);
                        });
                    } else {
                        listTrips.innerHTML = '<li style="color:#999">Sin viajes recientes</li>';
                    }
                    clientInfo.classList.remove('hidden');
                    operationsPanel.classList.remove('disabled');
                    clientActionsBox.classList.remove('disabled');
                    clienteBuscado = data.usuario;
                }
            })
            .catch(() => {
                clientError.textContent = 'Error al buscar el usuario.';
                clientError.classList.remove('hidden');
            });
    };

    // Event listeners
    btnBuscar.addEventListener('click', realizarBusqueda);
    
    // Enter key para búsqueda
    inputDni.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            realizarBusqueda();
        }
    });
});

// Event listeners para el modal de modificación de billete
document.addEventListener('DOMContentLoaded', () => {
    // Cerrar modal de modificación
    const btnCerrarModificar = document.getElementById('cerrarModificarBillete');
    if (btnCerrarModificar) {
        btnCerrarModificar.addEventListener('click', () => {
            cerrarModalModificarBillete();
        });
    }
    
    // Botón modificar datos del pasajero
    const btnModificarDatos = document.getElementById('btnModificarDatos');
    if (btnModificarDatos) {
        btnModificarDatos.addEventListener('click', () => {
            mostrarFormularioModificarDatos();
        });
    }
    
    // Botón cambiar asiento
    const btnModificarAsiento = document.getElementById('btnModificarAsiento');
    if (btnModificarAsiento) {
        btnModificarAsiento.addEventListener('click', () => {
            cargarAsientosParaModificacion();
        });
    }
    
    // Botón confirmar modificación de asiento
    const btnConfirmarModificacion = document.getElementById('btnConfirmarModificacion');
    if (btnConfirmarModificacion) {
        btnConfirmarModificacion.addEventListener('click', () => {
            confirmarModificacionBillete();
        });
    }
    
    // Formulario de modificación de datos del pasajero
    const formModificarDatos = document.getElementById('formModificarDatosPasajero');
    if (formModificarDatos) {
        formModificarDatos.addEventListener('submit', (e) => {
            guardarDatosModificadosPasajero(e);
        });
    }
});

// Función auxiliar para los botones de acción
function openModal(tipo) {
    if (!clienteBuscado) {
        alert('Primero busca un cliente.');
        return;
    }
    alert(`Abriendo proceso de ${tipo.toUpperCase()} para el cliente: ${clienteBuscado.nombre} (DNI: ${clienteBuscado.dni}, Email: ${clienteBuscado.email})`);
}let clienteBuscado = null;
document.addEventListener('DOMContentLoaded', () => {
    const btnBuscar = document.getElementById('btnBuscar');
    const inputDni = document.getElementById('dniInput');
    // Elementos del DOM a manipular
    const clientInfo = document.getElementById('clientInfo');
    const clientError = document.getElementById('clientError');
    const operationsPanel = document.getElementById('operationsPanel');
    const clientDniValue = document.getElementById('clientDniValue');
    const clientActionsBox = document.getElementById('clientActionsBox');
    // Elementos de datos
    const elName = document.getElementById('clientName');
    const elEmail = document.getElementById('clientEmail');
    const elPhone = document.getElementById('clientPhone');
    const elCard = document.getElementById('clientCard');
    const listTrips = document.getElementById('recentTrips');

    // Función de búsqueda
    const realizarBusqueda = () => {
        const busqueda = inputDni.value.trim();
        // Resetear vista
        clientInfo.classList.add('hidden');
        clientError.classList.add('hidden');
        operationsPanel.classList.add('disabled');
        clientActionsBox.classList.add('disabled');
        listTrips.innerHTML = '';
        clienteBuscado = null;
        if (!busqueda) {
            clientError.textContent = 'Introduce un DNI o correo válido.';
            clientError.classList.remove('hidden');
            return;
        }
        fetch('php/api_buscar_usuario_tramites.php?dni=' + encodeURIComponent(busqueda))
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    clientError.textContent = data.error;
                    clientError.classList.remove('hidden');
                } else {
                    elName.textContent = data.usuario.nombre;
                    clientDniValue.textContent = data.usuario.dni;
                    elEmail.textContent = data.usuario.email || '---';
                    elPhone.textContent = data.usuario.telefono || '---';
                    elCard.textContent = data.usuario.tarjeta || '****';
                    // Viajes
                    if (data.viajes && data.viajes.length > 0) {
                        data.viajes.forEach(viaje => {
                            const li = document.createElement('li');
                            li.innerHTML = `
                                <span>${viaje.ruta} <br><small>${viaje.fecha}</small></span>
                                <span class="status-ok">${viaje.estado}</span>
                            `;
                            listTrips.appendChild(li);
                        });
                    } else {
                        listTrips.innerHTML = '<li style="color:#999">Sin viajes recientes</li>';
                    }
                    clientInfo.classList.remove('hidden');
                    operationsPanel.classList.remove('disabled');
                    clientActionsBox.classList.remove('disabled');
                    clienteBuscado = data.usuario;
                }
            })
            .catch(() => {
                clientError.textContent = 'Error al buscar el usuario.';
                clientError.classList.remove('hidden');
            });
    };

    // Event listeners
    btnBuscar.addEventListener('click', realizarBusqueda);
    
    // Enter key para búsqueda
    inputDni.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            realizarBusqueda();
        }
    });

    // Continuar con el resto del código original...
        const busqueda = inputDni.value.trim();
        // Resetear vista
        clientInfo.classList.add('hidden');
        clientError.classList.add('hidden');
        operationsPanel.classList.add('disabled');
        clientActionsBox.classList.add('disabled');
        listTrips.innerHTML = '';
        clienteBuscado = null;
        if (!busqueda) {
            clientError.textContent = 'Introduce un DNI o correo válido.';
            clientError.classList.remove('hidden');
            return;
        }
        fetch('php/api_buscar_usuario_tramites.php?dni=' + encodeURIComponent(busqueda))
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    clientError.textContent = data.error;
                    clientError.classList.remove('hidden');
                } else {
                    elName.textContent = data.usuario.nombre;
                    clientDniValue.textContent = data.usuario.dni;
                    elEmail.textContent = data.usuario.email || '---';
                    elPhone.textContent = data.usuario.telefono || '---';
                    elCard.textContent = data.usuario.tarjeta || '****';
                    // Viajes
                    if (data.viajes && data.viajes.length > 0) {
                        data.viajes.forEach(viaje => {
                            const li = document.createElement('li');
                            li.innerHTML = `
                                <span>${viaje.ruta} <br><small>${viaje.fecha}</small></span>
                                <span class=\"status-ok\">${viaje.estado}</span>
                            `;
                            listTrips.appendChild(li);
                        });
                    } else {
                        listTrips.innerHTML = '<li style="color:#999">Sin viajes recientes</li>';
                    }
                    clientInfo.classList.remove('hidden');
                    operationsPanel.classList.remove('disabled');
                    clientActionsBox.classList.remove('disabled');
                    clienteBuscado = data.usuario;
                }
            })
            .catch(() => {
                clientError.textContent = 'Error al buscar el usuario.';
                clientError.classList.remove('hidden');
            });
    });
});

// Event listeners para el modal de modificación de billete
document.addEventListener('DOMContentLoaded', () => {
    // Cerrar modal de modificación
    const btnCerrarModificar = document.getElementById('cerrarModificarBillete');
    if (btnCerrarModificar) {
        btnCerrarModificar.addEventListener('click', () => {
            cerrarModalModificarBillete();
        });
    }
    
    // Botón modificar datos del pasajero
    const btnModificarDatos = document.getElementById('btnModificarDatos');
    if (btnModificarDatos) {
        btnModificarDatos.addEventListener('click', () => {
            mostrarFormularioModificarDatos();
        });
    }
    
    // Botón cambiar asiento
    const btnModificarAsiento = document.getElementById('btnModificarAsiento');
    if (btnModificarAsiento) {
        btnModificarAsiento.addEventListener('click', () => {
            cargarAsientosParaModificacion();
        });
    }
    
    // Botón confirmar modificación de asiento
    const btnConfirmarModificacion = document.getElementById('btnConfirmarModificacion');
    if (btnConfirmarModificacion) {
        btnConfirmarModificacion.addEventListener('click', () => {
            confirmarModificacionBillete();
        });
    }
    
    // Formulario de modificación de datos del pasajero
    const formModificarDatos = document.getElementById('formModificarDatosPasajero');
    if (formModificarDatos) {
        formModificarDatos.addEventListener('submit', (e) => {
            guardarDatosModificadosPasajero(e);
        });
    }
});

// Función auxiliar para los botones de acción
function openModal(tipo) {
    if (!clienteBuscado) {
        alert('Primero busca un cliente.');
        return;
    }
    // if (tipo === 'venta') {
    //     // Seleccionar cliente gestionado en la sesión y redirigir a compra.php
    //     if (!clienteBuscado.id_pasajero) {
    //         alert('Este usuario no es un pasajero válido.');
    //         return;
    //     }
    //     window.location.href = `php/seleccionar_cliente_gestionado.php?id_pasajero=${clienteBuscado.id_pasajero}`;
    //     return;
    // }
    // Aquí puedes implementar el resto de acciones para otros botones
    alert(`Abriendo proceso de ${tipo.toUpperCase()} para el cliente: ${clienteBuscado.nombre} (DNI: ${clienteBuscado.dni}, Email: ${clienteBuscado.email})`);
}