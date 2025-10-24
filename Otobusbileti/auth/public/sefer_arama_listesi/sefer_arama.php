<div class="arama-konteyner">
    <div class="arama-kutusu">
        <form action="seferler.php" method="POST" class="arama-formu"> 
            
            <div class="input-grup">
                <label for="departure">Nereden (Kalkış):</label>
                <select id="departure" name="departure" required>
                    <option value="">Şehir Seçin</option>
                    <?php 
                    if (isset($all_cities) && !empty($all_cities)) {
                        foreach ($all_cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city); ?>"
                                <?php if (isset($search_params['departure']) && $search_params['departure'] === $city) echo 'selected'; ?>
                            >
                                <?php echo htmlspecialchars($city); ?>
                            </option>
                        <?php endforeach; 
                    } ?>
                </select>
            </div>
            
            <div class="input-grup">
                <label for="arrival">Nereye (Varış):</label>
                <select id="arrival" name="arrival" required>
                    <option value="">Şehir Seçin</option>
                    <?php 
                    foreach ($all_cities as $city): ?>
                        <option value="<?php echo htmlspecialchars($city); ?>"
                            <?php 
                            if ($search_params['arrival'] === $city) echo 'selected'; 
                            ?>
                        >
                            <?php echo htmlspecialchars($city); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="input-grup">
                <label for="date">Gidiş Tarihi:</label>
                <input type="date" id="date" name="date" required 
                        min="<?php echo date('Y-m-d'); ?>"
                        value="<?php echo htmlspecialchars($search_params['date']); ?>">
            </div>

            <div class="input-grup"> 
                <button type="submit" class="arama-butonu">SEFER ARA</button>
            </div>
        </form>
    </div>
</div>