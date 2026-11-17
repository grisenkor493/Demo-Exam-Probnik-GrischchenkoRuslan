<?php
date_default_timezone_set('Europe/Moscow');
const DB_DRIVER = 'sqlite';          // 'sqlite' (без установки) или 'mysql' (XAMPP)
const SQLITE_PATH = __DIR__ . '/hotel.db';
const MYSQL_HOST = '127.0.0.1';
const MYSQL_NAME = 'hotel';
const MYSQL_USER = 'root';
const MYSQL_PASS = '';
const ADMIN_LOGIN = 'hotel123';
const ADMIN_PASSWORD = 'adminHotel';
const CATS = ['Стандарт', 'Студия', 'Люкс'];
const CATALOG_LIMIT = 6;          // сколько случайных номеров выводить в каталоге
