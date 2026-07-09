<?php
require '../includes/db.php';

$cartItems = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $cartKey => $qty) {
        list($productId, $variantId) = array_map('intval', explode('-', $cartKey));

        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) continue;

        $price = $row['price'];
        $variantLabel = '';

        if ($variantId > 0) {
            $vStmt = $conn->prepare("SELECT * FROM product_variants WHERE id = ?");
            $vStmt->bind_param("i", $variantId);
            $vStmt->execute();
            $variant = $vStmt->get_result()->fetch_assoc();
            if ($variant) {
                if ($variant['price'] !== null) $price = $variant['price'];
                $labelParts = array_filter([$variant['color'], $variant['storage'], $variant['ram']]);
                $variantLabel = implode(' / ', $labelParts);
            }
        }

        $subtotal = $price * $qty;
        $total += $subtotal;

        $cartItems[] = [
            'name' => $row['name'],
            'variant_label' => $variantLabel,
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header class="site-header">
    <a href="index.php" class="logo">Cart<span>cel</span></a>
    <p class="tagline">Genuine Gadgets, Verified Condition</p>
    <a href="cart.php" class="cart-link">Cart</a>
</header>

<div class="checkout-page">
    <h2>Checkout</h2>

    <div class="order-summary">
        <h3>Order Summary</h3>
        <?php foreach ($cartItems as $item): ?>
            <div class="summary-line">
                <span><?= htmlspecialchars($item['name']) ?><?= $item['variant_label'] ? ' (' . htmlspecialchars($item['variant_label']) . ')' : '' ?> x <?= $item['qty'] ?></span>
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