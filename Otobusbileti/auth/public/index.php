<?php 

require_once '../src/bootstrap.php';


$iller = [
    'Adana', 'Adıyaman', 'Afyonkarahisar', 'Ağrı', 'Amasya', 'Ankara', 
    'Antalya', 'Artvin', 'Aydın', 'Balıkesir', 'Bilecik', 'Bingöl', 
    'Bitlis', 'Bolu', 'Burdur', 'Bursa', 'Çanakkale', 'Çankırı', 
    'Çorum', 'Denizli', 'Diyarbakır', 'Edirne', 'Elazığ', 'Erzincan', 
    'Erzurum', 'Eskişehir', 'Gaziantep', 'Giresun', 'Gümüşhane', 
    'Hakkâri', 'Hatay', 'Isparta', 'Mersin', 'İstanbul', 'İzmir', 
    'Kars', 'Kastamonu', 'Kayseri', 'Kırklareli', 'Kırşehir', 'Kocaeli', 
    'Konya', 'Kütahya', 'Malatya', 'Manisa', 'Kahramanmaraş', 'Mardin', 
    'Muğla', 'Muş', 'Nevşehir', 'Niğde', 'Ordu', 'Rize', 'Sakarya', 
    'Samsun', 'Siirt', 'Sinop', 'Sivas', 'Tekirdağ', 'Tokat', 'Trabzon', 
    'Tunceli', 'Şanlıurfa', 'Uşak', 'Van', 'Yozgat', 'Zonguldak', 
    'Aksaray', 'Bayburt', 'Karaman', 'Kırıkkale', 'Batman', 'Şırnak', 
    'Bartın', 'Ardahan', 'Iğdır', 'Yalova', 'Karabük', 'Kilis', 
    'Osmaniye', 'Düzce'
];
sort($iller); 


$page_title = "Otobüs Bileti | Ana Sayfa";


?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="css/style.css"> 
</head>
<body>

<header class="header-bar">
    <div class="logo">
        <a href="index.php">OTOBÜS BİLETİM</a>
    </div>
    
    <nav class="auth-buttons">
        
        <?php if (is_logged_in()): ?>
            <span style="margin-right: 15px; font-weight: bold;">
                Hoş Geldin <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['user_id']) ?>
            </span>
            
            <?php if (check_role('Admin')): ?>
                <a href="admin_panel.php" class="admin-panel-btn" 
                   style="background-color: #f0ad4e; font-weight: bold;">
                    ADMIN PANELİ
                </a>
            <?php elseif (check_role('Firma Admin')): ?>
                <a href="firma_admin_panel.php" class="admin-panel-btn" 
                   style="background-color: #5cb85c; font-weight: bold;">
                    FİRMA PANELİ
                </a>
            <?php endif; ?>
            
            <a href="biletlerim.php">Hesabım</a> 
            
            <a href="biletlerim.php?action=dev_panel" style="background-color: #007bff; font-weight: bold;">
                TEST PANELİ
            </a>
            
            <a href="logout.php" class="register-btn">Çıkış Yap</a>
        <?php else: ?>
            <a href="login.php" class="login-btn">Giriş Yap</a>
            <a href="register.php" class="register-btn">Kayıt Ol</a>
        <?php endif; ?>
    </nav>
</header>

<div class="arama-konteyner">
    <div class="arama-kutusu">
        
        <form class="arama-formu" action="seferler.php" method="GET"> 
            
            <div class="input-grup">
                <label for="kalkis">Nereden (Kalkış)</label>
                <select id="kalkis" name="kalkis" required>
                    <option value="">Şehir Seçin</option>
                    <?php foreach ($iller as $il): ?>
                        <option value="<?php echo $il; ?>"><?php echo $il; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="input-grup">
                <label for="varis">Nereye (Varış)</label>
                <select id="varis" name="varis" required>
                    <option value="">Şehir Seçin</option>
                    <?php foreach ($iller as $il): ?>
                        <option value="<?php echo $il; ?>"><?php echo $il; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="input-grup">
                <label for="tarih">Gidiş Tarihi</label>
                <input type="date" id="tarih" name="tarih" required>
            </div>
            
            <div class="input-grup">
                <button type="submit" class="arama-butonu">
                    SEFER ARA
                </button>
            </div>
            
        </form>
    </div>
</div>

<?php 

?>
<footer class="site-footer">
    <p style="text-align: center; padding: 20px; color: #5D3FD3; background-color: #fff; margin-top: 50px; border-top: 1px solid #E6E6FA;">
        &copy; <?php echo date("Y"); ?> Otobüs Bileti Satış Uygulaması. Tüm hakları saklıdır.
    </p>
</footer>
<script>
    const aramaFormu = document.querySelector('.arama-formu');
    aramaFormu.addEventListener('submit', function(event) {
        const kalkisSelect = document.getElementById('kalkis');
        const varisSelect = document.getElementById('varis');
        const tarihInput = document.getElementById('tarih');
        const kalkisSehri = kalkisSelect.value;
        const varisSehri = varisSelect.value;
        const secilenTarih = tarihInput.value; 
        let hataMesaji = '';

        
        if (!kalkisSehri || !varisSehri || !secilenTarih) {
             hataMesaji = 'Lütfen tüm alanları doldurun.';
        }
        
        else if (kalkisSehri === varisSehri) {
            hataMesaji = 'Hata: Kalkış ve Varış şehri aynı olamaz!';
        }
        
        else {
            const bugun = new Date();
            bugun.setHours(0, 0, 0, 0); 
            const secilenTarihObj = new Date(secilenTarih + 'T00:00:00Z'); 
            if (secilenTarihObj < bugun) {
                hataMesaji = 'Hata: Geçmiş bir tarih seçemezsiniz!';
            }
        }

        
        if (hataMesaji) {
            alert(hataMesaji);
            event.preventDefault(); 
        }
        
    });
</script>

</body>
</html>