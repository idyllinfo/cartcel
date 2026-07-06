<?php
require '../includes/db.php';

$slug = $_GET['slug'] ?? '';

$stmt = $conn->prepare("SELECT * FROM products WHERE slug = ? AND status = 'active'");
$stmt->bind_param("s", $slug);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

$imgStmt = $conn->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
$imgStmt->bind_param("i", $product['id']);
$imgStmt->execute();
$images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$specStmt = $conn->prepare("SELECT spec_name, spec_value FROM product_specs WHERE product_id = ?");
$specStmt->bind_param("i", $product['id']);
$specStmt->execute();
$specs = $specStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($product['name']) ?> - Cartcel</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header>
    <h1><a href="index.php" style="color:white; text-decoration:none;">Cartcel</a></h1>
    <p>Quality Gadgets, Genuine Prices</p>
</header>

<div class="product-detail">
    <div class="product-detail-images">
        <?php foreach ($images as $img): ?>
            <img src="../uploads/<?= htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        <?php endforeach; ?>
    </div>

    <div class="product-detail-info">
        <span class="badge badge-<?= $product['condition_type'] ?>">
            <?= strtoupper(str_replace('_', ' ', $product['condition_type'])) ?>
        </span>
        <h2><?= htmlspecialchars($product['name']) ?></h2>
        <div class="price">₦<?= number_format($product['price'], 2) ?></div>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

        <?php if (count($specs) > 0): ?>
            <h3>Specifications</h3>
            <ul class="spec-list">
                <?php foreach ($specs as $spec): ?>
                    <li><strong><?= htmlspecialchars($spec['spec_name']) ?>:</strong> <?= htmlspecialchars($spec['spec_value']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <button class="add-to-cart-btn">Add to Cart</button>
    </div>
</div>

</body>
</html>