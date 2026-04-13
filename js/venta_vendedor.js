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
      // Limpiar selección previa
      document.getElementById('inputAsientoSeleccionado').value = '';
    });
}

document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('formSeleccionAsiento').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!viajeSeleccionado) return;
    const asiento = document.getElementById('inputAsientoSeleccionado').value;
    if (!asiento) {
      alert('Selecciona un asiento.');
      return;
    }
    fetch('php/api_comprar_billete_vendedor.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ id_viaje: viajeSeleccionado.id_viaje, numero_asiento: asiento })
    })
    .then(r=>r.json())
    .then(data => {
      document.getElementById('compraResultado').innerHTML = data.ok ? '<b>¡Compra realizada correctamente!</b>' : `<b>Error:</b> ${data.error}`;
    });
  });
});
