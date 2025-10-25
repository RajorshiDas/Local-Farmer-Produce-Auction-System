<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'Product Details - Farmer Dashboard';
requireLogin();

if (!isFarmer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$farmer_id = $_SESSION['user_id'];

// Get product details
$query = "SELECT p.*, f.name as farmer_name, f.farm_location
          FROM Products p
          JOIN Farmers f ON p.farmer_id = f.farmer_id
          WHERE p.product_id = ? AND p.farmer_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$product_id, $farmer_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit();
}

// Get images
$images_query = "SELECT image_path FROM ProductImages WHERE product_id = ?";
$images_stmt = $db->prepare($images_query);
$images_stmt->execute([$product_id]);
$images = $images_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get auction (if any)
$auction_query = "SELECT * FROM Auctions WHERE product_id = ? LIMIT 1";
$auction_stmt = $db->prepare($auction_query);
$auction_stmt->execute([$product_id]);
$auction = $auction_stmt->fetch(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body p-0">
                    <?php if (!empty($images)): ?>
                        <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($images as $index => $image): ?>
                                    <?php $img_src = '/farmer_auction/' . ltrim($image, '/'); ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="<?php echo $img_src; ?>" class="d-block w-100" style="height: 400px; object-fit: cover;" alt="Product Image">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($images) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 400px;">
                            <i class="fas fa-image fa-4x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h2>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Category:</strong><br>
                            <span class="badge bg-success"><?php echo htmlspecialchars($product['category']); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Weight:</strong><br>
                            <?php echo $product['weight']; ?> kg
                        </div>
                        <div class="col-md-3">
                            <strong>Certification:</strong><br>
                            <span class="badge bg-info"><?php echo htmlspecialchars($product['certification'] ?: 'None'); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Starting Bid:</strong><br>
                            <span class="text-success fw-bold">৳<?php echo number_format($product['starting_bid'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($auction): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-gavel me-2"></i>Auction</h5>
                    </div>
                    <div class="card-body">
                        <p>Status: <strong><?php echo $auction['status']; ?></strong></p>
                        <p>Current Highest Bid: <strong class="text-success">৳<?php echo number_format($auction['current_highest_bid'], 2); ?></strong></p>
                        <p>Ends: <?php echo date('M j, Y H:i', strtotime($auction['end_time'])); ?></p>
                        <a href="create_auction.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-outline-primary">Manage Auction</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Farmer</h5>
                    <p><?php echo htmlspecialchars($product['farmer_name']); ?></p>
                    <p><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($product['farm_location']); ?></p>
                    <div class="d-grid mt-3">
                        <a href="products.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Products</a>
                        <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary mt-2"><i class="fas fa-edit me-2"></i>Edit Product</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
