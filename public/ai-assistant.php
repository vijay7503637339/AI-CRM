<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/AI/LeadAgent.php';
require_auth();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(404); exit('Lead not found'); }

$stmt = db()->prepare('SELECT l.*, s.name AS stage_name FROM leads l JOIN pipeline_stages s ON s.id=l.stage_id WHERE l.id=? LIMIT 1');
$stmt->execute([$id]);
$lead = $stmt->fetch();
if (!$lead) { http_response_code(404); exit('Lead not found'); }

$stmt = db()->prepare('SELECT type, body, due_at, created_at FROM activities WHERE lead_id=? ORDER BY created_at DESC LIMIT 25');
$stmt->execute([$id]);
$activities = $stmt->fetchAll();

$agent = new LeadAgent();
$analysis = $agent->analyze($lead, $activities);

$save = db()->prepare('INSERT INTO lead_ai_insights (lead_id, score, qualification, summary, next_action, suggested_followup, factors_json) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score), qualification=VALUES(qualification), summary=VALUES(summary), next_action=VALUES(next_action), suggested_followup=VALUES(suggested_followup), factors_json=VALUES(factors_json)');
$save->execute([$id, $analysis['score'], $analysis['qualification'], $analysis['summary'], $analysis['next_action'], $analysis['suggested_followup'], json_encode($analysis['factors'], JSON_UNESCAPED_UNICODE)]);

db()->prepare('UPDATE leads SET ai_score=? WHERE id=?')->execute([$analysis['score'], $id]);

db()->prepare('INSERT INTO ai_runs (lead_id,user_id,agent,status,result_json) VALUES (?,?,?,?,?)')->execute([$id, $_SESSION['user_id'], 'lead-analyzer', 'completed', json_encode($analysis, JSON_UNESCAPED_UNICODE)]);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>AI Assistant | <?=e($lead['name'])?></title><link rel="stylesheet" href="assets/app.css"></head><body><header class="topbar"><div class="brand">AI CRM</div><nav><a href="index.php">Dashboard</a><a href="leads.php">Leads</a><a href="pipeline.php">Pipeline</a><a href="logout.php">Logout</a></nav></header><main class="container"><div class="page-head"><div><p class="eyebrow">AI SALES ASSISTANT</p><h1><?=e($lead['name'])?></h1><p class="muted"><?=e($lead['company'] ?? '')?> · <?=e($lead['email'] ?? '')?></p></div><a class="btn" href="lead-view.php?id=<?=$id?>">← Lead</a></div><section class="stats"><article><span>AI score</span><strong><?= (int)$analysis['score'] ?>/100</strong></article><article><span>Qualification</span><strong><?=e(ucfirst($analysis['qualification']))?></strong></article><article><span>Stage</span><strong><?=e($lead['stage_name'])?></strong></article><article><span>Provider</span><strong><?=e(ucfirst($analysis['provider'] ?? 'baseline'))?></strong></article></section><div class="form-grid"><section class="panel"><div class="panel-head"><h2>AI analysis</h2></div><p><?=nl2br(e($analysis['summary']))?></p><h3>Next best action</h3><p><?=nl2br(e($analysis['next_action']))?></p><h3>Why this score?</h3><ul><?php foreach($analysis['factors'] as $factor):?><li><?=e((string)$factor)?></li><?php endforeach;?></ul></section><section class="panel"><div class="panel-head"><h2>Suggested follow-up</h2></div><div class="message-box"><?=nl2br(e($analysis['suggested_followup']))?></div><p class="muted small">This is a draft. A future automation layer can require human approval before sending.</p><a class="btn primary" href="lead-view.php?id=<?=$id?>">Add as activity</a></section></div></main></body></html>
