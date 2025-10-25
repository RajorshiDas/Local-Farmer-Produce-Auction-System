<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'My Purchases - Buyer Dashboard';
requireLogin();

if (!isBuyer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$buyer_id = $_SESSION['user_id'];

// Get won auctions with payment status
$query = "SELECT a.*, p.product_name, p.description, p.category, p.weight, p.certification,
          f.name as farmer_name, f.farm_location, f.phone as farmer_phone,
          pa.amount as final_price, pa.status as payment_status, pa.created_at as payment_date,
          (SELECT image_path FROM ProductImages WHERE product_id = p.product_id LIMIT 1) as main_image
          FROM Auctions a 
          JOIN Products p ON a.product_id = p.product_id 
          JOIN Farmers f ON p.farmer_id = f.farmer_id 
          JOIN Bids b ON a.auction_id = b.auction_id 
          LEFT JOIN Payments pa ON a.auction_id = pa.auction_id AND pa.buyer_id = ?
          WHERE b.buyer_id = ? AND b.is_winning_bid = TRUE AND a.status = 'Closed'
          ORDER BY a.end_time DESC";

$stmt = $db->prepare($query);
$stmt->execute([$buyer_id, $buyer_id]);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-shopping-bag me-2"></i>My Purchases</h1>
                <a href="dashboard.php" class="btn btn-outline-success">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($purchases)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Purchases Yet</h4>
                        <p class="text-muted">You haven't won any auctions yet. Start bidding to make your first purchase!</p>
                        <a href="../auctions.php" class="btn btn-success btn-lg">
                            <i class="fas fa-gavel me-2"></i>Browse Auctions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($purchases as $purchase): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <?php if ($purchase['main_image']): ?>
                                    <img src="/farmer_auction/<?php echo ltrim($purchase['main_image'], '/'); ?>" class="img-fluid rounded-start h-100" style="object-fit: cover;" alt="<?php echo htmlspecialchars($purchase['product_name']); ?>">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($purchase['product_name']); ?></h5>
                                    <p class="card-text text-muted small">
                                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($purchase['farmer_name']); ?>
                                        <span class="ms-2"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($purchase['farm_location']); ?></span>
                                    </p>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($purchase['description'], 0, 100)) . '...'; ?></p>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted">Category</small><br>
                                            <span class="badge bg-success"><?php echo htmlspecialchars($purchase['category']); ?></span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Weight</small><br>
                                            <strong><?php echo $purchase['weight']; ?> kg</strong>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <small class="text-muted">Final Price</small><br>
                                            <span class="h5 text-success">৳<?php echo number_format($purchase['final_price'], 2); ?></span>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">Payment Status</small><br>
                                            <span class="badge bg-<?php echo $purchase['payment_status'] === 'Completed' ? 'success' : ($purchase['payment_status'] === 'Pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo $purchase['payment_status']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            Won: <?php echo date('M j, Y', strtotime($purchase['end_time'])); ?>
                                        </small>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#contactModal<?php echo $purchase['auction_id']; ?>">
                                                <i class="fas fa-phone me-1"></i>Contact Farmer
                                            </button>
                                            <?php if ($purchase['payment_status'] === 'Pending'): ?>
                                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $purchase['auction_id']; ?>">
                                                    <i class="fas fa-credit-card me-1"></i>Make Payment
                                                </button>
                                            <?php elseif ($purchase['payment_status'] === 'Completed'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Paid
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Farmer Modal -->
                <div class="modal fade" id="contactModal<?php echo $purchase['auction_id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Contact Farmer</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <h6><?php echo htmlspecialchars($purchase['farmer_name']); ?></h6>
                                <p class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($purchase['farm_location']); ?>
                                </p>
                                <?php if ($purchase['farmer_phone']): ?>
                                    <p>
                                        <i class="fas fa-phone me-1"></i>
                                        <a href="tel:<?php echo htmlspecialchars($purchase['farmer_phone']); ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($purchase['farmer_phone']); ?>
                                        </a>
                                    </p>
                                <?php endif; ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Contact the farmer to arrange pickup or delivery for your purchase.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Modal -->
                <?php if ($purchase['payment_status'] === 'Pending'): ?>
                <div class="modal fade" id="paymentModal<?php echo $purchase['auction_id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Make Payment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-4">
                                    <h4><?php echo htmlspecialchars($purchase['product_name']); ?></h4>
                                    <p class="text-muted">From: <?php echo htmlspecialchars($purchase['farmer_name']); ?></p>
                                    <div class="alert alert-success">
                                        <h3 class="mb-0">৳<?php echo number_format($purchase['final_price'], 2); ?></h3>
                                        <small>Final Amount</small>
                                    </div>
                                </div>
                                
                                <form id="paymentForm<?php echo $purchase['auction_id']; ?>">
                                    <input type="hidden" name="auction_id" value="<?php echo $purchase['auction_id']; ?>">
                                    <input type="hidden" name="amount" value="<?php echo $purchase['final_price']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Payment Method</label>
                                        <select class="form-select" name="payment_method" required>
                                            <option value="">Select Payment Method</option>
                                            <option value="bkash">bKash</option>
                                            <option value="nagad">Nagad</option>
                                            <option value="rocket">Rocket</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="cash_on_delivery">Cash on Delivery</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="transaction_id" class="form-label">Transaction ID / Reference</label>
                                        <input type="text" class="form-control" name="transaction_id" 
                                               placeholder="Enter transaction ID or reference number" required>
                                        <div class="form-text">For cash on delivery, enter 'COD'</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="payment_notes" class="form-label">Notes (Optional)</label>
                                        <textarea class="form-control" name="payment_notes" rows="3" 
                                                  placeholder="Any additional notes for the farmer"></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-success" onclick="submitPayment(<?php echo $purchase['auction_id']; ?>)">
                                    <i class="fas fa-credit-card me-1"></i>Submit Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function submitPayment(auctionId) {
    const form = document.getElementById('paymentForm' + auctionId);
    const formData = new FormData(form);
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    if (confirm('Submit payment confirmation?\n\nThis will notify the farmer about your payment.')) {
        fetch('ajax/submit_payment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Payment submitted successfully!', 'success');
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

function showNotification(message, type) {
    // Simple notification function
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}
</script>

<?php include '../includes/footer.php'; ?>
