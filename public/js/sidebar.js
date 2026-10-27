// laboratorio/public/js/sidebar.js
document.addEventListener('DOMContentLoaded', () => {
    const accordions = document.querySelectorAll('.sidebar-accordion');

    accordions.forEach(acc => {
        const id = acc.id;
        if (!id) return;

        // Recuperar estado desde localStorage
        const savedState = localStorage.getItem('sidebar_lab_' + id);
        if (savedState === 'open') {
            acc.open = true;
        } else if (savedState === 'closed') {
            acc.open = false;
        }

        // Registrar cambios del usuario
        acc.addEventListener('toggle', () => {
            localStorage.setItem('sidebar_lab_' + id, acc.open ? 'open' : 'closed');
        });
    });
});