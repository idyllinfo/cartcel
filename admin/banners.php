<?php
require '../includes/db.php';
require '../includes/admin-auth.php';

$error = '';
$success = '';

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $linkUrl = trim($_POST['link_url'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);

    if (empty($_FILES['banner_image']['name'])) {
        $error = "Please upload a banner image.";
    } else {
        $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowedExt)) {
            $error = "Invalid image format.";
        } else {
            $newName = 'banner_' . time() . '.' . $ext;
            $destination = '../uploads/' . $newName;
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $destination)) {
                $stmt = $conn->prepare("INSERT INTO banners (image_path, title, subtitle, link_url, sort_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $newName, $title, $subtitle, $linkUrl, $sortOrder);
                $stmt->execute();
                $success = "Banner added.";
            } else {
                $error = "Failed to upload image.";
            }
        }
    }
}

// Handle Edit (text fields only, not image)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $linkUrl = trim($_POST['link_url'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    $stmt = $conn->prepare("UPDATE banners SET title=?, subtitle=?, link_url=?, sort_order=?, status=? WHERE id=?");
    $stmt->bind_param("sssisi", $title, $subtitle, $linkUrl, $sortOrder, $status, $id);
    $stmt->execute();
    $success = "Banner updated.";
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("SELECT image_path FROM banners WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $b = $stmt->get_result()->fetch_assoc();
    if ($b) {
        @unlink('../uploads/' . $b['image_path']);
        $del = $conn->prepare("DELETE FROM banners WHERE id = ?");
        $del->bind_param("i", $id);
        $del->execute();
    }
    header('Location: banners.php');
    exit;
}

$banners = $conn->query("SELECT * FROM banners ORDER BY sort_order ASC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Banners - Cartcel Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header>
    <h1>Cartcel Admin</h1>
    <p><a href="dashboard.php" style="color:#ccc;">← Back to Dashboard</a> | <a href="logout.php" style="color:#ffb3b3;">Logout</a></p>
</header>

<div class="admin-content">
    <h2>Manage Homepage Banners</h2>

    <?php if ($success): ?><p class="success-msg"><?= htmlspecialchars($success) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error-msg"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <div class="admin-form-box" style="flex-direction:column; align-items:stretch;">
        <h3>Add New Banner</h3>
        <form method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:10px;">
            <input type="hidden" name="action" value="add">
            <label>Banner Image</label>
            <input type="file" name="banner_image" accept="image/*" required>
            <label>Title (optional, shown on banner)</label>
            <input type="text" name="title" placeholder="e.g. Big Gadget Sale">
            <label>Subtitle (optional)</label>
            <input type="text" name="subtitle" placeholder="e.g. Up to 20% off phones this week">
            <label>Link URL (optional — e.g. index.php?category=1)</label>
            <input type="text" name="link_url" placeholder="index.php?category=1">
            <label>Sort Order (lower number shows first)</label>
            <input type="number" name="sort_order" value="0">
            <button type="submit" class="add-to-cart-btn">Add Banner</button>
        </form>
    </div>

    <?php foreach ($banners as $b): ?>
        <div class="banner-admin-card">
            <img src="../uploads/<?= htmlspecialchars($b['image_path']) ?>">
            <form method="POST" style="flex:1;">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <label>Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($b['title']) ?>">
                <label>Subtitle</label>
                <input type="text" name="subtitle" value="<?= htmlspecialchars($b['subtitle']) ?>">
                <label>Link URL</label>
                <input type="text" name="link_url" value="<?= htmlspecialchars($b['link_url']) ?>">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="<?= $b['sort_order'] ?>" style="width:80px;">
                <label>Status</label>
                <select name="status">
                    <option value="active" <?= $b['status']=='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $b['status']=='inactive'?'selected':'' ?>>Inactive</option>
                </select>
                <div style="margin-top:10px;">
                    <button type="submit" class="small-btn">Save</button>
                    <a href="?delete=<?= $b['id'] ?>" class="small-btn danger" onclick="return confirm('Delete this banner?')">Delete</a>
                </div>
            </form>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>