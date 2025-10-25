<?php
/**
 * Auction Closing Cron Job
 * This script should be run every minute to check for expired auctions
 * Add this to your server's crontab: * * * * * php /path/to/farmer_auction/cron/close_auctions.php
 */

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Find auctions that should be closed
    $query = "SELECT a.*, p.product_name, p.farmer_id 
              FROM Auctions a 
              JOIN Products p ON a.product_id = p.product_id 
              WHERE a.status = 'Active' AND a.end_time <= NOW()";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $expired_auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($expired_auctions as $auction) {
        $db->beginTransaction();
        
        try {
            // Close the auction
            $close_query = "UPDATE Auctions SET status = 'Closed' WHERE auction_id = ?";
            $close_stmt = $db->prepare($close_query);
            $close_stmt->execute([$auction['auction_id']]);
            
            // The trigger will handle the rest (marking winning bid, creating payment, sending notification)
            
            $db->commit();
            echo "Closed auction {$auction['auction_id']} for product {$auction['product_name']}\n";
            
        } catch (Exception $e) {
            $db->rollBack();
            echo "Error closing auction {$auction['auction_id']}: " . $e->getMessage() . "\n";
        }
    }
    
    if (empty($expired_auctions)) {
        echo "No auctions to close at " . date('Y-m-d H:i:s') . "\n";
    }
    
} catch (Exception $e) {
    echo "Cron job error: " . $e->getMessage() . "\n";
}
?>
