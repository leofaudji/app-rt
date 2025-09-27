<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<div class="alert alert-danger m-3">Akses ditolak. Anda harus menjadi Admin untuk melihat halaman ini.</div>';
    if (!$is_spa_request) {
        require_once PROJECT_ROOT . '/views/footer.php';
    }
    return; // Stop rendering
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-gear-fill"></i> Pengaturan Aplikasi</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?= base_url('/manajemen') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Manajemen
        </a>
    </div>
</div>

<ul class="nav nav-tabs" id="settingsTab" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="general-settings-tab" data-bs-toggle="tab" data-bs-target="#general-settings" type="button" role="tab">Pengaturan Umum</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="surat-template-tab" data-bs-toggle="tab" data-bs-target="#surat-template-settings" type="button" role="tab">Template Surat</button>
  </li>
</ul>

<div class="tab-content" id="settingsTabContent">
    <!-- Tab Pengaturan Umum -->
    <div class="tab-pane fade show active" id="general-settings" role="tabpanel">
        <div class="card card-tab">
            <div class="card-body">
                <form id="settings-form" enctype="multipart/form-data">
                    <div id="settings-container">
                        <div class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Memuat...</span></div></div>
                    </div>
                    <hr>
                    <button type="button" class="btn btn-primary" id="save-settings-btn">
                        <i class="bi bi-save-fill"></i> Simpan Pengaturan Umum
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tab Template Surat -->
    <div class="tab-pane fade" id="surat-template-settings" role="tabpanel">
        <div class="card card-tab">
            <div class="card-header d-flex justify-content-between align-items-center">
                Daftar Template Surat Pengantar
                <button class="btn btn-sm btn-primary" id="add-template-btn"><i class="bi bi-plus-circle"></i> Tambah Template</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama Template</th>
                                <th>Judul di Surat</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="surat-templates-table-body">
                            <!-- Data akan dimuat di sini -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Template Surat -->
<div class="modal fade" id="suratTemplateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="suratTemplateModalLabel">Tambah Template Surat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="surat-template-form">
            <!-- Form fields will be populated by JS -->
        </form>
        <div id="template-placeholders-info" class="mt-3 alert alert-info small"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="save-template-btn">Simpan Template</button>
      </div>
    </div>
  </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>