<?php
// Suppress PHP warnings for production
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require_once 'config/database.php';
require_once 'config/session.php';

$page_title = 'Top Bidders - Farmer Auction System';
include 'includes/header.php';

// Get top bidding buyers
$database = new Database();
$db = $database->getConnection();



// Use the is_winning_bid flag from the database - much simpler and accurate!
$query = "SELECT b.*, 
          COUNT(DISTINCT bid.bid_id) as total_bids,
          COUNT(DISTINCT CASE WHEN bid.is_winning_bid = TRUE THEN bid.auction_id END) as auctions_won,
          COALESCE(SUM(CASE WHEN bid.is_winning_bid = TRUE THEN bid.bid_amount ELSE 0 END), 0) as total_spent,
          COUNT(DISTINCT bid.auction_id) as participated_auctions,
          ROUND(AVG(bid.bid_amount), 2) as avg_bid_amount,
          MAX(bid.bid_amount) as highest_bid,
          (SELECT COUNT(*) FROM Bids b2 WHERE b2.buyer_id = b.buyer_id AND b2.bid_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_bids
          FROM Buyers b
          LEFT JOIN Bids bid ON b.buyer_id = bid.buyer_id
          GROUP BY b.buyer_id
          HAVING auctions_won >= 3
          ORDER BY auctions_won DESC, total_spent DESC, total_bids DESC, b.created_at ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$top_bidders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all buyers stats for comparison using is_winning_bid flag
$stats_query = "SELECT 
                COUNT(DISTINCT b.buyer_id) as total_buyers,
                COUNT(DISTINCT CASE WHEN wins.auctions_won >= 3 THEN b.buyer_id END) as top_bidders_count,
                COALESCE(SUM(wins.auctions_won), 0) as total_wins
                FROM Buyers b
                LEFT JOIN (
                    SELECT buyer_id, 
                           COUNT(CASE WHEN is_winning_bid = TRUE THEN 1 END) as auctions_won
                    FROM Bids
                    GROUP BY buyer_id
                ) wins ON b.buyer_id = wins.buyer_id";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$general_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <!-- Page Header -->
    <div class="text-center mb-5">
        <div class="d-inline-block p-3 bg-primary text-white rounded-circle mb-3">
            <i class="fas fa-trophy fa-2x"></i>
        </div>
        <h1 class="display-4 fw-bold text-primary mb-3">
            <i class="fas fa-trophy me-2"></i>Top Bidders
        </h1>
        <p class="lead text-muted">
            Recognizing exceptional buyers who have won multiple auctions in our marketplace
        </p>
        <div class="alert alert-info d-inline-block mb-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Top Bidder Criteria:</strong> Won 3+ Auctions
        </div>

    </div>

    <!-- Statistics Cards -->
    <div class="row mb-5">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <div class="h3"><?php echo $general_stats['total_buyers']; ?></div>
                    <div>Total Buyers</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <i class="fas fa-trophy fa-2x mb-2"></i>
                    <div class="h3"><?php echo $general_stats['top_bidders_count']; ?></div>
                    <div>Top Bidders</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center bg-warning text-white">
                <div class="card-body">
                    <i class="fas fa-percentage fa-2x mb-2"></i>
                    <div class="h3">
                        <?php echo $general_stats['total_buyers'] > 0 ? round(($general_stats['top_bidders_count'] / $general_stats['total_buyers']) * 100, 1) : 0; ?>%
                    </div>
                    <div>Success Rate</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <i class="fas fa-gavel fa-2x mb-2"></i>
                    <div class="h3"><?php echo !empty($top_bidders) ? $top_bidders[0]['auctions_won'] : '0'; ?></div>
                    <div>Most Wins</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($top_bidders)): ?>
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-trophy fa-4x text-muted mb-3"></i>
                    <h3 class="text-muted">No Top Bidders Yet</h3>
                    <p class="text-muted">No buyers have met the criteria yet. Be the first to become a top bidder!</p>
                    <div class="alert alert-info d-inline-block">
                        <strong>How to become a Top Bidder:</strong><br>
                        • Participate in auctions<br>
                        • Win at least 3 auctions
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0">
                        <i class="fas fa-list-ol me-2 text-primary"></i>
                        Top Bidders Ranking
                    </h2>
                    <span class="badge bg-primary fs-6">
                        <?php echo count($top_bidders); ?> Qualified Bidders
                    </span>
                </div>

                <?php foreach ($top_bidders as $index => $buyer): 
                    $rank = $index + 1;
                ?>
                    <div class="card shadow-sm mb-4 position-relative overflow-hidden">
                        <!-- Rank Badge -->
                        <div class="position-absolute top-0 end-0 m-3">
                            <div class="badge bg-primary fs-5 rounded-circle p-3 shadow">
                                #<?php echo $rank; ?>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <!-- Buyer Info -->
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <!-- Trophy Icon for Top 3 -->
                                        <?php if ($index === 0): ?>
                                            <div class="badge bg-warning text-dark fs-4 rounded-circle p-3 me-3">
                                                <i class="fas fa-trophy"></i>
                                            </div>
                                        <?php elseif ($index === 1): ?>
                                            <div class="badge bg-secondary text-white fs-4 rounded-circle p-3 me-3">
                                                <i class="fas fa-medal"></i>
                                            </div>
                                        <?php elseif ($index === 2): ?>
                                            <div class="badge bg-warning text-dark fs-4 rounded-circle p-3 me-3">
                                                <i class="fas fa-award"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="badge bg-success text-white fs-4 rounded-circle p-3 me-3">
                                                <i class="fas fa-star"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div>
                                            <h5 class="card-title mb-1">
                                                <?php echo htmlspecialchars($buyer['name']); ?>
                                                <?php if ($index < 3): ?>
                                                    <span class="badge bg-warning text-dark ms-2">Top <?php echo $index + 1; ?></span>
                                                <?php endif; ?>
                                            </h5>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?php echo htmlspecialchars($buyer['email']); ?>
                                            </p>
                                            <small class="text-muted">
                                                Member since <?php echo date('M Y', strtotime($buyer['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Statistics -->
                                <div class="col-md-8">
                                    <div class="row text-center">
                                        <div class="col-md-3 col-6 mb-2">
                                            <div class="border-end">
                                                <div class="h5 text-success mb-0"><?php echo $buyer['auctions_won']; ?></div>
                                                <small class="text-muted">Auctions Won</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 mb-2">
                                            <div class="border-end">
                                                <div class="h5 text-primary mb-0">৳<?php echo number_format($buyer['total_spent'], 0); ?></div>
                                                <small class="text-muted">Total Spent</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 mb-2">
                                            <div class="border-end">
                                                <div class="h5 text-info mb-0"><?php echo $buyer['total_bids']; ?></div>
                                                <small class="text-muted">Total Bids</small>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 mb-2">
                                            <div class="h5 text-warning mb-0">৳<?php echo number_format($buyer['highest_bid'], 0); ?></div>
                                            <small class="text-muted">Highest Bid</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Additional Stats -->
                                    <div class="row mt-3 text-center">
                                        <div class="col-md-4 col-4">
                                            <small class="text-muted d-block">Win Rate</small>
                                            <span class="badge bg-success">
                                                <?php echo $buyer['participated_auctions'] > 0 ? round(($buyer['auctions_won'] / $buyer['participated_auctions']) * 100, 1) : 0; ?>%
                                            </span>
                                        </div>
                                        <div class="col-md-4 col-4">
                                            <small class="text-muted d-block">Avg Bid</small>
                                            <span class="badge bg-info">৳<?php echo number_format($buyer['avg_bid_amount'], 0); ?></span>
                                        </div>
                                        <div class="col-md-4 col-4">
                                            <small class="text-muted d-block">Recent Activity</small>
                                            <span class="badge bg-primary"><?php echo $buyer['recent_bids']; ?> bids (30d)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Call to Action -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-light border-0">
                <div class="card-body text-center py-5">
                    <h3 class="text-primary mb-3">Want to Join the Top Bidders?</h3>
                    <p class="text-muted mb-4">Start bidding on auctions and win your way to the top!</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="/farmer_auction/auctions.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-gavel me-2"></i>Browse Active Auctions
                        </a>
                        <a href="/farmer_auction/upcoming_auctions.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-clock me-2"></i>View Upcoming Auctions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.rank-badge {
    font-size: 1.2rem;
    font-weight: bold;
    color: #007bff;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #007bff;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.bg-purple {
    background-color: #6f42c1 !important;
}
</style>

<?php include 'includes/footer.php'; ?>