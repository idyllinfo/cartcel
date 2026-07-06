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
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header>
    <h1><a href="index.php" style="color:white; text-decoration:none;">Cartcel</a></h1>
    <p>Quality Gadgets, Genuine Prices</p>
</header>

<div class="cart-page">
    <h2>Your Cart</h2>

    <?php if (empty($cartItems)): ?>
        <p>Your cart is empty. <a href="index.php">Continue shopping</a></p>
    <?php else: ?>
        <?php foreach ($cartItems as $item): ?>
            <div class="cart-item">
                <img src="../uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                <div class="cart-item-info">
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <p>₦<?= number_format($item['price'], 2) ?> x <?= $item['qty'] ?> = <strong>₦<?= number_format($item['subtotal'], 2) ?></strong></p>
                    <form action="update-cart.php" method="POST" class="cart-item-actions">
                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
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