<?php
require '../includes/db.php';

$banners = $conn->query("SELECT * FROM banners WHERE status = 'active' ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);
$catResult = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $catResult->fetch_all(MYSQLI_ASSOC);
$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

$conditions = ["p.status = 'active'"];
$params = [];
$types = '';

if ($selectedCategory > 0) {
    $conditions[] = "p.category_id = ?";
    $params[] = $selectedCategory;
    $types .= 'i';
}

if ($searchTerm !== '') {
    $conditions[] = "(p.name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
    $likeTerm = '%' . $searchTerm . '%';
    $params[] = $likeTerm;
    $params[] = $likeTerm;
    $params[] = $likeTerm;
    $types .= 'sss';
}

$whereClause = implode(' AND ', $conditions);

$sql = "SELECT p.*, pi.image_path 
        FROM products p
        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
        WHERE $whereClause
        ORDER BY p.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Simple icon per category name (fallback to a generic icon)
function categoryIcon($name) {
    $name = strtolower($name);
    if (strpos($name, 'phone') !== false) return '📱';
    if (strpos($name, 'laptop') !== false) return '💻';
    if (strpos($name, 'accessor') !== false) return '🎧';
    return '🛍️';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cartcel - Gadgets Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<header class="site-header">
    <a href="index.php" class="logo">Cart<span>cel</span></a>
    <p class="tagline">Genuine Gadgets, Verified Condition</p>
    <form class="search-bar" action="index.php" method="GET">
        <input type="text" name="search" placeholder="Search phones, laptops, accessories..." value="<?= htmlspecialchars($searchTerm) ?>">
        <button type="submit">Search</button>
    </form>
    <a href="track-order.php" style="color:#F2EFE9; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; margin-right:8px;">Track Order</a>
<a href="cart.php" class="cart-link">Cart</a>
</header>

<?php if (!empty($banners) && $searchTerm === ''): ?>
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

<?php if ($searchTerm === '' && $selectedCategory === 0): ?>
<div class="shop-category-section">
    <h2>Shop by Category</h2>
    <div class="category-tiles">
        <?php foreach ($categories as $cat): ?>
            <a href="index.php?category=<?= $cat['id'] ?>" class="category-tile">
                <span class="cat-icon"><?= categoryIcon($cat['name']) ?></span>
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
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

<div class="section-heading">
    <h2>
        <?php if ($searchTerm !== ''): ?>
            Results for "<?= htmlspecialchars($searchTerm) ?>"
        <?php else: ?>
            Featured Products
        <?php endif; ?>
    </h2>
</div>

<div class="product-grid">
<?php if ($result->num_rows === 0): ?>
    <p style="padding: 20px; text-align:center;">
        <?php if ($searchTerm !== ''): ?>
            No products found matching "<?= htmlspecialchars($searchTerm) ?>". <a href="index.php" style="color:#C9A24B;">View all products</a>
        <?php else: ?>
            No products found in this category.
        <?php endif; ?>
    </p>
<?php endif; ?>
<?php while ($row = $result->fetch_assoc()): ?>
    <a href="product.php?slug=<?= urlencode($row['slug']) ?>" class="product-card">
        <img src="../uploads/<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
        <div class="product-info">
            <span class="badge badge-<?= $row['condition_type'] ?>">
                <?= $row['condition_type'] === 'uk_used' ? 'PRE-OWNED' : strtoupper(str_replace('_', ' ', $row['condition_type'])) ?>
            </span>
            <h3><?= htmlspecialchars($row['name']) ?></h3>
            <div class="price">₦<?= number_format($row['price'], 2) ?></div>
        </div>
    </a>
<?php endwhile; ?>
</div>

<footer class="site-footer">
    <div class="footer-columns">
        <div>
            <h4>Cartcel</h4>
            <p>Your one-stop shop for phones, laptops, and accessories — genuine and verified.</p>
        </div>
        <div>
            <h4>Quick Links</h4>
            <a href="index.php">Home</a>
            <a href="index.php">Shop</a>
            <a href="track-order.php">Track Order</a>
            <a href="cart.php">Cart</a>
        </div>
        <div>
            <h4>Contact Us</h4>
            <p>Email: info@cartcel.com</p>
            <p>Phone: +234 800 000 0000</p>
            <p>Lagos, Nigeria</p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?= date('Y') ?> Cartcel. All rights reserved.
    </div>
</footer>

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