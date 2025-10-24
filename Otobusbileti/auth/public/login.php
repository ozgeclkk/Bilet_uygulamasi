<?php

require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../src/login.php';
?>

<?php view('header', ['title' => 'Login']) ?>

<?php if (isset($errors['login'])) : ?>
    <div class="alert alert-error">
        <?= $errors['login'] ?>
    </div>
<?php endif ?>

    <form action="login.php" method="post" class="auth-form">
        <h1>Giriş</h1>
        <div>
            <label for="full_name">Kullanıcı Adı:</label>
            <input type="text" name="full_name" id="full_name" value="<?= $inputs['full_name'] ?? '' ?>">
            <small><?= $errors['full_name'] ?? '' ?></small>
        </div>
        <div>
            <label for="password">Şifre:</label>
            <input type="password" name="password" id="password">
            <small><?= $errors['password'] ?? '' ?></small>
        </div>
        <section>
            <button type="submit">Giriş</button>
            <a href="register.php">Kayıt Ol</a>
        </section>
    </form>

<?php view('footer') ?>