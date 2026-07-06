<?php
require '../includes/db.php';

// Get active banners
$banners = $conn->query("SELECT * FROM banners WHERE status = 'active' ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);

// Get all categories for the filter tabs
$catResult = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $catResult->fetch_all(MYSQLI_ASSOC);

// Check if a category filter was selected
$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;

if ($selectedCategory > 0) {
    $stmt = $conn->prepare("SELECT p.*, pi.image_path 
        FROM products p
        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
        WHERE p.status = 'active' AND p.category_id = ?
        ORDER BY p.created_at DESC");
    $stmt->bind_param("i", $selectedCategory);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT p.*, pi.image_path 
            FROM products p
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
            WHERE p.status = 'active'
            ORDER BY p.created_at DESC";
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cartcel - Gadgets Store</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header>
    <h1>Cartcel</h1>
    <p>Quality Gadgets, Genuine Prices</p>
    <a href="cart.php" class="cart-link">🛒 View Cart</a>
</header>

<?php if (!empty($banners)): ?>
<div class="banner-slider" id="bannerSlider">
    <?php foreach ($banners as $i => $b): ?>
        <div class="banner-slide <?= $i === 0 ? 'active' : '' ?>">
            <?php if (!empty($b['link_url'])): ?><a href="<?= htmlspecialchars($b['link_url']) ?>"><?php endif; ?>
                <img src="../uploads/<?= htmlspecialchars($b['image_path']) ?>" alt="<?= htmlspecialchars($b['title']) ?>">
                <?php if ($b['title'] || $b['subtitle']): ?>
                    <div class="banner-caption">
                        <?php if ($b['title']): ?><h2><?= htmlspecialchars($b['title']) ?></h2><?php endif; ?>
                        <?php if ($b['subtitle']): ?><p><?= htmlspecialchars($b['subtitle']) ?></p><?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php if (!empty($b['link_url'])): ?></a><?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php if (count($banners) > 1): ?>
    <div class="banner-dots">
        <?php foreach ($banners as $i => $b): ?>
            <button class="banner-dot <?= $i === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $i ?>)"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="category-filter">
    <a href="index.php" class="filter-btn <?= $selectedCategory === 0 ? 'active' : '' ?>">All</a>
    <?php foreach ($categories as $cat): ?>
        <a href="index.php?category=<?= $cat['id'] ?>" class="filter-btn <?= $selectedCategory === (int)$cat['id'] ? 'active' : '' ?>">
            <?= htmlspecialchars($cat['name']) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="product-grid">
<?php if ($result->num_rows === 0): ?>
    <p style="padding: 20px;">No products found in this category.</p>
<?php endif; ?>
<?php while ($row = $result->fetch_assoc()): ?>
    <a href="product.php?slug=<?= urlencode($row['slug']) ?>" class="product-card">
        <img src="../uploads/<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
        <div class="product-info">
            <span class="badge badge-<?= $row['condition_type'] ?>">
                <?= strtoupper(str_replace('_', ' ', $row['condition_type'])) ?>
            </span>
            <h3><?= htmlspecialchars($row['name']) ?></h3>
            <div class="price">₦<?= number_format($row['price'], 2) ?></div>
        </div>
    </a>
<?php endwhile; ?>
</div>

<script>
let currentSlide = 0;
const slides = document.querySelectorAll('.banner-slide');
const dots = document.querySelectorAll('.banner-dot');

function goToSlide(index) {
    if (slides.length === 0) return;
    slides[currentSlide].classList.remove('active');
    if (dots.length) dots[currentSlide].classList.remove('active');
    currentSlide = index;
    slides[currentSlide].classList.add('active');
    if (dots.length) dots[currentSlide].classList.add('active');
}

function nextSlide() {
    if (slides.length === 0) return;
    goToSlide((currentSlide + 1) % slides.length);
}

if (slides.length > 1) {
    setInterval(nextSlide, 4000);
}
</script>

</body>
</html>