<?php
require '../includes/db.php';

$cartItems = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $cartKey => $qty) {
        list($productId, $variantId) = array_map('intval', explode('-', $cartKey));

        $stmt = $conn->prepare("SELECT p.*, pi.image_path 
            FROM products p
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            WHERE p.id = ?");
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
            'cart_key' => $cartKey,
            'name' => $row['name'],
            'variant_label' => $variantLabel,
            'price' => $price,
            'image' => $row['image_path'],
            'qty' => $qty,
            'subtotal' => $subtotal
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Cart - Cartcel</title>
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

<div class="cart-page">
    <h2>Your Cart</h2>

    <?php if (empty($cartItems)): ?>
        <p>Your cart is empty. <a href="index.php" style="color:#C9A24B;">Continue shopping</a></p>
    <?php else: ?>
        <?php foreach ($cartItems as $item): ?>
            <div class="cart-item">
                <img src="../uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                <div class="cart-item-info">
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <?php if ($item['variant_label']): ?>
                        <p style="font-size:13px; color:#8A8D96; margin:2px 0;"><?= htmlspecialchars($item['variant_label']) ?></p>
                    <?php endif; ?>
                    <p>₦<?= number_format($item['price'], 2) ?> x <?= $item['qty'] ?> = <strong>₦<?= number_format($item['subtotal'], 2) ?></strong></p>
                    <form action="update-cart.php" method="POST" class="cart-item-actions">
                        <input type="hidden" name="cart_key" value="<?= htmlspecialchars($item['cart_key']) ?>">
                        <input type="number" name="quantity" value="<?= $item['qty'] ?>" min="1" style="width:60px;">
                        <button type="submit" name="action" value="update">Update</button>
                        <button type="submit" name="action" value="remove">Remove</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="cart-total">
            Total: ₦<?= number_format($total, 2) ?>
        </div>

        <a href="checkout.php" class="add-to-cart-btn" style="display:inline-block; text-decoration:none;">Proceed to Checkout</a>
    <?php endif; ?>
</div>

</body>
</html>