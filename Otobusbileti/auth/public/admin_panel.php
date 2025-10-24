<?php


require_once '../src/bootstrap.php'; 
require_auth('Admin', 'login.php');

$module = $_GET['module'] ?? 'dashboard'; 

$page_title = "Admin Paneli";
switch ($module) {
    case 'firma_crud': $page_title .= " - Firma Yönetimi"; break;
    case 'firma_admins': $page_title .= " - Firma Admin Atama"; break;
    default: $page_title .= " - Özet"; break;
}
?>
<?php require_once '../src/inc/header.php'; ?>

<div class="admin-panel-container"> 
    <h1><?= $page_title ?></h1>

    <nav class="admin-module-tabs">
        <a href="admin_panel.php?module=dashboard" 
           class="<?= ($module === 'dashboard') ? 'active' : '' ?>">Panel Özeti</a>
           
        <a href="admin_panel.php?module=firma_crud" 
           class="<?= ($module === 'firma_crud') ? 'active' : '' ?>">Firma Ekle/Yönet </a>
           
        <a href="admin_panel.php?module=firma_admins" 
           class="<?= ($module === 'firma_admins') ? 'active' : '' ?>">Firma Admin Ata/Yönet</a>
    </nav>
    
    <div class="row">
        <div class="col-md-12"> 
            
            <?php 
            switch ($module) {
                case 'firma_crud':
                    require_once '../panel_modules/firma_crud.php';
                    break;
                case 'firma_admins':
                    require_once '../panel_modules/firma_admins.php';
                    break;
                case 'dashboard':
                default:
                    echo '<h2>Admin Paneli Özeti</h2>';
                    echo '<div class="alert alert-success" style="background-color:#E6E6FA; color:#5D3FD3; padding:15px; border-radius:5px; border: 1px solid #C3B1E1;">Yönetim panelinize hoş geldiniz. Lütfen yukarıdaki sekmelerden bir işlem seçin.</div>';
                    break;
            } 
            ?>
        </div> 
    </div> 
</div> 

<?php require_once '../src/inc/footer.php'; ?>