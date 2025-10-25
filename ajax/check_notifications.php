<?php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !isBuyer()) {
    echo json_encode(['count' => 0]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$buyer_id = $_SESSION['user_id'];

$query = "SELECT COUNT(*) as count FROM Notifications WHERE buyer_id = ? AND is_read = FALSE";
$stmt = $db->prepare($query);
$stmt->execute([$buyer_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['count' => intval($result['count'])]);
?>
