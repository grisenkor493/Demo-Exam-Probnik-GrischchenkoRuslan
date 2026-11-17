<?php
require __DIR__ . '/../app/lib.php';
if (is_admin()) { header('Location: admin.php'); exit; }
$error = null; $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$st = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip=? AND ts>?');
$st->execute([$ip, time() - 300]);
$locked = (int)$st->fetchColumn() >= 5;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if ($locked) $error = 'Слишком много неудачных попыток. Повторите вход через 5 минут.';
    elseif (hash_equals(ADMIN_LOGIN, str_in($_POST, 'login')) & hash_equals(ADMIN_PASSWORD, str_in($_POST, 'password'))) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true; unset($_SESSION['csrf']);
        header('Location: admin.php'); exit;
    } else {
        db()->prepare('INSERT INTO login_attempts(ip,ts) VALUES(?,?)')->execute([$ip, time()]);
        $error = 'Неверный логин или пароль.';
    }
}
page_start('Вход — Светлые Сны'); ?>
<div class="d-flex justify-content-between flex-wrap align-items-center"><h1>Вход</h1></div>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= h($error) ?></div><?php endif; ?>
<form class="my-2" novalidate method="post">
    <?= csrf_field() ?>
    <div class="my-2"><label for="username" class="form-label">Логин</label>
        <input type="text" name="login" class="form-control" id="username" autocomplete="username" required></div>
    <div class="my-2"><label for="password" class="form-label">Пароль</label>
        <input type="password" name="password" class="form-control" id="password" autocomplete="current-password" required></div>
    <div class="d-grid gap-2"><button class="btn btn-primary" type="submit">Войти</button></div>
</form>
<?php page_end();
