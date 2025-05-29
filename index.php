<?php
// PHP kód: Uistite sa, že session je spustená na začiatku každého PHP súboru
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// PHP kód: Výpočet počtu položiek v košíku
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
    <title>Luxvlasy.sk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>"> <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <script src="js/main.js?v=<?php echo time(); ?>" defer></script>
    <script src="js/cart.js?v=<?php echo time(); ?>" defer></script>
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
                <li class="nav-item"><a href="index.php?category=Šampóny">Starostlivosť o vlasy</a></li>
                <li class="nav-item"><a href="index.php?category=Luxusné%20doplnky">Luxusné doplnky</a></li>
                <li class="nav-item"><a href="index.php?filter=discount">Akcie</a></li>
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
        <section id="promo-slider-container" class="slider-section">
            <div id="promoSlider" class="slider">
                <div class="slider-item active">
                    <img src="images/love_your_hair.png" alt="Starostlivost o vlasy akcia">
                    <div class="slider-caption">
                        <h5>Letný výpredaj!</h5>
                        <p>Až 30% zľava na vybrané položky.</p>
                        <a href="#" class="btn-promo">Nakupovať teraz</a>
                    </div>
                </div>
                <div class="slider-item">
                    <img src="images/BalmainHair_WEB_CampaignBanners_C1_2023_MaskPromo_02_EN_700x932_01ce7ce1-76d4-42f6-a749-15dcdf267b95.webp" alt="Balmain akcia">
                    <div class="slider-caption">
                        <h5>Novinky!</h5>
                        <p>Objavte najnovšie v kozmetike na vlasy.</p>
                        <a href="#" class="btn-promo">Preskúmať</a>
                    </div>
                </div>
                <div class="slider-item">
                    <img src="images/BalmainHair_WEB_MOBILE_HC_Webshop_Slider_C1_Promo_IlluminatingHairMask_C1_25_US_1400x.webp" alt="Balmain maska na vlasy">
                    <div class="slider-caption">
                        <h5>Doprava zadarmo!</h5>
                        <p>Pri všetkých objednávkach nad 50 € tento týždeň.</p>
                        <a href="#" class="btn-promo">Zistiť viac</a>
                    </div>
                </div>
            </div>
            <button class="slider-prev">&#10094;</button>
            <button class="slider-next">&#10095;</button>
            <div class="slider-dots"></div>
        </section>

        <section class="products-section">
            <h2>Obľúbené Produkty</h2>
            <div class="filter-buttons-container">
                <button class="filter-btn active" data-category="all">Všetko</button>
                <button class="filter-btn" data-category="Balmain">Balmain</button>
                <button class="filter-btn" data-category="Redken">Redken</button>
                <button class="filter-btn" data-category="Great Lengths">Great Lengths</button>
                <button class="filter-btn" data-category="Šampóny" data-filter-type="category">Šampóny</button>
                <button class="filter-btn" data-category="Masky" data-filter-type="category">Masky</button>
                <button class="filter-btn" data-category="Styling" data-filter-type="category">Styling</button>
                <button class="filter-btn" data-category="Oleje" data-filter-type="category">Oleje</button>
                <button class="filter-btn" data-category="Luxusné doplnky" data-filter-type="category">Luxusné doplnky</button>
                </div>
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