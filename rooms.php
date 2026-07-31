<?php
// rooms.php - Simple CRUD for Boarding House Rooms with Photo Management (Admin Only)
require_once __DIR__ . '/config.php';
requireAdmin();

$pdo = getDB();
$user = currentUser();
$message = '';
$error = '';

// ─── CRUD Actions ─────────────────────────────────────────────────────────────

// CREATE Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name        = trim($_POST['name'] ?? '');
    $type        = trim($_POST['type'] ?? 'Single');
    $price       = (float)($_POST['price'] ?? 0);
    $capacity    = (int)($_POST['capacity'] ?? 1);
    $floor       = trim($_POST['floor'] ?? '1st Floor');
    $status      = trim($_POST['status'] ?? 'Available');
    $description = trim($_POST['description'] ?? '');
    $image       = trim($_POST['image'] ?? '');

    // Default image if empty
    if (empty($image)) {
        $typeLower = strtolower($type);
        if (str_contains($typeLower, 'double')) $image = 'images/double.png';
        else if (str_contains($typeLower, 'studio')) $image = 'images/studio.png';
        else if (str_contains($typeLower, 'dorm') || str_contains($typeLower, 'bedspace')) $image = 'images/dormitory.png';
        else $image = 'images/single.png';
    }

    if ($name !== '' && $price > 0) {
        try {
            $stmt = $pdo->prepare("INSERT INTO `rooms` (`name`, `type`, `price`, `capacity`, `floor`, `status`, `image`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $type, $price, $capacity, $floor, $status, $image, $description]);
            $message = "Room created successfully!";
        } catch (Exception $e) {
            $error = "Error adding room: " . $e->getMessage();
        }
    } else {
        $error = "Please provide valid room name and price.";
    }
}

// UPDATE Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id          = (int)($_POST['id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $type        = trim($_POST['type'] ?? 'Single');
    $price       = (float)($_POST['price'] ?? 0);
    $capacity    = (int)($_POST['capacity'] ?? 1);
    $floor       = trim($_POST['floor'] ?? '1st Floor');
    $status      = trim($_POST['status'] ?? 'Available');
    $description = trim($_POST['description'] ?? '');
    $image       = trim($_POST['image'] ?? '');

    if ($id > 0 && $name !== '') {
        try {
            $stmt = $pdo->prepare("UPDATE `rooms` SET `name`=?, `type`=?, `price`=?, `capacity`=?, `floor`=?, `status`=?, `image`=?, `description`=? WHERE `id`=?");
            $stmt->execute([$name, $type, $price, $capacity, $floor, $status, $image, $description, $id]);
            $message = "Room #{$id} updated successfully!";
        } catch (Exception $e) {
            $error = "Error updating room: " . $e->getMessage();
        }
    }
}

// DELETE Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `rooms` WHERE `id` = ?");
            $stmt->execute([$id]);
            $message = "Room deleted successfully!";
        } catch (Exception $e) {
            $error = "Error deleting room: " . $e->getMessage();
        }
    }
}

// Room to Edit
$editRoom = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM `rooms` WHERE `id` = ?");
    $stmt->execute([$editId]);
    $editRoom = $stmt->fetch();
}

// READ Rooms
$rooms = $pdo->query("SELECT * FROM `rooms` ORDER BY `id` DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management CRUD - Boarding House</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="brand">🏠 BoardingHouse Hub</a>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Browse Rooms</a>
                <a href="rooms.php" class="nav-link active">Rooms CRUD</a>
                <a href="bookings.php" class="nav-link">Bookings CRUD</a>
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
                <h1 class="page-title">Room Management (CRUD)</h1>
                <p class="page-subtitle">Add, edit, view, or remove rooms and manage photos.</p>
            </div>
            <button class="btn btn-primary" onclick="toggleAddForm()">
                <?= $editRoom ? 'Editing Room #' . $editRoom['id'] : '+ Add New Room'; ?>
            </button>
        </div>

        <!-- Create / Edit Form -->
        <div id="roomFormCard" class="form-card" style="margin-bottom: 32px; <?= $editRoom ? 'display:block;' : 'display:none;'; ?>">
            <h3><?= $editRoom ? '✏️ Edit Room #' . $editRoom['id'] : '➕ Create New Room'; ?></h3>
            <form method="POST" style="margin-top:20px;">
                <input type="hidden" name="action" value="<?= $editRoom ? 'update' : 'create'; ?>">
                <?php if ($editRoom): ?>
                    <input type="hidden" name="id" value="<?= $editRoom['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Room Name *</label>
                    <input type="text" name="name" class="form-control" required value="<?= e($editRoom['name'] ?? ''); ?>" placeholder="e.g. Room 203 - Single Standard">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Room Type *</label>
                        <select name="type" class="form-control">
                            <?php foreach (['Single', 'Double', 'Studio', 'Bedspace'] as $t): ?>
                                <option value="<?= $t; ?>" <?= ($editRoom['type'] ?? '') === $t ? 'selected' : ''; ?>><?= $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Monthly Price (₱) *</label>
                        <input type="number" step="0.01" name="price" class="form-control" required value="<?= e($editRoom['price'] ?? '3500'); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Capacity (Persons)</label>
                        <input type="number" name="capacity" class="form-control" value="<?= e($editRoom['capacity'] ?? '1'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Floor Level</label>
                        <input type="text" name="floor" class="form-control" value="<?= e($editRoom['floor'] ?? '1st Floor'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-control">
                        <option value="Available" <?= ($editRoom['status'] ?? '') === 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Occupied" <?= ($editRoom['status'] ?? '') === 'Occupied' ? 'selected' : ''; ?>>Occupied</option>
                        <option value="Maintenance" <?= ($editRoom['status'] ?? '') === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Image Path / Photo URL</label>
                    <input type="text" name="image" class="form-control" value="<?= e($editRoom['image'] ?? ''); ?>" placeholder="images/single.png">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= e($editRoom['description'] ?? ''); ?></textarea>
                </div>

                <div style="display:flex; gap:12px; justify-content:flex-end;">
                    <?php if ($editRoom): ?>
                        <a href="rooms.php" class="btn btn-secondary">Cancel Edit</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= $editRoom ? 'Save Changes' : 'Create Room'; ?></button>
                </div>
            </form>
        </div>

        <!-- READ Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>ID</th>
                        <th>Room Name</th>
                        <th>Type</th>
                        <th>Monthly Price</th>
                        <th>Capacity</th>
                        <th>Floor</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <?php 
                            $roomTypeLower = strtolower($room['type']);
                            $defaultImage = 'images/single.png';
                            if (str_contains($roomTypeLower, 'double')) $defaultImage = 'images/double.png';
                            else if (str_contains($roomTypeLower, 'studio')) $defaultImage = 'images/studio.png';
                            else if (str_contains($roomTypeLower, 'dorm') || str_contains($roomTypeLower, 'bedspace')) $defaultImage = 'images/dormitory.png';
                            
                            $imagePath = !empty($room['image']) ? $room['image'] : $defaultImage;
                        ?>
                        <tr>
                            <td>
                                <img src="<?= e($imagePath); ?>" class="table-thumb" alt="Room" onerror="this.src='<?= $defaultImage; ?>'">
                            </td>
                            <td>#<?= $room['id']; ?></td>
                            <td>
                                <strong><?= e($room['name']); ?></strong>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><?= e(substr($room['description'], 0, 40)); ?>...</div>
                            </td>
                            <td><?= e($room['type']); ?></td>
                            <td style="color:#38bdf8; font-weight:600;"><?= formatMoney($room['price']); ?></td>
                            <td><?= e($room['capacity']); ?> Pax</td>
                            <td><?= e($room['floor']); ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower($room['status']); ?>">
                                    <?= e(ucfirst($room['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <a href="rooms.php?edit=<?= $room['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="POST" onsubmit="return confirm('Delete this room?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $room['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleAddForm() {
            var formCard = document.getElementById('roomFormCard');
            if (formCard.style.display === 'none') {
                formCard.style.display = 'block';
            } else {
                formCard.style.display = 'none';
            }
        }
    </script>
</body>
</html>
