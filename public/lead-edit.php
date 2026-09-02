<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth();
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT); if(!$id){http_response_code(404);exit('Lead not found');}
$stages=['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','proposal'=>'Proposal','won'=>'Won','lost'=>'Lost'];
$pdo=db();$stmt=$pdo->prepare('SELECT * FROM leads WHERE id=? LIMIT 1');$stmt->execute([$id]);$lead=$stmt->fetch();if(!$lead){http_response_code(404);exit('Lead not found');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 $name=trim($_POST['name']??'');
 if($name===''){$error='Lead name is required.';}else{
  $status=$_POST['status']??'new';if(!array_key_exists($status,$stages))$status='new';
  $followup=trim($_POST['follow_up_at']??'')?:null;
  $pdo->prepare('UPDATE leads SET name=?,company=?,email=?,phone=?,source=?,status=?,value=?,follow_up_at=?,notes=? WHERE id=?')->execute([$name,trim($_POST['company']??'')?:null,trim($_POST['email']??'')?:null,trim($_POST['phone']??'')?:null,trim($_POST['source']??'')?:'website',$status,(float)($_POST['value']??0),$followup,trim($_POST['notes']??'')?:null,$id]);
  $pdo->prepare("INSERT INTO activities (lead_id,user_id,type,description) VALUES (?,?, 'note', ?)")->execute([$id,$_SESSION['user_id'],'Lead details updated']);
  header('Location: lead-view.php?id='.$id);exit;
 }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit Lead | AI CRM</title><link rel="stylesheet" href="assets/app.css"></head><body><header class="topbar"><div class="brand">AI CRM</div><nav><a href="index.php">Dashboard</a><a href="leads.php">Leads</a><a href="pipeline.php">Pipeline</a><a href="tasks.php">Tasks</a><a href="analytics.php">Analytics</a><a href="logout.php">Logout</a></nav></header><main class="container narrow"><div class="page-head"><div><p class="eyebrow">CRM</p><h1>Edit lead</h1><p class="muted">Update contact, opportunity and follow-up details.</p></div><a href="lead-view.php?id=<?=$id?>">Cancel</a></div><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?><form method="post" class="panel form-grid"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><label>Lead name *<input name="name" required value="<?=e($lead['name'])?>"></label><label>Company<input name="company" value="<?=e($lead['company'])?>"></label><label>Email<input type="email" name="email" value="<?=e($lead['email'])?>"></label><label>Phone<input name="phone" value="<?=e($lead['phone'])?>"></label><label>Source<input name="source" value="<?=e($lead['source'])?>"></label><label>Value (₹)<input type="number" min="0" step="0.01" name="value" value="<?=e((string)$lead['value'])?>"></label><label>Status<select name="status"><?php foreach($stages as $key=>$label):?><option value="<?=e($key)?>" <?=$lead['status']===$key?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></label><label>Next follow-up<input type="datetime-local" name="follow_up_at" value="<?= $lead['follow_up_at'] ? e(date('Y-m-d\TH:i',strtotime($lead['follow_up_at']))) : '' ?>"></label><label class="full">Notes<textarea name="notes" rows="7"><?=e($lead['notes'])?></textarea></label><div class="full"><button class="btn primary" type="submit">Save changes</button> <a class="btn" href="lead-view.php?id=<?=$id?>">Cancel</a></div></form></main></body></html>
