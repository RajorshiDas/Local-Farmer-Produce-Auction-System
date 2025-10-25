<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isFarmer()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$payment_id = intval($input['payment_id']);
$farmer_id = $_SESSION['user_id'];

try {
    // Verify payment belongs to this farmer
    $check_query = "SELECT payment_id FROM Payments WHERE payment_id = ? AND farmer_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$payment_id, $farmer_id]);
    
    if (!$check_stmt->fetch()) {
        throw new Exception('Payment not found or access denied');
    }
    
    // Update payment status
    $update_query = "UPDATE Payments SET status = 'Completed' WHERE payment_id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([$payment_id]);
    
    echo json_encode(['success' => true, 'message' => 'Payment marked as completed']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
