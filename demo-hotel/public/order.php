<?php
require __DIR__ . '/../app/lib.php';
$id = filter_var($_GET['room'] ?? '', FILTER_VALIDATE_INT);
$room = false;
if ($id) { $st = db()->prepare('SELECT * FROM rooms WHERE id=?'); $st->execute([$id]); $room = $st->fetch(); }
if (!$room) { http_response_code(404); fail_page('Номер не найден.'); }

$errors = []; $f = []; $submitted = $_SERVER['REQUEST_METHOD'] === 'POST'; $today = date('Y-m-d');
if ($submitted) {
    csrf_check();
    foreach (['first_name', 'last_name', 'phone', 'email', 'check_in', 'check_out'] as $k) $f[$k] = str_in($_POST, $k);
    foreach ([['first_name', 'имя'], ['last_name', 'фамилию']] as [$k, $label]) {
        if ($f[$k] === '') $errors[$k] = "Пожалуйста, введите $label";
        elseif (mb_strlen($f[$k]) > 50 || !preg_match('/^[А-Яа-яЁё .\-]+$/u', $f[$k])) $errors[$k] = 'Допустимы кириллица, пробелы, точки и тире (до 50 символов)';
    }
    if ($f['phone'] === '') $errors['phone'] = 'Пожалуйста, введите номер телефона';
    elseif (!preg_match('/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/D', $f['phone'])) $errors['phone'] = 'Формат телефона: +7(999)123-45-67';
    if ($f['email'] === '') $errors['email'] = 'Пожалуйста, введите email';
    elseif (mb_strlen($f['email']) > 100 || !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/D', $f['email'])) $errors['email'] = 'Введите адрес вида name@example.com';
    $a = pdate($f['check_in']); $b = pdate($f['check_out']);
    if ($f['check_in'] === '') $errors['check_in'] = 'Пожалуйста, введите дату заезда';
    elseif (!$a) $errors['check_in'] = 'Укажите существующую дату';
    elseif ($a < $today) $errors['check_in'] = 'Дата заезда не может быть раньше текущей даты';
    if ($f['check_out'] === '') $errors['check_out'] = 'Пожалуйста, введите дату выезда';
    elseif (!$b) $errors['check_out'] = 'Укажите существующую дату';
    elseif ($a && $b <= $a) $errors['check_out'] = 'Дата выезда должна быть позже даты заезда';
    if (!$errors && overlap((int)$room['id'], $a, $b)) {
        $errors['check_in'] = 'Номер уже забронирован на эти даты (занятые дни отмечены в календаре)';
        $errors['check_out'] = 'Выберите другой период';
    }
    if (!$errors) {
        db()->prepare('INSERT INTO bookings(room_id,first_name,last_name,phone,email,check_in,check_out) VALUES(?,?,?,?,?,?,?)')
            ->execute([$room['id'], $f['first_name'], $f['last_name'], $f['phone'], $f['email'], $a, $b]);
        flash('ok', 'Заявка успешно отправлена!');
        header('Location: order.php?room=' . (int)$room['id']); exit;
    }
}
$st = db()->prepare("SELECT check_in, check_out FROM bookings WHERE room_id=? AND status='Одобрена' AND check_out>?");
$st->execute([$room['id'], $today]);
$busy = array_map(fn($r) => [$r['check_in'], $r['check_out']], $st->fetchAll());

function field(string $name, string $label, string $id, string $col, string $type = 'text', string $extra = ''): void {
    global $errors, $f, $submitted;
    $cls = isset($errors[$name]) ? 'is-invalid' : ($submitted ? 'is-valid' : ''); ?>
    <div class="<?= $col ?>">
        <label for="<?= $id ?>" class="form-label"><?= h($label) ?></label>
        <input type="<?= $type ?>" name="<?= $name ?>" class="form-control <?= $cls ?>" id="<?= $id ?>" value="<?= h($f[$name] ?? '') ?>" <?= $extra ?>>
        <div class="invalid-feedback"><?= h($errors[$name] ?? '') ?></div>
    </div>
<?php }

page_start('Бронирование номера — Светлые Сны'); ?>
<div class="d-flex justify-content-between flex-wrap align-items-center"><h1>Бронирование номера</h1></div>
<div class="alert alert-secondary">Выбранный номер: <b><?= h($room['category']) ?></b>, <?= h(rub($room['price'])) ?> / сутки, мест: <?= (int)$room['seats'] ?></div>
<div class="cals" id="cal" data-busy="<?= h(json_encode($busy)) ?>" data-today="<?= h($today) ?>"></div>
<p class="form-text" id="calnote">Нажмите на дату заезда, затем на дату выезда или введите даты вручную в формате ДД.ММ.ГГГГ. Красным отмечены занятые ночи.</p>
<form class="row g-3 needs-validation my-2" novalidate method="post" action="order.php?room=<?= (int)$room['id'] ?>">
    <?= csrf_field() ?>
    <?php field('first_name', 'Имя', 'validationCustom01', 'col-md-4', 'text', 'maxlength="50" autocomplete="given-name"');
    field('last_name', 'Фамилия', 'validationCustom02', 'col-md-4', 'text', 'maxlength="50" autocomplete="family-name"');
    field('phone', 'Телефон', 'validationCustomPhone', 'col-md-4', 'text', 'autocomplete="tel"');
    field('email', 'Почта', 'validationCustom03', 'col-md-6', 'text', 'maxlength="100" autocomplete="email"');
    field('check_in', 'Дата заезда (ДД.ММ.ГГГГ)', 'validationCustom04', 'col-md-3', 'text', 'placeholder="ДД.ММ.ГГГГ" inputmode="numeric" maxlength="10" autocomplete="off"');
    field('check_out', 'Дата выезда (ДД.ММ.ГГГГ)', 'validationCustom05', 'col-md-3', 'text', 'placeholder="ДД.ММ.ГГГГ" inputmode="numeric" maxlength="10" autocomplete="off"'); ?>
    <div class="d-grid gap-2"><button class="btn btn-primary" type="submit">Отправить заявку</button></div>
</form>
<?php page_end(['jquery-3.7.1.slim.min.js', 'jquery.inputmask.min.js', 'index.js', 'booking.js']);
