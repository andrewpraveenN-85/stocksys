<?php
// login.php
session_start();
require_once 'auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login - Restaurant Stock</title>
    <link rel="stylesheet" href="public/assets/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-container { background: #1f2937; padding: 2rem; border-radius: 12px; width: 100%; max-width: 400px; }
        .login-container h2 { text-align: center; margin-bottom: 1.5rem; color: #e2e8f0; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #e2e8f0; }
        .form-group input { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #334155; background: #0b1220; color: #e2e8f0; }
        .btn-login { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; }
        .btn-login:hover { background: #2563eb; }
        .error { color: #ef4444; text-align: center; margin-bottom: 1rem; padding: 0.5rem; background: #7f1d1d; border-radius: 8px; }
        .demo-credentials { background: #065f46; padding: 1rem; border-radius: 8px; margin-top: 1rem; }
        .demo-credentials h4 { margin: 0 0 0.5rem 0; color: #e2e8f0; }
        .demo-credentials p { margin: 0.25rem 0; color: #e2e8f0; font-size: 14px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Restaurant Stock Login</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Login</button>
        </form>
        
      
    </div>
</body>
</html>
