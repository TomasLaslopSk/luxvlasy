<?php
// cart.php
session_start();
require_once 'php/db_connection.php'; // Corrected path (assuming db_connection.php is in php/ subfolder)

// Define the base path for consistent URL generation
$basePath = BASE_URL_PATH;

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
    <title>Váš Košík - LuxVlasy.sk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <script>
        const BASE_URL_JS = "<?php echo BASE_URL_PATH; ?>";
        console.log("XXX:", "<?php echo BASE_URL_PATH; ?>")
    </script>

    <script src="<?php echo $basePath; ?>js/cart.js?v=<?php echo time(); ?>" defer></script>
    <script src="<?php echo $basePath; ?>js/main.js?v=<?php echo time(); ?>" defer></script>
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="brand-logo">
                <a href="<?php echo $basePath; ?>index.php">
                    <img src="<?php echo $basePath; ?>images/logo.png" alt="LuxVlasy.sk Logo" class="site-logo">
                </a>
            </div>

            <nav class="main-nav">
                <ul class="nav-list">
                    <li class="nav-item"><a href="<?php echo $basePath; ?>index.php">Domov</a></li>
                    <li class="nav-item"><a href="#">Starostlivosť o vlasy</a></li>
                    <li class="nav-item"><a href="<?php echo $basePath; ?>index.php?category=Luxusné%20doplnky">Luxusné doplnky</a></li>
                    <li class="nav-item"><a href="<?php echo $basePath; ?>index.php?filter=discount">Akcie</a></li>
                </ul>
            </nav>

            <div class="utility-icons">
                <a href="#" class="icon-link"><i class="fas fa-search"></i></a>
                <a href="#" class="icon-link"><i class="far fa-heart"></i></a>
                <a href="#" class="icon-link"><i class="far fa-user"></i></a>
                <a href="<?php echo $basePath; ?>cart.php" class="icon-link cart-icon">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-count-badge" id="cart-item-count"><?php echo $cartItemCount; ?></span>
                </a>
            </div>
        </div>
    </header>

    <main class="checkout-process-container">
        <div class="checkout-steps-indicator">
            <span class="step-label active" data-step="1">1 Nákupný košík</span>
            <span class="step-separator"></span>
            <span class="step-label" data-step="2">2 Doprava & platba</span>
            <span class="step-separator"></span>
            <span class="step-label" data-step="3">3 Informácie o Vás</span>
        </div>

        <section id="checkout-step-1" class="checkout-step active">
            <h2 class="section-title">Váš Nákupný Košík</h2>

            <div id="cart-items-container">
                <p class="loading-message">Načítavam položky košíka...</p>
            </div>

            <div class="cart-summary-box">
                <h3>Celkom k úhrade:</h3>
                <p class="total-price" id="cart-summary-total">€0,00</p>
                <button id="checkout-btn" class="button primary-button" disabled>
                    <i class="fas fa-credit-card"></i> Pokračovať k platbe
                </button>
            </div>
        </section>

        <section id="checkout-step-2" class="checkout-step">
            <h2 class="section-title">Doprava & platba</h2>
            <form id="shipping-payment-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="country">Krajina doručenia</label>
                        <select id="country" name="country">
                            <option value="SK">Slovenská republika</option>
                            <option value="CZ">Česká republika</option>
                            </select>
                    </div>
                    <div class="form-group">
                        <label for="currency">Mena</label>
                        <select id="currency" name="currency">
                            <option value="EUR">EUR</option>
                            <option value="CZK">CZK</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Zvoľte spôsob dopravy</label>
                    <div class="shipping-options" id="shipping-options">
                        <p class="info-message">Načítavam možnosti dopravy...</p>
                        <p class="error-message hidden" id="no-shipping-error">Táto kombinácia dopravy a platby nie je možná.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Zvoľte spôsob platby</label>
                    <div class="payment-options" id="payment-options">
                        <p class="info-message">Načítavam možnosti platby...</p>
                        <p class="error-message hidden" id="no-payment-error">Táto kombinácia dopravy a platby nie je možná.</p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="button secondary-button back-to-cart-btn" data-step="1">Späť na košík</button>
                    <button type="submit" class="button primary-button continue-to-info-btn">Pokračovať na Informácie o Vás</button>
                </div>
            </form>
        </section>

        <section id="checkout-step-3" class="checkout-step">
    <h2 class="section-title">Informácie o Vás</h2>
    <form id="user-info-form">
        <div class="form-row"> <div class="form-group">
                <label for="first_name">Meno:</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Priezvisko:</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="phone">Telefón:</label>
            <input type="tel" id="phone" name="phone">
        </div>

        <h3 class="subsection-title">Adresa doručenia:</h3> <div class="form-row"> <div class="form-group">
                <label for="street">Ulica:</label>
                <input type="text" id="street" name="street" required>
            </div>
            <div class="form-group" style="flex: 0 0 100px;"> <label for="house_number">Č. domu:</label>
                <input type="text" id="house_number" name="house_number" required>
            </div>
        </div>
        <div class="form-row"> <div class="form-group">
                <label for="city">Obec / Mesto:</label>
                <input type="text" id="city" name="city" required>
            </div>
            <div class="form-group">
                <label for="postal_code">PSČ:</label>
                <input type="text" id="postal_code" name="postal_code" required>
            </div>
        </div>
        <div class="form-group">
            <label for="address_country">Krajina:</label>
            <select id="address_country" name="address_country" required>
                <option value="SK">Slovenská republika</option>
                <option value="CZ">Česká republika</option>
                </select>
        </div>

        <div class="form-actions">
            <button type="button" class="button secondary-button back-to-shipping-btn" data-step="2">Späť na Dopravu & platbu</button>
            <button type="submit" class="button primary-button place-order-btn">Dokončiť objednávku</button>
        </div>
    </form>
</section>

    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> LuxVlasy.sk. Všetky práva vyhradené.</p>
    </footer>

    <style>
    /* General body and container improvements */
    body {
        font-family: 'Open Sans', sans-serif;
        background-color: #f8f8f8; /* A very light gray background */
        color: #333;
    }
    .main-header {
        background-color: #ffffff; /* White header for a clean look */
        border-bottom: 1px solid #e0e0e0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05); /* Subtle shadow */
    }
    .header-container {
        padding: 15px 40px; /* More padding */
    }
    .main-nav .nav-list .nav-item a {
        color: #555;
        font-weight: 600; /* Slightly bolder for navigation */
        transition: color 0.3s ease;
    }
    .main-nav .nav-list .nav-item a:hover {
        color: #007bff; /* Primary blue on hover */
    }
    .utility-icons .icon-link {
        color: #555;
        transition: color 0.3s ease;
    }
    .utility-icons .icon-link:hover {
        color: #007bff;
    }
    .cart-count-badge {
        background-color: black; /* Consistent blue for badge */
        color: white;
        font-weight: 700;
        font-size: 0.75em; /* Slightly smaller badge text */
        padding: 2px 6px;
        border-radius: 50%;
    }
    /* Bouncing animation remains for functionality */
    @keyframes bouncing {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    .cart-count-badge.bouncing {
        animation: bouncing 0.3s ease-in-out;
    }

    /* Main Checkout Process Container */
    .checkout-process-container {
        max-width: 960px; /* Slightly wider for more space */
        margin: 40px auto;
        padding: 30px 40px; /* More internal padding */
        background-color: #fff;
        border-radius: 8px; /* Slightly more rounded corners */
        box-shadow: 0 4px 15px rgba(0,0,0,0.08); /* More prominent, soft shadow */
    }

    /* Checkout Steps Indicator - MODERNIZED AND FIXED */
    .checkout-steps-indicator {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 50px;
        padding-bottom: 0;
        border-bottom: none;
        position: relative;
    }
    /* Horizontal line connecting steps */
    .checkout-steps-indicator::before {
        content: '';
        position: absolute;
        top: 18px; /* Center with the circles (half of width/height) */
        left: 0;
        right: 0;
        height: 2px;
        background-color: #e0e0e0; /* Light gray line */
        z-index: 1; /* Behind labels */
    }

    .step-label {
        flex-grow: 1;
        text-align: center;
        color: #999; /* Lighter color for inactive steps */
        font-weight: 600;
        position: relative;
        padding-top: 50px; /* More space for the circle/icon */
        z-index: 2; /* Above the line */
        transition: color 0.3s ease;
    }
    .step-label.active {
        color: #333; /* Darker for active text */
    }
    .step-label.completed {
        color: #007bff; /* Primary blue for completed text */
    }

    /* Circles for step numbers/icons */
    .step-label::after {
        content: attr(data-step); /* Use data-step for number by default */
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: 0; /* Position at the very top of padding-top space */
        left: 50%;
        transform: translateX(-50%);
        width: 36px; /* Size of the circle */
        height: 36px;
        border-radius: 50%;
        background-color: #e0e0e0; /* Inactive circle color */
        color: #fff; /* White number */
        font-size: 1.1em;
        font-weight: 700;
        border: 2px solid #e0e0e0; /* Border matches background for inactive */
        transition: all 0.3s ease;
    }

    /* Styles for Active Step Number/Icon */
    .step-label.active::after {
        background-color: black; /* Primary blue for active circle */
        border-color: black;
    }

    /* Styles for Completed Step Checkmark */
    .step-label.completed::after {
        content: '\f00c'; /* FontAwesome checkmark for completed */
        font-family: "Font Awesome 5 Free"; /* Ensure Font Awesome is used */
        font-weight: 900; /* Solid icon */
        background-color: #28a745; /* Green for completed circle */
        border-color: #28a745;
        font-size: 1.2em; /* Slightly larger checkmark */
    }

    /* Ensure text color matches circle for active/completed */
    .step-label.active {
        color: black; /* Active step text is blue */
    }
    .step-label.completed {
        color: #28a745; /* Completed step text is green */
    }

    /* Remove the separator span, as the ::before on indicator handles the line */
    .step-separator {
        display: none;
    }

    /* Checkout Steps Content */
    .section-title {
        font-size: 2em;
        color: #333;
        margin-bottom: 30px;
        font-weight: 700;
        text-align: center;
    }
    .checkout-step {
        display: none;
        padding: 30px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-top: 25px;
    }
    .checkout-step.active {
        display: block;
    }

    /* Form Elements */
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        font-size: 0.95em;
        margin-bottom: 8px;
        color: #555;
    }
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="tel"],
    .form-group select {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 1em;
        box-sizing: border-box; /* Crucial for width: 100% with padding */
    }
    .form-group input:focus, .form-group select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        outline: none;
    }

    /* Buttons */
    .button {
        padding: 12px 25px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        font-weight: 600;
        transition: background-color 0.3s ease, color 0.3s ease;
    }
    .primary-button {
        background-color:rgb(0, 7, 15);
        color: white;
    }
    .primary-button:hover {
        background-color: #f0c14b;
    }
    .primary-button:disabled {
        background-color: #cccccc;
        cursor: not-allowed;
    }
    .secondary-button {
        background-color: #f0f0f0;
        color: #555;
        border: 1px solid #ccc;
    }
    .secondary-button:hover {
        background-color: #e0e0e0;
        color: #333;
    }
    .form-actions {
        margin-top: 30px;
    }

    /* Shipping & Payment Options */
    .shipping-option, .payment-option {
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        padding: 10px 15px;
        border: 1px solid #eee;
        border-radius: 5px;
        background-color: #fdfdfd;
    }
    .shipping-option:hover, .payment-option:hover {
        background-color: #f5f5f5;
    }
    .shipping-option input[type="radio"], .payment-option input[type="radio"] {
        margin-right: 10px;
        transform: scale(1.2);
    }
    .shipping-option label, .payment-option label {
        margin-bottom: 0;
        font-weight: 400;
        color: #333;
        flex-grow: 1;
    }

    /* Cart Items Container - MODERNIZED AND FIXED */
    #cart-items-container {
        padding: 0;
        border: none;
    }
    .cart-item {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        margin-bottom: 12px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        background-color: #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        /* Ensure items stretch to fill container width if needed */
        width: 100%;
        box-sizing: border-box;
    }
    .cart-item img {
        flex-shrink: 0; /* Prevent image from shrinking */
        width: 90px;
        height: 90px;
        margin-right: 20px;
        border-radius: 4px;
        object-fit: contain;
        border: 1px solid #f0f0f0;
    }
    .cart-item h4 {
        flex-grow: 1; /* Product name takes available space */
        font-size: 1.15em;
        font-weight: 600;
        margin: 0;
        color: #333;
        line-height: 1.3; /* Better readability for long names */
    }
    /* Grouping price and quantity for better alignment */
    .cart-item .item-details-right {
        display: flex;
        align-items: center;
        gap: 15px; /* Space between price, quantity, subtotal */
        flex-shrink: 0; /* Prevent this group from shrinking */
    }
    .cart-item .price {
        font-weight: 700;
        color: #333;
        width: 80px; /* Fixed width for consistent alignment */
        text-align: right;
        margin: 0; /* Remove previous margins */
    }
    .cart-item .item-quantity {
        width: 70px;
        padding: 10px;
        text-align: center;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin: 0; /* Remove previous margins */
        font-size: 1em;
    }
    .cart-item .subtotal {
        font-weight: 700;
        color: #333;
        width: 120px; /* Wider for "Spolu: €XXX,XX" */
        text-align: right;
        margin: 0; /* Remove previous margins */
    }
    .cart-item .remove-from-cart-btn {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 10px 15px;
        cursor: pointer;
        border-radius: 5px;
        font-size: 0.9em;
        display: flex;
        align-items: center;
        gap: 5px;
        margin-left: 20px; /* Space from other elements */
        flex-shrink: 0; /* Prevent button from shrinking */
    }
    .cart-item .remove-from-cart-btn:hover {
        background-color: #c82333;
    }
    /* Specific styling for the 'Odstrániť' button container/wrapper if present */
    /* If you have a specific wrapper around the remove button, you might need to adjust its margin */
    /* Example: .remove-button-wrapper { margin-left: auto; } */


    .cart-summary-box {
        border-top: 1px solid #e0e0e0;
        padding-top: 25px;
        margin-top: 25px;
        text-align: right;
        background-color: #fdfdfd;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .cart-summary-box h3 {
        font-size: 1.4em;
        color: #555;
        font-weight: 600;
        margin-bottom: 10px; /* Space between title and price */
    }
    .cart-summary-box .total-price {
        font-size: 2.2em;
        font-weight: 700;
        color:rgb(0, 7, 15);
        margin-bottom: 25px;
    }
    .cart-summary-box .primary-button {
        padding: 15px 30px;
        font-size: 1.2em;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Additional Styling for the "Price without VAT" if it exists */
    .cart-summary-box .price-without-vat {
        font-size: 0.9em;
        color: #777;
        margin-top: -15px; /* Pull it closer to the total price */
        margin-bottom: 15px; /* Add space below it */
    }

    /* Messages (loading, empty, error) */
    .empty-cart-message, .loading-message, .error-message {
        text-align: center;
        padding: 30px;
        color: #777;
        font-style: normal;
        font-size: 1.1em;
        background-color: #f0f0f0;
        border-radius: 8px;
        margin-top: 20px;
    }
    .error-message {
        color: #dc3545;
        background-color: #ffeaea;
        border: 1px solid #dc3545;
    }

    /* Placeholder styles for 'Dárčeky a zľavy' section */
    .promo-section {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        background-color: #fff;
    }
    .promo-section h3 {
        margin-top: 0;
        font-size: 1.2em;
        color: #333;
    }
    .promo-section .gift-item {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        font-size: 0.95em;
        color: #555;
    }
    .promo-section .gift-item img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        margin-right: 10px;
        border-radius: 4px;
        border: 1px solid #eee;
    }
    .promo-section .change-link {
        margin-left: auto; /* Push "Zmeniť" to the right */
        color:rgb(0, 7, 15);
        text-decoration: none;
        font-weight: 600;
    }
    .promo-section .change-link:hover {
        text-decoration: underline;
    }
    .promo-section .coupon-code-checkbox {
        margin-top: 15px;
        display: flex;
        align-items: center;
    }
    .promo-section .coupon-code-checkbox input[type="checkbox"] {
        margin-right: 10px;
        transform: scale(1.1);
    }
    .promo-section .coupon-code-checkbox label {
        font-weight: 600;
        color: #333;
    }

    /* Summary sidebar (Potrebujete poradiť?) */
    .summary-sidebar {
        width: 300px; /* Example fixed width */
        margin-left: 30px;
        padding: 25px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        background-color: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        align-self: flex-start; /* Stick to the top */
    }
    .summary-sidebar h3 {
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 1.3em;
        color: #333;
    }
    .summary-sidebar p {
        font-size: 0.95em;
        margin-bottom: 10px;
        color: #555;
        line-height: 1.4;
    }
    .summary-sidebar .contact-info i {
        margin-right: 8px;
        color:rgb(0, 7, 15);
    }
    .summary-sidebar .contact-info a {
        color:rgb(0, 7, 15);
        text-decoration: none;
    }
    .summary-sidebar .contact-info a:hover {
        text-decoration: underline;
    }
    .summary-sidebar hr {
        border: none;
        border-top: 1px solid #eee;
        margin: 20px 0;
    }

    /* Flex container for main content and sidebar */
    .cart-checkout-main {
        display: flex;
        justify-content: space-between;
        align-items: flex-start; /* Align items to the top */
        gap: 30px; /* Space between content and sidebar */
    }
    /* Adjustments for the main cart section to fit flex layout */
    section#checkout-step-1 {
        flex-grow: 1; /* Allows the main cart section to take remaining space */
        padding: 0; /* Remove padding as cart items have it */
        border: none; /* Remove border from the section itself */
        box-shadow: none; /* Remove shadow from the section itself */
        margin-top: 0; /* Adjust margin */
    }

    /* Styling for the new table-like structure of cart items */
    .cart-table-header {
        display: flex;
        align-items: center;
        padding: 10px 20px;
        margin-bottom: 10px;
        color: #777;
        font-weight: 600;
        font-size: 0.9em;
        border-bottom: 1px solid #e0e0e0;
    }
    .cart-table-header div {
        text-align: right; /* Default align right for numeric columns */
    }
    .cart-table-header .header-product {
        flex-grow: 1;
        text-align: left;
        margin-left: 110px; /* Align with product name, considering image */
    }
    .cart-table-header .header-availability {
        width: 100px; /* Match width of stock status */
        text-align: center;
    }
    .cart-table-header .header-quantity {
        width: 70px; /* Match width of quantity input */
        text-align: center;
        margin: 0 20px; /* Match margins of input */
    }
    .cart-table-header .header-price-per-unit {
        width: 80px; /* Match width of price */
        margin: 0 20px;
    }
    .cart-table-header .header-total-price {
        width: 120px; /* Match width of subtotal */
        margin: 0 20px;
    }
    .cart-table-header .header-remove {
        width: 90px; /* Match width of remove button + its margin */
        margin-left: 20px;
    }

    /* Adjust cart item layout to match table headers */
    .cart-item .product-info {
        flex-grow: 1;
        display: flex;
        align-items: center;
    }
    .cart-item .stock-status {
        width: 100px;
        text-align: center;
        font-weight: 600;
        color: #28a745; /* Green for in stock */
        flex-shrink: 0;
    }
    .cart-item .stock-status.out-of-stock {
        color: #dc3545; /* Red for out of stock */
    }


    /* Media Queries for Responsiveness (Basic Example) */
    @media (max-width: 768px) {
        .header-container {
            padding: 15px 20px;
        }
        .main-nav {
            display: none; /* Hide nav on smaller screens, implement a hamburger menu */
        }
        .checkout-process-container {
            margin: 20px auto;
            padding: 20px;
        }
        .checkout-steps-indicator {
            flex-wrap: wrap; /* Allow steps to wrap */
            margin-bottom: 30px;
        }
        .step-label {
            width: 33.3%; /* Each step takes 1/3 width */
            padding-top: 40px;
            font-size: 0.9em;
        }
        .step-label::after {
            top: 5px;
            width: 30px;
            height: 30px;
            font-size: 1em;
        }
        .checkout-steps-indicator::before {
            top: 20px;
        }
        .cart-item {
            flex-wrap: wrap; /* Allow items to wrap */
            justify-content: center;
            text-align: center;
        }
        .cart-item img {
            margin-right: 0;
            margin-bottom: 10px;
        }
        .cart-item h4, .cart-item .price, .cart-item .item-quantity, .cart-item .subtotal, .cart-item .remove-from-cart-btn {
            width: 100%;
            text-align: center;
            margin: 5px 0;
        }
        .cart-item .item-details-right {
            flex-wrap: wrap;
            justify-content: center;
            gap: 5px;
        }
        .cart-item .price, .cart-item .subtotal {
            width: auto;
        }
        .summary-sidebar {
            width: 100%;
            margin-left: 0;
            margin-top: 30px;
        }
        .cart-checkout-main {
            flex-direction: column; /* Stack main content and sidebar */
        }
    }
</style>
</body>
</html>