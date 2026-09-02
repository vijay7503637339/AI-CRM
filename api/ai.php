<?php
require __DIR__.'/../app.php';
header('Content-Type: application/json');
if(!logged_in()){http_response_code(401);echo json_encode(['ok'=>false,'error'=>'Unauthorized']);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['ok'=>false,'error'=>'POST required']);exit;}
check_csrf();$id=(int)($_POST['id']??0);$s=$pdo->prepare('SELECT * FROM leads WHERE id=?');$s->execute([$id]);$l=$s->fetch();if(!$l){http_response_code(404);echo json_encode(['ok'=>false,'error'=>'Lead not found']);exit;}
$score=25;if($l['email'])$score+=15;if($l['phone'])$score+=15;if($l['company'])$score+=10;if($l['value']>=100000)$score+=20;elseif($l['value']>=50000)$score+=10;if($l['follow_up_at'])$score+=10;$score=min(100,$score);
$priority=$score>=75?'hot':($score>=45?'warm':'cold');$summary="{$l['name']} is a {$priority} lead with a {$score}/100 fit score.";$next=$score>=75?'Contact within 24 hours':($score>=45?'Follow up within 2–3 days':'Nurture and qualify further');
$config=require __DIR__.'/../config.php';
if($config['ai']['enabled']){
 $payload=['model'=>$config['ai']['model'],'messages'=>[['role'=>'system','content'=>'You are a CRM sales assistant. Return compact JSON with keys score (integer 0-100), priority (hot/warm/cold), summary, next_action. Do not invent facts.'],['role'=>'user','content'=>json_encode(['name'=>$l['name'],'company'=>$l['company'],'email'=>$l['email'],'phone'=>$l['phone'],'source'=>$l['source'],'status'=>$l['status'],'value'=>$l['value'],'notes'=>$l['notes']])]],'temperature'=>0.2];
 $ch=curl_init('https://api.openai.com/v1/chat/completions');curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$config['ai']['api_key']],CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_TIMEOUT=>30]);$raw=curl_exec($ch);curl_close($ch);$out=json_decode($raw,true);$text=$out['choices'][0]['message']['content']??'';$text=preg_replace('/^```json|```$/','',trim($text));$ai=json_decode($text,true);if(is_array($ai)){ $score=max(0,min(100,(int)($ai['score']??$score)));$priority=$ai['priority']??$priority;$summary=$ai['summary']??$summary;$next=$ai['next_action']??$next; }
}
$s=$pdo->prepare('UPDATE leads SET ai_score=?,ai_priority=?,ai_summary=?,ai_next_action=?,ai_analyzed_at=NOW(),updated_at=NOW() WHERE id=?');$s->execute([$score,$priority,$summary,$next,$id]);$s=$pdo->prepare('INSERT INTO ai_runs(lead_id,agent,score,priority,summary,next_action) VALUES(?,?,?,?,?,?)');$s->execute([$id,'lead_analyzer',$score,$priority,$summary,$next]);echo json_encode(['ok'=>true,'score'=>$score,'priority'=>$priority,'summary'=>$summary,'next_action'=>$next]);
