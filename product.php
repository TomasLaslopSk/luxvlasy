<?php
// product.php

// Ensure session is started at the very beginning of the file, if not already started.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// PHP code to calculate the current number of items in the cart for display in the header.
$cartItemCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartItemCount += $item['quantity'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Naše Produkty - LuxVlasy.sk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <script src="js/cart.js?v=<?php echo time(); ?>" defer></script>
    <script src="js/main.js?v=<?php echo time(); ?>" defer></script>

    <script src="js/product_display.js?v=<?php echo time(); ?>" defer></script>
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="brand-logo">
                <a href="index.php">
                    <img src="images/logo.png" alt="LuxVlasy.sk Logo" class="site-logo">
                </a>
            </div>

            <nav class="main-nav">
                <ul class="nav-list">
                    <li class="nav-item"><a href="index.php">Domov</a></li>
                    <li class="nav-item dropdown">
                        <a href="#" class="dropdown-toggle">Značky <i class="fas fa-chevron-down dropdown-arrow"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="#">Balmain</a></li>
                            <li><a href="#">Redken</a></li>
                            <li><a href="#">Great Lengths</a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a href="#">Starostlivosť o vlasy</a></li>
                    <li class="nav-item"><a href="#">Akcie</a></li>
                </ul>
            </nav>

            <div class="utility-icons">
                <a href="#" class="icon-link"><i class="fas fa-search"></i></a>
                <a href="#" class="icon-link"><i class="far fa-heart"></i></a>
                <a href="#" class="icon-link"><i class="far fa-user"></i></a>
                <a href="cart.php" class="icon-link cart-icon">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-count-badge" id="cart-item-count"><?php echo $cartItemCount; ?></span>
                </a>
            </div>
        </div>
    </header>

    <main>
        <section class="products-section">
            <h2 class="section-title">Naše Produkty</h2>
            <div id="product-list" class="product-grid">
                <p class="loading-message">Načítavam produkty...</p>
            </div>
        </section>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> LuxVlasy.sk. Všetky práva vyhradené.</p>
    </footer>

</body>
</html>