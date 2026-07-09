<?php
require '../includes/db.php';

$order = null;
$items = [];
$notFound = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');

    if ($orderId > 0 && $phone !== '') {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND phone = ?");
        $stmt->bind_param("is", $orderId, $phone);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if ($order) {
            $itemStmt = $conn->prepare("SELECT oi.*, p.name as product_name 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?");
            $itemStmt->bind_param("i", $order['id']);
            $itemStmt->execute();
            $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $notFound = true;
        }
    }
}

$steps = ['pending', 'paid', 'shipped', 'completed'];
$stepLabels = ['pending' => 'Order Placed', 'paid' => 'Payment Confirmed', 'shipped' => 'Shipped', 'completed' => 'Delivered'];
$currentStepIndex = $order ? array_search($order['status'], $steps) : -1;
$isCancelled = $order && $order['status'] === 'cancelled';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Track Your Order - Cartcel</title>
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

<div class="track-order-page">
    <h2>Track Your Order</h2>
    <p class="track-subtitle">Enter your Order ID and the phone number used at checkout.</p>

    <form method="POST" class="track-form">
        <div class="track-form-row">
            <div>
                <label>Order ID</label>
                <input type="number" name="order_id" placeholder="e.g. 13" value="<?= htmlspecialchars($_POST['order_id'] ?? '') ?>" required>
            </div>
            <div>
                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="e.g. 08012345678" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
            </div>
        </div>
        <button type="submit" class="add-to-cart-btn">Track Order</button>
    </form>

    <?php if ($notFound): ?>
        <p class="error-msg" style="margin-top:20px;">No order found matching that Order ID and phone number. Please check and try again.</p>
    <?php endif; ?>

    <?php if ($order): ?>
        <div class="track-result">
            <h3>Order #<?= $order['id'] ?></h3>
            <p class="track-date">Placed on <?= date('M j, Y g:ia', strtotime($order['created_at'])) ?></p>

            <?php if ($isCancelled): ?>
                <div class="track-cancelled">This order was cancelled.</div>
            <?php else: ?>
                <div class="track-timeline">
                    <?php foreach ($steps as $i => $step): ?>
                        <div class="track-step <?= $i <= $currentStepIndex ? 'done' : '' ?> <?= $i === $currentStepIndex ? 'current' : '' ?>">
                            <div class="track-step-row">
                                <div class="track-dot"><?= $i <= $currentStepIndex ? '✓' : ($i+1) ?></div>
                                <?php if ($i < count($steps) - 1): ?><div class="track-line <?= $i < $currentStepIndex ? 'done' : '' ?>"></div><?php endif; ?>
                            </div>
                            <div class="track-label"><?= $stepLabels[$step] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="track-details">
                <p><strong>Delivery Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
                <p><strong>Payment Method:</strong> <?= $order['payment_method'] === 'paystack' ? 'Paid Online' : 'Pay on Delivery' ?></p>
                <p><strong>Total:</strong> ₦<?= number_format($order['total'], 2) ?></p>
            </div>

            <h4>Items</h4>
            <table class="admin-table">
                <thead><tr><th>Product</th><th>Qty</th><th>Price</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name'] ?? 'Product removed') ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>₦<?= number_format($item['price'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<footer class="site-footer">
    <div class="footer-columns">
        <div>
            <h4>Cartcel</h4>
            <p>Your one-stop shop for phones, laptops, and accessories — genuine and verified.</p>
        </div>
        <div>
            <h4>Quick Links</h4>
            <a href="index.php">Home</a>
            <a href="index.php">Shop</a>
            <a href="track-order.php">Track Order</a>
        </div>
        <div>
            <h4>Contact Us</h4>
            <p>Email: info@cartcel.com</p>
            <p>Phone: +234 800 000 0000</p>
            <p>Lagos, Nigeria</p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> Cartcel. All rights reserved.
    </div>
</footer>

</body>
</html>