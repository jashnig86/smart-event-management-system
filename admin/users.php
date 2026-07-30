<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdminLogin();

$search = $_GET['search'] ?? '';
$sql = "SELECT u.*,
        (SELECT COUNT(*) FROM events WHERE user_id=u.id) as event_count,
        (SELECT COUNT(*) FROM registrations WHERE user_id=u.id) as reg_count
        FROM users u WHERE 1=1";
$params = [];
if ($search) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $q = "%$search%";
    $params = [$q,$q,$q];
}
$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Users - Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="admin-content">
    <?php showFlash(); ?>
    <div class="page-header">
        <h1><i class="bi bi-people me-2"></i>Manage Users</h1>
        <p>View all registered users and their activity</p>
    </div>

    <!-- Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Search Users</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, email or phone..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button>
                </div>
                <div class="col-md-3">
                    <a href="users.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-people me-2"></i>All Users (<?= count($users) ?>)
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Events Created</th>
                        <th>Registrations</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users): ?>
                    <?php foreach ($users as $i => $user): ?>
                    <tr>
                        <td class="text-muted"><?= $i+1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#06b6d4);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem;flex-shrink:0">
                                    <?= strtoupper(substr($user['name'],0,1)) ?>
                                </div>
                                <span class="fw-semibold"><?= htmlspecialchars($user['name']) ?></span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['phone'] ?: '—') ?></td>
                        <td>
                            <span class="badge bg-primary"><?= $user['event_count'] ?></span>
                        </td>
                        <td>
                            <span class="badge bg-success"><?= $user['reg_count'] ?></span>
                        </td>
                        <td><small class="text-muted"><?= date('d M Y', strtotime($user['created_at'])) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
