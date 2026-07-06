<?php
require '../includes/db.php';

// Get all categories for the filter tabs
$catResult = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $catResult->fetch_all(MYSQLI_ASSOC);

// Check if a category filter was selected
$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Build product query based on filter
if ($selectedCategory > 0) {
    $stmt = $conn->prepare("SELECT p.*, pi.image_path 
        FROM products p
        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
        WHERE p.status = 'active' AND p.category_id = ?
        ORDER BY p.created_at DESC");
    $stmt->bind_param("i", $selectedCategory);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT p.*, pi.image_path 
            FROM products p
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            WHERE p.status = 'active'
            ORDER BY p.created_at DESC";
    $result = $conn->query($sql);
}
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

<div class="category-filter">
    <a href="index.php" class="filter-btn <?= $selectedCategory === 0 ? 'active' : '' ?>">All</a>
    <?php foreach ($categories as $cat): ?>
        <a href="index.php?category=<?= $cat['id'] ?>" class="filter-btn <?= $selectedCategory === (int)$cat['id'] ? 'active' : '' ?>">
            <?= htmlspecialchars($cat['name']) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="product-grid">
<?php if ($result->num_rows === 0): ?>
    <p style="padding: 20px;">No products found in this category.</p>
<?php endif; ?>
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