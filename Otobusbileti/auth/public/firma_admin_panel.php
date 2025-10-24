<?php

require_once '../src/bootstrap.php'; 


require_auth(['Firma Admin', 'Admin'], 'login.php');

$isAdmin = check_role('Admin'); 

$FirmaID =($_SESSION['company_id'] ?? null); 

if (empty($FirmaID)) {
    
    http_response_code(403);
    die("<h1>Hata (403)</h1><p>Firma yöneticisinin atanmış bir firma kaydı bulunamadı.</p>");
}

$page_title = "Firma Yönetim Paneli";
?>
<?php require_once '../src/inc/header.php'; ?>

<div class="main-content">
    <h1><?= $page_title ?></h1>
    
    <?php if ($isAdmin): ?>
        <p class="admin-note">Sistem Yöneticisi olarak tüm seferleri ve firmaları yönetebilirsiniz.</p>
    <?php endif; ?>
    
    <?php require_once '../panel_modules/sefer_crud.php'; ?>
    
    <?php require_once '../panel_modules/kupon_yonetimi.php'; ?>
    
</div>

<?php require_once '../src/inc/footer.php'; ?>