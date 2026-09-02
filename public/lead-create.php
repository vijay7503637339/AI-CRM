<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth();

$stages = db()->query('SELECT id,name FROM pipeline_stages ORDER BY position')->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $error = 'Lead name is required.';
    } else {
        $stmt = db()->prepare('INSERT INTO leads (owner_id,stage_id,name,company,email,phone,source,estimated_value,next_follow_up,notes) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $followup = trim($_POST['next_follow_up'] ?? '') ?: null;
        $stmt->execute([
            $_SESSION['user_id'],
            (int)($_POST['stage_id'] ?? 1),
            $name,
            trim($_POST['company'] ?? '') ?: null,
            trim($_POST['email'] ?? '') ?: null,
            trim($_POST['phone'] ?? '') ?: null,
            trim($_POST['source'] ?? '') ?: null,
            (float)($_POST['estimated_value'] ?? 0),
            $followup,
            trim($_POST['notes'] ?? '') ?: null,
        ]);
        header('Location: leads.php');
        exit;
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Add Lead | AI CRM</title><link rel="stylesheet" href="assets/app.css"></head><body>
<header class="topbar"><div class="brand">AI CRM</div><nav><a href="index.php">Dashboard</a><a href="leads.php">Leads</a><a href="pipeline.php">Pipeline</a><a href="logout.php">Logout</a></nav></header>
<main class="container narrow"><div class="page-head"><div><p class="eyebrow">NEW PROSPECT</p><h1>Add lead</h1></div><a href="leads.php">Cancel</a></div><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?>
<form method="post" class="panel form-grid"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Lead name *<input name="name" required></label><label>Company<input name="company"></label><label>Email<input type="email" name="email"></label><label>Phone<input name="phone"></label><label>Source<input name="source" placeholder="Website, referral, ads..."></label><label>Estimated value (₹)<input type="number" min="0" step="0.01" name="estimated_value" value="0"></label><label>Stage<select name="stage_id"><?php foreach($stages as $stage):?><option value="<?=$stage['id']?>"><?=e($stage['name'])?></option><?php endforeach;?></select></label><label>Next follow-up<input type="datetime-local" name="next_follow_up"></label><label class="full">Notes<textarea name="notes" rows="5"></textarea></label><div class="full"><button class="btn primary" type="submit">Save lead</button></div></form></main></body></html>
