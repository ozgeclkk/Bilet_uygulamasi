<?php
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/register.php';
?>

<?php view('header', ['title' => 'Register']) ?>

<form action="register.php" method="post" class="auth-form">
    <h1>Kayıt Ol</h1>

    <div>
        <label for="full_name">Kullanıcı Adı:</label>
        <input type="text" name="full_name" id="full_name" value="<?= $inputs['full_name'] ?? '' ?>"
               class="<?= error_class($errors, 'full_name') ?>">
        <small><?= $errors['full_name'] ?? '' ?></small>
    </div>

    <div>
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?= $inputs['email'] ?? '' ?>"
               class="<?= error_class($errors, 'email') ?>">
        <small><?= $errors['email'] ?? '' ?></small>
    </div>

    <div>
        <label for="password">Şifre:</label>
        <input type="password" name="password" id="password" value="<?= $inputs['password'] ?? '' ?>"
               class="<?= error_class($errors, 'password') ?>">
        <small><?= $errors['password'] ?? '' ?></small>
    </div>

    <div>
        <label for="password2">Şifreyi Tekrar Girin:</label>
        <input type="password" name="password2" id="password2" value="<?= $inputs['password2'] ?? '' ?>"
               class="<?= error_class($errors, 'password2') ?>">
        <small><?= $errors['password2'] ?? '' ?></small>
    </div>

    <div>
        <label for="agree">
            <input type="checkbox" name="agree" id="agree" value="checked" <?= $inputs['agree'] ?? '' ?> />
            <a href="#" title="term of services">Hizmet Şartlarını</a>
            kabul
            ediyorum.
            
        </label>
        <small><?= $errors['agree'] ?? '' ?></small>
    </div>

    <button type="submit">Kayıt Ol</button>

    <footer>Zaten üye misin? <a href="login.php">Giriş yapın</a></footer>

</form>

<?php view('footer') ?>