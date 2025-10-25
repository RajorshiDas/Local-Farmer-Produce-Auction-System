<?php
require_once '../config/database.php';
require_once '../config/session.php';

$page_title = 'Edit Product - Farmer Dashboard';
requireLogin();

if (!isFarmer()) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$farmer_id = $_SESSION['user_id'];
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch product
$query = "SELECT * FROM Products WHERE product_id = ? AND farmer_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$product_id, $farmer_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit();
}

$error = '';
$success = '';

// Fetch existing product images
$images_stmt = $db->prepare("SELECT image_id, image_path FROM ProductImages WHERE product_id = ?");
$images_stmt->execute([$product_id]);
$product_images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $starting_bid = floatval($_POST['starting_bid']);
    $weight = floatval($_POST['weight']);
    $certification = trim($_POST['certification']);

    if (empty($product_name) || empty($description) || empty($category) || $starting_bid <= 0 || $weight <= 0) {
        $error = 'Please fill in all required fields with valid values.';
    } else {
        try {
            $updateQuery = "UPDATE Products SET product_name = ?, description = ?, category = ?, starting_bid = ?, weight = ?, certification = ? WHERE product_id = ? AND farmer_id = ?";
            $uStmt = $db->prepare($updateQuery);
            $uStmt->execute([$product_name, $description, $category, $starting_bid, $weight, $certification, $product_id, $farmer_id]);

            // Handle new image uploads (optional)
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = '../uploads/products/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Remove existing images from disk and DB (we replace gallery with uploaded files)
                if (!empty($product_images)) {
                    foreach ($product_images as $img) {
                        $full_path = __DIR__ . '/../' . $img['image_path'];
                        if (file_exists($full_path)) {
                            @unlink($full_path);
                        }
                    }
                    $delStmt = $db->prepare("DELETE FROM ProductImages WHERE product_id = ?");
                    $delStmt->execute([$product_id]);
                    // clear local array so later code is consistent
                    $product_images = [];
                }

                foreach ($_FILES['images']['name'] as $key => $filename) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
                        $new_filename = $product_id . '_' . $key . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $upload_path)) {
                            $image_path = 'uploads/products/' . $new_filename;
                            $image_query = "INSERT INTO ProductImages (product_id, image_path) VALUES (?, ?)";
                            $image_stmt = $db->prepare($image_query);
                            $image_stmt->execute([$product_id, $image_path]);
                        }
                    }
                }
                // refresh product_images list
                $images_stmt = $db->prepare("SELECT image_id, image_path FROM ProductImages WHERE product_id = ?");
                $images_stmt->execute([$product_id]);
                $product_images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $success = 'Product updated successfully.';
            // Refresh product data
            $stmt = $db->prepare($query);
            $stmt->execute([$product_id, $farmer_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = 'Error updating product: ' . $e->getMessage();
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
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Product</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="product_name" class="form-control" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Category</label>
                                <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($product['category']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Weight (kg)</label>
                                <input type="number" name="weight" class="form-control" step="0.01" value="<?php echo htmlspecialchars($product['weight']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Starting Bid (৳)</label>
                                <input type="number" name="starting_bid" class="form-control" step="0.01" value="<?php echo htmlspecialchars($product['starting_bid']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Certification</label>
                            <input type="text" name="certification" class="form-control" value="<?php echo htmlspecialchars($product['certification']); ?>">
                        </div>

                        <?php if (!empty($product_images)): ?>
                            <div class="mb-3">
                                <label class="form-label">Current Images</label>
                                <div class="d-flex gap-2 flex-wrap mb-2">
                                    <?php foreach ($product_images as $img): ?>
                                        <?php $img_src = '/farmer_auction/' . ltrim($img['image_path'], '/'); ?>
                                        <div style="width:120px;">
                                            <img src="<?php echo $img_src; ?>" class="img-thumbnail" style="width:100%; height:80px; object-fit:cover;" alt="Image">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Upload Additional Images (optional)</label>
                            <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                            <div class="form-text">New images will be added to the product gallery.</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="product_details.php?id=<?php echo $product_id; ?>" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
