<?php
// login.php - Login for Admin & Tenant
require_once __DIR__ . '/config.php';

$error = '';
$flashMessage = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'rooms.php' : 'index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email !== '' && $password !== '') {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user'] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
                'phone' => $user['phone'],
            ];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: rooms.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please enter both email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Boarding House Booking System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="setup-body">
    <div class="setup-card" style="max-width: 420px; text-align: left;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="setup-icon">🔐</div>
            <h2>Account Login</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Sign in to access your account</p>
        </div>

        <?php if ($flashMessage): ?>
            <div class="alert alert-danger">⚠️ <?= e($flashMessage); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= e($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="Enter your email address" value="<?= e($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; margin-top: 8px;">
                Log In
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border); font-size: 0.85rem; color: var(--text-muted); text-align: center;">
            <p>Don't have a tenant account? <a href="register.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Register here</a></p>
        </div>
    </div>
</body>
</html>
