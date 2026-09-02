<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        header('Location: index.php');
        exit;
    }

    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | AI CRM</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="auth-page">
<div class="auth-card">
    <div class="brand">AI CRM</div>
    <h1>Welcome back</h1>
    <p class="muted">Sign in to manage leads and follow-ups.</p>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Email<input type="email" name="email" required autocomplete="email"></label>
        <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
        <button class="btn primary" type="submit">Sign in</button>
    </form>
</div>
</body>
</html>
