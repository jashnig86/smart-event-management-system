<?php
// Admin sidebar - include in each admin page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<div class="sidebar">
    <a href="dashboard.php" class="sidebar-brand">
        🎪 EventMS <span class="badge-admin">ADMIN</span>
    </a>
    <nav>
        <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="events.php" class="nav-link <?= $currentPage === 'events' ? 'active' : '' ?>">
            <i class="bi bi-calendar3"></i> Manage Events
        </a>
        <a href="approve_event.php" class="nav-link <?= $currentPage === 'approve_event' ? 'active' : '' ?>">
            <i class="bi bi-check2-circle"></i> Pending Approvals
            <?php
            global $pdo;
            $pending = $pdo->query("SELECT COUNT(*) FROM events WHERE status='pending'")->fetchColumn();
            if ($pending > 0): ?>
            <span class="badge bg-warning text-dark ms-auto"><?= $pending ?></span>
            <?php endif; ?>
        </a>
        <a href="attendance.php" class="nav-link <?= $currentPage === 'attendance' ? 'active' : '' ?>">
            <i class="bi bi-qr-code-scan"></i> Mark Attendance
        </a>
        <a href="users.php" class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Manage Users
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="text-white-50 small mb-2"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['admin_name']) ?></div>
        <a href="logout.php" class="btn btn-sm btn-outline-light w-100"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
</div>
