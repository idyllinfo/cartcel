<?php
require '../includes/db.php';

$cartKey = $_POST['cart_key'] ?? '';
$action = $_POST['action'] ?? '';

if ($cartKey !== '' && isset($_SESSION['cart'][$cartKey])) {
    if ($action === 'remove') {
        unset($_SESSION['cart'][$cartKey]);
    } elseif ($action === 'update') {
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        if ($quantity > 0) {
            $_SESSION['cart'][$cartKey] = $quantity;
        } else {
            unset($_SESSION['cart'][$cartKey]);
        }
    }
}

header('Location: cart.php');
exit;
?>