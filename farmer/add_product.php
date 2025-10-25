<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'Add Product - Farmer Dashboard';
requireLogin();

if (!isFarmer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$farmer_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_POST) {
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $starting_bid = floatval($_POST['starting_bid']);
    $weight = floatval($_POST['weight']);
    $certification = trim($_POST['certification']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $create_auction = isset($_POST['create_auction']);
    
    // Validation
    if (empty($product_name) || empty($description) || empty($category) || $starting_bid <= 0 || $weight <= 0) {
        $error = 'Please fill in all required fields with valid values.';
    } elseif ($create_auction && (empty($start_time) || empty($end_time))) {
        $error = 'Please provide start and end times for the auction.';
    } elseif ($create_auction && strtotime($end_time) <= strtotime($start_time)) {
        $error = 'End time must be after start time.';
    } else {
        try {
            $db->beginTransaction();
            
            // Insert product
            $product_query = "INSERT INTO Products (farmer_id, product_name, description, category, starting_bid, weight, certification) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
            $product_stmt = $db->prepare($product_query);
            $product_stmt->execute([$farmer_id, $product_name, $description, $category, $starting_bid, $weight, $certification]);
            $product_id = $db->lastInsertId();
            
            // Handle image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = '../uploads/products/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $image_paths = [];
                foreach ($_FILES['images']['name'] as $key => $filename) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
                        $new_filename = $product_id . '_' . $key . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $upload_path)) {
                            $image_paths[] = 'uploads/products/' . $new_filename;
                        }
                    }
                }
                
                // Insert image paths
                foreach ($image_paths as $image_path) {
                    $image_query = "INSERT INTO ProductImages (product_id, image_path) VALUES (?, ?)";
                    $image_stmt = $db->prepare($image_query);
                    $image_stmt->execute([$product_id, $image_path]);
                }
            }
            
            // Create auction if requested
            if ($create_auction) {
                $auction_query = "INSERT INTO Auctions (product_id, start_time, end_time, current_highest_bid) 
                                  VALUES (?, ?, ?, ?)";
                $auction_stmt = $db->prepare($auction_query);
                $auction_stmt->execute([$product_id, $start_time, $end_time, $starting_bid]);
            }
            
            $db->commit();
            $success = 'Product added successfully!' . ($create_auction ? ' Auction created.' : '');
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error adding product: ' . $e->getMessage();
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
                        <i class="fas fa-plus me-2"></i>Add New Product
                    </h4>
                </div>
                <div class="card-body">
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
                                <a href="add_product.php" class="btn btn-outline-success btn-sm">Add Another</a>
                            </div>
                        </div>
                    <?php else: ?>

                    <form method="POST" enctype="multipart/form-data" id="productForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_name" class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" id="product_name" name="product_name" 
                                           value="<?php echo isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Vegetables" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Vegetables') ? 'selected' : ''; ?>>Vegetables</option>
                                        <option value="Fruits" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Fruits') ? 'selected' : ''; ?>>Fruits</option>
                                        <option value="Grains" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Grains') ? 'selected' : ''; ?>>Grains</option>
                                        <option value="Dairy" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Dairy') ? 'selected' : ''; ?>>Dairy</option>
                                        <option value="Spices" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Spices') ? 'selected' : ''; ?>>Spices</option>
                                        <option value="Other" <?php echo (isset($_POST['category']) && $_POST['category'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="starting_bid" class="form-label">Starting Bid (৳) *</label>
                                    <input type="number" class="form-control" id="starting_bid" name="starting_bid" 
                                           value="<?php echo isset($_POST['starting_bid']) ? $_POST['starting_bid'] : ''; ?>" 
                                           min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="weight" class="form-label">Weight (kg) *</label>
                                    <input type="number" class="form-control" id="weight" name="weight" 
                                           value="<?php echo isset($_POST['weight']) ? $_POST['weight'] : ''; ?>" 
                                           min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="certification" class="form-label">Certification</label>
                                    <select class="form-select" id="certification" name="certification">
                                        <option value="">Select Certification</option>
                                        <option value="Organic" <?php echo (isset($_POST['certification']) && $_POST['certification'] === 'Organic') ? 'selected' : ''; ?>>Organic</option>
                                        <option value="Premium" <?php echo (isset($_POST['certification']) && $_POST['certification'] === 'Premium') ? 'selected' : ''; ?>>Premium</option>
                                        <option value="Local" <?php echo (isset($_POST['certification']) && $_POST['certification'] === 'Local') ? 'selected' : ''; ?>>Local</option>
                                        <option value="None" <?php echo (isset($_POST['certification']) && $_POST['certification'] === 'None') ? 'selected' : ''; ?>>None</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="images" class="form-label">Product Images</label>
                            <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" onchange="previewImages(this)">
                            <div class="form-text">You can upload multiple images. Supported formats: JPG, PNG, GIF</div>
                            <div id="image-preview" class="row mt-2"></div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-header">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="create_auction" name="create_auction" 
                                           <?php echo (isset($_POST['create_auction'])) ? 'checked' : ''; ?> onchange="toggleAuctionFields()">
                                    <label class="form-check-label" for="create_auction">
                                        <strong>Create Auction for this Product</strong>
                                    </label>
                                </div>
                            </div>
                            <div class="card-body" id="auction_fields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_time" class="form-label">Auction Start Time *</label>
                                            <input type="datetime-local" class="form-control" id="start_time" name="start_time" 
                                                   value="<?php echo isset($_POST['start_time']) ? $_POST['start_time'] : date('Y-m-d\TH:i'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_time" class="form-label">Auction End Time *</label>
                                            <input type="datetime-local" class="form-control" id="end_time" name="end_time" 
                                                   value="<?php echo isset($_POST['end_time']) ? $_POST['end_time'] : date('Y-m-d\TH:i', strtotime('+7 days')); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="dashboard.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Add Product
                            </button>
                        </div>
                    </form>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAuctionFields() {
    const createAuction = document.getElementById('create_auction').checked;
    const auctionFields = document.getElementById('auction_fields');
    const startTime = document.getElementById('start_time');
    const endTime = document.getElementById('end_time');
    
    if (createAuction) {
        auctionFields.style.display = 'block';
        startTime.required = true;
        endTime.required = true;
    } else {
        auctionFields.style.display = 'none';
        startTime.required = false;
        endTime.required = false;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleAuctionFields();
});
</script>

<?php include '../includes/footer.php'; ?>
