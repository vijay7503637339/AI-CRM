<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/AI/LeadAgent.php';
require_auth();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(404); exit('Lead not found'); }

$stmt = db()->prepare('SELECT * FROM leads WHERE id=? LIMIT 1');
$stmt->execute([$id]);
$lead = $stmt->fetch();
if (!$lead) { http_response_code(404); exit('Lead not found'); }

$stmt = db()->prepare('SELECT type, description, created_at FROM activities WHERE lead_id=? ORDER BY created_at DESC LIMIT 25');
$stmt->execute([$id]);
$activities = $stmt->fetchAll();

$agent = new LeadAgent();
$analysis = $agent->analyze($lead, $activities);

$save = db()->prepare('INSERT INTO ai_runs (lead_id, agent, score, priority, summary, next_action) VALUES (?,?,?,?,?,?)');
$save->execute([$id, 'lead-analyzer', $analysis['score'], $analysis['qualification'], $analysis['summary'], $analysis['next_action']]);

db()->prepare('UPDATE leads SET ai_score=?, ai_priority=?, ai_summary=?, ai_next_action=?, ai_analyzed_at=NOW() WHERE id=?')->execute([
    $analysis['score'], $analysis['qualification'], $analysis['summary'], $analysis['next_action'], $id
]);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>AI Assistant | <?=e($lead['name'])?></title><link rel="stylesheet" href="assets/app.css"></head><body><header class="topbar"><div class="brand">AI CRM</div><nav><a href="index.php">Dashboard</a><a href="leads.php">Leads</a><a href="pipeline.php">Pipeline</a><a href="logout.php">Logout</a></nav></header><main class="container"><div class="page-head"><div><p class="eyebrow">AI SALES ASSISTANT</p><h1><?=e($lead['name'])?></h1><p class="muted"><?=e($lead['company'] ?? '')?> · <?=e($lead['email'] ?? '')?></p></div><a class="btn" href="lead-view.php?id=<?=$id?>">← Lead</a></div><section class="stats"><article><span>AI score</span><strong><?= (int)$analysis['score'] ?>/100</strong></article><article><span>Priority</span><strong><?=e(ucfirst($analysis['qualification']))?></strong></article><article><span>Status</span><strong><?=e(ucfirst($lead['status']))?></strong></article><article><span>Provider</span><strong><?=e(ucfirst($analysis['provider'] ?? 'baseline'))?></strong></article></section><div class="form-grid"><section class="panel"><div class="panel-head"><h2>AI analysis</h2></div><p><?=nl2br(e($analysis['summary']))?></p><h3>Next best action</h3><p><?=nl2br(e($analysis['next_action']))?></p><h3>Why this score?</h3><ul><?php foreach($analysis['factors'] as $factor):?><li><?=e((string)$factor)?></li><?php endforeach;?></ul></section><section class="panel"><div class="panel-head"><h2>Suggested follow-up</h2></div><div class="message-box"><?=nl2br(e($analysis['suggested_followup']))?></div><p class="muted small">This is a draft. A future automation layer can require human approval before sending.</p><a class="btn primary" href="lead-view.php?id=<?=$id?>">Back to lead</a></section></div></main></body></html>
