<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth();

$stats = [
    'total' => (int) db()->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
    'open' => (int) db()->query("SELECT COUNT(*) FROM leads WHERE status='open'")->fetchColumn(),
    'won' => (int) db()->query("SELECT COUNT(*) FROM leads WHERE status='won'")->fetchColumn(),
    'value' => (float) db()->query("SELECT COALESCE(SUM(estimated_value),0) FROM leads WHERE status='open'")->fetchColumn(),
];

$recent = db()->query("SELECT l.*, s.name AS stage_name FROM leads l JOIN pipeline_stages s ON s.id=l.stage_id ORDER BY l.created_at DESC LIMIT 8")->fetchAll();
$user = current_user();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard | AI CRM</title><link rel="stylesheet" href="assets/app.css"></head><body>
<header class="topbar"><div class="brand">AI CRM</div><nav><a href="index.php">Dashboard</a><a href="leads.php">Leads</a><a href="pipeline.php">Pipeline</a><a href="logout.php">Logout</a></nav></header>
<main class="container">
<div class="page-head"><div><p class="eyebrow">SALES WORKSPACE</p><h1>Dashboard</h1><p class="muted">Welcome, <?= e($user['name'] ?? 'User') ?>.</p></div><a class="btn primary" href="lead-create.php">+ Add lead</a></div>
<section class="stats"><article><span>Total leads</span><strong><?= $stats['total'] ?></strong></article><article><span>Open leads</span><strong><?= $stats['open'] ?></strong></article><article><span>Won</span><strong><?= $stats['won'] ?></strong></article><article><span>Open pipeline</span><strong>₹<?= number_format($stats['value'], 0) ?></strong></article></section>
<section class="panel"><div class="panel-head"><h2>Recent leads</h2><a href="leads.php">View all</a></div><div class="table-wrap"><table><thead><tr><th>Name</th><th>Company</th><th>Stage</th><th>Value</th><th>AI score</th></tr></thead><tbody><?php if (!$recent): ?><tr><td colspan="5" class="empty">No leads yet. Add your first lead.</td></tr><?php endif; ?><?php foreach ($recent as $lead): ?><tr><td><?= e($lead['name']) ?></td><td><?= e($lead['company']) ?></td><td><span class="pill"><?= e($lead['stage_name']) ?></span></td><td>₹<?= number_format((float)$lead['estimated_value'],0) ?></td><td><?= $lead['ai_score'] !== null ? (int)$lead['ai_score'].'/100' : 'Pending' ?></td></tr><?php endforeach; ?></tbody></table></div></section>
</main></body></html>
