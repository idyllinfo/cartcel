<?php
require '../includes/db.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmed - Cartcel</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header>
    <h1><a href="index.php" style="color:white; text-decoration:none;">Cartcel</a></h1>
    <p>Quality Gadgets, Genuine Prices</p>
</header>

<div class="confirmation-page">
    <h2>✅ Order Placed Successfully!</h2>
    <p>Thank you, <?= htmlspecialchars($order['customer_name']) ?>. Your order has been received.</p>
    <p><strong>Order ID:</strong> #<?= $order['id'] ?></p>
    <p><strong>Total:</strong> ₦<?= number_format($order['total'], 2) ?></p>
    <p><strong>Delivery Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
    <p>We'll contact you at <?= htmlspecialchars($order['phone']) ?> to confirm delivery details.</p>
    <a href="index.php" class="add-to-cart-btn" style="display:inline-block; text-decoration:none;">Continue Shopping</a>
</div>

</body>
</html>