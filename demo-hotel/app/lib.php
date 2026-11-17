<?php
require __DIR__ . '/config.php';
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'");

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function rub($n): string { return number_format((int)$n, 0, '', ' ') . ' ₽'; }
function dmy(string $iso): string { return implode('.', array_reverse(explode('-', $iso))); }
function str_in(array $src, string $key): string { return (isset($src[$key]) && is_string($src[$key])) ? trim($src[$key]) : ''; }

// ---------- база данных ----------
function seed_data(): array {
    $std = ['Полный санузел', 'Двуспальная кровать', 'Телевизор'];
    $stu = ['Кухонный уголок', 'Полный санузел', 'Двуспальная кровать', 'Телевизор', 'Холодильник'];
    $lux = ['Двухкомнатный', 'Полный санузел', 'Двуспальная кровать', 'Телевизор', 'Холодильник', 'Кондиционер'];
    return [
        ['Стандарт', 3000, 2, 18, 'standard-1.jpg', $std], ['Стандарт', 3400, 2, 20, 'standard-2.jpg', $std], ['Стандарт', 3800, 3, 24, 'standard-3.jpg', $std],
        ['Студия', 4600, 2, 28, 'studio-1.jpg', $stu], ['Студия', 5400, 3, 32, 'studio-2.jpg', $stu], ['Студия', 6200, 4, 36, 'studio-3.jpg', $stu],
        ['Люкс', 8500, 2, 50, 'lux-1.jpg', $lux], ['Люкс', 11500, 3, 62, 'lux-2.jpg', $lux], ['Люкс', 15000, 4, 80, 'lux-3.jpg', $lux],
    ];
}

function init_sqlite(PDO $pdo): void {
    $pdo->exec("
    CREATE TABLE rooms(id INTEGER PRIMARY KEY, category TEXT NOT NULL CHECK(category IN ('Стандарт','Студия','Люкс')),
      price INTEGER NOT NULL CHECK(price>0), seats INTEGER NOT NULL CHECK(seats>0), area REAL NOT NULL, image TEXT NOT NULL);
    CREATE TABLE amenities(id INTEGER PRIMARY KEY, name TEXT NOT NULL UNIQUE);
    CREATE TABLE room_amenities(room_id INTEGER REFERENCES rooms(id) ON DELETE CASCADE,
      amenity_id INTEGER REFERENCES amenities(id), PRIMARY KEY(room_id, amenity_id));
    CREATE TABLE bookings(id INTEGER PRIMARY KEY, room_id INTEGER NOT NULL REFERENCES rooms(id) ON DELETE CASCADE,
      first_name TEXT NOT NULL, last_name TEXT NOT NULL, phone TEXT NOT NULL, email TEXT NOT NULL,
      check_in TEXT NOT NULL, check_out TEXT NOT NULL,
      status TEXT NOT NULL DEFAULT 'Новая' CHECK(status IN ('Новая','Одобрена','Отклонена')),
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP);
    CREATE TABLE login_attempts(id INTEGER PRIMARY KEY, ip TEXT NOT NULL, ts INTEGER NOT NULL);");
    $insRoom = $pdo->prepare('INSERT INTO rooms(category,price,seats,area,image) VALUES(?,?,?,?,?)');
    $insAm = $pdo->prepare('INSERT OR IGNORE INTO amenities(name) VALUES(?)');
    $selAm = $pdo->prepare('SELECT id FROM amenities WHERE name=?');
    $link = $pdo->prepare('INSERT INTO room_amenities(room_id,amenity_id) VALUES(?,?)');
    foreach (seed_data() as [$cat, $price, $seats, $area, $img, $ams]) {
        $insRoom->execute([$cat, $price, $seats, $area, $img]);
        $rid = (int)$pdo->lastInsertId();
        foreach ($ams as $a) {
            $insAm->execute([$a]); $selAm->execute([$a]);
            $link->execute([$rid, (int)$selAm->fetchColumn()]);
        }
    }
}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    if (DB_DRIVER === 'mysql') {
        $pdo = new PDO('mysql:host=' . MYSQL_HOST . ';dbname=' . MYSQL_NAME . ';charset=utf8mb4', MYSQL_USER, MYSQL_PASS);
        $new = false;
    } else {
        $new = !file_exists(SQLITE_PATH);
        $pdo = new PDO('sqlite:' . SQLITE_PATH);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    if (DB_DRIVER !== 'mysql') $pdo->exec('PRAGMA foreign_keys=ON');
    if ($new) init_sqlite($pdo);
    return $pdo;
}

function amenities_map(): array {
    $map = [];
    foreach (db()->query('SELECT ra.room_id, a.name FROM room_amenities ra JOIN amenities a ON a.id=ra.amenity_id ORDER BY ra.room_id, a.id') as $r)
        $map[$r['room_id']][] = $r['name'];
    return $map;
}

// ---------- даты и брони ----------
function pdate(string $s): ?string {   // принимает ГГГГ-ММ-ДД (type=date) и ДД.ММ.ГГГГ; возвращает ISO
    foreach ([['/^\d{4}-\d{2}-\d{2}$/D', 'Y-m-d'], ['/^\d{2}\.\d{2}\.\d{4}$/D', 'd.m.Y']] as [$rx, $f]) {
        if (preg_match($rx, $s)) {
            $d = DateTime::createFromFormat('!' . $f, $s);
            return ($d && $d->format($f) === $s) ? $d->format('Y-m-d') : null;
        }
    }
    return null;
}
function overlap(int $roomId, string $ci, string $co, int $exclude = 0): bool {
    $st = db()->prepare("SELECT 1 FROM bookings WHERE room_id=? AND status='Одобрена' AND id<>? AND check_in<? AND check_out>? LIMIT 1");
    $st->execute([$roomId, $exclude, $co, $ci]);
    return (bool)$st->fetchColumn();
}

// ---------- безопасность ----------
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf()) . '">'; }
function csrf_check(): void {
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals((string)($_SESSION['csrf'] ?? ''), $t)) {
        http_response_code(400);
        fail_page('Некорректный запрос или устарела форма. Обновите страницу и повторите.');
    }
}
function is_admin(): bool { return !empty($_SESSION['admin']); }
function require_admin(): void { if (!is_admin()) { header('Location: login.php'); exit; } }
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }

// ---------- общий шаблон ----------
function page_start(string $title = 'Светлые Сны'): void { ?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/index.css">
    <title><?= h($title) ?></title>
</head>
<body class="container">
<header class="d-flex flex-wrap justify-content-center py-3">
    <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none">
        <span class="fs-4 mx-2 fw-medium">Светлые Сны</span>
    </a>
    <ul class="nav">
        <li class="nav-item"><a href="index.php" class="nav-link">Приезжайте как гости, уезжайте как друзья!</a></li>
        <li class="nav-item"><a href="catalog.php" class="nav-link fw-medium">Каталог</a></li>
        <?php if (is_admin()): ?>
        <li class="nav-item"><a href="admin.php" class="nav-link">Заявки</a></li>
        <li class="nav-item"><form method="post" action="logout.php"><?= csrf_field() ?><button class="nav-link" type="submit">Выйти</button></form></li>
        <?php endif; ?>
    </ul>
</header>
<?php foreach ($_SESSION['flash'] ?? [] as [$type, $msg]): ?>
<div class="alert <?= $type === 'ok' ? 'alert-success' : 'alert-danger' ?> text-center" role="alert"><?= h($msg) ?></div>
<?php endforeach; unset($_SESSION['flash']); ?>
<main>
<?php }

function page_end(array $scripts = []): void { ?>
</main>
<footer class="py-2 my-2">
    <ul class="nav justify-content-between align-items-center">
        <li class="nav-item"><a href="#" class="nav-link text-body-secondary">ул. г.Москва, ул. Ивовая, 48</a></li>
        <li class="nav-item"><a href="#" class="nav-link text-body-secondary">Время работы: Пн-Пт, с 8:00-17:00</a></li>
        <li class="nav-item"><a href="tel:88005553535" class="nav-link text-body-secondary">тел. 8 (800) 555 - 35 - 35</a></li>
        <li class="nav-item"><a href="mailto:обращения@СветлыеСны.рф" class="nav-link text-body-secondary">Email: обращения@СветлыеСны.рф</a></li>
        <?php if (!is_admin()): ?><li class="nav-item"><a href="login.php" class="nav-link text-body-secondary">Вход для администратора</a></li><?php endif; ?>
    </ul>
</footer>
<script src="js/bootstrap.bundle.min.js"></script>
<?php foreach ($scripts as $s): ?><script src="js/<?= h($s) ?>"></script>
<?php endforeach; ?>
</body>
</html>
<?php }

function fail_page(string $msg): void {
    page_start('Ошибка'); ?>
<h1>Ошибка</h1><p><?= h($msg) ?></p><a class="btn btn-primary" href="catalog.php">В каталог</a>
<?php page_end(); exit; }
