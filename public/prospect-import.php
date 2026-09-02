<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth();

$id = (int)($_POST['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: web-prospecting.php'); exit; }
verify_csrf();

$prospectStmt = db()->prepare('SELECT * FROM web_prospects WHERE id=? LIMIT 1');
$prospectStmt->execute([$id]);
$prospect = $prospectStmt->fetch();
if (!$prospect) { http_response_code(404); exit('Prospect not found.'); }

$stages = db()->query('SELECT id FROM pipeline_stages ORDER BY position LIMIT 1')->fetchColumn();
$stageId = (int)($stages ?: 1);

$existing = null;
if (!empty($prospect['phone'])) {
    $q=db()->prepare('SELECT id FROM leads WHERE phone=? LIMIT 1'); $q->execute([$prospect['phone']]); $existing=$q->fetchColumn();
}
if (!$existing && !empty($prospect['email'])) {
    $q=db()->prepare('SELECT id FROM leads WHERE email=? LIMIT 1'); $q->execute([$prospect['email']]); $existing=$q->fetchColumn();
}

if ($existing) {
    $leadId=(int)$existing;
} else {
    $stmt=db()->prepare('INSERT INTO leads (owner_id,stage_id,name,company,email,phone,source,estimated_value,notes) VALUES (?,?,?,?,?,?,?,?,?)');
    $notes='Imported from web prospecting. Source: '.$prospect['source_url'];
    $stmt->execute([$_SESSION['user_id'],$stageId,$prospect['business_name'],$prospect['business_name'],$prospect['email'],$prospect['phone'],'web_scrape',0,$notes]);
    $leadId=(int)db()->lastInsertId();
    $activity=db()->prepare('INSERT INTO activities (lead_id,user_id,type,description) VALUES (?,?,?,?)');
    $activity->execute([$leadId,$_SESSION['user_id'],'note','Lead imported from web prospecting: '.$prospect['source_url']]);
}

$update=db()->prepare("UPDATE web_prospects SET status='imported',lead_id=? WHERE id=?");
$update->execute([$leadId,$id]);
header('Location: lead-view.php?id='.$leadId);
exit;
