<?php
require '../includes/db.php';
require '../includes/admin-auth.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $productId > 0;
$error = '';

if (isset($_GET['delete_image']) && $productId) {
    $imgId = (int)$_GET['delete_image'];
    $stmt = $conn->prepare("SELECT image_path FROM product_images WHERE id = ? AND product_id = ?");
    $stmt->bind_param("ii", $imgId, $productId);
    $stmt->execute();
    $img = $stmt->get_result()->fetch_assoc();
    if ($img) {
        @unlink('../uploads/' . $img['image_path']);
        $del = $conn->prepare("DELETE FROM product_images WHERE id = ?");
        $del->bind_param("i", $imgId);
        $del->execute();
    }
    header("Location: product-form.php?id=$productId");
    exit;
}

if (isset($_GET['set_primary']) && $productId) {
    $imgId = (int)$_GET['set_primary'];
    $conn->query("UPDATE product_images SET is_primary = 0 WHERE product_id = $productId");
    $stmt = $conn->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?");
    $stmt->bind_param("ii", $imgId, $productId);
    $stmt->execute();
    header("Location: product-form.php?id=$productId");
    exit;
}

if (isset($_GET['delete_serial']) && $productId) {
    $unitId = (int)$_GET['delete_serial'];
    $stmt = $conn->prepare("DELETE FROM product_units WHERE id = ? AND product_id = ?");
    $stmt->bind_param("ii", $unitId, $productId);
    $stmt->execute();
    header("Location: product-form.php?id=$productId");
    exit;
}

if (isset($_GET['toggle_serial']) && $productId) {
    $unitId = (int)$_GET['toggle_serial'];
    $stmt = $conn->prepare("SELECT status FROM product_units WHERE id = ? AND product_id = ?");
    $stmt->bind_param("ii", $unitId, $productId);
    $stmt->execute();
    $unit = $stmt->get_result()->fetch_assoc();
    if ($unit) {
        $newStatus = $unit['status'] === 'available' ? 'sold' : 'available';
        $upd = $conn->prepare("UPDATE product_units SET status = ? WHERE id = ?");
        $upd->bind_param("si", $newStatus, $unitId);
        $upd->execute();
    }
    header("Location: product-form.php?id=$productId");
    exit;
}

if (isset($_GET['delete_variant']) && $productId) {
    $variantId = (int)$_GET['delete_variant'];
    $stmt = $conn->prepare("DELETE FROM product_variants WHERE id = ? AND product_id = ?");
    $stmt->bind_param("ii", $variantId, $productId);
    $stmt->execute();
    header("Location: product-form.php?id=$productId");
    exit;
}

$product = [
    'name' => '', 'category_id' => '', 'description' => '', 'price' => '',
    'condition_type' => 'new', 'brand' => '', 'has_serials' => 0,
    'stock_qty' => 0, 'status' => 'active'
];
$specs = [];
$units = [];
$images = [];
$variants = [];

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    $specStmt = $conn->prepare("SELECT * FROM product_specs WHERE product_id = ?");
    $specStmt->bind_param("i", $productId);
    $specStmt->execute();
    $specs = $specStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $unitStmt = $conn->prepare("SELECT * FROM product_units WHERE product_id = ? ORDER BY created_at DESC");
    $unitStmt->bind_param("i", $productId);
    $unitStmt->execute();
    $units = $unitStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $imgStmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
    $imgStmt->bind_param("i", $productId);
    $imgStmt->execute();
    $images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $varStmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC");
    $varStmt->bind_param("i", $productId);
    $varStmt->execute();
    $variants = $varStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $condition = $_POST['condition_type'] ?? 'new';
    $brand = trim($_POST['brand'] ?? '');
    $hasSerials = isset($_POST['has_serials']) ? 1 : 0;
    $stockQty = (int)($_POST['stock_qty'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    if ($name === '' || $price <= 0) {
        $error = "Name and a valid price are required.";
    } else {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));

        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?, condition_type=?, brand=?, has_serials=?, stock_qty=?, status=? WHERE id=?");
            $stmt->bind_param("isssdssiisi", $categoryId, $name, $slug, $description, $price, $condition, $brand, $hasSerials, $stockQty, $status, $productId);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO products (category_id, name, slug, description, price, condition_type, brand, has_serials, stock_qty, status) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("isssdssiis", $categoryId, $name, $slug, $description, $price, $condition, $brand, $hasSerials, $stockQty, $status);
            $stmt->execute();
            $productId = $conn->insert_id;
        }

        $conn->query("DELETE FROM product_specs WHERE product_id = $productId");
        if (!empty($_POST['spec_name'])) {
            $specStmt = $conn->prepare("INSERT INTO product_specs (product_id, spec_name, spec_value) VALUES (?, ?, ?)");
            foreach ($_POST['spec_name'] as $i => $sName) {
                $sName = trim($sName);
                $sValue = trim($_POST['spec_value'][$i] ?? '');
                if ($sName !== '' && $sValue !== '') {
                    $specStmt->bind_param("iss", $productId, $sName, $sValue);
                    $specStmt->execute();
                }
            }
        }

        if ($hasSerials && !empty($_POST['new_serials'])) {
            $serials = preg_split('/[\r\n,]+/', trim($_POST['new_serials']));
            $unitStmt = $conn->prepare("INSERT INTO product_units (product_id, serial_number, status) VALUES (?, ?, 'available')");
            foreach ($serials as $serial) {
                $serial = trim($serial);
                if ($serial !== '') {
                    $unitStmt->bind_param("is", $productId, $serial);
                    @$unitStmt->execute();
                }
            }
        }

        if (!empty($_POST['variant_color']) || !empty($_POST['variant_storage']) || !empty($_POST['variant_ram'])) {
            $vIds = $_POST['variant_id'] ?? [];
            $vColors = $_POST['variant_color'] ?? [];
            $vStorages = $_POST['variant_storage'] ?? [];
            $vRams = $_POST['variant_ram'] ?? [];
            $vPrices = $_POST['variant_price'] ?? [];
            $vStocks = $_POST['variant_stock'] ?? [];

            foreach ($vColors as $i => $color) {
                $color = trim($color);
                $storage = trim($vStorages[$i] ?? '');
                $ram = trim($vRams[$i] ?? '');
                if ($color === '' && $storage === '' && $ram === '') continue;

                $vPrice = trim($vPrices[$i] ?? '');
                $vPrice = $vPrice === '' ? null : (float)$vPrice;
                $vStock = (int)($vStocks[$i] ?? 0);
                $existingId = (int)($vIds[$i] ?? 0);

                if ($existingId > 0) {
                    $upd = $conn->prepare("UPDATE product_variants SET color=?, storage=?, ram=?, price=?, stock_qty=? WHERE id=? AND product_id=?");
                    $upd->bind_param("sssdiii", $color, $storage, $ram, $vPrice, $vStock, $existingId, $productId);
                    $upd->execute();
                } else {
                    $ins = $conn->prepare("INSERT INTO product_variants (product_id, color, storage, ram, price, stock_qty) VALUES (?, ?, ?, ?, ?, ?)");
                    $ins->bind_param("isssdi", $productId, $color, $storage, $ram, $vPrice, $vStock);
                    $ins->execute();
                }
            }
        }

        if (!empty($_FILES['product_image']['name'][0])) {
            $allowedExt = ['jpg','jpeg','png','webp'];
            foreach ($_FILES['product_image']['name'] as $i => $fileName) {
                if ($fileName === '') continue;
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExt)) {
                    $newName = 'product_' . $productId . '_' . time() . '_' . $i . '.' . $ext;
                    $destination = '../uploads/' . $newName;
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'][$i], $destination)) {
                        $countStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM product_images WHERE product_id = ?");
                        $countStmt->bind_param("i", $productId);
                        $countStmt->execute();
                        $cnt = $countStmt->get_result()->fetch_assoc()['cnt'];
                        $isPrimary = $cnt == 0 ? 1 : 0;

                        $imgStmt2 = $conn->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
                        $imgStmt2->bind_param("isi", $productId, $newName, $isPrimary);
                        $imgStmt2->execute();
                    }
                }
            }
        }

        header("Location: product-form.php?id=$productId&saved=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $isEdit ? 'Edit' : 'Add' ?> Product - Cartcel Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header class="site-header">
    <a href="dashboard.php" class="logo">Cart<span>cel</span></a>
    <p class="tagline">Admin Panel</p>
    <p style="color:#F2EFE9; font-size:13px;"><a href="products.php" style="color:#C9A24B;">← Back to Products</a> | <a href="logout.php" style="color:#C9A24B;">Logout</a></p>
</header>

<div class="admin-content">
    <h2><?= $isEdit ? 'Edit Product' : 'Add New Product' ?></h2>

    <?php if (isset($_GET['saved'])): ?><p class="success-msg">Product saved successfully.</p><?php endif; ?>
    <?php if ($error): ?><p class="error-msg"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="checkout-form">
        <label>Product Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>

        <label>Category</label>
        <select name="category_id">
            <option value="">-- Select --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Brand</label>
        <input type="text" name="brand" value="<?= htmlspecialchars($product['brand']) ?>">

        <label>Description</label>
        <textarea name="description" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>

        <label>Base Price (₦) — used when a variant has no price override</label>
        <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($product['price']) ?>" required>

        <label>Condition</label>
        <select name="condition_type">
            <option value="new" <?= $product['condition_type']=='new'?'selected':'' ?>>New</option>
          <option value="uk_used" <?= $product['condition_type']=='uk_used'?'selected':'' ?>>Pre-owned</option> 
            <option value="ng_used" <?= $product['condition_type']=='ng_used'?'selected':'' ?>>NG Used</option>
        </select>

        <label>Status</label>
        <select name="status">
            <option value="active" <?= $product['status']=='active'?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $product['status']=='inactive'?'selected':'' ?>>Inactive</option>
        </select>

        <label>
            <input type="checkbox" name="has_serials" value="1" <?= $product['has_serials'] ? 'checked' : '' ?> style="width:auto;">
            This product uses serial numbers (phones/laptops)
        </label>

        <div id="stockField" style="<?= $product['has_serials'] ? 'display:none;' : '' ?>">
            <label>Stock Quantity (only used if this product has NO variants below)</label>
            <input type="number" name="stock_qty" value="<?= htmlspecialchars($product['stock_qty']) ?>">
        </div>

        <label>Upload Product Image(s) — you can select multiple</label>
        <input type="file" name="product_image[]" accept="image/*" multiple>

        <?php if (!empty($images)): ?>
            <div class="current-images">
                <p><strong>Current Images:</strong> (click "Set Primary" to choose the main product photo)</p>
                <?php foreach ($images as $img): ?>
                    <div class="image-thumb">
                        <img src="../uploads/<?= htmlspecialchars($img['image_path']) ?>">
                        <?php if ($img['is_primary']): ?>
                            <span class="primary-tag">Primary</span>
                        <?php else: ?>
                            <a href="?id=<?= $productId ?>&set_primary=<?= $img['id'] ?>" class="small-btn">Set Primary</a>
                        <?php endif; ?>
                        <a href="?id=<?= $productId ?>&delete_image=<?= $img['id'] ?>" class="small-btn danger" onclick="return confirm('Delete this image?')">Delete</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h3>Specifications (e.g. RAM: 8GB, Color: Black)</h3>
        <div id="specsContainer">
            <?php if (!empty($specs)): foreach ($specs as $spec): ?>
                <div class="spec-row">
                    <input type="text" name="spec_name[]" placeholder="Spec name" value="<?= htmlspecialchars($spec['spec_name']) ?>">
                    <input type="text" name="spec_value[]" placeholder="Spec value" value="<?= htmlspecialchars($spec['spec_value']) ?>">
                    <button type="button" onclick="this.parentElement.remove()" class="small-btn danger">Remove</button>
                </div>
            <?php endforeach; else: ?>
                <div class="spec-row">
                    <input type="text" name="spec_name[]" placeholder="Spec name">
                    <input type="text" name="spec_value[]" placeholder="Spec value">
                    <button type="button" onclick="this.parentElement.remove()" class="small-btn danger">Remove</button>
                </div>
            <?php endif; ?>
        </div>
        <button type="button" onclick="addSpecRow()" class="small-btn">+ Add Spec</button>

        <h3>Variants (Color / Storage / RAM)</h3>
        <p style="font-size:13px; color:#8A8D96; margin-top:-8px;">Leave price blank to use the base price above. Leave color, storage, and RAM ALL blank on a row to skip it. Only fill this in if this product actually comes in multiple options.</p>
        <div id="variantsContainer">
            <?php if (!empty($variants)): foreach ($variants as $v): ?>
                <div class="variant-row">
                    <input type="hidden" name="variant_id[]" value="<?= $v['id'] ?>">
                    <input type="text" name="variant_color[]" placeholder="Color (e.g. Black)" value="<?= htmlspecialchars($v['color']) ?>">
                    <input type="text" name="variant_storage[]" placeholder="Storage (e.g. 128GB)" value="<?= htmlspecialchars($v['storage']) ?>">
                    <input type="text" name="variant_ram[]" placeholder="RAM (e.g. 8GB)" value="<?= htmlspecialchars($v['ram']) ?>">
                    <input type="number" name="variant_price[]" placeholder="Price override" step="0.01" value="<?= $v['price'] !== null ? htmlspecialchars($v['price']) : '' ?>">
                    <input type="number" name="variant_stock[]" placeholder="Stock" value="<?= htmlspecialchars($v['stock_qty']) ?>">
                    <?php if ($isEdit): ?>
                        <a href="?id=<?= $productId ?>&delete_variant=<?= $v['id'] ?>" class="small-btn danger" onclick="return confirm('Delete this variant?')">Delete</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; else: ?>
                <div class="variant-row">
                    <input type="hidden" name="variant_id[]" value="0">
                    <input type="text" name="variant_color[]" placeholder="Color (e.g. Black)">
                    <input type="text" name="variant_storage[]" placeholder="Storage (e.g. 128GB)">
                    <input type="text" name="variant_ram[]" placeholder="RAM (e.g. 8GB)">
                    <input type="number" name="variant_price[]" placeholder="Price override" step="0.01">
                    <input type="number" name="variant_stock[]" placeholder="Stock">
                    <button type="button" onclick="this.parentElement.remove()" class="small-btn danger">Remove</button>
                </div>
            <?php endif; ?>
        </div>
        <button type="button" onclick="addVariantRow()" class="small-btn">+ Add Variant</button>

        <?php if ($product['has_serials']): ?>
            <label>Add New Serial Numbers (one per line, only for new stock)</label>
            <textarea name="new_serials" rows="4" placeholder="IMEI123456&#10;IMEI789012"></textarea>

            <?php if (!empty($units)): ?>
                <h3>Existing Serials (<?= count($units) ?>)</h3>
                <table class="admin-table">
                    <thead><tr><th>Serial</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($units as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['serial_number']) ?></td>
                                <td><?= ucfirst($u['status']) ?></td>
                                <td>
                                    <a href="?id=<?= $productId ?>&toggle_serial=<?= $u['id'] ?>" class="small-btn">
                                        Mark <?= $u['status']==='available' ? 'Sold' : 'Available' ?>
                                    </a>
                                    <a href="?id=<?= $productId ?>&delete_serial=<?= $u['id'] ?>" class="small-btn danger" onclick="return confirm('Delete this serial?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <button type="submit" class="add-to-cart-btn">Save Product</button>
    </form>
</div>

<script>
function addSpecRow() {
    const container = document.getElementById('specsContainer');
    const row = document.createElement('div');
    row.className = 'spec-row';
    row.innerHTML = '<input type="text" name="spec_name[]" placeholder="Spec name"><input type="text" name="spec_value[]" placeholder="Spec value"><button type="button" onclick="this.parentElement.remove()" class="small-btn danger">Remove</button>';
    container.appendChild(row);
}

function addVariantRow() {
    const container = document.getElementById('variantsContainer');
    const row = document.createElement('div');
    row.className = 'variant-row';
    row.innerHTML = '<input type="hidden" name="variant_id[]" value="0"><input type="text" name="variant_color[]" placeholder="Color (e.g. Black)"><input type="text" name="variant_storage[]" placeholder="Storage (e.g. 128GB)"><input type="text" name="variant_ram[]" placeholder="RAM (e.g. 8GB)"><input type="number" name="variant_price[]" placeholder="Price override" step="0.01"><input type="number" name="variant_stock[]" placeholder="Stock"><button type="button" onclick="this.parentElement.remove()" class="small-btn danger">Remove</button>';
    container.appendChild(row);
}

const serialCheckbox = document.querySelector('input[name="has_serials"]');
if (serialCheckbox) {
    serialCheckbox.addEventListener('change', function() {
        document.getElementById('stockField').style.display = this.checked ? 'none' : 'block';
    });
}
</script>

</body>
</html>