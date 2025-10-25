<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'My Products - Farmer Dashboard';
requireLogin();

if (!isFarmer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$farmer_id = $_SESSION['user_id'];

// Get all products with auction status
$query = "SELECT p.*, a.auction_id, a.status as auction_status, a.current_highest_bid, a.end_time,
          (SELECT image_path FROM ProductImages WHERE product_id = p.product_id LIMIT 1) as main_image
          FROM Products p 
          LEFT JOIN Auctions a ON p.product_id = a.product_id 
          WHERE p.farmer_id = ? 
          ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([$farmer_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-box me-2"></i>My Products</h1>
                <a href="add_product.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Add Product
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($products)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-box fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Products Yet</h4>
                        <p class="text-muted">Start by adding your first product to the marketplace.</p>
                        <a href="add_product.php" class="btn btn-success btn-lg">
                            <i class="fas fa-plus me-2"></i>Add Your First Product
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($products as $product): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <?php if ($product['main_image']): ?>
                            <img src="<?php echo '/farmer_auction/' . ltrim($product['main_image'], '/'); ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                            <p class="card-text text-muted small">
                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($product['category']); ?>
                                <span class="ms-2"><i class="fas fa-weight me-1"></i><?php echo $product['weight']; ?> kg</span>
                            </p>
                            <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Starting Bid</small><br>
                                    <strong class="text-success">৳<?php echo number_format($product['starting_bid'], 2); ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Certification</small><br>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($product['certification'] ?: 'None'); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($product['auction_id']): ?>
                                <div class="alert alert-<?php echo $product['auction_status'] === 'Active' ? 'success' : ($product['auction_status'] === 'Closed' ? 'secondary' : 'danger'); ?> mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-gavel me-1"></i>
                                            <strong><?php echo $product['auction_status']; ?> Auction</strong>
                                        </span>
                                        <?php if ($product['current_highest_bid'] > $product['starting_bid']): ?>
                                            <span class="badge bg-warning">
                                                ৳<?php echo number_format($product['current_highest_bid'], 2); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($product['auction_status'] === 'Active'): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Ends: <?php echo date('M j, Y H:i', strtotime($product['end_time'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-1"></i>
                                    No active auction
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2">
                                <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                <?php if (!$product['auction_id']): ?>
                                    <a href="create_auction.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-success">
                                        <i class="fas fa-gavel me-1"></i>Create Auction
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

<?php include '../includes/footer.php'; ?>
