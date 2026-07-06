<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$host = 'localhost';
$db   = 'cartcel_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>