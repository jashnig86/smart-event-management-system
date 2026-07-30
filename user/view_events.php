<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/qr_generator.php';
requireUserLogin();

$userId = $_SESSION['user_id'];
$msg = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_event'])) {
    $eventId = (int)$_POST['event_id'];

    // Check event exists and is approved
    $ev = $pdo->prepare("SELECT * FROM events WHERE id = ? AND status = 'approved'");
    $ev->execute([$eventId]);
    $event = $ev->fetch();

    if (!$event) {
        flash('danger', 'Event not found or not available.');
    } else {
        // Check already registered
        $check = $pdo->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ?");
        $check->execute([$userId, $eventId]);
        if ($check->fetch()) {
            flash('warning', 'You are already registered for this event!');
        } else {
            // Check capacity
            $regCount = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ?");
            $regCount->execute([$eventId]);
            if ($regCount->fetchColumn() >= $event['capacity']) {
                flash('danger', 'Sorry, this event is at full capacity.');
            } else {
                // Generate unique registration number
                do {
                    $regNum = generateRegistrationNumber();
                    $exists = $pdo->prepare("SELECT id FROM registrations WHERE registration_number = ?");
                    $exists->execute([$regNum]);
                } while ($exists->fetch());

                // Generate QR code
                $qrFilename = 'qr_' . $regNum . '.png';
                $qrPath = QR_PATH . $qrFilename;
                generateQRCode($regNum, $qrPath);

                // Insert registration
                $ins = $pdo->prepare("INSERT INTO registrations (user_id, event_id, registration_number, qr_code_path) VALUES (?,?,?,?)");
                $ins->execute([$userId, $eventId, $regNum, $qrFilename]);
                flash('success', 'Successfully registered! Your QR ticket: <strong>' . $regNum . '</strong>');
            }
        }
    }
    redirect(BASE_URL . '/user/view_events.php');
}

// Filters
$category = $_GET['category'] ?? '';
$search   = $_GET['search'] ?? '';

$sql = "SELECT e.*, u.name as organizer,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) as reg_count
        FROM events e JOIN users u ON e.user_id = u.id
        WHERE e.status = 'approved'";
$params = [];
if ($category) { $sql .= " AND e.category = ?"; $params[] = $category; }
if ($search)   { $sql .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.venue LIKE ?)"; $q = "%$search%"; $params = array_merge($params, [$q,$q,$q]); }
$sql .= " ORDER BY e.date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// My registrations (to check which events user is registered for)
$myRegStmt = $pdo->prepare("SELECT event_id FROM registrations WHERE user_id = ?");
$myRegStmt->execute([$userId]);
$myRegEventIds = array_column($myRegStmt->fetchAll(), 'event_id');

$categories = ['Conference','Workshop','Concert','Sports','Seminar','Exhibition','Networking','Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Browse Events - EventMS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
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
                <li class="nav-item"><a class="nav-link text-white active" href="view_events.php"><i class="bi bi-calendar3 me-1"></i>Browse Events</a></li>
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
    <div class="page-header">
        <h1><i class="bi bi-calendar3 me-2"></i>Browse Events</h1>
        <p>Discover and register for amazing events</p>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search events..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>" <?= $category === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Events Grid -->
    <div class="row g-4">
        <?php if ($events): ?>
        <?php foreach ($events as $ev): ?>
        <?php
            $isRegistered = in_array($ev['id'], $myRegEventIds);
            $isFull = $ev['reg_count'] >= $ev['capacity'];
            $isPast = strtotime($ev['date']) < time();
        ?>
        <div class="col-sm-6 col-xl-3">
            <div class="event-card">
                <?php if ($ev['image'] && file_exists('../assets/uploads/' . $ev['image'])): ?>
                <img src="../assets/uploads/<?= htmlspecialchars($ev['image']) ?>" alt="Event Image">
                <?php else: ?>
                <div class="no-image">🎪</div>
                <?php endif; ?>
                <div class="event-card-body">
                    <span class="category-tag mb-2"><?= htmlspecialchars($ev['category']) ?></span>
                    <h5><?= htmlspecialchars($ev['title']) ?></h5>
                    <p class="event-meta mb-1"><i class="bi bi-calendar"></i><?= date('d M Y', strtotime($ev['date'])) ?></p>
                    <p class="event-meta mb-1"><i class="bi bi-clock"></i><?= date('h:i A', strtotime($ev['date'])) ?></p>
                    <p class="event-meta mb-2"><i class="bi bi-geo-alt"></i><?= htmlspecialchars($ev['venue']) ?></p>
                    <p class="event-meta mb-2"><i class="bi bi-person"></i>By <?= htmlspecialchars($ev['organizer']) ?></p>
                    <div class="mt-auto d-flex justify-content-between align-items-center">
                        <?php if ($ev['price'] > 0): ?>
                        <span class="price-badge">₹<?= number_format($ev['price'], 2) ?></span>
                        <?php else: ?>
                        <span class="price-badge free">FREE</span>
                        <?php endif; ?>
                        <small class="text-muted"><?= $ev['reg_count'] ?>/<?= $ev['capacity'] ?> seats</small>
                    </div>
                    <!-- Capacity bar -->
                    <div class="progress mt-2" style="height:4px">
                        <div class="progress-bar <?= $isFull ? 'bg-danger' : 'bg-success' ?>"
                             style="width:<?= min(100, ($ev['reg_count']/$ev['capacity'])*100) ?>%"></div>
                    </div>
                </div>
                <div class="event-card-footer">
                    <?php if ($isPast): ?>
                    <button class="btn btn-secondary btn-sm w-100" disabled>Event Ended</button>
                    <?php elseif ($isRegistered): ?>
                    <a href="my_registrations.php" class="btn btn-success btn-sm w-100"><i class="bi bi-check-circle me-1"></i>Registered</a>
                    <?php elseif ($isFull): ?>
                    <button class="btn btn-danger btn-sm w-100" disabled>Full</button>
                    <?php else: ?>
                    <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#regModal<?= $ev['id'] ?>">
                        <i class="bi bi-ticket me-1"></i>Register Now
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Register Modal -->
        <div class="modal fade" id="regModal<?= $ev['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow-lg">
                    <div class="modal-header border-0">
                        <h5 class="modal-title fw-bold"><?= htmlspecialchars($ev['title']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p><?= nl2br(htmlspecialchars(substr($ev['description'], 0, 300))) ?>...</p>
                        <table class="table table-sm">
                            <tr><td class="fw-bold">Date</td><td><?= date('d M Y, h:i A', strtotime($ev['date'])) ?></td></tr>
                            <tr><td class="fw-bold">Venue</td><td><?= htmlspecialchars($ev['venue']) ?></td></tr>
                            <tr><td class="fw-bold">Price</td><td><?= $ev['price'] > 0 ? '₹'.number_format($ev['price'],2) : 'FREE' ?></td></tr>
                            <tr><td class="fw-bold">Available</td><td><?= $ev['capacity'] - $ev['reg_count'] ?> seats</td></tr>
                        </table>
                        <div class="alert alert-info small">A unique QR code will be generated for your ticket.</div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST">
                            <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                            <input type="hidden" name="register_event" value="1">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-ticket me-1"></i>Confirm Registration</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="col-12 text-center py-5">
            <div style="font-size:4rem">🎪</div>
            <h4 class="mt-3 text-muted">No approved events found</h4>
            <p class="text-muted">Events you create are <strong>pending admin approval</strong> before appearing here.<br>
            Ask your admin to approve events at <code>admin/login.php</code>, or try different filters.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
