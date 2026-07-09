<?php
require '../includes/db.php';

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$variantId = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($productId > 0) {
    $cartKey = $productId . '-' . $variantId;
    if (isset($_SESSION['cart'][$cartKey])) {
        $_SESSION['cart'][$cartKey] += $quantity;
    } else {
        $_SESSION['cart'][$cartKey] = $quantity;
    }
}

header('Location: cart.php');
exit;
?>