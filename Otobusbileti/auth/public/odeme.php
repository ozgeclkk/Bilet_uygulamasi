<?php

require_once __DIR__ . '/../src/bootstrap.php'; 


$pdo = db();

$page_title = "Ödeme ve Bilet Onayı";
$error_message = '';
$success_message = '';
$ticket_details = null; 


require_auth(['User', 'Firma Admin', 'Admin'], 'login.php');
$user_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

   
    $trip_id = trim($_POST['trip_id'] ?? '');
    $selected_seats_str = trim($_POST['selected_seats'] ?? ''); 
    $submitted_total_price = (float)($_POST['total_price'] ?? 0);
    $passenger_data = $_POST['passenger'] ?? []; 

    
    $selected_seats = !empty($selected_seats_str) ? explode(',', $selected_seats_str) : [];

    
    if (empty($trip_id) || empty($selected_seats) || $submitted_total_price <= 0 || empty($passenger_data)) {
        $error_message = "Hata: Eksik veya geçersiz bilgi gönderildi. Lütfen koltuk seçimine geri dönün.";
    } 
    
    elseif (count($selected_seats) !== count($passenger_data)) {
         $error_message = "Hata: Seçilen koltuk sayısı ile girilen yolcu bilgisi sayısı eşleşmiyor.";
    }
    else {
       
        try {
            
            $sql_trip = "SELECT price, capacity, departure_time FROM Trips WHERE id = :trip_id";
            $stmt_trip = $pdo->prepare($sql_trip);
            $stmt_trip->execute([':trip_id' => $trip_id]);
            $trip = $stmt_trip->fetch();

            if (!$trip) {
                throw new Exception("Hata: Geçersiz sefer ID'si.");
            }

            
            if (strtotime($trip['departure_time']) < time()) {
                 throw new Exception("Hata: Bu seferin kalkış zamanı geçmiş.");
            }

            
            $server_calculated_price = count($selected_seats) * (float)$trip['price'];
            if (abs($server_calculated_price - $submitted_total_price) > 0.01) { 
                throw new Exception("Hata: Fiyat bilgisi uyuşmuyor. İşlem durduruldu.");
            }
            $total_price = $server_calculated_price; 

            $sql_user = "SELECT balance FROM User WHERE id = :user_id";
            $stmt_user = $pdo->prepare($sql_user);
            $stmt_user->execute([':user_id' => $user_id]);
            $user = $stmt_user->fetch();

            if (!$user || $user['balance'] < $total_price) {
                throw new Exception("Hata: Yetersiz sanal kredi. Mevcut Bakiye: " . number_format($user['balance'] ?? 0, 2) . " TL");
            }

           
            $placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
            $sql_check_seats = "
                SELECT COUNT(BS.id) 
                FROM Booked_Seats BS JOIN Tickets TI ON BS.ticket_id = TI.id
                WHERE TI.trip_id = ? AND TI.status = 'active' AND BS.seat_number IN ($placeholders)
            ";
            $params_check = array_merge([$trip_id], $selected_seats);
            $stmt_check = $pdo->prepare($sql_check_seats);
            $stmt_check->execute($params_check);
            $already_booked_count = $stmt_check->fetchColumn();

            if ($already_booked_count > 0) {
                throw new Exception("Hata: Seçtiğiniz koltuklardan bazıları siz işlemi tamamlarken satıldı. Lütfen koltuk seçimine geri dönüp tekrar deneyin.");
            }

         
            $pdo->beginTransaction();

            
            $sql_update_balance = "UPDATE User SET balance = balance - :amount WHERE id = :user_id";
            db_execute($sql_update_balance, [':amount' => $total_price, ':user_id' => $user_id]);

           
            $ticket_id = uniqid('TKT_', true); 
            $sql_insert_ticket = "
                INSERT INTO Tickets (id, trip_id, user_id, status, total_price, created_at) 
                VALUES (:id, :trip_id, :user_id, 'active', :total_price, datetime('now', 'localtime'))
            ";
            db_execute($sql_insert_ticket, [
                ':id' => $ticket_id,
                ':trip_id' => $trip_id,
                ':user_id' => $user_id,
                ':total_price' => $total_price
            ]);

            
            $sql_insert_seat = "
                INSERT INTO Booked_Seats (id, ticket_id, seat_number, passenger_name, passenger_tc, created_at) 
                VALUES (:id, :ticket_id, :seat_number, :p_name, :p_tc, datetime('now', 'localtime'))
            ";
            $stmt_insert_seat = $pdo->prepare($sql_insert_seat);

            foreach ($selected_seats as $seat_num) {
                if (!isset($passenger_data[$seat_num])) {
                    throw new Exception("Hata: Koltuk $seat_num için yolcu bilgisi bulunamadı."); 
                }
                $p_info = $passenger_data[$seat_num];
                $booked_seat_id = uniqid('BS_', true); 

                $stmt_insert_seat->execute([
                    ':id' => $booked_seat_id,
                    ':ticket_id' => $ticket_id,
                    ':seat_number' => $seat_num,
                    ':p_name' => trim($p_info['full_name'] ?? 'N/A'),
                    ':p_tc' => trim($p_info['tc_no'] ?? 'N/A') 
                ]);
            }

      
            $pdo->commit();

            $success_message = "Biletleriniz başarıyla oluşturuldu! Bilet ID: " . $ticket_id;
            
             $ticket_details = [
                 'ticket_id' => $ticket_id,
                 'trip_info' => $trip, 
                 'seats' => $selected_seats,
                 'passengers' => $passenger_data,
                 'total_price' => $total_price
             ];


        } catch (Exception $e) {
         
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = $e->getMessage();
        }
    } 
} else {
    
    $error_message = "Geçersiz istek. Lütfen koltuk seçimi yaparak devam edin.";
   
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="css/style.css"> 
    <style>
        .container { max-width: 700px; margin: 50px auto; padding: 30px; background-color:#fff; border-radius:8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; text-align: center; }
        .ticket-summary { border: 1px dashed #5D3FD3; padding: 20px; margin-top: 20px; background-color: #f8f7fc; border-radius: 5px;}
        .ticket-summary h3 { color: #5D3FD3; margin-top: 0; }
        .ticket-summary p { margin-bottom: 5px; }
        .action-links { text-align: center; margin-top: 30px; }
        .action-links a { display: inline-block; margin: 0 10px; padding: 10px 20px; background-color: #5D3FD3; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .action-links a:hover { background-color: #4A32A1; }
    </style>
</head>
<body>
    <main class="container">
        <h1><?= htmlspecialchars($page_title); ?></h1>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message); ?>
            </div>
            <div class="action-links">
                 <?php if ($trip_id): ?>
                     <a href="bilet_satin_alma.php?sefer_id=<?= htmlspecialchars($trip_id) ?>">Koltuk Seçimine Geri Dön</a>
                 <?php endif; ?>
                 <a href="index.php">Ana Sayfa</a>
            </div>

        <?php elseif ($success_message && $ticket_details): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message); ?>
            </div>

            <div class="ticket-summary">
                <h3>Bilet Detayları</h3>
                <p><strong>Bilet ID:</strong> <?= htmlspecialchars($ticket_details['ticket_id']) ?></p>
                <p><strong>Firma:</strong> <?= htmlspecialchars($ticket_details['trip_info']['company_name'] ?? 'N/A') ?></p>
                <p><strong>Güzergah:</strong> <?= htmlspecialchars($ticket_details['trip_info']['departure_city'] ?? '?') ?> → <?= htmlspecialchars($ticket_details['trip_info']['destination_city'] ?? '?') ?></p>
                <p><strong>Kalkış:</strong> <?= date('d.m.Y H:i', strtotime($ticket_details['trip_info']['departure_time'] ?? time())) ?></p>
                <p><strong>Seçilen Koltuklar:</strong> <?= implode(', ', $ticket_details['seats']) ?></p>
                <p><strong>Toplam Ödenen Tutar:</strong> <?= number_format($ticket_details['total_price'], 2) ?> TL</p>
                <hr>
                <h4>Yolcular:</h4>
                <?php foreach ($ticket_details['passengers'] as $seatNum => $passenger): ?>
                    <p><strong>Koltuk <?= $seatNum ?>:</strong> <?= htmlspecialchars($passenger['full_name']) ?> (TC: <?= htmlspecialchars($passenger['tc_no']) ?>)</p>
                <?php endforeach; ?>
            </div>

            <div class="action-links">
                <a href="pdf_indir.php?ticket_id=<?= htmlspecialchars($ticket_details['ticket_id']) ?>">Bileti PDF İndir</a>
                <a href="biletlerim.php">Tüm Biletlerim</a>
                <a href="index.php">Ana Sayfa</a>
            </div>

        <?php else:  ?>
             <div class="alert alert-warning">Bir sorun oluştu. Lütfen tekrar deneyin.</div>
              <div class="action-links"><a href="index.php">Ana Sayfa</a></div>
        <?php endif; ?>
    </main>

     <?php require_once '../src/inc/footer.php'; ?>
</body>
</html>