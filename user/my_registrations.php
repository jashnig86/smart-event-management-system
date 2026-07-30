<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireUserLogin();

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT r.*, e.title, e.date, e.venue, e.category, e.image, e.price,
           a.id as attended, a.marked_at
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    LEFT JOIN attendance a ON a.registration_id = r.id
    WHERE r.user_id = ?
    ORDER BY r.registered_at DESC
");
$stmt->execute([$userId]);
$registrations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My Tickets - EventMS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.ticket-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
    overflow: hidden;
    margin-bottom: 20px;
    display: flex;
    position: relative;
}
.ticket-card::before {
    content: '';
    position: absolute;
    left: 200px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: repeating-linear-gradient(to bottom, #e2e8f0 0, #e2e8f0 8px, transparent 8px, transparent 16px);
}
.ticket-left {
    width: 200px;
    min-width: 200px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}
.ticket-right { flex: 1; padding: 20px 24px; }
.ticket-qr img { width: 100px; height: 100px; border-radius: 8px; background: #fff; padding: 4px; }
@media (max-width: 576px) {
    .ticket-card { flex-direction: column; }
    .ticket-card::before { display: none; }
    .ticket-left { width: 100%; min-width: 0; flex-direction: row; gap: 16px; }
}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand text-white" href="dashboard.php">🎪 EventMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link text-white" href="dashboard.php"><i class="bi bi-house me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="view_events.php"><i class="bi bi-calendar3 me-1"></i>Browse Events</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="create_event.php"><i class="bi bi-plus-circle me-1"></i>Create Event</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="my_registrations.php"><i class="bi bi-ticket me-1"></i>My Tickets</a></li>
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
    <div class="page-header">
        <h1><i class="bi bi-ticket-perforated me-2"></i>My Tickets</h1>
        <p>Your event registrations and QR tickets</p>
    </div>

    <?php if ($registrations): ?>
        <?php foreach ($registrations as $reg): ?>
        <div class="ticket-card">
            <div class="ticket-left">
                <div class="ticket-qr mb-2">
                    <?php if ($reg['qr_code_path'] && file_exists('../assets/qrcodes/' . $reg['qr_code_path'])): ?>
                    <img src="../assets/qrcodes/<?= htmlspecialchars($reg['qr_code_path']) ?>" alt="QR Code">
                    <?php else: ?>
                    <div style="width:100px;height:100px;background:rgba(255,255,255,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:2.5rem">🎫</div>
                    <?php endif; ?>
                </div>
                <div class="reg-number" style="background:rgba(255,255,255,.2);color:#fff;font-size:.7rem">
                    <?= $reg['registration_number'] ?>
                </div>
            </div>
            <div class="ticket-right">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($reg['title']) ?></h5>
                        <span class="category-tag"><?= htmlspecialchars($reg['category']) ?></span>
                    </div>
                    <div class="text-end">
                        <?php if ($reg['attended']): ?>
                        <span class="badge bg-success fs-6">✓ Attended</span>
                        <div class="text-muted small mt-1">at <?= date('d M Y h:i A', strtotime($reg['marked_at'])) ?></div>
                        <?php else: ?>
                        <span class="badge bg-warning text-dark">Pending Attendance</span>
                        <?php endif; ?>
                    </div>
                </div>
                <hr class="my-3">
                <div class="row g-2">
                    <div class="col-sm-6">
                        <div class="text-muted small"><i class="bi bi-calendar me-1"></i>Date</div>
                        <div class="fw-semibold"><?= date('d M Y, h:i A', strtotime($reg['date'])) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i>Venue</div>
                        <div class="fw-semibold"><?= htmlspecialchars($reg['venue']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small"><i class="bi bi-cash me-1"></i>Price</div>
                        <div class="fw-semibold"><?= $reg['price'] > 0 ? '₹'.number_format($reg['price'],2) : 'FREE' ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small"><i class="bi bi-clock me-1"></i>Registered</div>
                        <div class="fw-semibold"><?= date('d M Y', strtotime($reg['registered_at'])) ?></div>
                    </div>
                </div>
                <div class="mt-3">
                    <?php if ($reg['qr_code_path'] && file_exists('../assets/qrcodes/' . $reg['qr_code_path'])): ?>
                    <button class="btn btn-sm btn-outline-primary btn-download-qr"
                        data-qr-src="../assets/qrcodes/<?= htmlspecialchars($reg['qr_code_path']) ?>"
                        data-reg-num="<?= $reg['registration_number'] ?>">
                        <i class="bi bi-download me-1"></i>Download QR
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
    <div class="text-center py-5">
        <div style="font-size:5rem">🎫</div>
        <h4 class="mt-3 text-muted">No tickets yet</h4>
        <p class="text-muted">Register for events to see your tickets here.</p>
        <a href="view_events.php" class="btn btn-primary mt-2"><i class="bi bi-calendar3 me-2"></i>Browse Events</a>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
