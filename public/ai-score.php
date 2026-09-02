<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth();
require_once __DIR__ . '/../app/AI/LeadScoringService.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Invalid lead.'); }
$stmt = db()->prepare('SELECT * FROM leads WHERE id=? LIMIT 1');
$stmt->execute([$id]); $lead=$stmt->fetch();
if (!$lead) { http_response_code(404); exit('Lead not found.'); }

$result=(new LeadScoringService())->score($lead);
db()->prepare('UPDATE leads SET ai_score=? WHERE id=?')->execute([$result['score'],$id]);
db()->prepare("INSERT INTO activities (lead_id,user_id,type,body) VALUES (?,?, 'system', ?)")->execute([$id,$_SESSION['user_id'],'AI score updated to '.$result['score'].'/100. '.$result['recommendation']]);
header('Location: lead-view.php?id='.$id); exit;
