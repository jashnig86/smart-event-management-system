<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdminLogin();

$totalEvents   = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pendingEvents = $pdo->query("SELECT COUNT(*) FROM events WHERE status='pending'")->fetchColumn();
$totalRegs     = $pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
$totalAttended = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();

// Recent events
$recentEvents = $pdo->query("
    SELECT e.*, u.name as organizer FROM events e
    JOIN users u ON e.user_id = u.id
    ORDER BY e.created_at DESC LIMIT 6
")->fetchAll();

// Category stats
$catStats = $pdo->query("
    SELECT category, COUNT(*) as cnt FROM events WHERE status='approved' GROUP BY category ORDER BY cnt DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard - EventMS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="admin-content">
    <?php showFlash(); ?>
    <div class="page-header">
        <h1><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h1>
        <p>Overview of your event management system</p>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="bi bi-calendar-event"></i></div>
                <div><h3><?= $totalEvents ?></h3><p>Total Events</p></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon cyan"><i class="bi bi-people"></i></div>
                <div><h3><?= $totalUsers ?></h3><p>Registered Users</p></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
                <div><h3><?= $pendingEvents ?></h3><p>Pending Approval</p></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-person-check"></i></div>
                <div><h3><?= $totalAttended ?></h3><p>Attendances Marked</p></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Events -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar3 me-2"></i>Recent Events</span>
                    <a href="events.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Organizer</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentEvents as $ev): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($ev['title']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($ev['category']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($ev['organizer']) ?></td>
                                <td><small><?= date('d M Y', strtotime($ev['date'])) ?></small></td>
                                <td>
                                    <span class="badge-status badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span>
                                </td>
                                <td>
                                    <?php if ($ev['status'] === 'pending'): ?>
                                    <a href="approve_event.php" class="btn btn-sm btn-warning">Review</a>
                                    <?php else: ?>
                                    <a href="events.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Stats -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-lightning me-2"></i>Quick Actions</div>
                <div class="card-body d-grid gap-2">
                    <a href="approve_event.php" class="btn btn-warning">
                        <i class="bi bi-check2-circle me-2"></i>Review Pending (<?= $pendingEvents ?>)
                    </a>
                    <a href="attendance.php" class="btn btn-primary">
                        <i class="bi bi-qr-code-scan me-2"></i>Mark Attendance
                    </a>
                    <a href="users.php" class="btn btn-outline-primary">
                        <i class="bi bi-people me-2"></i>View All Users
                    </a>
                </div>
            </div>

            <!-- Category Stats -->
            <div class="card">
                <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Events by Category</div>
                <div class="card-body">
                    <?php if ($catStats): ?>
                    <?php foreach ($catStats as $stat): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="fw-semibold"><?= htmlspecialchars($stat['category']) ?></small>
                            <small class="text-muted"><?= $stat['cnt'] ?></small>
                        </div>
                        <div class="progress" style="height:6px">
                            <div class="progress-bar" style="width:<?= min(100, ($stat['cnt'] / max(1,$totalEvents)) * 100) ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <p class="text-muted text-center">No approved events yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
