<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/roles.php';

$config = require '/home/quizmani/acepathhub_secure/db.php';

date_default_timezone_set('Africa/Lagos');

$conn = mysqli_connect(
    $config['host'],
    $config['user'],
    $config['pass'],
    $config['name']
);

if (!$conn) {
    http_response_code(500);
    exit('Database connection failed.');
}

mysqli_set_charset($conn, 'utf8mb4');