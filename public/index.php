<?php
require '../includes/db.php';

$sql = "SELECT p.*, pi.image_path 
        FROM products p
        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
        WHERE p.status = 'active'
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cartcel - Gadgets Store</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header>
    <h1>Cartcel</h1>
    <p>Quality Gadgets, Genuine Prices</p>
</header>

<div class="product-grid">
<?php while ($row = $result->fetch_assoc()): ?>
    <a href="product.php?slug=<?= urlencode($row['slug']) ?>" class="product-card">
        <img src="../uploads/<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
        <div class="product-info">
            <span class="badge badge-<?= $row['condition_type'] ?>">
                <?= strtoupper(str_replace('_', ' ', $row['condition_type'])) ?>
            </span>
            <h3><?= htmlspecialchars($row['name']) ?></h3>
            <div class="price">₦<?= number_format($row['price'], 2) ?></div>
        </div>
    </a>
<?php endwhile; ?>
</div>

</body>
</html>