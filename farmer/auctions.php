<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'My Auctions - Farmer Dashboard';
requireLogin();

if (!isFarmer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$farmer_id = $_SESSION['user_id'];

// Get all auctions for this farmer
$query = "SELECT a.*, p.product_name, p.description, p.category, p.weight,
          (SELECT image_path FROM ProductImages WHERE product_id = p.product_id LIMIT 1) as main_image,
          (SELECT COUNT(*) FROM Bids WHERE auction_id = a.auction_id) as bid_count
          FROM Auctions a 
          JOIN Products p ON a.product_id = p.product_id 
          WHERE p.farmer_id = ? 
          ORDER BY a.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([$farmer_id]);
$auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-gavel me-2"></i>My Auctions</h1>
                <a href="add_product.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Create New Auction
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($auctions)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-gavel fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Auctions Yet</h4>
                        <p class="text-muted">Create your first auction to start selling your products.</p>
                        <a href="add_product.php" class="btn btn-success btn-lg">
                            <i class="fas fa-plus me-2"></i>Create First Auction
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($auctions as $auction): ?>
                <?php $is_upcoming = isset($auction['start_time']) && strtotime($auction['start_time']) > time(); ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <?php if ($auction['main_image']): ?>
                            <img src="<?php echo '/farmer_auction/' . ltrim($auction['main_image'], '/'); ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($auction['product_name']); ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($auction['product_name']); ?></h5>
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
                            
                            <?php if ($is_upcoming): ?>
                                <div class="alert alert-info mb-3">
                            <?php else: ?>
                                <div class="alert alert-<?php echo $auction['status'] === 'Active' ? 'success' : ($auction['status'] === 'Closed' ? 'secondary' : 'danger'); ?> mb-3">
                            <?php endif; ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-gavel me-1"></i>
                                        <strong><?php echo $is_upcoming ? 'Upcoming' : $auction['status']; ?> Auction</strong>
                                    </span>
                                    <span class="badge bg-warning">
                                        ৳<?php echo number_format($auction['current_highest_bid'], 2); ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-gavel me-1"></i><?php echo $auction['bid_count']; ?> bids
                                    </small>
                                    <?php if (!$is_upcoming && $auction['status'] === 'Active'): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Ends: <?php echo date('M j, Y H:i', strtotime($auction['end_time'])); ?>
                                        </small>
                                    <?php elseif ($is_upcoming): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Starts: <?php echo date('M j, Y H:i', strtotime($auction['start_time'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="auction_details.php?id=<?php echo $auction['auction_id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                <?php if (!$is_upcoming && $auction['status'] === 'Active'): ?>
                                    <a href="auction_bids.php?id=<?php echo $auction['auction_id']; ?>" class="btn btn-success">
                                        <i class="fas fa-list me-1"></i>View Bids
                                    </a>
                                <?php elseif ($is_upcoming): ?>
                                    <button class="btn btn-secondary" disabled><i class="fas fa-clock me-1"></i>Upcoming</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
