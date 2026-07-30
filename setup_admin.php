<?php
/**
 * ADMIN SETUP HELPER
 * ==================
 * Run this file ONCE in your browser to set the admin password.
 * URL: http://localhost/event-management/setup_admin.php
 * 
 * DELETE THIS FILE after setup is complete!
 */

require_once 'includes/db.php';

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Delete existing admins and insert fresh one
$pdo->exec("DELETE FROM admins");
$stmt = $pdo->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
$stmt->execute(['Super Admin', 'admin@eventms.com', $hash]);

echo "<!DOCTYPE html><html><head>
<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'>
</head><body class='p-5' style='background:#f1f5f9'>
<div class='card mx-auto' style='max-width:500px'>
<div class='card-body p-4 text-center'>
<div style='font-size:3rem'>✅</div>
<h3 class='mt-3 fw-bold'>Admin Account Ready!</h3>
<table class='table mt-3 text-start'>
<tr><td class='fw-bold'>Email</td><td>admin@eventms.com</td></tr>
<tr><td class='fw-bold'>Password</td><td>admin123</td></tr>
<tr><td class='fw-bold'>Hash</td><td style='font-size:.7rem;word-break:break-all'>{$hash}</td></tr>
</table>
<a href='admin/login.php' class='btn btn-primary w-100 mt-2'>Go to Admin Login →</a>
<div class='alert alert-warning mt-3 text-start small'>
⚠️ <strong>Delete this file</strong> after logging in: <code>setup_admin.php</code>
</div>
</div>
</div>
</body></html>";
