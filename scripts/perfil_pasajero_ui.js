document.addEventListener('DOMContentLoaded', () => {
    const links = Array.from(document.querySelectorAll('.sidebar-link'));
    const panels = Array.from(document.querySelectorAll('.content-panel'));

    function activarPanel(id) {
        links.forEach((btn) => btn.classList.toggle('active', btn.dataset.target === id));
        panels.forEach((panel) => panel.classList.toggle('active', panel.id === id));
    }

    links.forEach((btn) => {
        btn.addEventListener('click', () => activarPanel(btn.dataset.target));
    });

    const accordions = document.querySelectorAll('.accordion-header');
    accordions.forEach((header) => {
        header.addEventListener('click', () => {
            header.parentElement.classList.toggle('active');
        });
    });
});
