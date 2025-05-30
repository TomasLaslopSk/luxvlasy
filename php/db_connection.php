<?php
// db_connection.php
// Definujte environment specific credentials a cesty

if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === 'localhost:8888') { // Prispôsobte port 8888, ak je iný
    // LOKÁLNE PROSTREDIE (MAMP)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');      // Váš MAMP užívateľ
    define('DB_PASS', 'root');      // Vaše MAMP heslo
    define('DB_NAME', 'luxvlasy_shop'); // Názov vašej lokálnej DB

    // Základná cesta pre statické súbory (CSS, JS, obrázky) a PHP stránky
    define('BASE_URL_PATH', '/luxvlasy_mamp/'); // Na produkcii je to koreň webu (prázdny reťazec)
    
    // Cesta k adresáru s PHP API skriptami (consistent definition for local)
    // This defines the subdirectory name, which will be combined with BASE_URL_JS in JS
    define('API_PATH_DIR', 'php/'); // Example: Your PHP API files are in /luxvlasy_mamp/php/
} else {
    // PRODUKČNÉ PROSTREDIE (Websupport.sk)
    define('DB_HOST', '37.9.175.195'); // Napr. 'mysql.websupport.sk' alebo 'localhost'
    define('DB_USER', 'lasloptomas');   // Užívateľ DB z Websupport.sk
    define('DB_PASS', 'Root1234@'); // Heslo DB z Websupport.sk
    define('DB_NAME', 'luxvlasy_shop');             // Názov DB z Websupport.sk

    // Základná cesta pre statické súbory (CSS, JS, obrázky) a PHP stránky
    define('BASE_URL_PATH', ''); // Na produkcii je to koreň webu (prázdny reťazec)
    
    // Cesta k adresáru s PHP API skriptami (consistent definition for production)
    define('API_PATH_DIR', 'php/'); // Example: Your PHP API files are in /php/
}

function getDbConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("Error connecting to the database."); // Túto správu neuvidí užívateľ, ak už je to ošetrené v get_products.php
    }
}

?>