<?php


 

function get_all_trips_for_management($company_id, $is_admin): array {
    $sql = "SELECT T.*, BC.name AS company_name 
            FROM Trips T
            JOIN Bus_Company BC ON T.company_id = BC.id";
    $params = [];
    
    if (!$is_admin && $company_id) {
        $sql .= " WHERE T.company_id = :cid";
        $params[':cid'] = $company_id;
    }
    
    $sql .= " ORDER BY T.departure_time DESC";
    
    
    try {
        return db_select($sql, $params);
    } catch (Exception $e) {
        
        error_log("Sefer çekme hatası: " . $e->getMessage());
        return []; 
    }
}



function create_trip_db($data, $company_id): bool {
    
     $trip_id = uniqid('trip_', true); 
    
    $sql = "INSERT INTO Trips (id, company_id, departure_city, destination_city, arrival_time, departure_time, price, capacity, created_at) 
            VALUES (:id, :cid, :dsc, :dct, :art, :dpt, :prc, :cap, datetime('now'))";
            
    $params = [
        ':id' => $trip_id,
        ':cid' => $company_id,
        ':dsc' => $data['departure_city'], 
        ':dct' => $data['destination_city'], 
        ':art' => $data['arrival_time'],
        ':dpt' => $data['departure_time'],
        ':prc' => (int)($data['price'] ?? 0),
        ':cap' => (int)($data['capacity'] ?? 40)
    ];

    try {
        return db_execute($sql, $params) > 0;
    } catch (Exception $e) {
        error_log("Sefer ekleme hatası: " . $e->getMessage());
        return false;
    }
}

function delete_trip_db($trip_id, $company_id, $is_admin): bool {
   
     $sql = "DELETE FROM Trips WHERE id = :id";
    $params = [':id' => $trip_id];
    
    if (!$is_admin) {
        $sql .= " AND company_id = :cid";
        $params[':cid'] = $company_id;
    }
    
    try {
        return db_execute($sql, $params) > 0;
    } catch (Exception $e) {
         error_log("Sefer silme hatası: " . $e->getMessage());
        return false;
    }
}


$sefer_message = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sefer_action'])) {
    $action = $_POST['sefer_action'];

    if ($action === 'add_trip') {
        $data = [
            'departure_city' => trim($_POST['departure_city'] ?? ''),
            'destination_city' => trim($_POST['destination_city'] ?? ''),
            
            'departure_time' => trim($_POST['departure_datetime'] ?? ''), 
            'arrival_time' => trim($_POST['arrival_datetime'] ?? ''), 
            'price'  => $_POST['price'] ?? 0,
            'capacity' => $_POST['capacity'] ?? 40 
        ];
        
       
        $company_to_use = $isAdmin && isset($_POST['company_id']) ? $_POST['company_id'] : $FirmaID;
        
        if (empty($data['departure_city']) || empty($company_to_use)) {
             $sefer_message = '<div style="color:red;">Hata: Tüm temel alanlar doldurulmalıdır.</div>';
        } elseif (create_trip_db($data, $company_to_use)) {
            $sefer_message = '<div style="color:green;">Yeni sefer başarıyla eklendi.</div>';
        } else {
            $sefer_message = '<div style="color:red;">Hata: Sefer eklenemedi.</div>';
        }

    } elseif ($action === 'delete_trip') {
        $trip_id = trim($_POST['trip_id'] ?? ''); 
        if (delete_trip_db($trip_id, $FirmaID, $isAdmin)) {
            $sefer_message = '<div style="color:orange;">Sefer başarıyla silindi.</div>';
        } else {
            $sefer_message = '<div style="color:red;">Hata: Sefer silinemedi veya yetkiniz yok.</div>';
        }
    }
}


$seferler = get_all_trips_for_management($FirmaID, $isAdmin);
$all_companies = $isAdmin ? db_select("SELECT id, name FROM Bus_Company ORDER BY name") : [];
?>

<div class="sefer-yonetimi">
    
    <div class="sefer-crud-form-section"> 
        <h3>Yeni Sefer Ekle</h3>
        
        <?php if ($sefer_message) echo '<div class="message" style="margin-bottom:15px;">' . $sefer_message . '</div>'; ?>
        
        <form method="POST" class="add-trip-form">
            <input type="hidden" name="sefer_action" value="add_trip">
            
            <?php if ($isAdmin): ?>
                <div class="input-group">
                    <label for="company_id">Firma Seçin (Admin):</label>
                    <select name="company_id" id="company_id" required>
                        <option value="">-- Firma Seçin --</option>
                        <?php foreach ($all_companies as $company): ?>
                            <option value="<?= htmlspecialchars($company['id']) ?>"><?= htmlspecialchars($company['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="input-group">
                <label for="departure_city">Kalkış Şehri:</label>
                <input type="text" name="departure_city" id="departure_city" placeholder="Örn: İstanbul" required>
            </div>
            
            <div class="input-group">
                <label for="destination_city">Varış Şehri:</label>
                <input type="text" name="destination_city" id="destination_city" placeholder="Örn: Ankara" required>
            </div>
            
            <div class="input-group">
                <label for="departure_datetime">Kalkış Zamanı (Tarih ve Saat):</label>
                <input type="datetime-local" name="departure_datetime" id="departure_datetime" required>
            </div>
            
            <div class="input-group">
                <label for="arrival_datetime">Tahmini Varış Zamanı:</label>
                <input type="datetime-local" name="arrival_datetime" id="arrival_datetime" required>
            </div>
            
            <div class="input-group">
                <label for="price">Fiyat (TL):</label>
                <input type="number" name="price" id="price" placeholder="Örn: 500" required min="0">
            </div>
            
            <div class="input-group">
                <label for="capacity">Koltuk Kapasitesi:</label>
                <select name="capacity" id="capacity" required>
                    <option value="40">40 Koltuk</option>
                    <option value="35">35 Koltuk</option>
                    <option value="30">30 Koltuk</option>
                </select>
            </div>
            
            <div class="submit-button-group">
                <button type="submit">Sefer Ekle</button>
            </div>
        </form>
    </div>
    
    <div class="sefer-table-section"> 
        <h3>Mevcut Seferler (<?= count($seferler); ?> Adet)</h3>
        <?php if (empty($seferler)): ?>
            <p style="text-align: center; padding: 15px; background-color: #fff;">Firmanıza ait aktif sefer bulunmamaktadır.</p>
        <?php else: ?>
            <table class="sefer-data-table"> 
                <thead>
                    <tr>
                        <th>ID</th>
                        <?php if ($isAdmin): ?><th>Firma</th><?php endif; ?>
                        <th>Kalkış</th>
                        <th>Varış</th>
                        <th>Kalkış Zamanı</th>
                        <th>Varış Zamanı</th>
                        <th>Fiyat</th>
                        <th>Kapasite</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($seferler as $sefer): ?>
                    <tr>
                        <td><?= substr(htmlspecialchars($sefer['id']), 0, 8); ?>...</td>
                        <?php if ($isAdmin): ?>
                            <td><?= htmlspecialchars($sefer['company_name']); ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($sefer['departure_city']); ?></td>
                        <td><?= htmlspecialchars($sefer['destination_city']); ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($sefer['departure_time'])); ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($sefer['arrival_time'])); ?></td>
                        <td><?= htmlspecialchars($sefer['price']); ?> TL</td>
                        <td><?= htmlspecialchars($sefer['capacity']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Silmek istediğinizden emin misiniz?')">
                                <input type="hidden" name="sefer_action" value="delete_trip">
                                <input type="hidden" name="trip_id" value="<?= htmlspecialchars($sefer['id']); ?>">
                                <button type="submit" class="delete-button">Sil</button> 
                            </form>
                            <a href="admin_panel.php?module=sefer&action=edit&id=<?= $sefer['id'] ?>">Düzenle</a> 
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>