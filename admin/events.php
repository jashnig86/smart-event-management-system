<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdminLogin();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Delete image if exists
    $ev = $pdo->prepare("SELECT image FROM events WHERE id=?");
    $ev->execute([$id]);
    $event = $ev->fetch();
    if ($event && $event['image'] && file_exists('../assets/uploads/' . $event['image'])) {
        unlink('../assets/uploads/' . $event['image']);
    }
    $pdo->prepare("DELETE FROM events WHERE id=?")->execute([$id]);
    flash('success', 'Event deleted.');
    redirect(BASE_URL . '/admin/events.php');
}

// Filters
$status   = $_GET['status'] ?? '';
$search   = $_GET['search'] ?? '';
$sql = "SELECT e.*, u.name as organizer,
        (SELECT COUNT(*) FROM registrations WHERE event_id=e.id) as reg_count
        FROM events e JOIN users u ON e.user_id=u.id WHERE 1=1";
$params = [];
if ($status) { $sql .= " AND e.status=?"; $params[] = $status; }
if ($search) { $sql .= " AND (e.title LIKE ? OR e.venue LIKE ?)"; $q="%$search%"; $params=array_merge($params,[$q,$q]); }
$sql .= " ORDER BY e.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Events - Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="admin-content">
    <?php showFlash(); ?>
    <div class="page-header">
        <h1><i class="bi bi-calendar3 me-2"></i>Manage Events</h1>
        <p>View, edit and manage all submitted events</p>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by title or venue..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending"  <?= $status==='pending'  ? 'selected':'' ?>>Pending</option>
                        <option value="approved" <?= $status==='approved' ? 'selected':'' ?>>Approved</option>
                        <option value="rejected" <?= $status==='rejected' ? 'selected':'' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="events.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list me-2"></i>All Events (<?= count($events) ?>)</span>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Event</th>
                        <th>Organizer</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Price</th>
                        <th>Registrations</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($events): ?>
                    <?php foreach ($events as $i => $ev): ?>
                    <tr>
                        <td class="text-muted"><?= $i+1 ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($ev['title']) ?></div>
                            <span class="category-tag"><?= htmlspecialchars($ev['category']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($ev['organizer']) ?></td>
                        <td><small><?= date('d M Y', strtotime($ev['date'])) ?></small></td>
                        <td><small><?= htmlspecialchars(substr($ev['venue'], 0, 30)) ?>...</small></td>
                        <td><?= $ev['price'] > 0 ? '₹'.number_format($ev['price'],2) : '<span class="text-success fw-bold">Free</span>' ?></td>
                        <td>
                            <span class="badge bg-primary"><?= $ev['reg_count'] ?>/<?= $ev['capacity'] ?></span>
                        </td>
                        <td>
                            <span class="badge-status badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?= $ev['id'] ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if ($ev['status'] === 'pending'): ?>
                                <a href="approve_event.php" class="btn btn-sm btn-warning" title="Review">
                                    <i class="bi bi-check2"></i>
                                </a>
                                <?php endif; ?>
                                <a href="events.php?delete=<?= $ev['id'] ?>" class="btn btn-sm btn-danger btn-delete" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- View Modal -->
                    <div class="modal fade" id="viewModal<?= $ev['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content border-0 rounded-4 shadow-lg">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title fw-bold"><?= htmlspecialchars($ev['title']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <?php if ($ev['image'] && file_exists('../assets/uploads/' . $ev['image'])): ?>
                                    <img src="../assets/uploads/<?= htmlspecialchars($ev['image']) ?>" class="img-fluid rounded mb-3" style="max-height:220px;width:100%;object-fit:cover">
                                    <?php endif; ?>
                                    <p><?= nl2br(htmlspecialchars($ev['description'])) ?></p>
                                    <table class="table table-sm mt-3">
                                        <tr><td class="fw-bold">Organizer</td><td><?= htmlspecialchars($ev['organizer']) ?></td></tr>
                                        <tr><td class="fw-bold">Date</td><td><?= date('d M Y, h:i A', strtotime($ev['date'])) ?></td></tr>
                                        <tr><td class="fw-bold">Venue</td><td><?= htmlspecialchars($ev['venue']) ?></td></tr>
                                        <tr><td class="fw-bold">Category</td><td><?= htmlspecialchars($ev['category']) ?></td></tr>
                                        <tr><td class="fw-bold">Price</td><td><?= $ev['price'] > 0 ? '₹'.number_format($ev['price'],2) : 'Free' ?></td></tr>
                                        <tr><td class="fw-bold">Capacity</td><td><?= $ev['capacity'] ?> (<?= $ev['reg_count'] ?> registered)</td></tr>
                                        <tr><td class="fw-bold">Status</td><td><span class="badge-status badge-<?= $ev['status'] ?>"><?= ucfirst($ev['status']) ?></span></td></tr>
                                        <?php if ($ev['rejection_reason']): ?>
                                        <tr><td class="fw-bold">Rejection Reason</td><td class="text-danger"><?= htmlspecialchars($ev['rejection_reason']) ?></td></tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">No events found.</td></tr>
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
