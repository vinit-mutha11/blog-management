<?php
declare(strict_types=1);

$dbHost = 'localhost';
$dbName = 'blog';
$dbUsername = 'root';
$dbPassword = 'vinitdatabase';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUsername, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('The site could not connect to its database. Check config/database.php and your MySQL server.');
}
