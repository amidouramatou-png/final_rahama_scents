<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

// Fetch products from database
$sql = "SELECT * FROM products WHERE stock > 0 ORDER BY id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - Rahama's Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #d97528;
        }
        .header-links a {
            color: #d97528;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }
        .cart-link {
            background: #d97528;
            color: white !important;
            padding: 10px 20px;
            border-radius: 8px;
        }
        .page-title {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 2rem;
        }
        
        /* Filters */
        .filters {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #d97528;
            background: white;
            color: #d97528;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
        }
        .filter-btn:hover, .filter-btn.active {
            background: #d97528;
            color: white;
        }
        
        /* Search */
        .search-box {
            display: block;
            width: 100%;
            max-width: 400px;
            margin: 0 auto 30px;
            padding: 12px 20px;
            border: 2px solid #ddd;
            border-radius: 25px;
            font-size: 16px;
        }
        .search-box:focus {
            outline: none;
            border-color: #d97528;
        }
        
        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .product-info {
            padding: 20px;
        }
        .product-name {
            font-size: 1.1rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .product-category {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .product-price {
            color: #d97528;
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .btn-add-cart {
            width: 100%;
            padding: 12px;
            background: #d97528;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-add-cart:hover {
            background: #b8651f;
        }
        
        /* Empty State */
        .no-products {
            text-align: center;
            padding: 50px;
            color: #888;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <h1><i class="fas fa-spa"></i> Rahama's Scents</h1>
    <div class="header-links">
        <?php if (isLoggedIn()): ?>
            <a href="my_orders.php">My Orders</a>
            <a href="cart.php" class="cart-link"><i class="fas fa-shopping-cart"></i> Cart</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="signup.php">Sign Up</a>
        <?php endif; ?>
    </div>
</div>

<h1 class="page-title">Our Premium Scents</h1>

<!-- Filters -->
<div class="filters">
    <button class="filter-btn active" data-filter="all">All</button>
    <button class="filter-btn" data-filter="floral">Floral</button>
    <button class="filter-btn" data-filter="fresh">Fresh</button>
    <button class="filter-btn" data-filter="woody">Woody</button>
    <button class="filter-btn" data-filter="citrus">Citrus</button>
    <button class="filter-btn" data-filter="oriental">Oriental</button>
    <button class="filter-btn" data-filter="gourmand">Gourmand</button>
</div>

<!-- Search -->
<input type="text" id="searchInput" placeholder="Search scents..." class="search-box">

<!-- Products Grid -->
<div class="products-grid" id="productsGrid">
    <?php if ($result->num_rows > 0): ?>
        <?php while ($product = $result->fetch_assoc()): ?>
            <div class="product-card" data-category="<?= $product['category'] ?>">
                <img src="<?= $product['image'] ?: 'placeholder.jpg' ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                <div class="product-info">
                    <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                    <div class="product-category"><?= ucfirst($product['category']) ?></div>
                    <div class="product-price">GHC <?= number_format($product['price'], 2) ?></div>
                    <form action="cart.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="btn-add-cart">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="no-products">
            <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <p>No products available yet.</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Filter products
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const filter = btn.dataset.filter;
        document.querySelectorAll('.product-card').forEach(card => {
            if (filter === 'all' || card.dataset.category === filter) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
});

// Search products
document.getElementById('searchInput').addEventListener('input', (e) => {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(card => {
        const name = card.querySelector('.product-name').textContent.toLowerCase();
        card.style.display = name.includes(term) ? 'block' : 'none';
    });
});
</script>

</body>
</html>