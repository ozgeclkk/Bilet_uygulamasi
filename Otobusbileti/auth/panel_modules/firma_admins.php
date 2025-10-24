<?php


if (!check_role('Admin')) {
    echo '<div class="alert alert-danger">Bu bölüme erişim yetkiniz yok.</div>';
    return; 
}

$assign_message = ''; 



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_firma_admin') {
    
    
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? ''); 
    $company_id_to_assign = trim($_POST['company_id_to_assign'] ?? '');

    
    if (empty($full_name) || empty($email) || empty($password) || empty($company_id_to_assign)) {
        $assign_message = '<div class="alert alert-danger">Hata: Lütfen tüm alanları (İsim, E-posta, Şifre, Firma) doldurun.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $assign_message = '<div class="alert alert-danger">Hata: Geçersiz e-posta formatı.</div>';
    } else {
        
        try {

            $success = register_user($email, $full_name, $password, 'Firma Admin', $company_id_to_assign);

            if ($success) {
                $assign_message = '<div class="alert alert-success">Yeni Firma Admin ('.htmlspecialchars($full_name).') başarıyla oluşturuldu ve firmaya atandı.</div>';
                
            } else {
                $assign_message = '<div class="alert alert-danger">Firma Admin oluşturulamadı. E-posta adresi zaten kullanılıyor olabilir.</div>';
            }
        } catch (Exception $e) {
            
            $assign_message = '<div class="alert alert-danger">Bir hata oluştu: ' . $e->getMessage() . '</div>';
        }
    }
}


$companies_list = db_select("SELECT id, name FROM Bus_Company ORDER BY name ASC");

?>

<div class="firma-admin-olusturma" style="margin-top: 30px;">
    <h3 style="color:#5D3FD3;">Yeni Firma Admin Oluştur ve Ata</h3>
    <p>Yeni bir kullanıcı hesabı oluşturup, onu doğrudan seçtiğiniz firmaya 'Firma Admin' rolüyle atayın.</p>

    <?php if ($assign_message) echo $assign_message;  ?>

    <form method="POST" action="admin_panel.php?module=firma_admins">
        <input type="hidden" name="action" value="create_firma_admin">

        <div class="form-group" style="margin-bottom: 15px;">
            <label for="full_name"><strong>Yeni Yöneticinin Adı Soyadı:</strong></label>
            <input type="text" name="full_name" id="full_name" class="form-control" required style="width: 100%; padding: 8px; border: 1px solid #ddd;">
        </div>
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label for="email"><strong>E-posta Adresi (Giriş için):</strong></label>
            <input type="email" name="email" id="email" class="form-control" required style="width: 100%; padding: 8px; border: 1px solid #ddddddff;">
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label for="password"><strong>Geçici Şifre:</strong></label>
            <input type="password" name="password" id="password" class="form-control" required style="width: 100%; padding: 8px; border: 1px solid #ddd;">
             <small>Yönetici ilk girişinde şifresini değiştirmelidir.</small>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label for="company_id_to_assign"><strong>Yönetilecek Firma:</strong></label>
            <select name="company_id_to_assign" id="company_id_to_assign" class="form-control" required style="width: 100%; padding: 8px; border: 1px solid #ddd;">
                <option value="">-- Firma Seçin --</option>
                <?php foreach ($companies_list as $company): ?>
                    <option value="<?= htmlspecialchars($company['id']) ?>">
                        <?= htmlspecialchars($company['name']) ?> (ID: <?= htmlspecialchars($company['id']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Yeni Firma Admin Oluştur 
        </button>
    </form>
</div>