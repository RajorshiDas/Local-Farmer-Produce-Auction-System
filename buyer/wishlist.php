<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'My Wishlist - Buyer Dashboard';
requireLogin();

if (!isBuyer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$buyer_id = $_SESSION['user_id'];

// Get wishlist items
$query = "SELECT w.*, p.product_name, p.description, p.category, p.weight, p.certification, p.starting_bid,
          f.name as farmer_name, f.farm_location,
          a.auction_id, a.status as auction_status, a.current_highest_bid, a.end_time,
          (SELECT image_path FROM ProductImages WHERE product_id = p.product_id LIMIT 1) as main_image
          FROM Wishlist w 
          JOIN Products p ON w.product_id = p.product_id 
          JOIN Farmers f ON p.farmer_id = f.farmer_id 
          LEFT JOIN Auctions a ON p.product_id = a.product_id 
          WHERE w.buyer_id = ? 
          ORDER BY w.added_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([$buyer_id]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-heart me-2"></i>My Wishlist</h1>
                <a href="../auctions.php" class="btn btn-success">
                    <i class="fas fa-search me-2"></i>Browse Auctions
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($wishlist_items)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-heart fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">Your Wishlist is Empty</h4>
                        <p class="text-muted">Add products to your wishlist to keep track of items you're interested in.</p>
                        <a href="../auctions.php" class="btn btn-success btn-lg">
                            <i class="fas fa-search me-2"></i>Browse Auctions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <?php if ($item['main_image']): ?>
                            <img src="/farmer_auction/<?php echo ltrim($item['main_image'], '/'); ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['product_name']); ?></h5>
                            <p class="card-text text-muted small">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($item['farmer_name']); ?>
                                <span class="ms-2"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($item['farm_location']); ?></span>
                            </p>
                            <p class="card-text"><?php echo htmlspecialchars(substr($item['description'], 0, 100)) . '...'; ?></p>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Category</small><br>
                                    <span class="badge bg-success"><?php echo htmlspecialchars($item['category']); ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Weight</small><br>
                                    <strong><?php echo $item['weight']; ?> kg</strong>
                                </div>
                            </div>
                            
                            <?php if ($item['auction_id']): ?>
                                <div class="alert alert-<?php echo $item['auction_status'] === 'Active' ? 'success' : 'secondary'; ?> mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-gavel me-1"></i>
                                            <strong><?php echo $item['auction_status']; ?> Auction</strong>
                                        </span>
                                        <?php if ($item['current_highest_bid'] > $item['starting_bid']): ?>
                                            <span class="badge bg-warning">
                                                ৳<?php echo number_format($item['current_highest_bid'], 2); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($item['auction_status'] === 'Active'): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Ends: <?php echo date('M j, Y H:i', strtotime($item['end_time'])); ?>
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
                                <?php if ($item['auction_id']): ?>
                                    <a href="../auction_details.php?id=<?php echo $item['auction_id']; ?>" class="btn btn-success">
                                        <i class="fas fa-eye me-1"></i>View Auction
                                    </a>
                                <?php else: ?>
                                    <a href="../auction_details.php?product_id=<?php echo $item['product_id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>View Product
                                    </a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline-danger" onclick="removeFromWishlist(<?php echo $item['product_id']; ?>, this)">
                                    <i class="fas fa-heart-broken me-1"></i>Remove from Wishlist
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function removeFromWishlist(productId, button) {
    if (confirm('Are you sure you want to remove this item from your wishlist?')) {
        fetch('../ajax/toggle_wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ product_id: productId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.closest('.col-lg-4').remove();
                showNotification('Item removed from wishlist', 'success');
            } else {
                showNotification('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'danger');
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>
