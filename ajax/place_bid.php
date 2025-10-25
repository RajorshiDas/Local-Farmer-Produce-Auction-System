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

$auction_id = intval($_POST['auction_id']);
$bid_amount = floatval($_POST['bid_amount']);
$buyer_id = $_SESSION['user_id'];

try {
    $db->beginTransaction();
    
    // Get auction details
    $auction_query = "SELECT a.*, p.product_name FROM Auctions a JOIN Products p ON a.product_id = p.product_id WHERE a.auction_id = ? AND a.status = 'Active'";
    $auction_stmt = $db->prepare($auction_query);
    $auction_stmt->execute([$auction_id]);
    $auction = $auction_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$auction) {
        throw new Exception('Auction not found or not active');
    }
    
    if ($bid_amount <= $auction['current_highest_bid']) {
        throw new Exception('Bid must be higher than current highest bid');
    }
    
    // Insert bid
    $bid_query = "INSERT INTO Bids (auction_id, buyer_id, bid_amount) VALUES (?, ?, ?)";
    $bid_stmt = $db->prepare($bid_query);
    $bid_stmt->execute([$auction_id, $buyer_id, $bid_amount]);
    
    // Update auction with new highest bid
    $update_query = "UPDATE Auctions SET current_highest_bid = ? WHERE auction_id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([$bid_amount, $auction_id]);
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Bid placed successfully']);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
