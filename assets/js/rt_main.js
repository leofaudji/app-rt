// =================================================================================
// APLIKASI RT - SINGLE PAGE APPLICATION (SPA) CORE
// =================================================================================
/**
 * Displays a toast notification.
 * @param {string} message The message to display.
 * @param {string} type The type of toast: 'success', 'error', or 'info'.
 * @param {string|null} title Optional title for the toast.
 */
function showToast(message, type = 'success', title = null) {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) return;

    const toastId = 'toast-' + Date.now();
    let toastIcon, defaultTitle;

    switch (type) {
        case 'error':
            toastIcon = '<i class="bi bi-x-circle-fill text-danger me-2"></i>';
            defaultTitle = 'Error';
            break;
        case 'info':
            toastIcon = '<i class="bi bi-bell-fill text-info me-2"></i>';
            defaultTitle = 'Notifikasi Baru';
            break;
        case 'success':
        default:
            toastIcon = '<i class="bi bi-check-circle-fill text-success me-2"></i>';
            defaultTitle = 'Sukses';
            break;
    }

    const toastTitle = title || defaultTitle;

    const toastHTML = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                ${toastIcon}
                <strong class="me-auto">${toastTitle}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 8000 });
    toast.show();
    toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
}

/**
 * Updates the active link in the sidebar based on the current URL.
 * @param {string} path The path of the page being navigated to.
 */
function updateActiveSidebarLink(path) {
    const sidebarLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    sidebarLinks.forEach(link => {
        link.classList.remove('active');
        const linkPath = new URL(link.href).pathname;
        const cleanCurrentPath = path.length > 1 ? path.replace(/\/$/, "") : path;
        const cleanLinkPath = linkPath.length > 1 ? linkPath.replace(/\/$/, "") : linkPath;
        if (cleanLinkPath === cleanCurrentPath) {
            link.classList.add('active');
        }
    });
}

/**
 * Main navigation function for the SPA.
 * Fetches page content and injects it into the main content area.
 * @param {string} url The URL to navigate to.
 * @param {boolean} pushState Whether to push a new state to the browser history.
 */
async function navigate(url, pushState = true) {
    const mainContent = document.querySelector('.main-content');
    const loadingBar = document.getElementById('spa-loading-bar');
    if (!mainContent) return;

    // --- Start Loading ---
    if (loadingBar) {
        loadingBar.classList.remove('is-finished'); // Reset state
        loadingBar.classList.add('is-loading');
    }

    // 1. Mulai animasi fade-out
    mainContent.classList.add('is-transitioning');

    // 2. Tunggu animasi fade-out selesai (durasi harus cocok dengan CSS)
    await new Promise(resolve => setTimeout(resolve, 200));

    try {
        const response = await fetch(url, {
            headers: {
                'X-SPA-Request': 'true' // Custom header to tell the backend this is an SPA request
            }
        });

        // --- Finish Loading ---
        if (loadingBar) {
            loadingBar.classList.add('is-finished');
        }

        if (!response.ok) {
            throw new Error(`Server responded with status ${response.status}`);
        }

        const html = await response.text();

        if (pushState) {
            history.pushState({ path: url }, '', url);
        }

        // 3. Ganti konten saat tidak terlihat
        mainContent.innerHTML = html;
        updateActiveSidebarLink(new URL(url).pathname);
        
        // 4. Mulai animasi fade-in
        mainContent.classList.remove('is-transitioning');

        runPageScripts(new URL(url).pathname); // Run scripts for the new page

        // Handle hash for scrolling to a specific item
        const hash = new URL(url).hash;
        if (hash) { 
            // Use a small timeout to ensure the element is rendered by the page script
            setTimeout(() => {
                const element = document.querySelector(hash);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Add a temporary highlight effect
                    element.classList.add('highlight-item');
                    setTimeout(() => element.classList.remove('highlight-item'), 3000);
                }
            }, 300); // 300ms delay should be enough
        } 
    } catch (error) {
        console.error('Navigation error:', error);
        let errorMessage = 'Gagal memuat halaman. Silakan coba lagi.';
        if (error.message.includes('403')) {
            errorMessage = 'Akses Ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.';
        } else if (error.message.includes('404')) {
            errorMessage = 'Halaman tidak ditemukan. Halaman yang Anda cari tidak ada atau telah dipindahkan.';
        }
        mainContent.innerHTML = `<div class="alert alert-danger m-3">${errorMessage}</div>`;
        // Tampilkan juga pesan error dengan fade-in
        mainContent.classList.remove('is-transitioning');
    } finally {
        // Hide the loading bar after a short delay to let the 'finished' animation complete
        if (loadingBar) {
            setTimeout(() => {
                loadingBar.classList.remove('is-loading');
                loadingBar.classList.remove('is-finished');
            }, 500); // 500ms delay
        }
    }
}

/**
 * A client-side router to run page-specific JavaScript after content is loaded.
 * @param {string} path The current page's path.
 */
function runPageScripts(path) {
    const cleanPath = path.replace(basePath, '').split('?')[0] || '/';

    if (cleanPath === '/dashboard') {
        initDashboardPage();
    } else if (cleanPath === '/warga') {
        initWargaPage();
    }
    else if (cleanPath === '/keuangan') {
        initKeuanganPage();
    }
    else if (cleanPath === '/users') {
        initUsersPage();
    }
    else if (cleanPath === '/rumah') {
        initRumahPage();
    }
    else if (cleanPath.startsWith('/rumah/detail/')) {
        initRumahDetailPage();
    }
    else if (cleanPath === '/iuran') {
        initIuranPage();
    }
    else if (cleanPath.startsWith('/iuran/histori')) {
        initIuranHistoriPage();
    }
    else if (cleanPath === '/kegiatan') {
        initKegiatanPage();
    }
    else if (cleanPath === '/laporan') {
        initLaporanPage();
    }
    else if (cleanPath === '/settings') {
        initSettingsPage();
    }
    else if (cleanPath === '/settings/iuran-history') {
        initIuranHistoriPerubahanPage();
    }
    else if (cleanPath === '/my-profile/change-password') {
        initMyProfilePage();
    }
    else if (cleanPath === '/my-profile/edit') {
        initMyProfileEditPage();
    }
    else if (cleanPath === '/iuran-saya') {
        initIuranSayaPage();
    }
    else if (cleanPath === '/tabungan-saya') {
        initTabunganSayaPage();
    }
    else if (cleanPath === '/keluarga-saya') {
        initKeluargaSayaPage();
    }
    else if (cleanPath === '/pengumuman') {
        initPengumumanPage();
    }
    else if (cleanPath === '/laporan-keuangan') {
        initLaporanKeuanganPage();
    }
    else if (cleanPath === '/dokumen') {
        initDokumenPage();
    }
    else if (cleanPath === '/polling') {
        initPollingPage();
    }
    else if (cleanPath === '/booking') {
        initBookingPage();
    }
    else if (cleanPath === '/anggaran') {
        initAnggaranPage();
    }
    else if (cleanPath === '/surat-pengantar') {
        initSuratPengantarPage();
    }
    else if (cleanPath === '/laporan/surat') {
        initLaporanSuratPage();
    }
    else if (cleanPath === '/galeri') {
        initGaleriPage();
    }
    else if (cleanPath.startsWith('/galeri/album/')) {
        initGaleriAlbumPage();
    }
    else if (cleanPath === '/aset') {
        initAsetPage();
    }
    else if (cleanPath === '/laporan/iuran') {
        initLaporanIuranPage();
    }
    else if (cleanPath === '/laporan/iuran/statistik') {
        initLaporanIuranStatistikPage();
    }
    else if (cleanPath === '/log-aktivitas') {
        initLogAktivitasPage();
    }
    else if (cleanPath === '/log-panik') {
        initPanicLogPage();
    }
    else if (cleanPath === '/laporan-terpadu') {
        initLaporanTerpaduPage();
    }
    else if (cleanPath.startsWith('/warga/profil/')) {
        initWargaProfilePage();
    }
    else if (cleanPath === '/manajemen') {
        initManajemenPage();
    }
    else if (cleanPath === '/tabungan') {
        initTabunganPage();
    }
    else if (cleanPath.startsWith('/tabungan/detail/')) {
        initTabunganDetailPage();
    }
    else if (cleanPath === '/manajemen/kategori-kas') {
        initManajemenKategoriPage();
    }
    else if (cleanPath === '/manajemen/kategori-tabungan') {
        initManajemenKategoriTabunganPage();
    }
}

// =================================================================================
// PAGE-SPECIFIC INITIALIZATION FUNCTIONS
// =================================================================================

function initManajemenPage() {
    const triggerTabList = document.querySelectorAll('#manajemenTab button[data-bs-toggle="tab"]');
    triggerTabList.forEach(triggerEl => {
        triggerEl.addEventListener('shown.bs.tab', event => {
            localStorage.setItem('lastManajemenTab', event.target.id);
        });
    });

    const lastTabId = localStorage.getItem('lastManajemenTab');
    if (lastTabId) {
        const lastTab = document.querySelector(`#${lastTabId}`);
        if (lastTab) {
            new bootstrap.Tab(lastTab).show();
        }
    }
}

function initDashboardPage() {
    const totalWargaWidget = document.getElementById('total-warga-widget');
    const saldoKasWidget = document.getElementById('saldo-kas-widget');
    const saldoTabunganWidget = document.getElementById('saldo-tabungan-widget');
    const rumahStatusChartCanvas = document.getElementById('rumah-status-chart');
    const birthdayWidgetList = document.getElementById('birthday-widget-list');
    const latestAnnouncementsWidget = document.getElementById('latest-announcements-widget');
    const upcomingActivitiesWidget = document.getElementById('upcoming-activities-widget');
    const demographicsChartCanvas = document.getElementById('demographics-chart');
    const kasMonthlyChartCanvas = document.getElementById('kas-monthly-chart');
    const adminTasksWidget = document.getElementById('admin-tasks-widget');
    const iuranSummaryWidget = document.getElementById('iuran-summary-widget');
    const newResidentsWidget = document.getElementById('new-residents-widget');
    const iuranProgressBar = document.getElementById('iuran-progress-bar');
    const saldoTrendMiniChartCanvas = document.getElementById('saldo-trend-mini-chart');
    const tabunganTrendMiniChartCanvas = document.getElementById('tabungan-trend-mini-chart');
    const iuranMenunggakWidget = document.getElementById('iuran-menunggak-widget');
    let rumahStatusChart, demographicsChart, kasMonthlyChart, saldoTrendMiniChart, tabunganTrendMiniChart;

    const bulanFilter = document.getElementById('dashboard-bulan-filter');
    const tahunFilter = document.getElementById('dashboard-tahun-filter');

    if (!totalWargaWidget || !bulanFilter || !tahunFilter) return;

    function setupFilters() {
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;

        // Populate years
        for (let i = 0; i < 5; i++) {
            const year = currentYear - i;
            tahunFilter.add(new Option(year, year));
        }

        // Populate months
        const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        months.forEach((month, index) => {
            bulanFilter.add(new Option(month, index + 1));
        });

        // Set default to current month and year
        bulanFilter.value = currentMonth;
        tahunFilter.value = currentYear;
    }

    async function fetchDashboardData(bulan, tahun) {
        // Show spinners
        const spinners = [totalWargaWidget, saldoKasWidget, saldoTabunganWidget, iuranSummaryWidget, birthdayWidgetList, latestAnnouncementsWidget, upcomingActivitiesWidget, adminTasksWidget, newResidentsWidget, iuranMenunggakWidget];
        spinners.forEach(el => {
            if (el) {
                const spinnerHtml = el.tagName === 'UL' || el.tagName === 'DIV' ? '<div class="text-center"><div class="spinner-border spinner-border-sm"></div></div>' : '<div class="spinner-border spinner-border-sm"></div>';
                el.innerHTML = spinnerHtml;
            }
        });

        try {
            const response = await fetch(`${basePath}/api/dashboard?bulan=${bulan}&tahun=${tahun}`);
            const result = await response.json();

            if (result.status === 'success') {
                const data = result.data;
                if (totalWargaWidget) totalWargaWidget.textContent = data.total_warga;
                if (saldoKasWidget) saldoKasWidget.textContent = data.saldo_kas;
                if (saldoTabunganWidget) saldoTabunganWidget.textContent = data.saldo_tabungan;

                if (rumahStatusChartCanvas && data.status_rumah) {
                    if (rumahStatusChart) {
                        rumahStatusChart.destroy();
                    }
                    const ctx = rumahStatusChartCanvas.getContext('2d');
                    rumahStatusChart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: data.status_rumah.labels,
                            datasets: [{
                                label: 'Jumlah Rumah',
                                data: data.status_rumah.data,
                                backgroundColor: [
                                    'rgba(25, 135, 84, 0.8)',  // Milik Sendiri (Success)
                                    'rgba(13, 110, 253, 0.8)', // Sewa (Primary)
                                    'rgba(108, 117, 125, 0.8)' // Kosong (Secondary)
                                ],
                                borderColor: document.body.classList.contains('dark-mode') ? '#1e1e1e' : '#fff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                }
                            }
                        }
                    });
                }
                if (demographicsChartCanvas && data.demografi) {
                    if (demographicsChart) {
                        demographicsChart.destroy();
                    }
                    const ctx = demographicsChartCanvas.getContext('2d');
                    demographicsChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.demografi.labels,
                            datasets: [{
                                label: 'Jumlah Warga',
                                data: data.demografi.data,
                                backgroundColor: [
                                    'rgba(54, 162, 235, 0.8)', // Laki-laki (Blue)
                                    'rgba(255, 99, 132, 0.8)',  // Perempuan (Pink)
                                    'rgba(75, 192, 192, 0.8)',  // Dewasa (Green)
                                    'rgba(255, 206, 86, 0.8)'  // Anak-anak (Yellow)
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } }
                        }
                    });
                }
                if (kasMonthlyChartCanvas && data.kas_bulanan) {
                    if (kasMonthlyChart) {
                        kasMonthlyChart.destroy();
                    }
                    const ctx = kasMonthlyChartCanvas.getContext('2d');
                    kasMonthlyChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: data.kas_bulanan.labels,
                            datasets: [{
                                label: 'Jumlah',
                                data: data.kas_bulanan.data,
                                backgroundColor: [
                                    'rgba(25, 135, 84, 0.8)',  // Pemasukan (Success)
                                    'rgba(220, 53, 69, 0.8)'  // Pengeluaran (Danger)
                                ],
                                borderColor: document.body.classList.contains('dark-mode') ? '#1e1e1e' : '#fff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom' },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.label || '';
                                            if (label) { label += ': '; }
                                            if (context.parsed !== null) {
                                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed);
                                            }
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
                if (saldoTrendMiniChartCanvas && data.saldo_trend) {
                    if (saldoTrendMiniChart) {
                        saldoTrendMiniChart.destroy();
                    }
                    const ctx = saldoTrendMiniChartCanvas.getContext('2d');
                    saldoTrendMiniChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.saldo_trend.labels,
                            datasets: [{
                                label: 'Saldo Kas',
                                data: data.saldo_trend.data,
                                fill: true,
                                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                                borderColor: 'rgba(25, 135, 84, 1)',
                                tension: 0.3,
                                pointRadius: 0,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { display: false },
                                x: { display: false }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) { label += ': '; }
                                            if (context.parsed.y !== null) {
                                                label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed.y);
                                            }
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
                if (tabunganTrendMiniChartCanvas && data.saldo_tabungan_trend) {
                    if (tabunganTrendMiniChart) {
                        tabunganTrendMiniChart.destroy();
                    }
                    const ctx = tabunganTrendMiniChartCanvas.getContext('2d');
                    tabunganTrendMiniChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.saldo_tabungan_trend.labels,
                            datasets: [{
                                label: 'Saldo Tabungan',
                                data: data.saldo_tabungan_trend.data,
                                fill: true,
                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                borderColor: 'rgba(13, 110, 253, 1)',
                                tension: 0.3,
                                pointRadius: 0,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: { y: { display: false }, x: { display: false } },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    callbacks: {
                                        label: function(context) {
                                            let label = context.dataset.label || '';
                                            if (label) { label += ': '; }
                                            if (context.parsed.y !== null) { label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed.y); }
                                            return label;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
                if (iuranSummaryWidget && data.iuran_summary) {
                    const summary = data.iuran_summary;
                    iuranSummaryWidget.innerHTML = `<h2 class="fw-bold">${summary.kk_lunas} / ${summary.total_kk} KK Lunas</h2>`;
                    if (iuranProgressBar) {
                        iuranProgressBar.style.width = `${summary.persentase}%`;
                        iuranProgressBar.setAttribute('aria-valuenow', summary.persentase);
                    }
                } else if (iuranSummaryWidget) {
                    iuranSummaryWidget.innerHTML = '<h2 class="fw-bold">-</h2>';
                }
                if (adminTasksWidget && data.admin_tasks) {
                    adminTasksWidget.innerHTML = ''; // Clear spinner
                    if (data.admin_tasks.length > 0) {
                        data.admin_tasks.forEach(task => {
                            const item = `
                                <a href="${basePath}${task.link}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    ${task.label}
                                    <span class="badge bg-danger rounded-pill">${task.count}</span>
                                </a>
                            `;
                            adminTasksWidget.insertAdjacentHTML('beforeend', item);
                        });
                    } else {
                        adminTasksWidget.innerHTML = `
                            <div class="list-group-item text-muted text-center">Tidak ada tugas yang menunggu.</div>
                        `;
                    }
                }
                if (birthdayWidgetList && data.ulang_tahun_bulan_ini) {
                    birthdayWidgetList.innerHTML = ''; // Clear spinner
                    if (data.ulang_tahun_bulan_ini.length > 0) {
                        data.ulang_tahun_bulan_ini.forEach(warga => {
                            const tgl = new Date(warga.tgl_lahir);
                            const tglFormatted = tgl.toLocaleDateString('id-ID', { day: 'numeric', month: 'long' });
                            const li = `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    ${warga.nama_lengkap}
                                    <span class="badge bg-info rounded-pill">${tglFormatted}</span>
                                </li>
                            `;
                            birthdayWidgetList.insertAdjacentHTML('beforeend', li);
                        });
                    } else {
                        birthdayWidgetList.innerHTML = '<li class="list-group-item text-muted">Tidak ada yang berulang tahun bulan ini.</li>';
                    }
                }
                if (latestAnnouncementsWidget && data.pengumuman_terbaru) {
                    latestAnnouncementsWidget.innerHTML = ''; // Clear spinner
                    if (data.pengumuman_terbaru.length > 0) {
                        data.pengumuman_terbaru.forEach(p => {
                            const timeAgo = timeSince(new Date(p.created_at));
                            const item = `
                                <a href="${basePath}/pengumuman" class="list-group-item list-group-item-action px-0">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 text-truncate" style="max-width: 70%;">${p.judul}</h6>
                                        <small class="text-muted">${timeAgo}</small>
                                    </div>
                                </a>
                            `;
                            latestAnnouncementsWidget.insertAdjacentHTML('beforeend', item);
                        });
                    } else {
                        latestAnnouncementsWidget.innerHTML = '<p class="text-muted mb-0">Tidak ada pengumuman baru.</p>';
                    }
                }
                if (upcomingActivitiesWidget && data.kegiatan_akan_datang) {
                    upcomingActivitiesWidget.innerHTML = ''; // Clear spinner
                    if (data.kegiatan_akan_datang.length > 0) {
                        data.kegiatan_akan_datang.forEach(k => {
                            const tgl = new Date(k.tanggal_kegiatan);
                            const tglFormatted = tgl.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' });
                            const item = `
                                <a href="${basePath}/kegiatan" class="list-group-item list-group-item-action px-0">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 text-truncate" style="max-width: 70%;">${k.judul}</h6>
                                        <small class="text-primary fw-bold">${tglFormatted}</small>
                                    </div>
                                </a>
                            `;
                            upcomingActivitiesWidget.insertAdjacentHTML('beforeend', item);
                        });
                    } else {
                        upcomingActivitiesWidget.innerHTML = '<p class="text-muted mb-0">Tidak ada kegiatan yang dijadwalkan.</p>';
                    }
                }
                if (iuranMenunggakWidget && data.iuran_menunggak) {
                    iuranMenunggakWidget.innerHTML = ''; // Clear spinner
                    if (data.iuran_menunggak.length > 0) {
                        data.iuran_menunggak.forEach(warga => {
                            const item = `
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="${basePath}/iuran/histori/${warga.no_kk}/kk" class="text-decoration-none text-dark">${warga.nama_lengkap}</a>
                                    <span class="badge bg-danger rounded-pill">${warga.jumlah_tunggakan} bln</span>
                                </li>
                            `;
                            iuranMenunggakWidget.insertAdjacentHTML('beforeend', item);
                        });
                    } else {
                        iuranMenunggakWidget.innerHTML = `
                            <li class="list-group-item text-muted text-center">
                                <i class="bi bi-check-all"></i> Semua warga patuh membayar iuran.
                            </li>`;
                    }
                }
            } else {
                throw new Error(result.message || 'Gagal memuat data.');
            }
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            [totalWargaWidget, saldoKasWidget, saldoTabunganWidget].forEach(el => { if (el) el.textContent = 'Error' });
            if (birthdayWidgetList) birthdayWidgetList.innerHTML = '<li class="list-group-item text-danger">Gagal memuat.</li>';
            if (latestAnnouncementsWidget) latestAnnouncementsWidget.innerHTML = '<p class="text-danger mb-0">Gagal memuat.</p>';
            if (upcomingActivitiesWidget) upcomingActivitiesWidget.innerHTML = '<p class="text-danger mb-0">Gagal memuat.</p>';
            if (adminTasksWidget) adminTasksWidget.innerHTML = '<div class="list-group-item text-danger">Gagal memuat.</div>';
            if (newResidentsWidget) newResidentsWidget.innerHTML = '<div class="list-group-item text-danger">Gagal memuat.</div>';
            if (iuranSummaryWidget) iuranSummaryWidget.innerHTML = '<h2 class="fw-bold">Error</h2>';
            if (iuranMenunggakWidget) iuranMenunggakWidget.innerHTML = '<li class="list-group-item text-danger">Gagal memuat.</li>';
        }
    }

    // --- Event Listeners ---
    const handleFilterChange = () => {
        fetchDashboardData(bulanFilter.value, tahunFilter.value);
    };

    bulanFilter.addEventListener('change', handleFilterChange);
    tahunFilter.addEventListener('change', handleFilterChange);

    // --- Initial Load ---
    setupFilters();
    fetchDashboardData(bulanFilter.value, tahunFilter.value);
}

function initWargaPage() {
    const wargaTableBody = document.getElementById('warga-table-body');
    const wargaTableHead = document.querySelector('#warga-table-body')?.closest('table')?.querySelector('thead');
    const searchInput = document.getElementById('search-warga');
    const printBtn = document.getElementById('print-warga-btn');
    const exportBtn = document.getElementById('export-warga-btn');
    const wargaModalEl = document.getElementById('wargaModal');
    const wargaModal = new bootstrap.Modal(wargaModalEl);
    const wargaForm = document.getElementById('warga-form');
    const saveWargaBtn = document.getElementById('save-warga-btn');
    const limitSelect = document.getElementById('warga-limit');
    const keluargaModalEl = document.getElementById('keluargaModal');
    const keluargaModal = new bootstrap.Modal(keluargaModalEl);
    const paginationContainer = document.getElementById('warga-pagination');
    const importModalEl = document.getElementById('importWargaModal');
    const importModal = new bootstrap.Modal(importModalEl);
    const importForm = document.getElementById('import-warga-form');
    const submitImportBtn = document.getElementById('submit-import-warga-btn');
    const downloadTemplateLink = document.getElementById('download-template-csv');
    const importResultDiv = document.getElementById('import-result');

    if (!wargaTableBody) return; // Exit if the required elements aren't on the page

    const kkSelect = document.getElementById('no_kk_select');
    const kkNewInput = document.getElementById('no_kk_new');

    // --- State ---
    let sortBy = 'no_kk';
    let sortDir = 'asc';
    let currentPage = 1;

    // --- Helper ---
    function calculateAge(birthDate) {
        if (!birthDate) return '-';
        const today = new Date();
        const birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDifference = today.getMonth() - birth.getMonth();
        if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        return age;
    }

    // --- Data Loading ---
    async function loadWarga(searchTerm = '', currentSortBy = 'nama_lengkap', currentSortDir = 'asc', page = 1, perPage = '10') {
        wargaTableBody.innerHTML = '<tr><td colspan="15" class="text-center">Memuat data...</td></tr>';
        try {
            let apiUrl = `${basePath}/api/warga?action=list&search=${encodeURIComponent(searchTerm)}&sort_by=${currentSortBy}&sort_dir=${currentSortDir}&page=${page}`;
            if (perPage !== 'all') {
                apiUrl += `&limit=${perPage}`;
            }

            const response = await fetch(apiUrl);
            const result = await response.json();
            wargaTableBody.innerHTML = ''; // Clear loading
            if (result.status === 'success' && result.data.length > 0) {
                const startIndex = (result.pagination.current_page - 1) * result.pagination.limit;
                result.data.forEach((w, index) => {
                    const tglLahirFormatted = w.tgl_lahir ? new Date(w.tgl_lahir).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '-';
                    const umur = calculateAge(w.tgl_lahir);
                    const umurText = (typeof umur === 'number') ? `${umur} tahun` : '-';
                    const fotoProfil = w.foto_profil 
                        ? `<img src="${basePath}/${w.foto_profil}" alt="Foto ${w.nama_lengkap}" class="rounded-circle" width="40" height="40" style="object-fit: cover;">`
                        : `<i class="bi bi-person-circle fs-2 text-secondary"></i>`;
                    const row = `
                        <tr>
                            <td>${startIndex + index + 1}</td>
                            <td class="text-center">${fotoProfil}</td>
                            <td><a href="${basePath}/warga/profil/${w.id}">${w.nama_lengkap}</a></td>
                            <td>${w.nik}</td>
                            <td>${w.alamat}</td>
                            <td>${w.no_telepon || '-'}</td>
                            <td>${w.jenis_kelamin || '-'}</td>
                            <td><span class="badge bg-${w.status_tinggal === 'tetap' ? 'success' : 'warning'}">${w.status_tinggal}</span></td>
                            <td>${w.pekerjaan || '-'}</td>
                            <td>${tglLahirFormatted}</td>
                            <td>${umurText}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary view-keluarga-btn" data-kk="${w.no_kk}" title="Lihat Keluarga"><i class="bi bi-person-lines-fill"></i></button>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-info edit-btn" data-id="${w.id}" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="${w.id}" data-nama="${w.nama_lengkap}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                            </td>
                        </tr>`;
                    wargaTableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                wargaTableBody.innerHTML = '<tr><td colspan="15" class="text-center">Tidak ada data ditemukan.</td></tr>';
            }
            renderPagination(paginationContainer, result.pagination, (newPage) => {
                loadWarga(searchInput.value, sortBy, sortDir, newPage, limitSelect.value);
            });
            currentPage = page;
        } catch (error) {
            wargaTableBody.innerHTML = `<tr><td colspan="15" class="text-center text-danger">Gagal memuat data.</td></tr>`;
            renderPagination(paginationContainer, null);
        }
    }

    // --- KK Dropdown Loading ---
    async function loadKKListForSelect() {
        if (!kkSelect) return;
        try {
            const response = await fetch(`${basePath}/api/warga?action=get_kk_list`);
            const result = await response.json();
            if (result.status === 'success') {
                const currentValue = kkSelect.value;
                kkSelect.innerHTML = `
                    <option value="">-- Pilih No. KK --</option>
                    <option value="new-kk">-- Buat No. KK Baru --</option>
                `;
                result.data.forEach(item => {
                    const optionText = item.kepala_keluarga
                        ? `${item.no_kk} (${item.kepala_keluarga})`
                        : item.no_kk;
                    kkSelect.insertAdjacentHTML('beforeend', `<option value="${item.no_kk}">${optionText}</option>`);
                });
                if (currentValue && kkSelect.querySelector(`option[value="${currentValue}"]`)) {
                    kkSelect.value = currentValue;
                }
            }
        } catch (error) {
            console.error('Failed to load KK list:', error);
        }
    }

    if (kkSelect) {
        kkSelect.addEventListener('change', () => {
            const isNew = kkSelect.value === 'new-kk';
            kkNewInput.classList.toggle('d-none', !isNew);
            kkNewInput.required = isNew;
        });
    }

    function updatePrintLink(searchTerm = '') {
        if (printBtn) {
            printBtn.href = `${basePath}/warga/cetak?search=${encodeURIComponent(searchTerm)}`;
        }
    }

    function updateExportLink(searchTerm = '') {
        if (exportBtn) {
            exportBtn.href = `${basePath}/api/warga/export?search=${encodeURIComponent(searchTerm)}`;
        }
    }



    // --- Event Listeners for Warga Page ---
    let debounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            currentPage = 1;
            loadWarga(searchInput.value, sortBy, sortDir, currentPage, limitSelect.value);
            updatePrintLink(searchInput.value);
            updateExportLink(searchInput.value);
        }, 300);
    });

    limitSelect.addEventListener('change', () => {
        currentPage = 1;
        loadWarga(searchInput.value, sortBy, sortDir, currentPage, limitSelect.value);
    });
    if (wargaTableHead) {
        wargaTableHead.addEventListener('click', (e) => {
            const th = e.target.closest('th.sortable');
            if (!th) return;

            const newSortBy = th.dataset.sort;
            if (sortBy === newSortBy) {
                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                sortBy = newSortBy;
                sortDir = 'asc';
            }
            currentPage = 1;

            wargaTableHead.querySelectorAll('th.sortable').forEach(header => header.classList.remove('asc', 'desc'));
            th.classList.add(sortDir);

            loadWarga(searchInput.value, sortBy, sortDir, currentPage, limitSelect.value);
        });
    }

    if (downloadTemplateLink) {
        downloadTemplateLink.href = `${basePath}/api/warga/template`;
    }

    if (submitImportBtn) {
        submitImportBtn.addEventListener('click', async () => {
            const fileInput = document.getElementById('warga_csv_file');
            if (fileInput.files.length === 0) {
                showToast('Silakan pilih file CSV terlebih dahulu.', 'error');
                return;
            }

            const formData = new FormData(importForm);
            const originalBtnHtml = submitImportBtn.innerHTML;
            submitImportBtn.disabled = true;
            submitImportBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengimpor...`;
            importResultDiv.classList.add('d-none');
            importResultDiv.innerHTML = '';

            try {
                const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                const fetchPromise = fetch(`${basePath}/api/warga/import`, { method: 'POST', body: formData });
                const [response] = await Promise.all([fetchPromise, minDelay]);

                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');

                if (result.status === 'success') {
                    let resultHtml = `<div class="alert alert-info"><strong>Ringkasan Impor:</strong><br>Berhasil: ${result.data.success_count}<br>Gagal: ${result.data.error_count}</div>`;
                    if (result.data.errors && result.data.errors.length > 0) {
                        resultHtml += `<p><strong>Contoh Error (maksimal 10):</strong></p><ul class="list-group">`;
                        result.data.errors.forEach(err => {
                            resultHtml += `<li class="list-group-item list-group-item-danger small">${err}</li>`;
                        });
                        resultHtml += `</ul>`;
                    }
                    importResultDiv.innerHTML = resultHtml;
                    importResultDiv.classList.remove('d-none');
                    loadWarga(searchInput.value, sortBy, sortDir, currentPage); // Reload table
                }
            } catch (error) {
                showToast('Terjadi kesalahan saat mengimpor data.', 'error');
            } finally {
                submitImportBtn.disabled = false;
                submitImportBtn.innerHTML = originalBtnHtml;
                fileInput.value = ''; // Clear file input
            }
        });
    }

    if (importModalEl) {
        importModalEl.addEventListener('hidden.bs.modal', () => {
            importResultDiv.classList.add('d-none');
            importResultDiv.innerHTML = '';
            importForm.reset();
        });
    }

    saveWargaBtn.addEventListener('click', async () => {
        const formData = new FormData(wargaForm);

        // Manually handle no_kk from the select/input combo
        let no_kk_value = kkSelect.value;
        if (no_kk_value === 'new-kk') {
            no_kk_value = kkNewInput.value.trim();
            if (!no_kk_value) {
                showToast('No. KK baru tidak boleh kosong.', 'error');
                return; // Stop submission
            }
        }
        formData.append('no_kk', no_kk_value);

        const originalBtnHtml = saveWargaBtn.innerHTML;
        saveWargaBtn.disabled = true;
        saveWargaBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;

        try {
            // Introduce a minimum delay to make the spinner visible
            const minDelay = new Promise(resolve => setTimeout(resolve, 500)); // 500ms delay
            const fetchPromise = fetch(`${basePath}/api/warga`, { method: 'POST', body: formData });

            const [response] = await Promise.all([fetchPromise, minDelay]);

            const result = await response.json();
            if (result.status === 'success') {
                wargaModal.hide();
                showToast(result.message, 'success');
                loadWarga(searchInput.value, sortBy, sortDir, currentPage, limitSelect.value);
                loadKKListForSelect(); // Reload KK list in case a new one was added
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveWargaBtn.disabled = false;
            saveWargaBtn.innerHTML = originalBtnHtml;
        }
    });

    wargaTableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'get_single');
            formData.append('id', id);
            const response = await fetch(`${basePath}/api/warga`, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                document.getElementById('wargaModalLabel').textContent = 'Edit Data Warga';
                document.getElementById('warga-id').value = result.data.id;
                document.getElementById('warga-action').value = 'update';
                document.getElementById('nama_lengkap').value = result.data.nama_lengkap;
                document.getElementById('nik').value = result.data.nik;
                document.getElementById('nama_panggilan').value = result.data.nama_panggilan;                
                document.getElementById('jenis_kelamin').value = result.data.jenis_kelamin;
                
                // Set KK dropdown
                kkNewInput.classList.add('d-none');
                kkNewInput.required = false;
                kkNewInput.value = '';
                if (kkSelect.querySelector(`option[value="${result.data.no_kk}"]`)) {
                    kkSelect.value = result.data.no_kk;
                } else {
                    // If KK not in list, show the 'new' input with the value
                    kkSelect.value = 'new-kk';
                    kkNewInput.classList.remove('d-none');
                    kkNewInput.required = true;
                    kkNewInput.value = result.data.no_kk;
                }

                document.getElementById('alamat').value = result.data.alamat;
                document.getElementById('no_telepon').value = result.data.no_telepon;
                document.getElementById('status_tinggal').value = result.data.status_tinggal;
                document.getElementById('pekerjaan').value = result.data.pekerjaan;
                document.getElementById('tgl_lahir').value = result.data.tgl_lahir;
                document.getElementById('status_dalam_keluarga').value = result.data.status_dalam_keluarga;
                wargaModal.show();
            }
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const id = deleteBtn.dataset.id;
            const nama = deleteBtn.dataset.nama;
            if (confirm(`Apakah Anda yakin ingin menghapus data warga "${nama}"?`)) {
                const originalIcon = deleteBtn.innerHTML;
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
                try {
                    const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    const fetchPromise = fetch(`${basePath}/api/warga`, { method: 'POST', body: formData });

                    const [response] = await Promise.all([fetchPromise, minDelay]);

                    const result = await response.json();
                    showToast(result.message, result.status === 'success' ? 'success' : 'error');
                    if (result.status === 'success') {
                        loadWarga(searchInput.value, sortBy, sortDir, currentPage, limitSelect.value);
                    } else {
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = originalIcon;
                    }
                } catch (error) {
                    showToast('Terjadi kesalahan jaringan.', 'error');
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = originalIcon;
                }
            }
        }

        const viewKeluargaBtn = e.target.closest('.view-keluarga-btn');
        if (viewKeluargaBtn) {
            const no_kk = viewKeluargaBtn.dataset.kk;
            const contentContainer = document.getElementById('keluarga-content');
            contentContainer.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
            keluargaModal.show();

            const formData = new FormData();
            formData.append('action', 'get_keluarga');
            formData.append('no_kk', no_kk);

            const response = await fetch(`${basePath}/api/warga`, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                let html = `<p>Menampilkan anggota keluarga untuk No. KK: <strong>${no_kk}</strong></p><ul class="list-group">`;
                result.data.forEach(anggota => {
                    html += `<li class="list-group-item">${anggota.nama_lengkap} (NIK: ${anggota.nik})</li>`;
                });
                html += '</ul>';
                contentContainer.innerHTML = html;
            }
        }
    });

    wargaModalEl.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        if (button && button.getAttribute('data-action') === 'add') {
            document.getElementById('wargaModalLabel').textContent = 'Tambah Warga Baru';
            wargaForm.reset();
            document.getElementById('warga-id').value = '';
            document.getElementById('warga-action').value = 'new';
            kkSelect.value = '';
            kkNewInput.classList.add('d-none');
            kkNewInput.required = false;
            kkNewInput.value = '';
            document.getElementById('foto-profil-preview').innerHTML = '';
        }
    });

    // Set initial sort indicator and load data
    const initialSortHeader = wargaTableHead?.querySelector(`th[data-sort="${sortBy}"]`);
    if (wargaTableHead && initialSortHeader) {
        initialSortHeader.classList.add(sortDir);
    }
    updatePrintLink(); // Set initial print link
    updateExportLink(); // Set initial export link
    loadKKListForSelect(); // Load KK list for the modal
    loadWarga('', sortBy, sortDir, currentPage, limitSelect.value); // Initial data load
}

function initWargaProfilePage() {
    const container = document.getElementById('warga-profile-container');
    if (!container) return;

    const wargaId = container.dataset.wargaId;

    function calculateAge(birthDate) {
        if (!birthDate) return '-';
        const today = new Date();
        const birth = new Date(birthDate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDifference = today.getMonth() - birth.getMonth();
        if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        return age;
    }

    async function loadProfile() {
        try {
            const response = await fetch(`${basePath}/api/warga?action=get_public_profile&id=${wargaId}`);
            const result = await response.json();

            if (result.status !== 'success' || !result.data) {
                throw new Error(result.message || 'Data profil tidak ditemukan.');
            }

            const profile = result.data;
            const tglLahirFormatted = profile.tgl_lahir ? new Date(profile.tgl_lahir).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '-';
            const umur = profile.tgl_lahir ? calculateAge(profile.tgl_lahir) : '-';
            const rumahInfo = (profile.blok && profile.nomor) ? `Blok ${profile.blok} No. ${profile.nomor}` : 'Belum menempati rumah';
            const fotoProfilHTML = profile.foto_profil
                ? `<img src="${basePath}/${profile.foto_profil}" alt="Foto ${profile.nama_lengkap}" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">`
                : '<i class="bi bi-person-bounding-box display-1 text-secondary"></i>';

            const profileHtml = `
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-person-circle"></i> Profil Warga</h1>
                    <a href="${basePath}/warga" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali ke Data Warga</a>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body text-center">
                                ${fotoProfilHTML}
                                <h4 class="mt-3">${profile.nama_lengkap}</h4>
                                <p class="text-muted">${profile.pekerjaan || 'Pekerjaan tidak diisi'}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">Informasi Detail</div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-4">Status dlm Keluarga</dt><dd class="col-sm-8">${profile.status_dalam_keluarga || '-'}</dd>
                                    <dt class="col-sm-4">No. Kartu Keluarga</dt><dd class="col-sm-8">${profile.no_kk}</dd>
                                    <dt class="col-sm-4">NIK</dt><dd class="col-sm-8">${profile.nik}</dd>
                                    <dt class="col-sm-4">Tanggal Lahir</dt><dd class="col-sm-8">${tglLahirFormatted} (${umur} tahun)</dd>
                                    <dt class="col-sm-4">Jenis Kelamin</dt><dd class="col-sm-8">${profile.jenis_kelamin || '-'}</dd>
                                    <dt class="col-sm-4">Agama</dt><dd class="col-sm-8">${profile.agama || '-'}</dd>
                                    <dt class="col-sm-4">Gol. Darah</dt><dd class="col-sm-8">${profile.golongan_darah || '-'}</dd>
                                    <dt class="col-sm-4">No. Telepon</dt><dd class="col-sm-8">${profile.no_telepon || '-'}</dd>
                                    <dt class="col-sm-4">Alamat Tinggal</dt><dd class="col-sm-8">${rumahInfo}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.innerHTML = profileHtml;
        } catch (error) {
            container.innerHTML = `<div class="alert alert-danger m-3">Gagal memuat profil: ${error.message}</div>`;
        }
    }

    loadProfile();
}

function initKeuanganPage() {
    const kasTableBody = document.getElementById('kas-table-body');
    const searchInput = document.getElementById('search-kas');
    const jenisFilter = document.getElementById('filter-jenis-kas');
    const kasModalEl = document.getElementById('kasModal');
    const kasModal = new bootstrap.Modal(kasModalEl);
    const kasForm = document.getElementById('kas-form');
    const saveKasBtn = document.getElementById('save-transaksi-kas-btn'); // Changed ID to be more specific
    const limitSelect = document.getElementById('kas-limit');
    const paginationContainer = document.getElementById('kas-pagination');
    const jenisSelectModal = document.getElementById('jenis');
    const kategoriSelectModal = document.getElementById('kategori');

    if (!kasTableBody) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    });

    async function updateKategoriOptions(jenis, selectedKategori = null) {
        kategoriSelectModal.innerHTML = '<option>Memuat...</option>';
        try {
            const response = await fetch(`${basePath}/api/kategori-kas`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error('Gagal memuat kategori');
            
            const kategoriList = result.data[jenis] || [];
            kategoriSelectModal.innerHTML = ''; // Clear loading/old options
            kategoriList.forEach(item => {
                const option = new Option(item.nama_kategori, item.nama_kategori);
                if (item.nama_kategori === selectedKategori) { option.selected = true; }
                kategoriSelectModal.add(option);
            });
        } catch (error) {
            kategoriSelectModal.innerHTML = '<option value="">Gagal memuat</option>';
        }
    }

    async function loadKas(searchTerm = '', jenis = '', page = 1, perPage = '10') {
        kasTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Memuat data...</td></tr>';
        try {
            let apiUrl = `${basePath}/api/kas?search=${encodeURIComponent(searchTerm)}&jenis=${encodeURIComponent(jenis)}&page=${page}`;
            if (perPage !== 'all') {
                apiUrl += `&limit=${perPage}`;
            }

            const response = await fetch(apiUrl);
            const result = await response.json();
            kasTableBody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(k => {
                    const row = `
                        <tr>
                            <td>${new Date(k.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</td>
                            <td><span class="badge bg-secondary">${k.kategori || 'Lain-lain'}</span></td>
                            <td>${k.keterangan}</td>
                            <td><span class="badge bg-${k.jenis === 'masuk' ? 'success' : 'danger'}">${k.jenis}</span></td>
                            <td>${currencyFormatter.format(k.jumlah)}</td>
                            <td>${k.pencatat}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-info edit-btn" data-id="${k.id}" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="${k.id}" data-keterangan="${k.keterangan}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                            </td>
                        </tr>`;
                    kasTableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                kasTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Tidak ada data transaksi ditemukan.</td></tr>';
            }
            renderPagination(paginationContainer, result.pagination, (newPage) => {
                loadKas(searchInput.value, jenisFilter.value, newPage, limitSelect.value);
            });
        } catch (error) {
            kasTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Gagal memuat data.</td></tr>`;
            renderPagination(paginationContainer, null);
        }
    }

    let debounceTimer;
    const combinedFilterHandler = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadKas(searchInput.value, jenisFilter.value, 1, limitSelect.value), 300);
    };

    searchInput.addEventListener('input', combinedFilterHandler);
    jenisFilter.addEventListener('change', combinedFilterHandler);
    limitSelect.addEventListener('change', () => {
        loadKas(searchInput.value, jenisFilter.value, 1, limitSelect.value);
    });

    if (jenisSelectModal) {
        jenisSelectModal.addEventListener('change', (e) => updateKategoriOptions(e.target.value));
    }

    saveKasBtn.addEventListener('click', async () => {
        const formData = new FormData(kasForm);
        const action = formData.get('action');
        const url = action === 'add' ? `${basePath}/api/kas/new` : `${basePath}/api/kas/update`;
        
        const originalBtnHtml = saveKasBtn.innerHTML;
        saveKasBtn.disabled = true;
        saveKasBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;

        try {
            const minDelay = new Promise(resolve => setTimeout(resolve, 500));
            const fetchPromise = fetch(url, { method: 'POST', body: formData });

            const [response] = await Promise.all([fetchPromise, minDelay]);

            const result = await response.json();
            if (result.status === 'success') {
                kasModal.hide();
                showToast(result.message, 'success');
                loadKas(searchInput.value, jenisFilter.value, 1, limitSelect.value);
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveKasBtn.disabled = false;
            saveKasBtn.innerHTML = originalBtnHtml;
        }
    });

    kasTableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'get_single');
            formData.append('id', id);
            const response = await fetch(`${basePath}/api/kas/update`, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                document.getElementById('kasModalLabel').textContent = 'Edit Transaksi';
                document.getElementById('kas-id').value = result.data.id;
                document.getElementById('kas-action').value = 'update';
                document.getElementById('tanggal').value = result.data.tanggal;
                document.getElementById('jenis').value = result.data.jenis;
                updateKategoriOptions(result.data.jenis, result.data.kategori);
                document.getElementById('keterangan').value = result.data.keterangan;
                document.getElementById('jumlah').value = result.data.jumlah;
                kasModal.show();
            }
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const id = deleteBtn.dataset.id;
            const keterangan = deleteBtn.dataset.keterangan;
            if (confirm(`Apakah Anda yakin ingin menghapus transaksi "${keterangan}"?`)) {
                const originalIcon = deleteBtn.innerHTML;
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
                try {
                    const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    const fetchPromise = fetch(`${basePath}/api/kas/delete`, { method: 'POST', body: formData });

                    const [response] = await Promise.all([fetchPromise, minDelay]);

                    const result = await response.json();
                    showToast(result.message, result.status === 'success' ? 'success' : 'error');
                    if (result.status === 'success') {
                        loadKas(searchInput.value, jenisFilter.value, 1, limitSelect.value);
                    } else {
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = originalIcon;
                    }
                } catch (error) {
                    showToast('Terjadi kesalahan jaringan.', 'error');
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = originalIcon;
                }
            }
        }
    });

    kasModalEl.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        if (button && button.getAttribute('data-action') === 'add') {
            document.getElementById('kasModalLabel').textContent = 'Tambah Transaksi Baru';
            kasForm.reset();
            document.getElementById('kas-id').value = '';
            document.getElementById('kas-action').value = 'add';
            // Set tanggal default ke hari ini
            document.getElementById('tanggal').valueAsDate = new Date();
            updateKategoriOptions(document.getElementById('jenis').value);
        }
    });

    loadKas(searchInput.value, jenisFilter.value, 1, limitSelect.value);
}

function initUsersPage() {
    const usersTableBody = document.getElementById('users-table-body');
    const userModalEl = document.getElementById('userModal');
    const userModal = new bootstrap.Modal(userModalEl);
    const userForm = document.getElementById('user-form');
    const saveUserBtn = document.getElementById('save-user-btn');

    if (!usersTableBody) return;

    async function loadUsers() {
        usersTableBody.innerHTML = '<tr><td colspan="6" class="text-center">Memuat data...</td></tr>';
        try {
            const response = await fetch(`${basePath}/api/users`);
            const result = await response.json();
            usersTableBody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach((user, index) => {
                    const row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${user.username}</td>
                            <td>${user.nama_lengkap || '-'}</td>
                            <td><span class="badge bg-info">${user.role}</span></td>
                            <td>${new Date(user.created_at).toLocaleDateString('id-ID')}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-info edit-btn" data-id="${user.id}" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="${user.id}" data-username="${user.username}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                            </td>
                        </tr>`;
                    usersTableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                usersTableBody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada data pengguna ditemukan.</td></tr>';
            }
        } catch (error) {
            usersTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Gagal memuat data.</td></tr>`;
        }
    }

    saveUserBtn.addEventListener('click', async () => {
        const formData = new FormData(userForm);
        const action = formData.get('action');
        const url = action === 'add' ? `${basePath}/api/users/new` : `${basePath}/api/users/update`;

        const originalBtnHtml = saveUserBtn.innerHTML;
        saveUserBtn.disabled = true;
        saveUserBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;

        try {
            const minDelay = new Promise(resolve => setTimeout(resolve, 500));
            const fetchPromise = fetch(url, { method: 'POST', body: formData });

            const [response] = await Promise.all([fetchPromise, minDelay]);

            const result = await response.json();
            if (result.status === 'success') {
                userModal.hide();
                showToast(result.message, 'success');
                loadUsers();
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveUserBtn.disabled = false;
            saveUserBtn.innerHTML = originalBtnHtml;
        }
    });

    usersTableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'get_single');
            formData.append('id', id);
            const response = await fetch(`${basePath}/api/users/update`, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                document.getElementById('userModalLabel').textContent = 'Edit Pengguna';
                userForm.reset();
                document.getElementById('user-id').value = result.data.id;
                document.getElementById('user-action').value = 'update';
                document.getElementById('username').value = result.data.username;
                document.getElementById('nama_lengkap').value = result.data.nama_lengkap;
                document.getElementById('role').value = result.data.role;
                document.getElementById('password').required = false;
                document.getElementById('password-help').style.display = 'block';
                userModal.show();
            }
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const id = deleteBtn.dataset.id;
            const username = deleteBtn.dataset.username;
            if (confirm(`Apakah Anda yakin ingin menghapus pengguna "${username}"?`)) {
                const originalIcon = deleteBtn.innerHTML;
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
                try {
                    const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    const fetchPromise = fetch(`${basePath}/api/users/delete`, { method: 'POST', body: formData });

                    const [response] = await Promise.all([fetchPromise, minDelay]);

                    const result = await response.json();
                    showToast(result.message, result.status === 'success' ? 'success' : 'error');
                    if (result.status === 'success') {
                        loadUsers();
                    } else {
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = originalIcon;
                    }
                } catch (error) {
                    showToast('Terjadi kesalahan jaringan.', 'error');
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = originalIcon;
                }
            }
        }
    });

    userModalEl.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        if (button && button.getAttribute('data-action') === 'add') {
            document.getElementById('userModalLabel').textContent = 'Tambah Pengguna Baru';
            userForm.reset();
            document.getElementById('user-id').value = '';
            document.getElementById('user-action').value = 'add';
            document.getElementById('password').required = true;
            document.getElementById('password-help').style.display = 'none';
        }
    });

    loadUsers();
}

function initRumahPage() {
    const rumahTableBody = document.getElementById('rumah-table-body');
    const rumahModalEl = document.getElementById('rumahModal');
    const rumahModal = new bootstrap.Modal(rumahModalEl);
    const rumahForm = document.getElementById('rumah-form');
    const saveRumahBtn = document.getElementById('save-rumah-btn');
    const kkSelect = document.getElementById('no_kk_penghuni');
    const anggotaKeluargaModalEl = document.getElementById('anggotaKeluargaModal');
    const anggotaKeluargaModal = new bootstrap.Modal(anggotaKeluargaModalEl);
    const occupantHistoryModalEl = document.getElementById('occupantHistoryModal');
    const occupantHistoryModal = new bootstrap.Modal(occupantHistoryModalEl);
    const printHistoryBtn = document.getElementById('print-history-btn');
    const kepemilikanFilter = document.getElementById('filter-kepemilikan-rumah');
    const searchInput = document.getElementById('search-rumah');
    const exportRumahBtn = document.getElementById('export-rumah-btn');

    if (!rumahTableBody) return;

    function updateExportLink(statusKepemilikan = 'semua', searchTerm = '') {
        if (exportRumahBtn) {
            const url = new URL(`${basePath}/api/rumah/export`, window.location.origin);
            url.searchParams.set('status_kepemilikan', statusKepemilikan);
            url.searchParams.set('search', searchTerm);
            exportRumahBtn.href = url.toString();
        }
    }

    async function loadRumah(statusKepemilikan = 'semua', searchTerm = '') {
        rumahTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Memuat data...</td></tr>';
        try {
            const response = await fetch(`${basePath}/api/rumah?action=list&status_kepemilikan=${statusKepemilikan}&search=${encodeURIComponent(searchTerm)}`);
            const result = await response.json();
            rumahTableBody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(r => {
                    const statusTinggal = r.status_tinggal
                        ? `<span class="badge bg-${r.status_tinggal === 'tetap' ? 'success' : 'warning'}">${r.status_tinggal}</span>`
                        : '<span class="text-muted">-</span>';
                    const tglMasukFormatted = r.tanggal_masuk
                        ? new Date(r.tanggal_masuk).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
                        : '<span class="text-muted">-</span>';
                    const row = `
                        <tr>
                            <td>
                                <a href="${basePath}/rumah/detail/${r.id}"><strong>${r.blok} / ${r.nomor}</strong></a>
                            </td>
                            <td>${r.pemilik || '-'}</td>
                            <td>
                                ${r.kepala_keluarga ? `<a href="#" class="view-anggota" data-kk="${r.no_kk_penghuni}">${r.kepala_keluarga}</a>` : '<span class="text-muted">Tidak berpenghuni</span>'}
                            </td>
                            <td>${tglMasukFormatted}</td>
                            <td>${statusTinggal}</td>
                            <td>
                                ${r.jumlah_anggota > 0 ? `<a href="#" class="view-anggota" data-kk="${r.no_kk_penghuni}">${r.jumlah_anggota} orang</a>` : '0'}
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-info edit-btn" data-id="${r.id}" title="Edit Data Rumah"><i class="bi bi-pencil-fill"></i></button>
                                    <button class="btn btn-sm btn-secondary history-btn" data-id="${r.id}" data-blok="${r.blok}" data-nomor="${r.nomor}" title="Lihat Histori Penghuni"><i class="bi bi-clock-history"></i></button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="${r.id}" data-blok="${r.blok}" data-nomor="${r.nomor}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </td>
                        </tr>`;
                    rumahTableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                rumahTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Tidak ada data rumah ditemukan.</td></tr>';
            }
        } catch (error) {
            rumahTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Gagal memuat data.</td></tr>`;
        }
    }

    async function loadKKList() {
        try {
            const response = await fetch(`${basePath}/api/rumah?action=get_kk_list`);
            const result = await response.json();
            if (result.status === 'success') {
                kkSelect.innerHTML = '<option value="">-- Tidak Berpenghuni --</option>';
                result.data.forEach(kk => {
                    kkSelect.insertAdjacentHTML('beforeend', `<option value="${kk.no_kk}">${kk.nama_lengkap} (KK: ${kk.no_kk})</option>`);
                });
            }
        } catch (error) {
            console.error('Gagal memuat daftar KK:', error);
        }
    }

    saveRumahBtn.addEventListener('click', async () => {
        const formData = new FormData(rumahForm);
        const url = `${basePath}/api/rumah`;
        
        const originalBtnHtml = saveRumahBtn.innerHTML;
        saveRumahBtn.disabled = true;
        saveRumahBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;

        try {
            const minDelay = new Promise(resolve => setTimeout(resolve, 500));
            const fetchPromise = fetch(url, { method: 'POST', body: formData });

            const [response] = await Promise.all([fetchPromise, minDelay]);

            const result = await response.json();
            if (result.status === 'success') {
                rumahModal.hide();
                showToast(result.message, 'success');
                loadRumah(kepemilikanFilter.value, searchInput.value);
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveRumahBtn.disabled = false;
            saveRumahBtn.innerHTML = originalBtnHtml;
        }
    });

    rumahTableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'get_single');
            formData.append('id', id);
            const response = await fetch(`${basePath}/api/rumah`, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                document.getElementById('rumahModalLabel').textContent = 'Edit Data Rumah';
                rumahForm.reset();
                document.getElementById('rumah-id').value = result.data.id;
                document.getElementById('rumah-action').value = 'update';
                document.getElementById('blok').value = result.data.blok;
                document.getElementById('nomor').value = result.data.nomor;
                document.getElementById('pemilik').value = result.data.pemilik;
                document.getElementById('no_kk_penghuni').value = result.data.no_kk_penghuni;
                rumahModal.show();
            }
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const { id, blok, nomor } = deleteBtn.dataset;
            if (confirm(`Apakah Anda yakin ingin menghapus rumah di Blok ${blok} No. ${nomor}?`)) {
                const originalIcon = deleteBtn.innerHTML;
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
                try {
                    const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    const fetchPromise = fetch(`${basePath}/api/rumah`, { method: 'POST', body: formData });

                    const [response] = await Promise.all([fetchPromise, minDelay]);

                    const result = await response.json();
                    showToast(result.message, result.status === 'success' ? 'success' : 'error');
                    if (result.status === 'success') {
                        loadRumah(kepemilikanFilter.value, searchInput.value);
                    } else {
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = originalIcon;
                    }
                } catch (error) {
                    showToast('Terjadi kesalahan jaringan.', 'error');
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = originalIcon;
                }
            }
        }

        const historyBtn = e.target.closest('.history-btn');
        if (historyBtn) {
            const { id, blok, nomor } = historyBtn.dataset;
            document.getElementById('history-rumah-info').textContent = `Blok ${blok} No. ${nomor}`;
            if (printHistoryBtn) printHistoryBtn.dataset.id = id; // Store the ID on the print button
            const contentContainer = document.getElementById('occupant-history-content');
            contentContainer.innerHTML = '<tr><td colspan="4" class="text-center"><div class="spinner-border"></div></td></tr>';
            occupantHistoryModal.show();

            try {
                const response = await fetch(`${basePath}/api/rumah?action=get_occupant_history&rumah_id=${id}`);
                const result = await response.json();
                contentContainer.innerHTML = '';
                if (result.status === 'success' && result.data.length > 0) {
                    result.data.forEach(h => {
                        const tglMasuk = new Date(h.tanggal_masuk).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                        const tglKeluar = h.tanggal_keluar ? new Date(h.tanggal_keluar).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '<span class="badge bg-success">Penghuni Saat Ini</span>';
                        const catatan = h.catatan || 'Klik untuk menambah catatan';
                        const catatanClass = h.catatan ? '' : 'text-muted fst-italic';
                        const rowHtml = `
                            <tr>
                                <td>${h.kepala_keluarga || '(Data KK tidak ditemukan)'}</td>
                                <td>${tglMasuk}</td>
                                <td>${tglKeluar}</td>
                                <td class="editable-note" data-history-id="${h.id}" title="Klik untuk mengedit catatan">
                                    <span class="${catatanClass}">${catatan}</span>
                                </td>
                            </tr>
                        `;
                        contentContainer.insertAdjacentHTML('beforeend', rowHtml);
                    });
                } else if (result.status === 'success') {
                    contentContainer.innerHTML = '<tr><td colspan="4" class="text-center">Belum ada histori penghuni untuk rumah ini.</td></tr>';
                } else { throw new Error(result.message); }
            } catch (error) {
                contentContainer.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Gagal memuat histori: ${error.message}</td></tr>`;
            }
        }

        const viewAnggotaBtn = e.target.closest('.view-anggota');
        if (viewAnggotaBtn) {
            e.preventDefault();
            const no_kk = viewAnggotaBtn.dataset.kk;
            const contentContainer = document.getElementById('anggota-keluarga-content');
            contentContainer.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
            anggotaKeluargaModal.show();
            const response = await fetch(`${basePath}/api/rumah?action=get_anggota_keluarga&no_kk=${no_kk}`);
            const result = await response.json();
            if (result.status === 'success') {
                let html = '<ul class="list-group">';
                result.data.forEach(anggota => {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center">${anggota.nama_lengkap} <span class="badge bg-secondary">${anggota.status_dalam_keluarga || 'Lainnya'}</span></li>`;
                });
                html += '</ul>';
                contentContainer.innerHTML = html;
            }
        }
    });

    // Listener for inline editing of history notes
    const historyContent = document.getElementById('occupant-history-content');
    if (historyContent) {
        historyContent.addEventListener('click', function(e) {
            const target = e.target.closest('.editable-note');
            // Do nothing if not clicking the note cell, or if it's already an input
            if (!target || target.querySelector('input')) return;

            const historyId = target.dataset.historyId;
            const span = target.querySelector('span');
            const currentNote = span.textContent === 'Klik untuk menambah catatan' ? '' : span.textContent;

            // Replace span with an input field
            target.innerHTML = `<input type="text" class="form-control form-control-sm" value="${currentNote}" data-history-id="${historyId}">`;
            const input = target.querySelector('input');
            input.focus();

            // Function to save the note
            const saveNote = async () => {
                const newNote = input.value.trim();
                
                // Show a spinner while saving
                target.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';

                try {
                    const formData = new FormData();
                    formData.append('action', 'update_history_note');
                    formData.append('history_id', historyId);
                    formData.append('catatan', newNote);

                    const response = await fetch(`${basePath}/api/rumah`, { method: 'POST', body: formData });
                    const result = await response.json();

                    if (result.status !== 'success') throw new Error(result.message);
                    
                    // Update UI with the new note
                    const noteText = newNote || 'Klik untuk menambah catatan';
                    const noteClass = newNote ? '' : 'text-muted fst-italic';
                    target.innerHTML = `<span class="${noteClass}">${noteText}</span>`;

                } catch (error) {
                    showToast(`Gagal menyimpan: ${error.message}`, 'error');
                    // Revert to original text on error
                    const noteText = currentNote || 'Klik untuk menambah catatan';
                    const noteClass = currentNote ? '' : 'text-muted fst-italic';
                    target.innerHTML = `<span class="${noteClass}">${noteText}</span>`;
                }
            };

            input.addEventListener('blur', saveNote);
            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    input.blur(); // Trigger saveNote on Enter
                } else if (event.key === 'Escape') {
                    // Revert to original text without saving
                    const noteText = currentNote || 'Klik untuk menambah catatan';
                    const noteClass = currentNote ? '' : 'text-muted fst-italic';
                    target.innerHTML = `<span class="${noteClass}">${noteText}</span>`;
                }
            });
        });
    }

    // Listener for the print history button in the modal
    if (printHistoryBtn) {
        printHistoryBtn.addEventListener('click', () => {
            const rumahId = printHistoryBtn.dataset.id;
            window.open(`${basePath}/rumah/histori/cetak?id=${rumahId}`, '_blank');
        });
    }

    let debounceTimer;
    const combinedFilterHandler = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            loadRumah(kepemilikanFilter.value, searchInput.value);
            updateExportLink(kepemilikanFilter.value, searchInput.value);
        }, 300);
    };

    if (searchInput) {
        searchInput.addEventListener('input', combinedFilterHandler);
    }
 
    if (kepemilikanFilter) {
        kepemilikanFilter.addEventListener('change', combinedFilterHandler);
    }

    rumahModalEl.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        if (button && button.getAttribute('data-action') === 'add') {
            document.getElementById('rumahModalLabel').textContent = 'Tambah Rumah Baru';
            rumahForm.reset();
            document.getElementById('rumah-id').value = '';
            document.getElementById('rumah-action').value = 'add';
        }
    });

    updateExportLink(kepemilikanFilter.value, searchInput.value);
    loadRumah(kepemilikanFilter.value, searchInput.value);
    loadKKList();
}

function initRumahDetailPage() {
    const container = document.getElementById('rumah-detail-container');
    if (!container) return;

    const rumahId = container.dataset.rumahId;
    const alamatEl = document.getElementById('rumah-detail-alamat');
    const infoContentEl = document.getElementById('rumah-info-content');
    const historyContentEl = document.getElementById('rumah-history-content');
    const printHistoryBtn = document.getElementById('print-history-btn');

    async function loadDetail() {
        try {
            const response = await fetch(`${basePath}/api/rumah?action=get_detail&id=${rumahId}`);
            const result = await response.json();

            if (result.status !== 'success' || !result.data.info) {
                throw new Error(result.message || 'Data rumah tidak ditemukan.');
            }

            const { info, anggota, histori } = result.data;

            // Render Header
            const alamatText = `Blok ${info.blok} No. ${info.nomor}`;
            alamatEl.textContent = alamatText;
            document.title = `Detail Rumah - ${alamatText}`;

            // Render Info Rumah & Penghuni
            let infoHtml = `
                <dl class="row">
                    <dt class="col-sm-4">Pemilik Properti</dt>
                    <dd class="col-sm-8">${info.pemilik || '-'}</dd>
                </dl>
                <hr>
                <h5>Penghuni Saat Ini</h5>
            `;
            if (info.kepala_keluarga) {
                infoHtml += `
                    <dl class="row">
                        <dt class="col-sm-4">Kepala Keluarga</dt>
                        <dd class="col-sm-8">${info.kepala_keluarga}</dd>
                        <dt class="col-sm-4">No. KK</dt>
                        <dd class="col-sm-8">${info.no_kk_penghuni}</dd>
                        <dt class="col-sm-4">Status Tinggal</dt>
                        <dd class="col-sm-8"><span class="badge bg-${info.status_tinggal === 'tetap' ? 'success' : 'warning'}">${info.status_tinggal}</span></dd>
                    </dl>
                    <h6>Anggota Keluarga:</h6>
                    <ul class="list-group list-group-flush">
                `;
                if (anggota.length > 0) {
                    anggota.forEach(a => {
                        infoHtml += `<li class="list-group-item">${a.nama_lengkap} <span class="text-muted small">(${a.status_dalam_keluarga})</span></li>`;
                    });
                } else {
                    infoHtml += `<li class="list-group-item text-muted">Hanya kepala keluarga yang terdata.</li>`;
                }
                infoHtml += `</ul>`;
            } else {
                infoHtml += `<p class="text-muted">Rumah ini sedang tidak berpenghuni.</p>`;
            }
            infoContentEl.innerHTML = infoHtml;

            // Render Histori
            historyContentEl.innerHTML = '';
            if (histori.length > 0) {
                histori.forEach(h => {
                    const tglMasuk = new Date(h.tanggal_masuk).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                    const tglKeluar = h.tanggal_keluar ? new Date(h.tanggal_keluar).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '<span class="badge bg-success">Sekarang</span>';
                    historyContentEl.innerHTML += `
                        <tr>
                            <td>${h.kepala_keluarga || '(Data tidak ada)'}</td>
                            <td>${tglMasuk}</td>
                            <td>${tglKeluar}</td>
                        </tr>
                    `;
                });
                printHistoryBtn.disabled = false;
                printHistoryBtn.onclick = () => {
                    window.open(`${basePath}/rumah/histori/cetak?id=${rumahId}`, '_blank');
                };
            } else {
                historyContentEl.innerHTML = `<tr><td colspan="3" class="text-center text-muted">Belum ada histori.</td></tr>`;
            }

        } catch (error) {
            const errorHtml = `<div class="alert alert-danger">${error.message}</div>`;
            alamatEl.textContent = 'Error';
            infoContentEl.innerHTML = errorHtml;
            historyContentEl.innerHTML = `<tr><td colspan="3" class="text-center text-danger">Gagal memuat.</td></tr>`;
        }
    }

    loadDetail();
}

function initIuranPage() {
    const iuranTableBody = document.getElementById('iuran-table-body');
    const bulanFilter = document.getElementById('filter-bulan');
    const tahunFilter = document.getElementById('filter-tahun');
    const searchInput = document.getElementById('search-iuran');
    const statusFilter = document.getElementById('filter-status-pembayaran');
    const bayarModalEl = document.getElementById('bayarModal');
    const limitSelect = document.getElementById('iuran-limit');
    const paginationContainer = document.getElementById('iuran-pagination');
    const printBtn = document.getElementById('cetak-iuran-btn');
    const saveBayarBtn = document.getElementById('save-bayar-btn');

    if (!iuranTableBody) return;
    
    // Inisialisasi modal hanya jika elemennya ada
    const bayarModal = bayarModalEl ? new bootstrap.Modal(bayarModalEl) : null;
    const currencyFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    });

    async function loadIuranSummary() {
        const totalPemasukanEl = document.getElementById('total-pemasukan-iuran');
        const jumlahBelumBayarEl = document.getElementById('jumlah-belum-bayar');

        if (!totalPemasukanEl || !jumlahBelumBayarEl) return;

        totalPemasukanEl.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';
        jumlahBelumBayarEl.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';

        const tahun = tahunFilter.value;
        const bulan = bulanFilter.value;
        const searchTerm = searchInput.value;

        try {
            const response = await fetch(`${basePath}/api/iuran?action=get_summary&tahun=${tahun}&bulan=${bulan}&search=${encodeURIComponent(searchTerm)}`);
            const result = await response.json();
            if (result.status === 'success') {
                totalPemasukanEl.textContent = currencyFormatter.format(result.data.total_pemasukan);
                jumlahBelumBayarEl.textContent = currencyFormatter.format(result.data.jumlah_belum_bayar);
            } else {
                totalPemasukanEl.textContent = 'Error';
                jumlahBelumBayarEl.textContent = 'Error';
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Error loading iuran summary:', error);
        }
    }

    function updatePrintLink() {
        if (!printBtn) return;
        const tahun = tahunFilter.value;
        const bulan = bulanFilter.value;
        const status = statusFilter.value;
        const searchTerm = searchInput.value;
        
        const url = new URL(`${basePath}/iuran/cetak`, window.location.origin);
        url.searchParams.set('tahun', tahun);
        url.searchParams.set('bulan', bulan);
        url.searchParams.set('status', status);
        url.searchParams.set('search', searchTerm);
        
        printBtn.href = url.toString();
    }

    // --- Setup Filter ---
    function setupFilters() {
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;

        // Populate years
        for (let i = 0; i < 5; i++) {
            const year = currentYear - i;
            tahunFilter.add(new Option(year, year));
        }

        // Populate months
        const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        months.forEach((month, index) => {
            bulanFilter.add(new Option(month, index + 1));
        });

        // Set default to current month and year
        bulanFilter.value = currentMonth;
        tahunFilter.value = currentYear;
    }

    async function loadIuran(page = 1) {
        const tahun = tahunFilter.value;
        const bulan = bulanFilter.value;
        const searchTerm = searchInput.value;
        const status = statusFilter.value;
        const perPage = limitSelect.value;
        if (!tahun || !bulan) return;

        iuranTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Memuat data...</td></tr>';
        try {
            let apiUrl = `${basePath}/api/iuran?action=list&tahun=${tahun}&bulan=${bulan}&status=${status}&search=${encodeURIComponent(searchTerm)}&page=${page}`;
            if (perPage !== 'all') {
                apiUrl += `&limit=${perPage}`;
            }
            const response = await fetch(apiUrl);
            const result = await response.json();
            iuranTableBody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(r => {
                    const isPaid = r.iuran_id !== null;
                    const statusBadge = isPaid
                        ? `<span class="badge bg-success">Lunas</span>`
                        : `<span class="badge bg-warning text-dark">Belum Lunas</span>`;
                    const tglBayar = isPaid ? new Date(r.tanggal_bayar).toLocaleDateString('id-ID') : '-';
                    const paymentBtn = isPaid
                        ? `<button class="btn btn-sm btn-secondary" disabled>Lunas</button>`
                        : `<button class="btn btn-sm btn-primary bayar-btn" data-no-kk="${r.no_kk}" data-nama="${r.kepala_keluarga || `KK: ${r.no_kk}`}">Tandai Lunas</button>`;

                    const row = `
                        <tr>
                            <td>${r.kepala_keluarga || `(KK: ${r.no_kk})`}</td>
                            <td>${r.alamat}</td>
                            <td>${statusBadge}</td>
                            <td>${tglBayar}</td>
                            <td class="text-end">
                                <div class="btn-group" role="group">
                                    ${paymentBtn}
                                    <a href="${basePath}/iuran/histori/${r.no_kk}/kk" class="btn btn-sm btn-outline-info" title="Lihat Histori"><i class="bi bi-clock-history"></i></a>
                                </div>
                            </td>
                        </tr>`;
                    iuranTableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                iuranTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada data warga ditemukan.</td></tr>';
            }
            renderPagination(paginationContainer, result.pagination, (newPage) => {
                loadIuran(newPage);
            });
        } catch (error) {
            iuranTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Gagal memuat data.</td></tr>`;
            renderPagination(paginationContainer, null);
        }
    }

    let debounceTimer;
    const combinedFilterHandler = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            loadIuran(1);
            loadIuranSummary();
            updatePrintLink();
        }, 300);
    };

    searchInput.addEventListener('input', combinedFilterHandler);
    bulanFilter.addEventListener('change', combinedFilterHandler);
    tahunFilter.addEventListener('change', combinedFilterHandler);
    statusFilter.addEventListener('change', combinedFilterHandler);
    limitSelect.addEventListener('change', combinedFilterHandler);

    iuranTableBody.addEventListener('click', async (e) => {
        const bayarBtn = e.target.closest('.bayar-btn');        
        if (bayarBtn && bayarModal) { // Pastikan modal ada
            const tahun = tahunFilter.value;
            const bulan = bulanFilter.value;

            document.getElementById('nama-warga-bayar').textContent = bayarBtn.dataset.nama;
            document.getElementById('periode-bayar').textContent = `${bulanFilter.options[bulanFilter.selectedIndex].text} ${tahun}`;
            const hiddenInput = document.getElementById('bayar-warga-id'); // Assuming this ID exists
            hiddenInput.name = 'no_kk'; // Change name to match backend
            hiddenInput.value = bayarBtn.dataset.noKk;
            document.getElementById('bayar-periode-tahun').value = tahun;
            document.getElementById('bayar-periode-bulan').value = bulan;
            document.getElementById('catatan_bayar').value = '';
            document.getElementById('bayar-tanggal').valueAsDate = new Date();

            // Fetch the correct fee for the period
            const jumlahInput = document.getElementById('bayar-jumlah');
            jumlahInput.value = ''; // Clear previous value
            jumlahInput.placeholder = 'Memuat...';
            
            bayarModal.show();

            const response = await fetch(`${basePath}/api/iuran?action=get_fee_for_period&tahun=${tahun}&bulan=${bulan}`);
            const result = await response.json();
            jumlahInput.value = result.data.fee || 0;
        }
    });

    if (saveBayarBtn && bayarModal) {        
        saveBayarBtn.addEventListener('click', async () => {
        const form = document.getElementById('bayar-form'); // Pastikan form ada
        const formData = new FormData(form);
        formData.append('action', 'bayar');

        const originalBtnHtml = saveBayarBtn.innerHTML;
        saveBayarBtn.disabled = true;
        saveBayarBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;

        try {
            const minDelay = new Promise(resolve => setTimeout(resolve, 500));
            const fetchPromise = fetch(`${basePath}/api/iuran`, { method: 'POST', body: formData });

            const [response] = await Promise.all([fetchPromise, minDelay]);

            const result = await response.json();
            if (result.status === 'success') {
                bayarModal.hide();
                showToast(result.message, 'success');
                loadIuran(1);
                loadIuranSummary(); // Also refresh the summary
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveBayarBtn.disabled = false;
            saveBayarBtn.innerHTML = originalBtnHtml;
        }
    });    }

    setupFilters();
    loadIuranSummary(); // Load summary on initial page load
    loadIuran(1);
    updatePrintLink(); // Initial call
}

function initIuranHistoriPage() {
    const wargaNameEl = document.getElementById('histori-warga-nama');
    const historyTableBody = document.getElementById('histori-iuran-table-body');
    const summaryEl = document.getElementById('histori-summary');

    if (!wargaNameEl || !historyTableBody) return;

    // Extract noKk from path: /iuran/histori/357.../kk
    const pathParts = window.location.pathname.split('/');
    const noKk = pathParts.length > 2 ? pathParts[pathParts.length - 2] : null;

    const currencyFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    });

    if (!noKk) {
        wargaNameEl.textContent = 'Error';
        historyTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">ID Warga tidak ditemukan di URL.</td></tr>';
        return;
    }

    async function loadHistory() {
        try {
            const response = await fetch(`${basePath}/api/iuran?action=get_history&no_kk=${noKk}`);
            const result = await response.json();

            if (result.status === 'success') {
                const { warga, history } = result.data;
                wargaNameEl.textContent = warga.nama_lengkap;
                document.title = `Histori Iuran - ${warga.nama_lengkap}`;

                historyTableBody.innerHTML = '';
                if (history.length > 0) {
                    const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                    history.forEach(item => {
                        const periode = `${months[item.periode_bulan - 1]} ${item.periode_tahun}`;
                        const tglBayar = new Date(item.tanggal_bayar).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                        const row = `
                            <tr>
                                <td>${periode}</td>
                                <td>${currencyFormatter.format(item.jumlah)}</td>
                                <td>${tglBayar}</td>
                                <td>${item.catatan || '-'}</td>
                            </tr>
                        `;
                        historyTableBody.insertAdjacentHTML('beforeend', row);
                    });
                    const totalPaid = history.reduce((acc, item) => acc + parseFloat(item.jumlah), 0);
                    if (summaryEl) {
                        summaryEl.innerHTML = `Total iuran yang telah dibayarkan: <strong>${currencyFormatter.format(totalPaid)}</strong> (${history.length} bulan).`;
                    }
                } else {
                    historyTableBody.innerHTML = '<tr><td colspan="4" class="text-center">Belum ada riwayat pembayaran.</td></tr>';
                    if (summaryEl) summaryEl.innerHTML = '';
                }
            } else { throw new Error(result.message); }
        } catch (error) {
            wargaNameEl.textContent = 'Error';
            historyTableBody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">Gagal memuat histori: ${error.message}</td></tr>`;
        }
    }
    loadHistory();
}

function initKegiatanPage() {
    const kegiatanList = document.getElementById('kegiatan-list');
    const isAdmin = (typeof userRole !== 'undefined' && userRole === 'admin');

    if (!kegiatanList) return;

    async function loadKegiatan() {
        kegiatanList.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';
        try {
            const response = await fetch(`${basePath}/api/kegiatan?action=list`);
            const result = await response.json();
            kegiatanList.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(k => {
                    const tgl = new Date(k.tanggal_kegiatan);
                    const tglFormatted = tgl.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                    const adminControls = isAdmin ? `
                        <div class="card-footer bg-transparent border-top-0 d-flex flex-wrap gap-2">
                            <button class="btn btn-sm btn-info edit-btn" data-id="${k.id}"><i class="bi bi-pencil-fill"></i> Edit</button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${k.id}" data-judul="${k.judul}"><i class="bi bi-trash-fill"></i> Hapus</button>
                            <a href="${basePath}/kegiatan/undangan?id=${k.id}" target="_blank" class="btn btn-sm btn-success"><i class="bi bi-printer-fill"></i> Cetak Undangan</a>
                        </div>` : '';

                    const card = `
                        <div class="col-md-6 col-lg-4 mb-4" id="kegiatan-${k.id}">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title">${k.judul}</h5>
                                    <h6 class="card-subtitle mb-2 text-muted">${tglFormatted}</h6>
                                    <p class="card-text">${k.deskripsi || ''}</p>
                                    <p class="card-text"><small class="text-muted">Lokasi: ${k.lokasi || 'Tidak ditentukan'}</small></p>
                                </div>
                                ${adminControls}
                            </div>
                        </div>`;
                    kegiatanList.insertAdjacentHTML('beforeend', card);
                });
            } else {
                kegiatanList.innerHTML = '<div class="col-12"><div class="alert alert-info">Belum ada kegiatan yang dijadwalkan.</div></div>';
            }
        } catch (error) {
            kegiatanList.innerHTML = '<div class="col-12"><div class="alert alert-danger">Gagal memuat data kegiatan.</div></div>';
        }
    }

    if (isAdmin) {
        const kegiatanModalEl = document.getElementById('kegiatanModal');
        const kegiatanModal = new bootstrap.Modal(kegiatanModalEl);
        const kegiatanForm = document.getElementById('kegiatan-form');
        const saveKegiatanBtn = document.getElementById('save-kegiatan-btn');

        kegiatanModalEl.addEventListener('show.bs.modal', async (event) => {
            const button = event.relatedTarget;
            const action = button.getAttribute('data-action');
            kegiatanForm.reset();
            document.getElementById('kegiatan-action').value = action;

            if (action === 'add') {
                document.getElementById('kegiatanModalLabel').textContent = 'Tambah Kegiatan Baru';
                document.getElementById('kegiatan-id').value = '';
            } else { // edit
                document.getElementById('kegiatanModalLabel').textContent = 'Edit Kegiatan';
                const id = button.dataset.id;
                document.getElementById('kegiatan-id').value = id;
                
                const formData = new FormData();
                formData.append('action', 'get_single');
                formData.append('id', id);
                const response = await fetch(`${basePath}/api/kegiatan`, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    document.getElementById('judul').value = result.data.judul;
                    document.getElementById('deskripsi').value = result.data.deskripsi;
                    document.getElementById('tanggal_kegiatan').value = result.data.tanggal_kegiatan.slice(0, 16);
                    document.getElementById('lokasi').value = result.data.lokasi;
                }
            }
        });

        saveKegiatanBtn.addEventListener('click', async () => {
            const formData = new FormData(kegiatanForm);
            
            const originalBtnHtml = saveKegiatanBtn.innerHTML;
            saveKegiatanBtn.disabled = true;
            saveKegiatanBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;

            try {
                const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                const fetchPromise = fetch(`${basePath}/api/kegiatan`, { method: 'POST', body: formData });

                const [response] = await Promise.all([fetchPromise, minDelay]);

                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    kegiatanModal.hide();
                    loadKegiatan();
                    // Check if there's a WhatsApp URL in the response
                    if (result.whatsapp_url) {
                        if (confirm('Kegiatan berhasil dibuat. Kirim undangan via WhatsApp sekarang?')) {
                            window.open(result.whatsapp_url, '_blank');
                        }
                    }
                }
            } catch (error) {
                showToast('Terjadi kesalahan jaringan.', 'error');
            } finally {
                saveKegiatanBtn.disabled = false;
                saveKegiatanBtn.innerHTML = originalBtnHtml;
            }
        });

        kegiatanList.addEventListener('click', async (e) => {
            const deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                const { id, judul } = deleteBtn.dataset;
                if (confirm(`Yakin ingin menghapus kegiatan "${judul}"?`)) {
                    const originalIcon = deleteBtn.innerHTML;
                    deleteBtn.disabled = true;
                    deleteBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
                    try {
                        const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('id', id);
                        const fetchPromise = fetch(`${basePath}/api/kegiatan`, { method: 'POST', body: formData });

                        const [response] = await Promise.all([fetchPromise, minDelay]);

                        const result = await response.json();
                        showToast(result.message, result.status === 'success' ? 'success' : 'error');
                        if (result.status === 'success') {
                            loadKegiatan();
                        } else {
                            deleteBtn.disabled = false;
                            deleteBtn.innerHTML = originalIcon;
                        }
                    } catch (error) {
                        showToast('Terjadi kesalahan jaringan.', 'error');
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = originalIcon;
                    }
                }
            }

            const editBtn = e.target.closest('.edit-btn');
            if (editBtn) {
                editBtn.setAttribute('data-bs-toggle', 'modal');
                editBtn.setAttribute('data-bs-target', '#kegiatanModal');
                editBtn.setAttribute('data-action', 'edit');
                new bootstrap.Modal(kegiatanModalEl).show(editBtn);
            }
        });
    }

    loadKegiatan();
}

function initLaporanPage() {
    const isAdminOrBendahara = (typeof userRole !== 'undefined' && ['admin', 'bendahara'].includes(userRole));

    if (isAdminOrBendahara) {
        initLaporanPageAdmin();
    } else {
        initLaporanPageWarga();
    }

    // Common logic for both roles
    const laporanModalEl = document.getElementById('laporanModal');
    if (!laporanModalEl) return;
    
    const laporanModal = new bootstrap.Modal(laporanModalEl);
    const laporanForm = document.getElementById('laporan-form');
    const saveLaporanBtn = document.getElementById('save-laporan-btn');

    saveLaporanBtn.addEventListener('click', async () => {
        const formData = new FormData(laporanForm);
        
        // Basic validation
        if (!formData.get('kategori') || !formData.get('deskripsi')) {
            showToast('Kategori dan Deskripsi wajib diisi.', 'error');
            return;
        }

        const originalBtnHtml = saveLaporanBtn.innerHTML;
        saveLaporanBtn.disabled = true;
        saveLaporanBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...';

        try {
            const minDelay = new Promise(resolve => setTimeout(resolve, 500));
            const fetchPromise = fetch(`${basePath}/api/laporan`, { method: 'POST', body: formData });

            const [response] = await Promise.all([fetchPromise, minDelay]);

            const result = await response.json();
            if (result.status === 'success') {
                laporanModal.hide();
                showToast(result.message, 'success');
                // Reload the appropriate list
                if (isAdminOrBendahara) {
                    document.getElementById('filter-status-laporan').dispatchEvent(new Event('change'));
                } else {
                    initLaporanPageWarga(); // Reload the user's own report list
                }
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveLaporanBtn.disabled = false;
            saveLaporanBtn.innerHTML = originalBtnHtml;
        }
    });

    laporanModalEl.addEventListener('hidden.bs.modal', () => {
        laporanForm.reset();
    });
}

function initLaporanPageAdmin() {
    const laporanTableBody = document.getElementById('laporan-table-body');
    const statusFilter = document.getElementById('filter-status-laporan');

    if (!laporanTableBody) return;

    async function loadAdminLaporan() {
        const status = statusFilter ? statusFilter.value : '';
        laporanTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Memuat data...</td></tr>';
        try {
            const response = await fetch(`${basePath}/api/laporan?action=list&status=${status}`);
            const result = await response.json();
            laporanTableBody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(l => {
                    const statusColors = { baru: 'primary', diproses: 'warning', selesai: 'success' };
                    const fotoHtml = l.foto ? `<a href="${basePath}/${l.foto.replace(/\\/g, '/')}" target="_blank" class="btn btn-sm btn-outline-info">Lihat</a>` : 'Tidak ada';
                    const row = `
                        <tr>
                            <td>${new Date(l.created_at).toLocaleDateString('id-ID')}</td>
                            <td>${l.pelapor}</td>
                            <td>${l.kategori}</td>
                            <td>${l.deskripsi}</td>
                            <td>${fotoHtml}</td>
                            <td><span class="badge bg-${statusColors[l.status]}">${l.status}</span></td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Ubah Status
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item change-status-btn" href="#" data-id="${l.id}" data-status="baru">Baru</a></li>
                                        <li><a class="dropdown-item change-status-btn" href="#" data-id="${l.id}" data-status="diproses">Diproses</a></li>
                                        <li><a class="dropdown-item change-status-btn" href="#" data-id="${l.id}" data-status="selesai">Selesai</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>`;
                    laporanTableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                laporanTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Tidak ada laporan ditemukan.</td></tr>';
            }
        } catch (error) {
            laporanTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Gagal memuat data.</td></tr>`;
        }
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', loadAdminLaporan);
    }

    laporanTableBody.addEventListener('click', async (e) => {
        const changeStatusBtn = e.target.closest('.change-status-btn');
        if (changeStatusBtn) {
            e.preventDefault();
            const { id, status } = changeStatusBtn.dataset;
            const dropdownToggle = changeStatusBtn.closest('.dropdown').querySelector('.dropdown-toggle');
            const originalToggleHtml = dropdownToggle.innerHTML;
            dropdownToggle.disabled = true;
            dropdownToggle.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

            try {
                const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('id', id);
                formData.append('status', status);
                const fetchPromise = fetch(`${basePath}/api/laporan`, { method: 'POST', body: formData });

                const [response] = await Promise.all([fetchPromise, minDelay]);

                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    loadAdminLaporan();
                } else {
                    dropdownToggle.disabled = false;
                    dropdownToggle.innerHTML = originalToggleHtml;
                }
            } catch (error) {
                showToast('Terjadi kesalahan jaringan.', 'error');
                dropdownToggle.disabled = false;
                dropdownToggle.innerHTML = originalToggleHtml;
            }
        }
    });

    loadAdminLaporan();
}

function initLaporanPageWarga() {
    const laporanListContainer = document.getElementById('laporan-list-warga');
    if (!laporanListContainer) return;

    async function loadMyLaporan() {
        laporanListContainer.innerHTML = '<div class="col-12 text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Memuat...</span></div></div>';
        try {
            const response = await fetch(`${basePath}/api/laporan?action=list_own`);
            const result = await response.json();

            laporanListContainer.innerHTML = ''; // Clear loading spinner

            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(l => {
                    const statusColors = { baru: 'primary', diproses: 'warning', selesai: 'success' };
                    const tgl = new Date(l.created_at);
                    const tglFormatted = tgl.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                    const fotoHtml = l.foto ? `<a href="${basePath}/${l.foto.replace(/\\/g, '/')}" target="_blank" class="card-link">Lihat Foto</a>` : '';

                    const card = `
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="card-title mb-1">${l.kategori}</h5>
                                        <span class="badge bg-${statusColors[l.status]} text-capitalize">${l.status}</span>
                                    </div>
                                    <h6 class="card-subtitle mb-2 text-muted">${tglFormatted}</h6>
                                    <p class="card-text">${l.deskripsi}</p>
                                    ${fotoHtml}
                                </div>
                            </div>
                        </div>
                    `;
                    laporanListContainer.insertAdjacentHTML('beforeend', card);
                });
            } else {
                laporanListContainer.innerHTML = '<div class="col-12"><div class="alert alert-info">Anda belum pernah membuat laporan.</div></div>';
            }
        } catch (error) {
            console.error("Error loading my reports:", error);
            laporanListContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger">Gagal memuat riwayat laporan Anda.</div></div>';
        }
    }

    loadMyLaporan();
}

function initSettingsPage() {
    const generalSettingsContainer = document.getElementById('settings-container');
    const saveGeneralSettingsBtn = document.getElementById('save-settings-btn');
    const generalSettingsForm = document.getElementById('settings-form');
    const suratTemplateTab = document.getElementById('surat-template-tab');
    let settingsData = {}; // Store settings data globally within the function scope

    // --- General Settings ---
    if (!generalSettingsContainer) return;

    async function loadSettings() {
        try {
            const response = await fetch(`${basePath}/api/settings`);
            const result = await response.json();

            if (result.status === 'success') {
                settingsData = result.data; // Store the data
                const settings = result.data;
                generalSettingsContainer.innerHTML = `
                    <div class="mb-3">
                        <label for="app_name" class="form-label">Nama Aplikasi</label>
                        <input type="text" class="form-control" id="app_name" name="app_name" value="${settings.app_name || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="housing_name" class="form-label">Nama Perumahan</label>
                        <input type="text" class="form-control" id="housing_name" name="housing_name" value="${settings.housing_name || ''}">
                    </div>
                    <div class="mb-3">
                        <label for="rt_head_name" class="form-label">Nama Ketua RT</label>
                        <input type="text" class="form-control" id="rt_head_name" name="rt_head_name" value="${settings.rt_head_name || ''}">
                        <small class="form-text text-muted">Nama ini akan ditampilkan di bagian tanda tangan surat.</small>
                    </div>
                    <div class="mb-3">
                        <label for="notification_interval" class="form-label">Interval Refresh Notifikasi (ms)</label>
                        <input type="number" class="form-control" id="notification_interval" name="notification_interval" value="${settings.notification_interval || ''}">
                    </div>
                    <hr>
                    <h5>Pengaturan Keuangan</h5>
                    <div class="mb-3">
                        <label class="form-label">Iuran Bulanan Saat Ini</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" class="form-control" value="${new Intl.NumberFormat('id-ID').format(settings.monthly_fee || 0)}" readonly>
                            <button class="btn btn-outline-primary" type="button" id="change-fee-btn" data-bs-toggle="modal" data-bs-target="#iuranModal">
                                <i class="bi bi-pencil-fill"></i> Ubah
                            </button>
                            <a href="${basePath}/settings/iuran-history" class="btn btn-outline-secondary" title="Lihat Histori Perubahan">
                                <i class="bi bi-clock-history"></i>
                            </a>
                        </div>
                    </div>
                    <!-- Modal Iuran -->
                    <div class="modal fade" id="iuranModal" tabindex="-1" aria-labelledby="iuranModalLabel" aria-hidden="true">
                      <div class="modal-dialog">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="iuranModalLabel">Ubah Nominal Iuran Bulanan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <p>Nominal iuran saat ini: <strong>Rp ${new Intl.NumberFormat('id-ID').format(settings.monthly_fee || 0)}</strong></p>
                            <div class="mb-3"><label for="new_monthly_fee" class="form-label">Nominal Iuran Baru (Rp)</label><input type="number" class="form-control" id="new_monthly_fee" name="monthly_fee" value="${settings.monthly_fee || ''}"></div>
                            <div class="mb-3"><label for="fee_start_date" class="form-label">Mulai Berlaku Tanggal</label><input type="date" class="form-control" id="fee_start_date" name="fee_start_date" value="${new Date().toISOString().slice(0,10)}"></div>
                            <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle-fill"></i> Perubahan ini akan dicatat dalam histori dan akan mempengaruhi perhitungan tunggakan di masa mendatang. Pastikan tanggal mulai berlaku sudah benar.</div>
                          </div>
                          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" id="save-iuran-change-btn" data-bs-dismiss="modal">Simpan Perubahan</button></div>
                        </div>
                      </div>
                    </div>
                    <div class="mb-3">
                        <label for="log_cleanup_interval_days" class="form-label">Interval Bersihkan Log (hari)</label>
                        <input type="number" class="form-control" id="log_cleanup_interval_days" name="log_cleanup_interval_days" value="${settings.log_cleanup_interval_days || '180'}">
                        <small class="form-text text-muted">Log aktivitas dan panik yang lebih lama dari interval ini akan dihapus saat pembersihan dijalankan.</small>
                    </div>
                    <div class="mb-3">
                        <label for="whatsapp_notification_number" class="form-label">Nomor WhatsApp Notifikasi</label>
                        <input type="text" class="form-control" id="whatsapp_notification_number" name="whatsapp_notification_number" value="${settings.whatsapp_notification_number || ''}">
                        <small class="form-text text-muted">Masukkan nomor WA (diawali 08...) yang akan menerima notifikasi untuk diteruskan ke grup warga. Kosongkan untuk menonaktifkan.</small>
                    </div>
                    <hr>
                    <h5>Gambar Kop Surat</h5>
                    <div class="mb-3">
                        <label for="letterhead_image" class="form-label">Unggah Gambar Kop Surat</label>
                        <input type="file" class="form-control" id="letterhead_image" name="letterhead_image" accept="image/png,image/jpeg">
                        <small class="form-text text-muted">Gunakan file PNG/JPG. Gambar ini akan ditampilkan di bagian atas semua surat yang dicetak. Rekomendasi lebar: 750px.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kop Surat Saat Ini:</label>
                        <div id="letterhead-preview" style="background: #f8f9fa; border: 1px solid #ccc; padding: 10px; text-align: center;">
                            ${settings.letterhead_image && settings.letterhead_image_exists 
                                ? `<img src="${basePath}/${settings.letterhead_image}?t=${new Date().getTime()}" alt="Kop Surat" style="max-width: 100%;">` 
                                : '<span class="text-muted">Belum ada kop surat.</span>'
                            }
                        </div>
                    </div>

                    <hr>
                    <h5>Tanda Tangan Ketua RT</h5>
                    <div class="mb-3">
                        <label for="signature_image" class="form-label">Unggah Gambar Tanda Tangan</label>
                        <input type="file" class="form-control" id="signature_image" name="signature_image" accept="image/png">
                        <small class="form-text text-muted">Gunakan file PNG dengan latar belakang transparan. Gambar akan ditampilkan di surat pengantar.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanda Tangan Saat Ini:</label>
                        <div id="signature-preview">
                            ${settings.signature_image && settings.signature_image_exists 
                                ? `<img src="${basePath}/${settings.signature_image}?t=${new Date().getTime()}" alt="Tanda Tangan" style="max-height: 80px; border: 1px solid #ccc; padding: 5px; background: #f8f9fa;">` 
                                : '<span class="text-muted">Belum ada tanda tangan.</span>'
                            }
                        </div>
                    </div>
                    <hr>
                    <h5>Stempel RT</h5>
                    <div class="mb-3">
                        <label for="stamp_image" class="form-label">Unggah Gambar Stempel</label>
                        <input type="file" class="form-control" id="stamp_image" name="stamp_image" accept="image/png">
                        <small class="form-text text-muted">Gunakan file PNG dengan latar belakang transparan.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stempel Saat Ini:</label>
                        <div id="stamp-preview">
                            ${settings.stamp_image && settings.stamp_image_exists 
                                ? `<img src="${basePath}/${settings.stamp_image}?t=${new Date().getTime()}" alt="Stempel" style="max-height: 80px; border: 1px solid #ccc; padding: 5px; background: #f8f9fa;">` 
                                : '<span class="text-muted">Belum ada stempel.</span>'
                            }
                        </div>
                    </div>
                `;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            generalSettingsContainer.innerHTML = `<div class="alert alert-danger">Gagal memuat pengaturan: ${error.message}</div>`;
        }
    }

    // Listener untuk tombol simpan di modal iuran.
    // Kita tidak menyimpan langsung, tapi hanya memicu tombol simpan utama.
    // Ini memastikan semua data di form (termasuk yang di modal) terkirim bersamaan.
    generalSettingsContainer.addEventListener('click', function(e) {
        if (e.target.id === 'save-iuran-change-btn') {
            saveGeneralSettingsBtn.click();
        }
    });

    saveGeneralSettingsBtn.addEventListener('click', async () => {
        const formData = new FormData(generalSettingsForm);
        const originalBtnHtml = saveGeneralSettingsBtn.innerHTML;
        saveGeneralSettingsBtn.disabled = true;
        saveGeneralSettingsBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;

        try {
            const minDelay = new Promise(resolve => setTimeout(resolve, 500));
            const fetchPromise = fetch(`${basePath}/api/settings`, { method: 'POST', body: formData });

            const [response] = await Promise.all([fetchPromise, minDelay]);

            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                loadSettings(); // Reload settings to show new signature
                showToast('Beberapa perubahan mungkin memerlukan refresh halaman untuk diterapkan.', 'info', 'Informasi');
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveGeneralSettingsBtn.disabled = false;
            saveGeneralSettingsBtn.innerHTML = originalBtnHtml;
        }
    });

    loadSettings();

    // --- Surat Template Settings ---
    if (suratTemplateTab) {
        suratTemplateTab.addEventListener('shown.bs.tab', initSuratTemplateSettings);
    }
}

function initSuratTemplateSettings() {
    const tableBody = document.getElementById('surat-templates-table-body');
    const modalEl = document.getElementById('suratTemplateModal');
    const modal = new bootstrap.Modal(modalEl);
    const modalLabel = document.getElementById('suratTemplateModalLabel');
    const form = document.getElementById('surat-template-form');
    const saveBtn = document.getElementById('save-template-btn');
    const addBtn = document.getElementById('add-template-btn');
    const placeholdersInfo = document.getElementById('template-placeholders-info');

    if (!tableBody) return;

    async function loadTemplates() {
        tableBody.innerHTML = '<tr><td colspan="3" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        const response = await fetch(`${basePath}/api/surat-templates`);
        const result = await response.json();
        tableBody.innerHTML = '';
        if (result.status === 'success' && result.data.length > 0) {
            result.data.forEach(t => {
                const row = `
                    <tr>
                        <td>${t.nama_template}</td>
                        <td>${t.judul_surat}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-info edit-template-btn" data-id="${t.id}"><i class="bi bi-pencil-fill"></i> Edit</button>
                            <button class="btn btn-sm btn-danger delete-template-btn" data-id="${t.id}" data-nama="${t.nama_template}"><i class="bi bi-trash-fill"></i> Hapus</button>
                        </td>
                    </tr>
                `;
                tableBody.insertAdjacentHTML('beforeend', row);
            });
        } else {
            tableBody.innerHTML = '<tr><td colspan="3" class="text-center">Belum ada template surat.</td></tr>';
        }
    }

    function renderForm(data = {}) {
        form.innerHTML = `
            <input type="hidden" name="id" value="${data.id || ''}">
            <input type="hidden" name="action" id="template-action" value="${data.id ? 'update' : 'create'}">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nama_template" class="form-label">Nama Template</label>
                    <input type="text" class="form-control" id="nama_template" name="nama_template" value="${data.nama_template || ''}" required>
                    <small class="form-text text-muted">Nama ini akan muncul di dropdown pilihan surat untuk warga.</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="judul_surat" class="form-label">Judul di Dokumen PDF</label>
                    <input type="text" class="form-control" id="judul_surat" name="judul_surat" value="${data.judul_surat || ''}" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="konten" class="form-label">Isi Konten Surat</label>
                <textarea class="form-control" id="konten" name="konten" rows="15" required>${data.konten || ''}</textarea>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="requires_parent_data" name="requires_parent_data" ${data.requires_parent_data == 1 ? 'checked' : ''}>
                <label class="form-check-label" for="requires_parent_data">
                    Template ini memerlukan data orang tua (untuk Pengantar Nikah, dll.)
                </label>
            </div>
        `;
        placeholdersInfo.innerHTML = `
            <strong>Placeholder yang tersedia:</strong><br>
            <code>{{surat.nama_lengkap}}</code>, <code>{{surat.nik}}</code>, <code>{{surat.alamat}}</code>, <code>{{surat.pekerjaan}}</code>, 
            <code>{{surat.tgl_lahir_formatted}}</code>, <code>{{surat.keperluan}}</code>, <code>{{surat.nomor_surat}}</code>, 
            <code>{{app.housing_name}}</code>, <code>{{app.rt_head_name}}</code>, <code>{{app.current_date}}</code>.<br>
            <strong>Jika memerlukan data orang tua:</strong> <code>{{data_ayah.nama_lengkap}}</code>, <code>{{data_ibu.nama_lengkap}}</code>, dll.
        `;
    }

    addBtn.addEventListener('click', () => {
        modalLabel.textContent = 'Tambah Template Surat';
        renderForm();
        modal.show();
    });

    tableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-template-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'get_single');
            formData.append('id', id);
            const response = await fetch(`${basePath}/api/surat-templates`, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                modalLabel.textContent = 'Edit Template Surat';
                renderForm(result.data);
                modal.show();
            }
        }

        const deleteBtn = e.target.closest('.delete-template-btn');
        if (deleteBtn) {
            const { id, nama } = deleteBtn.dataset;
            if (confirm(`Yakin ingin menghapus template "${nama}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                const response = await fetch(`${basePath}/api/surat-templates`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') loadTemplates();
            }
        }
    });

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const response = await fetch(`${basePath}/api/surat-templates`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') {
            modal.hide();
            loadTemplates();
        }
    });

    loadTemplates();
}

function initIuranHistoriPerubahanPage() {
    const tableBody = document.getElementById('fee-history-table-body');
    if (!tableBody) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    });

    async function loadHistory() {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        try {
            const response = await fetch(`${basePath}/api/settings?action=get_fee_history`);
            const result = await response.json();

            if (result.status === 'success') {
                tableBody.innerHTML = '';
                if (result.data.length > 0) {
                    result.data.forEach(item => {
                        const startDate = new Date(item.start_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                        const endDate = item.end_date 
                            ? new Date(item.end_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
                            : '<span class="badge bg-success">Saat Ini</span>';
                        const createdAt = new Date(item.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });

                        const row = `
                            <tr>
                                <td class="fw-bold">${currencyFormatter.format(item.monthly_fee)}</td>
                                <td>${startDate}</td>
                                <td>${endDate}</td>
                                <td>${item.updated_by_name || 'N/A'}</td>
                                <td>${createdAt}</td>
                            </tr>
                        `;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Belum ada histori perubahan nominal iuran.</td></tr>';
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Gagal memuat histori: ${error.message}</td></tr>`;
        }
    }

    loadHistory();
}

function initMyProfilePage() {
    const form = document.getElementById('change-password-form');
    const saveBtn = document.getElementById('save-password-btn');

    if (!form || !saveBtn) return;

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const originalBtnHtml = saveBtn.innerHTML;

        // Client-side validation
        if (formData.get('new_password') !== formData.get('confirm_password')) {
            showToast('Password baru dan konfirmasi tidak cocok.', 'error');
            return;
        }
        if (formData.get('new_password').length < 6) {
            showToast('Password baru minimal harus 6 karakter.', 'error');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

        try {
            const minDelay = new Promise(resolve => setTimeout(resolve, 500));
            const fetchPromise = fetch(`${basePath}/api/my-profile/change-password`, {
                method: 'POST',
                body: formData
            });

            const [response] = await Promise.all([fetchPromise, minDelay]);

            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                form.reset();
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        }
    });
}

function initMyProfileEditPage() {
    const container = document.getElementById('profile-fields-container');
    const saveBtn = document.getElementById('save-profile-btn');
    const form = document.getElementById('edit-profile-form');

    if (!container) return;

    async function loadProfile() {
        try {
            const response = await fetch(`${basePath}/api/my-profile`);
            const result = await response.json();

            if (result.status === 'success') {
                const profile = result.data;
                container.innerHTML = `
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" value="${profile.nama_lengkap || ''}" readonly disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">NIK</label>
                            <input type="text" class="form-control" value="${profile.nik || ''}" readonly disabled>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. Kartu Keluarga</label>
                        <input type="text" class="form-control" value="${profile.no_kk || ''}" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <input type="text" class="form-control" value="${profile.alamat || ''}" readonly disabled>
                    </div>
                    <hr>
                    <p class="text-muted">Anda dapat mengubah data di bawah ini:</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="no_telepon" class="form-label">No. Telepon</label>
                            <input type="text" class="form-control" id="no_telepon" name="no_telepon" value="${profile.no_telepon || ''}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="pekerjaan" class="form-label">Pekerjaan</label>
                            <input type="text" class="form-control" id="pekerjaan" name="pekerjaan" value="${profile.pekerjaan || ''}">
                        </div>
                    </div>
                `;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            container.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
            saveBtn.style.display = 'none';
        }
    }

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;

        try {
            const minDelay = new Promise(resolve => setTimeout(resolve, 500));
            const fetchPromise = fetch(`${basePath}/api/my-profile`, { method: 'POST', body: formData });
            const [response] = await Promise.all([fetchPromise, minDelay]);

            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        }
    });

    loadProfile();
}

function initIuranSayaPage() {
    const tableBody = document.getElementById('riwayat-iuran-table-body');
    if (!tableBody) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    });

    async function loadRiwayatIuran() {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        try {
            const response = await fetch(`${basePath}/api/iuran-saya`);
            const result = await response.json();

            if (result.status === 'success') {
                tableBody.innerHTML = '';
                if (result.data.length > 0) {
                    const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
                    result.data.forEach(item => {
                        const periode = `${months[item.periode_bulan - 1]} ${item.periode_tahun}`;
                        const tglBayar = new Date(item.tanggal_bayar).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                        const row = `
                            <tr>
                                <td>${periode}</td>
                                <td>${currencyFormatter.format(item.jumlah)}</td>
                                <td>${tglBayar}</td>
                                <td>${item.pencatat || 'N/A'}</td>
                                <td>${item.catatan || '-'}</td>
                            </tr>
                        `;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Anda belum memiliki riwayat pembayaran iuran.</td></tr>';
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Gagal memuat riwayat iuran: ${error.message}</td></tr>`;
        }
    }

    loadRiwayatIuran();
}

function initKeluargaSayaPage() {
    const tableBody = document.getElementById('keluarga-saya-table-body');
    const infoNoKk = document.getElementById('info-no-kk');
    const anggotaModalEl = document.getElementById('anggotaModal');
    const anggotaModal = new bootstrap.Modal(anggotaModalEl);
    const anggotaForm = document.getElementById('anggota-form');
    const saveAnggotaBtn = document.getElementById('save-anggota-btn');
    const noKkSpan = document.getElementById('anggota-no-kk');
    let userNoKk = ''; // To store the user's KK number

    if (!tableBody) return;

    async function loadMyFamily() {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        infoNoKk.textContent = 'Memuat data...';
        try {
            const response = await fetch(`${basePath}/api/warga?action=get_my_family`);
            const result = await response.json();

            if (result.status === 'success') {
                userNoKk = result.no_kk; // Store the KK number
                infoNoKk.textContent = `Anggota Keluarga untuk No. KK: ${result.no_kk || '(Tidak Ditemukan)'}`;
                tableBody.innerHTML = '';
                if (result.data.length > 0) {
                    result.data.forEach(member => {
                        const tglLahirFormatted = member.tgl_lahir
                            ? new Date(member.tgl_lahir).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
                            : '-';

                        const isCurrentUser = (typeof username !== 'undefined' && username === member.nama_panggilan);
                        const editButton = isCurrentUser
                            ? `<a href="${basePath}/my-profile/edit" class="btn btn-sm btn-info"><i class="bi bi-pencil-fill"></i> Edit Profil</a>`
                            : '';

                        const row = `
                            <tr>
                                <td>${member.nama_lengkap}</td>
                                <td>${member.status_dalam_keluarga || 'Lainnya'}</td>
                                <td>${tglLahirFormatted}</td>
                                <td>${member.pekerjaan || '-'}</td>
                                <td class="text-end">${editButton}</td>
                            </tr>
                        `;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada anggota keluarga yang ditemukan. Pastikan data Anda sudah lengkap.</td></tr>';
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Gagal memuat data keluarga: ${error.message}</td></tr>`;
            infoNoKk.textContent = 'Gagal Memuat Data';
        }
    }

    // Add event listener for the new modal
    if (anggotaModalEl) {
        anggotaModalEl.addEventListener('show.bs.modal', () => {
            anggotaForm.reset();
            if (noKkSpan) {
                noKkSpan.textContent = userNoKk;
            }
        });
    }

    // Add event listener for the save button
    if (saveAnggotaBtn) {
        saveAnggotaBtn.addEventListener('click', async () => {
            const formData = new FormData(anggotaForm);
            // The no_kk is handled by the backend based on session, so we don't need to add it here.

            const originalBtnHtml = saveAnggotaBtn.innerHTML;
            saveAnggotaBtn.disabled = true;
            saveAnggotaBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;

            try {
                const response = await fetch(`${basePath}/api/warga`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    anggotaModal.hide();
                    loadMyFamily(); // Reload the family list
                }
            } catch (error) {
                showToast('Terjadi kesalahan jaringan.', 'error');
            } finally {
                saveAnggotaBtn.disabled = false;
                saveAnggotaBtn.innerHTML = originalBtnHtml;
            }
        });
    }

    loadMyFamily();
}

function initTabunganSayaPage() {
    const container = document.getElementById('tabungan-saya-container');
    if (!container) return;

    const saldoEl = document.getElementById('saldo-saya-total');
    const tableBody = document.getElementById('tabungan-saya-table-body');
    const goalsContainer = document.getElementById('savings-goals-container');
    const goalModal = new bootstrap.Modal(document.getElementById('goalModal'));
    const printBtn = document.getElementById('cetak-tabungan-saya-btn');
    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    async function loadMySavings() {
        saldoEl.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
        if (printBtn) printBtn.classList.add('disabled');
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>';

        try {
            // We need to get the warga_id of the current user first.
            const profileRes = await fetch(`${basePath}/api/my-profile`);
            const profileResult = await profileRes.json();
            if (profileResult.status !== 'success' || !profileResult.data.warga_id) {
                 throw new Error("Profil warga tidak ditemukan. Tidak dapat memuat tabungan.");
            }
            const wargaId = profileResult.data.warga_id;

            const response = await fetch(`${basePath}/api/tabungan?action=detail&warga_id=${wargaId}`);
            const result = await response.json();

            if (result.status === 'success') {
                if (printBtn) {
                    printBtn.href = `${basePath}/tabungan/cetak/${wargaId}`;
                    printBtn.classList.remove('disabled');
                }

                saldoEl.textContent = currencyFormatter.format(result.data.saldo);
                renderGoals(result.data.goals, result.data.saldo);
                tableBody.innerHTML = '';
                if (result.data.transactions.length > 0) {
                    result.data.transactions.forEach(tx => {
                        const row = `
                            <tr>
                                <td>${new Date(tx.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })}</td>
                                <td><span class="badge bg-${tx.jenis === 'setor' ? 'success' : 'danger'}">${tx.jenis}</span></td>
                                <td>${tx.nama_kategori}</td>
                                <td>${tx.keterangan || '-'}</td>
                                <td class="text-end">${currencyFormatter.format(tx.jumlah)}</td>
                            </tr>
                        `;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Belum ada riwayat transaksi.</td></tr>';
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            saldoEl.textContent = 'Error';
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Gagal memuat data: ${error.message}</td></tr>`;
        }
    }

    function renderGoals(goals, currentBalance) {
        goalsContainer.innerHTML = '';
        if (goals.length > 0) {
            goals.forEach(goal => {
                const progress = Math.min((currentBalance / goal.target_jumlah) * 100, 100);
                const isAchieved = progress >= 100;
                const targetDate = goal.tanggal_target ? `Target: ${new Date(goal.tanggal_target).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'})}` : 'Tanpa batas waktu';

                const goalCard = `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title">${goal.nama_goal}</h5>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item edit-goal-btn" href="#" data-goal='${JSON.stringify(goal)}'>Edit</a></li>
                                            <li><a class="dropdown-item text-danger delete-goal-btn" href="#" data-id="${goal.id}" data-nama="${goal.nama_goal}">Hapus</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <p class="card-text text-muted small">${targetDate}</p>
                                <p class="fw-bold">${currencyFormatter.format(goal.target_jumlah)}</p>
                                <div class="progress" role="progressbar" style="height: 20px;">
                                    <div class="progress-bar ${isAchieved ? 'bg-success' : 'bg-primary'}" style="width: ${progress}%">${progress.toFixed(0)}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                goalsContainer.insertAdjacentHTML('beforeend', goalCard);
            });
        } else {
            goalsContainer.innerHTML = '<div class="col-12"><div class="alert alert-info text-center">Anda belum memiliki target tabungan.</div></div>';
        }
    }

    document.getElementById('goalModal').addEventListener('show.bs.modal', (e) => {
        const button = e.relatedTarget;
        const form = document.getElementById('goal-form');
        form.reset();
        if (button && button.dataset.action === 'add') {
            document.getElementById('goalModalLabel').textContent = 'Tambah Target Tabungan';
            document.getElementById('goal-action').value = 'add_goal';
        }
    });

    document.getElementById('save-goal-btn').addEventListener('click', async () => {
        const form = document.getElementById('goal-form');
        const formData = new FormData(form);
        const response = await fetch(`${basePath}/api/tabungan`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') {
            goalModal.hide();
            loadMySavings();
        }
    });

    goalsContainer.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-goal-btn');
        if (editBtn) {
            e.preventDefault();
            const goal = JSON.parse(editBtn.dataset.goal);
            document.getElementById('goalModalLabel').textContent = 'Edit Target Tabungan';
            document.getElementById('goal-action').value = 'update_goal';
            document.getElementById('goal-id').value = goal.id;
            document.getElementById('nama_goal').value = goal.nama_goal;
            document.getElementById('target_jumlah').value = goal.target_jumlah;
            document.getElementById('tanggal_target').value = goal.tanggal_target;
            goalModal.show();
        }
    });

    loadMySavings();
}

function initTabunganPage() {
    const tableBody = document.getElementById('tabungan-summary-table-body');
    const searchInput = document.getElementById('search-tabungan');
    const totalSaldoEl = document.getElementById('total-semua-saldo');
    if (!tableBody) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    async function loadSummary(searchTerm = '') {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        totalSaldoEl.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
        try {
            const response = await fetch(`${basePath}/api/tabungan?action=summary&search=${encodeURIComponent(searchTerm)}`);
            const result = await response.json();

            if (result.status === 'success') {
                tableBody.innerHTML = '';
                let totalSaldo = 0;
                if (result.data.length > 0) {
                    result.data.forEach(item => {
                        const saldo = parseFloat(item.saldo) || 0;
                        totalSaldo += saldo;
                        const row = `
                            <tr>
                                <td>${item.nama_lengkap}</td>
                                <td>${item.no_kk}</td>
                                <td>Blok ${item.blok} / ${item.nomor}</td>
                                <td class="text-end fw-bold">${currencyFormatter.format(saldo)}</td>
                                <td class="text-end">
                                    <a href="${basePath}/tabungan/detail/${item.warga_id}" class="btn btn-sm btn-info">
                                        <i class="bi bi-search"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        `;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada data tabungan ditemukan.</td></tr>';
                }
                totalSaldoEl.textContent = currencyFormatter.format(totalSaldo);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Gagal memuat data.</td></tr>`;
            totalSaldoEl.textContent = 'Error';
        }
    }

    let debounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadSummary(searchInput.value), 300);
    });

    loadSummary();
}

function initTabunganDetailPage() {
    const container = document.getElementById('tabungan-detail-container');
    if (!container) return;

    const wargaId = container.dataset.wargaId;
    const namaEl = document.getElementById('detail-warga-nama');
    const saldoEl = document.getElementById('detail-saldo-total');
    const tableBody = document.getElementById('tabungan-detail-table-body');
    const modalEl = document.getElementById('tabunganTxModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('tabungan-tx-form');
    const saveBtn = document.getElementById('save-tabungan-tx-btn');
    const jenisSelect = document.getElementById('tx-jenis');
    const kategoriSelect = document.getElementById('tx-kategori');

    const currencyFormatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });

    async function loadDetail() {
        namaEl.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
        saldoEl.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center p-5"><div class="spinner-border"></div></td></tr>';

        try {
            const response = await fetch(`${basePath}/api/tabungan?action=detail&warga_id=${wargaId}`);
            const result = await response.json();
            if (result.status === 'success') {
                const { warga, transactions, saldo } = result.data;
                namaEl.textContent = `${warga.nama_lengkap} (KK: ${warga.no_kk})`;
                saldoEl.textContent = currencyFormatter.format(saldo);
                tableBody.innerHTML = '';
                if (transactions.length > 0) {
                    transactions.forEach(tx => {
                        const row = `
                            <tr>
                                <td>${new Date(tx.tanggal).toLocaleDateString('id-ID')}</td>
                                <td><span class="badge bg-${tx.jenis === 'setor' ? 'success' : 'danger'}">${tx.jenis}</span></td>
                                <td>${tx.nama_kategori}</td>
                                <td>${tx.keterangan || '-'}</td>
                                <td class="text-end">${currencyFormatter.format(tx.jumlah)}</td>
                                <td>${tx.pencatat}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="${tx.id}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                                </td>
                            </tr>
                        `;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Belum ada transaksi.</td></tr>';
                }
            } else { throw new Error(result.message); }
        } catch (error) {
            namaEl.textContent = 'Error';
            saldoEl.textContent = 'Error';
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Gagal memuat: ${error.message}</td></tr>`;
        }
    }

    async function loadKategoriOptions(jenis) {
        kategoriSelect.innerHTML = '<option>Memuat...</option>';
        const response = await fetch(`${basePath}/api/tabungan-kategori`);
        const result = await response.json();
        kategoriSelect.innerHTML = '';
        if (result.status === 'success' && result.data[jenis]) {
            result.data[jenis].forEach(cat => kategoriSelect.add(new Option(cat.nama_kategori, cat.id)));
        }
    }

    modalEl.addEventListener('show.bs.modal', () => {
        form.reset();
        document.getElementById('tx-tanggal').valueAsDate = new Date();
        loadKategoriOptions(jenisSelect.value);
    });

    jenisSelect.addEventListener('change', () => loadKategoriOptions(jenisSelect.value));

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const response = await fetch(`${basePath}/api/tabungan`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') {
            modal.hide();
            loadDetail();
        }
    });

    tableBody.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            if (confirm('Yakin ingin menghapus transaksi ini? Saldo akan dihitung ulang.')) {
                const formData = new FormData();
                formData.append('action', 'delete_transaction');
                formData.append('id', deleteBtn.dataset.id);
                const response = await fetch(`${basePath}/api/tabungan`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') loadDetail();
            }
        }
    });

    loadDetail();
}

function initManajemenKategoriPage() {
    const tableBodyMasuk = document.getElementById('kategori-masuk-table-body');
    const tableBodyKeluar = document.getElementById('kategori-keluar-table-body');
    const modalEl = document.getElementById('kategoriKasModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('kategori-kas-form');
    const saveBtn = document.getElementById('save-kategori-btn');

    if (!tableBodyMasuk || !tableBodyKeluar) return;

    async function loadKategori() {
        tableBodyMasuk.innerHTML = '<tr><td colspan="2" class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></td></tr>';
        tableBodyKeluar.innerHTML = '<tr><td colspan="2" class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></td></tr>';

        try {
            const response = await fetch(`${basePath}/api/kategori-kas`);
            const result = await response.json();

            if (result.status === 'success') {
                renderTable(tableBodyMasuk, result.data.masuk);
                renderTable(tableBodyKeluar, result.data.keluar);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            tableBodyMasuk.innerHTML = '<tr><td colspan="2" class="text-center text-danger">Gagal memuat.</td></tr>';
            tableBodyKeluar.innerHTML = '<tr><td colspan="2" class="text-center text-danger">Gagal memuat.</td></tr>';
        }
    }

    function renderTable(tbody, data) {
        tbody.innerHTML = '';
        if (data && data.length > 0) {
            data.forEach(item => {
                const row = `
                    <tr>
                        <td>${item.nama_kategori}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-info edit-btn" data-id="${item.id}" title="Edit"><i class="bi bi-pencil-fill"></i></button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${item.id}" data-nama="${item.nama_kategori}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', row);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Belum ada kategori.</td></tr>';
        }
    }

    modalEl.addEventListener('show.bs.modal', (e) => {
        const button = e.relatedTarget;
        form.reset();
        document.getElementById('kategori-action').value = 'add';
        document.getElementById('kategori-id').value = '';
        document.getElementById('kategoriKasModalLabel').textContent = 'Tambah Kategori Baru';
        
        // Set jenis based on which "Tambah" button was clicked
        if (button && button.dataset.jenis) {
            document.getElementById('kategori-jenis').value = button.dataset.jenis;
        }
    });

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const response = await fetch(`${basePath}/api/kategori-kas`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') {
            modal.hide();
            loadKategori();
        }
    });

    document.getElementById('kategori-tables-container').addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            const formData = new FormData();
            formData.append('action', 'get_single');
            formData.append('id', id); // This line is correct, but the URL below was wrong
            const response = await fetch(`${basePath}/api/kategori-kas`, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                document.getElementById('kategoriKasModalLabel').textContent = 'Edit Kategori';
                document.getElementById('kategori-action').value = 'update';
                document.getElementById('kategori-id').value = result.data.id;
                document.getElementById('nama_kategori').value = result.data.nama_kategori;
                document.getElementById('kategori-jenis').value = result.data.jenis;
                modal.show();
            }
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const { id, nama } = deleteBtn.dataset;
            if (confirm(`Yakin ingin menghapus kategori "${nama}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                const response = await fetch(`${basePath}/api/kategori-kas`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') loadKategori();
            }
        }
    });

    loadKategori();
}

function initManajemenKategoriTabunganPage() {
    const tableBodySetor = document.getElementById('kategori-setor-table-body');
    const tableBodyTarik = document.getElementById('kategori-tarik-table-body');
    const modalEl = document.getElementById('kategoriTabunganModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('kategori-tabungan-form');
    const saveBtn = document.getElementById('save-kategori-btn');

    if (!tableBodySetor || !tableBodyTarik) return;

    async function loadKategori() {
        tableBodySetor.innerHTML = '<tr><td colspan="2" class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></td></tr>';
        tableBodyTarik.innerHTML = '<tr><td colspan="2" class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></td></tr>';

        try {
            const response = await fetch(`${basePath}/api/tabungan-kategori`);
            const result = await response.json();
            if (result.status === 'success') {
                renderTable(tableBodySetor, result.data.setor);
                renderTable(tableBodyTarik, result.data.tarik);
            } else { throw new Error(result.message); }
        } catch (error) {
            tableBodySetor.innerHTML = '<tr><td colspan="2" class="text-center text-danger">Gagal memuat.</td></tr>';
            tableBodyTarik.innerHTML = '<tr><td colspan="2" class="text-center text-danger">Gagal memuat.</td></tr>';
        }
    }

    function renderTable(tbody, data) {
        tbody.innerHTML = '';
        if (data && data.length > 0) {
            data.forEach(item => {
                const row = `<tr><td>${item.nama_kategori}</td><td class="text-end"><button class="btn btn-sm btn-info edit-btn" data-id="${item.id}"><i class="bi bi-pencil-fill"></i></button> <button class="btn btn-sm btn-danger delete-btn" data-id="${item.id}" data-nama="${item.nama_kategori}"><i class="bi bi-trash-fill"></i></button></td></tr>`;
                tbody.insertAdjacentHTML('beforeend', row);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Belum ada kategori.</td></tr>';
        }
    }

    modalEl.addEventListener('show.bs.modal', (e) => {
        const button = e.relatedTarget;
        form.reset();
        document.getElementById('kategori-action').value = 'add';
        document.getElementById('kategori-id').value = '';
        document.getElementById('kategoriTabunganModalLabel').textContent = 'Tambah Kategori Baru';
        if (button && button.dataset.jenis) {
            document.getElementById('kategori-jenis').value = button.dataset.jenis;
        }
    });

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const response = await fetch(`${basePath}/api/tabungan-kategori`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') { modal.hide(); loadKategori(); }
    });

    document.getElementById('kategori-tabungan-tables-container').addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) { /* Logic for edit is similar to kas, can be added if needed */ }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            const { id, nama } = deleteBtn.dataset;
            if (confirm(`Yakin ingin menghapus kategori "${nama}"?`)) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                const response = await fetch(`${basePath}/api/tabungan-kategori`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') loadKategori();
            }
        }
    });

    loadKategori();
}

function initAnggaranPage() {
    const yearFilter = document.getElementById('anggaran-tahun-filter');
    const reportTableBody = document.getElementById('anggaran-report-table-body');
    const modalEl = document.getElementById('anggaranModal');
    const modal = new bootstrap.Modal(modalEl);
    const modalTahunLabel = document.getElementById('modal-tahun-label');
    const managementContainer = document.getElementById('anggaran-management-container');
    const addAnggaranForm = document.getElementById('add-anggaran-form');

    if (!yearFilter || !reportTableBody) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    });

    // Populate year filter
    const currentYear = new Date().getFullYear();
    for (let i = 0; i < 5; i++) {
        const year = currentYear - i;
        yearFilter.add(new Option(year, year));
    }

    async function loadReport() {
        const selectedYear = yearFilter.value;
        reportTableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        try {
            const response = await fetch(`${basePath}/api/anggaran?action=get_report&tahun=${selectedYear}`);
            const result = await response.json();
            reportTableBody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(item => {
                    const percentage = item.persentase;
                    let progressBarColor = 'bg-success';
                    if (percentage > 75) progressBarColor = 'bg-warning';
                    if (percentage > 95) progressBarColor = 'bg-danger';

                    const row = `
                        <tr>
                            <td>${item.kategori}</td>
                            <td class="text-end">${currencyFormatter.format(item.jumlah_anggaran)}</td>
                            <td class="text-end">${currencyFormatter.format(item.realisasi_belanja)}</td>
                            <td class="text-end fw-bold ${item.sisa_anggaran < 0 ? 'text-danger' : ''}">${currencyFormatter.format(item.sisa_anggaran)}</td>
                            <td>
                                <div class="progress" role="progressbar" style="height: 20px;">
                                    <div class="progress-bar ${progressBarColor}" style="width: ${Math.min(percentage, 100)}%">${percentage.toFixed(1)}%</div>
                                </div>
                            </td>
                        </tr>
                    `;
                    reportTableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                reportTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Belum ada data anggaran untuk tahun ini.</td></tr>';
            }
        } catch (error) {
            reportTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Gagal memuat laporan.</td></tr>';
        }
    }

    async function loadBudgetManagement() {
        const selectedYear = yearFilter.value;
        modalTahunLabel.textContent = selectedYear;
        managementContainer.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';
        try {
            const response = await fetch(`${basePath}/api/anggaran?action=list_budget&tahun=${selectedYear}`);
            const result = await response.json();
            managementContainer.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(item => {
                    const itemHtml = `
                        <div class="input-group mb-2">
                            <span class="input-group-text" style="width: 150px;">${item.kategori}</span>
                            <input type="number" class="form-control budget-amount-input" data-id="${item.id}" value="${item.jumlah_anggaran}">
                            <button class="btn btn-outline-danger delete-budget-btn" data-id="${item.id}" title="Hapus"><i class="bi bi-trash"></i></button>
                        </div>
                    `;
                    managementContainer.insertAdjacentHTML('beforeend', itemHtml);
                });
            } else {
                managementContainer.innerHTML = '<p class="text-muted text-center">Belum ada anggaran yang ditetapkan untuk tahun ini.</p>';
            }
        } catch (error) {
            managementContainer.innerHTML = '<div class="alert alert-danger">Gagal memuat data anggaran.</div>';
        }
    }

    yearFilter.addEventListener('change', loadReport);

    modalEl.addEventListener('show.bs.modal', loadBudgetManagement);
    modalEl.addEventListener('hidden.bs.modal', loadReport); // Refresh report after closing modal

    addAnggaranForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const kategori = document.getElementById('new-kategori').value;
        const jumlah = document.getElementById('new-jumlah').value;
        const tahun = yearFilter.value;

        const formData = new FormData();
        formData.append('action', 'add_budget');
        formData.append('tahun', tahun);
        formData.append('kategori', kategori);
        formData.append('jumlah', jumlah);

        const response = await fetch(`${basePath}/api/anggaran`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') {
            addAnggaranForm.reset();
            loadBudgetManagement();
        }
    });

    managementContainer.addEventListener('change', async (e) => {
        if (e.target.classList.contains('budget-amount-input')) {
            const id = e.target.dataset.id;
            const jumlah = e.target.value;

            const formData = new FormData();
            formData.append('action', 'save_budget');
            formData.append('id', id);
            formData.append('jumlah', jumlah);

            const response = await fetch(`${basePath}/api/anggaran`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
        }
    });

    managementContainer.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.delete-budget-btn');
        if (deleteBtn) {
            if (confirm('Yakin ingin menghapus kategori anggaran ini?')) {
                const id = deleteBtn.dataset.id;
                const formData = new FormData();
                formData.append('action', 'delete_budget');
                formData.append('id', id);
                const response = await fetch(`${basePath}/api/anggaran`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') loadBudgetManagement();
            }
        }
    });

    loadReport();
}

function initSuratPengantarPage() {
    const isAdmin = (typeof userRole !== 'undefined' && userRole === 'admin');

    if (isAdmin) {
        const tableBody = document.getElementById('surat-admin-table-body');
        if (!tableBody) return;

        async function loadAdminRequests() {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
            const response = await fetch(`${basePath}/api/surat-pengantar`);
            const result = await response.json();
            tableBody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(s => {
                    const suratJson = JSON.stringify(s).replace(/'/g, '&apos;');
                    const statusColors = { pending: 'primary', approved: 'success', rejected: 'danger' };
                    const row = `
                        <tr>
                            <td>${new Date(s.created_at).toLocaleDateString('id-ID')}</td>
                            <td>${s.pemohon}</td>
                            <td>${s.jenis_surat}</td>
                            <td>${s.keperluan}</td>
                            <td><span class="badge bg-${statusColors[s.status]}">${s.status}</span></td>
                            <td>${s.pemroses || '-'}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-info view-surat-btn" data-surat='${suratJson}' data-bs-toggle="modal" data-bs-target="#suratModal">Kelola</button>
                            </td>
                        </tr>`;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Tidak ada permintaan surat.</td></tr>';
            }
        }

        const modalEl = document.getElementById('suratModal');
        const modal = new bootstrap.Modal(modalEl);
        modalEl.addEventListener('show.bs.modal', (e) => {
            const button = e.relatedTarget;
            // Pastikan modal dipicu oleh tombol "Kelola" yang memiliki data
            if (!button || !button.dataset.surat) {
                return;
            }
            const suratData = JSON.parse(button.dataset.surat);
            
            document.getElementById('surat-id').value = suratData.id;
            document.getElementById('suratModalLabel').textContent = 'Kelola Permintaan Surat';
            document.getElementById('surat-form-fields').classList.add('d-none');
            document.getElementById('surat-admin-actions').classList.remove('d-none');
            document.getElementById('surat-info-view').classList.remove('d-none');

            document.getElementById('view-pemohon').textContent = suratData.pemohon;
            document.getElementById('view-jenis-surat').textContent = suratData.jenis_surat || '(Tidak Ditemukan)';
            document.getElementById('view-keperluan').textContent = suratData.keperluan;
            document.getElementById('nomor_surat').value = suratData.nomor_surat || '';
            document.getElementById('keterangan_admin').value = suratData.keterangan_admin || '';

            const footer = document.getElementById('surat-modal-footer');
            footer.innerHTML = `
                <button type="button" class="btn btn-danger" id="reject-surat-btn">Tolak</button>
                <button type="button" class="btn btn-success" id="approve-surat-btn">Setujui & Simpan</button>
            `;

            document.getElementById('approve-surat-btn').onclick = () => handleAdminAction('approved');
            document.getElementById('reject-surat-btn').onclick = () => handleAdminAction('rejected');
        });

        async function handleAdminAction(status) {
            const form = document.getElementById('surat-form');
            const formData = new FormData(form);
            formData.set('action', 'update_status');
            formData.set('status', status);

            // Validasi: Nomor surat wajib diisi jika disetujui
            if (status === 'approved' && !formData.get('nomor_surat').trim()) {
                showToast('Nomor surat wajib diisi jika permintaan disetujui.', 'error');
                document.getElementById('nomor_surat').focus();
                return;
            }

            const response = await fetch(`${basePath}/api/surat-pengantar`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                modal.hide();
                loadAdminRequests();
            }
        }

        loadAdminRequests();

    } else { // Warga view
        const listContainer = document.getElementById('surat-warga-list');
        if (!listContainer) return;

        async function loadMyRequests() {
            listContainer.innerHTML = '<div class="col-12 text-center p-5"><div class="spinner-border"></div></div>';
            const response = await fetch(`${basePath}/api/surat-pengantar`);
            const result = await response.json();
            listContainer.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(s => {
                    const statusColors = { pending: 'primary', approved: 'success', rejected: 'danger' };
                    const printButton = (s.status === 'approved' && s.nomor_surat)
                        ? `<a href="${basePath}/surat-pengantar/cetak?id=${s.id}" target="_blank" class="btn btn-sm btn-success"><i class="bi bi-printer-fill"></i> Cetak PDF</a>`
                        : '';
                    const cancelButton = (s.status === 'pending')
                        ? `<button class="btn btn-sm btn-danger cancel-surat-btn" data-id="${s.id}" data-jenis="${s.jenis_surat}"><i class="bi bi-x-circle"></i> Batalkan</button>`
                        : '';
 
                    const card = `
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="card-title">${s.jenis_surat}</h5>
                                        <span class="badge bg-${statusColors[s.status]}">${s.status}</span>
                                    </div>
                                    <p class="card-text">${s.keperluan}</p>
                                    ${s.nomor_surat ? `<p class="card-text"><strong>No. Surat:</strong> ${s.nomor_surat}</p>` : ''}
                                    ${s.keterangan_admin ? `<p class="card-text text-danger small"><strong>Ket. Admin:</strong> ${s.keterangan_admin}</p>` : ''}
                                    <div class="mt-3 d-flex gap-2">
                                        ${printButton}
                                        ${cancelButton}
                                    </div>
                                </div>
                                <div class="card-footer text-muted small">
                                    Diajukan pada ${new Date(s.created_at).toLocaleDateString('id-ID')}
                                </div>
                            </div>
                        </div>`;
                    listContainer.insertAdjacentHTML('beforeend', card);
                });
            } else {
                listContainer.innerHTML = '<div class="col-12"><div class="alert alert-info">Anda belum pernah mengajukan permintaan surat.</div></div>';
            }
        }

        const modalEl = document.getElementById('suratModal');
        const modal = new bootstrap.Modal(modalEl);
        modalEl.addEventListener('show.bs.modal', () => {
            const jenisSuratSelect = document.getElementById('jenis_surat');
            jenisSuratSelect.innerHTML = '<option>Memuat...</option>';
            fetch(`${basePath}/api/surat-templates`)
                .then(res => res.json())
                .then(result => {
                    jenisSuratSelect.innerHTML = '<option value="">-- Pilih Jenis Surat --</option>';
                    if (result.status === 'success') {
                        result.data.forEach(template => {
                            jenisSuratSelect.add(new Option(template.nama_template, template.nama_template));
                        });
                    }
                });

            document.getElementById('suratModalLabel').textContent = 'Ajukan Permintaan Surat';
            document.getElementById('surat-form').reset();
            document.getElementById('surat-action').value = 'create';
            document.getElementById('surat-info-view').classList.add('d-none');
            
            const footer = document.getElementById('surat-modal-footer');
            footer.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="save-surat-btn">Kirim Permintaan</button>
            `;
            document.getElementById('save-surat-btn').onclick = async () => {
                const formData = new FormData(document.getElementById('surat-form'));
                const response = await fetch(`${basePath}/api/surat-pengantar`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    modal.hide();
                    loadMyRequests();
                }
            };
        });

        listContainer.addEventListener('click', async (e) => {
            const cancelBtn = e.target.closest('.cancel-surat-btn');
            if (cancelBtn) {
                const id = cancelBtn.dataset.id;
                const jenis = cancelBtn.dataset.jenis;
                if (confirm(`Apakah Anda yakin ingin membatalkan permintaan surat untuk "${jenis}"?`)) {
                    
                    const originalBtnHtml = cancelBtn.innerHTML;
                    cancelBtn.disabled = true;
                    cancelBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

                    try {
                        const formData = new FormData();
                        formData.append('action', 'cancel_request');
                        formData.append('surat_id', id);

                        const response = await fetch(`${basePath}/api/surat-pengantar`, { method: 'POST', body: formData });
                        const result = await response.json();
                        showToast(result.message, result.status === 'success' ? 'success' : 'error');
                        if (result.status === 'success') {
                            loadMyRequests();
                        } else {
                            cancelBtn.disabled = false;
                            cancelBtn.innerHTML = originalBtnHtml;
                        }
                    } catch (error) {
                        showToast('Terjadi kesalahan jaringan.', 'error');
                        cancelBtn.disabled = false;
                        cancelBtn.innerHTML = originalBtnHtml;
                    }
                }
            }
        });

        loadMyRequests();
    }
}

function initLaporanTerpaduPage() {
    // Initialize the first tab's content on page load
    initLaporanKeuanganPage();

    const triggerTabList = document.querySelectorAll('#laporanTerpaduTab button[data-bs-toggle="tab"]');
    triggerTabList.forEach(triggerEl => {
        triggerEl.addEventListener('shown.bs.tab', event => {
            const targetId = event.target.getAttribute('data-bs-target');
            if (targetId === '#laporan-keuangan-pane') {
                initLaporanKeuanganPage();
            } else if (targetId === '#laporan-tunggakan-pane') {
                initLaporanIuranPage();
            } else if (targetId === '#laporan-statistik-pane') {
                initLaporanIuranStatistikPage();
            } else if (targetId === '#laporan-surat-pane') {
                initLaporanSuratPage();
            }
            localStorage.setItem('lastLaporanTerpaduTab', event.target.id);
        });
    });

    // Handle tab persistence on page load
    const lastTabId = localStorage.getItem('lastLaporanTerpaduTab');
    if (lastTabId) {
        const lastTab = document.querySelector(`#${lastTabId}`);
        if (lastTab) {
            // Use 'shown.bs.tab' to trigger the correct init function after the tab is shown
            new bootstrap.Tab(lastTab).show();
        }
    }
}

function initLaporanSuratPage() {
    const tipeFilter = document.getElementById('laporan-tipe-filter');
    const bulanFilterContainer = document.getElementById('laporan-bulan-filter-container');
    const bulanFilter = document.getElementById('laporan-bulan-filter');
    const tahunFilter = document.getElementById('laporan-tahun-filter');
    const statusFilter = document.getElementById('laporan-status-filter');
    const tableBody = document.getElementById('laporan-surat-table-body');
    const totalSummary = document.getElementById('total-surat-summary');
    const exportPdfBtn = document.getElementById('export-surat-pdf-btn');
    const exportExcelBtn = document.getElementById('export-surat-excel-btn');
    const chartCanvas = document.getElementById('surat-report-chart');

    if (!tipeFilter || !bulanFilter || !tahunFilter || !tableBody || !statusFilter || !chartCanvas) return;

    function setupFilters() {
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;

        // Populate years
        for (let i = 0; i < 5; i++) {
            const year = currentYear - i;
            tahunFilter.add(new Option(year, year));
        }

        // Populate months
        const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        months.forEach((month, index) => {
            bulanFilter.add(new Option(month, index + 1));
        });

        // Set default to current month and year
        bulanFilter.value = currentMonth;
        tahunFilter.value = currentYear;
    }

    async function loadReport() {
        const tipe = tipeFilter.value;
        const bulan = bulanFilter.value;
        const tahun = tahunFilter.value;
        const status = statusFilter.value;

        tableBody.innerHTML = '<tr><td colspan="2" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        totalSummary.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';

        try {
            const url = `${basePath}/api/surat-pengantar?action=get_report&tipe=${tipe}&tahun=${tahun}&status=${status}` + (tipe === 'bulanan' ? `&bulan=${bulan}` : '');
            const response = await fetch(url);
            const result = await response.json();

            if (result.status === 'success') {
                totalSummary.textContent = result.data.total;
                tableBody.innerHTML = '';
                if (result.data.details.length > 0) {
                    renderSuratChart(result.data.details);
                    result.data.details.forEach(item => {
                        tableBody.insertAdjacentHTML('beforeend', `<tr><td>${item.jenis_surat}</td><td class="text-end">${item.jumlah}</td></tr>`);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="2" class="text-center">Tidak ada data untuk periode ini.</td></tr>';
                }
            } else { throw new Error(result.message); }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="2" class="text-center text-danger">Gagal memuat laporan.</td></tr>`;
            totalSummary.textContent = 'Error';
            if (window.laporanSuratChart) {
                window.laporanSuratChart.destroy();
                window.laporanSuratChart = null;
            }
            showToast(error.message, 'error');
        }
    }

    function updateExportLinks() {
        const tipe = tipeFilter.value;
        const bulan = bulanFilter.value;
        const tahun = tahunFilter.value;
        const status = statusFilter.value;
        const baseExcelUrl = `${basePath}/api/laporan/surat/export/excel?tipe=${tipe}&tahun=${tahun}&status=${status}`;

        if (exportExcelBtn) {
            exportExcelBtn.href = tipe === 'bulanan' ? `${baseExcelUrl}&bulan=${bulan}` : baseExcelUrl;
        }
    }

    function renderSuratChart(details) {
        if (window.laporanSuratChart) {
            window.laporanSuratChart.destroy();
        }

        if (!details || details.length === 0) {
            return; // Do nothing if no data
        }

        const labels = details.map(item => item.jenis_surat);
        const data = details.map(item => item.jumlah);

        const backgroundColors = [
            'rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)', 'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)',
            'rgba(199, 199, 199, 0.7)', 'rgba(83, 102, 255, 0.7)'
        ];

        window.laporanSuratChart = new Chart(chartCanvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Surat',
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: document.body.classList.contains('dark-mode') ? '#343a40' : '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    }

    exportPdfBtn.addEventListener('click', (e) => {
        e.preventDefault();

        if (!window.laporanSuratChart) {
            showToast('Grafik belum dimuat atau tidak ada data untuk diekspor.', 'error');
            return;
        }

        // 1. Ambil gambar grafik sebagai base64
        const chartImage = window.laporanSuratChart.toBase64Image();
        
        // 2. Simpan gambar di sessionStorage untuk diteruskan ke tab baru
        try {
            sessionStorage.setItem('chartImageData', chartImage);
        } catch (error) {
            showToast('Gagal menyimpan data grafik untuk dicetak. Coba lagi.', 'error');
            console.error('Session storage error:', error);
            return;
        }

        // 3. Buat URL untuk halaman cetak dengan filter saat ini
        const tipe = tipeFilter.value;
        const bulan = bulanFilter.value;
        const tahun = tahunFilter.value;
        const status = statusFilter.value;
        let url = `${basePath}/laporan/surat/cetak?tipe=${tipe}&tahun=${tahun}&status=${status}`;
        if (tipe === 'bulanan') { url += `&bulan=${bulan}`; }

        // 4. Buka halaman cetak di tab baru
        window.open(url, '_blank');
    });

    tipeFilter.addEventListener('change', () => {
        bulanFilterContainer.style.display = tipeFilter.value === 'bulanan' ? 'block' : 'none';
        loadReport();
        updateExportLinks();
    });
    bulanFilter.addEventListener('change', () => {
        loadReport();
        updateExportLinks();
    });
    tahunFilter.addEventListener('change', () => {
        loadReport();
        updateExportLinks();
    });
    statusFilter.addEventListener('change', () => {
        loadReport();
        updateExportLinks();
    });

    // Panggil fungsi untuk mengisi filter dan memuat data awal
    setupFilters();
    loadReport();
    updateExportLinks();
}

function initAsetPage() {
    const tableBody = document.getElementById('aset-table-body');
    const modalEl = document.getElementById('asetModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('aset-form');
    const saveBtn = document.getElementById('save-aset-btn');

    if (!tableBody) return;

    async function loadAset() {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        const response = await fetch(`${basePath}/api/aset`);
        const result = await response.json();
        tableBody.innerHTML = '';
        if (result.status === 'success' && result.data.length > 0) {
            result.data.forEach(a => {
                const kondisiColors = { 'Baik': 'success', 'Rusak Ringan': 'warning', 'Rusak Berat': 'danger' };
                const row = `
                    <tr id="aset-${a.id}">
                        <td>${a.nama_aset}</td>
                        <td>${a.jumlah}</td>
                        <td><span class="badge bg-${kondisiColors[a.kondisi]}">${a.kondisi}</span></td>
                        <td>${a.lokasi_simpan || '-'}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-info edit-btn" data-id="${a.id}"><i class="bi bi-pencil-fill"></i></button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${a.id}"><i class="bi bi-trash-fill"></i></button>
                        </td>
                    </tr>`;
                tableBody.insertAdjacentHTML('beforeend', row);
            });
        } else {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Belum ada data aset.</td></tr>';
        }
    }

    modalEl.addEventListener('show.bs.modal', async (e) => {
        const button = e.relatedTarget;
        const action = button.dataset.action;
        form.reset();
        document.getElementById('aset-action').value = action;
        if (action === 'add') {
            document.getElementById('asetModalLabel').textContent = 'Tambah Aset Baru';
            document.getElementById('aset-id').value = '';
        } else {
            document.getElementById('asetModalLabel').textContent = 'Edit Aset';
            const id = button.dataset.id;
            document.getElementById('aset-id').value = id;
            const formData = new FormData();
            formData.append('action', 'get_single');
            formData.append('id', id);
            const response = await fetch(`${basePath}/api/aset`, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                document.getElementById('nama_aset').value = result.data.nama_aset;
                document.getElementById('jumlah_aset').value = result.data.jumlah;
                document.getElementById('kondisi_aset').value = result.data.kondisi;
                document.getElementById('lokasi_simpan').value = result.data.lokasi_simpan;
            }
        }
    });

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const response = await fetch(`${basePath}/api/aset`, { method: 'POST', body: formData });
        const result = await response.json();
        showToast(result.message, result.status === 'success' ? 'success' : 'error');
        if (result.status === 'success') {
            modal.hide();
            loadAset();
        }
    });

    tableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-btn');
        if (editBtn) {
            editBtn.setAttribute('data-bs-toggle', 'modal');
            editBtn.setAttribute('data-bs-target', '#asetModal');
            editBtn.setAttribute('data-action', 'edit');
            new bootstrap.Modal(modalEl).show(editBtn);
        }

        const deleteBtn = e.target.closest('.delete-btn');
        if (deleteBtn) {
            if (confirm('Yakin ingin menghapus aset ini?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', deleteBtn.dataset.id);
                const response = await fetch(`${basePath}/api/aset`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') loadAset();
            }
        }
    });

    loadAset();
}

function initGaleriPage() {
    const albumList = document.getElementById('album-list');
    const isAdmin = (typeof userRole !== 'undefined' && userRole === 'admin');

    if (!albumList) return;

    async function loadAlbums() {
        albumList.innerHTML = '<div class="text-center p-5"><div class="spinner-border" style="width: 3rem; height: 3rem;"></div></div>';
        try {
            const response = await fetch(`${basePath}/api/galeri?action=list_albums`);
            const result = await response.json();
            albumList.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(album => {
                    const tgl = new Date(album.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                    
                    let thumbnailHtml;
                    if (album.thumbnail) {
                        thumbnailHtml = `<img src="${basePath}/${album.thumbnail}" class="card-img-top" alt="${album.judul}" style="height: 200px; object-fit: cover;">`;
                    } else {
                        thumbnailHtml = `
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-body-secondary text-secondary" style="height: 200px;">
                                <i class="bi bi-images" style="font-size: 3rem;"></i>
                            </div>
                        `;
                    }

                    const adminControls = isAdmin ? `
                        <div class="position-absolute top-0 end-0 p-2">
                            <button class="btn btn-sm btn-light edit-album-btn" data-id="${album.id}" title="Edit Album"><i class="bi bi-pencil-fill"></i></button>
                            <button class="btn btn-sm btn-danger delete-album-btn" data-id="${album.id}" data-judul="${album.judul}" title="Hapus Album"><i class="bi bi-trash-fill"></i></button>
                        </div>
                    ` : '';

                    const card = `
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm album-card">
                                <a href="${basePath}/galeri/album/${album.id}" class="text-decoration-none text-dark">
                                    ${thumbnailHtml}
                                    <div class="card-body">
                                        <h5 class="card-title">${album.judul}</h5>
                                        <p class="card-text text-muted small">${album.deskripsi || ''}</p>
                                    </div>
                                </a>
                                <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><i class="bi bi-image"></i> ${album.jumlah_foto} foto</small>
                                    <small class="text-muted">Dibuat: ${tgl}</small>
                                </div>
                                ${adminControls}
                            </div>
                        </div>`;
                    albumList.insertAdjacentHTML('beforeend', card);
                });
            } else {
                albumList.innerHTML = '<div class="col-12"><div class="alert alert-info">Belum ada album foto yang dibuat.</div></div>';
            }
        } catch (error) {
            albumList.innerHTML = '<div class="col-12"><div class="alert alert-danger">Gagal memuat album.</div></div>';
        }
    }

    if (isAdmin) {
        const modalEl = document.getElementById('albumModal');
        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('album-form');
        const saveBtn = document.getElementById('save-album-btn');
        const kegiatanSelect = document.getElementById('kegiatan_id_album');

        async function loadKegiatanList() {
            try {
                const response = await fetch(`${basePath}/api/kegiatan?action=list`);
                const result = await response.json();
                kegiatanSelect.innerHTML = '<option value="">-- Tidak ditautkan --</option>';
                if (result.status === 'success') {
                    result.data.forEach(k => kegiatanSelect.add(new Option(k.judul, k.id)));
                }
            } catch (e) { console.error('Gagal memuat daftar kegiatan'); }
        }

        modalEl.addEventListener('show.bs.modal', async (e) => {
            const button = e.relatedTarget;
            const action = button.dataset.action;
            form.reset();
            document.getElementById('album-action').value = action;
            await loadKegiatanList();

            if (action === 'add') {
                document.getElementById('album-action').value = 'create_album';
                document.getElementById('album-id').value = '';
            } else { // edit
                document.getElementById('albumModalLabel').textContent = 'Edit Album';
                const id = button.dataset.id;
                // Fetch album data to populate form (simplified for brevity)
                // In a real app, you'd fetch the specific album's data here.
                // For now, we just set the ID.
                document.getElementById('album-id').value = id;
                // You would need to fetch and set judul, deskripsi, and kegiatan_id here.
            }
        });

        saveBtn.addEventListener('click', async () => {
            const formData = new FormData(form);
            const response = await fetch(`${basePath}/api/galeri`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                modal.hide();
                loadAlbums();
            }
        });

        // Listener for ADD button to open modal
        modalEl.addEventListener('show.bs.modal', async (e) => {
            const button = e.relatedTarget;
            // Only proceed if it's the "add" button, edit is handled separately
            if (!button || button.dataset.action !== 'add') return;

            form.reset();
            document.getElementById('albumModalLabel').textContent = 'Buat Album Baru';
            document.getElementById('album-action').value = 'create_album';
            document.getElementById('album-id').value = '';
            await loadKegiatanList();
        });

        // Delegated listener for EDIT and DELETE buttons
        albumList.addEventListener('click', async (e) => {
            const editBtn = e.target.closest('.edit-album-btn');
            if (editBtn) {
                const id = editBtn.dataset.id;
                
                // Fetch album data
                const formData = new FormData();
                formData.append('action', 'get_single_album');
                formData.append('id', id);

                try {
                    const response = await fetch(`${basePath}/api/galeri`, { method: 'POST', body: formData });
                    const result = await response.json();

                    if (result.status === 'success') {
                        const album = result.data;
                        
                        document.getElementById('albumModalLabel').textContent = 'Edit Album';
                        form.reset();
                        await loadKegiatanList();
                        
                        document.getElementById('album-action').value = 'update_album';
                        document.getElementById('album-id').value = album.id;
                        document.getElementById('judul_album').value = album.judul;
                        document.getElementById('deskripsi_album').value = album.deskripsi || '';
                        document.getElementById('kegiatan_id_album').value = album.kegiatan_id || '';
                        
                        modal.show();
                    } else { showToast(result.message, 'error'); }
                } catch (error) { showToast('Gagal mengambil data album.', 'error'); }
            }

            const deleteBtn = e.target.closest('.delete-album-btn');
            if (deleteBtn) {
                const { id, judul } = deleteBtn.dataset;
                if (confirm(`Yakin ingin menghapus album "${judul}" dan semua fotonya?`)) {
                    const formData = new FormData();
                    formData.append('action', 'delete_album');
                    formData.append('id', id);
                    const response = await fetch(`${basePath}/api/galeri`, { method: 'POST', body: formData });
                    const result = await response.json();
                    showToast(result.message, result.status === 'success' ? 'success' : 'error');
                    if (result.status === 'success') loadAlbums();
                }
            }
        });
    }

    loadAlbums();
}

function initGaleriAlbumPage() {
    const container = document.getElementById('album-detail-container');
    if (!container) return;

    const albumId = container.dataset.albumId;
    const titleEl = document.getElementById('album-title');
    const descEl = document.getElementById('album-description');
    const gridEl = document.getElementById('photo-grid');
    const isAdmin = (typeof userRole !== 'undefined' && userRole === 'admin');
    const viewPhotoModalEl = document.getElementById('viewPhotoModal');
    const viewPhotoModal = new bootstrap.Modal(viewPhotoModalEl);
    const commentForm = document.getElementById('comment-form');
    const commentList = document.getElementById('comment-list');

    async function loadAlbumDetail() {
        try {
            const response = await fetch(`${basePath}/api/galeri?action=get_album&id=${albumId}`);
            const result = await response.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { info, photos } = result.data;
            titleEl.textContent = info.judul;
            descEl.textContent = info.deskripsi || '';
            document.title = `Galeri: ${info.judul}`;

            gridEl.innerHTML = '';
            if (photos.length > 0) {
                photos.forEach(photo => {
                    const adminControls = isAdmin ? `<button class="btn btn-sm btn-danger delete-photo-btn" data-id="${photo.id}" title="Hapus Foto"><i class="bi bi-trash"></i></button>` : '';
                    const photoCard = `
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <div class="card photo-card" data-photo-id="${photo.id}" data-caption="${photo.caption || ''}" data-path="${basePath}/${photo.path_file}">
                                <img src="${basePath}/${photo.path_file}" class="img-fluid" alt="${photo.caption || 'Foto Kegiatan'}" style="cursor: pointer;">
                                <div class="photo-overlay">
                                    ${adminControls}
                                </div>
                            </div>
                        </div>
                    `;
                    gridEl.insertAdjacentHTML('beforeend', photoCard);
                });
            } else {
                gridEl.innerHTML = '<div class="col-12"><div class="alert alert-info">Belum ada foto di album ini.</div></div>';
            }
        } catch (error) {
            gridEl.innerHTML = `<div class="col-12"><div class="alert alert-danger">Gagal memuat foto: ${error.message}</div></div>`;
        }
    }

    // --- Event Listeners ---
    if (isAdmin) {
        const uploadModalEl = document.getElementById('uploadFotoModal');
        const uploadModal = new bootstrap.Modal(uploadModalEl);
        const saveUploadBtn = document.getElementById('save-upload-btn');

        uploadModalEl.addEventListener('hidden.bs.modal', () => {
            const form = document.getElementById('upload-foto-form');
            if(form) form.reset();
        });

        saveUploadBtn.addEventListener('click', async () => {
            const form = document.getElementById('upload-foto-form');
            const formData = new FormData(form);
            
            const originalBtnHtml = saveUploadBtn.innerHTML;
            saveUploadBtn.disabled = true;
            saveUploadBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Mengunggah...`;

            try {
                const response = await fetch(`${basePath}/api/galeri`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    uploadModal.hide();
                    loadAlbumDetail();
                }
            } catch (error) {
                showToast('Terjadi kesalahan jaringan saat mengunggah.', 'error');
            } finally {
                saveUploadBtn.disabled = false;
                saveUploadBtn.innerHTML = originalBtnHtml;
            }
        });

        gridEl.addEventListener('click', async (e) => {
            const deleteBtn = e.target.closest('.delete-photo-btn');
            if (deleteBtn) {
                if (confirm('Yakin ingin menghapus foto ini?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete_photo');
                    formData.append('id', deleteBtn.dataset.id);
                    const response = await fetch(`${basePath}/api/galeri`, { method: 'POST', body: formData });
                    const result = await response.json();
                    showToast(result.message, result.status === 'success' ? 'success' : 'error');
                    if (result.status === 'success') loadAlbumDetail();
                }
            }
        });
    }

    gridEl.addEventListener('click', async (e) => {
        const card = e.target.closest('.photo-card');
        // Don't open modal if delete button is clicked
        if (card && !e.target.closest('.delete-photo-btn')) {
            const photoId = card.dataset.photoId;
            const photoPath = card.dataset.path;
            const photoCaption = card.dataset.caption;

            document.getElementById('view-photo-img').src = photoPath;
            document.getElementById('view-photo-caption').textContent = photoCaption || 'Detail Foto';
            document.getElementById('comment-foto-id').value = photoId;
            commentList.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm"></div></div>';
            viewPhotoModal.show();

            // Fetch comments
            try {
                const response = await fetch(`${basePath}/api/galeri?action=get_photo_details&id=${photoId}`);
                const result = await response.json();
                if (result.status === 'success') {
                    renderComments(result.data.comments);
                } else {
                    commentList.innerHTML = '<div class="alert alert-warning small">Gagal memuat komentar.</div>';
                }
            } catch (error) {
                commentList.innerHTML = '<div class="alert alert-danger small">Terjadi kesalahan jaringan.</div>';
            }
        }
    });

    function renderComments(comments) {
        commentList.innerHTML = '';
        if (comments.length > 0) {
            comments.forEach(comment => appendComment(comment));
        } else {
            commentList.innerHTML = '<p class="text-muted text-center small mt-3">Belum ada komentar.</p>';
        }
    }

    function appendComment(comment) {
        const timeAgo = timeSince(new Date(comment.created_at));
        const profilePicHtml = comment.foto_profil 
            ? `<img src="${basePath}/${comment.foto_profil}" class="rounded-circle me-2" width="32" height="32" style="object-fit: cover;">`
            : `<i class="bi bi-person-circle fs-4 text-secondary me-2"></i>`;
        const deleteBtnHtml = comment.can_delete
            ? `<button class="btn btn-sm btn-link text-danger p-0 delete-comment-btn" data-comment-id="${comment.id}" title="Hapus"><i class="bi bi-trash"></i></button>` 
            : '';

        const commentHtml = `
            <div class="d-flex align-items-start mb-3" id="comment-${comment.id}">
                ${profilePicHtml}
                <div class="flex-grow-1">
                    <div class="bg-body-secondary rounded p-2">
                        <div class="d-flex justify-content-between">
                            <strong class="small">${comment.nama_lengkap}</strong>
                            ${deleteBtnHtml}
                        </div>
                        <p class="mb-0 small">${comment.komentar}</p>
                    </div>
                    <small class="text-muted ms-2">${timeAgo}</small>
                </div>
            </div>
        `;
        commentList.insertAdjacentHTML('beforeend', commentHtml);
        commentList.scrollTop = commentList.scrollHeight;
    }

    commentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = document.getElementById('submit-comment-btn');
        const formData = new FormData(commentForm);
        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const response = await fetch(`${basePath}/api/galeri`, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                if (commentList.querySelector('.text-muted')) commentList.innerHTML = '';
                appendComment(result.data);
                commentForm.reset();
                document.getElementById('comment-foto-id').value = formData.get('foto_id');
                document.querySelector('#comment-form input[name="action"]').value = 'add_comment';
            } else { showToast(result.message, 'error'); }
        } catch (error) { showToast('Gagal mengirim komentar.', 'error'); } 
        finally { submitBtn.disabled = false; submitBtn.innerHTML = originalBtnHtml; }
    });

    commentList.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.delete-comment-btn');
        if (deleteBtn) {
            const commentId = deleteBtn.dataset.commentId;
            if (confirm('Yakin ingin menghapus komentar ini?')) {
                const formData = new FormData();
                formData.append('action', 'delete_comment');
                formData.append('comment_id', commentId);
                const response = await fetch(`${basePath}/api/galeri`, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    document.getElementById(`comment-${commentId}`).remove();
                    showToast('Komentar dihapus.', 'success');
                } else { showToast(result.message, 'error'); }
            }
        }
    });

    loadAlbumDetail();
}

function initLaporanIuranPage() {
    const tableBody = document.getElementById('tunggakan-table-body');
    const searchInput = document.getElementById('search-tunggakan');
    const tahunFilter = document.getElementById('filter-tahun-tunggakan');
    const minTunggakanFilter = document.getElementById('filter-min-tunggakan');
    const cetakBtn = document.getElementById('cetak-tunggakan-btn');
    const exportBtn = document.getElementById('export-tunggakan-btn');
    const totalWargaEl = document.getElementById('total-warga-menunggak');
    const totalPotensiEl = document.getElementById('total-potensi-pemasukan');

    if (!tableBody) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    });

    function setupFilters() {
        const currentYear = new Date().getFullYear();
        for (let i = 0; i < 5; i++) {
            const year = currentYear - i;
            tahunFilter.add(new Option(year, year));
        }
    }

    async function loadLaporan() {
        const tahun = tahunFilter.value;
        const min_tunggakan = minTunggakanFilter.value;
        const search = searchInput.value;

        tableBody.innerHTML = '<tr><td colspan="6" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        totalWargaEl.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
        totalPotensiEl.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';

        try {
            const url = `${basePath}/api/laporan/iuran?tahun=${tahun}&min_tunggakan=${min_tunggakan}&search=${encodeURIComponent(search)}`;
            const response = await fetch(url);
            const result = await response.json();

            if (result.status === 'success') {
                tableBody.innerHTML = '';
                if (result.data.length > 0) {
                    result.data.forEach((item, index) => {
                        const row = `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${item.nama_lengkap}</td>
                                <td>${item.alamat}</td>
                                <td>${item.jumlah_tunggakan} bulan</td>
                                <td class="text-danger fw-bold">${currencyFormatter.format(item.total_tunggakan)}</td>
                                <td class="text-end">
                                    <a href="${basePath}/iuran/histori/${item.no_kk}/kk" class="btn btn-sm btn-outline-info" title="Lihat Histori">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                </td>
                            </tr>
                        `;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Tidak ada warga yang menunggak sesuai filter.</td></tr>';
                }
                // Update summary
                totalWargaEl.textContent = result.summary.total_warga;
                totalPotensiEl.textContent = currencyFormatter.format(result.summary.total_potensi);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Gagal memuat laporan: ${error.message}</td></tr>`;
            totalWargaEl.textContent = 'Error';
            totalPotensiEl.textContent = 'Error';
        }
    }

    function updateActionButtons() {
        const tahun = tahunFilter.value;
        const min_tunggakan = minTunggakanFilter.value;
        const search = searchInput.value;
        
        cetakBtn.onclick = () => {
            const url = `${basePath}/laporan/iuran/cetak?tahun=${tahun}&min_tunggakan=${min_tunggakan}&search=${encodeURIComponent(search)}`;
            window.open(url, '_blank');
        };

        exportBtn.onclick = () => {
            const url = `${basePath}/api/laporan/iuran/export?tahun=${tahun}&min_tunggakan=${min_tunggakan}&search=${encodeURIComponent(search)}`;
            window.location.href = url;
        };
    }

    let debounceTimer;
    const combinedFilterHandler = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            loadLaporan();
            updateActionButtons();
        }, 300);
    };

    searchInput.addEventListener('input', combinedFilterHandler);
    tahunFilter.addEventListener('change', combinedFilterHandler);
    minTunggakanFilter.addEventListener('change', combinedFilterHandler);

    setupFilters();
    loadLaporan();
    updateActionButtons();
}

function initLaporanIuranStatistikPage() {
    const tahunFilter = document.getElementById('statistik-tahun-filter');
    const loadingSpinner = document.getElementById('statistik-loading-spinner');
    const pemasukanCanvas = document.getElementById('pemasukan-chart');
    const kepatuhanCanvas = document.getElementById('kepatuhan-chart');

    function setupFilters() {
        const currentYear = new Date().getFullYear();
        for (let i = 0; i < 5; i++) {
            const year = currentYear - i;
            tahunFilter.add(new Option(year, year));
        }
    }

    async function loadStatistik() {
        loadingSpinner.style.display = 'block';
        if (window.laporanStatistikPemasukanChart) window.laporanStatistikPemasukanChart.destroy();
        if (window.laporanStatistikKepatuhanChart) window.laporanStatistikKepatuhanChart.destroy();

        const selectedYear = tahunFilter.value;

        try {
            const response = await fetch(`${basePath}/api/laporan/iuran/statistik?tahun=${selectedYear}`);
            const result = await response.json();

            if (result.status === 'success') {
                const { labels, pemasukan, kepatuhan } = result.data;

                // Render Pemasukan Chart (Bar)
                window.laporanStatistikPemasukanChart = new Chart(pemasukanCanvas, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: pemasukan.label,
                            data: pemasukan.data,
                            backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value); }
                                }
                            }
                        }
                    }
                });

                // Render Kepatuhan Chart (Line)
                window.laporanStatistikKepatuhanChart = new Chart(kepatuhanCanvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: kepatuhan.label,
                            data: kepatuhan.data,
                            fill: false,
                            borderColor: 'rgb(255, 99, 132)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: { callback: function(value) { return value + "%" } }
                            }
                        },
                        plugins: {
                            annotation: {
                                annotations: {
                                    targetLine: {
                                        type: 'line',
                                        yMin: 90,
                                        yMax: 90,
                                        borderColor: 'rgb(220, 53, 69)', // bs-danger
                                        borderWidth: 2,
                                        borderDash: [6, 6],
                                        label: {
                                            content: 'Target 90%',
                                            position: 'end',
                                            backgroundColor: 'rgba(220, 53, 69, 0.8)',
                                            enabled: true
                                        }
                                    }
                                }
                            }
                        }
                    }
                });

            } else { throw new Error(result.message); }
        } catch (error) {
            showToast(`Gagal memuat statistik: ${error.message}`, 'error');
        } finally {
            loadingSpinner.style.display = 'none';
        }
    }

    if (!tahunFilter || !loadingSpinner || !pemasukanCanvas || !kepatuhanCanvas) return;

    tahunFilter.addEventListener('change', loadStatistik);
    setupFilters();
    loadStatistik();
}

function initLogAktivitasPage() {
    const tableBody = document.getElementById('log-table-body');
    const searchInput = document.getElementById('search-log');
    const limitSelect = document.getElementById('log-limit');
    const paginationContainer = document.getElementById('log-pagination');
    const clearOldLogsBtn = document.getElementById('clear-old-logs-btn');
    const logDetailModalEl = document.getElementById('logDetailModal');
    const logDetailModal = new bootstrap.Modal(logDetailModalEl);

    if (!tableBody) return;

    // Dynamically set the button text based on the global setting
    if (clearOldLogsBtn && typeof logCleanupDays !== 'undefined') {
        const months = Math.round(logCleanupDays / 30);
        clearOldLogsBtn.innerHTML = `
            <i class="bi bi-trash3-fill"></i> Bersihkan Log > ${months} Bulan
        `;
    }

    let currentPage = 1;

    async function loadLogs(searchTerm = '', page = 1, perPage = '15') {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        try {
            let apiUrl = `${basePath}/api/log?search=${encodeURIComponent(searchTerm)}&page=${page}`;
            if (perPage !== 'all') {
                apiUrl += `&limit=${perPage}`;
            }
            const response = await fetch(apiUrl);
            const result = await response.json();
            tableBody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(log => {
                    const truncatedDetails = (log.details && log.details.length > 50) ? log.details.substring(0, 50) + '...' : (log.details || '-');
                    const logData = JSON.stringify(log).replace(/'/g, "&apos;");

                    const row = `
                        <tr class="log-row" data-log='${logData}' style="cursor: pointer;" title="Klik untuk detail">
                            <td>${new Date(log.timestamp).toLocaleString('id-ID')}</td>
                            <td>${log.username}</td>
                            <td><span class="badge bg-secondary">${log.action}</span></td>
                            <td>${truncatedDetails}</td>
                            <td>${log.ip_address}</td>
                        </tr>`;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada data log ditemukan.</td></tr>';
            }
            renderPagination(paginationContainer, result.pagination, (newPage) => {
                loadLogs(searchInput.value, newPage, limitSelect.value);
            });
            currentPage = page;
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Gagal memuat data log.</td></tr>`;
            renderPagination(paginationContainer, null);
        }
    }

    let debounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadLogs(searchInput.value, 1, limitSelect.value), 300);
    });

    limitSelect.addEventListener('change', () => {
        loadLogs(searchInput.value, 1, limitSelect.value);
    });

    tableBody.addEventListener('click', (e) => {
        const row = e.target.closest('.log-row');
        if (row && row.dataset.log) {
            const logData = JSON.parse(row.dataset.log.replace(/&apos;/g, "'"));
            
            document.getElementById('modal-log-waktu').textContent = new Date(logData.timestamp).toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'long' });
            document.getElementById('modal-log-username').textContent = logData.username;
            document.getElementById('modal-log-aksi').textContent = logData.action;
            document.getElementById('modal-log-ip').textContent = logData.ip_address;
            document.getElementById('modal-log-detail').textContent = logData.details || '(Tidak ada detail)';
            
            logDetailModal.show();
        }
    });

    if (clearOldLogsBtn) {
        clearOldLogsBtn.addEventListener('click', async () => {
            const months = Math.round(logCleanupDays / 30);
            if (confirm(`Apakah Anda yakin ingin menghapus semua log aktivitas yang lebih lama dari ${months} bulan? Aksi ini tidak dapat dibatalkan.`)) {
                const originalBtnHtml = clearOldLogsBtn.innerHTML;
                clearOldLogsBtn.disabled = true;
                clearOldLogsBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Membersihkan...`;

                try {
                    const formData = new FormData();
                    formData.append('action', 'clear_old');
                    
                    const response = await fetch(`${basePath}/api/log`, {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    showToast(result.message, result.status === 'success' ? 'success' : 'error');
                    
                    if (result.status === 'success') {
                        loadLogs(searchInput.value, 1, limitSelect.value); // Reload logs from the first page
                    }
                } catch (error) {
                    showToast('Terjadi kesalahan jaringan.', 'error');
                } finally {
                    clearOldLogsBtn.disabled = false;
                    clearOldLogsBtn.innerHTML = originalBtnHtml;
                }
            }
        });
    }

    loadLogs(searchInput.value, 1, limitSelect.value);
}

function initPanicLogPage() {
    const tableBody = document.getElementById('panic-log-table-body');
    const paginationContainer = document.getElementById('panic-log-pagination');

    if (!tableBody) return;

    let currentPage = 1;

    async function loadLog(page = 1) {
        tableBody.innerHTML = '<tr><td colspan="5" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        try {
            const response = await fetch(`${basePath}/api/panic-log?page=${page}`);
            const result = await response.json();
            tableBody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(log => {
                    const row = `
                        <tr>
                            <td>${new Date(log.timestamp).toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'long' })}</td>
                            <td>${log.nama_lengkap}</td>
                            <td>${log.alamat}</td>
                            <td>${log.no_telepon || '-'}</td>
                            <td>${log.ip_address}</td>
                        </tr>`;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada data log panik ditemukan.</td></tr>';
            }
            renderPanicLogPagination(result.pagination);
            currentPage = page;
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Gagal memuat data log.</td></tr>`;
            renderPanicLogPagination(null);
        }
    }

    function renderPanicLogPagination(pagination) {
        if (!paginationContainer) return;
        paginationContainer.innerHTML = '';
        if (!pagination || pagination.total_pages <= 1) return;

        const { current_page, total_pages } = pagination;

        const prevDisabled = current_page === 1 ? 'disabled' : '';
        paginationContainer.insertAdjacentHTML('beforeend', `<li class="page-item ${prevDisabled}"><a class="page-link" href="#" data-page="${current_page - 1}">Previous</a></li>`);

        const maxPagesToShow = 5;
        let startPage, endPage;
        if (total_pages <= maxPagesToShow) {
            startPage = 1; endPage = total_pages;
        } else {
            const maxPagesBeforeCurrent = Math.floor(maxPagesToShow / 2);
            const maxPagesAfterCurrent = Math.ceil(maxPagesToShow / 2) - 1;
            if (current_page <= maxPagesBeforeCurrent) { startPage = 1; endPage = maxPagesToShow; } 
            else if (current_page + maxPagesAfterCurrent >= total_pages) { startPage = total_pages - maxPagesToShow + 1; endPage = total_pages; } 
            else { startPage = current_page - maxPagesBeforeCurrent; endPage = current_page + maxPagesAfterCurrent; }
        }

        if (startPage > 1) {
            paginationContainer.insertAdjacentHTML('beforeend', `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
            if (startPage > 2) paginationContainer.insertAdjacentHTML('beforeend', `<li class="page-item disabled"><span class="page-link">...</span></li>`);
        }
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === current_page ? 'active' : '';
            paginationContainer.insertAdjacentHTML('beforeend', `<li class="page-item ${activeClass}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`);
        }
        if (endPage < total_pages) {
            if (endPage < total_pages - 1) paginationContainer.insertAdjacentHTML('beforeend', `<li class="page-item disabled"><span class="page-link">...</span></li>`);
            paginationContainer.insertAdjacentHTML('beforeend', `<li class="page-item"><a class="page-link" href="#" data-page="${total_pages}">${total_pages}</a></li>`);
        }

        const nextDisabled = current_page === total_pages ? 'disabled' : '';
        paginationContainer.insertAdjacentHTML('beforeend', `<li class="page-item ${nextDisabled}"><a class="page-link" href="#" data-page="${current_page + 1}">Next</a></li>`);
    }

    paginationContainer.addEventListener('click', (e) => { e.preventDefault(); const pageLink = e.target.closest('.page-link'); if (pageLink && !pageLink.parentElement.classList.contains('disabled')) { const page = parseInt(pageLink.dataset.page, 10); if (page !== currentPage) loadLog(page); } });
    loadLog();
}

function initPengumumanPage() {
    const listContainer = document.getElementById('pengumuman-list');
    const isAdmin = (typeof userRole !== 'undefined' && userRole === 'admin');

    if (!listContainer) return;

    async function loadPengumuman() {
        listContainer.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';
        try {
            const response = await fetch(`${basePath}/api/pengumuman?action=list`);
            const result = await response.json();
            listContainer.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(p => {
                    const isScheduled = p.tanggal_terbit && new Date(p.tanggal_terbit) > new Date();
                    const publishDate = p.tanggal_terbit ? new Date(p.tanggal_terbit) : new Date(p.created_at);
                    const tglFormatted = publishDate.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                    
                    let statusBadge = '';
                    if (isScheduled && isAdmin) {
                        const scheduledTime = new Date(p.tanggal_terbit).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
                        statusBadge = `<span class="badge bg-info">Dijadwalkan: ${scheduledTime}</span>`;
                    }

                    const adminControls = isAdmin ? `
                        <div class="card-footer bg-transparent border-top-0 d-flex flex-wrap gap-2">
                            <button class="btn btn-sm btn-info edit-btn" data-id="${p.id}"><i class="bi bi-pencil-fill"></i> Edit</button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${p.id}" data-judul="${p.judul}"><i class="bi bi-trash-fill"></i> Hapus</button>
                        </div>` : '';

                    const card = `
                        <div class="col-12 mb-4" id="pengumuman-${p.id}">
                            <div class="card shadow-sm">
                                <div class="card-header bg-transparent border-0 d-flex justify-content-between">
                                    <small class="text-muted">Diposting oleh ${p.pembuat || 'Admin'} pada ${tglFormatted}</small>
                                    ${statusBadge}
                                </div>
                                <div class="card-body pt-0">
                                    <h5 class="card-title">${p.judul}</h5>
                                    <p class="card-text">${nl2br(p.isi_pengumuman)}</p>
                                </div>
                                ${adminControls}
                            </div>
                        </div>`;
                    listContainer.insertAdjacentHTML('beforeend', card);
                });
            } else {
                listContainer.innerHTML = '<div class="col-12"><div class="alert alert-info">Belum ada pengumuman.</div></div>';
            }
        } catch (error) {
            listContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger">Gagal memuat data pengumuman.</div></div>';
        }
    }

    if (isAdmin) {
        const modalEl = document.getElementById('pengumumanModal');
        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('pengumuman-form');
        const saveBtn = document.getElementById('save-pengumuman-btn');

        modalEl.addEventListener('show.bs.modal', async (event) => {
            const button = event.relatedTarget;
            const action = button.getAttribute('data-action');
            form.reset();
            document.getElementById('pengumuman-action').value = action;

            if (action === 'add') {
                document.getElementById('pengumumanModalLabel').textContent = 'Buat Pengumuman Baru';
                document.getElementById('pengumuman-id').value = '';
                document.getElementById('tanggal_terbit').value = '';
            } else { // edit
                document.getElementById('pengumumanModalLabel').textContent = 'Edit Pengumuman';
                const id = button.dataset.id;
                document.getElementById('pengumuman-id').value = id;
                
                const formData = new FormData();
                formData.append('action', 'get_single');
                formData.append('id', id);
                const response = await fetch(`${basePath}/api/pengumuman`, { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    document.getElementById('judul-pengumuman').value = result.data.judul;
                    document.getElementById('isi-pengumuman').value = result.data.isi_pengumuman;
                    document.getElementById('tanggal_terbit').value = result.data.tanggal_terbit ? result.data.tanggal_terbit.slice(0, 16) : '';
                }
            }
        });

        saveBtn.addEventListener('click', async () => {
            const formData = new FormData(form);
            
            const originalBtnHtml = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;

            try {
                const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                const fetchPromise = fetch(`${basePath}/api/pengumuman`, { method: 'POST', body: formData });

                const [response] = await Promise.all([fetchPromise, minDelay]);

                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    modal.hide();
                    loadPengumuman();
                    // Check if there's a WhatsApp URL in the response
                    if (result.whatsapp_url) {
                        if (confirm('Pengumuman berhasil dibuat. Kirim notifikasi via WhatsApp sekarang?')) {
                            window.open(result.whatsapp_url, '_blank');
                        }
                    }
                }
            } catch (error) {
                showToast('Terjadi kesalahan jaringan.', 'error');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnHtml;
            }
        });

        listContainer.addEventListener('click', async (e) => {
            const deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                const { id, judul } = deleteBtn.dataset;
                if (confirm(`Yakin ingin menghapus pengumuman "${judul}"?`)) {
                    const originalIcon = deleteBtn.innerHTML;
                    deleteBtn.disabled = true;
                    deleteBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
                    try {
                        const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('id', id);
                        const fetchPromise = fetch(`${basePath}/api/pengumuman`, { method: 'POST', body: formData });

                        const [response] = await Promise.all([fetchPromise, minDelay]);

                        const result = await response.json();
                        showToast(result.message, result.status === 'success' ? 'success' : 'error');
                        if (result.status === 'success') {
                            loadPengumuman();
                        } else {
                            deleteBtn.disabled = false;
                            deleteBtn.innerHTML = originalIcon;
                        }
                    } catch (error) {
                        showToast('Terjadi kesalahan jaringan.', 'error');
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = originalIcon;
                    }
                }
            }

            const editBtn = e.target.closest('.edit-btn');
            if (editBtn) {
                editBtn.setAttribute('data-bs-toggle', 'modal');
                editBtn.setAttribute('data-bs-target', '#pengumumanModal');
                editBtn.setAttribute('data-action', 'edit');
                new bootstrap.Modal(modalEl).show(editBtn);
            }
        });
    }

    function nl2br (str) {
        if (typeof str === 'undefined' || str === null) {
            return '';
        }
        return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
    }

    loadPengumuman();
}

function initLaporanKeuanganPage() {
    const yearFilter = document.getElementById('laporan-tahun-filter');
    const monthFilter = document.getElementById('laporan-bulan-filter');
    const categoryFilter = document.getElementById('laporan-kategori-filter');
    const printBtn = document.getElementById('cetak-laporan-keuangan-btn');
    const loadingSpinner = document.getElementById('laporan-loading-spinner');
    const monthlyCtx = document.getElementById('monthly-summary-chart');
    const expenseCtx = document.getElementById('expense-category-chart');
    const currencyFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    });

    async function loadMonthlySummaryDetails(selectedCategory = '') {
        const summaryContainer = document.getElementById('monthly-summary-details-container');
        if (!summaryContainer) return;

        const saldoAwalEl = document.getElementById('summary-saldo-awal');
        const pemasukanEl = document.getElementById('summary-pemasukan');
        const pengeluaranEl = document.getElementById('summary-pengeluaran');
        const saldoAkhirEl = document.getElementById('summary-saldo-akhir');

        const spinner = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';
        saldoAwalEl.innerHTML = spinner;
        pemasukanEl.innerHTML = spinner;
        pengeluaranEl.innerHTML = spinner;
        saldoAkhirEl.innerHTML = spinner;

        const selectedYear = yearFilter.value;
        const selectedMonth = monthFilter.value;

        try {
            const response = await fetch(`${basePath}/api/laporan-keuangan?action=get_monthly_summary_details&tahun=${selectedYear}&bulan=${selectedMonth}&kategori=${selectedCategory}`);
            const result = await response.json();

            if (result.status === 'success') {
                const { saldo_awal, total_pemasukan, total_pengeluaran, saldo_akhir } = result.data;
                saldoAwalEl.textContent = currencyFormatter.format(saldo_awal);
                pemasukanEl.textContent = currencyFormatter.format(total_pemasukan);
                pengeluaranEl.textContent = currencyFormatter.format(total_pengeluaran);
                saldoAkhirEl.textContent = currencyFormatter.format(saldo_akhir);
            } else { throw new Error(result.message); }
        } catch (error) {
            [saldoAwalEl, pemasukanEl, pengeluaranEl, saldoAkhirEl].forEach(el => el.textContent = 'Error');
        }
    }

    if (!yearFilter || !monthFilter || !categoryFilter || !monthlyCtx || !expenseCtx) return;

    // Populate year filter
    const currentYear = new Date().getFullYear();
    for (let i = 0; i < 5; i++) {
        const year = currentYear - i;
        yearFilter.add(new Option(year, year));
    }

    // Populate month filter
    const currentMonth = new Date().getMonth() + 1;
    const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    months.forEach((month, index) => {
        monthFilter.add(new Option(month, index + 1));
    });
    monthFilter.value = currentMonth;

    // Note: The charts on this page show an annual summary.
    // The print button will generate a monthly report based on the filter.

    async function loadKategoriFilter() {
        try {
            const response = await fetch(`${basePath}/api/kategori-kas`);
            const result = await response.json();
            if (result.status === 'success') {
                categoryFilter.innerHTML = '<option value="">Semua Kategori</option>'; // Reset
                const allCategories = [...result.data.masuk, ...result.data.keluar];
                // Sort and remove duplicates
                const uniqueCategories = [...new Map(allCategories.map(item => [item.nama_kategori, item])).values()]
                                         .sort((a, b) => a.nama_kategori.localeCompare(b.nama_kategori));

                uniqueCategories.forEach(cat => {
                    categoryFilter.add(new Option(cat.nama_kategori, cat.nama_kategori));
                });
            }
        } catch (error) {
            console.error("Gagal memuat filter kategori:", error);
        }
    }

    async function loadChartData(selectedCategory = '') {
        loadingSpinner.style.display = 'block';
        const selectedYear = yearFilter.value;

        try {
            const [monthlyRes, expenseRes] = await Promise.all([ // Fetch data for both charts
                fetch(`${basePath}/api/laporan-keuangan?action=monthly_summary&tahun=${selectedYear}&kategori=${selectedCategory}`),
                fetch(`${basePath}/api/laporan-keuangan?action=expense_categories&tahun=${selectedYear}`)
            ]);
            const monthlyData = await monthlyRes.json();
            const expenseData = await expenseRes.json();

            // Destroy old charts if they exist
            if (window.laporanKeuanganMonthlyChart) window.laporanKeuanganMonthlyChart.destroy();
            if (window.laporanKeuanganExpenseChart) window.laporanKeuanganExpenseChart.destroy();

            // Render Monthly Summary Chart (Bar)
            window.laporanKeuanganMonthlyChart = new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: monthlyData.data.labels,
                    datasets: [
                        { label: 'Pemasukan', data: monthlyData.data.pemasukan, backgroundColor: 'rgba(75, 192, 192, 0.6)' },
                        { label: 'Pengeluaran', data: monthlyData.data.pengeluaran, backgroundColor: 'rgba(255, 99, 132, 0.6)' }
                    ]
                }
            });

            // Render Expense Category Chart (Pie)
            window.laporanKeuanganExpenseChart = new Chart(expenseCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(expenseData.data),
                    datasets: [{ data: Object.values(expenseData.data) }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

        } catch (error) {
            console.error("Failed to load chart data:", error);
            showToast("Gagal memuat data laporan keuangan.", "error");
        } finally {
            loadingSpinner.style.display = 'none';
        }
    }

    function handleFilterChange() {
        const selectedCategory = categoryFilter.value;
        loadChartData(selectedCategory);
        loadMonthlySummaryDetails(selectedCategory);
    }

    yearFilter.addEventListener('change', () => {
        handleFilterChange();
    });
    yearFilter.dataset.listenerAdded = 'true'; // Mark as added

    monthFilter.addEventListener('change', () => loadMonthlySummaryDetails(categoryFilter.value));

    categoryFilter.addEventListener('change', handleFilterChange);

    if (printBtn) {
        printBtn.addEventListener('click', () => {
            const tahun = yearFilter.value;
            const bulan = monthFilter.value;
            const kategori = categoryFilter.value;
            const url = `${basePath}/laporan-keuangan/cetak?tahun=${tahun}&bulan=${bulan}&kategori=${encodeURIComponent(kategori)}`;
            window.open(url, '_blank');
        });
    }
    
    loadKategoriFilter().then(() => {
        handleFilterChange(); // Initial load for all data
    });
}

function initDokumenPage() {
    const tableBody = document.getElementById('dokumen-table-body');
    const isAdmin = (typeof userRole !== 'undefined' && userRole === 'admin');

    if (!tableBody) return;

    async function loadDokumen() {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center p-5"><div class="spinner-border"></div></td></tr>';
        try {
            const response = await fetch(`${basePath}/api/dokumen?action=list`);
            const result = await response.json();
            tableBody.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(d => {
                    const tgl = new Date(d.created_at);
                    const tglFormatted = tgl.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                    const adminControls = isAdmin ? `
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${d.id}" data-nama="${d.nama_dokumen}" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                    ` : '';

                    const row = `
                        <tr id="dokumen-${d.id}">
                            <td>
                                <a href="${basePath}/${d.path_file.replace(/\\/g, '/')}" target="_blank">
                                    <i class="bi bi-file-earmark-text-fill"></i> ${d.nama_dokumen}
                                </a>
                            </td>
                            <td><span class="badge bg-info">${d.kategori}</span></td>
                            <td>${d.deskripsi || '-'}</td>
                            <td>${tglFormatted}</td>
                            <td>${d.pengunggah || 'N/A'}</td>
                            <td class="text-end">
                                <a href="${basePath}/${d.path_file.replace(/\\/g, '/')}" target="_blank" class="btn btn-sm btn-success" title="Unduh"><i class="bi bi-download"></i></a>
                                ${adminControls}
                            </td>
                        </tr>`;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Belum ada dokumen yang diunggah.</td></tr>';
            }
        } catch (error) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Gagal memuat data dokumen.</div></td></tr>';
        }
    }

    if (isAdmin) {
        const modalEl = document.getElementById('dokumenModal');
        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('dokumen-form');
        const saveBtn = document.getElementById('save-dokumen-btn');

        modalEl.addEventListener('hidden.bs.modal', () => {
            form.reset();
        });

        saveBtn.addEventListener('click', async () => {
            const formData = new FormData(form);
            if (!formData.get('nama_dokumen') || !formData.get('file_dokumen').name) {
                showToast('Nama dokumen dan file wajib diisi.', 'error');
                return;
            }

            const originalBtnHtml = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengunggah...`;

            try {
                const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                const fetchPromise = fetch(`${basePath}/api/dokumen`, { method: 'POST', body: formData });
                const [response] = await Promise.all([fetchPromise, minDelay]);
                
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    modal.hide();
                    loadDokumen();
                }
            } catch (error) {
                showToast('Terjadi kesalahan jaringan.', 'error');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnHtml;
            }
        });

        tableBody.addEventListener('click', async (e) => {
            const deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                const { id, nama } = deleteBtn.dataset;
                if (confirm(`Yakin ingin menghapus dokumen "${nama}"? Aksi ini tidak dapat dibatalkan.`)) {
                    const originalIcon = deleteBtn.innerHTML;
                    deleteBtn.disabled = true;
                    deleteBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
                    try {
                        const minDelay = new Promise(resolve => setTimeout(resolve, 500));
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('id', id);
                        const fetchPromise = fetch(`${basePath}/api/dokumen`, { method: 'POST', body: formData });
                        const [response] = await Promise.all([fetchPromise, minDelay]);

                        const result = await response.json();
                        showToast(result.message, result.status === 'success' ? 'success' : 'error');
                        if (result.status === 'success') {
                            loadDokumen();
                        } else {
                            deleteBtn.disabled = false;
                            deleteBtn.innerHTML = originalIcon;
                        }
                    } catch (error) {
                        showToast('Terjadi kesalahan jaringan.', 'error');
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = originalIcon;
                    }
                }
            }
        });
    }

    loadDokumen();
}

function initPollingPage() {
    const listContainer = document.getElementById('polling-list');
    const isAdmin = (typeof userRole !== 'undefined' && userRole === 'admin');

    if (!listContainer) return;

    function renderPoll(poll) {
        const tgl = new Date(poll.created_at);
        const tglFormatted = tgl.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        const hasVoted = poll.user_vote !== null;
        const isClosed = poll.status === 'closed';

        let optionsHtml = '';
        if (!hasVoted && !isClosed) {
            // Voting View
            poll.options.forEach((option, index) => {
                optionsHtml += `<button class="btn btn-outline-primary d-block w-100 mb-2 vote-btn" data-poll-id="${poll.id}" data-option-index="${index}">${option}</button>`;
            });
        } else {
            // Results View
            poll.options.forEach((option, index) => {
                const voteCount = poll.results[index] || 0;
                const percentage = poll.total_votes > 0 ? ((voteCount / poll.total_votes) * 100).toFixed(1) : 0;
                const isUserChoice = poll.user_vote == index;
                optionsHtml += `
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span>${option} ${isUserChoice ? '<i class="bi bi-check-circle-fill text-success"></i>' : ''}</span>
                            <span class="fw-bold">${voteCount} suara (${percentage}%)</span>
                        </div>
                        <div class="progress" role="progressbar" style="height: 20px;">
                            <div class="progress-bar" style="width: ${percentage}%">${percentage > 10 ? `${percentage}%` : ''}</div>
                        </div>
                    </div>
                `;
            });
        }

        const adminControls = isAdmin ? `
            <div class="card-footer d-flex justify-content-end gap-2">
                ${isClosed ? 
                    `<button class="btn btn-sm btn-success open-poll-btn" data-poll-id="${poll.id}">Buka Kembali</button>` : 
                    `<button class="btn btn-sm btn-warning close-poll-btn" data-poll-id="${poll.id}">Tutup Polling</button>`
                }
                <button class="btn btn-sm btn-danger delete-poll-btn" data-poll-id="${poll.id}" data-question="${poll.question}">Hapus</button>
            </div>` : '';

        return `
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">${poll.question}</h5>
                        <span class="badge bg-${isClosed ? 'secondary' : 'success'}">${isClosed ? 'Ditutup' : 'Aktif'}</span>
                    </div>
                    <div class="card-body">
                        ${optionsHtml}
                    </div>
                    <div class="card-footer text-muted small">
                        Dibuat oleh ${poll.creator || 'Admin'} pada ${tglFormatted} &bull; Total ${poll.total_votes} suara
                    </div>
                    ${adminControls}
                </div>
            </div>
        `;
    }

    async function loadPolling() {
        listContainer.innerHTML = '<div class="text-center p-5"><div class="spinner-border"></div></div>';
        try {
            const response = await fetch(`${basePath}/api/polling?action=list`);
            const result = await response.json();
            listContainer.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(poll => {
                    listContainer.insertAdjacentHTML('beforeend', renderPoll(poll));
                });
            } else {
                listContainer.innerHTML = '<div class="col-12"><div class="alert alert-info">Belum ada jajak pendapat.</div></div>';
            }
        } catch (error) {
            listContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger">Gagal memuat data.</div></div>';
        }
    }

    listContainer.addEventListener('click', async (e) => {
        const voteBtn = e.target.closest('.vote-btn');
        if (voteBtn) {
            const pollId = voteBtn.dataset.pollId;
            const optionIndex = voteBtn.dataset.optionIndex;
            
            const formData = new FormData();
            formData.append('action', 'vote');
            formData.append('polling_id', pollId);
            formData.append('selected_option', optionIndex);

            try {
                const response = await fetch(`${basePath}/api/polling`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    loadPolling(); // Reload all polls to show results
                }
            } catch (error) {
                showToast('Terjadi kesalahan jaringan.', 'error');
            }
        }

        const adminBtn = e.target.closest('.open-poll-btn, .close-poll-btn, .delete-poll-btn');
        if (isAdmin && adminBtn) {
            const pollId = adminBtn.dataset.pollId;
            let action, status, confirmMsg;

            if (adminBtn.classList.contains('open-poll-btn')) {
                action = 'update_status'; status = 'open'; confirmMsg = 'Yakin ingin membuka kembali polling ini?';
            } else if (adminBtn.classList.contains('close-poll-btn')) {
                action = 'update_status'; status = 'closed'; confirmMsg = 'Yakin ingin menutup polling ini?';
            } else { // delete
                action = 'delete'; confirmMsg = `Yakin ingin menghapus polling "${adminBtn.dataset.question}"? Aksi ini tidak dapat dibatalkan.`;
            }

            if (confirm(confirmMsg)) {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('polling_id', pollId);
                if (status) formData.append('status', status);

                const response = await fetch(`${basePath}/api/polling`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') loadPolling();
            }
        }
    });

    if (isAdmin) {
        const modalEl = document.getElementById('pollingModal');
        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('polling-form');
        const saveBtn = document.getElementById('save-polling-btn');
        const optionsContainer = document.getElementById('polling-options-container');

        document.getElementById('add-option-btn').addEventListener('click', () => {
            const newOption = `<div class="input-group mb-2"><input type="text" class="form-control" name="options[]" required><button class="btn btn-outline-danger remove-option-btn" type="button"><i class="bi bi-trash"></i></button></div>`;
            optionsContainer.insertAdjacentHTML('beforeend', newOption);
        });

        optionsContainer.addEventListener('click', (e) => {
            if (e.target.closest('.remove-option-btn')) {
                e.target.closest('.input-group').remove();
            }
        });

        saveBtn.addEventListener('click', async () => {
            const formData = new FormData(form);
            const response = await fetch(`${basePath}/api/polling`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                modal.hide();
                loadPolling();
            }
        });
    }

    loadPolling();
}

function initBookingPage() {
    const calendarEl = document.getElementById('booking-calendar');
    if (!calendarEl) return;

    const modalEl = document.getElementById('bookingModal');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('booking-form');
    const saveBtn = document.getElementById('save-booking-btn');
    const deleteBtn = document.getElementById('delete-booking-btn');
    const approveBtn = document.getElementById('approve-booking-btn');
    const rejectBtn = document.getElementById('reject-booking-btn');
    const fasilitasSelect = document.getElementById('fasilitas_id');

    let calendar;
    let currentWargaId = null; // Will be fetched later

    // Get current user's warga_id
    async function getCurrentWargaId() {
        // This is a simplified way. A dedicated API endpoint would be better.
        // We assume username is NIK.
        try {
            const response = await fetch(`${basePath}/api/warga?action=list&search=${encodeURIComponent(userRole === 'admin' ? '' : 'dummy_nik_to_get_id')}`);
            // This is a placeholder. A real implementation needs a dedicated endpoint to get user's warga_id.
            // For now, we'll just use the admin role to determine delete rights.
        } catch(e) { /* ignore */ }
    }

    // Load fasilitas list into dropdown
    async function loadFasilitasList() {
        try {
            const response = await fetch(`${basePath}/api/booking?action=list_fasilitas`);
            const result = await response.json();
            if (result.status === 'success') {
                fasilitasSelect.innerHTML = '<option value="">-- Pilih Fasilitas --</option>';
                result.data.forEach(f => {
                    fasilitasSelect.add(new Option(f.nama_fasilitas, f.id));
                });
            }
        } catch (error) {
            console.error("Gagal memuat daftar fasilitas:", error);
        }
    }

    function showFormView() {
        document.getElementById('booking-info-view').classList.add('d-none');
        document.getElementById('booking-form-fields').classList.remove('d-none');
        document.getElementById('admin-actions').classList.add('d-none');
        saveBtn.classList.remove('d-none');
        deleteBtn.classList.add('d-none');
    }

    function showInfoView(event) {
        document.getElementById('booking-info-view').classList.remove('d-none');
        document.getElementById('booking-form-fields').classList.add('d-none');
        saveBtn.classList.add('d-none');

        document.getElementById('view-fasilitas').textContent = event.extendedProps.fasilitas;
        document.getElementById('view-judul').textContent = event.extendedProps.judul;
        document.getElementById('view-pemesan').textContent = event.extendedProps.pemesan;
        const start = new Date(event.startStr).toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'short' });
        const end = new Date(event.endStr).toLocaleString('id-ID', { dateStyle: 'full', timeStyle: 'short' });
        document.getElementById('view-waktu').textContent = `${start} - ${end}`;
        document.getElementById('view-status').innerHTML = `<span class="badge bg-primary text-capitalize">${event.extendedProps.status}</span>`;

        // Admin actions
        if (userRole === 'admin' && event.extendedProps.status === 'pending') {
            document.getElementById('admin-actions').classList.remove('d-none');
            document.getElementById('booking-id').value = event.id;
        } else {
            document.getElementById('admin-actions').classList.add('d-none');
        }
        
        // Delete button logic (for admin or owner)
        if (userRole === 'admin') { // Simplified: only admin can delete
            deleteBtn.classList.remove('d-none');
            document.getElementById('booking-id').value = event.id;
        } else {
            deleteBtn.classList.add('d-none');
        }
    }

    calendar = new FullCalendar.Calendar(calendarEl, {
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        initialView: 'dayGridMonth',
        locale: 'id',
        events: `${basePath}/api/booking?action=list_events`,
        selectable: true,
        dateClick: function(info) {
            showFormView();
            form.reset();
            document.getElementById('bookingModalLabel').textContent = 'Buat Booking Baru';
            document.getElementById('booking-action').value = 'create';
            document.getElementById('tanggal_mulai').value = info.dateStr + 'T09:00';
            document.getElementById('tanggal_selesai').value = info.dateStr + 'T11:00';
            modal.show();
        },
        eventClick: function(info) {
            showInfoView(info.event);
            document.getElementById('bookingModalLabel').textContent = 'Detail Booking';
            modal.show();
        }
    });

    calendar.render();
    loadFasilitasList();

    saveBtn.addEventListener('click', async () => {
        const formData = new FormData(form);
        const originalBtnHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Mengajukan...`;
        try {
            const minDelay = new Promise(resolve => setTimeout(resolve, 500));
            const fetchPromise = fetch(`${basePath}/api/booking`, { method: 'POST', body: formData });
            const [response] = await Promise.all([fetchPromise, minDelay]);
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                modal.hide();
                calendar.refetchEvents();
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalBtnHtml;
        }
    });
    
    async function handleAdminAction(status) {
        const bookingId = document.getElementById('booking-id').value;
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('booking_id', bookingId);
        formData.append('status', status);
        
        try {
            const response = await fetch(`${basePath}/api/booking`, { method: 'POST', body: formData });
            const result = await response.json();
            showToast(result.message, result.status === 'success' ? 'success' : 'error');
            if (result.status === 'success') {
                modal.hide();
                calendar.refetchEvents();
            }
        } catch (error) {
            showToast('Terjadi kesalahan jaringan.', 'error');
        }
    }

    approveBtn.addEventListener('click', () => handleAdminAction('approved'));
    rejectBtn.addEventListener('click', () => handleAdminAction('rejected'));
    
    deleteBtn.addEventListener('click', async () => {
        if (confirm('Apakah Anda yakin ingin menghapus booking ini?')) {
            const bookingId = document.getElementById('booking-id').value;
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('booking_id', bookingId);
            
            try {
                const response = await fetch(`${basePath}/api/booking`, { method: 'POST', body: formData });
                const result = await response.json();
                showToast(result.message, result.status === 'success' ? 'success' : 'error');
                if (result.status === 'success') {
                    modal.hide();
                    calendar.refetchEvents();
                }
            } catch (error) {
                showToast('Terjadi kesalahan jaringan.', 'error');
            }
        }
    });
}

/**
 * Initializes notification polling and UI updates.
 */
function initNotificationPolling() {
    let lastKnownUnreadCount = -1; // Use -1 to indicate it's not yet initialized

    const poll = async () => {
        try {
            const response = await fetch(`${basePath}/api/notifications?action=list`);
            if (!response.ok) return; // Don't show error for failed polls

            const result = await response.json();
            if (result.status === 'success') {
                const newUnreadCount = result.data.unread_count;

                // If this is not the first poll and the count has increased, show a toast.
                if (lastKnownUnreadCount !== -1 && newUnreadCount > lastKnownUnreadCount) {
                    const latestNotification = result.data.notifications[0];
                    showToast(latestNotification.message, 'info');
                }

                // Update UI and set the count for the next poll.
                updateNotificationUI(result.data);
                lastKnownUnreadCount = newUnreadCount;
            }
        } catch (error) {
            // Silently fail, don't spam console or user
            // console.error("Polling error:", error);
        }
    };

    poll(); // Initial poll
    setInterval(poll, notificationInterval); // Use interval from settings
}

/**
 * Updates the notification badge and dropdown list.
 * @param {object} data The notification data from the API.
 */
function updateNotificationUI(data) {
    const badge = document.getElementById('notification-count-badge');
    const list = document.getElementById('notification-dropdown-list');

    if (!badge || !list) return;

    const unreadCount = data.unread_count;
    const notifications = data.notifications;

    // Update badge
    if (unreadCount > 0) {
        badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
        badge.style.display = 'block';
    } else {
        badge.style.display = 'none';
    }

    // Update dropdown list
    list.innerHTML = ''; // Clear existing items
    if (notifications.length > 0) {
        notifications.forEach(notif => {
            const timeAgo = timeSince(new Date(notif.created_at));
            const isUnreadClass = notif.is_read == 0 ? 'fw-bold' : '';
            const itemHTML = `
                <li>
                    <a class="dropdown-item text-wrap ${isUnreadClass}" href="${basePath}${notif.link}">
                        <div class="small">${notif.message}</div>
                        <div class="text-muted small">${timeAgo}</div>
                    </a>
                </li>
            `;
            list.insertAdjacentHTML('beforeend', itemHTML);
        });
    } else {
        list.innerHTML = '<li><p class="dropdown-item text-muted text-center small mb-0">Tidak ada notifikasi.</p></li>';
    }
}

/**
 * Calculates time since a given date.
 * @param {Date} date The date to compare against.
 * @returns {string} A human-readable string like "5 menit lalu".
 */
function timeSince(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + " tahun lalu";
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + " bulan lalu";
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + " hari lalu";
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + " jam lalu";
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + " menit lalu";
    return "Baru saja";
}

// =================================================================================
// GLOBAL INITIALIZATION
// =================================================================================

document.addEventListener('DOMContentLoaded', function () {
    // --- Sidebar Toggle Logic ---
    const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');

    const toggleSidebar = () => {
        document.body.classList.toggle('sidebar-collapsed');
        // Save the state to localStorage
        const isCollapsed = document.body.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebar-collapsed', isCollapsed);
    };

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', toggleSidebar);
    }

    if (sidebarOverlay) {
        // Di layar kecil, klik pada overlay akan menutup sidebar
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // --- Notification Polling ---
    const notificationDropdownEl = document.getElementById('notificationDropdown');
    if (notificationDropdownEl) {
        initNotificationPolling();

        notificationDropdownEl.addEventListener('show.bs.dropdown', async () => {
            const badge = document.getElementById('notification-count-badge');
            if (badge && parseInt(badge.textContent, 10) > 0) {
                // Mark as read on the server
                const formData = new FormData();
                formData.append('action', 'mark_all_read');
                try {
                    const response = await fetch(`${basePath}/api/notifications`, { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.status === 'success') {
                        // Visually reset the badge immediately after a short delay
                        setTimeout(() => { badge.style.display = 'none'; }, 500);
                    }
                } catch (error) {
                    console.error('Failed to mark notifications as read:', error);
                }
            }
        });
    }

    // --- Theme Switcher ---
    const themeSwitcher = document.getElementById('theme-switcher');
    if (themeSwitcher) {
        const themeIcon = themeSwitcher.querySelector('i');
        const themeText = document.getElementById('theme-switcher-text');

        // Function to set the switcher state
        const setSwitcherState = (theme) => {
            if (theme === 'dark') {
                themeIcon.classList.replace('bi-moon-stars-fill', 'bi-sun-fill');
                themeText.textContent = 'Mode Terang';
            } else {
                themeIcon.classList.replace('bi-sun-fill', 'bi-moon-stars-fill');
                themeText.textContent = 'Mode Gelap';
            }
        };

        // Set initial state based on what's already applied to the body
        const currentTheme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
        setSwitcherState(currentTheme);

        themeSwitcher.addEventListener('click', (e) => {
            e.preventDefault();
            const newTheme = document.body.classList.toggle('dark-mode') ? 'dark' : 'light';
            localStorage.setItem('theme', newTheme);
            setSwitcherState(newTheme);
        });
    }

    // --- Panic Button Logic ---
    const panicButton = document.getElementById('panic-button');
    if (panicButton) {
        let holdTimeout;
        const originalButtonHtml = panicButton.innerHTML;

        const startHold = (e) => {
            e.preventDefault();
            // Prevent action if button is already processing
            if (panicButton.disabled) return;

            panicButton.classList.add('is-holding');

            holdTimeout = setTimeout(async () => {
                panicButton.disabled = true;
                panicButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...`;

                try {
                    const response = await fetch(`${basePath}/api/panic`, { method: 'POST' });
                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.message || 'Server error');
                    }

                    showToast(result.message, 'success');
                    panicButton.classList.remove('btn-danger');
                    panicButton.classList.add('btn-success');
                    panicButton.innerHTML = `<i class="bi bi-check-circle-fill"></i> Terkirim`;

                } catch (error) {
                    // Use error.message if available from the thrown error
                    showToast(error.message || 'Gagal mengirim sinyal darurat.', 'error');
                    panicButton.innerHTML = `<i class="bi bi-x-circle-fill"></i> Gagal`;
                } finally {
                    // Reset button to original state after a few seconds
                    setTimeout(() => {
                        panicButton.classList.remove('is-holding', 'btn-success');
                        panicButton.classList.add('btn-danger');
                        panicButton.innerHTML = originalButtonHtml;
                        panicButton.disabled = false;
                    }, 5000); // Reset after 5 seconds
                }
            }, 3000); // 3 seconds
        };

        const cancelHold = () => {
            if (panicButton.disabled) return;
            clearTimeout(holdTimeout);
            panicButton.classList.remove('is-holding');
        };

        panicButton.addEventListener('mousedown', startHold);
        panicButton.addEventListener('touchstart', startHold, { passive: false });
        panicButton.addEventListener('mouseup', cancelHold);
        panicButton.addEventListener('mouseleave', cancelHold);
        panicButton.addEventListener('touchend', cancelHold);
    }

    // --- Live Clock in Header ---
    const clockElement = document.getElementById('live-clock');
    if (clockElement) {
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        function updateLiveClock() {
            const now = new Date();
            const dayName = days[now.getDay()];
            const day = now.getDate().toString().padStart(2, '0');
            const monthName = months[now.getMonth()];
            const year = now.getFullYear();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');

            clockElement.textContent = `${dayName}, ${day} ${monthName} ${year} ${hours}:${minutes}:${seconds}`;
        }

        updateLiveClock(); // Initial call
        setInterval(updateLiveClock, 1000); // Update every second
    }

    // --- SPA Navigation Listeners ---
    // Intercept clicks on internal links
    document.body.addEventListener('click', e => {
        const link = e.target.closest('a');
        // Check if it's an internal, navigable link that doesn't open a new tab, trigger a modal/dropdown, or has the 'data-spa-ignore' attribute
        if (link && link.href && link.target !== '_blank' && new URL(link.href).origin === window.location.origin && !link.getAttribute('data-bs-toggle') && link.getAttribute('data-spa-ignore') === null) {
            e.preventDefault();
            if (new URL(link.href).pathname !== window.location.pathname) {
                navigate(link.href);
            }
        }
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', e => {
        if (e.state && e.state.path) {
            navigate(e.state.path, false); // false = don't push a new state
        }
    });

    // --- Initial Page Load ---
    updateActiveSidebarLink(window.location.pathname);
    runPageScripts(window.location.pathname);
});

/**
 * Initializes the global search functionality.
 */
function initGlobalSearch() {
    const searchModalEl = document.getElementById('globalSearchModal');
    if (!searchModalEl) return;

    const searchInput = document.getElementById('global-search-input');
    const resultsContainer = document.getElementById('global-search-results');
    const spinner = document.getElementById('global-search-spinner');
    const searchModal = new bootstrap.Modal(searchModalEl);

    let debounceTimer;

    const performSearch = async () => {
        const term = searchInput.value.trim();

        if (term.length < 3) {
            resultsContainer.innerHTML = '<p class="text-muted text-center">Masukkan minimal 3 karakter untuk mencari.</p>';
            spinner.style.display = 'none';
            return;
        }

        spinner.style.display = 'block';

        try {
            const response = await fetch(`${basePath}/api/global-search?term=${encodeURIComponent(term)}`);
            const result = await response.json();

            resultsContainer.innerHTML = '';
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(item => {
                    const resultItem = `
                        <a href="${basePath}${item.link}" class="search-result-item" data-bs-dismiss="modal">
                            <div class="d-flex align-items-center">
                                <i class="bi ${item.icon} fs-4 me-3 text-primary"></i>
                                <div>
                                    <div class="fw-bold">${item.title}</div>
                                    <small class="text-muted">${item.subtitle}</small>
                                </div>
                                <span class="badge bg-secondary ms-auto">${item.type}</span>
                            </div>
                        </a>
                    `;
                    resultsContainer.insertAdjacentHTML('beforeend', resultItem);
                });
            } else if (result.status === 'success') {
                resultsContainer.innerHTML = `<p class="text-muted text-center">Tidak ada hasil ditemukan untuk "<strong>${term}</strong>".</p>`;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            resultsContainer.innerHTML = `<p class="text-danger text-center">Terjadi kesalahan: ${error.message}</p>`;
        } finally {
            spinner.style.display = 'none';
        }
    };

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        spinner.style.display = 'block';
        debounceTimer = setTimeout(performSearch, 500); // Debounce for 500ms
    });

    searchModalEl.addEventListener('shown.bs.modal', () => {
        searchInput.focus();
    });

    searchModalEl.addEventListener('hidden.bs.modal', () => {
        searchInput.value = '';
        resultsContainer.innerHTML = '<p class="text-muted text-center">Masukkan kata kunci untuk memulai pencarian.</p>';
    });

    // Add keyboard shortcut (Ctrl+K or Cmd+K)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault(); // Prevent default browser action (e.g., search)
            searchModal.show();
        }
    });
}
/**
 * Renders pagination controls.
 * @param {HTMLElement} container The container element for the pagination.
 * @param {object|null} pagination The pagination object from the API.
 * @param {function} onPageClick The callback function to execute when a page link is clicked.
 */
function renderPagination(container, pagination, onPageClick) {
    if (!container) return;
    container.innerHTML = '';
    if (!pagination || pagination.total_pages <= 1) return;

    const { current_page, total_pages } = pagination;

    const createPageItem = (page, text, isDisabled = false, isActive = false) => {
        const li = document.createElement('li');
        li.className = `page-item ${isDisabled ? 'disabled' : ''} ${isActive ? 'active' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.dataset.page = page;
        a.innerHTML = text;
        li.appendChild(a);
        return li;
    };

    container.appendChild(createPageItem(current_page - 1, 'Previous', current_page === 1));

    const maxPagesToShow = 5;
    let startPage, endPage;
    if (total_pages <= maxPagesToShow) {
        startPage = 1; endPage = total_pages;
    } else {
        const maxPagesBeforeCurrent = Math.floor(maxPagesToShow / 2);
        const maxPagesAfterCurrent = Math.ceil(maxPagesToShow / 2) - 1;
        if (current_page <= maxPagesBeforeCurrent) { startPage = 1; endPage = maxPagesToShow; } 
        else if (current_page + maxPagesAfterCurrent >= total_pages) { startPage = total_pages - maxPagesToShow + 1; endPage = total_pages; } 
        else { startPage = current_page - maxPagesBeforeCurrent; endPage = current_page + maxPagesAfterCurrent; }
    }

    if (startPage > 1) {
        container.appendChild(createPageItem(1, '1'));
        if (startPage > 2) container.appendChild(createPageItem(0, '...', true));
    }

    for (let i = startPage; i <= endPage; i++) {
        container.appendChild(createPageItem(i, i, false, i === current_page));
    }

    if (endPage < total_pages) {
        if (endPage < total_pages - 1) container.appendChild(createPageItem(0, '...', true));
        container.appendChild(createPageItem(total_pages, total_pages));
    }

    container.appendChild(createPageItem(current_page + 1, 'Next', current_page === total_pages));

    container.addEventListener('click', (e) => {
        e.preventDefault();
        const pageLink = e.target.closest('.page-link');
        if (pageLink && !pageLink.parentElement.classList.contains('disabled')) {
            const page = parseInt(pageLink.dataset.page, 10);
            if (page !== current_page) {
                onPageClick(page);
            }
        }
    });
}

// Initialize global search on every page load
initGlobalSearch();