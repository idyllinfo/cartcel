<?php
require '../includes/db.php';
require '../includes/admin-auth.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Cartcel</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header>
    <h1>Cartcel Admin</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['admin_username']) ?> | <a href="logout.php" style="color:#ffb3b3;">Logout</a></p>
</header>

<div class="admin-dashboard">
    <a href="products.php" class="admin-card">📦 Manage Products</a>
    <a href="categories.php" class="admin-card">🗂️ Manage Categories</a>
    <a href="banners.php" class="admin-card">🖼️ Manage Homepage Banners</a>
    <a href="orders.php" class="admin-card">🧾 Manage Orders</a>
</div>

</body>
</html>