<?php
require '../includes/db.php';

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$action = $_POST['action'] ?? '';

if ($productId > 0) {
    if ($action === 'remove') {
        unset($_SESSION['cart'][$productId]);
    } elseif ($action === 'update') {
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        if ($quantity > 0) {
            $_SESSION['cart'][$productId] = $quantity;
        } else {
            unset($_SESSION['cart'][$productId]);
        }
    }
}

header('Location: cart.php');
exit;
?>