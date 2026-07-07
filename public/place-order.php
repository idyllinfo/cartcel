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

// Get cart items with current prices
$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

$stmt = $conn->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$result = $stmt->get_result();

$orderItems = [];
$total = 0;

while ($row = $result->fetch_assoc()) {
    $qty = $_SESSION['cart'][$row['id']];
    $subtotal = $row['price'] * $qty;
    $total += $subtotal;
    $orderItems[] = [
        'product_id' => $row['id'],
        'quantity' => $qty,
        'price' => $row['price']
    ];
}

// Insert order (status pending until paid, or pending for delivery until fulfilled)
$orderStmt = $conn->prepare("INSERT INTO orders (customer_name, phone, address, total, status, payment_method) VALUES (?, ?, ?, ?, 'pending', ?)");
$orderStmt->bind_param("sssds", $customerName, $phone, $address, $total, $paymentMethod);
$orderStmt->execute();
$orderId = $conn->insert_id;

// Insert order items
$itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
foreach ($orderItems as $item) {
    $itemStmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
    $itemStmt->execute();
}

// Store email temporarily in session for Paystack step
$_SESSION['checkout_email'] = $email;

if ($paymentMethod === 'delivery') {
    // Clear cart and go straight to confirmation
    $_SESSION['cart'] = [];
    header("Location: order-confirmation.php?order_id=$orderId");
    exit;
} else {
    // Redirect to Paystack initialization
    header("Location: paystack-initialize.php?order_id=$orderId");
    exit;
}
?>