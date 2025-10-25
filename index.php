<?php
require_once 'config/database.php';
require_once 'config/session.php';

$page_title = 'Home - Farmer Auction System';
include 'includes/header.php';

// Get active auctions for homepage
$database = new Database();
$db = $database->getConnection();

$query = "SELECT a.*, p.product_name, p.description, p.category, p.weight, p.certification, 
          f.name as farmer_name, f.farm_location,
          (SELECT image_path FROM ProductImages WHERE product_id = p.product_id LIMIT 1) as main_image
          FROM Auctions a 
          JOIN Products p ON a.product_id = p.product_id 
          JOIN Farmers f ON p.farmer_id = f.farmer_id 
          WHERE a.status = 'Active' AND a.start_time <= NOW() AND a.end_time > NOW()
          ORDER BY a.end_time ASC 
          LIMIT 6";

$stmt = $db->prepare($query);
$stmt->execute();
$auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-lg-8 mx-auto text-center">
            <h1 class="display-4 fw-bold text-success mb-3">
                <i class="fas fa-seedling me-3"></i>Farmer Auction System
            </h1>
            <p class="lead text-muted mb-4">
                Connect directly with farmers and bid on fresh, organic produce. 
                Support local agriculture while getting the best quality products.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="register.php" class="btn btn-success btn-lg">
                    <i class="fas fa-user-plus me-2"></i>Join as Farmer
                </a>
                <a href="register.php" class="btn btn-outline-success btn-lg">
                    <i class="fas fa-shopping-cart me-2"></i>Join as Buyer
                </a>
            </div>
        </div>
    </div>

    <!-- Active Auctions Section -->
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="fas fa-gavel me-2"></i>Active Auctions
                <a href="auctions.php" class="btn btn-outline-success btn-sm float-end">View All</a>
            </h2>
        </div>
    </div>

    <div class="row">
        <?php if (empty($auctions)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    No active auctions at the moment. Check back later!
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($auctions as $auction): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card auction-card h-100" data-auction-id="<?php echo $auction['auction_id']; ?>">
                        <?php if ($auction['main_image']): ?>
                            <img src="<?php echo $auction['main_image']; ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($auction['product_name']); ?>">
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
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <small class="text-muted">Current Bid</small><br>
                                    <span class="h5 text-success current-bid">৳<?php echo number_format($auction['current_highest_bid'], 2); ?></span>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">Time Left</small><br>
                                    <span class="countdown-timer" data-end-time="<?php echo $auction['end_time']; ?>" data-start-time="<?php echo $auction['start_time']; ?>">
                                        Loading...
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Auction Timing Info -->
                            <div class="alert alert-success mb-3">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <small class="text-muted">Started:</small><br>
                                        <small><strong><?php echo date('M j, H:i', strtotime($auction['start_time'])); ?></strong></small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Ends:</small><br>
                                        <small><strong><?php echo date('M j, H:i', strtotime($auction['end_time'])); ?></strong></small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="auction_details.php?id=<?php echo $auction['auction_id']; ?>" class="btn btn-success">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Features Section -->
    <div class="row mt-5">
        <div class="col-12">
            <h2 class="text-center mb-4">Why Choose Our Platform?</h2>
        </div>
        <div class="col-md-4 text-center mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <i class="fas fa-leaf fa-3x text-success mb-3"></i>
                    <h5>Fresh & Organic</h5>
                    <p class="text-muted">Direct from farm to your table with certified organic products.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-center mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-success mb-3"></i>
                    <h5>Fair Pricing</h5>
                    <p class="text-muted">Competitive bidding ensures fair prices for both farmers and buyers.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-center mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <i class="fas fa-truck fa-3x text-success mb-3"></i>
                    <h5>Direct Connection</h5>
                    <p class="text-muted">Connect directly with local farmers in your area.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
