<?php
// php/process_order.php

session_start(); // Start the session to access $_SESSION['cart']

header('Content-Type: application/json'); // Set header for JSON response (always active in production)

require_once 'db_connection.php'; // Include your database connection file

$response = ['success' => false, 'message' => '']; // Initialize response array

// 1. Validate Request Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    die(); // Use die() to ensure script terminates after sending JSON
}

// 2. Retrieve Cart Items from Session
$cart_items = $_SESSION['cart'] ?? [];

if (empty($cart_items)) {
    $response['message'] = 'Váš košík je prázdny. Prosím, pridajte produkty do košíka.';
    echo json_encode($response);
    die(); // Use die()
}

// 3. Retrieve and Sanitize Form Data (from Step 3 - Personal & Shipping Info)
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$phone = trim($_POST['phone'] ?? '');

$shipping_address_street = trim($_POST['street'] ?? '');
$shipping_address_house_number = trim($_POST['house_number'] ?? '');
$shipping_address_city = trim($_POST['city'] ?? '');
$shipping_address_postal_code = trim($_POST['postal_code'] ?? '');
$shipping_address_country = trim($_POST['address_country'] ?? ''); // This comes from dropdown

// 4. Retrieve Shipping and Payment Methods (from Step 2 - Shipping & Payment)
$shipping_method_id = filter_input(INPUT_POST, 'shipping_method_id', FILTER_VALIDATE_INT);
$payment_method_id = filter_input(INPUT_POST, 'payment_method_id', FILTER_VALIDATE_INT);

// Basic server-side validation for required fields
if (
    empty($first_name) || empty($last_name) || empty($shipping_address_street) || empty($shipping_address_house_number) ||
    empty($shipping_address_city) || empty($shipping_address_postal_code) || empty($shipping_address_country)
) {
    $response['message'] = 'Chýbajúce alebo neplatné údaje vo formulári. Skontrolujte všetky polia adresy a mena.';
    echo json_encode($response);
    die();
}
if (!$email) {
    $response['message'] = 'Neplatný formát emailovej adresy.';
    echo json_encode($response);
    die();
}
if (!is_numeric($shipping_method_id) || $shipping_method_id <= 0) {
    $response['message'] = 'Neplatná metóda dopravy.';
    echo json_encode($response);
    die();
}
if (!is_numeric($payment_method_id) || $payment_method_id <= 0) {
    $response['message'] = 'Neplatná metóda platby.';
    echo json_encode($response);
    die();
}

$conn = getDbConnection(); // Establish database connection

try {
    // Start a transaction for atomicity: either all database operations succeed, or none do.
    $conn->beginTransaction();

    $total_order_amount = 0;
    $product_details_for_items = []; // To store actual product details for order_items insertion

    // Validate Cart Items and Calculate Total Amount from DB prices
    foreach ($cart_items as $item) {
        if (!is_array($item)) {
            error_log("Malformed item found in cart session during order processing."); // Log but don't expose
            throw new Exception("Interná chyba košíka: Položka nie je platná.");
        }

        $product_id = (int)($item['product_id'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);

        if ($product_id <= 0 || $quantity <= 0) {
            throw new Exception("Neplatné ID produktu alebo množstvo v košíku.");
        }

        // Fetch actual product price and stock from the database
        $stmt_product = $conn->prepare("SELECT name, price, stock FROM products WHERE id = ?");
        $stmt_product->execute([$product_id]);
        $product_data = $stmt_product->fetch(PDO::FETCH_ASSOC);

        if (!$product_data) {
            throw new Exception("Produkt s ID $product_id nebol nájdený.");
        }

        if ($product_data['stock'] < $quantity) {
            throw new Exception("Nedostatočné zásoby pre produkt: " . $product_data['name'] . ". Dostupných: " . $product_data['stock']);
        }

        $item_price_per_unit = (float)$product_data['price'];
        $item_subtotal = $item_price_per_unit * $quantity;
        $total_order_amount += $item_subtotal;

        $product_details_for_items[] = [
            'product_id' => $product_id,
            'product_name' => $product_data['name'],
            'quantity' => $quantity,
            'price_per_unit' => $item_price_per_unit,
            'subtotal' => $item_subtotal,
            'current_stock' => $product_data['stock']
        ];
    }

    // Fetch shipping method price
    $stmt_shipping = $conn->prepare("SELECT price FROM shipping_methods WHERE id = ?");
    $stmt_shipping->execute([$shipping_method_id]);
    $shipping_price_data = $stmt_shipping->fetch(PDO::FETCH_ASSOC);

    if (!$shipping_price_data) {
        throw new Exception("Vybraná metóda dopravy nebola nájdená.");
    }
    $total_order_amount += (float)$shipping_price_data['price'];


    // 5. Insert into `orders` table
    $stmt_order = $conn->prepare("INSERT INTO `orders` (
        `first_name`, `last_name`, `email`, `phone`,
        `shipping_address_street`, `shipping_address_house_number`, `shipping_address_city`,
        `shipping_address_postal_code`, `shipping_address_country`,
        `shipping_method_id`, `payment_method_id`, `total_amount`,
        `order_status`, `payment_status`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt_order->execute([
        $first_name, $last_name, $email, $phone,
        $shipping_address_street, $shipping_address_house_number, $shipping_address_city,
        $shipping_address_postal_code, $shipping_address_country,
        $shipping_method_id, $payment_method_id, $total_order_amount,
        'Pending', 'Awaiting Payment' // Default statuses
    ]);

    $order_id = $conn->lastInsertId(); // Get the ID of the newly inserted order

    // 6. Insert into `order_items` and Update Product Stock
    $stmt_order_item = $conn->prepare("INSERT INTO `order_items` (
        `order_id`, `product_id`, `product_name`, `quantity`, `price_per_unit`, `subtotal`
    ) VALUES (?, ?, ?, ?, ?, ?)");

    $stmt_update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($product_details_for_items as $item_details) {
        $stmt_order_item->execute([
            $order_id,
            $item_details['product_id'],
            $item_details['product_name'],
            $item_details['quantity'],
            $item_details['price_per_unit'],
            $item_details['subtotal']
        ]);

        // Update product stock
        $stmt_update_stock->execute([$item_details['quantity'], $item_details['product_id']]);
    }

    // Commit the transaction if all operations were successful
    $conn->commit();

    // 7. Clear the cart from session after successful order
    unset($_SESSION['cart']);

    $response['success'] = true;
    $response['message'] = 'Objednávka (ID: ' . $order_id . ') úspešne spracovaná!';
    $response['order_id'] = $order_id; // Pass order ID back to frontend

} catch (Exception $e) { // Catch both PDOException and custom Exceptions
    // Ensure transaction is rolled back on any error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Order processing error: " . $e->getMessage()); // Log detailed error for server-side
    $response['message'] = 'Nastala chyba pri spracovaní objednávky. Skúste to prosím znova.'; // Generic message for user
}

// Final response for the client (always active in production)
echo json_encode($response);
die();
?>