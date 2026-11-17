<?php
require __DIR__ . '/../app/lib.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT); $act = str_in($_POST, 'action'); $b = false;
    if ($id) { $st = db()->prepare('SELECT * FROM bookings WHERE id=?'); $st->execute([$id]); $b = $st->fetch(); }
    if (!$b) flash('error', 'Заявка не найдена.');
    elseif ($act === 'approve') {
        if (overlap((int)$b['room_id'], $b['check_in'], $b['check_out'], $id)) flash('error', "Заявку №$id нельзя одобрить: на эти даты уже есть одобренная бронь.");
        else { db()->prepare("UPDATE bookings SET status='Одобрена' WHERE id=?")->execute([$id]); flash('ok', "Заявка №$id одобрена."); }
    } elseif ($act === 'reject') {
        db()->prepare("UPDATE bookings SET status='Отклонена' WHERE id=?")->execute([$id]); flash('ok', "Заявка №$id отклонена.");
    } elseif ($act === 'delete') {
        db()->prepare('DELETE FROM bookings WHERE id=?')->execute([$id]); flash('ok', "Заявка №$id удалена.");
    } else flash('error', 'Неизвестное действие.');
    header('Location: admin.php'); exit;
}
$rows = db()->query('SELECT b.*, r.category, r.price FROM bookings b JOIN rooms r ON r.id=b.room_id ORDER BY b.id DESC')->fetchAll();
$badge = ['Новая' => 'text-bg-warning', 'Одобрена' => 'text-bg-success', 'Отклонена' => 'text-bg-secondary'];
$acts = [['approve', 'Одобрить', 'btn-success'], ['reject', 'Отклонить', 'btn-warning'], ['delete', 'Удалить', 'btn-danger']];
page_start('Панель администратора — Светлые Сны'); ?>
<div class="d-flex justify-content-between flex-wrap align-items-center"><h1>Панель администратора</h1></div>
<div class="d-flex justify-content-around flex-wrap align-items-center">
<?php foreach ($rows as $b): ?>
    <div class="card">
        <div class="card-body">
            <h5>Заявка №<?= (int)$b['id'] ?> <span class="badge <?= $badge[$b['status']] ?? '' ?>"><?= h($b['status']) ?></span></h5>
            <h5>Фамилия: <?= h($b['last_name']) ?></h5>
            <h5>Имя: <?= h($b['first_name']) ?></h5>
            <h5>Телефон: <?= h($b['phone']) ?></h5>
            <ul class="list-group">
                <li class="list-group-item">Номер: <?= h($b['category']) ?>, <?= h(rub($b['price'])) ?> / сутки</li>
                <li class="list-group-item">Email: <?= h($b['email']) ?></li>
                <li class="list-group-item">Дата заезда: <?= h(dmy($b['check_in'])) ?></li>
                <li class="list-group-item">Дата выезда: <?= h(dmy($b['check_out'])) ?></li>
            </ul>
        </div>
        <div class="d-grid gap-2">
            <?php foreach ($acts as [$act, $label, $cls]):
                if (($act === 'approve' && $b['status'] === 'Одобрена') || ($act === 'reject' && $b['status'] === 'Отклонена')) continue; ?>
            <form method="post" action="admin.php" class="d-grid"><?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="<?= $act ?>">
                <button class="btn <?= $cls ?>" type="submit"><?= $label ?></button></form>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
<?php if (!$rows): ?><div class="alert alert-secondary w-100 my-3">Заявок пока нет.</div><?php endif; ?>
</div>
<?php page_end();
