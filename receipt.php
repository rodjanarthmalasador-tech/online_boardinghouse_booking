<?php
// receipt.php - Printable & Downloadable PDF Booking Receipt
require_once __DIR__ . '/config.php';

$bookingId = (int)($_GET['id'] ?? 0);
if ($bookingId <= 0) {
    die("Invalid booking ID.");
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT b.*, r.name AS room_name, r.price AS room_price, r.type AS room_type, r.floor AS room_floor, r.description AS room_desc,
                              p.payment_method, p.reference_number, p.status AS payment_status, p.created_at AS payment_date
                       FROM `bookings` b 
                       JOIN `rooms` r ON b.room_id = r.id 
                       LEFT JOIN `payments` p ON p.id = (
                           SELECT id FROM `payments` WHERE booking_id = b.id ORDER BY id DESC LIMIT 1
                       )
                       WHERE b.id = ?");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Booking receipt not found.");
}

// Security check
$user = currentUser();
$isOwner = false;

if (isAdmin()) {
    $isOwner = true;
} else if ($user && ($booking['user_id'] == $user['id'] || strtolower($booking['tenant_email'] ?? '') == strtolower($user['email'] ?? ''))) {
    $isOwner = true;
} else if (isset($_SESSION['last_booking_id']) && $_SESSION['last_booking_id'] == $bookingId) {
    $isOwner = true;
} else if (!$user && !empty($booking['tenant_email'])) {
    $isOwner = true;
}

if (!$isOwner) {
    requireAuth();
}

$tenantName  = !empty($booking['tenant_name']) ? $booking['tenant_name'] : 'Tenant Guest';
$tenantPhone = !empty($booking['tenant_phone']) ? $booking['tenant_phone'] : 'N/A';
$tenantEmail = !empty($booking['tenant_email']) ? $booking['tenant_email'] : 'N/A';

// Handle null dates safely
$rawCheckIn  = !empty($booking['check_in_date']) ? $booking['check_in_date'] : ($booking['move_in_date'] ?? null);
$checkInDate = !empty($rawCheckIn) ? date('F d, Y', strtotime((string)$rawCheckIn)) : 'Pending Confirmation';

$rawCreatedAt = !empty($booking['created_at']) ? $booking['created_at'] : null;
$bookingDate  = !empty($rawCreatedAt) ? date('F d, Y g:i A', strtotime((string)$rawCreatedAt)) : date('F d, Y g:i A');

$receiptNo   = 'REC-' . str_pad($booking['id'], 5, '0', STR_PAD_LEFT);
$payStatus   = $booking['payment_status'] ?? 'Unpaid';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Receipt #<?= e($receiptNo); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <!-- html2pdf.js for instant PDF download -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <nav class="navbar no-print">
        <div class="nav-container">
            <a href="index.php" class="brand">🏠 BoardingHouse Hub</a>
            <div class="nav-links" style="display:flex; gap:10px;">
                <a href="index.php" class="nav-link">Browse Rooms</a>
                <?php if (isLoggedIn()): ?>
                    <a href="my_bookings.php" class="nav-link">My Bookings</a>
                <?php endif; ?>
                <button onclick="downloadPDF()" class="btn btn-primary btn-sm">📥 Download PDF</button>
                <button onclick="window.print()" class="btn btn-secondary btn-sm">🖨️ Print Receipt</button>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Action Bar with 2 Separate Buttons -->
        <div class="action-bar no-print">
            <a href="<?= isLoggedIn() ? (isAdmin() ? 'bookings.php' : 'my_bookings.php') : 'index.php'; ?>" class="btn btn-secondary btn-sm">
                ← Back to Dashboard
            </a>
            <div style="display:flex; gap:12px;">
                <button onclick="window.print()" class="btn btn-secondary">
                    🖨️ Print Receipt
                </button>
                <button onclick="downloadPDF()" class="btn btn-primary">
                    📥 Download PDF
                </button>
            </div>
        </div>

        <!-- Receipt Box to Print / Convert to PDF -->
        <div class="receipt-wrapper" id="receiptCard">
            <div class="receipt-header">
                <div>
                    <div class="brand-title">🏠 BoardingHouse Hub</div>
                    <p style="color: #94a3b8; font-size: 0.9rem;">Official Reservation Receipt</p>
                </div>
                <div>
                    <div class="receipt-title">RECEIPT</div>
                    <div style="font-size: 0.9rem; color: #94a3b8; text-align: right; margin-top: 4px;">
                        #<?= e($receiptNo); ?>
                    </div>
                    <div style="text-align: right; margin-top: 8px;">
                        <span class="badge badge-<?= strtolower($booking['status'] ?? 'pending'); ?>">
                            Booking: <?= e(ucfirst($booking['status'] ?? 'Pending')); ?>
                        </span>
                        <span class="badge badge-<?= strtolower(str_replace(' ', '', $payStatus)); ?>" style="margin-left: 4px;">
                            Payment: <?= e($payStatus); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Important Owner Presentation Notice -->
            <div class="notice-box">
                <span style="font-size: 1.4rem;">⚠️</span>
                <div>
                    <strong>IMPORTANT NOTICE FOR TENANTS:</strong>
                    <div style="margin-top: 2px;">
                        Please <strong>present this official PDF receipt first to the Boarding House Owner / Landlord</strong> upon move-in or room key collection for verification and confirmation.
                    </div>
                </div>
            </div>

            <div class="meta-grid">
                <div class="meta-box">
                    <div class="meta-label">Tenant Details</div>
                    <div class="meta-value"><?= e($tenantName); ?></div>
                    <div style="font-size: 0.85rem; color: #94a3b8; margin-top: 4px;">
                        📞 Phone: <?= e($tenantPhone); ?>
                    </div>
                    <div style="font-size: 0.85rem; color: #94a3b8;">
                        ✉️ Email: <?= e($tenantEmail); ?>
                    </div>
                </div>

                <div class="meta-box">
                    <div class="meta-label">Reservation & Payment Info</div>
                    <div class="meta-value">Check-in: <?= e($checkInDate); ?></div>
                    <div style="font-size: 0.85rem; color: #94a3b8; margin-top: 4px;">
                        💳 Method: <?= e($booking['payment_method'] ?? 'Not Specified'); ?>
                    </div>
                    <?php if (!empty($booking['reference_number'])): ?>
                        <div style="font-size: 0.85rem; color: #94a3b8;">
                            🔖 Ref #: <?= e($booking['reference_number']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <table class="item-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Room Type</th>
                        <th>Floor Level</th>
                        <th style="text-align: right;">Monthly Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong><?= e($booking['room_name']); ?></strong>
                            <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 4px;"><?= e($booking['room_desc'] ?? ''); ?></div>
                        </td>
                        <td><?= e(ucfirst($booking['room_type'] ?? '')); ?></td>
                        <td><?= e($booking['room_floor'] ?? ''); ?></td>
                        <td style="text-align: right; font-weight: 600; color: #38bdf8;">
                            <?= formatMoney($booking['room_price']); ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="total-box">
                <div>
                    <div style="font-weight: 600; font-size: 1rem;">Total Monthly Rental Rate</div>
                    <div style="font-size: 0.8rem; color: #94a3b8;">Payment Status: <strong><?= e($payStatus); ?></strong></div>
                </div>
                <div class="total-price">
                    <?= formatMoney($booking['room_price']); ?>
                </div>
            </div>

            <?php if (!empty($booking['notes'])): ?>
                <div style="background: rgba(255,255,255,0.03); padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 0.85rem; border-left: 3px solid #38bdf8;">
                    <strong>Tenant Notes / Requests:</strong> "<?= e($booking['notes']); ?>"
                </div>
            <?php endif; ?>

            <div style="border-top: 1px solid #334155; padding-top: 20px; text-align: center; font-size: 0.8rem; color: #94a3b8;">
                <p>Thank you for choosing BoardingHouse Hub!</p>
                <p style="margin-top: 4px;"><strong>Reminders:</strong> Present Receipt #<?= e($receiptNo); ?> to the Boarding House Owner upon check-in.</p>
            </div>
        </div>
    </div>

    <script>
        function downloadPDF() {
            var element = document.getElementById('receiptCard');
            if (typeof html2pdf !== 'undefined') {
                var opt = {
                    margin:       8,
                    filename:     'Booking_Receipt_<?= e($receiptNo); ?>.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true },
                    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };
                html2pdf().set(opt).from(element).save();
            } else {
                window.print();
            }
        }
    </script>
</body>
</html>
