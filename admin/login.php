<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (isAdminLoggedIn()) redirect(BASE_URL . '/admin/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($pass, $admin['password'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            redirect(BASE_URL . '/admin/dashboard.php');
        } else {
            $error = 'Invalid admin credentials.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login - EventMS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-page" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%)">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="icon">🔐</div>
            <h2>Admin Portal</h2>
            <p>Event Management System</p>
        </div>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Admin Email</label>
                <input type="email" name="email" class="form-control" placeholder="admin@eventms.com" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Admin password" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">
                <i class="bi bi-shield-lock me-2"></i>Admin Login
            </button>
        </form>
        <p class="text-center mt-3 text-muted small">
            Default: admin@eventms.com / admin123
        </p>
        <hr>
        <p class="text-center text-muted small"><a href="../user/login.php">← Back to User Login</a></p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
