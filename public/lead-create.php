<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth();

$stages = ['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','proposal'=>'Proposal','won'=>'Won','lost'=>'Lost'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $error = 'Lead name is required.';
    } else {
        $stmt = db()->prepare('INSERT INTO leads (name,company,email,phone,source,status,value,follow_up_at,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $followup = trim($_POST['follow_up_at'] ?? '') ?: null;
        $stmt->execute([
            $name,
            trim($_POST['company'] ?? '') ?: null,
            trim($_POST['email'] ?? '') ?: null,
            trim($_POST['phone'] ?? '') ?: null,
            trim($_POST['source'] ?? '') ?: 'website',
            in_array($_POST['status'] ?? 'new', array_keys($stages), true) ? $_POST['status'] : 'new',
            (float)($_POST['value'] ?? 0),
            $followup,
            trim($_POST['notes'] ?? '') ?: null,
            $_SESSION['user_id'],
        ]);
        header('Location: leads.php');
        exit;
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Add Lead | AI CRM</title><link rel="stylesheet" href="assets/app.css"></head><body>
<header class="topbar"><div class="brand">AI CRM</div><nav><a href="index.php">Dashboard</a><a href="leads.php">Leads</a><a href="pipeline.php">Pipeline</a><a href="logout.php">Logout</a></nav></header>
<main class="container narrow"><div class="page-head"><div><p class="eyebrow">NEW PROSPECT</p><h1>Add lead</h1></div><a href="leads.php">Cancel</a></div><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?>
<form method="post" class="panel form-grid"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Lead name *<input name="name" required></label><label>Company<input name="company"></label><label>Email<input type="email" name="email"></label><label>Phone<input name="phone"></label><label>Source<input name="source" placeholder="Website, referral, ads..." value="website"></label><label>Value (₹)<input type="number" min="0" step="0.01" name="value" value="0"></label><label>Status<select name="status"><?php foreach($stages as $key=>$label):?><option value="<?=e($key)?>"><?=e($label)?></option><?php endforeach;?></select></label><label>Next follow-up<input type="datetime-local" name="follow_up_at"></label><label class="full">Notes<textarea name="notes" rows="5"></textarea></label><div class="full"><button class="btn primary" type="submit">Save lead</button></div></form></main></body></html>
