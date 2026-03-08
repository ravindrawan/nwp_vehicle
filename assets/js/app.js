<?php
// ============================================================
// app.js equivalent — custom JS for NWPC Vehicle Fleet System
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ── Sidebar Toggle (Mobile) ──────────────────────────────
    const sidebarToggle  = document.getElementById('sidebarToggle');
    const mainSidebar    = document.getElementById('mainSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        mainSidebar?.classList.add('open');
        sidebarOverlay?.classList.add('show');
    }

    function closeSidebar() {
        mainSidebar?.classList.remove('open');
        sidebarOverlay?.classList.remove('show');
    }

    sidebarToggle?.addEventListener('click', openSidebar);
    sidebarOverlay?.addEventListener('click', closeSidebar);

    // ── Init DataTables ──────────────────────────────────────
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 25,
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            }
        });
    }

    // ── Init Select2 ─────────────────────────────────────────
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select an option',
            allowClear: true
        });
    }

    // ── Auto-dismiss alerts ───────────────────────────────────
    setTimeout(function () {
        document.querySelectorAll('.alert.auto-dismiss').forEach(function (el) {
            el.style.transition = 'opacity .5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        });
    }, 4000);

});
