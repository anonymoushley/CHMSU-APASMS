<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Test database connection
require_once '../config/database.php';

try {
    // Test connection
    $pdo->query("SELECT 1");
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if tables exist
    $tables = ['personal_info', 'socio_demographic'];
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
            
            // Show table structure
            echo "<h3>Structure of $table:</h3>";
            $columns = $pdo->query("SHOW COLUMNS FROM $table");
            echo "<pre>";
            while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
                print_r($column);
            }
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' does not exist</p>";
        }
    }
    
    // Check for recent errors in the error log
    echo "<h2>Recent Error Log</h2>";
    $error_log = file_get_contents('../error_log.txt');
    if ($error_log) {
        echo "<pre>" . htmlspecialchars($error_log) . "</pre>";
    } else {
        echo "<p>No error log found</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}
?> 