<?php
require __DIR__ . '/../app/lib.php';
$cat = str_in($_GET, 'category'); $price = str_in($_GET, 'max_price'); $seats = str_in($_GET, 'seats');
$errors = []; $where = []; $args = [];
if ($cat !== '') {
    if (in_array($cat, CATS, true)) { $where[] = 'category=?'; $args[] = $cat; } else $errors[] = 'Выберите категорию из списка.';
}
if ($price !== '') {
    if (ctype_digit($price) && (int)$price > 0 && (int)$price < 100000000) { $where[] = 'price<=?'; $args[] = (int)$price; }
    else $errors[] = 'Цена должна быть целым положительным числом.';
}
if ($seats !== '') {
    if (ctype_digit($seats) && (int)$seats > 0 && (int)$seats <= 20) { $where[] = 'seats>=?'; $args[] = (int)$seats; }
    else $errors[] = 'Количество мест должно быть числом от 1 до 20.';
}
$rooms = [];
if (!$errors) {
    // случайный набор номеров (по заданию), с учётом выбранных фильтров
    $rand = DB_DRIVER === 'mysql' ? 'RAND()' : 'RANDOM()';
    $st = db()->prepare('SELECT * FROM rooms' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY ' . $rand . ' LIMIT ' . (int)CATALOG_LIMIT);
    $st->execute($args);
    $rooms = $st->fetchAll();
}
$am = amenities_map();
page_start('Каталог номеров — Светлые Сны'); ?>
<div class="d-flex justify-content-between flex-wrap">
    <h1>Каталог номеров</h1>
    <form method="get" action="catalog.php" class="d-flex flex-wrap gap-2 align-items-start">
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                Категории<?= $cat !== '' && in_array($cat, CATS, true) ? ': ' . h($cat) : '' ?>
            </button>
            <ul class="dropdown-menu p-2">
                <?php foreach (CATS as $i => $c): ?><li><div class="form-check">
                    <input class="form-check-input" type="radio" name="category" id="cat<?= $i ?>" value="<?= h($c) ?>" <?= $cat === $c ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cat<?= $i ?>"><?= h($c) ?></label></div></li><?php endforeach; ?>
            </ul>
        </div>
        <input class="form-control w-auto" name="max_price" value="<?= h($price) ?>" inputmode="numeric" placeholder="Цена до, ₽" aria-label="Цена за сутки до">
        <input class="form-control w-auto" name="seats" value="<?= h($seats) ?>" inputmode="numeric" placeholder="Мест от" aria-label="Количество мест от">
        <button class="btn btn-primary" type="submit">Применить</button>
        <a class="btn btn-danger" href="catalog.php">Сбросить фильтр</a>
    </form>
</div>
<?php if ($errors): ?><div class="alert alert-danger my-2" role="alert"><?php foreach ($errors as $e) echo h($e), '<br>'; ?></div><?php endif; ?>
<div class="d-flex justify-content-around flex-wrap align-items-center">
<?php foreach ($rooms as $r): ?>
    <div class="card">
        <img src="img/rooms/<?= h($r['image']) ?>" class="card-img-top" alt="<?= h($r['category']) ?>">
        <div class="card-body">
            <h3>Категория: <?= h($r['category']) ?></h3>
            <h5>Цена: <?= h(rub($r['price'])) ?> / сутки</h5>
            <h5>Характеристики:</h5>
            <ul class="list-group">
                <li class="list-group-item">Мест: <?= (int)$r['seats'] ?>, площадь: <?= (int)round($r['area']) ?> м²</li>
                <?php foreach ($am[$r['id']] ?? [] as $a): ?><li class="list-group-item"><?= h($a) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <div class="d-grid gap-2"><a href="order.php?room=<?= (int)$r['id'] ?>" class="btn btn-success">Забронировать</a></div>
    </div>
<?php endforeach; ?>
<?php if (!$rooms && !$errors): ?><div class="alert alert-secondary w-100 my-3">Нет номеров по выбранным условиям. Измените фильтр или нажмите «Сбросить фильтр».</div><?php endif; ?>
</div>
<?php page_end();
