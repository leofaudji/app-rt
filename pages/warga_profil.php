<?php
$is_spa_request = isset($_SERVER['HTTP_X_SPA_REQUEST']) && $_SERVER['HTTP_X_SPA_REQUEST'] === 'true';
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/header.php';
}

// The router captures the ID, but we need to get it from the URL path for SPA.
// Example URL: /app-rt/warga/profil/1
$path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$warga_id = end($path_parts);

if (!is_numeric($warga_id)) {
    echo '<div class="alert alert-danger m-3">ID Warga tidak valid.</div>';
    if (!$is_spa_request) {
        require_once PROJECT_ROOT . '/views/footer.php';
    }
    return;
}
?>

<div id="warga-profile-container" data-warga-id="<?= htmlspecialchars($warga_id) ?>">
    <!-- Profile content will be loaded here by JavaScript -->
    <div class="text-center p-5"><div class="spinner-border" style="width: 3rem; height: 3rem;" role="status"><span class="visually-hidden">Memuat profil...</span></div></div>
</div>

<?php
if (!$is_spa_request) {
    require_once PROJECT_ROOT . '/views/footer.php';
}
?>