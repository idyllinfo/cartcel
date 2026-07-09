<?php
require '../includes/db.php';
require '../includes/admin-auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
        $stmt = $conn->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $slug);
        if ($stmt->execute()) {
            $success = "Category added.";
        } else {
            $error = "Error adding category (maybe it already exists).";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
        $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $slug, $id);
        $stmt->execute();
        $success = "Category updated.";
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: categories.php');
    exit;
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Categories - Cartcel Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header class="site-header">
    <a href="dashboard.php" class="logo">Cart<span>cel</span></a>
    <p class="tagline">Admin Panel</p>
    <p style="color:#F2EFE9; font-size:13px;"><a href="dashboard.php" style="color:#C9A24B;">← Back to Dashboard</a> | <a href="logout.php" style="color:#C9A24B;">Logout</a></p>
</header>

<div class="admin-content">
    <h2>Manage Categories</h2>

    <?php if ($success): ?><p class="success-msg"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error-msg"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <div class="admin-form-box">
        <h3>Add New Category</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <input type="text" name="name" placeholder="Category name (e.g. Phones)" required>
            <button type="submit" class="add-to-cart-btn">Add Category</button>
        </form>
    </div>

    <table class="admin-table">
        <thead>
            <tr><th>Name</th><th>Slug</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                        <td><input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>"></td>
                        <td><?= htmlspecialchars($cat['slug']) ?></td>
                        <td>
                            <button type="submit" class="small-btn">Save</button>
                            <a href="?delete=<?= $cat['id'] ?>" class="small-btn danger" onclick="return confirm('Delete this category?')">Delete</a>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>