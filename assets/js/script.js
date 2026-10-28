(function () {
    'use strict';

    // Konfirmasi untuk semua form yang memiliki atribut data-confirm.
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var message = form.getAttribute('data-confirm') || 'Lanjutkan tindakan ini?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // Tutup notifikasi sukses secara otomatis tanpa mengganggu pesan error.
    if (window.bootstrap) {
        document.querySelectorAll('.app-alert.alert-success').forEach(function (alertElement) {
            window.setTimeout(function () {
                window.bootstrap.Alert.getOrCreateInstance(alertElement).close();
            }, 6000);
        });
    }

    // Tombol tampil/sembunyikan password.
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.getElementById(button.getAttribute('data-password-toggle'));
            if (!target) {
                return;
            }

            var show = target.type === 'password';
            target.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');

            var icon = button.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye', !show);
                icon.classList.toggle('bi-eye-slash', show);
            }
        });
    });

    // Tombol cetak halaman laporan.
    document.querySelectorAll('[data-print]').forEach(function (button) {
        button.addEventListener('click', function () {
            window.print();
        });
    });

    // Cegah pengiriman ganda pada form yang ditandai.
    document.querySelectorAll('form[data-submit-once]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var button = form.querySelector('button[type="submit"]');
            if (!button) {
                return;
            }

            window.setTimeout(function () {
                // Jangan nonaktifkan tombol jika pengiriman dibatalkan
                // oleh konfirmasi hapus atau validasi browser.
                if (event.defaultPrevented) {
                    return;
                }

                button.disabled = true;
                button.classList.add('is-submitting');
            }, 0);
        });
    });

    // Sidebar untuk layar kecil.
    var sidebar = document.getElementById('appSidebar');
    var sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    var sidebarBackdrop = document.querySelector('.sidebar-backdrop');
    var sidebarCloseButtons = document.querySelectorAll('[data-sidebar-close]');

    function setSidebar(open, restoreFocus) {
        if (!sidebar) {
            return;
        }

        sidebar.classList.toggle('is-open', open);
        document.body.classList.toggle('sidebar-open', open);

        if (sidebarBackdrop) {
            sidebarBackdrop.classList.toggle('is-visible', open);
        }

        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        if (open) {
            var firstLink = sidebar.querySelector('.sidebar-link');
            if (firstLink) {
                firstLink.focus();
            }
        } else if (restoreFocus && sidebarToggle) {
            sidebarToggle.focus();
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            setSidebar(!sidebar.classList.contains('is-open'), false);
        });
    }

    sidebarCloseButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setSidebar(false, true);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
            setSidebar(false, true);
        }
    });

    var desktopSidebar = window.matchMedia('(min-width: 992px)');
    var resetSidebar = function (event) {
        if (event.matches) {
            setSidebar(false, false);
        }
    };

    if (typeof desktopSidebar.addEventListener === 'function') {
        desktopSidebar.addEventListener('change', resetSidebar);
    } else if (typeof desktopSidebar.addListener === 'function') {
        desktopSidebar.addListener(resetSidebar);
    }
})();
