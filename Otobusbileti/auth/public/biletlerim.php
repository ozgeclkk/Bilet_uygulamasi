<?php
// AUTH/public/biletlerim.php

require_once '../src/bootstrap.php'; 

// ====================================================================
// 1. ERİŞİM KONTROLÜ
// ====================================================================

$page_title = "Hesap Kontrol Paneli ve Biletlerim";
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'User';

// ====================================================================
// 2. GELİŞTİRİCİ / TEST PANELI MANTIĞI (PHP)
// ====================================================================

$dev_message = '';
$show_dev_panel = (isset($_GET['action']) && $_GET['action'] === 'dev_panel');
$all_users = [];
$available_roles = ['Admin', 'Firma Admin', 'User']; 

if ($show_dev_panel) {
    if (!is_admin())
     {
        http_response_code(403);
        die("<h1>Yetkisiz Erişim (403)</h1><p>Bu Test Paneli sadece 'Admin' rolü için tasarlanmıştır.</p>");
    }

    $all_users = db_select("SELECT id, full_name, email, role, company_id FROM User ORDER BY full_name ASC");

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dev_action']) && $_POST['dev_action'] === 'change_role') {
        
        $target_user_id = trim($_POST['target_user_id'] ?? '');
        $new_role = trim($_POST['new_role'] ?? '');
        $new_company_id = trim($_POST['new_company_id'] ?? '');
        $company_to_save = empty($new_company_id) ? null : $new_company_id;

        if (!empty($target_user_id) && in_array($new_role, $available_roles)) {
            
            $sql = "UPDATE User SET role = :role, company_id = :cid WHERE id = :uid";
            $params = [':role' => $new_role, ':cid' => $company_to_save, ':uid' => $target_user_id];
            db_execute($sql, $params);
            
            $dev_message_text = 'Kullanıcı rolü başarıyla ' . $new_role . ' olarak değiştirildi.';
            
            if ($target_user_id === $user_id) { 
                 $_SESSION['user_role'] = $new_role;
                 $_SESSION['company_id'] = $company_to_save;
            }
            
            if ($target_user_id === $user_id) { 
                if ($new_role === 'Admin') {
                    redirect('admin_panel.php');
                } elseif ($new_role === 'Firma Admin') {
                    redirect('firma_admin_panel.php');
                } else {
                    redirect('biletlerim.php?msg=' . urlencode('Rolünüz User olarak değiştirildi.'));
                }
            } else {
                redirect('biletlerim.php?action=dev_panel&msg=' . urlencode($dev_message_text));
            }
        }
    }
}


// ====================================================================
// 3. NORMAL KULLANICI PANELE AİT VERİ ÇEKME
// ====================================================================

$user_data_query = db_select("SELECT balance, full_name, email FROM User WHERE id = :uid", [':uid' => $user_id]);
$balance = $user_data_query[0]['balance'] ?? 0;
$user_full_name = $user_data_query[0]['full_name'] ?? $_SESSION['full_name'];

$sql = "
    SELECT 
        T.id AS ticket_id, T.status, T.total_price, 
        TR.departure_city, TR.destination_city, TR.departure_time,
        BC.name AS company_name,
        GROUP_CONCAT(BS.seat_number) AS seats_numbers
    FROM Tickets T
    JOIN Trips TR ON T.trip_id = TR.id
    JOIN Bus_Company BC ON TR.company_id = BC.id
    LEFT JOIN Booked_Seats BS ON T.id = BS.ticket_id 
    WHERE T.user_id = :uid AND T.status IN ('active', 'cancelled')
    GROUP BY T.id 
    ORDER BY TR.departure_time DESC
";
$user_tickets = db_select($sql, [':uid' => $user_id]);

$active_tickets = [];
$past_tickets = [];
$current_time_str = date('Y-m-d H:i:s');

foreach ($user_tickets as $ticket) {
    if ($ticket['status'] === 'active' && $ticket['departure_time'] > $current_time_str) {
        $active_tickets[] = $ticket;
    } else {
        $past_tickets[] = $ticket;
    }
}
?>

<?php require_once '../src/inc/header.php'; ?>

<div class="container" style="max-width: 1200px; margin: 50px auto; padding: 20px;">

    <?php 
    if (isset($_GET['msg'])) {
        echo '<div style="background-color:#d4edda; color:#155724; padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold;">' . htmlspecialchars($_GET['msg']) . '</div>';
    }
    ?>

    <?php if ($show_dev_panel): ?>
        
        <div class="dev-panel" style="border: 3px solid #C3B1E1; padding: 25px; margin-bottom: 40px; background-color: #ffffff; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); border-radius: 8px;">
            <h1 style="color:#5D3FD3; border-bottom: 1px solid #E6E6FA; padding-bottom: 10px;">[GELİŞTİRİCİ] ROL VE HESAP YÖNETİM PANELİ</h1>
            <p class="mb-3">Bu panel, sadece test amaçlı olarak kullanıcı rollerini ve firma atamalarını anında değiştirmenizi sağlar. **Rolünüzü değiştirirseniz ilgili panele yönlendirilirsiniz.**</p>

            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="dev_action" value="change_role">

                <div style="display: flex; gap: 15px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label>Hedef Kullanıcı:</label>
                        <select name="target_user_id" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <?php foreach ($all_users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $u['id'] === $user_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['full_name']) ?> (<?= htmlspecialchars($u['role']) ?> - <?= htmlspecialchars($u['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="width: 200px;">
                        <label>Yeni Rol:</label>
                        <select name="new_role" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <?php foreach ($available_roles as $role): ?>
                                <option value="<?= $role ?>"><?= $role ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="width: 200px;">
                        <label>Firma ID (Firma Admin için):</label>
                        <input type="text" name="new_company_id" placeholder="ID Girin (Örn: C42)" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <div style="flex: 0 0 150px;">
                        <button type="submit" style="width: 100%; padding: 10px; background-color: #5D3FD3; /* Mor Buton */ 
                                                        color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                            Rolü Güncelle
                        </button>
                    </div>
                </div>
            </form>
            <p style="text-align: right; margin-top: 15px;"><a href="biletlerim.php" style="color: #5D3FD3; font-weight: bold;">Hesap Özetine Geri Dön</a></p>
        </div>
        
    <?php else: ?>
        
        <h1 style="color:#5D3FD3; text-align:center; margin-bottom: 30px;"><?= $page_title ?></h1>

        <div class="user-info-card" style="display: flex; justify-content: space-between; align-items: center; border: 1px solid #C3B1E1; padding: 25px; margin-bottom: 40px; background-color: #fff; border-radius: 8px;">
            
            <div class="user-details">
                <h2>Hoş Geldiniz, <strong><?= htmlspecialchars($user_full_name) ?></strong></h2>
                <p>E-posta: <?= htmlspecialchars($user_data_query[0]['email']) ?></p>
                <p>Rolünüz: <strong style="color:#5D3FD3;"><?= htmlspecialchars($user_role) ?></strong></p>
            </div>
            
            <div class="balance-details" style="text-align: right;">
                <p style="font-size: 1.1em; margin-bottom: 5px;">Mevcut Sanal Kredi (Balance):</p>
                <strong style="color:green; font-size:2.2em; display: block;"><?= number_format($balance, 2) ?> TL</strong>
                
                <?php if (check_role(['Admin', 'Firma Admin'])): ?>
                    <a href="<?= check_role('Admin') ? 'admin_panel.php' : 'firma_admin_panel.php' ?>" 
                       style="background:#5D3FD3; color:white; padding:8px 12px; border-radius:5px; text-decoration:none; font-weight:bold; display: inline-block; margin-top: 10px;">
                        Yönetici Paneline Git →
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <h2 style="color:#5D3FD3; border-bottom: 2px solid #eee; padding-bottom: 10px;">Aktif Biletlerim (İptal Edilebilir)</h2>
        <?php if (empty($active_tickets)): ?>
            <p style="padding: 20px; text-align:center; background-color: #f7f7f7;">Yakın zamanda planlanmış aktif biletiniz bulunmamaktadır.</p>
        <?php else: ?>
            <table class="tickets-table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th style="padding: 10px; border: 1px solid #ddd;">Firma</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Güzergah</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Kalkış Zamanı</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Koltuklar</th>
                        <th style="padding: 10px; border: 1px solid #ddd;">Fiyat</th>
                        <th style="padding: 10px; border: 1px solid #ddd; width: 250px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_tickets as $ticket): 
                        $remaining_time_seconds = strtotime($ticket['departure_time']) - time();
                        $can_cancel = $remaining_time_seconds >= 3600; 
                    ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($ticket['company_name']) ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($ticket['departure_city']) ?> → <?= htmlspecialchars($ticket['destination_city']) ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <strong style="color:<?= $can_cancel ? 'green' : 'red' ?>;">
                                <?= date('d.m.Y H:i', strtotime($ticket['departure_time'])) ?>
                            </strong>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars(str_replace(',', ', ', $ticket['seats_numbers'])) ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= number_format($ticket['total_price'], 2) ?> TL</td>
                        <td style="padding: 10px; border: 1px solid #ddd; text-align:center;">
                            <a href="pdf_indir.php?ticket_id=<?= $ticket['ticket_id'] ?>" style="color:#5D3FD3; margin-right: 15px; font-weight: bold;">PDF İndir</a>
                            
                            <?php if ($can_cancel): ?>
                                <a href="bilet_iptal.php?ticket_id=<?= $ticket['ticket_id'] ?>" style="color:#dc3545; font-weight:bold;">İptal Et</a>
                            <?php else: ?>
                                <span style="color:#777; font-size: 0.9em; font-weight: bold;">İptal Süresi Doldu</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2 style="color:#5D3FD3; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-top: 50px;">Geçmiş, Tamamlanmış ve İptal Edilen Biletler</h2>
            <?php if (empty($past_tickets)): ?>
                <p style="padding: 20px; text-align:center; background-color: #f7f7f7;">Daha önce tamamlanmış veya iptal edilmiş biletiniz bulunmamaktadır.</p>
            <?php else: ?>
                <table class="tickets-table" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="background-color: #f2f2f2;">
                            <th style="padding: 10px; border: 1px solid #ddd;">Firma</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Güzergah</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Kalkış Zamanı</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Koltuklar</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Durum</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($past_tickets as $ticket): 
                            $status_color = $ticket['status'] === 'cancelled' ? '#dc3545' : 'green';
                            $status_text = $ticket['status'] === 'cancelled' ? 'İPTAL EDİLDİ (İade Yapıldı)' : 'TAMAMLANDI';
                        ?>
                        <tr style="border-bottom: 1px solid #eee; opacity: 0.8;">
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($ticket['company_name']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($ticket['departure_city']) ?> → <?= htmlspecialchars($ticket['destination_city']) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= date('d.m.Y H:i', strtotime($ticket['departure_time'])) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars(str_replace(',', ', ', $ticket['seats_numbers'])) ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd; color:<?= $status_color ?>; font-weight:bold;"><?= $status_text ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align:center;">
                                <?php if ($ticket['status'] !== 'cancelled'): ?>
                                    <a href="pdf_indir.php?ticket_id=<?= $ticket['ticket_id'] ?>" style="color:#007bff;">PDF İndir</a>
                                <?php else: ?>
                                    <span style="color: #777;">Yok</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php endif; ?>
                <?php endif; ?>


</div>

<?php require_once '../src/inc/footer.php'; ?>