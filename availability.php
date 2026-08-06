<?php
// availability.php - Tenant-facing Room Availability Calendar
require_once __DIR__ . '/config.php';

$pdo = getDB();
$user = currentUser();

date_default_timezone_set('Asia/Manila');
$today = new DateTimeImmutable('today');
$days = [];
for ($i = 0; $i < 14; $i++) {
    $days[] = $today->modify("+{$i} days");
}

$rooms = $pdo->query("SELECT * FROM `rooms` ORDER BY `id` ASC")->fetchAll();

$bookingStmt = $pdo->prepare(
    "SELECT room_id, status, check_in_date, tenant_name
     FROM `bookings`
     WHERE check_in_date >= CURDATE()
       AND status IN ('Pending', 'Approved')
     ORDER BY check_in_date ASC"
);
$bookingStmt->execute();
$bookings = $bookingStmt->fetchAll();

$roomBookings = [];
foreach ($bookings as $booking) {
    if (empty($booking['check_in_date'])) {
        continue;
    }
    $roomBookings[$booking['room_id']][$booking['check_in_date']][] = $booking;
}

$totalRooms = count($rooms);
$availableRooms = 0;
$occupiedRooms = 0;
$maintenanceRooms = 0;
foreach ($rooms as $room) {
    $status = strtolower($room['status']);
    if ($status === 'available') {
        $availableRooms++;
    } elseif ($status === 'occupied') {
        $occupiedRooms++;
    } elseif ($status === 'maintenance') {
        $maintenanceRooms++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Availability - Boarding House</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="brand">🏠 BoardingHouse Hub</a>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Browse Rooms</a>
                <a href="reports.php" class="nav-link active">Reports</a>
                <?php if (isLoggedIn()): ?>
                    <a href="my_bookings.php" class="nav-link">My Bookings</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Room Availability Calendar</h1>
                <p class="page-subtitle">Check room availability for the next 14 days and see upcoming reservations.</p>
            </div>
            <div>
                <span class="badge badge-available">Available: <?= $availableRooms; ?></span>
                <span class="badge badge-approved">Occupied: <?= $occupiedRooms; ?></span>
                <span class="badge badge-rejected">Maintenance: <?= $maintenanceRooms; ?></span>
            </div>
        </div>

        <div class="availability-summary">
            <div class="summary-card">
                <div class="summary-label">Total Rooms</div>
                <div class="summary-value"><?= $totalRooms; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Available</div>
                <div class="summary-value"><?= $availableRooms; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Occupied</div>
                <div class="summary-value"><?= $occupiedRooms; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Maintenance</div>
                <div class="summary-value"><?= $maintenanceRooms; ?></div>
            </div>
        </div>

        <div class="table-container availability-table">
            <table>
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Status</th>
                        <?php foreach ($days as $day): ?>
                            <th><?= e($day->format('M d')); ?><br><span style="font-size:0.8rem; color:var(--text-muted);"><?= e($day->format('D')); ?></span></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td>
                                <strong><?= e($room['name']); ?></strong>
                                <div style="font-size:0.85rem; color:var(--text-muted);"><?= e($room['type']); ?> • <?= formatMoney($room['price']); ?></div>
                            </td>
                            <td>
                                <span class="badge badge-<?= strtolower($room['status']); ?>"><?= e($room['status']); ?></span>
                            </td>
                            <?php foreach ($days as $day): ?>
                                <?php
                                    $dateKey = $day->format('Y-m-d');
                                    $events = $roomBookings[$room['id']][$dateKey] ?? [];
                                    $cellClass = 'available';
                                    $cellText = 'Available';
                                    if (!empty($events)) {
                                        $firstEvent = $events[0];
                                        $cellClass = strtolower($firstEvent['status']) === 'approved' ? 'booked' : 'pending';
                                        $cellText = count($events) > 1 ? count($events) . ' bookings' : e($firstEvent['status']);
                                    }
                                ?>
                                <td>
                                    <div class="availability-cell <?= $cellClass; ?>">
                                        <?= $cellText; ?>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="availability-legend">
            <span class="legend-item"><span class="legend-dot available"></span>Available</span>
            <span class="legend-item"><span class="legend-dot booked"></span>Approved Booking</span>
            <span class="legend-item"><span class="legend-dot pending"></span>Pending Booking</span>
        </div>
    </div>
</body>
</html>
