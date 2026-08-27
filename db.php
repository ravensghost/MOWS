<?php
// db.php - Database connection configuration

// 1. InfinityFree Database Credentials
// Replace these with the exact details found in your InfinityFree cPanel under "MySQL Databases"
$host     = 'sql106.infinityfree.com'; // Example: sql123.epizy.com (Do not use 'localhost')
$dbname   = 'if0_42766016_epiz_xxxx_mows'; // Your InfinityFree database name
$username = 'if0_42766016';      // Your InfinityFree control panel username
$password = 'J1v9JSfAJm8w'; // Your vPanel/Account password

// 2. PDO Connection String (DSN)
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

// 3. PDO Options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch rows as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];

// 4. Establish the Connection
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // In a live environment, do not output the raw error message to the user.
    // Log the error to a file and show a generic message instead.
    error_log("Database Connection Failed: " . $e->getMessage());
    die("A database connection error occurred. Please try again later.");
}
?>