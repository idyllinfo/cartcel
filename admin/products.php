<?php
require '../includes/db.php';
require '../includes/admin-auth.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: products.php');
    exit;
}

$products = $conn->query("SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Products - Cartcel Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header>
    <h1>Cartcel Admin</h1>
    <p><a href="dashboard.php" style="color:#ccc;">← Back to Dashboard</a> | <a href="logout.php" style="color:#ffb3b3;">Logout</a></p>
</header>

<div class="admin-content">
    <h2>Manage Products</h2>

    <a href="product-form.php" class="add-to-cart-btn" style="display:inline-block; text-decoration:none; margin-bottom:20px;">+ Add New Product</a>

    <table class="admin-table">
        <thead>
            <tr><th>Name</th><th>Category</th><th>Price</th><th>Condition</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                    <td>₦<?= number_format($p['price'], 2) ?></td>
                    <td><?= strtoupper(str_replace('_',' ',$p['condition_type'])) ?></td>
                    <td><?= $p['has_serials'] ? 'Serialized' : $p['stock_qty'] ?></td>
                    <td><?= ucfirst($p['status']) ?></td>
                    <td>
                        <a href="product-form.php?id=<?= $p['id'] ?>" class="small-btn">Edit</a>
                        <a href="?delete=<?= $p['id'] ?>" class="small-btn danger" onclick="return confirm('Delete this product?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>