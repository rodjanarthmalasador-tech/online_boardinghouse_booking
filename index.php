<?php
// index.php - Public Room Listings & Booking Form with Room Pictures
require_once __DIR__ . '/config.php';

$pdo = getDB();
$user = currentUser();
$message = '';
$newBookingId = null;
$error = '';

// Handle Booking Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book') {
    $roomId      = (int)($_POST['room_id'] ?? 0);
    $tenantName  = trim($_POST['tenant_name'] ?? '');
    $tenantPhone = trim($_POST['tenant_phone'] ?? '');
    $tenantEmail = trim($_POST['tenant_email'] ?? '');
    $checkInDate = trim($_POST['check_in_date'] ?? '');
    $notes       = trim($_POST['notes'] ?? '');
    $userId      = $user['id'] ?? null;

    if ($roomId > 0 && $tenantName !== '' && $tenantPhone !== '' && $checkInDate !== '') {
        try {
            $cols = array_column($pdo->query("SHOW COLUMNS FROM `bookings`")->fetchAll(), 'Field');
            
            if (in_array('move_in_date', $cols)) {
                $stmt = $pdo->prepare("INSERT INTO `bookings` (`room_id`, `user_id`, `tenant_name`, `tenant_phone`, `tenant_email`, `check_in_date`, `move_in_date`, `notes`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                $stmt->execute([$roomId, $userId, $tenantName, $tenantPhone, $tenantEmail, $checkInDate, $checkInDate, $notes]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO `bookings` (`room_id`, `user_id`, `tenant_name`, `tenant_phone`, `tenant_email`, `check_in_date`, `notes`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
                $stmt->execute([$roomId, $userId, $tenantName, $tenantPhone, $tenantEmail, $checkInDate, $notes]);
            }
            
            $newBookingId = $pdo->lastInsertId();
            $_SESSION['last_booking_id'] = $newBookingId;

            $message = "Your booking request for Room #{$roomId} has been submitted successfully!";
        } catch (Exception $e) {
            $error = "Error submitting booking: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields (Name, Phone, and Check-in Date).";
    }
}

// Fetch Rooms
$typeFilter = $_GET['type'] ?? '';
$sql = "SELECT * FROM `rooms` WHERE 1=1";
$params = [];

if ($typeFilter !== '') {
    $sql .= " AND LOWER(`type`) = ?";
    $params[] = strtolower($typeFilter);
}
$sql .= " ORDER BY `id` DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boarding House Booking System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .room-img-container {
            width: 100%;
            height: 180px;
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 16px;
            position: relative;
            background: #0f172a;
        }
        .room-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .card:hover .room-img {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="brand">🏠 BoardingHouse Hub</a>
            <div class="nav-links" style="display:flex; align-items:center;">
                <a href="index.php" class="nav-link active">Browse Rooms</a>
                
                <?php if (isLoggedIn()): ?>
                    <a href="my_bookings.php" class="nav-link">My Bookings</a>
                    <?php if (isAdmin()): ?>
                        <a href="rooms.php" class="nav-link">Rooms CRUD</a>
                        <a href="bookings.php" class="nav-link">Bookings CRUD</a>
                    <?php endif; ?>
                    <div style="display:flex; align-items:center; gap:8px; margin-left:12px;">
                        <span style="font-size:0.85rem; color:#38bdf8;">👤 <?= e($user['name']); ?> (<?= ucfirst($user['role']); ?>)</span>
                        <a href="logout.php" class="btn btn-secondary btn-sm">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Log In</a>
                    <a href="register.php" class="btn btn-primary btn-sm" style="margin-left:8px;">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>✓ <?= e($message); ?></div>
                <?php if ($newBookingId): ?>
                    <div style="display:flex; gap:8px;">
                        <a href="receipt.php?id=<?= $newBookingId; ?>" target="_blank" class="btn btn-primary btn-sm" style="background:#fff; color:#0f172a;">
                            📥 Download PDF
                        </a>
                        <a href="receipt.php?id=<?= $newBookingId; ?>" target="_blank" class="btn btn-secondary btn-sm">
                            🖨️ Print Receipt
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= e($error); ?></div>
        <?php endif; ?>

        <div class="page-header">
            <div>
                <h1 class="page-title">Available Boarding House Rooms</h1>
                <p class="page-subtitle">Find your perfect stay and submit a booking request online.</p>
            </div>
            <div>
                <form method="GET" style="display: flex; gap: 8px;">
                    <select name="type" class="form-control" onchange="this.form.submit()">
                        <option value="">All Room Types</option>
                        <option value="single" <?= strtolower($typeFilter) === 'single' ? 'selected' : ''; ?>>Single</option>
                        <option value="double" <?= strtolower($typeFilter) === 'double' ? 'selected' : ''; ?>>Double</option>
                        <option value="studio" <?= strtolower($typeFilter) === 'studio' ? 'selected' : ''; ?>>Studio</option>
                        <option value="dormitory" <?= strtolower($typeFilter) === 'dormitory' ? 'selected' : ''; ?>>Dormitory / Bedspace</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="grid">
            <?php foreach ($rooms as $room): ?>
                <?php 
                    $isAvailable = strtolower($room['status']) === 'available';
                    $roomTypeLower = strtolower($room['type']);
                    $defaultImage = 'images/single.png';
                    if (str_contains($roomTypeLower, 'double')) $defaultImage = 'images/double.png';
                    else if (str_contains($roomTypeLower, 'studio')) $defaultImage = 'images/studio.png';
                    else if (str_contains($roomTypeLower, 'dorm') || str_contains($roomTypeLower, 'bedspace')) $defaultImage = 'images/dormitory.png';
                    
                    $imagePath = !empty($room['image']) ? $room['image'] : $defaultImage;
                ?>
                <div class="card">
                    <!-- Room Photo -->
                    <div class="room-img-container">
                        <img src="<?= e($imagePath); ?>" alt="<?= e($room['name']); ?>" class="room-img" onerror="this.src='<?= $defaultImage; ?>'">
                    </div>

                    <div class="card-header">
                        <span class="badge badge-<?= strtolower($room['status']); ?>">
                            <?= e(ucfirst($room['status'])); ?>
                        </span>
                        <span style="font-size: 0.85rem; color: var(--text-muted);"><?= e($room['floor']); ?></span>
                    </div>
                    <h3 class="card-title"><?= e($room['name']); ?></h3>
                    <div class="card-price"><?= formatMoney($room['price']); ?> <span style="font-size:0.8rem; font-weight:normal; color:var(--text-muted);">/ month</span></div>
                    <p class="card-desc"><?= e($room['description']); ?></p>
                    <div class="card-meta">
                        <span>👤 Capacity: <?= e($room['capacity']); ?> Person(s)</span>
                        <span>🏷️ Type: <?= e(ucfirst($room['type'])); ?></span>
                    </div>

                    <?php if ($isAvailable): ?>
                        <button class="btn btn-primary" onclick="openBookingModal(<?= $room['id']; ?>, '<?= e(addslashes($room['name'])); ?>')">
                            Book This Room
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
                            <?= e(ucfirst($room['status'])); ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Booking Modal Form -->
    <div id="bookingModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:200; backdrop-filter:blur(4px); justify-content:center; align-items:center;">
        <div class="form-card" style="width: 90%; max-width: 500px; position:relative;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 id="modalRoomTitle">Reserve Room</h3>
                <span onclick="closeBookingModal()" style="cursor:pointer; font-size:1.5rem; color:var(--text-muted);">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="book">
                <input type="hidden" name="room_id" id="modalRoomId">

                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="tenant_name" class="form-control" required placeholder="e.g. Maria Santos" value="<?= e($user['name'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone Number *</label>
                        <input type="text" name="tenant_phone" class="form-control" required placeholder="09123456789" value="<?= e($user['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="tenant_email" class="form-control" placeholder="maria@example.com" value="<?= e($user['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Target Check-in Date *</label>
                    <input type="date" name="check_in_date" class="form-control" required min="<?= date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Special Notes / Requirements</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Any special requests..."></textarea>
                </div>

                <div style="display:flex; gap:12px; justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeBookingModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Booking</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openBookingModal(roomId, roomName) {
            document.getElementById('modalRoomId').value = roomId;
            document.getElementById('modalRoomTitle').innerText = 'Reserve: ' + roomName;
            document.getElementById('bookingModal').style.display = 'flex';
        }
        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
        }
    </script>
</body>
</html>
