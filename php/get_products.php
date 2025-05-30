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

// --- The filtering logic remains the same as your original file ---
// This part filters the $allProducts array based on GET parameters
$filterType = $_GET['filter_type'] ?? 'all';
$filterValue = $_GET['filter_value'] ?? '';

$filteredProducts = [];

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
    foreach ($allProducts as $product) {
        if (isset($product['product_category']) && strcasecmp($product['product_category'], $filterValue) === 0) {
            $filteredProducts[] = $product;
        }
    }
}

echo json_encode($filteredProducts);
?>