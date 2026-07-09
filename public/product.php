<?php
require '../includes/db.php';

$slug = $_GET['slug'] ?? '';

$stmt = $conn->prepare("SELECT * FROM products WHERE slug = ? AND status = 'active'");
$stmt->bind_param("s", $slug);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

$imgStmt = $conn->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC");
$imgStmt->bind_param("i", $product['id']);
$imgStmt->execute();
$images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$specStmt = $conn->prepare("SELECT spec_name, spec_value FROM product_specs WHERE product_id = ?");
$specStmt->bind_param("i", $product['id']);
$specStmt->execute();
$specs = $specStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$varStmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ? AND status = 'active' ORDER BY id ASC");
$varStmt->bind_param("i", $product['id']);
$varStmt->execute();
$variants = $varStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$colors = array_values(array_unique(array_filter(array_column($variants, 'color'))));
$storages = array_values(array_unique(array_filter(array_column($variants, 'storage'))));
$rams = array_values(array_unique(array_filter(array_column($variants, 'ram'))));
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($product['name']) ?> - Cartcel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header class="site-header">
    <a href="index.php" class="logo">Cart<span>cel</span></a>
    <p class="tagline">Genuine Gadgets, Verified Condition</p>
    <a href="cart.php" class="cart-link">Cart</a>
</header>

<div class="product-detail">
    <div class="product-detail-images">
        <?php foreach ($images as $img): ?>
            <img src="../uploads/<?= htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        <?php endforeach; ?>
    </div>

    <div class="product-detail-info">
        <span class="badge badge-<?= $product['condition_type'] ?>">
            <?= $product['condition_type'] === 'uk_used' ? 'PRE-OWNED' : strtoupper(str_replace('_', ' ', $product['condition_type'])) ?>
        </span>
        <h2><?= htmlspecialchars($product['name']) ?></h2>
        <div class="price" id="displayPrice">₦<?= number_format($product['price'], 2) ?></div>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

        <?php if (count($specs) > 0): ?>
            <h3>Specifications</h3>
            <ul class="spec-list">
                <?php foreach ($specs as $spec): ?>
                    <li><strong><?= htmlspecialchars($spec['spec_name']) ?>:</strong> <?= htmlspecialchars($spec['spec_value']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form action="add-to-cart.php" method="POST" id="addToCartForm">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <input type="hidden" name="variant_id" id="selectedVariantId" value="0">

            <?php if (!empty($colors)): ?>
                <label>Color</label>
                <select name="color_select" id="colorSelect" required>
                    <option value="">-- Choose color --</option>
                    <?php foreach ($colors as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <?php if (!empty($storages)): ?>
                <label>Storage</label>
                <select name="storage_select" id="storageSelect" required>
                    <option value="">-- Choose storage --</option>
                    <?php foreach ($storages as $s): ?>
                        <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <?php if (!empty($rams)): ?>
                <label>RAM</label>
                <select name="ram_select" id="ramSelect" required>
                    <option value="">-- Choose RAM --</option>
                    <?php foreach ($rams as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <?php if (!empty($variants)): ?>
                <p id="stockNotice" style="font-size:13px; color:#8A8D96; margin-top:8px;"></p>
            <?php endif; ?>

            <label for="quantity">Quantity:</label>
            <input type="number" id="quantity" name="quantity" value="1" min="1" style="width:60px; margin: 0 10px;">
            <button type="submit" class="add-to-cart-btn" id="addToCartBtn">Add to Cart</button>
        </form>
    </div>
</div>

<?php if (!empty($variants)): ?>
<script>
const variants = <?= json_encode($variants) ?>;
const basePrice = <?= (float)$product['price'] ?>;
const hasColor = <?= !empty($colors) ? 'true' : 'false' ?>;
const hasStorage = <?= !empty($storages) ? 'true' : 'false' ?>;
const hasRam = <?= !empty($rams) ? 'true' : 'false' ?>;

const colorSelect = document.getElementById('colorSelect');
const storageSelect = document.getElementById('storageSelect');
const ramSelect = document.getElementById('ramSelect');
const priceDisplay = document.getElementById('displayPrice');
const variantIdInput = document.getElementById('selectedVariantId');
const stockNotice = document.getElementById('stockNotice');
const addBtn = document.getElementById('addToCartBtn');

function updateSelection() {
    const selColor = hasColor ? colorSelect.value : null;
    const selStorage = hasStorage ? storageSelect.value : null;
    const selRam = hasRam ? ramSelect.value : null;

    if ((hasColor && !selColor) || (hasStorage && !selStorage) || (hasRam && !selRam)) {
        stockNotice.textContent = '';
        return;
    }

    const match = variants.find(v =>
        (!hasColor || v.color === selColor) &&
        (!hasStorage || v.storage === selStorage) &&
        (!hasRam || v.ram === selRam)
    );

    if (match) {
        const price = match.price !== null ? parseFloat(match.price) : basePrice;
        priceDisplay.textContent = '₦' + price.toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        variantIdInput.value = match.id;

        if (match.stock_qty > 0) {
            stockNotice.textContent = match.stock_qty + ' in stock';
            stockNotice.style.color = '#7A8E6B';
            addBtn.disabled = false;
        } else {
            stockNotice.textContent = 'Out of stock for this option';
            stockNotice.style.color = '#B23B3B';
            addBtn.disabled = true;
        }
    } else {
        stockNotice.textContent = 'This combination is not available';
        stockNotice.style.color = '#B23B3B';
        variantIdInput.value = 0;
        addBtn.disabled = true;
    }
}

if (colorSelect) colorSelect.addEventListener('change', updateSelection);
if (storageSelect) storageSelect.addEventListener('change', updateSelection);
if (ramSelect) ramSelect.addEventListener('change', updateSelection);
</script>
<?php endif; ?>

</body>
</html>