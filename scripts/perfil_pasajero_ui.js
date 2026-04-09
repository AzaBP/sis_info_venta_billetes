document.addEventListener('DOMContentLoaded', () => {
    const links = Array.from(document.querySelectorAll('.sidebar-link'));
    const panels = Array.from(document.querySelectorAll('.content-panel'));

    function activarPanel(id) {
        links.forEach((btn) => btn.classList.toggle('active', btn.dataset.target === id));
        panels.forEach((panel) => {
            const activo = panel.id === id;
            panel.classList.toggle('active', activo);
            panel.hidden = !activo;
        });
    }

    links.forEach((btn) => {
        btn.addEventListener('click', () => activarPanel(btn.dataset.target));
    });

    activarPanel('panel-datos');

    const accordions = document.querySelectorAll('.accordion-header');
    accordions.forEach((header) => {
        header.addEventListener('click', () => {
            header.parentElement.classList.toggle('active');
        });
    });
});
