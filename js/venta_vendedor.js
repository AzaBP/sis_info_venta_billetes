// Lógica del flujo de venta para el vendedor
let viajeSeleccionado = null;
let asientosDisponibles = [];

function mostrarModalVentaVendedor() {
  document.getElementById('modalVentaVendedor').classList.remove('hidden');
  document.getElementById('ventaVendedorPaso1').classList.remove('hidden');
  document.getElementById('ventaVendedorPaso2').classList.add('hidden');
  document.getElementById('resultadosViajes').innerHTML = '';
  document.getElementById('compraResultado').innerHTML = '';
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
    // Mostrar precio base
    document.getElementById('precioBaseCompra').textContent = viajeSeleccionado.precio_base;
    document.getElementById('precioFinalCompra').textContent = viajeSeleccionado.precio_base;
    document.getElementById('descuentoCompra').value = 0;
  });
  // Actualizar precio final al cambiar descuento
  document.getElementById('descuentoCompra').addEventListener('input', function() {
    const base = parseFloat(document.getElementById('precioBaseCompra').textContent);
    const desc = parseFloat(this.value) || 0;
    const final = Math.max(0, base - (base * desc / 100));
    document.getElementById('precioFinalCompra').textContent = final.toFixed(2);
  });
  // Confirmar compra con datos
  document.getElementById('formDatosCompra').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!viajeSeleccionado) return;
    const asiento = document.getElementById('inputAsientoSeleccionado').value;
    const descuento = parseFloat(document.getElementById('descuentoCompra').value) || 0;
    fetch('php/api_comprar_billete_vendedor.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ id_viaje: viajeSeleccionado.id_viaje, numero_asiento: asiento, descuento: descuento })
    })
    .then(r=>r.json())
    .then(data => {
      document.getElementById('compraResultado').innerHTML = data.ok ? '<b>¡Compra realizada correctamente!</b>' : `<b>Error:</b> ${data.error}`;
    });
  });
});
