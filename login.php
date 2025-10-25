
<?php
require_once 'config/database.php';
require_once 'config/session.php';

$page_title = 'Login - Farmer Auction System';
$error = '';

if (isLoggedIn()) {
    redirectToDashboard();
}

if ($_POST) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $table = ($user_type === 'farmer') ? 'Farmers' : 'Buyers';
        $id_field = ($user_type === 'farmer') ? 'farmer_id' : 'buyer_id';
        
        $query = "SELECT {$id_field}, name, email, password FROM {$table} WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user[$id_field];
            $_SESSION['user_type'] = $user_type;
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            
            redirectToDashboard();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-sign-in-alt fa-3x text-success mb-3"></i>
                        <h2 class="fw-bold">Login</h2>
                        <p class="text-muted">Access your account</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="user_type" class="form-label">Account Type</label>
                            <select class="form-select" id="user_type" name="user_type" required>
                                <option value="">Select Account Type</option>
                                <option value="farmer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'farmer') ? 'selected' : ''; ?>>Farmer</option>
                                <option value="buyer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'buyer') ? 'selected' : ''; ?>>Buyer</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted">Don't have an account? 
                            <a href="register.php" class="text-success fw-bold">Register here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
