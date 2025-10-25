<?php
// Suppress PHP warnings for production
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

require_once 'config/database.php';
require_once 'config/session.php';

$page_title = 'Upcoming Auctions - Farmer Auction System';
include 'includes/header.php';

// Get upcoming auctions (start_time in future)
$database = new Database();
$db = $database->getConnection();

$query = "SELECT a.*, p.product_name, p.description, p.category, p.weight, p.certification, p.starting_bid,
          f.name as farmer_name, f.farm_location,
          (SELECT image_path FROM ProductImages WHERE product_id = p.product_id LIMIT 1) as main_image
          FROM Auctions a 
          JOIN Products p ON a.product_id = p.product_id 
          JOIN Farmers f ON p.farmer_id = f.farmer_id 
          WHERE a.start_time > NOW() AND a.status = 'Active'
          ORDER BY a.start_time ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-lg-8 mx-auto text-center">
            <h1 class="display-4 fw-bold text-info mb-3">
                <i class="fas fa-clock me-3"></i>Upcoming Auctions
            </h1>
            <p class="lead text-muted mb-4">
                Get ready for these exciting auctions starting soon!
            </p>
        </div>
    </div>

    <!-- Upcoming Auctions Section -->
    <div class="row">
        <?php if (empty($auctions)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    No upcoming auctions at the moment. Check back later!
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($auctions as $auction): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card auction-card h-100" data-auction-id="<?php echo $auction['auction_id']; ?>">
                        <?php if ($auction['main_image']): ?>
                            <img src="<?php echo '/farmer_auction/' . ltrim($auction['main_image'], '/'); ?>" class="card-img-top product-image" alt="<?php echo htmlspecialchars($auction['product_name']); ?>">
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
                            
                            <div class="alert alert-info mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-clock me-1"></i>
                                        <strong>Upcoming Auction</strong>
                                    </span>
                                    <span class="badge bg-primary">
                                        ৳<?php echo number_format($auction['starting_bid'], 2); ?>
                                    </span>
                                </div>
                                
                                <!-- Start Date Display -->
                                <div class="text-center mt-2">
                                    <small class="text-muted">Starts on:</small><br>
                                    <strong class="text-info"><?php echo date('M j, Y H:i', strtotime($auction['start_time'])); ?></strong>
                                </div>
                                
                                <!-- Countdown to Start -->
                                <div class="text-center mt-2">
                                    <small class="text-muted">Time until start:</small><br>
                                    <span class="countdown-timer upcoming-timer h6 text-primary" 
                                          data-start-time="<?php echo $auction['start_time']; ?>"
                                          data-end-time="<?php echo $auction['end_time']; ?>">
                                        Loading...
                                    </span>
                                </div>
                                
                                <!-- End Date Display -->
                                <div class="text-center mt-2">
                                    <small class="text-muted">Will end on:</small><br>
                                    <strong class="text-secondary"><?php echo date('M j, Y H:i', strtotime($auction['end_time'])); ?></strong>
                                </div>
                                
                                <!-- Auction Duration Display -->
                                <div class="text-center mt-2">
                                    <small class="text-muted">Auction Duration:</small><br>
                                    <?php 
                                    $duration_seconds = strtotime($auction['end_time']) - strtotime($auction['start_time']);
                                    $duration_hours = floor($duration_seconds / 3600);
                                    $duration_days = floor($duration_hours / 24);
                                    $remaining_hours = $duration_hours % 24;
                                    
                                    if ($duration_days > 0) {
                                        echo "<strong class='text-info'>{$duration_days}d {$remaining_hours}h</strong>";
                                    } else {
                                        echo "<strong class='text-info'>{$duration_hours}h</strong>";
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="auction_details.php?id=<?php echo $auction['auction_id']; ?>" class="btn btn-outline-info">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                <?php if (isLoggedIn() && isBuyer()): ?>
                                    <button class="btn btn-info" disabled>
                                        <i class="fas fa-clock me-1"></i>Auction Not Started
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Countdown timer for upcoming auctions (counts down to start_time)
function updateCountdownToStart() {
    const countdowns = document.querySelectorAll('.countdown-to-start');
    
    countdowns.forEach(function(element) {
        const startTime = new Date(element.getAttribute('data-start-time')).getTime();
        const now = new Date().getTime();
        const distance = startTime - now;
        
        if (distance > 0) {
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            element.innerHTML = days + "d " + hours + "h " + minutes + "m " + seconds + "s";
        } else {
            element.innerHTML = "Starting now...";
            // Optionally reload page when auction starts
            setTimeout(() => location.reload(), 2000);
        }
    });
}

// Update countdown every second
setInterval(updateCountdownToStart, 1000);
updateCountdownToStart(); // Initial call
</script>

<?php include 'includes/footer.php'; ?>