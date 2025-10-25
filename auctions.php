<?php
require_once 'config/database.php';
require_once 'config/session.php';

$page_title = 'Active Auctions - Farmer Auction System';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'end_time';

// Build query
$query = "SELECT a.*, p.product_name, p.description, p.category, p.weight, p.certification, 
          f.name as farmer_name, f.farm_location,
          (SELECT image_path FROM ProductImages WHERE product_id = p.product_id LIMIT 1) as main_image,
          (SELECT COUNT(*) FROM Bids WHERE auction_id = a.auction_id) as bid_count
          FROM Auctions a 
          JOIN Products p ON a.product_id = p.product_id 
          JOIN Farmers f ON p.farmer_id = f.farmer_id 
          WHERE a.status = 'Active' AND a.start_time <= NOW() AND a.end_time > NOW()";

$params = [];

if ($category) {
    $query .= " AND p.category = ?";
    $params[] = $category;
}

if ($search) {
    $query .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY a.current_highest_bid ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY a.current_highest_bid DESC";
        break;
    case 'time_left':
        $query .= " ORDER BY a.end_time ASC";
        break;
    case 'bids':
        $query .= " ORDER BY bid_count DESC";
        break;
    default:
        $query .= " ORDER BY a.end_time ASC";
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories_query = "SELECT DISTINCT category FROM Products ORDER BY category";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-gavel me-2"></i>Active Auctions
                <span class="badge bg-success"><?php echo count($auctions); ?> Active</span>
            </h1>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search Products</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" placeholder="Search products...">
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" 
                                    <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sort" class="form-label">Sort By</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="end_time" <?php echo ($sort === 'end_time') ? 'selected' : ''; ?>>Time Left</option>
                        <option value="price_low" <?php echo ($sort === 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo ($sort === 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="bids" <?php echo ($sort === 'bids') ? 'selected' : ''; ?>>Most Bids</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Auctions Grid -->
    <?php if (empty($auctions)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-gavel fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Active Auctions</h4>
                        <p class="text-muted">There are no active auctions matching your criteria.</p>
                        <a href="auctions.php" class="btn btn-success">View All Auctions</a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
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
                                    <span class="countdown-timer" data-end-time="<?php echo $auction['end_time']; ?>">
                                        Loading...
                                    </span>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-gavel me-1"></i><?php echo $auction['bid_count']; ?> bids
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-certificate me-1"></i><?php echo htmlspecialchars($auction['certification'] ?: 'None'); ?>
                                </small>
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
                                    <i class="fas fa-eye me-1"></i>View Details & Bid
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
