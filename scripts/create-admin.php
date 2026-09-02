<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (PHP_SAPI !== 'cli') {
    exit("Run this script from the command line.\n");
}

$name = $argv[1] ?? null;
$email = $argv[2] ?? null;
$password = $argv[3] ?? null;

if (!$name || !$email || !$password) {
    exit("Usage: php scripts/create-admin.php \"Admin Name\" admin@example.com strong-password\n");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Invalid email address.\n");
}

if (strlen($password) < 8) {
    exit("Password must be at least 8 characters.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = db()->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'admin')");
$stmt->execute([$name, strtolower($email), $hash]);

echo "Admin user created successfully.\n";
