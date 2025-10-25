<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'Buyer Dashboard - Farmer Auction System';
requireLogin();

if (!isBuyer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$buyer_id = $_SESSION['user_id'];

// Get buyer statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM Bids WHERE buyer_id = ?) as total_bids,
    (SELECT COUNT(*) FROM Bids b JOIN Auctions a ON b.auction_id = a.auction_id WHERE b.buyer_id = ? AND b.is_winning_bid = TRUE AND a.status = 'Closed') as won_auctions,
    (SELECT COUNT(*) FROM Wishlist WHERE buyer_id = ?) as wishlist_items,
    (SELECT COUNT(*) FROM Notifications WHERE buyer_id = ? AND is_read = FALSE) as unread_notifications";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$buyer_id, $buyer_id, $buyer_id, $buyer_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent bids
$bids_query = "SELECT b.*, a.auction_id, a.status as auction_status, a.end_time, a.current_highest_bid,
               p.product_name, p.description,
               (SELECT image_path FROM ProductImages WHERE product_id = p.product_id LIMIT 1) as main_image
               FROM Bids b 
               JOIN Auctions a ON b.auction_id = a.auction_id 
               JOIN Products p ON a.product_id = p.product_id 
               WHERE b.buyer_id = ? 
               ORDER BY b.bid_time DESC 
               LIMIT 5";

$bids_stmt = $db->prepare($bids_query);
$bids_stmt->execute([$buyer_id]);
$recent_bids = $bids_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get won auctions
$won_query = "SELECT a.*, p.product_name, p.description, f.name as farmer_name,
              (SELECT image_path FROM ProductImages WHERE product_id = p.product_id LIMIT 1) as main_image
              FROM Auctions a 
              JOIN Products p ON a.product_id = p.product_id 
              JOIN Farmers f ON p.farmer_id = f.farmer_id 
              JOIN Bids b ON a.auction_id = b.auction_id 
              WHERE b.buyer_id = ? AND b.is_winning_bid = TRUE AND a.status = 'Closed'
              ORDER BY a.end_time DESC 
              LIMIT 5";

$won_stmt = $db->prepare($won_query);
$won_stmt->execute([$buyer_id]);
$won_auctions = $won_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-shopping-cart me-2"></i>Buyer Dashboard
                <span class="text-muted fs-6">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
            </h1>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-body text-center">
                    <i class="fas fa-gavel fa-2x mb-2"></i>
                    <div class="stats-number"><?php echo $stats['total_bids']; ?></div>
                    <div>Total Bids</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-body text-center">
                    <i class="fas fa-trophy fa-2x mb-2"></i>
                    <div class="stats-number"><?php echo $stats['won_auctions']; ?></div>
                    <div>Won Auctions</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-body text-center">
                    <i class="fas fa-heart fa-2x mb-2"></i>
                    <div class="stats-number"><?php echo $stats['wishlist_items']; ?></div>
                    <div>Wishlist Items</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-body text-center position-relative">
                    <i class="fas fa-bell fa-2x mb-2"></i>
                    <div class="stats-number"><?php echo $stats['unread_notifications']; ?></div>
                    <div>Notifications</div>
                    <?php if ($stats['unread_notifications'] > 0): ?>
                        <span class="notification-badge"><?php echo $stats['unread_notifications']; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="../auctions.php" class="btn btn-success w-100">
                                <i class="fas fa-search me-2"></i>Browse Auctions
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="wishlist.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-heart me-2"></i>My Wishlist
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="notifications.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-bell me-2"></i>Notifications
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="purchases.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-shopping-bag me-2"></i>My Purchases
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Bids -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-gavel me-2"></i>Recent Bids</h5>
                    <a href="bids.php" class="btn btn-sm btn-outline-success">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_bids)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-gavel fa-2x mb-2"></i>
                            <p>No bids yet. <a href="../auctions.php">Start bidding</a></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_bids as $bid): ?>
                            <div class="d-flex align-items-center mb-3 p-2 border rounded">
                                <div class="flex-shrink-0">
                                    <?php if ($bid['main_image']): ?>
                                        <img src="/farmer_auction/<?php echo ltrim($bid['main_image'], '/'); ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;" alt="Product">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($bid['product_name']); ?></h6>
                                    <small class="text-muted">
                                        <span class="badge bg-<?php echo $bid['auction_status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                            <?php echo $bid['auction_status']; ?>
                                        </span>
                                        <span class="ms-2">Your bid: ৳<?php echo number_format($bid['bid_amount'], 2); ?></span>
                                        <?php if ($bid['is_winning_bid']): ?>
                                            <span class="badge bg-success ms-2">Winning</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Won Auctions -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Won Auctions</h5>
                    <a href="purchases.php" class="btn btn-sm btn-outline-success">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($won_auctions)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-trophy fa-2x mb-2"></i>
                            <p>No won auctions yet. <a href="../auctions.php">Start bidding</a></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($won_auctions as $auction): ?>
                            <div class="d-flex align-items-center mb-3 p-2 border rounded">
                                <div class="flex-shrink-0">
                                    <?php if ($auction['main_image']): ?>
                                        <img src="/farmer_auction/<?php echo ltrim($auction['main_image'], '/'); ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;" alt="Product">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($auction['product_name']); ?></h6>
                                    <small class="text-muted">
                                        <span class="badge bg-success">Won</span>
                                        <span class="ms-2">Final price: ৳<?php echo number_format($auction['current_highest_bid'], 2); ?></span>
                                        <br>
                                        <small>From: <?php echo htmlspecialchars($auction['farmer_name']); ?></small>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Set user type for notifications
var userType = 'buyer';
</script>

<?php include '../includes/footer.php'; ?>
