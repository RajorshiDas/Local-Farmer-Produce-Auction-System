<?php
/**
 * Setup script for Farmer Auction System
 * Run this once to set up the database and create necessary directories
 */

require_once 'config/database.php';

echo "<h1>Farmer Auction System Setup</h1>";

// Create uploads directory
$uploads_dir = 'uploads/products';
if (!file_exists($uploads_dir)) {
    if (mkdir($uploads_dir, 0777, true)) {
        echo "<p style='color: green;'>✓ Created uploads directory</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to create uploads directory</p>";
    }
} else {
    echo "<p style='color: green;'>✓ Uploads directory already exists</p>";
}

// Test database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if tables exist
    $tables_query = "SHOW TABLES";
    $tables_stmt = $db->prepare($tables_query);
    $tables_stmt->execute();
    $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_tables = ['Farmers', 'Buyers', 'Products', 'ProductImages', 'Auctions', 'Bids', 'Payments', 'Reviews', 'Wishlist', 'Notifications'];
    $missing_tables = array_diff($required_tables, $tables);
    
    if (empty($missing_tables)) {
        echo "<p style='color: green;'>✓ All required tables exist</p>";
    } else {
        echo "<p style='color: red;'>✗ Missing tables: " . implode(', ', $missing_tables) . "</p>";
        echo "<p>Please run the database.sql file first.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Setup Complete!</h2>";
echo "<p>Your Farmer Auction System is ready to use.</p>";
echo "<p><a href='index.php'>Go to Homepage</a></p>";
?>
