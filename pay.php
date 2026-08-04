<?php
// pay.php - Online Payment Submission (GCash / Maya / Bank / Cash)
require_once __DIR__ . '/config.php';

$bookingId = (int)($_GET['booking_id'] ?? 0);
if ($bookingId <= 0) {
    die("Invalid booking ID.");
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT b.*, r.name AS room_name, r.price AS room_price, r.type AS room_type 
                       FROM `bookings` b 
                       JOIN `rooms` r ON b.room_id = r.id 
                       WHERE b.id = ?");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking not found.");
}

$user = currentUser();
$error = '';
$message = '';

// Check existing payment
$pStmt = $pdo->prepare("SELECT * FROM `payments` WHERE `booking_id` = ? ORDER BY `id` DESC LIMIT 1");
$pStmt->execute([$bookingId]);
$payment = $pStmt->fetch();

// Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_payment') {
    $paymentMethod   = trim($_POST['payment_method'] ?? 'GCash');
    $referenceNumber = trim($_POST['reference_number'] ?? '');
    $amount          = (float)($_POST['amount'] ?? $booking['room_price']);
    $proofImageName  = null;

    // Handle File Upload if provided
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['proof_image']['tmp_name'];
        $fileName    = $_FILES['proof_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = 'proof_' . $bookingId . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/uploads/payments/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }

            $destPath = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $proofImageName = $newFileName;
            } else {
                $error = "Error saving uploaded payment receipt.";
            }
        } else {
            $error = "Invalid file format. Please upload JPG, PNG, WEBP, or PDF.";
        }
    }

    if (!$error) {
        try {
            $inStmt = $pdo->prepare("INSERT INTO `payments` (`booking_id`, `payment_method`, `reference_number`, `proof_image`, `amount`, `status`) VALUES (?, ?, ?, ?, ?, 'Pending Verification')");
            $inStmt->execute([$bookingId, $paymentMethod, $referenceNumber, $proofImageName, $amount]);

            $message = "Payment details submitted! Management will verify your transaction.";
            
            // Refresh payment info
            $pStmt->execute([$bookingId]);
            $payment = $pStmt->fetch();
        } catch (Exception $e) {
            $error = "Payment submission failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Online Payment - Booking #<?= $bookingId; ?></title>
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary sticky-top">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand fw-bold" href="index.php">🏠 BoardingHouse Hub</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a href="index.php" class="nav-link">Browse Rooms</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a href="my_bookings.php" class="nav-link">My Bookings</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-3 px-lg-4 py-4 py-lg-5">
        <div class="page-header" style="max-width: 650px; margin: 0 auto 24px auto;">
            <div>
                <h1 class="page-title">Submit Online Payment</h1>
                <p class="page-subtitle">Reservation for <?= e($booking['room_name']); ?> (#<?= $bookingId; ?>)</p>
            </div>
            <a href="receipt.php?id=<?= $bookingId; ?>" target="_blank" class="btn btn-secondary btn-sm">📄 View Receipt</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success" style="max-width: 650px; margin: 0 auto 24px auto;">✓ <?= e($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" style="max-width: 650px; margin: 0 auto 24px auto;">❌ <?= e($error); ?></div>
        <?php endif; ?>

        <?php if ($payment): ?>
            <div class="alert alert-success" style="max-width: 650px; margin: 0 auto 24px auto;">
                <strong>Payment Status:</strong> <?= e($payment['status']); ?>
                <br>
                <span style="font-size:0.85rem;">Method: <strong><?= e($payment['payment_method']); ?></strong> | Ref #: <strong><?= e($payment['reference_number'] ?: 'N/A'); ?></strong></span>
                <?php if ($payment['proof_image']): ?>
                    <div style="margin-top: 8px;">
                        <a href="uploads/payments/<?= e($payment['proof_image']); ?>" target="_blank" style="color:#38bdf8; font-weight:600;">🖼️ View Uploaded Receipt Photo</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="pay-card shadow-lg">
            <!-- Account Details Box -->
            <div class="account-box">
                <div style="font-weight: 700; color:#38bdf8; margin-bottom: 8px;">💳 Boarding House Payment Accounts:</div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                    <div>📱 <strong>GCash:</strong> 0912-345-6789</div>
                    <div>💳 <strong>Maya:</strong> 0912-345-6789</div>
                    <div>🏦 <strong>BDO Bank:</strong> 1234-5678-9012</div>
                    <div>💵 <strong>Cash:</strong> Pay upon Move-In</div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="action" value="submit_payment">

                <div class="col-12">
                    <label class="form-label">Select Payment Method *</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="GCash">📱 GCash</option>
                        <option value="Maya">💳 Maya</option>
                        <option value="Bank Transfer">🏦 Bank Transfer (BDO / BPI)</option>
                        <option value="Cash on Check-in">💵 Cash on Check-in</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Amount Paid (₱) *</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required value="<?= e($booking['room_price']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Reference / Ref #</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="e.g. 1002345892 (Optional for Cash)">
                </div>

                <div class="col-12">
                    <label class="form-label">Upload Proof of Payment (Screenshot / Receipt)</label>
                    <input type="file" name="proof_image" class="form-control" accept="image/*,.pdf">
                    <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Upload screenshot of your GCash/Maya confirmation or deposit slip.</div>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                    <a href="my_bookings.php" class="btn btn-secondary">Back to My Bookings</a>
                    <button type="submit" class="btn btn-primary">Submit Payment Details</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
