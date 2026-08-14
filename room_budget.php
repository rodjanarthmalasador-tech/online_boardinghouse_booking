<?php
require_once __DIR__ . '/config.php';
$user = currentUser();

if (isAdmin()) {
    header('Location: rooms.php');
    exit;
}

$pdo = getDB();

$selectedRoomName = trim($_GET['room'] ?? '');
$selectedRoomPrice = (float)($_GET['price'] ?? 0);
$rooms = $pdo->query("SELECT * FROM `rooms` ORDER BY `id` ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boarding House Budget Planner</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .task-shell {
            max-width: 920px;
            margin: 32px auto;
        }

        .calc-card {
            display: grid;
            grid-template-columns: 1.3fr 0.9fr;
            gap: 24px;
            margin-top: 26px;
        }

        .result-box {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            display: none;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }

        .summary-row:last-of-type {
            border-bottom: none;
        }

        .summary-label {
            color: var(--text-muted);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .calc-card {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="brand">🏠 BoardingHouse Hub</a>
            <div class="nav-links" style="display:flex; align-items:center; flex-wrap:wrap;">
                <a href="index.php" class="nav-link">Browse Rooms</a>
                <a href="room_budget.php" class="nav-link active">Room Budget</a>
                <?php if (isAdmin()): ?>
                    <a href="reports.php" class="nav-link">Reports</a>
                <?php endif; ?>

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

    <div class="container task-shell py-4">
        <div class="page-header mb-4">
            <div>
                <h1 class="page-title">Boarding House Budget Planner</h1>
                <p class="page-subtitle">Estimate total room costs, utilities, and deposit before booking.</p>
            </div>
        </div>

        <div class="calc-card">
            <div class="card shadow-sm border-0 form-card">
                <div class="card-body p-4">
                    <form id="budgetForm" novalidate class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="roomName">Room Name *</label>
                            <select id="roomName" class="form-select" required>
                                <option value="">Select a room</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= e($room['name']); ?>" data-price="<?= (float)$room['price']; ?>" <?= ($selectedRoomName !== '' && strtolower($room['name']) === strtolower($selectedRoomName)) ? 'selected' : ''; ?>>
                                        <?= e($room['name']); ?> - <?= formatMoney($room['price']); ?> / month
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="monthlyRent">Monthly Rent *</label>
                            <input id="monthlyRent" class="form-control" type="number" min="0" value="<?= $selectedRoomPrice > 0 ? $selectedRoomPrice : ''; ?>" placeholder="5500" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="months">Stay Duration (Months) *</label>
                            <input id="months" class="form-control" type="number" min="1" placeholder="6" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="utilities">Utilities and Internet</label>
                            <input id="utilities" class="form-control" type="number" min="0" placeholder="1200">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="deposit">Security Deposit</label>
                            <input id="deposit" class="form-control" type="number" min="0" placeholder="3000">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="extraFee">Other Fees</label>
                            <input id="extraFee" class="form-control" type="number" min="0" placeholder="500">
                        </div>

                        <div class="col-12 d-flex flex-wrap gap-2 mt-2">
                            <button type="button" id="calculateBtn" class="btn btn-primary">Calculate Estimate</button>
                            <button type="reset" class="btn btn-secondary">Reset</button>
                        </div>

                        <div id="budgetError" class="alert alert-danger mb-0" style="display:none; margin-top:8px; width:100%;"></div>
                    </form>
                </div>
            </div>

            <div id="resultBox" class="result-box card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="mb-3">Cost Summary</h3>

                <div class="summary-row">
                    <span class="summary-label">Room</span>
                    <span id="resultRoom">-</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Rent for Selected Months</span>
                    <span id="resultRent">-</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Utilities</span>
                    <span id="resultUtilities">-</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Security Deposit</span>
                    <span id="resultDeposit">-</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Other Fees</span>
                    <span id="resultExtra">-</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Estimated Total</span>
                    <span id="resultTotal" style="font-size:1.2rem; font-weight:700; color:#38bdf8;">-</span>
                </div>

                <p id="resultMessage" style="margin-top:18px; color:#34d399; font-weight:600; display:none;">Estimated cost is ready.</p>
            </div>
        </div>
    </div>

    <script>
        const calculateBtn = document.getElementById('calculateBtn');
        const budgetError = document.getElementById('budgetError');
        const resultBox = document.getElementById('resultBox');

        calculateBtn.addEventListener('click', function () {
            const roomSelect = document.getElementById('roomName');
            const roomName = roomSelect.value.trim();
            const selectedRoom = roomSelect.options[roomSelect.selectedIndex];
            const presetPrice = Number(selectedRoom?.dataset?.price || 0);
            const monthlyRent = Number(document.getElementById('monthlyRent').value || presetPrice || 0);
            const months = Number(document.getElementById('months').value);
            const utilities = Number(document.getElementById('utilities').value || 0);
            const deposit = Number(document.getElementById('deposit').value || 0);
            const extraFee = Number(document.getElementById('extraFee').value || 0);

            if (!roomName || !monthlyRent || !months) {
                budgetError.textContent = 'Please select a room and provide a valid monthly rent and stay duration.';
                budgetError.style.display = 'block';
                resultBox.style.display = 'none';
                return;
            }

            budgetError.style.display = 'none';

            const rentTotal = monthlyRent * months;
            const utilityTotal = utilities * months;
            const totalCost = rentTotal + utilityTotal + deposit + extraFee;

            document.getElementById('resultRoom').textContent = roomName;
            document.getElementById('resultRent').textContent = '₱' + rentTotal.toLocaleString();
            document.getElementById('resultUtilities').textContent = '₱' + utilityTotal.toLocaleString();
            document.getElementById('resultDeposit').textContent = '₱' + deposit.toLocaleString();
            document.getElementById('resultExtra').textContent = '₱' + extraFee.toLocaleString();
            document.getElementById('resultTotal').textContent = '₱' + totalCost.toLocaleString();

            resultBox.style.display = 'block';
            document.getElementById('resultMessage').style.display = 'block';

            document.querySelector('.page-title').textContent = 'Budget Ready';
        });

        document.getElementById('roomName').addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            const presetPrice = Number(selected?.dataset?.price || 0);
            if (presetPrice > 0) {
                document.getElementById('monthlyRent').value = presetPrice;
            }
        });

        document.getElementById('budgetForm').addEventListener('reset', function () {
            setTimeout(function () {
                document.querySelector('.page-title').textContent = 'Boarding House Budget Planner';
                resultBox.style.display = 'none';
                budgetError.style.display = 'none';
            }, 10);
        });
    </script>
</body>
</html>
