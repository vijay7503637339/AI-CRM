<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth();

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $stmt = db()->prepare("SELECT l.*, s.name AS stage_name FROM leads l JOIN pipeline_stages s ON s.id=l.stage_id WHERE l.name LIKE ? OR l.company LIKE ? OR l.email LIKE ? OR l.phone LIKE ? ORDER BY l.created_at DESC");
    $like = "%{$q}%";
    $stmt->execute([$like,$like,$like,$like]);
    $leads = $stmt->fetchAll();
} else {
    $leads = db()->query("SELECT l.*, s.name AS stage_name FROM leads l JOIN pipeline_stages s ON s.id=l.stage_id ORDER BY l.created_at DESC")->fetchAll();
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Leads | AI CRM</title><link rel="stylesheet" href="assets/app.css"></head><body>
<header class="topbar"><div class="brand">AI CRM</div><nav><a href="index.php">Dashboard</a><a href="leads.php">Leads</a><a href="pipeline.php">Pipeline</a><a href="logout.php">Logout</a></nav></header>
<main class="container"><div class="page-head"><div><p class="eyebrow">CRM</p><h1>Leads</h1><p class="muted">Manage prospects in one place.</p></div><a class="btn primary" href="lead-create.php">+ Add lead</a></div>
<section class="panel"><form class="search" method="get"><input name="q" value="<?= e($q) ?>" placeholder="Search name, company, email or phone"><button class="btn" type="submit">Search</button></form><div class="table-wrap"><table><thead><tr><th>Name</th><th>Contact</th><th>Source</th><th>Stage</th><th>Value</th><th>Follow-up</th></tr></thead><tbody><?php if (!$leads): ?><tr><td colspan="6" class="empty">No matching leads.</td></tr><?php endif; ?><?php foreach($leads as $lead): ?><tr><td><strong><?= e($lead['name']) ?></strong><br><span class="muted small"><?= e($lead['company']) ?></span></td><td><?= e($lead['phone']) ?><br><span class="muted small"><?= e($lead['email']) ?></span></td><td><?= e($lead['source']) ?: '—' ?></td><td><span class="pill"><?= e($lead['stage_name']) ?></span></td><td>₹<?= number_format((float)$lead['estimated_value'],0) ?></td><td><?= $lead['next_follow_up'] ? e(date('d M Y, h:i A', strtotime($lead['next_follow_up']))) : '—' ?></td></tr><?php endforeach; ?></tbody></table></div></section></main></body></html>
