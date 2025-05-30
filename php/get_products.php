<?php
// php/get_products.php
header('Content-Type: application/json');

// Include your database connection file
require_once 'db_connection.php';

$conn = getDbConnection(); // Get the PDO database connection

$allProducts = []; // Initialize an empty array to store products from the database

try {
    // Prepare the SQL query to fetch all products from the 'products' table
    // Ensure the column names exactly match your database table's column names
    $stmt = $conn->prepare("SELECT
                                id,
                                name,
                                short_description,
                                long_description,
                                price,
                                discount,
                                image,
                                hover_image,
                                brand,
                                product_category,
                                stock,
                                is_active -- Include the new is_active column
                            FROM products
                            WHERE is_active = 1"); // Filter to display only active products
    $stmt->execute();
    $dbProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Populate $allProducts with data fetched from the database
    foreach ($dbProducts as $product) {
        $allProducts[] = [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'short_description' => $product['short_description'],
            'long_description' => $product['long_description'],
            'price' => (string)$product['price'], // Convert DECIMAL to string for consistent output
            'discount' => (string)$product['discount'], // Convert DECIMAL to string
            'image' => $product['image'],
            'hover_image' => $product['hover_image'],
            'brand' => $product['brand'],
            'product_category' => $product['product_category']
            // 'stock' is fetched but not directly sent in this product array (used internally for checks)
        ];
    }

} catch (PDOException $e) {
    // Log the error for debugging (NEVER expose $e->getMessage() directly to users in production)
    error_log("Error fetching products from DB: " . $e->getMessage());
    // Return an error message to the frontend
    echo json_encode(['error' => 'Could not fetch products. Database error.']);
    exit;
}

// --- Filtering Logic ---
$filteredProducts = [];

// Determine the filter type and value(s)
$filterType = $_GET['filter_type'] ?? 'all';
$filterValue = $_GET['filter_value'] ?? '';

// Priority given to 'category[]' if present for multi-category filtering
// This ensures that if category[]= parameters are in the URL, they are processed
if (isset($_GET['category']) && is_array($_GET['category'])) {
    $filterType = 'category'; // Force filterType to 'category' if array of categories is sent
    $filterValue = $_GET['category']; // Assign the array of categories
} else if (isset($_GET['category']) && !is_array($_GET['category'])) {
    // This handles a single 'category' parameter without array notation
    // e.g., index.php?category=Šampóny (without filter_type)
    $filterType = 'category';
    $filterValue = $_GET['category'];
}


if ($filterType === 'all') {
    $filteredProducts = $allProducts;
} elseif ($filterType === 'discount') {
    foreach ($allProducts as $product) {
        // Check if the discount is greater than 0 (or a specific threshold like 0.01)
        if (isset($product['discount']) && (float)$product['discount'] > 0) {
            $filteredProducts[] = $product;
        }
    }
} elseif ($filterType === 'brand') {
    foreach ($allProducts as $product) {
        if (isset($product['brand']) && strcasecmp($product['brand'], $filterValue) === 0) {
            $filteredProducts[] = $product;
        }
    }
} elseif ($filterType === 'category') {
    // Handle multi-category filtering (if $filterValue is an array)
    if (is_array($filterValue) && !empty($filterValue)) {
        // Convert all filter values to lowercase for case-insensitive comparison
        // Using mb_strtolower for multi-byte characters
        $filterValuesLower = array_map('mb_strtolower', $filterValue);
        foreach ($allProducts as $product) {
            if (isset($product['product_category']) && in_array(mb_strtolower($product['product_category']), $filterValuesLower)) {
                $filteredProducts[] = $product;
            }
        }
    } else if (is_string($filterValue) && $filterValue !== '') { // Handle single category filtering (original behavior)
        foreach ($allProducts as $product) {
            if (isset($product['product_category']) && strcasecmp($product['product_category'], $filterValue) === 0) {
                $filteredProducts[] = $product;
            }
        }
    } else {
        // If 'category' filterType is set, but no valid category value(s) are provided,
        // it means nothing should be filtered, so return no products by category.
        // Or, you could return all products if you prefer a 'no selection' to mean 'all in category group'.
        // For now, we'll make it return no products in this specific filter type if nothing is selected.
        $filteredProducts = [];
    }
}

echo json_encode($filteredProducts);
?>