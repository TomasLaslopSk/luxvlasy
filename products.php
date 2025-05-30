<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produkty</title>
    <link rel="stylesheet" href="<?php echo BASE_URL_PATH; ?>style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header>
        <nav class="main-nav">
            <ul>
                <li class="nav-item"><a href="<?php echo BASE_URL_PATH; ?>products.php?category=Luxusné%20doplnky">Luxusné doplnky</a></li>
                <li class="nav-item"><a href="<?php echo BASE_URL_PATH; ?>products.php?filter=discount">Akcie</a></li>
                <li class="nav-item"><a href="<?php echo BASE_URL_PATH; ?>index.php">Domov</a></li>
            </ul>
        </nav>
        </header>

    <main>
        <section class="promo-slider" id="promoSlider">
            </section>

        <section class="product-filters">
            <div class="filter-buttons-container">
                <button class="filter-btn active" data-filter-type="all" data-category="all">Všetko</button>
                <button class="filter-btn" data-filter-type="category" data-category="Luxusné doplnky">Luxusné doplnky</button>
                <button class="filter-btn" data-filter-type="category" data-category="Šampóny">Šampóny</button>
                <button class="filter-btn" data-filter-type="brand" data-category="Balmain">Balmain</button>
                <button class="filter-btn" data-filter-type="brand" data-category="Redken">Redken</button>
                <button class="filter-btn" data-filter-type="discount" data-category="discount">Akcie</button>
            </div>
        </section>

        <section class="products-section">
            <div id="product-list" class="products-grid">
                </div>
        </section>
    </main>

    <footer>
        </footer>

    <script src="<?php echo BASE_URL_PATH; ?>js/cart.js"></script>
    <script src="<?php echo BASE_URL_PATH; ?>js/product_display.js"></script>
</body>
</html>