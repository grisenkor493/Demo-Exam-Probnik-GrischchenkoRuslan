<?php
require __DIR__ . '/../app/lib.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); $_SESSION = []; session_destroy(); }
header('Location: index.php');
