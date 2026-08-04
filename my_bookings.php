<?php
// my_bookings.php - Tenant Personal Booking Tracker & Payment Portal
require_once __DIR__ . '/config.php';

requireAuth();
$user = currentUser();
$pdo = getDB();

// Fetch personal bookings with payment status
$stmt = $pdo->prepare("SELECT b.*, r.name AS room_name, r.price AS room_price, r.type AS room_type, r.floor AS room_floor,
                              p.status AS payment_status, 
                              p.payment_method AS payment_method, 
                              p.reference_number
                       FROM `bookings` b 
                       JOIN `rooms` r ON b.room_id = r.id 
                       LEFT JOIN `payments` p ON p.id = (
                           SELECT id FROM `payments` WHERE booking_id = b.id ORDER BY id DESC LIMIT 1
                       )
                       WHERE b.user_id = ? OR (b.tenant_email != '' AND b.tenant_email = ?) 
                       ORDER BY b.id DESC");
$stmt->execute([$user['id'], $user['email']]);
$myBookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Boarding House</title>
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
                    <li class="nav-item"><a href="my_bookings.php" class="nav-link active">My Bookings</a></li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item"><a href="rooms.php" class="nav-link">Rooms CRUD</a></li>
                        <li class="nav-item"><a href="bookings.php" class="nav-link">Bookings CRUD</a></li>
                    <?php endif; ?>
                    <li class="nav-item d-flex align-items-center gap-2 ms-lg-2">
                        <span class="navbar-text text-info small">👤 <?= e($user['name']); ?> (<?= ucfirst($user['role']); ?>)</span>
                        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-3 px-lg-4 py-4 py-lg-5">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Booking Tracker</h1>
                <p class="page-subtitle">Track room reservation approval and submit online payments (GCash / Maya / Bank).</p>
            </div>
        </div>

        <div class="table-container shadow-sm">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Reserved Room</th>
                        <th>Check-in Date</th>
                        <th>Booking Status</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($myBookings) === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; color:var(--text-muted); padding:32px;">
                                You have not submitted any room reservations yet.
                                <br><br>
                                <a href="index.php" class="btn btn-primary btn-sm">Browse Available Rooms</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($myBookings as $b): ?>
                            <?php 
                                $payStatus = $b['payment_status'] ?? 'Unpaid';
                            ?>
                            <tr>
                                <td>#<?= $b['id']; ?></td>
                                <td>
                                    <strong><?= e($b['room_name']); ?></strong>
                                    <div style="font-size:0.8rem; color:#38bdf8;"><?= formatMoney($b['room_price']); ?> / month (<?= e($b['room_type']); ?>)</div>
                                </td>
                                <td><?= !empty($b['check_in_date']) ? date('M d, Y', strtotime((string)$b['check_in_date'])) : (!empty($b['move_in_date']) ? date('M d, Y', strtotime((string)$b['move_in_date'])) : 'Pending Date'); ?></td>
                                <td>
                                    <span class="badge badge-<?= strtolower($b['status']); ?>">
                                        <?= e(ucfirst($b['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= strtolower(str_replace(' ', '', $payStatus)); ?>">
                                        <?= e($payStatus); ?>
                                    </span>
                                    <?php if (!empty($b['reference_number'])): ?>
                                        <div style="font-size:0.75rem; color:var(--text-muted);">Ref: <?= e($b['reference_number']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="pay.php?booking_id=<?= $b['id']; ?>" class="btn btn-primary btn-sm">
                                            💳 Pay Online
                                        </a>
                                        <a href="receipt.php?id=<?= $b['id']; ?>" target="_blank" class="btn btn-secondary btn-sm">
                                            📄 PDF Receipt
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
