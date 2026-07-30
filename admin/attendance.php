<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdminLogin();

$adminId = $_SESSION['admin_id'];
$result  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registration_number'])) {
    $regNum = strtoupper(trim($_POST['registration_number']));

    // Find registration
    $stmt = $pdo->prepare("
        SELECT r.*, e.title as event_title, e.date as event_date, e.venue,
               u.name as attendee_name, u.email as attendee_email,
               a.id as already_attended, a.marked_at
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        JOIN users u ON r.user_id = u.id
        LEFT JOIN attendance a ON a.registration_id = r.id
        WHERE r.registration_number = ?
    ");
    $stmt->execute([$regNum]);
    $reg = $stmt->fetch();

    if (!$reg) {
        $result = ['type' => 'error', 'message' => "Registration number <strong>$regNum</strong> not found."];
    } elseif ($reg['already_attended']) {
        $result = [
            'type' => 'warning',
            'message' => "Already marked as attended on " . date('d M Y h:i A', strtotime($reg['marked_at'])) . "! Possible proxy attempt.",
            'reg' => $reg
        ];
    } else {
        // Mark attendance
        $ins = $pdo->prepare("INSERT INTO attendance (registration_id, marked_by) VALUES (?,?)");
        $ins->execute([$reg['id'], $adminId]);
        $result = [
            'type' => 'success',
            'message' => "✅ Attendance marked successfully!",
            'reg' => $reg
        ];
    }
}

// Recent attendance records
$recentAttendance = $pdo->query("
    SELECT a.*, r.registration_number, e.title, u.name as attendee, adm.name as marked_by_name
    FROM attendance a
    JOIN registrations r ON a.registration_id = r.id
    JOIN events e ON r.event_id = e.id
    JOIN users u ON r.user_id = u.id
    JOIN admins adm ON a.marked_by = adm.id
    ORDER BY a.marked_at DESC
    LIMIT 15
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mark Attendance - Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="admin-content">
    <?php showFlash(); ?>
    <div class="page-header">
        <h1><i class="bi bi-qr-code-scan me-2"></i>Mark Attendance</h1>
        <p>Scan or enter the registration number from the attendee's QR ticket</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="scanner-box">
                <div class="big-icon">📱</div>
                <h4 class="fw-bold mb-1">Scan QR / Enter Code</h4>
                <p class="text-muted mb-4">Enter the registration number shown on the attendee's QR ticket to verify identity and mark attendance</p>

                <form method="POST" id="scanForm">
                    <div class="mb-3">
                        <input type="text"
                               name="registration_number"
                               id="reg_number_scan"
                               class="form-control scan-input form-control-lg"
                               placeholder="EVT-20241201-ABCDEF"
                               autocomplete="off"
                               autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-check-circle me-2"></i>Verify & Mark Attendance
                    </button>
                </form>

                <?php if ($result): ?>
                <div class="mt-4 alert alert-<?= $result['type'] === 'success' ? 'success' : ($result['type'] === 'warning' ? 'warning' : 'danger') ?>">
                    <div><?= $result['message'] ?></div>
                    <?php if (isset($result['reg'])): ?>
                    <hr>
                    <div class="fw-bold"><?= htmlspecialchars($result['reg']['attendee_name']) ?></div>
                    <small><?= htmlspecialchars($result['reg']['attendee_email']) ?></small><br>
                    <small><i class="bi bi-calendar me-1"></i><?= htmlspecialchars($result['reg']['event_title']) ?></small><br>
                    <small><i class="bi bi-map me-1"></i><?= htmlspecialchars($result['reg']['venue']) ?></small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history me-2"></i>Recent Attendance Records
                </div>
                <div class="table-responsive">
                    <?php if ($recentAttendance): ?>
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Attendee</th>
                                <th>Event</th>
                                <th>Reg. No.</th>
                                <th>Marked At</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAttendance as $att): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($att['attendee']) ?></td>
                                <td><?= htmlspecialchars($att['title']) ?></td>
                                <td><span class="reg-number" style="font-size:.7rem"><?= $att['registration_number'] ?></span></td>
                                <td><small><?= date('d M, h:i A', strtotime($att['marked_at'])) ?></small></td>
                                <td><small><?= htmlspecialchars($att['marked_by_name']) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-clipboard-x fs-1"></i>
                        <p class="mt-2">No attendance records yet.</p>
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
