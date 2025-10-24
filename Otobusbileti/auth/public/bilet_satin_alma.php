<?php

require_once __DIR__ . '/../src/bootstrap.php'; 


$pdo = db();

$page_title = "Bilet Satın Alma";
$trip = null;
$booked_seats = [];
$error_message = '';


if (!is_logged_in()) {
    $redirect = urlencode($_SERVER['REQUEST_URI']);
    
    redirect("login.php?redirect=$redirect&message=Bilet_satın_almak_için_giriş_yapmalısınız.");
}


$trip_id = isset($_GET['sefer_id']) ? trim($_GET['sefer_id']) : null;


if (!$trip_id) {
    $error_message = "Hata: Satın alınacak sefer bilgisi (ID) bulunamadı.";
} else {
    
    try {
        
        $sql_trip = "
            SELECT 
                T.id, T.departure_city, T.destination_city, T.departure_time, T.arrival_time, T.price, T.capacity,
                BC.name AS company_name
            FROM Trips T JOIN Bus_Company BC ON T.company_id = BC.id
            WHERE T.id = :trip_id
        ";
        $stmt_trip = $pdo->prepare($sql_trip);
        $stmt_trip->bindParam(':trip_id', $trip_id);
        $stmt_trip->execute();
        $trip = $stmt_trip->fetch(PDO::FETCH_ASSOC);

        
        if (!$trip) {
            $error_message = "Hata: Belirtilen ID'ye sahip bir sefer bulunamadı.";
        } else { 
            
            $sql_booked_seats = "
                SELECT BS.seat_number
                FROM Booked_Seats BS JOIN Tickets TI ON BS.ticket_id = TI.id
                WHERE TI.trip_id = :trip_id AND TI.status = 'active' 
            "; 
            $stmt_seats = $pdo->prepare($sql_booked_seats);
            $stmt_seats->bindParam(':trip_id', $trip_id);
            $stmt_seats->execute();
            
            
            $booked_seats = array_map('strval', $stmt_seats->fetchAll(PDO::FETCH_COLUMN, 0)); 
        }

    } catch (PDOException $e) {
        
        $error_message = "Veritabanı hatası: " . $e->getMessage();
        
    }
} 
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="css/style.css"> 
    <style>
        
        .seating-layout { 
            display: grid; 
            grid-template-columns: repeat(2, 50px) 25px repeat(2, 50px); 
            width: 275px; 
            margin: 20px auto; 
            border: 2px solid #5D3FD3; 
            padding: 15px; 
            border-radius: 8px;
            background-color: #f8f8f8;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .seat { 
            width: 50px; height: 50px; margin: 5px 0; line-height: 50px; 
            text-align: center; border: 1px solid #aaa; cursor: pointer; 
            border-radius: 4px; font-size: 1em; font-weight: bold;
            transition: transform 0.1s, background-color 0.2s, color 0.2s;
        }
        .seat:hover:not(.booked) { 
            transform: scale(1.05); box-shadow: 0 0 5px #5D3FD3;
        }
        .seat.available { background-color: #e6e6fa; color: #5D3FD3; }
        .seat.booked { 
            background-color: #f44336; color: white; cursor: not-allowed; 
            box-shadow: inset 0 0 5px rgba(0,0,0,0.4); 
        }
        .seat.selected { background-color: #4caf50; color: white; transform: scale(1.02); }
        .driver-area { grid-column: 1 / span 2; height: 50px; text-align: center; line-height: 50px; font-size: 0.8em; font-weight: bold; background: #ccc; border-radius: 4px; }
        .front-door { grid-column: 4 / span 2; height: 50px; text-align: center; line-height: 50px; font-size: 0.8em; font-weight: bold; background: #ccc; border-radius: 4px; }
        .aisle { grid-column: 3; height: 50px; } 
        
        
        .passenger-input-group { margin-bottom: 15px; padding: 15px; border: 1px solid #eee; border-radius: 5px; background-color: #fafafa;}
        .passenger-input-group h4 { color: #5D3FD3; margin-top: 0; margin-bottom: 10px; }
        .passenger-input-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9em; color: #555;}
        .passenger-input-group input { width: calc(100% - 20px); padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 8px; }

        
        .container { max-width: 800px; margin: 30px auto; padding: 30px; background-color:#fff; border-radius:8px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .trip-summary { border: 1px solid #ddd; padding: 20px; margin-bottom: 30px; background-color:#f9f9f9; border-left: 5px solid #5D3FD3; border-radius: 5px; }
        .trip-summary h2 { margin-top: 0; color: #5D3FD3; }
        .trip-summary p { margin-bottom: 8px; }
        .trip-summary strong { color: #333; }
        h1, h3 { color: #5D3FD3; text-align: center; margin-bottom: 20px;}
        hr { border: 0; border-top: 1px solid #eee; margin: 25px 0; }
        #continue-button { background-color:#5D3FD3; color:white; padding:12px 25px; border:none; border-radius:6px; cursor:pointer; float:right; font-size: 1.1em; font-weight: bold; transition: background-color 0.3s;}
        #continue-button:disabled { background-color: #ccc; cursor: not-allowed; }
        #continue-button:hover:not(:disabled) { background-color: #4A32A1; }
    </style>
</head>
<body>

    <main class="container">
        <h1><?= htmlspecialchars($page_title); ?></h1>

        <?php if ($error_message): ?>
            <p class="alert alert-danger" style="color:red; font-weight:bold; text-align: center; padding: 15px; background-color: #f8d7da; border-radius: 5px;"><?= htmlspecialchars($error_message); ?></p>
            <div style="text-align: center; margin-top: 20px;"><a href="index.php" style="color: #5D3FD3; font-weight: bold;">Ana Sayfaya Dön</a></div>

        <?php elseif ($trip): ?>
            
            <div class="trip-summary">
                <h2><?= htmlspecialchars($trip['departure_city']) ?> → <?= htmlspecialchars($trip['destination_city']); ?></h2>
                <p><strong>Firma:</strong> <?= htmlspecialchars($trip['company_name']); ?></p>
                <p><strong>Kalkış / Varış:</strong> <?= date('d.m.Y H:i', strtotime($trip['departure_time'])) ?> / <?= date('d.m.Y H:i', strtotime($trip['arrival_time'])); ?></p>
                <p><strong>Birim Fiyat:</strong> <span style="font-size:1.5em; color:#dc3545; font-weight: bold;"><?= number_format($trip['price'], 2) . ' TL'; ?></span></p>
                <p><strong>Kapasite:</strong> <?= $trip['capacity']; ?> Koltuk</p>
            </div>

            <form action="odeme.php" method="POST"> 
                <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip_id); ?>">
                
                <h3>Koltuk Seçimi</h3>
                <p>Lütfen otobüs şemasından bir veya daha fazla koltuk seçiniz. (Toplam seçilen: <span id="selected-count" style="font-weight:bold;">0</span>)</p>

                <div class="seating-layout">
                    <div class="driver-area">Şoför</div>
                    <div class="front-door">Kapı</div>
                    
                    <?php 
                    $capacity = $trip['capacity'];
                    for ($i = 1; $i <= $capacity; $i++): 
                        $is_booked = in_array(strval($i), $booked_seats); 
                        $class = $is_booked ? 'booked' : 'available';
                        $onClickAction = $is_booked ? '' : 'onclick="toggleSeat(this)"'; 
                    ?>
                        <div class="seat <?= $class; ?>" 
                             data-seat="<?= $i; ?>" 
                             <?= $onClickAction ?> > 
                            <?= $i; ?>
                        </div>
                        <?php 
                        
                        if (($i % 4) == 2): 
                            echo '<div class="aisle"></div>'; 
                        endif; 
                        ?>
                    <?php endfor; ?>
                </div>

                <input type="hidden" name="selected_seats" id="selected-seats-input" required>
                <input type="hidden" name="total_price" id="total-price-input" required>

                <h3 style="margin-top: 30px;">Yolcu Bilgileri</h3>
                <div id="passenger-info-container">
                    <p style="color: blue; font-style: italic;">Lütfen önce koltuk seçimi yapınız.</p>
                </div>
                
                <hr>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                     <p style="font-size: 1.2em; margin: 0;">Ödenecek Toplam Tutar:</p>
                     <strong id="total-price-display" style="font-size: 1.8em; color: #dc3545;">0.00 TL</strong>
                </div>
                
                <div style="text-align: right; margin-top: 20px;">
                     <button type="submit" id="continue-button" disabled>Ödemeye Geç</button>
                </div>
            </form>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const selectedSeatsInput = document.getElementById('selected-seats-input');
                    const totalPriceInput = document.getElementById('total-price-input');
                    const totalPriceDisplay = document.getElementById('total-price-display');
                    const selectedCountDisplay = document.getElementById('selected-count');
                    const passengerContainer = document.getElementById('passenger-info-container');
                    const continueButton = document.getElementById('continue-button');

                    let selectedSeats = []; 
                    let basePrice = <?= json_encode(floatval($trip['price'])); ?>; 

                    
                    window.toggleSeat = function(seatElement) {
                        const seatNumber = parseInt(seatElement.getAttribute('data-seat'));
                        const index = selectedSeats.indexOf(seatNumber);

                        if (index > -1) { 
                            selectedSeats.splice(index, 1);
                            seatElement.classList.remove('selected');
                            seatElement.classList.add('available');
                        } else { 
                            selectedSeats.push(seatNumber);
                            seatElement.classList.add('selected');
                            seatElement.classList.remove('available');
                        }
                        
                        selectedSeats.sort((a, b) => a - b); 
                        updateDisplay(); 
                    }

                    function updateDisplay() {
                        const count = selectedSeats.length;
                        selectedCountDisplay.textContent = count; 
                        const totalPrice = count * basePrice; 
                        
                        
                        selectedSeatsInput.value = selectedSeats.join(','); 
                        totalPriceInput.value = totalPrice.toFixed(2); 
                        
                        
                        totalPriceDisplay.textContent = totalPrice.toFixed(2) + ' TL';
                        
                       
                        continueButton.disabled = count === 0;
                        
                      
                        updatePassengerInputs(count);
                    }

                    
                    function updatePassengerInputs(count) {
                        passengerContainer.innerHTML = ''; 
                        if (count === 0) {
                            passengerContainer.innerHTML = '<p style="color: blue; font-style: italic;">Lütfen önce koltuk seçimi yapınız.</p>';
                            return;
                        }

                       
                        selectedSeats.forEach((seatNumber) => {
                            const div = document.createElement('div');
                            div.className = 'passenger-input-group'; 
                            
                            div.innerHTML = `
                                <h4>Koltuk ${seatNumber} Yolcu Bilgisi:</h4>
                                <label for="passenger_${seatNumber}_name">Ad Soyad:</label>
                                <input type="text" id="passenger_${seatNumber}_name" name="passenger[${seatNumber}][full_name]" placeholder="Yolcu Adı Soyadı" required>
                                
                                <label for="passenger_${seatNumber}_tc">TC Kimlik No:</label>
                                <input type="text" id="passenger_${seatNumber}_tc" name="passenger[${seatNumber}][tc_no]" placeholder="TC Kimlik Numarası" required pattern="[1-9]{1}[0-9]{9}[02468]{1}" title="Geçerli bir TC Kimlik No giriniz (11 Haneli)."> 
                                
                                `; 
                            passengerContainer.appendChild(div);
                        });
                    }

                    
                    updateDisplay(); 
                });
            </script>

        <?php endif; ?>
    </main>

    <?php require_once '../src/inc/footer.php'; ?>
</body>
</html>