<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$customerName = trim($_POST['customer_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? 'delivery';

if ($customerName === '' || $phone === '' || $address === '' || $email === '') {
    die("All fields are required. <a href='checkout.php'>Go back</a>");
}

if (!in_array($paymentMethod, ['paystack', 'delivery'])) {
    $paymentMethod = 'delivery';
}

$orderItems = [];
$total = 0;

foreach ($_SESSION['cart'] as $cartKey => $qty) {
    list($productId, $variantId) = array_map('intval', explode('-', $cartKey));

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) continue;

    $price = $row['price'];

    if ($variantId > 0) {
        $vStmt = $conn->prepare("SELECT * FROM product_variants WHERE id = ?");
        $vStmt->bind_param("i", $variantId);
        $vStmt->execute();
        $variant = $vStmt->get_result()->fetch_assoc();
        if ($variant && $variant['price'] !== null) {
            $price = $variant['price'];
        }
    }

    $subtotal = $price * $qty;
    $total += $subtotal;

    $orderItems[] = [
        'product_id' => $productId,
        'variant_id' => $variantId > 0 ? $variantId : null,
        'quantity' => $qty,
        'price' => $price
    ];
}

$orderStmt = $conn->prepare("INSERT INTO orders (customer_name, phone, address, total, status, payment_method) VALUES (?, ?, ?, ?, 'pending', ?)");
$orderStmt->bind_param("sssds", $customerName, $phone, $address, $total, $paymentMethod);
$orderStmt->execute();
$orderId = $conn->insert_id;

$itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, variant_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
foreach ($orderItems as $item) {
    $itemStmt->bind_param("iiiid", $orderId, $item['product_id'], $item['variant_id'], $item['quantity'], $item['price']);
    $itemStmt->execute();
}

$_SESSION['checkout_email'] = $email;

if ($paymentMethod === 'delivery') {
    $_SESSION['cart'] = [];
    header("Location: order-confirmation.php?order_id=$orderId");
    exit;
} else {
    header("Location: paystack-initialize.php?order_id=$orderId");
    exit;
}
?>