<?php
// php/db_connection.php
function getDbConnection() {
    $host = 'localhost'; // MAMP's database host is typically localhost
    $db = 'luxvlasy_shop'; // This must match the name you created in phpMyAdmin
    $user = 'root'; // MAMP's default MySQL username
    $pass = 'root'; // MAMP's default MySQL password (often 'root' or empty)
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Fetch results as associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                // Use native prepared statements for security
    ];
    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage()); // Log the error
        die('Database connection failed: ' . $e->getMessage()); // Show the error for local debugging
    }
}
?>