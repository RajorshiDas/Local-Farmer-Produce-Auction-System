<?php
require_once 'config/database.php';
require_once 'config/session.php';

$page_title = 'Auction Details - Farmer Auction System';

$database = new Database();
$db = $database->getConnection();

$auction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get auction details
$query = "SELECT a.*, p.product_name, p.description, p.category, p.weight, p.certification, p.starting_bid,
          f.name as farmer_name, f.farm_location, f.phone as farmer_phone,
          (SELECT COUNT(*) FROM Bids WHERE auction_id = a.auction_id) as bid_count
          FROM Auctions a 
          JOIN Products p ON a.product_id = p.product_id 
          JOIN Farmers f ON p.farmer_id = f.farmer_id 
          WHERE a.auction_id = ?";

$stmt = $db->prepare($query);
$stmt->execute([$auction_id]);
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

// Get recent bids
$bids_query = "SELECT b.*, bu.name as buyer_name 
                FROM Bids b 
                JOIN Buyers bu ON b.buyer_id = bu.buyer_id 
                WHERE b.auction_id = ? 
                ORDER BY b.bid_time DESC 
                LIMIT 10";
$bids_stmt = $db->prepare($bids_query);
$bids_stmt->execute([$auction_id]);
$bids = $bids_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if auction is upcoming (start time is in the future)
$is_upcoming = strtotime($auction['start_time']) > time();
$actual_status = $is_upcoming ? 'Upcoming' : $auction['status'];

// Check if user is logged in and is a buyer
$can_bid = isLoggedIn() && isBuyer() && !$is_upcoming;
$user_id = isLoggedIn() ? $_SESSION['user_id'] : 0;

// Check if product is in user's wishlist
$in_wishlist = false;
if ($can_bid) {
    $wishlist_query = "SELECT wishlist_id FROM Wishlist WHERE buyer_id = ? AND product_id = ?";
    $wishlist_stmt = $db->prepare($wishlist_query);
    $wishlist_stmt->execute([$user_id, $auction['product_id']]);
    $in_wishlist = $wishlist_stmt->fetch() ? true : false;
}

include 'includes/header.php';
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
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="<?php echo $image; ?>" class="d-block w-100" style="height: 400px; object-fit: cover;" alt="Product Image">
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

            <!-- Recent Bids -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-gavel me-2"></i>Recent Bids
                        <span class="badge bg-primary"><?php echo $auction['bid_count']; ?> total</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($bids)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-gavel fa-2x mb-2"></i>
                            <p>No bids yet. Be the first to bid!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Bidder</th>
                                        <th>Amount</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bids as $bid): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($bid['buyer_name']); ?></td>
                                            <td><strong class="text-success">৳<?php echo number_format($bid['bid_amount'], 2); ?></strong></td>
                                            <td><?php echo date('M j, Y H:i', strtotime($bid['bid_time'])); ?></td>
                                            <td>
                                                <?php if ($bid['is_winning_bid']): ?>
                                                    <span class="badge bg-success">Winning</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Outbid</span>
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
                        <span class="badge bg-<?php echo $is_upcoming ? 'info' : ($auction['status'] === 'Active' ? 'success' : ($auction['status'] === 'Closed' ? 'secondary' : 'danger')); ?>">
                            <?php echo $actual_status; ?>
                        </span>
                    </h5>
                    
                    <?php if ($is_upcoming): ?>
                        <div class="text-center mb-3">
                            <div class="countdown-timer upcoming-timer h4 text-info" data-start-time="<?php echo $auction['start_time']; ?>" data-end-time="<?php echo $auction['end_time']; ?>">
                                Loading...
                            </div>
                            <small class="text-muted">Starts In</small>
                        </div>
                    <?php elseif ($auction['status'] === 'Active'): ?>
                        <div class="text-center mb-3">
                            <div class="countdown-timer h4 text-danger" data-end-time="<?php echo $auction['end_time']; ?>" data-start-time="<?php echo $auction['start_time']; ?>">
                                Loading...
                            </div>
                            <small class="text-muted">Time Remaining</small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <?php if ($is_upcoming): ?>
                                    <div class="h4 text-info">৳<?php echo number_format($auction['starting_bid'], 2); ?></div>
                                    <small class="text-muted">Starting Bid</small>
                                <?php else: ?>
                                    <div class="h4 text-success">৳<?php echo number_format($auction['current_highest_bid'], 2); ?></div>
                                    <small class="text-muted">Current Bid</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="h4 text-primary"><?php echo $auction['bid_count']; ?></div>
                            <small class="text-muted">Total Bids</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bid Form -->
            <?php if ($is_upcoming): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="fas fa-clock fa-3x text-info mb-3"></i>
                            <h5 class="text-info">Auction Not Started</h5>
                            <p class="text-muted">This auction will begin soon. You can place bids once it starts.</p>
                            <small class="text-muted">Start Time: <?php echo date('M j, Y H:i', strtotime($auction['start_time'])); ?></small>
                        </div>
                    </div>
                </div>
            <?php elseif ($auction['status'] === 'Active'): ?>
                <?php if ($can_bid): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-gavel me-2"></i>Place Your Bid
                            </h5>
                            
                            <form id="bidForm" onsubmit="return validateBidForm()">
                                <div class="mb-3">
                                    <label for="bid_amount" class="form-label">Your Bid (৳)</label>
                                    <input type="number" class="form-control" id="bid_amount" name="bid_amount" 
                                           min="<?php echo $auction['current_highest_bid'] + 1; ?>" 
                                           step="0.01" required>
                                    <input type="hidden" id="current_bid" value="<?php echo $auction['current_highest_bid']; ?>">
                                    <input type="hidden" id="auction_id" value="<?php echo $auction['auction_id']; ?>">
                                    <div class="form-text">
                                        Minimum bid: ৳<?php echo number_format($auction['current_highest_bid'] + 1, 2); ?>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-gavel me-2"></i>Place Bid
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card mb-4">
                        <div class="card-body text-center">
                            <i class="fas fa-lock fa-2x text-muted mb-3"></i>
                            <h6>Login Required</h6>
                            <p class="text-muted">You need to be logged in as a buyer to place bids.</p>
                            <a href="login.php" class="btn btn-success">Login</a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <i class="fas fa-ban fa-2x text-muted mb-3"></i>
                        <h6>Auction <?php echo $actual_status; ?></h6>
                        <p class="text-muted">This auction is no longer accepting bids.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Wishlist -->
            <?php if ($can_bid): ?>
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <button type="button" class="btn <?php echo $in_wishlist ? 'btn-danger' : 'btn-outline-danger'; ?> w-100" 
                                onclick="toggleWishlist(<?php echo $auction['product_id']; ?>, this)">
                            <i class="fas <?php echo $in_wishlist ? 'fa-heart' : 'fa-heart'; ?> me-2"></i>
                            <?php echo $in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Farmer Info -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-user me-2"></i>Farmer Information
                    </h5>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($auction['farmer_name']); ?></strong></p>
                    <p class="mb-1">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        <?php echo htmlspecialchars($auction['farm_location']); ?>
                    </p>
                    <?php if ($auction['farmer_phone']): ?>
                        <p class="mb-0">
                            <i class="fas fa-phone me-1"></i>
                            <?php echo htmlspecialchars($auction['farmer_phone']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Bid form submission
document.getElementById('bidForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('auction_id', document.getElementById('auction_id').value);
    
    fetch('ajax/place_bid.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Bid placed successfully!', 'success');
            location.reload();
        } else {
            showNotification('Error: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'danger');
    });
});
</script>

<?php include 'includes/footer.php'; ?>
