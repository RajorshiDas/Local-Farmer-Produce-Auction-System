<?php
require_once 'config/database.php';
require_once 'config/session.php';

$page_title = 'Register - Farmer Auction System';
$error = '';
$success = '';

if (isLoggedIn()) {
    redirectToDashboard();
}

if ($_POST) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $user_type = $_POST['user_type'];
    $farm_location = ($user_type === 'farmer') ? trim($_POST['farm_location']) : '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($phone) || empty($address)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($user_type === 'farmer' && empty($farm_location)) {
        $error = 'Farm location is required for farmers.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if email already exists
        $table = ($user_type === 'farmer') ? 'Farmers' : 'Buyers';
        $check_query = "SELECT email FROM {$table} WHERE email = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$email]);
        
        if ($check_stmt->fetch()) {
            $error = 'Email address already exists.';
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if ($user_type === 'farmer') {
                $insert_query = "INSERT INTO Farmers (name, email, password, phone, address, farm_location) VALUES (?, ?, ?, ?, ?, ?)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->execute([$name, $email, $hashed_password, $phone, $address, $farm_location]);
            } else {
                $insert_query = "INSERT INTO Buyers (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->execute([$name, $email, $hashed_password, $phone, $address]);
            }
            
            $success = 'Registration successful! You can now login.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-success mb-3"></i>
                        <h2 class="fw-bold">Create Account</h2>
                        <p class="text-muted">Join our farming community</p>
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
                                <a href="login.php" class="btn btn-success btn-sm">Go to Login</a>
                            </div>
                        </div>
                    <?php else: ?>

                    <form method="POST" id="registerForm">
                        <div class="mb-3">
                            <label for="user_type" class="form-label">Account Type *</label>
                            <select class="form-select" id="user_type" name="user_type" required onchange="toggleFarmLocation()">
                                <option value="">Select Account Type</option>
                                <option value="farmer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'farmer') ? 'selected' : ''; ?>>Farmer</option>
                                <option value="buyer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'buyer') ? 'selected' : ''; ?>>Buyer</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Minimum 6 characters</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3" id="farm_location_div" style="display: none;">
                                    <label for="farm_location" class="form-label">Farm Location *</label>
                                    <input type="text" class="form-control" id="farm_location" name="farm_location" 
                                           value="<?php echo isset($_POST['farm_location']) ? htmlspecialchars($_POST['farm_location']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label">Address *</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </form>

                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <p class="text-muted">Already have an account? 
                            <a href="login.php" class="text-success fw-bold">Login here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFarmLocation() {
    const userType = document.getElementById('user_type').value;
    const farmLocationDiv = document.getElementById('farm_location_div');
    const farmLocationInput = document.getElementById('farm_location');
    
    if (userType === 'farmer') {
        farmLocationDiv.style.display = 'block';
        farmLocationInput.required = true;
    } else {
        farmLocationDiv.style.display = 'none';
        farmLocationInput.required = false;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleFarmLocation();
});
</script>

<?php include 'includes/footer.php'; ?>
