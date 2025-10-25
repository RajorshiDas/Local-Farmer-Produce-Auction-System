<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isFarmer()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$auction_id = intval($input['auction_id']);
$farmer_id = $_SESSION['user_id'];

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Verify auction belongs to this farmer and is active
    $verify_query = "SELECT a.*, p.farmer_id, p.product_name 
                     FROM Auctions a 
                     JOIN Products p ON a.product_id = p.product_id 
                     WHERE a.auction_id = ? AND p.farmer_id = ? AND a.status = 'Active'";
    
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute([$auction_id, $farmer_id]);
    $auction = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$auction) {
        throw new Exception('Auction not found or cannot be closed');
    }
    
    // Check if there are any bids
    $bid_check = "SELECT COUNT(*) as bid_count FROM Bids WHERE auction_id = ?";
    $bid_stmt = $db->prepare($bid_check);
    $bid_stmt->execute([$auction_id]);
    $bid_count = $bid_stmt->fetch(PDO::FETCH_ASSOC)['bid_count'];
    
    if ($bid_count == 0) {
        // No bids - just close the auction
        $close_query = "UPDATE Auctions SET status = 'Closed' WHERE auction_id = ?";
        $close_stmt = $db->prepare($close_query);
        $close_stmt->execute([$auction_id]);
        
        $message = 'Auction closed successfully (no bids received)';
    } else {
        // Has bids - close auction and trigger winner determination
        $close_query = "UPDATE Auctions SET status = 'Closed' WHERE auction_id = ?";
        $close_stmt = $db->prepare($close_query);
        $close_stmt->execute([$auction_id]);
        
        // The database trigger will automatically handle winner determination
        $message = 'Auction closed successfully. Winner has been notified.';
    }
    
    // Log the manual closure
    $log_query = "INSERT INTO Notifications (buyer_id, auction_id, message) 
                  SELECT DISTINCT b.buyer_id, ?, 
                         CONCAT('Auction for ', ?, ' was closed early by the farmer.') 
                  FROM Bids b WHERE b.auction_id = ?";
    $log_stmt = $db->prepare($log_query);
    $log_stmt->execute([$auction_id, $auction['product_name'], $auction_id]);
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>