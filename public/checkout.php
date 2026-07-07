<?php
require '../includes/db.php';

$cartItems = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $stmt = $conn->prepare("SELECT p.*, pi.image_path 
        FROM products p
        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
        WHERE p.id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $qty = $_SESSION['cart'][$row['id']];
        $subtotal = $row['price'] * $qty;
        $total += $subtotal;
        $cartItems[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'price' => $row['price'],
            'qty' => $qty,
            'subtotal' => $subtotal
        ];
    }
}

if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - Cartcel</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header>
    <h1><a href="index.php" style="color:white; text-decoration:none;">Cartcel</a></h1>
    <p>Quality Gadgets, Genuine Prices</p>
</header>

<div class="checkout-page">
    <h2>Checkout</h2>

    <div class="order-summary">
        <h3>Order Summary</h3>
        <?php foreach ($cartItems as $item): ?>
            <div class="summary-line">
                <span><?= htmlspecialchars($item['name']) ?> x <?= $item['qty'] ?></span>
                <span>₦<?= number_format($item['subtotal'], 2) ?></span>
            </div>
        <?php endforeach; ?>
        <div class="summary-total">
            <span>Total</span>
            <span>₦<?= number_format($total, 2) ?></span>
        </div>
    </div>

    <form action="place-order.php" method="POST" class="checkout-form">
        <label>Full Name</label>
        <input type="text" name="customer_name" required>

        <label>Phone Number</label>
        <input type="text" name="phone" required>

        <label>Email (needed for Paystack receipt)</label>
        <input type="email" name="email" required>

        <label>Delivery Address</label>
        <textarea name="address" required></textarea>

        <label>Payment Method</label>
        <div class="payment-options">
            <label class="payment-option">
                <input type="radio" name="payment_method" value="paystack" checked>
                💳 Pay Online (Card/Bank Transfer via Paystack)
            </label>
            <label class="payment-option">
                <input type="radio" name="payment_method" value="delivery">
                🚚 Pay on Delivery
            </label>
        </div>

        <button type="submit" class="add-to-cart-btn">Place Order</button>
    </form>
</div>

</body>
</html>