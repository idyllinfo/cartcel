<?php
require '../includes/db.php';
require '../includes/admin-auth.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Cartcel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header class="site-header">
    <a href="dashboard.php" class="logo">Cart<span>cel</span></a>
    <p class="tagline">Admin Panel</p>
    <p style="color:#F2EFE9; font-size:13px;">Welcome, <?= htmlspecialchars($_SESSION['admin_username']) ?> | <a href="logout.php" style="color:#C9A24B;">Logout</a></p>
</header>

<div class="admin-dashboard">
    <a href="products.php" class="admin-card">📦 Manage Products</a>
    <a href="categories.php" class="admin-card">🗂️ Manage Categories</a>
    <a href="banners.php" class="admin-card">🖼️ Manage Homepage Banners</a>
    <a href="orders.php" class="admin-card">🧾 Manage Orders</a>
</div>

</body>
</html>