<?php
// order_confirmation.php
session_start();
require_once 'php/db_connection.php'; // Adjust path if necessary

// Temporarily enable ALL error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Define the base path for consistent URL generation
$basePath = BASE_URL_PATH; // This must match your MAMP setup

$order_id = null;
$order_details = null;
$order_items = [];
$error_message = '';
$qr_code_image_url = ''; // Variable to store the generated QR code URL

// Get order_id from URL parameter
if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    error_log("Order confirmation page accessed for order_id: " . $order_id); // Log access

    $conn = getDbConnection(); // Assume this connects and returns PDO object

    // Check if database connection was successful
    if (!$conn) {
        $error_message = 'Nastala interná chyba: Nepodarilo sa pripojiť k databáze.';
        error_log("Failed to get database connection in order_confirmation.php");
    } else {
        try {
            // Fetch main order details
            $sql_order = "SELECT
                o.id, o.first_name, o.last_name, o.email, o.phone,
                o.shipping_address_street, o.shipping_address_house_number,
                o.shipping_address_city, o.shipping_address_postal_code, o.shipping_address_country,
                o.total_amount, o.order_status, o.payment_status, o.created_at AS order_date,
                sm.name AS shipping_method_name, sm.price AS shipping_method_price,
                pm.name AS payment_method_name, pm.details AS payment_method_details
                FROM orders o
                JOIN shipping_methods sm ON o.shipping_method_id = sm.id
                JOIN payment_methods pm ON o.payment_method_id = pm.id
                WHERE o.id = ?";
            $stmt_order = $conn->prepare($sql_order);

            if (!$stmt_order) {
                $error_info = $conn->errorInfo();
                error_log("Failed to prepare order details statement: " . (isset($error_info[2]) ? $error_info[2] : 'Unknown error'));
                $error_message = 'Nastala chyba pri príprave dotazu objednávky. Kód: ' . (isset($error_info[1]) ? $error_info[1] : 'N/A');
                throw new PDOException("Failed to prepare order details statement.");
            }

            $stmt_order->execute([$order_id]);
            $order_details = $stmt_order->fetch(PDO::FETCH_ASSOC);

            if ($order_details) {
                error_log("Order details found for ID " . $order_id . ": " . print_r($order_details, true));

                // Generate QR code URL if payment method is "Bankový prevod s QR" and IBAN details are available
                if ($order_details['payment_method_name'] === 'Bankový prevod s QR' && !empty($order_details['payment_method_details'])) {
                    // Split details: IBAN, BIC/SWIFT, and optionally Recipient Name (e.g., "SKXXXXXXXXXXXXXXX;BICCODE;CompanyName")
                    $payment_details_parts = explode(';', $order_details['payment_method_details']);
                    $iban = trim($payment_details_parts[0]);
                    $bic_swift = isset($payment_details_parts[1]) ? trim($payment_details_parts[1]) : ''; // Optional BIC/SWIFT
                    $recipient_name = isset($payment_details_parts[2]) ? trim($payment_details_parts[2]) : ''; // Optional Recipient Name

                    $amount = number_format($order_details['total_amount'], 2, '.', ''); // Format amount with dot as decimal separator
                    $vs = $order_details['id']; // Order ID as Variable Symbol

                    // Construct the EPC QR code data string (BCD 001 1 SCT format)
                    $payBySquareData = "BCD\n" .
                                       "001\n" .
                                       "1\n" .
                                       "SCT\n" .
                                       ($bic_swift ? $bic_swift : '') . "\n" .
                                       $recipient_name . "\n" .
                                       $iban . "\n" .
                                       "EUR" . $amount . "\n" .
                                       "\n" .
                                       "VS" . $vs . "\n" .
                                       "\n";

                    // Use a QR code API to generate the image
                    $qr_code_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($payBySquareData);

                    error_log("Generated EPC QR Code Data: " . $payBySquareData);
                    error_log("Generated QR Code Image URL: " . $qr_code_image_url);

                }


                // Fetch order items
                $sql_items = "SELECT
                    oi.product_name, oi.quantity, oi.price_per_unit, oi.subtotal, p.image
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?";
                $stmt_items = $conn->prepare($sql_items);

                if (!$stmt_items) {
                    $error_info = $conn->errorInfo();
                    error_log("Failed to prepare order items statement: " . (isset($error_info[2]) ? $error_info[2] : 'Unknown error'));
                    $error_message = 'Nastala chyba pri príprave dotazu položiek objednávky. Kód: ' . (isset($error_info[1]) ? $error_info[1] : 'N/A');
                    throw new PDOException("Failed to prepare order items statement.");
                }

                $stmt_items->execute([$order_id]);
                $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

                // --- ADD DELIVERY COST AS A SEPARATE ITEM IN THE LIST ---
                if (isset($order_details['shipping_method_price']) && $order_details['shipping_method_price'] > 0) {
                    $order_items[] = [
                        'product_name' => htmlspecialchars($order_details['shipping_method_name']),
                        'quantity' => 1,
                        'price_per_unit' => $order_details['shipping_method_price'],
                        'subtotal' => $order_details['shipping_method_price'],
                        'image' => null, // Set image to null or an empty string for icons
                        'is_delivery' => true // Custom flag to identify this as the delivery item
                    ];
                }
                // --- END ADD DELIVERY COST ---

                error_log("Order items fetched and augmented for ID " . $order_id . ": " . print_r($order_items, true));

                // Clear the cart session after successful order view (optional, but good practice)
                if (isset($_SESSION['cart'])) {
                    unset($_SESSION['cart']);
                    error_log("Cart session cleared for order ID: " . $order_id);
                }

            } else {
                $error_message = 'Objednávka s daným ID nebola nájdená v databáze.';
                error_log("No order found with ID: " . $order_id);
            }

        } catch (PDOException $e) {
            error_log("Database error fetching order confirmation: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
            if (empty($error_message) || strpos($error_message, 'Nastala chyba pri načítavaní detailov objednávky') !== false) {
                 $error_message = 'Nastala chyba pri načítavaní detailov objednávky. Prosím, skúste to znova. (Detail: ' . $e->getMessage() . ')';
            }
        } finally {
            if ($conn) {
                $conn = null;
            }
        }
    }
} else {
    $error_message = 'Neplatné ID objednávky alebo chýbajúci parameter.';
    error_log("Invalid or missing order_id parameter. GET: " . print_r($_GET, true));
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
    <title>Potvrdenie Objednávky - LuxVlasy.sk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
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

    <main class="order-confirmation-container">
        <?php if ($order_details): ?>
            <div class="confirmation-header-section">
                <h1 class="confirmation-title"><i class="fas fa-check-circle confirmation-icon"></i> Ďakujeme Vám za Vašu objednávku!</h1>
                <p class="confirmation-message">Vaša objednávka bola úspešne prijatá a spracovaná.</p>
                <p class="order-id-display">Číslo Vašej objednávky: <strong>#<?php echo htmlspecialchars($order_details['id']); ?></strong></p>
                <p class="confirmation-instruction">Informácie o Vašej objednávke boli odoslané na Vašu e-mailovú adresu: <strong><?php echo htmlspecialchars($order_details['email']); ?></strong>. Tovar bude expedovaný hneď ako obdržíme platbu.</p>
                <?php if (!empty($order_details['order_date'])): ?>
                    <p class="order-date-display">Dátum objednávky: <strong><?php echo htmlspecialchars((new DateTime($order_details['order_date']))->format('d.m.Y H:i')); ?></strong></p>
                <?php endif; ?>
            </div>

            <div class="order-details-summary-section">
                <h2 class="section-title">Detaily Objednávky</h2>

                <div class="order-summary-grid">
                    <div class="summary-card shipping-details">
                        <h3><i class="fas fa-shipping-fast card-icon"></i> Doručovacie údaje</h3>
                        <p><strong>Meno:</strong> <?php echo htmlspecialchars($order_details['first_name'] . ' ' . $order_details['last_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order_details['email']); ?></p>
                        <p><strong>Telefón:</strong> <?php echo htmlspecialchars($order_details['phone'] ?: 'N/A'); ?></p>
                        <p><strong>Adresa:</strong> <?php echo htmlspecialchars($order_details['shipping_address_street'] . ' ' . $order_details['shipping_address_house_number'] . ', ' . $order_details['shipping_address_city'] . ', ' . $order_details['shipping_address_postal_code'] . ', ' . $order_details['shipping_address_country']); ?></p>
                    </div>
                    <div class="summary-card payment-shipping-details">
                        <h3><i class="fas fa-wallet card-icon"></i> Platba & Doprava</h3>
                        <p><strong>Doprava:</strong> <?php echo htmlspecialchars($order_details['shipping_method_name']); ?> (<?php echo number_format($order_details['shipping_method_price'], 2, ',', ' '); ?> €)</p>
                        <p><strong>Platba:</strong> <?php echo htmlspecialchars($order_details['payment_method_name']); ?></p>
                        <p><strong>Status objednávky:</strong> <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order_details['order_status'])); ?>"><?php echo htmlspecialchars($order_details['order_status']); ?></span></p>
                        <p><strong>Status platby:</strong> <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order_details['payment_status'])); ?>"><?php echo htmlspecialchars($order_details['payment_status']); ?></span></p>

                        <?php if ($order_details['payment_method_name'] === 'Bankový prevod s QR'): ?>
                            <div class="qr-code-section">
                                <h4>QR kód pre platbu</h4>
                                <?php if (!empty($qr_code_image_url)): ?>
                                    <img src="<?php echo htmlspecialchars($qr_code_image_url); ?>" alt="QR Code pre bankový prevod" class="qr-code-image">
                                    <p class="qr-info">Naskenujte QR kód pre rýchlu platbu.</p>
                                <?php else: ?>
                                    <p class="qr-info">QR kód sa nepodarilo vygenerovať. Uistite sa, že bankové údaje sú správne nastavené v databáze (IBAN, BIC/SWIFT a názov príjemcu).</p>
                                <?php endif; ?>

                                <?php if (!empty($order_details['payment_method_details'])):
                                    // Re-parse details to display for clarity
                                    $payment_details_parts_display = explode(';', $order_details['payment_method_details']);
                                    $iban_display = trim($payment_details_parts_display[0]);
                                    $bic_swift_display = isset($payment_details_parts_display[1]) ? trim($payment_details_parts_display[1]) : '';
                                    $recipient_name_display = isset($payment_details_parts_display[2]) ? trim($payment_details_parts_display[2]) : '';
                                    ?>
                                    <div class="bank-details">
                                        <h4>Bankové údaje</h4>
                                        <p>IBAN: <strong><?php echo nl2br(htmlspecialchars($iban_display)); ?></strong></p>
                                        <?php if (!empty($bic_swift_display)): ?>
                                            <p>BIC/SWIFT: <strong><?php echo htmlspecialchars($bic_swift_display); ?></strong></p>
                                        <?php endif; ?>
                                        <?php if (!empty($recipient_name_display)): ?>
                                            <p>Názov príjemcu: <strong><?php echo htmlspecialchars($recipient_name_display); ?></strong></p>
                                        <?php endif; ?>
                                        <p>Variabilný symbol: <strong><?php echo htmlspecialchars($order_details['id']); ?></strong></p>
                                        <p>Poznámka: <strong>Objednávka #<?php echo htmlspecialchars($order_details['id'] . ' - ' . $order_details['first_name'] . ' ' . $order_details['last_name']); ?></strong></p>
                                        <p>Čiastka: <strong><?php echo number_format($order_details['total_amount'], 2, ',', ' '); ?> €</strong></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <h3 class="section-title items-title"><i class="fas fa-boxes card-icon"></i> Položky objednávky</h3>
                <div class="order-items-list">
                    <?php if (!empty($order_items)): ?>
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item-card">
                                <div class="item-image-wrapper">
                                    <?php if (isset($item['is_delivery']) && $item['is_delivery']): ?>
                                        <i class="fas fa-truck delivery-icon-in-list"></i> <?php else: ?>
                                        <img src="<?php echo $basePath; ?>images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="item-info">
                                    <p class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                    <p class="item-quantity">Množstvo: <?php echo htmlspecialchars($item['quantity']); ?></p>
                                </div>
                                <p class="item-price"><?php echo number_format($item['price_per_unit'], 2, ',', ' '); ?> € / ks</p>
                                <p class="item-subtotal">Spolu: <?php echo number_format($item['subtotal'], 2, ',', ' '); ?> €</p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Žiadne položky pre túto objednávku.</p>
                    <?php endif; ?>
                </div>

                <div class="order-total-summary">
                    <p>Celková suma: <span class="total-amount"><?php echo number_format($order_details['total_amount'], 2, ',', ' '); ?> €</span></p>
                </div>
            </div>

            <div class="action-buttons confirmation-buttons">
                <a href="<?php echo $basePath; ?>index.php" class="button secondary-button">Prejsť na Domov</a>
            </div>

        <?php else: ?>
            <h1 class="confirmation-title error"><i class="fas fa-exclamation-triangle confirmation-icon"></i> Chyba</h1>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
            <p class="error-suggestion">Prosím, skúste to znova alebo <a href="<?php echo $basePath; ?>contact.php">kontaktujte podporu</a>.</p>
            <div class="action-buttons error-buttons">
                 <a href="<?php echo $basePath; ?>index.php" class="button primary-button">Prejsť na Domov</a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php echo date("Y"); ?> LuxVlasy.sk. Všetky práva vyhradené.</p>
    </footer>

    </body>
</html>