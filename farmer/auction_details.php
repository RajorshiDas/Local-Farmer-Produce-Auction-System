<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'Auction Details - Farmer Dashboard';
requireLogin();

if (!isFarmer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$auction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$farmer_id = $_SESSION['user_id'];

// Get auction details
$query = "SELECT a.*, p.product_name, p.description, p.category, p.weight, p.certification, p.starting_bid,
          f.name as farmer_name, f.farm_location
          FROM Auctions a 
          JOIN Products p ON a.product_id = p.product_id 
          JOIN Farmers f ON p.farmer_id = f.farmer_id 
          WHERE a.auction_id = ? AND p.farmer_id = ?";

$stmt = $db->prepare($query);
$stmt->execute([$auction_id, $farmer_id]);
$auction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$auction) {
    header('Location: auctions.php');
    exit();
}

// Get product images
$images_query = "SELECT image_path FROM ProductImages WHERE product_id = ?";
$images_stmt = $db->prepare($images_query);
$images_stmt->execute([$auction['product_id']]);
$images = $images_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get all bids
$bids_query = "SELECT b.*, bu.name as buyer_name, bu.phone as buyer_phone
                FROM Bids b 
                JOIN Buyers bu ON b.buyer_id = bu.buyer_id 
                WHERE b.auction_id = ? 
                ORDER BY b.bid_time DESC";
$bids_stmt = $db->prepare($bids_query);
$bids_stmt->execute([$auction_id]);
$bids = $bids_stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Product Images -->
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

            <!-- Product Details -->
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title"><?php echo htmlspecialchars($auction['product_name']); ?></h2>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($auction['description'])); ?></p>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <strong>Category:</strong><br>
                            <span class="badge bg-success"><?php echo htmlspecialchars($auction['category']); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Weight:</strong><br>
                            <?php echo $auction['weight']; ?> kg
                        </div>
                        <div class="col-md-3">
                            <strong>Certification:</strong><br>
                            <span class="badge bg-info"><?php echo htmlspecialchars($auction['certification'] ?: 'None'); ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Starting Bid:</strong><br>
                            <span class="text-success fw-bold">৳<?php echo number_format($auction['starting_bid'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- All Bids -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-gavel me-2"></i>All Bids
                        <span class="badge bg-primary"><?php echo count($bids); ?> total</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($bids)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-gavel fa-2x mb-2"></i>
                            <p>No bids yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Bidder</th>
                                        <th>Amount</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Contact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bids as $bid): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($bid['buyer_name']); ?></strong>
                                            </td>
                                            <td>
                                                <strong class="text-success">৳<?php echo number_format($bid['bid_amount'], 2); ?></strong>
                                            </td>
                                            <td><?php echo date('M j, Y H:i', strtotime($bid['bid_time'])); ?></td>
                                            <td>
                                                <?php if ($bid['is_winning_bid']): ?>
                                                    <span class="badge bg-success">Winning</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Outbid</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($bid['buyer_phone']): ?>
                                                    <a href="tel:<?php echo htmlspecialchars($bid['buyer_phone']); ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-phone me-1"></i>Call
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No phone</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Auction Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-clock me-2"></i>Auction Status
                        <span class="badge bg-<?php echo $auction['status'] === 'Active' ? 'success' : ($auction['status'] === 'Closed' ? 'secondary' : 'danger'); ?>">
                            <?php echo $auction['status']; ?>
                        </span>
                    </h5>
                    
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="h4 text-success">৳<?php echo number_format($auction['current_highest_bid'], 2); ?></div>
                            <small class="text-muted">Current Bid</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 text-primary"><?php echo count($bids); ?></div>
                            <small class="text-muted">Total Bids</small>
                        </div>
                    </div>
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted">Start Time</small><br>
                            <strong><?php echo date('M j, Y H:i', strtotime($auction['start_time'])); ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">End Time</small><br>
                            <strong><?php echo date('M j, Y H:i', strtotime($auction['end_time'])); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="auctions.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Auctions
                        </a>
                        <a href="products.php" class="btn btn-outline-primary">
                            <i class="fas fa-box me-2"></i>View Product
                        </a>
                        <?php if ($auction['status'] === 'Active'): ?>
                            <button type="button" class="btn btn-warning" onclick="confirmCloseAuction(<?php echo $auction['auction_id']; ?>)">
                                <i class="fas fa-clock me-2"></i>Close Auction Early
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmCloseAuction(auctionId) {
    if (confirm('⚠️ Close auction early?\n\nThis will:\n• End bidding immediately\n• Award to current highest bidder\n• Cannot be undone\n\nProceed?')) {
        if (confirm('Final confirmation: Close auction now?')) {
            fetch('ajax/close_auction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ auction_id: auctionId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Auction closed successfully', 'success');
                    location.reload();
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
}

function showNotification(message, type) {
    // Simple notification function
    alert(message);
}
</script>

<?php include '../includes/footer.php'; ?>
