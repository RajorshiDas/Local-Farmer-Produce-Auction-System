<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'Payments - Farmer Dashboard';
requireLogin();

if (!isFarmer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$farmer_id = $_SESSION['user_id'];

// Get payments for this farmer
$query = "SELECT pa.*, a.auction_id, a.end_time, p.product_name, p.description,
          bu.name as buyer_name, bu.phone as buyer_phone, bu.address as buyer_address
          FROM Payments pa 
          JOIN Auctions a ON pa.auction_id = a.auction_id 
          JOIN Products p ON a.product_id = p.product_id 
          JOIN Buyers bu ON pa.buyer_id = bu.buyer_id 
          WHERE pa.farmer_id = ? 
          ORDER BY pa.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([$farmer_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_earnings = 0;
$completed_payments = 0;
foreach ($payments as $payment) {
    if ($payment['status'] === 'Completed') {
        $total_earnings += $payment['amount'];
        $completed_payments++;
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-credit-card me-2"></i>Payments</h1>
                <a href="dashboard.php" class="btn btn-outline-success">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                    <div class="stats-number">৳<?php echo number_format($total_earnings, 2); ?></div>
                    <div>Total Earnings</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <div class="stats-number"><?php echo $completed_payments; ?></div>
                    <div>Completed Payments</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card dashboard-card">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <div class="stats-number"><?php echo count($payments) - $completed_payments; ?></div>
                    <div>Pending Payments</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($payments)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Payments Yet</h4>
                        <p class="text-muted">You haven't received any payments yet. Create auctions to start earning!</p>
                        <a href="add_product.php" class="btn btn-success btn-lg">
                            <i class="fas fa-plus me-2"></i>Create Auction
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Buyer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($payment['product_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">Auction #<?php echo $payment['auction_id']; ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($payment['buyer_name']); ?></strong>
                                                    <?php if ($payment['buyer_phone']): ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-phone me-1"></i>
                                                            <a href="tel:<?php echo htmlspecialchars($payment['buyer_phone']); ?>" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($payment['buyer_phone']); ?>
                                                            </a>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong class="text-success">৳<?php echo number_format($payment['amount'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $payment['status'] === 'Completed' ? 'success' : ($payment['status'] === 'Submitted' ? 'info' : ($payment['status'] === 'Pending' ? 'warning' : 'danger')); ?>">
                                                    <?php echo $payment['status']; ?>
                                                </span>
                                                <?php if ($payment['status'] === 'Submitted' && isset($payment['payment_method'])): ?>
                                                    <br><small class="text-muted">via <?php echo htmlspecialchars($payment['payment_method']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y H:i', strtotime($payment['created_at'])); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#contactModal<?php echo $payment['payment_id']; ?>">
                                                        <i class="fas fa-phone me-1"></i>Contact
                                                    </button>
                                                    <?php if ($payment['status'] === 'Submitted'): ?>
                                                        <button type="button" class="btn btn-sm btn-info me-1" data-bs-toggle="modal" data-bs-target="#paymentDetailsModal<?php echo $payment['payment_id']; ?>">
                                                            <i class="fas fa-eye me-1"></i>View Payment
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-success" onclick="markAsCompleted(<?php echo $payment['payment_id']; ?>)">
                                                            <i class="fas fa-check me-1"></i>Confirm Payment
                                                        </button>
                                                    <?php elseif ($payment['status'] === 'Pending'): ?>
                                                        <button type="button" class="btn btn-sm btn-warning" disabled>
                                                            <i class="fas fa-clock me-1"></i>Awaiting Payment
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Contact Buyer Modal -->
                                        <div class="modal fade" id="contactModal<?php echo $payment['payment_id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Contact Buyer</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h6><?php echo htmlspecialchars($payment['buyer_name']); ?></h6>
                                                        <?php if ($payment['buyer_phone']): ?>
                                                            <p>
                                                                <i class="fas fa-phone me-1"></i>
                                                                <a href="tel:<?php echo htmlspecialchars($payment['buyer_phone']); ?>" class="text-decoration-none">
                                                                    <?php echo htmlspecialchars($payment['buyer_phone']); ?>
                                                                </a>
                                                            </p>
                                                        <?php endif; ?>
                                                        <?php if ($payment['buyer_address']): ?>
                                                            <p>
                                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                                <?php echo htmlspecialchars($payment['buyer_address']); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle me-2"></i>
                                                            Contact the buyer to arrange delivery or pickup for the purchased item.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Payment Details Modal -->
                                        <?php if ($payment['status'] === 'Submitted'): ?>
                                        <div class="modal fade" id="paymentDetailsModal<?php echo $payment['payment_id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Payment Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h6><?php echo htmlspecialchars($payment['product_name']); ?></h6>
                                                        <p class="text-muted">Buyer: <?php echo htmlspecialchars($payment['buyer_name']); ?></p>
                                                        
                                                        <div class="row mb-3">
                                                            <div class="col-6">
                                                                <strong>Amount:</strong><br>
                                                                <span class="h5 text-success">৳<?php echo number_format($payment['amount'], 2); ?></span>
                                                            </div>
                                                            <div class="col-6">
                                                                <strong>Payment Method:</strong><br>
                                                                <?php echo htmlspecialchars($payment['payment_method'] ?? 'Not specified'); ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <strong>Transaction ID:</strong><br>
                                                            <code><?php echo htmlspecialchars($payment['transaction_id'] ?? 'Not provided'); ?></code>
                                                        </div>
                                                        
                                                        <?php if (!empty($payment['payment_notes'])): ?>
                                                        <div class="mb-3">
                                                            <strong>Buyer Notes:</strong><br>
                                                            <div class="alert alert-light">
                                                                <?php echo htmlspecialchars($payment['payment_notes']); ?>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="mb-3">
                                                            <strong>Submitted:</strong><br>
                                                            <?php echo isset($payment['submitted_at']) ? date('M j, Y H:i', strtotime($payment['submitted_at'])) : 'Not available'; ?>
                                                        </div>
                                                        
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            Please verify the payment details before confirming completion.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="button" class="btn btn-success" onclick="markAsCompleted(<?php echo $payment['payment_id']; ?>)">
                                                            <i class="fas fa-check me-1"></i>Confirm Payment Received
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function markAsCompleted(paymentId) {
    if (confirm('Mark this payment as completed? This action cannot be undone.')) {
        fetch('ajax/mark_payment_complete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ payment_id: paymentId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Payment marked as completed', 'success');
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
</script>

<?php include '../includes/footer.php'; ?>
