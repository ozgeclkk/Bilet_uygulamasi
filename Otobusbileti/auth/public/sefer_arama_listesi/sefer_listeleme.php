<div class="sayfa-konteyner">
    
    <aside class="filtre-panel">
        <h4>Filtreleme Seçenekleri</h4>
        
        <div class="filtre-grup">
            <h4>Firma</h4>
            <label><input type="checkbox" name="company[]" value="otobus_a"> Otobüs A</label> <br>
            <label><input type="checkbox" name="company[]" value="otobus_b"> Otobüs B</label>
        </div>
        
        <button class="filtre-uygula">Filtrele</button>
    </aside>

    <section class="sefer-listesi">
        
        <h2>Bulunan Seferler (<?php echo count($trips); ?>)</h2>
        
        <div class="siralama-alani">
            <label for="sort">Sırala:</label>
            <select id="sort">
                <option value="time">Kalkış Saati</option>
                <option value="price">Fiyat (En Ucuz)</option>
                <option value="duration">Süre</option>
            </select>
        </div>
        
        <?php foreach ($trips as $trip): ?>
    <?php 
        $trip_id = $trip['trip_id']; 
        $redirect_url = urlencode("bilet_satin_alma.php?sefer_id=" . $trip_id); 
        
       
        $available_seats = $trip['capacity'] - $trip['sold_seats'];
        
        $seats_are_available = ($available_seats > 0);
        
        
        $occupancy_status_text = $seats_are_available ? "{$available_seats} Available Seats ({$trip['capacity']} Total)" : "FULLY BOOKED ({$trip['capacity']} Total)";
        $occupancy_color = $seats_are_available ? 'green' : 'red';
    ?>
    
    <div class="sefer-karti">
        
        <div class="firma-bilgi">
            <strong><?php echo htmlspecialchars($trip['company_name'] ?? 'Bilinmiyor'); ?></strong>
            <span>Kalkış: <?php echo htmlspecialchars($trip['departure_city']); ?></span>
            <span>Varış: <?php echo htmlspecialchars($trip['destination_city']); ?></span>
        </div>
        
        <div class="saat-bilgi">
            <span class="kalkis-saat"><?php echo date('H:i', strtotime($trip['departure_time'])); ?></span>
            <span class="ok-isareti">→</span>
            <span class="varis-saat"><?php echo date('H:i', strtotime($trip['arrival_time'])); ?></span>
            <span style="display: block; font-size: 0.9em; color: #6c757d;">
                Yaklaşık Süre: X saat
            </span>
        </div>
        
        <div class="fiyat-bilgi">
            <span class="fiyat"><?php echo htmlspecialchars(number_format($trip['price'], 2)) . ' TL'; ?></span>
            
            <span style="display: block; font-size: 0.9em; color: <?php echo $occupancy_color; ?>; font-weight: bold; margin-bottom: 5px;">
                <?php echo $occupancy_status_text; ?>
            </span>
            
            <?php if ($seats_are_available):?>
                <?php if (is_logged_in() && get_current_user_role() === 'User'): ?>
                    <a href="bilet_satin_alma.php?sefer_id=<?php echo $trip_id; ?>" class="bilet-al-butonu">Bilet Al</a>
                <?php else: ?>
                    <a href="login.php?redirect=<?php echo $redirect_url; ?>" class="bilet-al-butonu" style="background-color: #5D3FD3; color: white;">Giriş Yap & Bilet Al</a>
                <?php endif; ?>
            <?php else: ?>
                <span class="bilet-al-butonu" style="background-color: #6c757d; cursor: not-allowed; opacity: 0.7;">Satış Kapalı (Dolu)</span>
            <?php endif; ?>
            
        </div>
    </div> 
<?php endforeach; ?>