<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth();
$pdo=db();
$q=trim($_GET['q']??'');
$status=$_GET['status']??'';
$priority=$_GET['priority']??'';
$statusLabels=['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','proposal'=>'Proposal','won'=>'Won','lost'=>'Lost'];
$priorities=['hot'=>'Hot','warm'=>'Warm','cold'=>'Cold'];
$where=[];$params=[];
if($q!==''){ $where[]='(name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ?)';$like="%{$q}%";array_push($params,$like,$like,$like,$like); }
if(array_key_exists($status,$statusLabels)){ $where[]='status=?';$params[]=$status; }
if(array_key_exists($priority,$priorities)){ $where[]='ai_priority=?';$params[]=$priority; }
$sql='SELECT * FROM leads'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY CASE WHEN ai_score IS NULL THEN 1 ELSE 0 END, ai_score DESC, created_at DESC';
$stmt=$pdo->prepare($sql);$stmt->execute($params);$leads=$stmt->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Leads | AI CRM</title><link rel="stylesheet" href="assets/app.css"></head><body>
<header class="topbar"><div class="brand">AI CRM</div><nav><a href="index.php">Dashboard</a><a href="leads.php">Leads</a><a href="pipeline.php">Pipeline</a><a href="tasks.php">Tasks</a><a href="analytics.php">Analytics</a><a href="logout.php">Logout</a></nav></header>
<main class="container"><div class="page-head"><div><p class="eyebrow">CRM</p><h1>Leads</h1><p class="muted">Search, qualify and manage your entire sales pipeline.</p></div><div class="head-actions"><a class="btn" href="web-prospecting.php">Find prospects</a><a class="btn primary" href="lead-create.php">+ Add lead</a></div></div>
<section class="panel"><form class="search" method="get"><input name="q" value="<?=e($q)?>" placeholder="Search name, company, email or phone"><select name="status"><option value="">All stages</option><?php foreach($statusLabels as $key=>$label):?><option value="<?=e($key)?>" <?=$status===$key?'selected':''?>><?=e($label)?></option><?php endforeach;?></select><select name="priority"><option value="">All AI priorities</option><?php foreach($priorities as $key=>$label):?><option value="<?=e($key)?>" <?=$priority===$key?'selected':''?>><?=e($label)?></option><?php endforeach;?></select><button class="btn" type="submit">Filter</button><a class="btn" href="leads.php">Reset</a></form><div class="table-wrap"><table><thead><tr><th>Name</th><th>Contact</th><th>Source</th><th>Status</th><th>Value</th><th>AI</th><th>Follow-up</th><th>Action</th></tr></thead><tbody><?php if(!$leads):?><tr><td colspan="8" class="empty">No matching leads.</td></tr><?php endif;?><?php foreach($leads as $lead):?><tr><td><a href="lead-view.php?id=<?=$lead['id']?>"><strong><?=e($lead['name'])?></strong></a><br><span class="muted small"><?=e($lead['company'])?></span></td><td><?=e($lead['phone'])?><br><span class="muted small"><?=e($lead['email'])?></span></td><td><?=e($lead['source'])?:'—'?></td><td><span class="pill"><?=e($statusLabels[$lead['status']] ?? ucfirst($lead['status']))?></span></td><td>₹<?=number_format((float)$lead['value'],0)?></td><td><?php if($lead['ai_score']!==null):?><strong><?=((int)$lead['ai_score'])?>/100</strong> <span class="priority priority-<?=e($lead['ai_priority']??'cold')?>"><?=e(ucfirst($lead['ai_priority']??''))?></span><?php else:?>Pending<?php endif;?></td><td><?= $lead['follow_up_at'] ? e(date('d M Y, h:i A',strtotime($lead['follow_up_at']))) : '—' ?></td><td><a class="btn" href="lead-edit.php?id=<?=$lead['id']?>">Edit</a></td></tr><?php endforeach;?></tbody></table></div></section></main></body></html>
