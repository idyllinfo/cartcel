<?php
require '../includes/db.php';
require '../includes/paystack-config.php';

$reference = $_GET['reference'] ?? '';

if ($reference === '') {
    die("No payment reference provided.");
}

$ch = curl_init("https://api.paystack.co/transaction/verify/" . rawurlencode($reference));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . PAYSTACK_SECRET_KEY
]);
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    die("Verification connection error: " . $err);
}

$result = json_decode($response, true);

if (!empty($result['status']) && $result['data']['status'] === 'success') {
    // Find the order by reference
    $stmt = $conn->prepare("SELECT * FROM orders WHERE payment_reference = ?");
    $stmt->bind_param("s", $reference);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if ($order) {
        // Mark as paid
        $upd = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
        $upd->bind_param("i", $order['id']);
        $upd->execute();

        // Clear cart now that payment is confirmed
        $_SESSION['cart'] = [];

        header("Location: order-confirmation.php?order_id=" . $order['id']);
        exit;
    } else {
        die("Order not found for this payment reference.");
    }
} else {
    echo "<p>Payment verification failed or was not successful.</p>";
    echo "<a href='cart.php'>Return to Cart</a>";
}
?>