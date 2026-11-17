<?php
require __DIR__ . '/../app/lib.php';
page_start('Светлые Сны — бронирование номеров'); ?>
<div class="d-flex justify-content-between flex-wrap align-items-center"><h1>Светлые Сны</h1></div>
<div class="card mx-auto" style="max-width:100%">
    <div class="card-body">
        <h3>Приезжайте как гости, уезжайте как друзья!</h3>
        <p class="mb-3">Уютная гостиница в Москве. Выберите номер в каталоге, отправьте заявку на бронирование — администратор уточнит детали по телефону и подтвердит заезд.</p>
        <ul class="list-group mb-3">
            <li class="list-group-item">Стандарт — от 3 000 ₽ / сутки</li>
            <li class="list-group-item">Студия — от 4 600 ₽ / сутки</li>
            <li class="list-group-item">Люкс — от 8 500 ₽ / сутки</li>
        </ul>
        <div class="d-grid gap-2"><a href="catalog.php" class="btn btn-primary">Перейти в каталог номеров</a></div>
    </div>
</div>
<?php page_end();
