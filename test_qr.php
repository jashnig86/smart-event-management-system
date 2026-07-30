<?php
/**
 * QR CODE TEST PAGE
 * Visit: http://localhost/event-management/test_qr.php
 * DELETE THIS FILE after confirming QR codes work!
 */
require_once 'includes/db.php';
require_once 'includes/qr_generator.php';

$testNum = generateRegistrationNumber();
$outDir  = __DIR__ . '/assets/qrcodes/';
if (!is_dir($outDir)) mkdir($outDir, 0755, true);

$filename = 'test_qr_' . time() . '.png';
$outPath  = $outDir . $filename;

$ok = generateQRCode($testNum, $outPath);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>QR Test</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="p-5" style="background:#f1f5f9">
<div class="card mx-auto" style="max-width:480px">
<div class="card-body p-4 text-center">
    <h4 class="fw-bold mb-3">QR Code Generator Test</h4>

    <?php if ($ok && file_exists($outPath)): ?>
    <div class="alert alert-success">✅ QR code generated successfully!</div>
    <img src="assets/qrcodes/<?= $filename ?>" style="border:4px solid #e2e8f0;border-radius:8px;width:220px;height:220px">
    <div class="mt-3">
        <strong>Registration Number:</strong><br>
        <code style="font-size:1rem"><?= $testNum ?></code>
    </div>
    <p class="text-muted mt-3 small">Scan this with your phone camera — it should read: <strong><?= $testNum ?></strong></p>
    <?php else: ?>
    <div class="alert alert-danger">
        ❌ QR generation failed.<br><br>
        <strong>Possible causes:</strong><br>
        • PHP GD extension not enabled<br>
        • <code>assets/qrcodes/</code> folder not writable<br><br>
        <strong>Fix:</strong> Open <code>php.ini</code> in XAMPP, find <code>;extension=gd</code>, remove the <code>;</code>, restart Apache.
    </div>
    <?php endif; ?>

    <hr>
    <a href="user/login.php" class="btn btn-primary">Go to App</a>
    <p class="text-muted small mt-3">⚠️ Delete <code>test_qr.php</code> when done.</p>
</div>
</div>
</body>
</html>
