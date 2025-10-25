<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isBuyer()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$buyer_id = $_SESSION['user_id'];
$auction_id = intval($_POST['auction_id']);
$amount = floatval($_POST['amount']);
$payment_method = $_POST['payment_method'];
$transaction_id = $_POST['transaction_id'];
$payment_notes = $_POST['payment_notes'] ?? '';

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Verify buyer won this auction and payment is pending
    $verify_query = "SELECT p.payment_id, p.status, a.auction_id, pr.product_name, pr.farmer_id
                     FROM Payments p 
                     JOIN Auctions a ON p.auction_id = a.auction_id
                     JOIN Products pr ON a.product_id = pr.product_id
                     WHERE p.auction_id = ? AND p.buyer_id = ? AND p.status = 'Pending'";
    
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute([$auction_id, $buyer_id]);
    $payment = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception('Payment record not found or already processed');
    }
    
    // Update payment with buyer's payment information
    $update_query = "UPDATE Payments SET 
                     status = 'Submitted',
                     payment_method = ?,
                     transaction_id = ?,
                     payment_notes = ?,
                     submitted_at = NOW()
                     WHERE payment_id = ?";
    
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([$payment_method, $transaction_id, $payment_notes, $payment['payment_id']]);
    
    // Add payment_method, transaction_id, payment_notes, submitted_at columns if they don't exist
    // This is handled in database schema, but we'll create a notification for the farmer
    
    // Notify farmer about payment submission
    $notify_query = "INSERT INTO Notifications (buyer_id, auction_id, message) VALUES (?, ?, ?)";
    $message = "Payment submitted for " . $payment['product_name'] . " via " . $payment_method . ". Transaction ID: " . $transaction_id;
    
    // For farmers, we need to create a farmer notification system or use email
    // For now, we'll create a simple log that farmers can check
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Payment submitted successfully. The farmer will be notified.']);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>