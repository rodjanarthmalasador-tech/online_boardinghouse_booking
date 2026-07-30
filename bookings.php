<?php
// bookings.php - Simple CRUD for Managing Booking Requests & Payment Verification (Admin Only)
require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDB();
$user = currentUser();
$message = '';
$error = '';

// ─── CRUD Actions ─────────────────────────────────────────────────────────────

// UPDATE Booking Status (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? 'Pending');

    if ($bookingId > 0 && in_array(strtolower($newStatus), ['pending', 'approved', 'rejected'])) {
        try {
            $stmt = $pdo->prepare("UPDATE `bookings` SET `status` = ? WHERE `id` = ?");
            $stmt->execute([$newStatus, $bookingId]);

            $bStmt = $pdo->prepare("SELECT `room_id` FROM `bookings` WHERE `id` = ?");
            $bStmt->execute([$bookingId]);
            $roomId = $bStmt->fetchColumn();

            if ($roomId) {
                if (strtolower($newStatus) === 'approved') {
                    $pdo->prepare("UPDATE `rooms` SET `status` = 'Occupied' WHERE `id` = ?")->execute([$roomId]);
                } else if (strtolower($newStatus) === 'rejected') {
                    $pdo->prepare("UPDATE `rooms` SET `status` = 'Available' WHERE `id` = ?")->execute([$roomId]);
                }
            }

            $message = "Booking #{$bookingId} marked as '{$newStatus}'!";
        } catch (Exception $e) {
            $error = "Error updating booking: " . $e->getMessage();
        }
    }
}

// VERIFY Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_payment') {
    $paymentId = (int)($_POST['payment_id'] ?? 0);
    $bookingId = (int)($_POST['booking_id'] ?? 0);

    if ($paymentId > 0 && $bookingId > 0) {
        try {
            $pdo->prepare("UPDATE `payments` SET `status` = 'Verified' WHERE `id` = ?")->execute([$paymentId]);
            $pdo->prepare("UPDATE `bookings` SET `status` = 'Approved' WHERE `id` = ?")->execute([$bookingId]);
            
            $roomId = $pdo->query("SELECT `room_id` FROM `bookings` WHERE `id` = {$bookingId}")->fetchColumn();
            if ($roomId) {
                $pdo->prepare("UPDATE `rooms` SET `status` = 'Occupied' WHERE `id` = ?")->execute([$roomId]);
            }

            $message = "Payment for Booking #{$bookingId} VERIFIED successfully!";
        } catch (Exception $e) {
            $error = "Error verifying payment: " . $e->getMessage();
        }
    }
}

// DELETE Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    if ($bookingId > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `bookings` WHERE `id` = ?");
            $stmt->execute([$bookingId]);
            $message = "Booking request deleted successfully!";
        } catch (Exception $e) {
            $error = "Error deleting booking: " . $e->getMessage();
        }
    }
}

// READ Bookings with Room, User & Payment Info
$sql = "SELECT b.*, r.name AS room_name, r.price AS room_price, r.type AS room_type, 
               u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
               p.id AS payment_id, COALESCE(p.payment_method, p.method) AS payment_method, p.reference_number, COALESCE(p.proof_image, p.receipt_photo) AS proof_image, p.status AS payment_status, p.amount AS paid_amount
        FROM `bookings` b 
        JOIN `rooms` r ON b.room_id = r.id 
        LEFT JOIN `users` u ON b.user_id = u.id 
        LEFT JOIN `payments` p ON p.id = (
            SELECT id FROM `payments` WHERE booking_id = b.id ORDER BY id DESC LIMIT 1
        )
        ORDER BY b.id DESC";
$bookings = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking & Payment Management - Boarding House</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="brand">🏠 BoardingHouse Hub</a>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Browse Rooms</a>
                <a href="rooms.php" class="nav-link">Rooms CRUD</a>
                <a href="bookings.php" class="nav-link active">Bookings CRUD</a>
                <div style="display:flex; align-items:center; gap:8px; margin-left:12px;">
                    <span style="font-size:0.85rem; color:#38bdf8;">👑 Admin (<?= e($user['name']); ?>)</span>
                    <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success">✓ <?= e($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= e($error); ?></div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <h1 class="page-title">Booking & Online Payment Management</h1>
                <p class="page-subtitle">Review booking applications, verify online payment receipts (GCash / Maya / Bank), and print PDF receipts.</p>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tenant Info</th>
                        <th>Room Reserved</th>
                        <th>Check-in Date</th>
                        <th>Payment Info</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($bookings) === 0): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; color:var(--text-muted); padding:32px;">No booking requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <?php 
                                $tenantName  = !empty($b['tenant_name']) ? $b['tenant_name'] : ($b['user_name'] ?? 'Guest Tenant');
                                $tenantPhone = !empty($b['tenant_phone']) ? $b['tenant_phone'] : ($b['user_phone'] ?? 'N/A');
                                $tenantEmail = !empty($b['tenant_email']) ? $b['tenant_email'] : ($b['user_email'] ?? 'N/A');
                                $checkInDate = !empty($b['check_in_date']) ? $b['check_in_date'] : ($b['move_in_date'] ?? null);
                                $payStatus   = $b['payment_status'] ?? 'Unpaid';
                            ?>
                            <tr>
                                <td>#<?= $b['id']; ?></td>
                                <td>
                                    <strong><?= e($tenantName); ?></strong>
                                    <div>📞 <?= e($tenantPhone); ?></div>
                                    <div style="font-size:0.8rem; color:var(--text-muted);"><?= e($tenantEmail); ?></div>
                                </td>
                                <td>
                                    <strong><?= e($b['room_name']); ?></strong>
                                    <div style="font-size:0.8rem; color:#38bdf8;"><?= formatMoney($b['room_price']); ?>/mo</div>
                                </td>
                                <td><?= $checkInDate ? date('M d, Y', strtotime((string)$checkInDate)) : 'Pending Date'; ?></td>
                                <td>
                                    <div><span class="badge badge-<?= strtolower(str_replace(' ', '', $payStatus)); ?>"><?= e($payStatus); ?></span></div>
                                    <?php if (!empty($b['payment_method'])): ?>
                                        <div style="font-size:0.8rem; margin-top:4px;">Method: <strong><?= e($b['payment_method']); ?></strong></div>
                                        <?php if (!empty($b['reference_number'])): ?>
                                            <div style="font-size:0.75rem; color:var(--text-muted);">Ref: <?= e($b['reference_number']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($b['proof_image'])): ?>
                                            <div style="margin-top:2px;">
                                                <a href="uploads/payments/<?= e($b['proof_image']); ?>" target="_blank" style="color:#38bdf8; font-size:0.8rem; font-weight:600;">🖼️ View Receipt Photo</a>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= strtolower($b['status']); ?>">
                                        <?= e(ucfirst($b['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:4px;">
                                        <div style="display:flex; gap:4px;">
                                            <a href="receipt.php?id=<?= $b['id']; ?>" target="_blank" class="btn btn-secondary btn-sm" title="View/Print PDF Receipt">
                                                📄 Receipt
                                            </a>
                                            <?php if ($b['payment_id'] && strtolower($payStatus) !== 'verified'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="verify_payment">
                                                    <input type="hidden" name="payment_id" value="<?= $b['payment_id']; ?>">
                                                    <input type="hidden" name="booking_id" value="<?= $b['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" title="Verify Payment & Approve">✓ Verify Pay</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>

                                        <div style="display:flex; gap:4px;">
                                            <?php if (strtolower($b['status']) !== 'approved'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="booking_id" value="<?= $b['id']; ?>">
                                                    <input type="hidden" name="status" value="Approved">
                                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if (strtolower($b['status']) !== 'rejected'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="booking_id" value="<?= $b['id']; ?>">
                                                    <input type="hidden" name="status" value="Rejected">
                                                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST" onsubmit="return confirm('Delete this booking record?');" style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="booking_id" value="<?= $b['id']; ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm">Del</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
