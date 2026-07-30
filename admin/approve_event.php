<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdminLogin();

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventId = (int)$_POST['event_id'];
    $action  = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $pdo->prepare("UPDATE events SET status='approved' WHERE id=?")->execute([$eventId]);
        flash('success', 'Event approved successfully!');
    } elseif ($action === 'reject') {
        $reason = sanitize($_POST['reason'] ?? '');
        $pdo->prepare("UPDATE events SET status='rejected', rejection_reason=? WHERE id=?")->execute([$reason, $eventId]);
        flash('success', 'Event rejected.');
    }
    redirect(BASE_URL . '/admin/approve_event.php');
}

$pendingEvents = $pdo->query("
    SELECT e.*, u.name as organizer, u.email as org_email,
           (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) as reg_count
    FROM events e
    JOIN users u ON e.user_id = u.id
    WHERE e.status = 'pending'
    ORDER BY e.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Event Approvals - Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="admin-content">
    <?php showFlash(); ?>
    <div class="page-header">
        <h1><i class="bi bi-check2-circle me-2"></i>Pending Approvals</h1>
        <p>Review and approve or reject submitted events</p>
    </div>

    <?php if ($pendingEvents): ?>
    <div class="row g-4">
        <?php foreach ($pendingEvents as $ev): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-start">
                        <div class="col-md-2">
                            <?php if ($ev['image'] && file_exists('../assets/uploads/' . $ev['image'])): ?>
                            <img src="../assets/uploads/<?= htmlspecialchars($ev['image']) ?>" class="rounded" style="width:100%;height:120px;object-fit:cover">
                            <?php else: ?>
                            <div class="rounded d-flex align-items-center justify-content-center" style="height:120px;background:linear-gradient(135deg,#4f46e5,#06b6d4);font-size:2.5rem;color:rgba(255,255,255,.5)">🎪</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-7">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h5 class="mb-0 fw-bold"><?= htmlspecialchars($ev['title']) ?></h5>
                                <span class="category-tag"><?= htmlspecialchars($ev['category']) ?></span>
                            </div>
                            <p class="text-muted mb-2" style="font-size:.9rem"><?= htmlspecialchars(substr($ev['description'], 0, 200)) ?>...</p>
                            <div class="row g-2" style="font-size:.85rem">
                                <div class="col-sm-6"><i class="bi bi-calendar text-primary me-1"></i><?= date('d M Y, h:i A', strtotime($ev['date'])) ?></div>
                                <div class="col-sm-6"><i class="bi bi-geo-alt text-primary me-1"></i><?= htmlspecialchars($ev['venue']) ?></div>
                                <div class="col-sm-6"><i class="bi bi-person text-primary me-1"></i><?= htmlspecialchars($ev['organizer']) ?> (<?= htmlspecialchars($ev['org_email']) ?>)</div>
                                <div class="col-sm-3"><i class="bi bi-cash text-primary me-1"></i><?= $ev['price'] > 0 ? '₹'.number_format($ev['price'],2) : 'FREE' ?></div>
                                <div class="col-sm-3"><i class="bi bi-people text-primary me-1"></i>Cap: <?= $ev['capacity'] ?></div>
                            </div>
                            <div class="text-muted mt-2" style="font-size:.8rem">Submitted: <?= date('d M Y h:i A', strtotime($ev['created_at'])) ?></div>
                        </div>
                        <div class="col-md-3 d-flex flex-column gap-2">
                            <form method="POST">
                                <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-check-circle me-1"></i>Approve
                                </button>
                            </form>
                            <button class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal" data-event-id="<?= $ev['id'] ?>">
                                <i class="bi bi-x-circle me-1"></i>Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <div style="font-size:4rem">✅</div>
            <h4 class="mt-3 text-muted">All caught up!</h4>
            <p class="text-muted">No events pending approval right now.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-2"></i>Reject Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="event_id" id="reject_event_id">
                    <input type="hidden" name="action" value="reject">
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection (optional)</label>
                        <textarea name="reason" class="form-control" rows="4" placeholder="Provide a reason to help the organizer improve their submission..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle me-1"></i>Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
