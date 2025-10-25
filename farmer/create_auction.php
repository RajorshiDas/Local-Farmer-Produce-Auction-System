<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'Create Auction - Farmer Dashboard';
requireLogin();

if (!isFarmer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$farmer_id = $_SESSION['user_id'];
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$error = '';
$success = '';

// Get product details
$product_query = "SELECT * FROM Products WHERE product_id = ? AND farmer_id = ?";
$product_stmt = $db->prepare($product_query);
$product_stmt->execute([$product_id, $farmer_id]);
$product = $product_stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit();
}

// Check if auction already exists
$auction_check = "SELECT auction_id FROM Auctions WHERE product_id = ?";
$auction_stmt = $db->prepare($auction_check);
$auction_stmt->execute([$product_id]);
if ($auction_stmt->fetch()) {
    header('Location: products.php');
    exit();
}

if ($_POST) {
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    if (empty($start_time) || empty($end_time)) {
        $error = 'Please provide start and end times.';
    } elseif (strtotime($end_time) <= strtotime($start_time)) {
        $error = 'End time must be after start time.';
    } else {
        try {
            $auction_query = "INSERT INTO Auctions (product_id, start_time, end_time, current_highest_bid) 
                              VALUES (?, ?, ?, ?)";
            $auction_stmt = $db->prepare($auction_query);
            $auction_stmt->execute([$product_id, $start_time, $end_time, $product['starting_bid']]);
            
            $success = 'Auction created successfully!';
        } catch (Exception $e) {
            $error = 'Error creating auction: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-gavel me-2"></i>Create Auction
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Product Info -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="row">
                                <div class="col-md-3">
                                    <small class="text-muted">Category</small><br>
                                    <span class="badge bg-success"><?php echo htmlspecialchars($product['category']); ?></span>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Weight</small><br>
                                    <strong><?php echo $product['weight']; ?> kg</strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Starting Bid</small><br>
                                    <strong class="text-success">৳<?php echo number_format($product['starting_bid'], 2); ?></strong>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Certification</small><br>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($product['certification'] ?: 'None'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            <div class="mt-2">
                                <a href="products.php" class="btn btn-success btn-sm">View Products</a>
                                <a href="auctions.php" class="btn btn-outline-success btn-sm">View Auctions</a>
                            </div>
                        </div>
                    <?php else: ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">Auction Start Time *</label>
                                    <input type="datetime-local" class="form-control" id="start_time" name="start_time" 
                                           value="<?php echo isset($_POST['start_time']) ? $_POST['start_time'] : date('Y-m-d\TH:i'); ?>" required>
                                    <div class="form-text">When should the auction begin?</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">Auction End Time *</label>
                                    <input type="datetime-local" class="form-control" id="end_time" name="end_time" 
                                           value="<?php echo isset($_POST['end_time']) ? $_POST['end_time'] : date('Y-m-d\TH:i', strtotime('+7 days')); ?>" required>
                                    <div class="form-text">When should the auction end?</div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>Auction Guidelines</h6>
                            <ul class="mb-0">
                                <li>Minimum auction duration: 1 hour</li>
                                <li>Maximum auction duration: 30 days</li>
                                <li>You cannot modify auction times once created</li>
                                <li>Auctions automatically close at the end time</li>
                                <li>Highest bidder wins the auction</li>
                            </ul>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="products.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-gavel me-2"></i>Create Auction
                            </button>
                        </div>
                    </form>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
