<?php
// reports.php - Boarding House Trends & Reports with Charts
require_once __DIR__ . '/config.php';

requireAdmin();

$pdo = getDB();
$user = currentUser();

// Summary counts
$roomCounts = $pdo->query("SELECT status, COUNT(*) AS total FROM `rooms` GROUP BY status")->fetchAll();
$bookingCounts = $pdo->query("SELECT status, COUNT(*) AS total FROM `bookings` GROUP BY status")->fetchAll();
$paymentCounts = $pdo->query("SELECT status, COUNT(*) AS total FROM `payments` GROUP BY status")->fetchAll();

$roomStatusMap = array_column($roomCounts, 'total', 'status');
$bookingStatusMap = array_column($bookingCounts, 'total', 'status');
$paymentStatusMap = array_column($paymentCounts, 'total', 'status');

$lastSixMonths = $pdo->query(
    "SELECT DATE_FORMAT(check_in_date, '%Y-%m') AS month_key,
            CONCAT(MONTHNAME(check_in_date), ' ', YEAR(check_in_date)) AS month_label,
            COUNT(*) AS total_bookings
     FROM `bookings`
     WHERE check_in_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY month_key, month_label
     ORDER BY month_key ASC"
)->fetchAll();

$monthLabels = [];
$monthValues = [];
foreach ($lastSixMonths as $row) {
    $monthLabels[] = $row['month_label'];
    $monthValues[] = (int)$row['total_bookings'];
}

$totalRooms = array_sum($roomStatusMap);
$totalBookings = array_sum($bookingStatusMap);
$totalPayments = array_sum($paymentStatusMap);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boarding House Trends</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <h1 class="page-title">Boarding House Trends</h1>
                <p class="page-subtitle">Visual reports for room status, booking activity, and payment trends.</p>
            </div>
        </div>

        <div class="availability-summary">
            <div class="summary-card">
                <div class="summary-label">Total Rooms</div>
                <div class="summary-value"><?= $totalRooms; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Total Bookings</div>
                <div class="summary-value"><?= $totalBookings; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Total Payments</div>
                <div class="summary-value"><?= $totalPayments; ?></div>
            </div>
        </div>

        <div class="reports-grid">
            <div class="report-card">
                <div class="report-header">
                    <h2>Room Status Distribution</h2>
                    <p>Available, Occupied, Maintenance</p>
                </div>
                <div class="report-chart">
                    <canvas id="roomsChart"></canvas>
                </div>
            </div>

            <div class="report-card">
                <div class="report-header">
                    <h2>Booking Status Breakdown</h2>
                    <p>Pending, Approved, Rejected</p>
                </div>
                <div class="report-chart">
                    <canvas id="bookingsChart"></canvas>
                </div>
            </div>

            <div class="report-card full-width">
                <div class="report-header">
                    <h2>Bookings Trend (Last 6 Months)</h2>
                    <p>Monthly reservation activity</p>
                </div>
                <div class="report-chart">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        const roomData = {
            labels: ['Available', 'Occupied', 'Maintenance'],
            datasets: [{
                data: [
                    <?= (int)($roomStatusMap['Available'] ?? 0); ?>,
                    <?= (int)($roomStatusMap['Occupied'] ?? 0); ?>,
                    <?= (int)($roomStatusMap['Maintenance'] ?? 0); ?>
                ],
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                borderWidth: 1
            }]
        };

        const bookingData = {
            labels: ['Pending', 'Approved', 'Rejected'],
            datasets: [{
                data: [
                    <?= (int)($bookingStatusMap['Pending'] ?? 0); ?>,
                    <?= (int)($bookingStatusMap['Approved'] ?? 0); ?>,
                    <?= (int)($bookingStatusMap['Rejected'] ?? 0); ?>
                ],
                backgroundColor: ['#f59e0b', '#10b981', '#ef4444'],
                borderWidth: 1
            }]
        };

        const trendData = {
            labels: <?= json_encode($monthLabels); ?>,
            datasets: [{
                label: 'Bookings',
                data: <?= json_encode($monthValues); ?>,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.15)',
                tension: 0.25,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#4f46e5'
            }]
        };

        new Chart(document.getElementById('roomsChart'), {
            type: 'doughnut',
            data: roomData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        new Chart(document.getElementById('bookingsChart'), {
            type: 'pie',
            data: bookingData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: trendData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    </script>
</body>
</html>
