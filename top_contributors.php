<?php
// Suppress PHP warnings for production
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require_once 'config/database.php';
require_once 'config/session.php';

$page_title = 'Top Contributors - Farmer Auction System';
include 'includes/header.php';

// Get top contributing farmers
$database = new Database();
$db = $database->getConnection();

$query = "SELECT f.*, 
          COUNT(DISTINCT p.product_id) as total_products,
          COUNT(DISTINCT a.auction_id) as total_auctions,
          COALESCE(SUM(CASE WHEN pa.status = 'Completed' THEN pa.amount ELSE 0 END), 0) as total_earnings,
          COUNT(DISTINCT pa.payment_id) as completed_sales,
          ROUND(AVG(CASE WHEN pa.status = 'Completed' THEN pa.amount ELSE NULL END), 2) as avg_sale_price,
          (SELECT COUNT(*) FROM Auctions a2 
           JOIN Products p2 ON a2.product_id = p2.product_id 
           WHERE p2.farmer_id = f.farmer_id AND a2.status = 'Active') as active_auctions
          FROM Farmers f
          LEFT JOIN Products p ON f.farmer_id = p.farmer_id
          LEFT JOIN Auctions a ON p.product_id = a.product_id
          LEFT JOIN Payments pa ON a.auction_id = pa.auction_id AND pa.farmer_id = f.farmer_id
          GROUP BY f.farmer_id
          HAVING total_products >= 3 AND total_earnings >= 1000
          ORDER BY total_earnings DESC, total_products DESC, f.created_at ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$top_contributors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all farmers stats for comparison
$stats_query = "SELECT 
                COUNT(DISTINCT f.farmer_id) as total_farmers,
                COUNT(DISTINCT CASE WHEN p_count.product_count >= 3 AND earnings.total_earnings >= 1000 THEN f.farmer_id END) as top_contributors_count
                FROM Farmers f
                LEFT JOIN (
                    SELECT farmer_id, COUNT(*) as product_count 
                    FROM Products 
                    GROUP BY farmer_id
                ) p_count ON f.farmer_id = p_count.farmer_id
                LEFT JOIN (
                    SELECT pa.farmer_id, SUM(CASE WHEN pa.status = 'Completed' THEN pa.amount ELSE 0 END) as total_earnings
                    FROM Payments pa
                    GROUP BY pa.farmer_id
                ) earnings ON f.farmer_id = earnings.farmer_id";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$general_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="text-center mb-5">
                <h1>
                    <i class="fas fa-star text-warning me-2"></i>Top Contributors
                </h1>
                <p class="lead text-muted">
                    Recognizing outstanding farmers who have contributed significantly to our marketplace
                </p>
                <div class="alert alert-info d-inline-block mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Top Contributor Criteria:</strong> 3+ Products Listed & 1000+ Taka Earned
                </div>
                

            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-5">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <div class="h3"><?php echo $general_stats['total_farmers']; ?></div>
                    <div>Total Farmers</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <i class="fas fa-crown fa-2x mb-2"></i>
                    <div class="h3"><?php echo $general_stats['top_contributors_count']; ?></div>
                    <div>Top Contributors</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center bg-warning text-white">
                <div class="card-body">
                    <i class="fas fa-percentage fa-2x mb-2"></i>
                    <div class="h3">
                        <?php echo $general_stats['total_farmers'] > 0 ? round(($general_stats['top_contributors_count'] / $general_stats['total_farmers']) * 100, 1) : 0; ?>%
                    </div>
                    <div>Success Rate</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <i class="fas fa-handshake fa-2x mb-2"></i>
                    <div class="h3"><?php echo !empty($top_contributors) ? '৳' . number_format($top_contributors[0]['total_earnings'], 0) : '৳0'; ?></div>
                    <div>Top Earner</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($top_contributors)): ?>
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-star fa-4x text-muted mb-3"></i>
                    <h3 class="text-muted">No Top Contributors Yet</h3>
                    <p class="text-muted">No farmers have met the criteria yet. Be the first to become a top contributor!</p>
                    <div class="alert alert-info d-inline-block">
                        <strong>How to become a Top Contributor:</strong><br>
                        • List at least 3 products<br>
                        • Earn at least ৳1,000 from completed sales
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="fas fa-medal me-2"></i>Top Contributing Farmers
                    <small class="text-muted">(<?php echo count($top_contributors); ?> farmers)</small>
                </h2>
            </div>
        </div>

        <div class="row">
            <?php foreach ($top_contributors as $index => $farmer): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100 <?php echo $index < 3 ? 'border-warning' : ''; ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <?php if ($index === 0): ?>
                                        <div class="badge bg-warning text-dark fs-4 rounded-circle p-3">
                                            <i class="fas fa-crown"></i>
                                        </div>
                                    <?php elseif ($index === 1): ?>
                                        <div class="badge bg-secondary text-white fs-4 rounded-circle p-3">
                                            <i class="fas fa-medal"></i>
                                        </div>
                                    <?php elseif ($index === 2): ?>
                                        <div class="badge bg-warning text-dark fs-4 rounded-circle p-3">
                                            <i class="fas fa-award"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="badge bg-success text-white fs-4 rounded-circle p-3">
                                            <i class="fas fa-star"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="card-title mb-1">
                                        <?php echo htmlspecialchars($farmer['name']); ?>
                                        <?php if ($index < 3): ?>
                                            <span class="badge bg-warning text-dark ms-2">Top <?php echo $index + 1; ?></span>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($farmer['farm_location']); ?>
                                    </p>
                                    <small class="text-muted">
                                        Member since <?php echo date('M Y', strtotime($farmer['created_at'])); ?>
                                    </small>
                                </div>
                            </div>

                            <div class="row text-center mb-3">
                                <div class="col-3">
                                    <div class="border-end">
                                        <div class="h5 text-success mb-0">৳<?php echo number_format($farmer['total_earnings'], 0); ?></div>
                                        <small class="text-muted">Total Earnings</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="border-end">
                                        <div class="h5 text-primary mb-0"><?php echo $farmer['total_products']; ?></div>
                                        <small class="text-muted">Products</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="border-end">
                                        <div class="h5 text-info mb-0"><?php echo $farmer['completed_sales']; ?></div>
                                        <small class="text-muted">Sales</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="h5 text-warning mb-0"><?php echo $farmer['active_auctions']; ?></div>
                                    <small class="text-muted">Active</small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Total Auctions:</small><br>
                                    <strong><?php echo $farmer['total_auctions']; ?></strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Avg Sale Price:</small><br>
                                    <strong>৳<?php echo $farmer['avg_sale_price'] ? number_format($farmer['avg_sale_price'], 0) : '0'; ?></strong>
                                </div>
                            </div>

                            <?php if ($farmer['phone']): ?>
                                <div class="mt-3">
                                    <small class="text-muted">Contact:</small><br>
                                    <a href="tel:<?php echo htmlspecialchars($farmer['phone']); ?>" class="text-decoration-none">
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($farmer['phone']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Call to Action -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h4><i class="fas fa-handshake me-2"></i>Want to Become a Top Contributor?</h4>
                    <p class="text-muted">Join our marketplace and start selling your quality products!</p>
                    <div class="d-flex justify-content-center gap-3">
                        <?php if (!isLoggedIn()): ?>
                            <a href="register.php" class="btn btn-success">
                                <i class="fas fa-user-plus me-2"></i>Register as Farmer
                            </a>
                        <?php elseif (isFarmer()): ?>
                            <a href="farmer/add_product.php" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>Add Product
                            </a>
                        <?php endif; ?>
                        <a href="auctions.php" class="btn btn-outline-primary">
                            <i class="fas fa-gavel me-2"></i>Browse Auctions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>