document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.sidebar-toggle');
    const closeBtn = document.querySelector('.sidebar-mobile-close');
    const overlay = document.querySelector('.sidebar-overlay');
    const collapseToggle = document.querySelector('.sidebar-collapse-toggle');
    const sidebar = document.querySelector('.sidebar');
    const shell = document.querySelector('.admin-shell');

    function updateSidebarToggleIcon() {
        if (!collapseToggle || !shell) return;

        const icon = collapseToggle.querySelector('i');
        if (!icon) return;

        if (shell.classList.contains('sidebar-collapsed')) {
            icon.className = 'bi bi-arrow-right';
            collapseToggle.setAttribute('aria-label', 'Expand sidebar');
            collapseToggle.setAttribute('title', 'Expand sidebar');
        } else {
            icon.className = 'bi bi-list';
            collapseToggle.setAttribute('aria-label', 'Minimise sidebar');
            collapseToggle.setAttribute('title', 'Minimise sidebar');
        }
    }

    function openSidebar(){
        if (sidebar) sidebar.classList.add('show');
        if (overlay) overlay.classList.add('show');
        document.body.classList.add('sidebar-open');
    }

    function closeSidebar(){
        if (sidebar) sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
        document.body.classList.remove('sidebar-open');
    }

    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            if (sidebar.classList.contains('show')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeSidebar();
    });

    document.querySelectorAll('.side-menu a, .profile-edit-link, .logout-btn').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992) closeSidebar();
        });
    });

    if (shell && localStorage.getItem('sidebarCollapsed') === 'true') {
        shell.classList.add('sidebar-collapsed');
    }

    updateSidebarToggleIcon();

    if (collapseToggle && shell) {
        collapseToggle.addEventListener('click', () => {
            shell.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', shell.classList.contains('sidebar-collapsed') ? 'true' : 'false');
            updateSidebarToggleIcon();
        });
    }

    document.querySelectorAll('.menu-parent').forEach(btn => {
        btn.addEventListener('click', () => {
            if (shell && shell.classList.contains('sidebar-collapsed')) {
                shell.classList.remove('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', 'false');
                updateSidebarToggleIcon();
            }

            btn.closest('.menu-group').classList.toggle('open');
        });
    });

    
    // Show/hide password field toggle
    document.querySelectorAll('input[type="password"]').forEach((input, index) => {
        if (input.dataset.passwordToggleReady === 'true') return;

        input.dataset.passwordToggleReady = 'true';

        const wrapper = document.createElement('div');
        wrapper.className = 'password-field-wrap';

        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'password-toggle-btn';
        btn.setAttribute('aria-label', 'Lihat password');
        btn.innerHTML = '<i class="bi bi-eye"></i>';

        wrapper.appendChild(btn);

        btn.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            btn.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Lihat password');
        });
    });

    
    // Collapsed sidebar hover labels
    document.querySelectorAll('.side-menu a, .menu-parent, .logout-btn, .new-project-btn').forEach((item) => {
        const label = item.innerText.replace(/\s+/g, ' ').trim();
        if (label) item.setAttribute('data-menu-label', label);
    });

    
    // Collapsed sidebar hover labels
    document.querySelectorAll('.side-menu a, .menu-parent, .logout-btn, .new-project-btn').forEach((item) => {
        const label = item.innerText.replace(/\s+/g, ' ').trim();
        if (label) item.setAttribute('data-menu-label', label);
    });

    // DataTables for product/content table view
    // Important: DataTables does not support tbody rows with colspan.
    // Empty messages are shown outside the table, while DataTables handles empty tbody.
    if (window.jQuery && document.querySelector('.admin-datatable')) {
        jQuery('.admin-datatable').each(function () {
            const table = this;
            const headerCount = table.querySelectorAll('thead th').length;
            const invalidRows = Array.from(table.querySelectorAll('tbody tr')).filter((row) => {
                if (row.querySelector('td[colspan]')) return true;
                return row.children.length !== headerCount;
            });

            invalidRows.forEach((row) => row.remove());

            jQuery(table).DataTable({
                paging: false,
                info: false,
                responsive: true,
                autoWidth: false,
                order: [],
                language: {
                    search: 'Cari:',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Tidak ada data'
                }
            });
        });
    }
    // Upload validation based on Website Settings
    const uploadSettings = window.UPLOAD_SETTINGS || null;

    function bytesToMb(bytes) {
        return (bytes / (1024 * 1024)).toFixed(2);
    }

    function getUploadAlertHost(input) {
        return input.closest('.upload-field-wrap, .mb-3, .col-md-6, .col-12, .product-side-panel, .profile-edit-preview') || input.parentElement;
    }

    function showUploadAlert(input, message) {
        const host = getUploadAlertHost(input);
        if (!host) return;

        const oldAlert = host.querySelector('.upload-setting-alert');
        if (oldAlert) oldAlert.remove();

        const alert = document.createElement('div');
        alert.className = 'alert alert-danger upload-setting-alert mt-2 mb-0';
        alert.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i><span>' + message + '</span>';

        input.insertAdjacentElement('afterend', alert);

        setTimeout(() => {
            alert.classList.add('show-alert');
        }, 20);
    }

    function showUploadSuccess(input) {
        const host = getUploadAlertHost(input);
        if (!host) return;

        const oldAlert = host.querySelector('.upload-setting-alert');
        if (oldAlert) oldAlert.remove();

        const success = document.createElement('div');
        success.className = 'alert alert-success upload-setting-alert upload-setting-success mt-2 mb-0';
        success.innerHTML = '<i class="bi bi-check-circle-fill"></i><span>File sesuai dengan aturan upload.</span>';

        input.insertAdjacentElement('afterend', success);

        setTimeout(() => {
            success.classList.add('show-alert');
        }, 20);

        setTimeout(() => {
            success.remove();
        }, 2600);
    }

    function clearUploadAlert(input) {
        const host = getUploadAlertHost(input);
        const oldAlert = host ? host.querySelector('.upload-setting-alert') : null;
        if (oldAlert) oldAlert.remove();
    }

    if (uploadSettings) {
        document.querySelectorAll('input[type="file"]').forEach((input) => {
            input.classList.add('upload-setting-ready');

            input.addEventListener('change', () => {
                clearUploadAlert(input);

                const files = Array.from(input.files || []);
                if (!files.length) return;

                const isImageField =
                    input.classList.contains('upload-validate-image') ||
                    input.name === 'avatar' ||
                    input.name === 'favicon' ||
                    (input.getAttribute('accept') || '').includes('image') ||
                    /\.(jpg|jpeg|png|gif|webp|ico)/i.test(input.getAttribute('accept') || '');

                const allowedSource = isImageField
                    ? (uploadSettings.allowedImageExtensions || uploadSettings.allowedExtensions || [])
                    : (uploadSettings.allowedExtensions || []);

                const allowed = allowedSource.map((ext) => String(ext).toLowerCase().replace('.', ''));
                const maxBytes = Number(uploadSettings.maxBytes || 0);
                const maxMb = uploadSettings.maxMb || bytesToMb(maxBytes);

                for (const file of files) {
                    const ext = file.name.includes('.') ? file.name.split('.').pop().toLowerCase() : '';

                    if (allowed.length && !allowed.includes(ext)) {
                        input.value = '';
                        showUploadAlert(input, 'Extension file <strong>.' + (ext || 'unknown') + '</strong> tidak diperbolehkan. Extension yang boleh: <strong>' + allowed.join(', ') + '</strong>.');
                        return;
                    }

                    if (maxBytes > 0 && file.size > maxBytes) {
                        input.value = '';
                        showUploadAlert(input, 'Ukuran file <strong>' + file.name + '</strong> adalah ' + bytesToMb(file.size) + 'MB. Maksimal upload: <strong>' + maxMb + 'MB</strong>.');
                        return;
                    }
                }

                showUploadSuccess(input);
            });
        });
    }

    // Profile dropdown animation class
    document.querySelectorAll('.topbar-profile .dropdown-menu').forEach((menu) => {
        menu.addEventListener('transitionend', () => {
            if (!menu.classList.contains('show')) {
                menu.classList.remove('profile-menu-animate');
            }
        });
    });

    document.querySelectorAll('.topbar-profile').forEach((profile) => {
        profile.addEventListener('shown.bs.dropdown', () => {
            const menu = profile.querySelector('.dropdown-menu');
            if (menu) menu.classList.add('profile-menu-animate');
        });
        profile.addEventListener('hide.bs.dropdown', () => {
            const menu = profile.querySelector('.dropdown-menu');
            if (menu) menu.classList.remove('profile-menu-animate');
        });
    });

    
    // Profile button click animation
    document.querySelectorAll('.topbar-profile-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            btn.classList.remove('profile-click-pulse');
            void btn.offsetWidth;
            btn.classList.add('profile-click-pulse');
        });

        btn.addEventListener('animationend', () => {
            btn.classList.remove('profile-click-pulse');
        });
    });

    
    // Auto submit best seller toggle in product list
    document.querySelectorAll('.best-seller-auto-submit').forEach((input) => {
        input.addEventListener('change', () => {
            const form = input.closest('form');
            if (form) form.submit();
        });
    });

    const chartEl = document.getElementById('salesChart');

    if (chartEl) {
        new Chart(chartEl, {
            type: 'line',
            data: {
                labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul'],
                datasets: [
                    {label:'Revenue',data:[12000,19000,17000,24000,28000,26000,34000],borderColor:'#2453b6',backgroundColor:'rgba(36,83,182,.13)',tension:.45,fill:true},
                    {label:'Orders',data:[9000,13000,21000,19000,22000,25000,28000],borderColor:'#f7c751',backgroundColor:'rgba(247,199,81,.13)',tension:.45,fill:true}
                ]
            },
            options: {
                responsive: true,
                plugins: {legend: {labels: {usePointStyle: true, boxWidth: 8}}},
                scales: {y: {grid: {color:'#edf1fb'}, ticks:{display:false}}, x:{grid:{display:false}}}
            }
        });
    }
});
