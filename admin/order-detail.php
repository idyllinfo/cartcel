<?php
require '../includes/db.php';
require '../includes/admin-auth.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'];
    $allowedStatuses = ['pending','paid','shipped','completed','cancelled'];
    if (in_array($status, $allowedStatuses)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $orderId);
        $stmt->execute();
    }
    header("Location: order-detail.php?id=$orderId&saved=1");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found.");
}

$itemStmt = $conn->prepare("SELECT oi.*, p.name as product_name 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?");
$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();
$items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order #<?= $order['id'] ?> - Cartcel Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header class="site-header">
    <a href="dashboard.php" class="logo">Cart<span>cel</span></a>
    <p class="tagline">Admin Panel</p>
    <p style="color:#F2EFE9; font-size:13px;"><a href="orders.php" style="color:#C9A24B;">← Back to Orders</a> | <a href="logout.php" style="color:#C9A24B;">Logout</a></p>
</header>

<div class="admin-content">
    <h2>Order #<?= $order['id'] ?></h2>

    <?php if (isset($_GET['saved'])): ?><p class="success-msg">Order status updated.</p><?php endif; ?>

    <div class="order-detail-box">
        <p><strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
        <p><strong>Date:</strong> <?= date('M j, Y g:ia', strtotime($order['created_at'])) ?></p>
        <p><strong>Current Status:</strong> <span class="order-status status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></p>

        <form method="POST" style="display:flex; gap:10px; align-items:center; margin-top:15px;">
            <label style="margin:0;">Update Status:</label>
            <select name="status">
                <option value="pending" <?= $order['status']=='pending'?'selected':'' ?>>Pending</option>
                <option value="paid" <?= $order['status']=='paid'?'selected':'' ?>>Paid</option>
                <option value="shipped" <?= $order['status']=='shipped'?'selected':'' ?>>Shipped</option>
                <option value="completed" <?= $order['status']=='completed'?'selected':'' ?>>Completed</option>
                <option value="cancelled" <?= $order['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
            </select>
            <button type="submit" class="small-btn">Update</button>
        </form>
    </div>

    <h3>Items Ordered</h3>
    <table class="admin-table">
        <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name'] ?? 'Product removed') ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>₦<?= number_format($item['price'], 2) ?></td>
                    <td>₦<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="cart-total" style="text-align:right; margin-top:15px;">
        Total: ₦<?= number_format($order['total'], 2) ?>
    </div>
</div>

</body>
</html>