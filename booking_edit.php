<?php
require __DIR__ . '/lib/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST'){
    // E02 - Request gate: Chỉ POST có CSRF đúng mới được xử lý
    if (!csrf_valid($_POST)){
        http_response_code(403);
        exit('Invalid CSRF token');
    }
    // E08 – Unknown ref: tra booking theo ref, không có thì dừng, không tạo hay sửa gì
    $ref = (string)($_POST['ref'] ?? '');
    $index = find_booking_index($_SESSION['bookings'], $ref);
    if ($index === null){
        http_response_code(404);
        exit('Booking not found.');
    }
    //Workshop lấy từ Session (server), không lấy từ POST
    $workshopId = (string)($_SESSION['bookings'][$index]['workshop_id'] ?? '');
    // E03 – Validation: Tái sử dụng validate_booking()
    $result = validate_booking([
        'full_name' => $_POST['full_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'quantity' => $_POST['quantity'] ?? null,
        'workshop_id' => $workshopId,
    ], $workshops);
    // E06 - Failure: Có lỗi thì giữ dữ liệu đang nhập, Session booking chưa đổi
    if ($result['errors']){
        $_SESSION['edit_errors'] = $result['errors'];
        $_SESSION['edit_old'] = [
            'full_name' => $result['data']['full_name'],
            'email' => $result['data']['email'],
            'quantity' => (string)($_POST['quantity'] ?? ''),
        ];
        redirect_to('booking_edit.php?ref=' . urlencode($ref));
    }
    // E04 + E05 - Update and Recalculate: Chỉ cập nhật đúng phần tử $index, tính lại amount ở server
    $d = $result['data'];
    $w = $workshops[$workshopId];
    $_SESSION['bookings'][$index]['full_name'] = $d['full_name'];
    $_SESSION['bookings'][$index]['email'] = $d['email'];
    $_SESSION['bookings'][$index]['quantity'] = $d['quantity'];
    $_SESSION['bookings'][$index]['amount'] = $w['fee'] * $d['quantity'];
    unset($_SESSION['edit_errors'], $_SESSION['edit_old']);
    $_SESSION['flash'] = 'Booking updated successfully.';
    // E07 - Success: POST/ Redirect/ GET bằng 303
    redirect_to('dashboard.php');
}
// --- GET ---
// E01 - Load: đọc ref từ URL, tra booking trong Session
$ref = (string)($_GET['ref'] ?? '');
$index = find_booking_index($_SESSION['bookings'], $ref);
$booking = $index !== null ? $_SESSION['bookings'][$index] : null;
// E08 - Unknown ref: ref không tồn tại thì báo 404, không tạo hay sửa gì
if ($booking === null){
    http_response_code(404);
}

$unitFee = null;
if ($booking !== null){
    $unitFee = $workshops[$booking['workshop_id'] ?? '']['fee'] ?? null;
}
$errors = $_SESSION['edit_errors'] ?? [];
$old = $_SESSION['edit_old'] ?? [];
unset($_SESSION['edit_errors'], $_SESSION['edit_old']);
if (!$old && $booking !== null){
    $old = [
        'full_name' => $booking['full_name'],
        'email' => $booking['email'],
        'quantity' => $booking['quantity'],
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit booking</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($theme) ?>">
    <main class="wrap">
        <header>
            <h1>Edit Booking</h1>
            <nav>
                <a href="index.php">Catalog</a>
                <a href="dashboard.php">My Bookings</a>
            </nav>
        </header>

        <?php if ($booking === null): ?>
            <div class="panel">
                <p>Booking not found</p>
                <p><a href="dashboard.php">Back to My Bookings</a></p>
            </div>
        <?php else: ?>
            <form method="post" action="booking_edit.php" class="panel form-stack" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="ref" value="<?= e($booking['ref']) ?>">

                <p><strong>Reference:</strong> <?= e($booking['ref']) ?></p>
                <p><strong>Workshop:</strong> <?= e($booking['workshop']) ?>
                    (<?= $unitFee !== null ? number_format($unitFee) . ' VND / ticket' : 'N/A' ?>)</p>
                <small class="error"><?= e($errors['workshop_id'] ?? '') ?></small>
                <label>Full name
                    <input name="full_name" value="<?= e($old['full_name']) ?>">
                </label>
                <small class="error"><?= e($errors['full_name'] ?? '') ?></small>
                <label>Email
                    <input name="email" value="<?= e($old['email']) ?>">
                </label>
                <small class="error"><?= e($errors['email'] ?? '') ?></small>
                <label>Tickets (1-4)
                    <input name="quantity" type="number" min="1" max="4" value="<?= e($old['quantity']) ?>">
                </label>
                <small class="error"><?= e($errors['quantity'] ?? '') ?></small>
                <button>Save Changes (POST)</button>
                <a href="booking_detail.php?ref=<?= urlencode($booking['ref']) ?>">Cancel</a>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>