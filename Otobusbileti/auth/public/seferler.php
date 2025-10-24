
<?php
// Kütüphane Yolları
require_once __DIR__ . '/../src/libs/connection.php'; 
require_once __DIR__ . '/../src/libs/helpers.php';    
require_once __DIR__ . '/../src/auth.php';           
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/libs/sehirler.php'; 

$all_cities = getCitiesList();
$pdo = db(); 

$page_title = "Sefer Arama Sonuçları";
$trips = [];
$error_message = '';
$is_search_executed = false; 

// URL'den gelen arama ve sıralama parametrelerini al
$departure = $_GET['kalkis'] ?? null;
$arrival = $_GET['varis'] ?? null;
$date = $_GET['tarih'] ?? null;
$sort_by = $_GET['sort_by'] ?? 'time'; // Varsayılan: Kalkış Saati

// Form geri doldurma için parametreleri sakla
$search_params = [
    'departure' => $departure,
    'arrival' => $arrival,
    'date' => $date 
];





// SQL Sorgusu Hazırlama Fonksiyonu (Sıralama Eklendi)
function buildTripQuery($where_clause_with_prefix = "", $sort_by = 'time') {
    
    $order_clause = "T.departure_time ASC"; 
    
    switch ($sort_by) {
        case 'price_asc': 
            $order_clause = "T.price ASC";
            break;
        case 'time': 
        default:
            $order_clause = "T.departure_time ASC";
            break;
    }

    $sql = "
        SELECT 
            T.id AS trip_id, T.departure_city, T.destination_city, T.departure_time, T.arrival_time,
            T.price, T.capacity, C.name AS company_name, T.company_id, COUNT(TI.id) AS sold_seats 
        FROM 
            Trips T
        LEFT JOIN Bus_Company C ON T.company_id = C.id
        LEFT JOIN Tickets TI ON T.id = TI.trip_id AND TI.status = 'active'
        " . $where_clause_with_prefix . "
        GROUP BY 
            T.id, C.name, T.departure_city, T.destination_city, T.departure_time, T.arrival_time, T.price, T.capacity, T.company_id
        ORDER BY 
            {$order_clause}  
    ";
    return $sql;
}


if ($departure && $arrival && $date) {
    $is_search_executed = true;
    
    // Sanitizasyon Çözüm 1 ile yapıldı (trim)
    $departure = trim($departure);
    $arrival = trim($arrival);
    $date = trim($date);
    
    if (empty($departure) || empty($arrival) || empty($date)) {
        $error_message = "Hata: Lütfen tüm arama kriterlerini doldurun.";
    } else {
        $sql_conditions = ["T.departure_city = :departure", "T.destination_city = :arrival", "DATE(T.departure_time) = :date"];
        $sql_parameters = [':departure' => $departure, ':arrival' => $arrival, ':date' => $date];
        
        $where_clause = " WHERE " . implode(" AND ", $sql_conditions);
        
        $sql = buildTripQuery($where_clause, $sort_by);
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($sql_parameters); 
            $trips = $stmt->fetchAll(PDO::FETCH_ASSOC); 
            
            if (empty($trips)) {
                $error_message = "Belirtilen kriterlere uygun sefer bulunamadı. ";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
    
    
    
} else {
    // GET (Sayfa Doğrudan Açıldı) - index.php'ye yönlendir
    header('Location: index.php'); 
    exit;
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <link rel="stylesheet" href="css/style.css"> 
</head>

<body>
    <header class="header-bar">
        <div class="logo"><a href="index.php">Otobüs Bileti</a></div>
        <nav class="auth-buttons">
            <a href="index.php">Ana Sayfa</a>
            <?php if (is_logged_in()): ?>
                <a href="hesabim.php">Hesabım</a>
                <a href="logout.php" class="register-btn">Çıkış Yap</a>
            <?php else: ?>
                <a href="login.php">Giriş Yap</a>
                <a href="register.php" class="register-btn">Kayıt Ol</a>
            <?php endif; ?>
        </nav>
    </header>

    

    <main class="container">
        
        <?php if ($is_search_executed): ?>
            
            <h2 style="text-align:center; margin:30px 0 30px; color:#5D3FD3;">
                <?php echo htmlspecialchars($search_params['departure']) . ' → ' . htmlspecialchars($search_params['arrival']) . ' (' . date('d.m.Y', strtotime($search_params['date'])) . ') Seferleri'; ?>
            </h2>

            <?php if (!empty($error_message) && empty($trips)): ?>
                <p class="alert alert-danger" style="margin-top: 20px; text-align: center;"><?php echo htmlspecialchars($error_message); ?></p>
            
            <?php elseif (!empty($trips)): ?>
                
                <section class="sefer-listesi" style="max-width: 900px; margin: 0 auto;"> 
                    
                    <?php 
                    $current_params = $_GET; 
                    unset($current_params['sort_by']); 
                    $base_query_string = http_build_query($current_params);
                    $active_sort = $_GET['sort_by'] ?? 'time'; 
                    ?>
                    
                    <div class="siralama-alani">
                        <label for="sort">Sırala:</label>
                        <select id="sort" onchange="window.location.href = 'seferler.php?<?php echo $base_query_string; ?>&sort_by=' + this.value;">
                            <option value="time" <?php if ($active_sort == 'time') echo 'selected'; ?>>Kalkış Saati</option>
                            <option value="price_asc" <?php if ($active_sort == 'price_asc') echo 'selected'; ?>>Fiyat (En Ucuz)</option>
                            <option value="duration" <?php if ($active_sort == 'duration') echo 'selected'; ?>>Süre</option>
                        </select>
                    </div>

                    <?php foreach ($trips as $trip): 
                        $available_seats = $trip['capacity'] - $trip['sold_seats'];
                        $seats_are_available = ($available_seats > 0);
                        $occupancy_status_text = $seats_are_available ? "{$available_seats} Boş ({$trip['capacity']} Toplam)" : "TÜMÜ DOLU";
                        $occupancy_color = $seats_are_available ? 'green' : 'red';
                        
                        // Seyahat Süresi Hesaplaması
                        $departure_ts = strtotime($trip['departure_time']);
                        $arrival_ts = strtotime($trip['arrival_time']);
                        $diff = abs($arrival_ts - $departure_ts); 
                        $hours = floor($diff / 3600);
                        $minutes = floor(($diff % 3600) / 60);
                        $duration_text = "{$hours} Saat {$minutes} Dakika";
                    ?>
                        
                        <div class="sefer-karti">
                            
                            <div class="firma-bilgi">
                                <strong><?php echo htmlspecialchars($trip['company_name']); ?></strong>
                                <span>Kalkış: <?php echo htmlspecialchars($trip['departure_city']); ?></span>
                                <span>Varış: <?php echo htmlspecialchars($trip['destination_city']); ?></span>
                            </div>
                            
                            <div class="saat-bilgi">
                                <span class="kalkis-saat"><?php echo date('H:i', $departure_ts); ?></span>
                                <span class="ok-isareti">→</span>
                                <span class="varis-saat"><?php echo date('H:i', $arrival_ts); ?></span>
                                <span style="display: block; font-size: 0.9em; color: #6c757d;">
                                    Yaklaşık Süre: **<?php echo $duration_text; ?>**
                                </span>
                            </div>
                            
                            <div class="fiyat-bilgi">
                                <span class="fiyat"><?php echo number_format($trip['price'], 2); ?> TL</span>
                                
                                <span style="display: block; font-size: 0.9em; color: <?php echo $occupancy_color; ?>; font-weight: bold; margin-bottom: 5px;">
                                    <?php echo $occupancy_status_text; ?>
                                </span>
                                
                                <?php if ($seats_are_available): ?>
                                    <a href="bilet_satin_alma.php?sefer_id=<?php echo $trip['trip_id']; ?>" class="bilet-al-butonu">Koltuk Seç</a>
                                <?php else: ?>
                                    <span class="bilet-al-butonu" style="background-color: #ccc; cursor: not-allowed; opacity: 0.7;">Dolu</span>
                                <?php endif; ?>
                                
                            </div>
                        </div> 
                    <?php endforeach; ?>
                </section>
                
            <?php endif; ?>
            
            <div style="text-align:center; margin-top:30px;">
                <a href="index.php" style="background:#5D3FD3; color:white; padding:10px 16px; border-radius:5px; text-decoration:none; font-weight:bold;">Yeni Arama Yap</a>
            </div>
            
        <?php else: ?>
            <p style="text-align:center; margin-top:50px; color:#777;">Yönlendirme hatası. Lütfen Ana Sayfadan arama yapınız.</p>
        <?php endif; ?>
        
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Otobüs Bileti Sistemi| Her hakkı saklıdır.</p>
    </footer>

</body>
</html>