<?php
// Suppress PHP warnings for production
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require_once 'config/database.php';
require_once 'config/session.php';

$page_title = 'Past Auctions - Farmer Auction System';
include 'includes/header.php';

// Get closed auctions with winner information
$database = new Database();
$db = $database->getConnection();

$query = "SELECT a.*, p.product_name, p.description, p.category, p.weight, p.certification, p.starting_bid,
          f.name as farmer_name, f.farm_location,
          (SELECT image_path FROM ProductImages WHERE product_id = p.product_id LIMIT 1) as main_image,
          (SELECT COUNT(*) FROM Bids WHERE auction_id = a.auction_id) as bid_count,
          w.buyer_id as winner_id, bu.name as winner_name, w.bid_amount as winning_bid
          FROM Auctions a 
          JOIN Products p ON a.product_id = p.product_id 
          JOIN Farmers f ON p.farmer_id = f.farmer_id 
          LEFT JOIN Bids w ON a.auction_id = w.auction_id AND w.is_winning_bid = TRUE
          LEFT JOIN Buyers bu ON w.buyer_id = bu.buyer_id
          WHERE a.status = 'Closed'
          ORDER BY a.end_time DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$past_auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>
                    <i class="fas fa-history text-secondary me-2"></i>Past Auctions
                </h1>
                <small class="text-muted">Showing completed auctions with results</small>
            </div>
        </div>
    </div>

    <?php if (empty($past_auctions)): ?>
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-history fa-4x text-muted mb-3"></i>
                    <h3 class="text-muted">No Past Auctions</h3>
                    <p class="text-muted">No auctions have been completed yet.</p>
                    <a href="auctions.php" class="btn btn-success">View Active Auctions</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($past_auctions as $auction): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <?php if ($auction['main_image']): ?>
                            <img src="/farmer_auction/<?php echo ltrim($auction['main_image'], '/'); ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($auction['product_name']); ?>" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($auction['product_name']); ?></h5>
                            <p class="card-text text-muted small">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($auction['farmer_name']); ?>
                                <span class="ms-2"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($auction['farm_location']); ?></span>
                            </p>
                            <p class="card-text"><?php echo htmlspecialchars(substr($auction['description'], 0, 100)) . '...'; ?></p>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Category</small><br>
                                    <span class="badge bg-success"><?php echo htmlspecialchars($auction['category']); ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Weight</small><br>
                                    <strong><?php echo $auction['weight']; ?> kg</strong>
                                </div>
                            </div>
                            
                            <div class="alert alert-secondary mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-flag-checkered me-1"></i>
                                        <strong>Auction Completed</strong>
                                    </span>
                                    <span class="badge bg-secondary">
                                        <?php echo $auction['bid_count']; ?> bids
                                    </span>
                                </div>
                                <div class="text-center mt-2">
                                    <small class="text-muted">Ended: <?php echo date('M j, Y H:i', strtotime($auction['end_time'])); ?></small>
                                </div>
                            </div>
                            
                            <?php if ($auction['winner_id']): ?>
                                <div class="alert alert-success mb-3">
                                    <div class="text-center">
                                        <i class="fas fa-trophy text-warning me-2"></i>
                                        <strong>Winner: <?php echo htmlspecialchars($auction['winner_name']); ?></strong><br>
                                        <span class="h5 text-success">৳<?php echo number_format($auction['winning_bid'], 2); ?></span>
                                        <br><small class="text-muted">Final Price</small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-3">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>No Winner</strong><br>
                                        <small class="text-muted">No bids received</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2">
                                <a href="auction_details.php?id=<?php echo $auction['auction_id']; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                <?php if (isLoggedIn() && isBuyer() && $auction['winner_id'] == $_SESSION['user_id']): ?>
                                    <a href="buyer/purchases.php" class="btn btn-success">
                                        <i class="fas fa-credit-card me-1"></i>Make Payment
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>