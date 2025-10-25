<?php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isBuyer()) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['product_id']);
$buyer_id = $_SESSION['user_id'];

try {
    // Check if item is already in wishlist
    $check_query = "SELECT wishlist_id FROM Wishlist WHERE buyer_id = ? AND product_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$buyer_id, $product_id]);
    $existing = $check_stmt->fetch();
    
    if ($existing) {
        // Remove from wishlist
        $delete_query = "DELETE FROM Wishlist WHERE buyer_id = ? AND product_id = ?";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([$buyer_id, $product_id]);
        
        echo json_encode(['success' => true, 'in_wishlist' => false, 'message' => 'Removed from wishlist']);
    } else {
        // Add to wishlist
        $insert_query = "INSERT INTO Wishlist (buyer_id, product_id) VALUES (?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([$buyer_id, $product_id]);
        
        echo json_encode(['success' => true, 'in_wishlist' => true, 'message' => 'Added to wishlist']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
