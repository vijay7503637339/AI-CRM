<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth();

$stages = db()->query('SELECT id,name,probability FROM pipeline_stages ORDER BY position')->fetchAll();
$leads = db()->query("SELECT * FROM leads WHERE status='open' ORDER BY estimated_value DESC, created_at DESC")->fetchAll();
$byStage = [];
foreach ($leads as $lead) {
    $byStage[$lead['stage_id']][] = $lead;
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pipeline | AI CRM</title><link rel="stylesheet" href="assets/app.css"></head><body>
<header class="topbar"><div class="brand">AI CRM</div><nav><a href="index.php">Dashboard</a><a href="leads.php">Leads</a><a href="pipeline.php">Pipeline</a><a href="logout.php">Logout</a></nav></header>
<main class="container wide"><div class="page-head"><div><p class="eyebrow">SALES PIPELINE</p><h1>Pipeline</h1><p class="muted">Visual view of open opportunities.</p></div><a class="btn primary" href="lead-create.php">+ Add lead</a></div>
<div class="kanban"><?php foreach($stages as $stage): $items=$byStage[$stage['id']] ?? []; ?><section class="kanban-col"><div class="kanban-head"><strong><?=e($stage['name'])?></strong><span><?=count($items)?></span></div><p class="muted small"><?= (int)$stage['probability'] ?>% probability</p><?php if(!$items):?><div class="empty-card">No leads</div><?php endif;?><?php foreach($items as $lead):?><article class="lead-card"><strong><?=e($lead['name'])?></strong><span><?=e($lead['company'])?></span><div class="lead-card-foot"><b>₹<?=number_format((float)$lead['estimated_value'],0)?></b><small><?= $lead['ai_score'] !== null ? 'AI '.(int)$lead['ai_score'] : 'AI pending' ?></small></div></article><?php endforeach;?></section><?php endforeach;?></div>
</main></body></html>
