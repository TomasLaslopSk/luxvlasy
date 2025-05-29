<?php
// php/get_shipping_payment_options.php
error_reporting(E_ALL);        // Enable all error reporting for development
ini_set('display_errors', 1);  // Display errors on the screen for development
header('Content-Type: application/json');

require_once 'db_connection.php'; // Include your database connection

$conn = getDbConnection(); // Get the PDO connection

$shippingMethods = [];
$paymentMethods = [];

try {
    // Fetch shipping methods from the database
    // REMOVED 'description' from the SELECT list as it's unnecessary and not found
    $stmt_shipping = $conn->prepare("SELECT id, name, price FROM shipping_methods ORDER BY price ASC");
    $stmt_shipping->execute();
    $shippingMethods = $stmt_shipping->fetchAll(PDO::FETCH_ASSOC);

    // Fetch payment methods from the database
    // REMOVED 'description' from the SELECT list as it's unnecessary and not found
    $stmt_payment = $conn->prepare("SELECT id, name FROM payment_methods ORDER BY id ASC");
    $stmt_payment->execute();
    $paymentMethods = $stmt_payment->fetchAll(PDO::FETCH_ASSOC);

    // Return the fetched data as JSON
    echo json_encode([
        'success' => true,
        'shipping_methods' => $shippingMethods,
        'payment_methods' => $paymentMethods
    ]);

} catch (PDOException $e) {
    // Log the error for debugging purposes
    error_log("Error fetching shipping/payment options from DB: " . $e->getMessage());
    // Return an error response to the frontend
    echo json_encode(['success' => false, 'message' => 'Chyba pri načítaní možností dopravy a platby.']);
}
?>