<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$customerName = trim($_POST['customer_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if ($customerName === '' || $phone === '' || $address === '') {
    die("All fields are required. <a href='checkout.php'>Go back</a>");
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

// Insert order
$orderStmt = $conn->prepare("INSERT INTO orders (customer_name, phone, address, total, status) VALUES (?, ?, ?, ?, 'pending')");
$orderStmt->bind_param("sssd", $customerName, $phone, $address, $total);
$orderStmt->execute();
$orderId = $conn->insert_id;

// Insert order items
$itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
foreach ($orderItems as $item) {
    $itemStmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
    $itemStmt->execute();
}

// Clear cart
$_SESSION['cart'] = [];

header("Location: order-confirmation.php?order_id=$orderId");
exit;
?>