<?php
/**
 * Update sample account passwords
 * Run this once to set real passwords for testing
 */

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Update farmer passwords
    $farmer_passwords = [
        'john@example.com' => 'farmer123',
        'jane@example.com' => 'farmer123'
    ];
    
    foreach ($farmer_passwords as $email => $password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE Farmers SET password = ? WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$hashed_password, $email]);
        echo "Updated password for farmer: $email<br>";
    }
    
    // Update buyer passwords
    $buyer_passwords = [
        'alice@example.com' => 'buyer123',
        'bob@example.com' => 'buyer123'
    ];
    
    foreach ($buyer_passwords as $email => $password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE Buyers SET password = ? WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$hashed_password, $email]);
        echo "Updated password for buyer: $email<br>";
    }
    
    echo "<br><strong>Sample Account Passwords:</strong><br>";
    echo "<strong>Farmers:</strong><br>";
    echo "john@example.com / farmer123<br>";
    echo "jane@example.com / farmer123<br><br>";
    
    echo "<strong>Buyers:</strong><br>";
    echo "alice@example.com / buyer123<br>";
    echo "bob@example.com / buyer123<br><br>";
    
    echo "<a href='login.php'>Go to Login Page</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
