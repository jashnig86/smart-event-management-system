<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireUserLogin();

$userId = $_SESSION['user_id'];

// Stats
$myEvents = $pdo->prepare("SELECT COUNT(*) FROM events WHERE user_id = ?");
$myEvents->execute([$userId]); $totalMyEvents = $myEvents->fetchColumn();

$myRegs = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE user_id = ?");
$myRegs->execute([$userId]); $totalRegs = $myRegs->fetchColumn();

$totalApproved = $pdo->query("SELECT COUNT(*) FROM events WHERE status='approved'")->fetchColumn();

// My created events (to show status)
$myEventsStmt = $pdo->prepare("SELECT * FROM events WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$myEventsStmt->execute([$userId]);
$myCreatedEvents = $myEventsStmt->fetchAll();

// Recent registrations with QR
$regsStmt = $pdo->prepare("
    SELECT r.*, e.title, e.date, e.venue, e.category,
           a.id as attended
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    LEFT JOIN attendance a ON a.registration_id = r.id
    WHERE r.user_id = ?
    ORDER BY r.registered_at DESC
    LIMIT 5
");
$regsStmt->execute([$userId]);
$recentRegs = $regsStmt->fetchAll();

// Recent approved events
$eventsStmt = $pdo->query("
    SELECT e.*, u.name as organizer,
           (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) as reg_count
    FROM events e
    JOIN users u ON e.user_id = u.id
    WHERE e.status = 'approved' AND e.date >= NOW()
    ORDER BY e.date ASC
    LIMIT 4
");
$upcomingEvents = $eventsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - EventMS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand text-white" href="dashboard.php">🎪 EventMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link text-white active" href="dashboard.php"><i class="bi bi-house me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="view_events.php"><i class="bi bi-calendar3 me-1"></i>Browse Events</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="create_event.php"><i class="bi bi-plus-circle me-1"></i>Create Event</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="my_registrations.php"><i class="bi bi-ticket me-1"></i>My Tickets</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <a href="logout.php" class="btn btn-sm btn-light text-primary fw-bold">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid p-4">
    <?php showFlash(); ?>

    <!-- Page Header -->
    <div class="page-header">
        <h1>👋 Hello, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
        <p>Here's what's happening with your events today.</p>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="bi bi-calendar-event"></i></div>
                <div><h3><?= $totalMyEvents ?></h3><p>Events Created</p></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon cyan"><i class="bi bi-ticket-perforated"></i></div>
                <div><h3><?= $totalRegs ?></h3><p>Registrations</p></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                <div><h3><?= $totalApproved ?></h3><p>Live Events</p></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-qr-code"></i></div>
                <div><h3><?= $totalRegs ?></h3><p>QR Tickets</p></div>
            </div>
        </div>
    </div>

    <!-- My Created Events Status -->
    <?php if ($myCreatedEvents): ?>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-pencil-square me-2"></i>My Created Events</span>
            <a href="create_event.php" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>New Event</a>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Event</th><th>Date</th><th>Category</th><th>Status</th><th>Note</th></tr></thead>
                <tbody>
                <?php foreach ($myCreatedEvents as $ce): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($ce['title']) ?></td>
                    <td><small><?= date('d M Y', strtotime($ce['date'])) ?></small></td>
                    <td><span class="category-tag"><?= htmlspecialchars($ce['category']) ?></span></td>
                    <td><span class="badge-status badge-<?= $ce['status'] ?>"><?= ucfirst($ce['status']) ?></span></td>
                    <td>
                        <?php if ($ce['status'] === 'pending'): ?>
                        <small class="text-warning"><i class="bi bi-clock me-1"></i>Waiting for admin approval</small>
                        <?php elseif ($ce['status'] === 'approved'): ?>
                        <small class="text-success"><i class="bi bi-check-circle me-1"></i>Live — visible in Browse Events</small>
                        <?php elseif ($ce['status'] === 'rejected'): ?>
                        <small class="text-danger"><i class="bi bi-x-circle me-1"></i><?= $ce['rejection_reason'] ? htmlspecialchars($ce['rejection_reason']) : 'Rejected by admin' ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Upcoming Events -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar3 me-2"></i>Upcoming Events</span>
                    <a href="view_events.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($upcomingEvents): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcomingEvents as $ev): ?>
                        <div class="list-group-item px-4 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1 fw-bold"><?= htmlspecialchars($ev['title']) ?></h6>
                                    <small class="text-muted"><i class="bi bi-calendar me-1"></i><?= date('d M Y, h:i A', strtotime($ev['date'])) ?></small><br>
                                    <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ev['venue']) ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="category-tag mb-1"><?= htmlspecialchars($ev['category']) ?></span><br>
                                    <a href="view_events.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-primary mt-1">View</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-calendar-x fs-1"></i>
                        <p class="mt-2">No upcoming events. <a href="view_events.php">Browse events</a></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- My Tickets -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-ticket me-2"></i>My Recent Tickets</span>
                    <a href="my_registrations.php" class="btn btn-sm btn-outline-primary">All Tickets</a>
                </div>
                <div class="card-body">
                    <?php if ($recentRegs): ?>
                        <?php foreach ($recentRegs as $reg): ?>
                        <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0">
                            <?php if ($reg['qr_code_path'] && file_exists('../assets/qrcodes/' . $reg['qr_code_path'])): ?>
                            <img src="../assets/qrcodes/<?= $reg['qr_code_path'] ?>" width="60" height="60" class="rounded">
                            <?php else: ?>
                            <div style="width:60px;height:60px;background:#ede9fe;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.5rem">🎫</div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <div class="fw-bold" style="font-size:.9rem"><?= htmlspecialchars($reg['title']) ?></div>
                                <div class="reg-number" style="font-size:.75rem"><?= $reg['registration_number'] ?></div>
                                <?php if ($reg['attended']): ?>
                                <span class="badge bg-success" style="font-size:.7rem">✓ Attended</span>
                                <?php else: ?>
                                <span class="badge bg-secondary" style="font-size:.7rem">Not Attended</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-ticket-perforated fs-1"></i>
                        <p class="mt-2">No registrations yet.<br><a href="view_events.php">Find events to join</a></p>
                    </div>
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
