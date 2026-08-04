<?php
// register.php - Tenant Registration
require_once __DIR__ . '/config.php';

$error = '';
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name !== '' && $email !== '' && $password !== '') {
        $pdo = getDB();
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `email` = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "An account with this email address already exists.";
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `password`, `role`, `phone`) VALUES (?, ?, ?, 'tenant', ?)");
                $stmt->execute([$name, $email, $hash, $phone]);

                $newId = $pdo->lastInsertId();
                $_SESSION['user'] = [
                    'id'    => $newId,
                    'name'  => $name,
                    'email' => $email,
                    'role'  => 'tenant',
                    'phone' => $phone,
                ];

                header('Location: index.php');
                exit;
            } catch (Exception $e) {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Registration - Boarding House System</title>
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="setup-body">
    <div class="setup-card shadow-lg" style="max-width: 450px; text-align: left;">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="setup-icon">📝</div>
            <h2>Register Tenant Account</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Create an account to book boarding rooms easily</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= e($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="row g-3">
            <div class="col-12">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" required placeholder="e.g. Maria Santos" value="<?= e($_POST['name'] ?? ''); ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" required placeholder="maria@example.com" value="<?= e($_POST['email'] ?? ''); ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="09123456789" value="<?= e($_POST['phone'] ?? ''); ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Password *</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary w-100">
                    Create Account & Log In
                </button>
            </div>
        </form>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border); font-size: 0.85rem; color: var(--text-muted); text-align: center;">
            <p>Already have an account? <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Log in here</a></p>
        </div>
    </div>
</body>
</html>
