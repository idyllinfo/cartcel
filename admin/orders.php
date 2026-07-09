<?php
require '../includes/db.php';
require '../includes/admin-auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'] ?? 'pending';
    $allowedStatuses = ['pending','paid','shipped','completed','cancelled'];
    if (in_array($status, $allowedStatuses)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $orderId);
        $stmt->execute();
    }
    header('Location: orders.php');
    exit;
}

$filterStatus = $_GET['status'] ?? '';
if ($filterStatus !== '') {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $filterStatus);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders - Cartcel Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header class="site-header">
    <a href="dashboard.php" class="logo">Cart<span>cel</span></a>
    <p class="tagline">Admin Panel</p>
    <p style="color:#F2EFE9; font-size:13px;"><a href="dashboard.php" style="color:#C9A24B;">← Back to Dashboard</a> | <a href="logout.php" style="color:#C9A24B;">Logout</a></p>
</header>

<div class="admin-content">
    <h2>Manage Orders</h2>

    <div class="category-filter" style="padding-left:0; justify-content:flex-start;">
        <a href="orders.php" class="filter-btn <?= $filterStatus==='' ? 'active':'' ?>">All</a>
        <a href="orders.php?status=pending" class="filter-btn <?= $filterStatus==='pending' ? 'active':'' ?>">Pending</a>
        <a href="orders.php?status=paid" class="filter-btn <?= $filterStatus==='paid' ? 'active':'' ?>">Paid</a>
        <a href="orders.php?status=shipped" class="filter-btn <?= $filterStatus==='shipped' ? 'active':'' ?>">Shipped</a>
        <a href="orders.php?status=completed" class="filter-btn <?= $filterStatus==='completed' ? 'active':'' ?>">Completed</a>
        <a href="orders.php?status=cancelled" class="filter-btn <?= $filterStatus==='cancelled' ? 'active':'' ?>">Cancelled</a>
    </div>

    <table class="admin-table">
        <thead>
            <tr><th>Order #</th><th>Customer</th><th>Phone</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td>#<?= $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['customer_name']) ?></td>
                    <td><?= htmlspecialchars($o['phone']) ?></td>
                    <td>₦<?= number_format($o['total'], 2) ?></td>
                    <td>
                        <span class="order-status status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>
                    </td>
                    <td><?= date('M j, Y g:ia', strtotime($o['created_at'])) ?></td>
                    <td>
                        <a href="order-detail.php?id=<?= $o['id'] ?>" class="small-btn">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
                <tr><td colspan="7" style="text-align:center; padding:20px;">No orders found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>