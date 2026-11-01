<?php 
require __DIR__ . '/lib/bootstrap.php';
// D01 – Valid reference: đọc ref từ GET, ép về chuỗi, thiếu thì là ''
$ref = (string)($_GET['ref'] ?? '');

// Tra trong Session không tìm thấy thì $index = null
$index = find_booking_index($_SESSION['bookings'], $ref);
$booking = $index !== null ? $_SESSION['bookings'][$index] : null;

// Lấy giá gốc từ catalog (server), không lấy từ client
$unitFee = null;
if ($booking !== null){
    $unitFee = $workshops[$booking['workshop_id'] ?? '']['fee'] ?? null;
}

// D02 – Missing/ unknown reference: Không tìm thấy thì trả 404
if ($booking === null){
    http_response_code(404);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking detail</title>
    <link rel="stylesheet" href="assets/style.css">    
</head>
<body class="<?= e($theme) ?>">
    <main class="wrap">
        <header>
            <h1>Booking Detail</h1>
            <nav>
                <a href="index.php">Catalog</a>
                <a href="dashboard.php">My bookings</a>
            </nav>
        </header>

        <?php if ($booking === null): ?>
            <div class="panel">
                <p>Booking not found.</p>
                <p><a href="dashboard.php">Back to My Bookings</a></p>
            </div>
        <?php else: ?>
            <div class="panel">
                <table>
                    <tr><th>Reference</th><td><?= e($booking['ref']) ?></td></tr>
                    <tr><th>Full name</th><td><?= e($booking['full_name']) ?></td></tr>
                    <tr><th>Email</th><td><?= e($booking['email']) ?></td></tr>
                    <tr><th>Workshop</th><td><?= e($booking['workshop']) ?></td></tr>
                    <tr><th>Quantity</th><td><?= e($booking['quantity']) ?></td></tr>
                    <tr>
                        <th>Unit fee</th>
                        <td><?= $unitFee !== null ? number_format($unitFee) . 'VND' : 'N/A' ?></td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td><?= number_format($booking['amount']) ?> VND</td>
                    </tr>
                </table>
            </div>
            <p>
                <a class="btn" href="booking_edit.php?ref=<?= urldecode($booking['ref']) ?>">Edit This Booking</a>
                <a href="dashboard.php">Back to My Bookings</a>
            </p>
            <?php endif; ?>
    </main>
</body>
</html>