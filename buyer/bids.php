<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'My Bids - Buyer Dashboard';
requireLogin();

if (!isBuyer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$buyer_id = $_SESSION['user_id'];

// Get all bids with auction and product details
$query = "SELECT b.*, a.status as auction_status, a.end_time, a.current_highest_bid,
          p.product_name, p.description, p.category, p.weight,
          f.name as farmer_name, f.farm_location,
          (SELECT image_path FROM ProductImages WHERE product_id = p.product_id LIMIT 1) as main_image
          FROM Bids b 
          JOIN Auctions a ON b.auction_id = a.auction_id 
          JOIN Products p ON a.product_id = p.product_id 
          JOIN Farmers f ON p.farmer_id = f.farmer_id 
          WHERE b.buyer_id = ? 
          ORDER BY b.bid_time DESC";

$stmt = $db->prepare($query);
$stmt->execute([$buyer_id]);
$all_bids = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-gavel me-2"></i>My Bids</h1>
                <a href="dashboard.php" class="btn btn-outline-success">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($all_bids)): ?>
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-gavel fa-4x text-muted mb-3"></i>
                    <h3 class="text-muted">No Bids Yet</h3>
                    <p class="text-muted">You haven't placed any bids yet. Start bidding on auctions!</p>
                    <a href="../auctions.php" class="btn btn-success">
                        <i class="fas fa-search me-2"></i>Browse Auctions
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($all_bids as $bid): ?>
                <?php 
                $is_upcoming = strtotime($bid['end_time']) > time() && $bid['auction_status'] === 'Active';
                $is_closed = $bid['auction_status'] === 'Closed';
                $is_winning = $bid['is_winning_bid'];
                $is_current_highest = $bid['bid_amount'] == $bid['current_highest_bid'] && !$is_closed;
                ?>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <?php if ($bid['main_image']): ?>
                                    <img src="/farmer_auction/<?php echo ltrim($bid['main_image'], '/'); ?>" class="img-fluid rounded-start h-100" style="object-fit: cover;" alt="<?php echo htmlspecialchars($bid['product_name']); ?>">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($bid['product_name']); ?></h5>
                                    <p class="card-text text-muted small">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($bid['farmer_name']); ?>
                                        <span class="ms-2"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($bid['farm_location']); ?></span>
                                    </p>
                                    
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <small class="text-muted">Your Bid</small><br>
                                            <span class="h6 text-primary">৳<?php echo number_format($bid['bid_amount'], 2); ?></span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Bid Time</small><br>
                                            <small><?php echo date('M j, Y H:i', strtotime($bid['bid_time'])); ?></small>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted">Current Highest</small><br>
                                            <span class="text-success">৳<?php echo number_format($bid['current_highest_bid'], 2); ?></span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Status</small><br>
                                            <?php if ($is_winning && $is_closed): ?>
                                                <span class="badge bg-success">Won Auction</span>
                                            <?php elseif ($is_current_highest && !$is_closed): ?>
                                                <span class="badge bg-success">Leading</span>
                                            <?php elseif ($is_closed): ?>
                                                <span class="badge bg-secondary">Outbid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Outbid</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted">Category</small><br>
                                            <span class="badge bg-success"><?php echo htmlspecialchars($bid['category']); ?></span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Weight</small><br>
                                            <strong><?php echo $bid['weight']; ?> kg</strong>
                                        </div>
                                    </div>
                                    
                                    <?php if ($bid['auction_status'] === 'Active'): ?>
                                        <div class="alert alert-info mb-3">
                                            <div class="text-center">
                                                <small class="text-muted">Auction ends in:</small><br>
                                                <span class="countdown-timer h6" data-end-time="<?php echo $bid['end_time']; ?>">
                                                    Loading...
                                                </span>
                                            </div>
                                        </div>
                                    <?php elseif ($bid['auction_status'] === 'Closed'): ?>
                                        <div class="alert alert-secondary mb-3">
                                            <div class="text-center">
                                                <i class="fas fa-flag-checkered me-1"></i>
                                                <strong>Auction Closed</strong><br>
                                                <small class="text-muted">Ended: <?php echo date('M j, Y H:i', strtotime($bid['end_time'])); ?></small>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="../auction_details.php?id=<?php echo $bid['auction_id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>View Auction
                                        </a>
                                        <?php if ($is_winning && $is_closed): ?>
                                            <a href="purchases.php" class="btn btn-success">
                                                <i class="fas fa-credit-card me-1"></i>Go to Purchases
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Set user type for notifications
var userType = 'buyer';
</script>

<?php include '../includes/footer.php'; ?>