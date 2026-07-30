<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireUserLogin();

$userId = $_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $date        = $_POST['date'] ?? '';
    $venue       = sanitize($_POST['venue'] ?? '');
    $category    = sanitize($_POST['category'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $capacity    = (int)($_POST['capacity'] ?? 0);

    if (!$title)    $errors[] = 'Event title is required.';
    if (!$date)     $errors[] = 'Event date is required.';
    if (!$venue)    $errors[] = 'Venue is required.';
    if (!$category) $errors[] = 'Category is required.';
    if ($capacity < 1) $errors[] = 'Capacity must be at least 1.';

    // Handle image
    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $maxSize = 5 * 1024 * 1024;
        if (!in_array($_FILES['image']['type'], $allowed)) {
            $errors[] = 'Invalid image format. Use JPG, PNG, GIF or WEBP.';
        } elseif ($_FILES['image']['size'] > $maxSize) {
            $errors[] = 'Image too large. Max 5MB.';
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = 'event_' . uniqid() . '.' . strtolower($ext);
            $destPath = UPLOAD_PATH . $imageName;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
                $errors[] = 'Failed to upload image.';
                $imageName = null;
            }
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO events (user_id, title, description, date, venue, category, price, capacity, image, status) VALUES (?,?,?,?,?,?,?,?,?,'pending')");
        $stmt->execute([$userId, $title, $description, $date, $venue, $category, $price, $capacity, $imageName]);
        flash('success', 'Event submitted for approval! You\'ll see it live once approved by admin.');
        redirect(BASE_URL . '/user/dashboard.php');
    }
}

$categories = ['Conference','Workshop','Concert','Sports','Seminar','Exhibition','Networking','Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create Event - EventMS</title>
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
                <li class="nav-item"><a class="nav-link text-white" href="view_events.php"><i class="bi bi-calendar3 me-1"></i>Browse Events</a></li>
                <li class="nav-item"><a class="nav-link text-white active" href="create_event.php"><i class="bi bi-plus-circle me-1"></i>Create Event</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="my_registrations.php"><i class="bi bi-ticket me-1"></i>My Tickets</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <a href="logout.php" class="btn btn-sm btn-light text-primary fw-bold">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container py-4" style="max-width:750px">
    <div class="page-header">
        <h1><i class="bi bi-plus-circle me-2"></i>Create New Event</h1>
        <p>Fill in the details below. Your event will go live after admin approval.</p>
    </div>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="form-wrapper">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Event Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Tech Conference 2025" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" rows="4" placeholder="Describe your event..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date & Time *</label>
                    <input type="datetime-local" name="date" class="form-control" value="<?= $_POST['date'] ?? '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat ?>" <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Venue *</label>
                    <input type="text" name="venue" class="form-control" placeholder="e.g. Chennai Trade Centre, Chennai" value="<?= htmlspecialchars($_POST['venue'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Price per Person (₹)</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="price" class="form-control" placeholder="0 for free" min="0" step="0.01" value="<?= $_POST['price'] ?? '0' ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Capacity (Max Attendees) *</label>
                    <input type="number" name="capacity" class="form-control" placeholder="e.g. 100" min="1" value="<?= $_POST['capacity'] ?? '' ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Event Banner Image</label>
                    <input type="file" name="image" id="event_image" class="form-control" accept="image/*">
                    <div class="form-text">Max 5MB. JPG, PNG, GIF or WEBP.</div>
                    <img id="imagePreview" src="#" alt="Preview" style="display:none;max-height:200px;margin-top:12px;border-radius:8px;object-fit:cover;width:100%">
                </div>
                <div class="col-12 d-flex gap-3 mt-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-2"></i>Submit for Approval
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>
