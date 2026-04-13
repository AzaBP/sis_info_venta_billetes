// Lógica del flujo de venta para el vendedor
let viajeSeleccionado = null;
let asientosDisponibles = [];

function mostrarModalVentaVendedor() {
  // 1. Verificamos que el vendedor haya buscado un cliente primero
  if (typeof clienteBuscado === 'undefined' || !clienteBuscado) {
      alert('Por favor, busca y selecciona un cliente usando su DNI antes de iniciar una venta.');
      return;
  }

  document.getElementById('modalVentaVendedor').classList.remove('hidden');
  document.getElementById('ventaVendedorPaso1').classList.remove('hidden');
  document.getElementById('ventaVendedorPaso2').classList.add('hidden');
  
  // Asegurarnos de que el paso 3 también esté oculto al iniciar
  const paso3 = document.getElementById('ventaVendedorPaso3');
  if(paso3) paso3.classList.add('hidden');

  document.getElementById('resultadosViajes').innerHTML = '';
  document.getElementById('compraResultado').innerHTML = '';

  // 2. Mejora UX: Autocompletar la fecha de hoy en el buscador de viajes
  const hoy = new Date().toISOString().split('T')[0];
  const inputFecha = document.querySelector('#formBuscarViajes input[name="fecha"]');
  if(inputFecha) inputFecha.value = hoy;

  // 3. Pre-rellenar los datos del formulario de compra (Paso 3)
  // Utilizamos la variable global 'clienteBuscado' de vendedor.js
  document.getElementById('facturaNombre').value = clienteBuscado.nombre + (clienteBuscado.apellido ? ' ' + clienteBuscado.apellido : '');
  document.getElementById('facturaNif').value = clienteBuscado.dni || '';
  document.getElementById('facturaEmail').value = clienteBuscado.email || '';
  // El campo de dirección lo dejamos vacío a menos que lo traigas en clienteBuscado.direccion
  document.getElementById('facturaDireccion').value = ''; 
}

function cerrarModalVentaVendedor() {
  document.getElementById('modalVentaVendedor').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
  // Siempre ocultar el modal al cargar
  document.getElementById('modalVentaVendedor').classList.add('hidden');

  const btnVenta = document.getElementById('btnIniciarVenta');
  const cerrarBtn = document.getElementById('cerrarVentaVendedor');
  btnVenta && btnVenta.addEventListener('click', mostrarModalVentaVendedor);
  cerrarBtn && cerrarBtn.addEventListener('click', cerrarModalVentaVendedor);

  // Paso 1: Buscar viajes
  document.getElementById('formBuscarViajes').addEventListener('submit', function(e) {
    e.preventDefault();
    const datos = Object.fromEntries(new FormData(this));
    fetch('php/api_buscar_viajes.php?origen='+encodeURIComponent(datos.origen)+'&destino='+encodeURIComponent(datos.destino)+'&fecha='+encodeURIComponent(datos.fecha))
      .then(r=>r.json())
      .then(data => {
        const res = document.getElementById('resultadosViajes');
        if (data.error || !data.viajes || data.viajes.length === 0) {
          res.innerHTML = '<p>No hay viajes disponibles.</p>';
        } else {
          res.innerHTML = '<ul>' + data.viajes.map((v,i) => `<li><button type='button' onclick='seleccionarViajeVendedor(${JSON.stringify(v.id_viaje)})'>${v.origen} → ${v.destino} (${v.fecha} ${v.hora_salida}) - Tren: ${v.tipo_tren} - ${v.precio_base}€</button></li>`).join('') + '</ul>';
          window._viajesVendedor = data.viajes;
        }
      });
  });
});

function seleccionarViajeVendedor(id_viaje) {
  const viajes = window._viajesVendedor || [];
  const viaje = viajes.find(v => v.id_viaje == id_viaje);
  if (!viaje) return;
  viajeSeleccionado = viaje;
  // Buscar todos los asientos (disponibles y ocupados)
  fetch('php/api_asientos_todos.php?id_viaje='+id_viaje)
    .then(r=>r.json())
    .then(data => {
      const asientos = data.asientos || [];
      document.getElementById('ventaVendedorPaso1').classList.add('hidden');
      document.getElementById('ventaVendedorPaso2').classList.remove('hidden');
      document.getElementById('infoViajeSeleccionado').innerHTML = `${viaje.origen} → ${viaje.destino} (${viaje.fecha} ${viaje.hora_salida}) - Tren: ${viaje.tipo_tren}`;
      // Mostrar asientos visualmente
      const grid = document.getElementById('asientosGrid');
      grid.innerHTML = '';
      grid.style.border = '2px dashed #0a2a66'; // Borde para depuración visual
      if (asientos.length === 0) {
        grid.innerHTML = '<span style="grid-column: span 8; color: #c00;">No hay asientos en este tren.</span>';
      } else {
        asientos.forEach(a => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'asiento-btn';
          btn.textContent = a.numero_asiento;
          if (a.estado !== 'disponible') {
            btn.disabled = true;
          }
          btn.onclick = function() {
            if (btn.disabled) return;
            document.querySelectorAll('.asiento-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            document.getElementById('inputAsientoSeleccionado').value = a.numero_asiento;
          };
          grid.appendChild(btn);
        });
      }
      // Limpiar selección previa
      document.getElementById('inputAsientoSeleccionado').value = '';
    })
    .catch(err => {
      const grid = document.getElementById('asientosGrid');
      grid.innerHTML = '<span style="color:#c00">Error cargando asientos</span>';
      grid.style.border = '2px dashed #c00';
    });
}

document.addEventListener('DOMContentLoaded', function() {
  // Paso de asientos a datos de compra
  document.getElementById('btnPasoDatosCompra').addEventListener('click', function() {
    const asiento = document.getElementById('inputAsientoSeleccionado').value;
    if (!viajeSeleccionado || !asiento) {
      alert('Selecciona un asiento.');
      return;
    }
    // Mostrar paso 3
    document.getElementById('ventaVendedorPaso2').classList.add('hidden');
    document.getElementById('ventaVendedorPaso3').classList.remove('hidden');
    // Mostrar resumen de viaje y asiento
    document.getElementById('resumenViajeVendedor').textContent = `${viajeSeleccionado.origen} → ${viajeSeleccionado.destino} (${viajeSeleccionado.fecha} ${viajeSeleccionado.hora_salida}) - Tren: ${viajeSeleccionado.tipo_tren}`;
    document.getElementById('resumenAsientoVendedor').textContent = asiento;
    // Mostrar precio base y precio final
    document.getElementById('precioBaseCompra').textContent = viajeSeleccionado.precio_base;
    document.getElementById('precioFinalCompra').textContent = viajeSeleccionado.precio_base;
    document.getElementById('descuentoCompra').value = 0;
    document.getElementById('promoMsgVendedor').textContent = '';
  });
  // Actualizar precio final al cambiar descuento
  document.getElementById('descuentoCompra').addEventListener('input', function() {
    const base = parseFloat(document.getElementById('precioBaseCompra').textContent);
    const desc = parseFloat(this.value) || 0;
    const final = Math.max(0, base - (base * desc / 100));
    document.getElementById('precioFinalCompra').textContent = final.toFixed(2);
    // Mensaje visual de descuento
    const promoMsg = document.getElementById('promoMsgVendedor');
    if (desc > 0) {
      promoMsg.textContent = `Descuento aplicado: -${desc}%`;
      promoMsg.style.color = '#17632A';
    } else {
      promoMsg.textContent = '';
    }
  });
  // Confirmar compra con datos
  document.getElementById('formDatosCompra').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!viajeSeleccionado) return;
    const asiento = document.getElementById('inputAsientoSeleccionado').value;
    const descuento = parseFloat(document.getElementById('descuentoCompra').value) || 0;
    // Recoger datos de compra
    const facturaNombre = document.getElementById('facturaNombre').value;
    const facturaNif = document.getElementById('facturaNif').value;
    const facturaDireccion = document.getElementById('facturaDireccion').value;
    const facturaEmail = document.getElementById('facturaEmail').value;
    // Deshabilitar botón para evitar dobles envíos
    const btn = this.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    
    fetch('php/api_comprar_billete_vendedor.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ 
        // NUEVO: Enviamos el ID del cliente al backend
        id_usuario: clienteBuscado.id_usuario || clienteBuscado.id_pasajero, 
        id_viaje: viajeSeleccionado.id_viaje, 
        numero_asiento: asiento, 
        descuento: descuento,
        facturaNombre,
        facturaNif,
        facturaDireccion,
        facturaEmail
      })
    })
    .then(r=>r.json())
    .then(data => {
      document.getElementById('compraResultado').innerHTML = data.ok ? '<b style="color:#17632A"><i class="fa-solid fa-check-circle"></i> ¡Compra realizada correctamente!</b>' : `<b style="color:#c00"><i class="fa-solid fa-circle-exclamation"></i> Error:</b> ${data.error}`;
      if (btn) btn.disabled = false;
      
      // Opcional: Refrescar la lista de viajes del cliente llamando de nuevo a la búsqueda o actualizando el DOM
    })
    .catch(() => { 
        document.getElementById('compraResultado').innerHTML = '<b style="color:#c00">Error de conexión al procesar la compra.</b>';
        if (btn) btn.disabled = false; 
    });
  });
});
