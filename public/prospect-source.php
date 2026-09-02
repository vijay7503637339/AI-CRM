<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: lead-campaigns.php'); exit; }
verify_csrf();
$name = trim($_POST['name'] ?? '');
$seedUrl = trim($_POST['seed_url'] ?? '');
$category = trim($_POST['category'] ?? '');
$location = trim($_POST['location'] ?? '');
if ($name === '' || $seedUrl === '' || !filter_var($seedUrl, FILTER_VALIDATE_URL)) { header('Location: lead-campaigns.php?error=invalid_source'); exit; }
try {
    $parts = parse_url($seedUrl);
    if (!in_array(strtolower($parts['scheme'] ?? ''), ['http','https'], true)) throw new InvalidArgumentException('Only HTTP/HTTPS sources are allowed.');
    db()->prepare('INSERT INTO prospect_sources (name,seed_url,category,location,created_by) VALUES (?,?,?,?,?)')->execute([$name,$seedUrl,$category ?: null,$location ?: null,current_user()['id']]);
    header('Location: lead-campaigns.php?source=created');
} catch (Throwable $e) { header('Location: lead-campaigns.php?error=' . rawurlencode($e->getMessage())); }
exit;
