<?php

function get_all_companies(): array {
    $sql = "SELECT id, name, logo_path, created_at FROM Bus_Company ORDER BY name ASC";
    return db_select($sql);
}


function create_company_db($name, $logo_path = null): bool {
    $company_id = uniqid('comp_', true); 
    
    $sql = "INSERT INTO Bus_Company (id, name, logo_path, created_at) 
            VALUES (:id, :name, :logo, datetime('now'))";
            
    $params = [
        ':id' => $company_id,
        ':name' => $name,
        ':logo' => $logo_path 
    ];

    return db_execute($sql, $params) > 0;
}



function delete_company_db($company_id): bool {
    
    $sql = "DELETE FROM Bus_Company WHERE id = :id";
    return db_execute($sql, [':id' => $company_id]) > 0;
}


$firma_message = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['firma_action'])) {
    $action = $_POST['firma_action'];

    if ($action === 'add_company') {
        $name = trim($_POST['name'] ?? '');
        
        if (empty($name)) {
             $firma_message = '<div style="color:red;">Hata: Firma adı boş bırakılamaz.</div>';
        } elseif (create_company_db($name)) {
            $firma_message = '<div style="color:green;">Yeni firma başarıyla eklendi.</div>';
        } else {
            $firma_message = '<div style="color:red;">Hata: Firma eklenemedi (İsim daha önce kullanılmış olabilir).</div>';
        }

    } elseif ($action === 'delete_company') {
        $company_id = trim($_POST['company_id'] ?? ''); 
        if (delete_company_db($company_id)) {
            $firma_message = '<div style="color:orange;">Firma başarıyla silindi.</div>';
        } else {
            $firma_message = '<div style="color:red;">Hata: Firma silinemedi. Firmaya ait aktif seferler olabilir.</div>';
        }
    }
}


$firmalar = get_all_companies();
?>

<div class="admin-form-section"> 
    <h3>Yeni Otobüs Firması Ekle</h3>
    
    <?php if ($firma_message) echo '<div class="message" style="margin-bottom:15px;">' . $firma_message . '</div>'; ?>
    
    <form method="POST" class="add-company-form"> 
        <input type="hidden" name="firma_action" value="add_company">
        <input type="text" name="name" placeholder="Firma Adı (Örn: Metro Turizm)" required>
        <button type="submit">Firma Ekle</button>
    </form>
</div>

<div class="admin-table-section"> 
    <h3>Mevcut Firmalar (<?= count($firmalar); ?> Adet)</h3>
    <?php if (empty($firmalar)): ?>
        <p style="text-align: center; padding: 15px; background-color: #fff;">Sistemde kayıtlı firma bulunmamaktadır.</p>
    <?php else: ?>
        <table class="admin-data-table"> 
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Firma Adı</th>
                    <th>Kayıt Tarihi</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($firmalar as $firma): ?>
                <tr>
                    <td><?= substr(htmlspecialchars($firma['id']), 0, 8); ?>...</td>
                    <td><?= htmlspecialchars($firma['name']); ?></td>
                    <td><?= htmlspecialchars($firma['created_at']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Firmayı silmek tüm seferlerini etkileyebilir. Emin misiniz?')">
                            <input type="hidden" name="firma_action" value="delete_company">
                            <input type="hidden" name="company_id" value="<?= htmlspecialchars($firma['id']); ?>">
                            <button type="submit" class="delete-button">Sil</button> 
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>