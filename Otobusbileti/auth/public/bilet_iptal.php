<?php

require_once '../src/bootstrap.php'; 


require_auth(['User', 'Firma Admin', 'Admin'], 'login.php');

$page_title = "Bilet İptal İşlemi";
$message = '';
$is_success = false;
$ticket_id = $_GET['ticket_id'] ?? null;
$user_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_ticket') {
    $ticket_id = $_POST['ticket_id'] ?? null;
    
    if (!$ticket_id) {
        $message = "Hata: İptal edilecek bilet ID'si belirtilmedi.";
    } else {
       
        $sql = "
            SELECT 
                T.id AS ticket_id, T.user_id, T.trip_id, T.total_price, 
                TR.departure_time, U.balance
            FROM Tickets T
            JOIN Trips TR ON T.trip_id = TR.id
            JOIN User U ON T.user_id = U.id
            WHERE T.id = :tid AND T.status = 'active'
        ";
        $ticket = db_select($sql, [':tid' => $ticket_id]);
        
        if (empty($ticket)) {
            $message = "Hata: Aktif ve geçerli bir bilet bulunamadı.";
        } else {
            $ticket = $ticket[0];
            $departure_time = strtotime($ticket['departure_time']);
            $current_time = time();
            
            
            if ($ticket['user_id'] !== $user_id) {
                
                 $message = "Hata: Bu bileti iptal etmeye yetkiniz yok.";
            } 
            
            elseif (($departure_time - $current_time) < 3600) { 
                $message = "İptal Başarısız: Kalkış saatine 1 saatten az süre kaldığı için bilet iptal edilemez.";
            } 
            else {
                
                try {
                    global $db_conn;
                    $db_conn->beginTransaction();

                    
                    $sql_ticket_update = "UPDATE Tickets SET status = 'cancelled', total_price = 0 
                                          WHERE id = :tid AND user_id = :uid";
                    db_execute($sql_ticket_update, [':tid' => $ticket_id, ':uid' => $user_id]);
                    
                    
                    $refund_amount = $ticket['total_price'];
                    $sql_user_update = "UPDATE User SET balance = balance + :refund WHERE id = :uid";
                    db_execute($sql_user_update, [':refund' => $refund_amount, ':uid' => $user_id]);

                    

                    $db_conn->commit();
                    $is_success = true;
                    $message = "Bilet başarıyla iptal edildi. " . number_format($refund_amount, 2) . " sanal kredi hesabınıza iade edildi.";
                    
                } catch (Exception $e) {
                    $db_conn->rollBack();
                    $message = "Veritabanı hatası oluştu. İptal edilemedi: " . $e->getMessage();
                }
            }
        }
    }
} 

elseif ($ticket_id && $_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $sql = "
        SELECT 
            T.id AS ticket_id, T.user_id, T.total_price, TR.departure_time,
            TR.departure_city, TR.destination_city, BC.name AS company_name
        FROM Tickets T
        JOIN Trips TR ON T.trip_id = TR.id
        JOIN Bus_Company BC ON TR.company_id = BC.id
        WHERE T.id = :tid AND T.status = 'active' AND T.user_id = :uid
    ";
    $ticket = db_select($sql, [':tid' => $ticket_id, ':uid' => $user_id]);

    if (empty($ticket)) {
        $message = "Hata: İptal edilecek aktif bilet bulunamadı.";
    } else {
        $ticket_to_confirm = $ticket[0];
        
        $remaining_time_seconds = strtotime($ticket_to_confirm['departure_time']) - time();
        
        if ($remaining_time_seconds < 3600) {
            $message = "İptal Edilemez: Kalkış saatine 1 saatten az süre kaldı.";
            $ticket_to_confirm = null;
        }
    }
}
?>

<?php require_once '../src/inc/header.php'; ?>

<div class="container" style="max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">

    <h1 style="color:#5D3FD3; text-align:center;"><?= $page_title ?></h1>

    <?php if ($message): ?>
        <p class="alert <?= $is_success ? 'alert-success' : 'alert-danger' ?>" style="text-align:center; padding: 10px; margin-bottom: 20px; background-color: <?= $is_success ? '#d4edda' : '#f8d7da' ?>; color: <?= $is_success ? '#155724' : '#721c24' ?>; border-radius: 5px; font-weight: bold;">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <?php if (isset($ticket_to_confirm) && $ticket_to_confirm): ?>
        
        <h3 style="margin-top: 30px; text-align:center;">İptal Onayı</h3>
        <p>Aşağıdaki bileti iptal etmek istediğinizden emin misiniz?</p>

        <div class="ticket-info" style="border: 1px dashed #5D3FD3; padding: 15px; margin-bottom: 20px;">
            <p><strong>Bilet ID:</strong> <?= htmlspecialchars($ticket_to_confirm['ticket_id']) ?></p>
            <p><strong>Firma:</strong> <?= htmlspecialchars($ticket_to_confirm['company_name']) ?></p>
            <p><strong>Güzergah:</strong> <?= htmlspecialchars($ticket_to_confirm['departure_city']) ?> → <?= htmlspecialchars($ticket_to_confirm['destination_city']) ?></p>
            <p><strong>Kalkış Zamanı:</strong> <?= date('d.m.Y H:i', strtotime($ticket_to_confirm['departure_time'])) ?></p>
            <p><strong>İade Edilecek Tutar:</strong> <strong style="color:green; font-size:1.2em;"><?= number_format($ticket_to_confirm['total_price'], 2) ?> Sanal Kredi</strong></p>
        </div>

        <form method="POST" style="text-align:center;">
            <input type="hidden" name="action" value="cancel_ticket">
            <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket_to_confirm['ticket_id']) ?>">
            <button type="submit" onclick="return confirm('Bu bilet geri alınamaz şekilde iptal edilecektir. Emin misiniz?')" 
                    style="padding: 10px 20px; background-color: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                Bileti Kesin Olarak İptal Et
            </button>
        </form>
        
    <?php elseif ($ticket_id && empty($message)): ?>
        <p style="text-align:center; color:#555;">İptal edilecek biletin detayları alınamadı. Bilet daha önce iptal edilmiş veya geçersiz olabilir.</p>
    <?php endif; ?>

    <div style="text-align:center; margin-top: 30px;">
        <a href="biletlerim.php" style="color:#5D3FD3; text-decoration:none; font-weight:bold;">Bilet Listesine Geri Dön</a>
    </div>

</div>

<?php require_once '../src/inc/footer.php'; ?>