<?php
session_start();
require __DIR__ . '/db.php';
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function csrf(){ if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function check_csrf(){ if(!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Invalid request.'); } }
function logged_in(){ return isset($_SESSION['user_id']); }
function require_login(){ if(!logged_in()){ header('Location: ?page=login'); exit; } }
function flash($msg){ $_SESSION['flash']=$msg; }
function get_flash(){ $m=$_SESSION['flash']??null; unset($_SESSION['flash']); return $m; }
function layout_start($title){ require_login(); $flash=get_flash(); ?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?> · WebStripe AI CRM</title><link rel="stylesheet" href="assets/app.css"></head><body><aside class="sidebar"><div class="brand">WebStripe <span>AI CRM</span></div><nav><a href="?page=dashboard">Dashboard</a><a href="?page=leads">Leads</a><a href="?page=pipeline">Pipeline</a></nav><div class="user">Signed in as <?=e($_SESSION['user_name']??'User')?><a href="?page=logout">Logout</a></div></aside><main class="main"><header><h1><?=e($title)?></h1><a class="button" href="?page=leads&action=new">+ New Lead</a></header><?php if($flash):?><div class="flash"><?=e($flash)?></div><?php endif; ?>
<?php }
function layout_end(){ ?></main></body></html><?php }
