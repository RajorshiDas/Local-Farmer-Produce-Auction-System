<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'Notifications - Buyer Dashboard';
requireLogin();

if (!isBuyer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$buyer_id = $_SESSION['user_id'];

// Mark all notifications as read
$mark_read_query = "UPDATE Notifications SET is_read = TRUE WHERE buyer_id = ?";
$mark_read_stmt = $db->prepare($mark_read_query);
$mark_read_stmt->execute([$buyer_id]);

// Get all notifications
$query = "SELECT n.*, a.auction_id, p.product_name 
          FROM Notifications n 
          JOIN Auctions a ON n.auction_id = a.auction_id 
          JOIN Products p ON a.product_id = p.product_id 
          WHERE n.buyer_id = ? 
          ORDER BY n.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([$buyer_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-bell me-2"></i>Notifications</h1>
                <a href="dashboard.php" class="btn btn-outline-success">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-bell fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Notifications</h4>
                        <p class="text-muted">You don't have any notifications yet.</p>
                        <a href="../auctions.php" class="btn btn-success btn-lg">
                            <i class="fas fa-search me-2"></i>Browse Auctions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="d-flex align-items-start mb-3 p-3 border rounded">
                                <div class="flex-shrink-0 me-3">
                                    <i class="fas fa-bell fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></h6>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-box me-1"></i><?php echo htmlspecialchars($notification['product_name']); ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('M j, Y H:i', strtotime($notification['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="flex-shrink-0">
                                    <a href="../auction_details.php?id=<?php echo $notification['auction_id']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-eye me-1"></i>View
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
