<?php
require '../includes/db.php';
require '../includes/paystack-config.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found.");
}

$email = $_SESSION['checkout_email'] ?? 'customer@example.com';
$amountInKobo = (int) round($order['total'] * 100);

$callbackUrl = "http://localhost/cartcel/public/paystack-verify.php";

$fields = [
    'email' => $email,
    'amount' => $amountInKobo,
    'reference' => 'cartcel_' . $orderId . '_' . time(),
    'callback_url' => $callbackUrl,
    'metadata' => ['order_id' => $orderId]
];

$ch = curl_init("https://api.paystack.co/transaction/initialize");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

;

if ($err) {
    die("Paystack connection error: " . $err);
}

$result = json_decode($response, true);

if (!empty($result['status']) && $result['status'] === true) {
    $reference = $fields['reference'];
    $upd = $conn->prepare("UPDATE orders SET payment_reference = ? WHERE id = ?");
    $upd->bind_param("si", $reference, $orderId);
    $upd->execute();

    header("Location: " . $result['data']['authorization_url']);
    exit;
} else {
    echo "<p>Payment initialization failed: " . htmlspecialchars($result['message'] ?? 'Unknown error') . "</p>";
    echo "<a href='checkout.php'>Try again</a>";
}
?>