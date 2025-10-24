<?php

require_once '../src/bootstrap.php'; 


$trip_id = $_GET['id'] ?? null;
$sefer_detay = null;
$error_message = '';

if ($trip_id) {
   
    $sql = "
        SELECT 
            T.*, 
            BC.name AS company_name,
            COUNT(TI.id) AS sold_seats
        FROM Trips T
        JOIN Bus_Company BC ON T.company_id = BC.id
        LEFT JOIN Tickets TI ON T.id = TI.trip_id AND TI.status = 'active'
        WHERE T.id = :id
        GROUP BY T.id, BC.name
    ";

    $result = db_select($sql, [':id' => $trip_id]);
    
    if (!empty($result)) {
        $sefer_detay = $result[0];
    } else {
        $error_message = "Hata: Belirtilen ID'de sefer bulunamadı.";
    }
} else {
    $error_message = "Hata: Geçerli bir sefer ID'si belirtilmedi.";
}


$sold_seats = [];
if ($sefer_detay) {
    
    $sold_seats_data = db_select("
        SELECT seat_number 
        FROM Booked_Seats BS
        JOIN Tickets T ON BS.ticket_id = T.id
        WHERE T.trip_id = :trip_id AND T.status = 'active'
    ", [':trip_id' => $trip_id]);
    
    
    $sold_seats = array_column($sold_seats_data, 'seat_number');
}


$is_user_logged_in = is_logged_in();
$user_can_buy = $is_user_logged_in; 
$user_role = $_SESSION['user_role'] ?? 'Ziyaretçi';

$page_title = $sefer_detay ? "Sefer Detayı: " . $sefer_detay['departure_city'] . " - " . $sefer_detay['destination_city'] : "Sefer Detayı";
?>

<?php require_once '../src/inc/header.php'; ?>

<div class="container sefer-detay-page">

    <?php if ($error_message): ?>
        <p class="alert alert-danger" style="text-align:center; margin-top:50px; font-size:18px; color:red;">
            <?= htmlspecialchars($error_message) ?>
        </p>
        <div style="text-align:center; margin-top:20px;"><a href="index.php">Yeni Arama Yap</a></div>
    <?php else: ?>
        
        <h1 class="page-header" style="text-align:center; color:#5D3FD3; margin-top:30px;"><?= $page_title ?></h1>
        
        <div class="trip-details-card">
            
            <div class="detail-block">
                <strong>Firma:</strong> <span><?= htmlspecialchars($sefer_detay['company_name']) ?></span>
                <strong>Fiyat:</strong> <span class="price"><?= number_format($sefer_detay['price'], 2) ?> TL</span>
            </div>
            
            <div class="detail-block">
                <strong>Kalkış:</strong> <span><?= htmlspecialchars($sefer_detay['departure_city']) ?> (<?= date('d.m.Y H:i', strtotime($sefer_detay['departure_time'])) ?>)</span>
                <strong>Varış:</strong> <span><?= htmlspecialchars($sefer_detay['destination_city']) ?> (<?= date('d.m.Y H:i', strtotime($sefer_detay['arrival_time'])) ?>)</span>
            </div>
        </div>

        <h2 style="margin-top:40px; text-align:center;">Koltuk Seçimi</h2>
        
        <div class="seat-selection-area">
            
            <form action="bilet_satın_alma.php" method="GET" class="seat-form">
                <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip_id) ?>">
                
                <div class="bus-layout">
                    <?php 
                    $total_seats = $sefer_detay['capacity'];
                    
                    for ($i = 1; $i <= $total_seats; $i++):
                        $is_sold = in_array($i, $sold_seats);
                        $seat_class = $is_sold ? 'sold' : 'available';
                        $disabled_attr = $is_sold ? 'disabled' : '';
                        
                        
                        if ($i % 4 == 1 && $i > 1) { echo '<div class="aisle-break"></div>'; } 
                        
                        if ($i % 4 == 3) { echo '<div class="aisle-break-right"></div>'; } 
                    ?>
                        <label class="seat-label <?= $seat_class ?>">
                            <input type="checkbox" name="seats[]" value="<?= $i ?>" <?= $disabled_attr ?> class="seat-input">
                            <?= $i ?>
                        </label>
                    <?php endfor; ?>
                    
                    <div class="bus-legend">
                        <span class="legend-item available">Boş</span>
                        <span class="legend-item sold">Dolu</span>
                        <span class="legend-item selected">Seçili</span>
                    </div>
                </div> 

                <div class="buy-button-area">
                    <?php if ($user_can_buy): ?>
                        <button type="submit" class="buy-button">Bilet Satın Almaya Devam Et</button>
                    <?php else: ?>
                        <button type="button" class="buy-button disabled-button" 
                                onclick="alert('Lütfen Giriş Yapın.'); window.location.href='login.php?redirect=' + encodeURIComponent(window.location.href);">
                            Bilet Satın Al (Giriş Yap)
                        </button>
                        <p class="login-tip">Bilet satın almak için lütfen giriş yapın.</p>
                    <?php endif; ?>
                </div>

            </form>
        </div>

    <?php endif; ?>
</div>

<style>
.trip-details-card {
    border: 1px solid #ddd;
    border-left: 5px solid #5D3FD3; 
    padding: 20px;
    margin: 20px auto;
    max-width: 800px;
    background-color: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.detail-block { margin-bottom: 10px; }
.detail-block strong { color: #5D3FD3; display: inline-block; width: 100px; }
.price { font-size: 1.5em; color: #dc3545; font-weight: bold; }

.seat-selection-area { text-align: center; margin: 30px auto; max-width: 500px; }
.bus-layout { display: flex; flex-wrap: wrap; justify-content: center; gap: 5px; border: 1px solid #ccc; padding: 20px; border-radius: 8px; background-color: #f9f9f9; }

.seat-label {
    width: 40px;
    height: 40px;
    line-height: 40px;
    border-radius: 5px;
    background-color: #e0e0e0;
    cursor: pointer;
    font-size: 0.9em;
    font-weight: bold;
    color: #333;
    transition: background-color 0.2s;
    position: relative;
    margin: 2px;
}
.seat-label.available:hover { background-color: #C3B1E1; }
.seat-label.sold { background-color: #dc3545 !important; color: white; cursor: not-allowed; }
.seat-label input.seat-input { display: none; }
.seat-label input.seat-input:checked + span, .seat-label.selected { background-color: #5D3FD3; color: white; }

.seat-label input.seat-input:checked {
    background-color: #5D3FD3;
}

.seat-label.available { background-color: #a8d7a8; } 

.bus-legend { margin-top: 20px; width: 100%; text-align: center; font-size: 0.9em; }
.legend-item { margin-right: 15px; }
.legend-item::before { content: '■'; font-size: 1.2em; margin-right: 5px; }
.legend-item.available::before { color: #a8d7a8; }
.legend-item.sold::before { color: #dc3545; }
.legend-item.selected::before { color: #5D3FD3; }

.buy-button-area { margin-top: 30px; }
.buy-button { padding: 15px 30px; background-color: #5D3FD3; color: white; border: none; border-radius: 5px; font-size: 1.1em; cursor: pointer; transition: background-color 0.3s; }
.buy-button:hover { background-color: #4A32A1; }
.disabled-button { background-color: #ccc; cursor: not-allowed; }
.login-tip { color: #dc3545; margin-top: 10px; font-weight: bold; }
</style>

<?php require_once '../src/inc/footer.php'; ?>