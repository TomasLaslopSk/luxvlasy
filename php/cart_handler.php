<?php
// php/cart_handler.php
error_reporting(E_ALL);        // Enable all error reporting for development
ini_set('display_errors', 1);  // Display errors on the screen for development
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

// Ensure $_SESSION['cart'] is always an array. This is good practice.
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Determine the action from POST or GET parameters
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $productId = $_POST['product_id'] ?? null;
        $quantity = (int)($_POST['quantity'] ?? 1);
        $productName = $_POST['product_name'] ?? 'Neznámy produkt';
        $productPrice = (float)($_POST['product_price'] ?? 0.00);
        $productImage = $_POST['product_image'] ?? 'placeholder.jpg';

        if ($productId && $quantity > 0) {
            $found = false;
            foreach ($_SESSION['cart'] as $key => $item) {
                // IMPORTANT FIX: Ensure $item is an array before trying to access its offsets.
                // This prevents "TypeError: Cannot access offset of type string on string"
                if (is_array($item) && isset($item['product_id']) && $item['product_id'] == $productId) {
                    $_SESSION['cart'][$key]['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $_SESSION['cart'][] = [
                    'product_id' => $productId,
                    'name' => $productName,
                    'quantity' => $quantity,
                    'price' => $productPrice,
                    'image' => $productImage
                ];
            }
            $response['success'] = true;
            $response['message'] = 'Produkt pridaný do košíka.';
        } else {
            $response['message'] = 'Neplatné ID produktu alebo množstvo.';
        }
        break;

    case 'remove':
        $productId = $_POST['product_id'] ?? null;
        if ($productId) {
            foreach ($_SESSION['cart'] as $key => $item) {
                // IMPORTANT FIX: Ensure $item is an array before trying to access its offsets.
                if (is_array($item) && isset($item['product_id']) && $item['product_id'] == $productId) {
                    unset($_SESSION['cart'][$key]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array after removal
                    $response['success'] = true;
                    $response['message'] = 'Produkt odstránený z košíka.';
                    break;
                }
            }
            if (!$response['success']) {
                $response['message'] = 'Produkt nebol nájdený v košíku.';
            }
        } else {
            $response['message'] = 'Neplatné ID produktu.';
        }
        break;

    case 'update_quantity':
        $productId = $_POST['product_id'] ?? null;
        $newQuantity = (int)($_POST['quantity'] ?? 0);

        if ($productId) {
            $found = false;
            foreach ($_SESSION['cart'] as $key => $item) {
                // IMPORTANT FIX: Ensure $item is an array before trying to access its offsets.
                if (is_array($item) && isset($item['product_id']) && $item['product_id'] == $productId) {
                    if ($newQuantity > 0) {
                        $_SESSION['cart'][$key]['quantity'] = $newQuantity;
                        $response['success'] = true;
                        $response['message'] = 'Množstvo aktualizované.';
                    } else {
                        // If new quantity is 0 or less, remove the item
                        unset($_SESSION['cart'][$key]);
                        $_SESSION['cart'] = array_values($_SESSION['cart']);
                        $response['success'] = true;
                        $response['message'] = 'Produkt odstránený z košíka.';
                    }
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $response['message'] = 'Produkt nebol nájdený v košíku.';
            }
        } else {
            $response['message'] = 'Neplatné ID produktu.';
        }
        break;

    case 'get_count':
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            // IMPORTANT FIX: Ensure $item is an array before trying to access its offsets.
            if (is_array($item)) {
                $count += (int)($item['quantity'] ?? 0);
            }
        }
        $response['success'] = true;
        $response['count'] = $count;
        $response['message'] = 'Počet položiek v košíku načítaný.';
        break;

    case 'get_items':
        $cartItems = [];
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            // IMPORTANT FIX: Ensure $item is an array before trying to access its offsets.
            // If it's not an array, skip it to prevent TypeErrors and potentially clean up corrupted session data.
            if (!is_array($item)) {
                // Optionally: unset($_SESSION['cart'][$key]); // Uncomment to remove corrupted entries from session
                continue;
            }

            // Safely retrieve product details, providing default values if keys are missing
            $productId = $item['product_id'] ?? null;
            $name = $item['name'] ?? 'Neznámy produkt';
            $quantity = (int)($item['quantity'] ?? 0);
            $price = (float)($item['price'] ?? 0.00);
            $image = $item['image'] ?? 'placeholder.jpg';

            // Only add valid items to the response
            if ($productId !== null && $quantity > 0) {
                $subtotal = $quantity * $price;
                $total += $subtotal;
                $cartItems[] = [
                    'product_id' => $productId,
                    'name' => $name,
                    'quantity' => $quantity,
                    'price' => $price,
                    'image' => $image,
                    'subtotal' => $subtotal
                ];
            }
        }
        $response['success'] = true;
        $response['items'] = $cartItems;
        $response['total'] = (float)$total; // Ensure total is a float
        $response['message'] = 'Položky košíka načítané.';
        break;

    default:
        // 'Invalid request' message is set at the top for unknown actions
        break;
}

// Always include the current cart count in the response for convenience
$currentCartCount = 0;
foreach ($_SESSION['cart'] as $item) {
    // IMPORTANT FIX: Ensure $item is an array before trying to access its offsets.
    if (is_array($item)) {
        $currentCartCount += (int)($item['quantity'] ?? 0);
    }
}
$response['newCartCount'] = $currentCartCount;

echo json_encode($response);
exit; // It's good practice to exit after sending JSON to prevent accidental further output
?>