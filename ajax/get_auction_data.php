<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$auction_id = intval($_GET['auction_id']);

$query = "SELECT a.current_highest_bid, 
          (SELECT COUNT(*) FROM Bids WHERE auction_id = a.auction_id) as bid_count
          FROM Auctions a 
          WHERE a.auction_id = ?";

$stmt = $db->prepare($query);
$stmt->execute([$auction_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo json_encode([
        'success' => true,
        'current_bid' => number_format($result['current_highest_bid'], 2),
        'bid_count' => intval($result['bid_count'])
    ]);
} else {
    echo json_encode(['success' => false]);
}
?>
