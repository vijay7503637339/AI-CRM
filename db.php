<?php
$config = require __DIR__ . '/config.php';
$db = $config['db'];
$dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
try { $pdo = new PDO($dsn, $db['user'], $db['pass'], $options); }
catch (Throwable $e) { http_response_code(500); exit('Database connection failed. Check config.php and import database/schema.sql.'); }
