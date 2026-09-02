<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(404); exit('Lead not found'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'activity') {
        $body = trim($_POST['body'] ?? '');
        $type = $_POST['type'] ?? 'note';
        $allowed = ['note','call','email','whatsapp','meeting','task'];
        if ($body !== '' && in_array($type, $allowed, true)) {
            $stmt = db()->prepare('INSERT INTO activities (lead_id,user_id,type,body,due_at) VALUES (?,?,?,?,?)');
            $stmt->execute([$id, $_SESSION['user_id'], $type, $body, trim($_POST['due_at'] ?? '') ?: null]);
        }
    } elseif ($action === 'stage') {
        $stage = filter_input(INPUT_POST, 'stage_id', FILTER_VALIDATE_INT);
        if ($stage) {
            $stmt = db()->prepare('SELECT name FROM pipeline_stages WHERE id=?'); $stmt->execute([$stage]); $stageName=$stmt->fetchColumn();
            if ($stageName) {
                $status = $stageName === 'Won' ? 'won' : ($stageName === 'Lost' ? 'lost' : 'open');
                db()->prepare('UPDATE leads SET stage_id=?,status=? WHERE id=?')->execute([$stage,$status,$id]);
                db()->prepare("INSERT INTO activities (lead_id,user_id,type,body) VALUES (?,?, 'system', ?)")->execute([$id,$_SESSION['user_id'], 'Stage changed to '.$stageName]);
            }
        }
    }
    header('Location: lead-view.php?id='.$id); exit;
}

$stmt = db()->prepare('SELECT l.*, s.name AS stage_name FROM leads l JOIN pipeline_stages s ON s.id=l.stage_id WHERE l.id=? LIMIT 1');
$stmt->execute([$id]); $lead=$stmt->fetch();
if (!$lead) { http_response_code(404); exit('Lead not found'); }
$stages=db()->query('SELECT id,name FROM pipeline_stages ORDER BY position')->fetchAll();
$stmt=db()->prepare('SELECT a.*,u.name AS user_name FROM activities a LEFT JOIN users u ON u.id=a.user_id WHERE a.lead_id=? ORDER BY a.created_at DESC');
$stmt->execute([$id]); $activities=$stmt->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($lead['name'])?> | AI CRM</title><link rel="stylesheet" href="assets/app.css"></head><body>
<header class="topbar"><div class="brand">AI CRM</div><nav><a href="index.php">Dashboard</a><a href="leads.php">Leads</a><a href="pipeline.php">Pipeline</a><a href="logout.php">Logout</a></nav></header>
<main class="container"><div class="page-head"><div><p class="eyebrow">LEAD</p><h1><?=e($lead['name'])?></h1><p class="muted"><?=e($lead['company'])?> · <?=e($lead['email'])?></p></div><a class="btn" href="leads.php">← Back</a></div>
<section class="stats"><article><span>Stage</span><strong><?=e($lead['stage_name'])?></strong></article><article><span>AI score</span><strong><?= $lead['ai_score']!==null ? (int)$lead['ai_score'].'/100':'Pending' ?></strong></article><article><span>Value</span><strong>₹<?=number_format((float)$lead['estimated_value'],0)?></strong></article><article><span>Follow-up</span><strong class="small"><?= $lead['next_follow_up'] ? e(date('d M Y, h:i A',strtotime($lead['next_follow_up']))) : 'Not set' ?></strong></article></section>
<div class="form-grid"><section class="panel"><div class="panel-head"><h2>Lead details</h2></div><p><b>Phone:</b> <?=e($lead['phone'])?:'—'?></p><p><b>Source:</b> <?=e($lead['source'])?:'—'?></p><p><b>Status:</b> <?=e(ucfirst($lead['status']))?></p><p><b>Notes:</b><br><?=nl2br(e($lead['notes']))?:'—'?></p><form method="post" style="margin-top:20px"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="stage"><label>Move stage<select name="stage_id"><?php foreach($stages as $s):?><option value="<?=$s['id']?>" <?=$s['id']==$lead['stage_id']?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></label><button class="btn primary" style="margin-top:12px" type="submit">Update stage</button></form></section>
<section class="panel"><div class="panel-head"><h2>Add activity</h2></div><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="activity"><label>Type<select name="type"><option>note</option><option>call</option><option>email</option><option>whatsapp</option><option>meeting</option><option>task</option></select></label><label style="margin-top:14px">Due date<input type="datetime-local" name="due_at"></label><label style="margin-top:14px">Note / activity<textarea name="body" rows="5" required></textarea></label><button class="btn primary" style="margin-top:12px" type="submit">Add activity</button></form></section></div>
<section class="panel" style="margin-top:18px"><div class="panel-head"><h2>Activity timeline</h2></div><?php if(!$activities):?><p class="muted">No activity yet.</p><?php endif;?><?php foreach($activities as $a):?><div style="padding:14px 0;border-bottom:1px solid #edf0f5"><b><?=e(ucfirst($a['type']))?></b> <span class="muted small">· <?=e($a['user_name']??'System')?> · <?=e(date('d M Y, h:i A',strtotime($a['created_at'])))?></span><p><?=nl2br(e($a['body']))?></p><?php if($a['due_at']):?><span class="pill">Due <?=e(date('d M Y, h:i A',strtotime($a['due_at'])))?></span><?php endif;?></div><?php endforeach;?></section>
</main></body></html>
