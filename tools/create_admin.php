<?php
// Run: php tools/create_admin.php "Admin" "admin@example.com" "ChangeMe123!"
require __DIR__.'/../db.php';
if(PHP_SAPI!=='cli'){exit("CLI only\n");}
[$script,$name,$email,$password]=$argv+array_fill(0,4,null);if(!$name||!$email||!$password){exit("Usage: php tools/create_admin.php Name email password\n");}
$s=$pdo->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),password_hash=VALUES(password_hash)');$s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);echo "Admin user ready: {$email}\n";
