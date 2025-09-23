<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="bi bi-person-badge-fill"></i> Edit Profil Saya</h1>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                Data Diri
            </div>
            <div class="card-body">
                <form id="edit-profile-form">
                    <div id="profile-fields-container">
                        <div class="text-center p-5"><div class="spinner-border"></div></div>
                    </div>
                    <hr>
                    <button type="button" class="btn btn-primary" id="save-profile-btn">
                        <i class="bi bi-save-fill"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>