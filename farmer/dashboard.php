<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'Farmer Dashboard - Farmer Auction System';
requireLogin();

if (!isFarmer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$farmer_id = $_SESSION['user_id'];

// Get farmer statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM Products WHERE farmer_id = ?) as total_products,
    (SELECT COUNT(*) FROM Auctions a JOIN Products p ON a.product_id = p.product_id WHERE p.farmer_id = ? AND a.status = 'Active' AND a.start_time <= NOW() AND a.end_time > NOW()) as active_auctions,
    (SELECT COUNT(*) FROM Auctions a JOIN Products p ON a.product_id = p.product_id WHERE p.farmer_id = ? AND (a.status = 'Closed' OR a.end_time <= NOW())) as closed_auctions,
    (SELECT COALESCE(SUM(amount), 0) FROM Payments pa JOIN Auctions a ON pa.auction_id = a.auction_id JOIN Products p ON a.product_id = p.product_id WHERE p.farmer_id = ? AND pa.status = 'Completed') as total_earnings";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$farmer_id, $farmer_id, $farmer_id, $farmer_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent products
$products_query = "SELECT p.*, 
                   CASE 
                       WHEN a.status = 'Active' AND a.start_time > NOW() THEN 'Upcoming'
                       WHEN a.status = 'Active' AND a.start_time <= NOW() AND a.end_time > NOW() THEN 'Active'
                       WHEN a.status = 'Closed' OR a.end_time <= NOW() THEN 'Closed'
                       ELSE a.status
                   END as auction_status,
                   a.current_highest_bid, a.end_time, a.start_time
                   FROM Products p 
                   LEFT JOIN Auctions a ON p.product_id = a.product_id 
                   WHERE p.farmer_id = ? 
                   ORDER BY p.created_at DESC 
                   LIMIT 5";

$products_stmt = $db->prepare($products_query);
$products_stmt->execute([$farmer_id]);
$recent_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent auctions
$auctions_query = "SELECT a.*, p.product_name, p.description, 
                   (SELECT COUNT(*) FROM Bids WHERE auction_id = a.auction_id) as bid_count
                   FROM Auctions a 
                   JOIN Products p ON a.product_id = p.product_id 
                   WHERE p.farmer_id = ? 
                   ORDER BY a.created_at DESC 
                   LIMIT 5";

$auctions_stmt = $db->prepare($auctions_query);
$auctions_stmt->execute([$farmer_id]);
$recent_auctions = $auctions_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-tractor me-2"></i>Farmer Dashboard
                <span class="text-muted fs-6">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</span>
            </h1>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-body text-center">
                    <i class="fas fa-seedling fa-2x mb-2"></i>
                    <div class="stats-number"><?php echo $stats['total_products']; ?></div>
                    <div>Total Products</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-body text-center">
                    <i class="fas fa-gavel fa-2x mb-2"></i>
                    <div class="stats-number"><?php echo $stats['active_auctions']; ?></div>
                    <div>Active Auctions</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <div class="stats-number"><?php echo $stats['closed_auctions']; ?></div>
                    <div>Completed Auctions</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                    <div class="stats-number">৳<?php echo number_format($stats['total_earnings'], 2); ?></div>
                    <div>Total Earnings</div>
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
                            <a href="add_product.php" class="btn btn-success w-100">
                                <i class="fas fa-plus me-2"></i>Add Product
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="products.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-list me-2"></i>Manage Products
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="auctions.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-gavel me-2"></i>Manage Auctions
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="payments.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-credit-card me-2"></i>View Payments
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Products -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-box me-2"></i>Recent Products</h5>
                    <a href="products.php" class="btn btn-sm btn-outline-success">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_products)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-box fa-2x mb-2"></i>
                            <p>No products yet. <a href="add_product.php">Add your first product</a></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_products as $product): ?>
                            <div class="d-flex align-items-center mb-3 p-2 border rounded">
                                <div class="flex-shrink-0">
                                    <?php
                                    $image_query = "SELECT image_path FROM ProductImages WHERE product_id = ? LIMIT 1";
                                    $image_stmt = $db->prepare($image_query);
                                    $image_stmt->execute([$product['product_id']]);
                                    $image = $image_stmt->fetch(PDO::FETCH_ASSOC);
                                    ?>
                                    <?php if ($image): ?>
                                        <img src="/farmer_auction/<?php echo $image['image_path']; ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;" alt="Product">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php if ($product['auction_status']): ?>
                                            <span class="badge bg-<?php 
                                                echo $product['auction_status'] === 'Active' ? 'success' : 
                                                    ($product['auction_status'] === 'Upcoming' ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo $product['auction_status']; ?>
                                            </span>
                                            <?php if ($product['auction_status'] === 'Active' && $product['current_highest_bid']): ?>
                                                <span class="ms-2">Current: ৳<?php echo number_format($product['current_highest_bid'], 2); ?></span>
                                            <?php elseif ($product['auction_status'] === 'Upcoming'): ?>
                                                <span class="ms-2">Starts: <?php echo date('M j, H:i', strtotime($product['start_time'])); ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No auction</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Auctions -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-gavel me-2"></i>Recent Auctions</h5>
                    <a href="auctions.php" class="btn btn-sm btn-outline-success">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_auctions)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-gavel fa-2x mb-2"></i>
                            <p>No auctions yet. <a href="add_product.php">Create your first auction</a></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_auctions as $auction): ?>
                            <?php $is_upcoming = isset($auction['start_time']) && strtotime($auction['start_time']) > time(); ?>
                            <div class="d-flex align-items-center mb-3 p-2 border rounded">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-gavel fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($auction['product_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php if ($is_upcoming): ?>
                                            <span class="badge bg-info">Upcoming</span>
                                        <?php else: ?>
                                            <span class="badge bg-<?php echo $auction['status'] === 'Active' ? 'success' : ($auction['status'] === 'Closed' ? 'secondary' : 'danger'); ?>">
                                                <?php echo $auction['status']; ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="ms-2"><?php echo $auction['bid_count']; ?> bids</span>
                                        <?php if ($auction['current_highest_bid']): ?>
                                            <span class="ms-2">Highest: ৳<?php echo number_format($auction['current_highest_bid'], 2); ?></span>
                                        <?php endif; ?>
                                    </small>
                                    <?php if ($is_upcoming): ?>
                                        <div class="small text-muted mt-1">Starts: <?php echo date('M j, Y H:i', strtotime($auction['start_time'])); ?></div>
                                    <?php elseif ($auction['status'] === 'Active'): ?>
                                        <div class="small text-muted mt-1">Ends: <?php echo date('M j, Y H:i', strtotime($auction['end_time'])); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
