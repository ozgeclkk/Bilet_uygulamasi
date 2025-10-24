<?php

if (!check_role(['Admin', 'Firma Admin'])) {
    echo '<div class="alert alert-danger">Bu bölüme erişim yetkiniz yok.</div>';
    return; 
}


$coupon_message = ''; 

 
function get_coupons_for_management($company_id, $is_admin): array {
    $sql = "SELECT C.*, BC.name AS company_name 
            FROM Coupons C
            LEFT JOIN Bus_Company BC ON C.company_id = BC.id";
    $params = [];
    
    
    if (!$is_admin && $company_id) {
        $sql .= " WHERE C.company_id = :cid";
        $params[':cid'] = $company_id;
    }
    
    $sql .= " ORDER BY C.created_at DESC";
    
    try {
        return db_select($sql, $params);
    } catch (Exception $e) {
        error_log("Kupon çekme hatası: " . $e->getMessage());
        return []; 
    }
}


function create_coupon_db($data, $company_id): bool|string {
   
    $coupon_id = uniqid('coup_', true); 
    
   
    $existing = db_select("SELECT id FROM Coupons WHERE code = :code", [':code' => $data['code']]);
    if (!empty($existing)) {
        return "Hata: Bu kupon kodu ('" . htmlspecialchars($data['code']) . "') zaten kullanılıyor.";
    }

    $sql = "INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, company_id, created_at) 
            VALUES (:id, :code, :discount, :limit, :expire, :cid, datetime('now'))";
            
    $params = [
        ':id' => $coupon_id,
        ':code' => $data['code'],
        ':discount' => (float)($data['discount'] ?? 0),
        ':limit' => (int)($data['usage_limit'] ?? 1), 
        ':expire' => $data['expire_date'], 
        ':cid' => $company_id 
    ];

    try {
        return db_execute($sql, $params) > 0;
    } catch (Exception $e) {
        error_log("Kupon ekleme hatası: " . $e->getMessage());
        return "Veritabanı hatası: Kupon eklenemedi.";
    }
}


function delete_coupon_db($coupon_id, $company_id, $is_admin): bool {
    $sql = "DELETE FROM Coupons WHERE id = :id";
    $params = [':id' => $coupon_id];
    
    
    if (!$is_admin && $company_id) {
        $sql .= " AND company_id = :cid";
        $params[':cid'] = $company_id;
    }
    
    try {
        return db_execute($sql, $params) > 0;
    } catch (Exception $e) {
         error_log("Kupon silme hatası: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['coupon_action'])) {
    $action = $_POST['coupon_action'];

    if ($action === 'add_coupon') {
        $data = [
            'code' => trim($_POST['code'] ?? ''),
            'discount' => trim($_POST['discount'] ?? '0'),
            'usage_limit' => trim($_POST['usage_limit'] ?? '1'),
            'expire_date' => trim($_POST['expire_date'] ?? '')
        ];
        
        
        $company_to_use = $isAdmin && isset($_POST['company_id']) ? trim($_POST['company_id']) : $current_user_company_id;
        
        
        if (empty($data['code']) || !is_numeric($data['discount']) || $data['discount'] <= 0 || !ctype_digit($data['usage_limit']) || $data['usage_limit'] < 1 || empty($data['expire_date']) || empty($company_to_use)) {
             $coupon_message = '<div class="alert alert-danger">Hata: Tüm alanlar doğru ve eksiksiz doldurulmalıdır (Kod, Pozitif İndirim, Pozitif Limit, Tarih, Firma).</div>';
        } 
        
        elseif (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $data['expire_date'])) {
             $coupon_message = '<div class="alert alert-danger">Hata: Geçerli bir son kullanma tarihi girin (YYYY-MM-DD formatında).</div>';
        }
        else {
            $result = create_coupon_db($data, $company_to_use);
            if ($result === true) {
                $coupon_message = '<div class="alert alert-success">Yeni kupon başarıyla eklendi. Liste güncellendi.</div>';
                
            } else {
                
                $coupon_message = '<div class="alert alert-danger">' . htmlspecialchars($result) . '</div>';
            }
        }

    } elseif ($action === 'delete_coupon') {
        $coupon_id = trim($_POST['coupon_id'] ?? ''); 
        if (!empty($coupon_id) && delete_coupon_db($coupon_id, $current_user_company_id, $isAdmin)) {
            $coupon_message = '<div class="alert alert-warning">Kupon başarıyla silindi. Liste güncellendi.</div>';
        
        } else {
            $coupon_message = '<div class="alert alert-danger">Hata: Kupon silinemedi veya yetkiniz yok.</div>';
        }
    }
}

$coupons = get_coupons_for_management($current_user_company_id, $isAdmin);


$all_companies_for_coupon = $isAdmin ? db_select("SELECT id, name FROM Bus_Company ORDER BY name") : [];
?>

<div class="coupon-management">
    <h2>Kupon Yönetimi</h2>

    <div class="admin-form-section"> 
        <h3>Yeni İndirim Kuponu Ekle</h3>
        
        <?php if ($coupon_message) echo '<div class="message" style="margin-bottom:15px;">' . $coupon_message . '</div>'; ?>
        
        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?module=kupon" class="add-coupon-form" style="display: flex; flex-wrap: wrap; gap: 15px;">
            <input type="hidden" name="coupon_action" value="add_coupon">
            
            <?php if ($isAdmin): ?>
                <div style="flex: 1 1 100%;"> <label for="coupon_company_id">Firma Seçin (Admin):</label>
                    <select name="company_id" id="coupon_company_id" required style="width: 100%; padding: 10px;">
                        <option value="">-- Firma Seçin --</option>
                        <?php foreach ($all_companies_for_coupon as $company): ?>
                            <option value="<?= htmlspecialchars($company['id']) ?>"><?= htmlspecialchars($company['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div style="flex: 1 1 200px;"> <label for="code">Kupon Kodu:</label>
                <input type="text" name="code" id="code" placeholder="Örn: INDIRIM25" required style="width: 100%; padding: 10px;">
            </div>
            
            <div style="flex: 1 1 120px;">
                <label for="discount">İndirim Oranı (%):</label>
                <input type="number" name="discount" id="discount" placeholder="Örn: 10" required min="1" max="100" step="any" style="width: 100%; padding: 10px;">
            </div>
            
            <div style="flex: 1 1 120px;">
                <label for="usage_limit">Kullanım Limiti:</label>
                <input type="number" name="usage_limit" id="usage_limit" placeholder="Örn: 100" required min="1" style="width: 100%; padding: 10px;">
            </div>
            
            <div style="flex: 1 1 180px;">
                <label for="expire_date">Son Kullanma Tarihi:</label>
                <input type="date" name="expire_date" id="expire_date" required min="<?= date('Y-m-d') ?>" style="width: 100%; padding: 10px;">
            </div>
            
            <div style="flex-basis: 100%; text-align: right; margin-top: 10px;">
                <button type="submit">Kupon Ekle</button>
            </div>
        </form>
    </div>
    
    <div class="admin-table-section"> 
        <h3>Mevcut Kuponlar (<?= count($coupons); ?> Adet)</h3>
        <?php if (empty($coupons)): ?>
            <p style="text-align: center; padding: 15px; background-color: #fff;">Aktif kupon bulunmamaktadır.</p>
        <?php else: ?>
            <table class="admin-data-table coupon-data-table"> 
                <thead>
                    <tr>
                        <th>Kod</th>
                        <th>İndirim (%)</th>
                        <th>Limit</th>
                        <th>Son Tarih</th>
                        <?php if ($isAdmin): ?><th>Firma</th><?php endif; ?>
                        <th>Oluşturma</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($coupon['code']); ?></strong></td>
                        <td><?= htmlspecialchars($coupon['discount']); ?>%</td>
                        <td><?= htmlspecialchars($coupon['usage_limit']); ?></td>
                        <td><?= date('d.m.Y', strtotime($coupon['expire_date'])); ?></td>
                        <?php if ($isAdmin): ?>
                            <td><?= htmlspecialchars($coupon['company_name'] ?? 'Genel'); ?></td>
                        <?php endif; ?>
                         <td><?= date('d.m.Y H:i', strtotime($coupon['created_at'])); ?></td>
                        <td>
                            <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?module=kupon" style="display:inline;" onsubmit="return confirm('Bu kuponu silmek istediğinizden emin misiniz?')">
                                <input type="hidden" name="coupon_action" value="delete_coupon">
                                <input type="hidden" name="coupon_id" value="<?= htmlspecialchars($coupon['id']); ?>">
                                <button type="submit" class="delete-button">Sil</button> 
                            </form>
                            </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>