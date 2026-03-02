
document.addEventListener('DOMContentLoaded', function() {
  fetch('php/abonos_usuario_api.php')
    .then(res => res.json())
    .then(abonos => {
      const cont = document.getElementById('abonos-list');
      if (!abonos.length) {
        cont.innerHTML = '<p>No tienes abonos activos.</p>';
        return;
      }
      cont.innerHTML = '<ul>' + abonos.map(a => `
        <li>
          <strong>${a.tipo.charAt(0).toUpperCase() + a.tipo.slice(1)}</strong>
          <span> (${a.fecha_inicio} - ${a.fecha_fin})</span>
          <span class="estado ${a.estado}">${a.estado === 'activo' ? 'Activo' : 'Vencido'}</span>
          ${a.viajes_totales ? `<span> | Viajes restantes: ${a.viajes_restantes}</span>` : ''}
        </li>
      `).join('') + '</ul>';
    })
    .catch(() => {
      document.getElementById('abonos-list').innerHTML = '<p>Error cargando abonos.</p>';
    });
});