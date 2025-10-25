<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'Auction Bids - Farmer Dashboard';
requireLogin();

if (!isFarmer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$auction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$farmer_id = $_SESSION['user_id'];

// Verify auction belongs to this farmer
$aq = $db->prepare("SELECT a.*, p.product_id, p.product_name, p.farmer_id, p.product_name, (SELECT image_path FROM ProductImages WHERE product_id = p.product_id LIMIT 1) as main_image FROM Auctions a JOIN Products p ON a.product_id = p.product_id WHERE a.auction_id = ?");
$aq->execute([$auction_id]);
$auction = $aq->fetch(PDO::FETCH_ASSOC);

if (!$auction || $auction['farmer_id'] != $farmer_id) {
    header('Location: auctions.php');
    exit();
}

// Get bids
$bq = $db->prepare("SELECT b.*, bu.name as buyer_name, bu.email as buyer_email FROM Bids b JOIN Buyers bu ON b.buyer_id = bu.buyer_id WHERE b.auction_id = ? ORDER BY b.bid_time DESC");
$bq->execute([$auction_id]);
$bids = $bq->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body p-0">
                    <?php if (!empty($auction['main_image'])): ?>
                        <img src="<?php echo '/farmer_auction/' . ltrim($auction['main_image'], '/'); ?>" class="d-block w-100" style="height: 400px; object-fit: cover;" alt="Product Image">
                    <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 400px;"><i class="fas fa-image fa-3x text-muted"></i></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h3><?php echo htmlspecialchars($auction['product_name']); ?></h3>
                    <p>Auction status: <strong><?php echo $auction['status']; ?></strong></p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5>Bids (<?php echo count($bids); ?>)</h5></div>
                <div class="card-body">
                    <?php if (empty($bids)): ?>
                        <div class="text-center text-muted">No bids yet.</div>
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
                                        <td><?php echo htmlspecialchars($bid['buyer_name']); ?></td>
                                        <td><strong class="text-success">৳<?php echo number_format($bid['bid_amount'],2); ?></strong></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($bid['bid_time'])); ?></td>
                                        <td><?php echo $bid['is_winning_bid'] ? '<span class="badge bg-success">Winning</span>' : '<span class="badge bg-secondary">Outbid</span>'; ?></td>
                                        <td><a href="mailto:<?php echo htmlspecialchars($bid['buyer_email']); ?>" class="btn btn-sm btn-outline-primary">Email</a></td>
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
            <div class="card mb-4">
                <div class="card-body">
                    <a href="auctions.php" class="btn btn-outline-secondary mb-2"><i class="fas fa-arrow-left me-2"></i>Back to Auctions</a>
                    <a href="product_details.php?id=<?php echo $auction['product_id']; ?>" class="btn btn-primary"><i class="fas fa-box me-2"></i>View Product</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
