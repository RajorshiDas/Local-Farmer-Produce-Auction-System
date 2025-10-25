<?php
require_once 'config/database.php';
require_once 'config/session.php';

$page_title = 'My Profile - Farmer Auction System';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$table = ($user_type === 'farmer') ? 'Farmers' : 'Buyers';
$id_field = ($user_type === 'farmer') ? 'farmer_id' : 'buyer_id';

$message = '';
$error = '';

// Fetch current user data
$query = "SELECT * FROM {$table} WHERE {$id_field} = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Something's wrong; logout and redirect
    session_destroy();
    header('Location: /farmer_auction/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $farm_location = isset($_POST['farm_location']) ? trim($_POST['farm_location']) : null;

        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        // Check if email or password is changing to require current password
        $requires_password = false;
        if ($email !== $user['email']) $requires_password = true;
        if (!empty($new_password)) $requires_password = true;

        if ($requires_password) {
            if (empty($current_password)) {
                $error = 'Please enter your current password to change email or password.';
            } else {
                if (!password_verify($current_password, $user['password'])) {
                    $error = 'Current password is incorrect.';
                }
            }
        }

        if (empty($error)) {
            // Validate password change
            $password_to_store = $user['password'];
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error = 'New password and confirmation do not match.';
                } else {
                    $password_to_store = password_hash($new_password, PASSWORD_DEFAULT);
                }
            }
        }

        if (empty($error)) {
            // Update user
            if ($user_type === 'farmer') {
                $updateQuery = "UPDATE Farmers SET name = ?, email = ?, phone = ?, address = ?, farm_location = ?, password = ? WHERE farmer_id = ?";
                $params = [$name, $email, $phone, $address, $farm_location, $password_to_store, $user_id];
            } else {
                $updateQuery = "UPDATE Buyers SET name = ?, email = ?, phone = ?, address = ?, password = ? WHERE buyer_id = ?";
                $params = [$name, $email, $phone, $address, $password_to_store, $user_id];
            }

            try {
                $uStmt = $db->prepare($updateQuery);
                $uStmt->execute($params);
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $message = 'Profile updated successfully.';
                // Refresh user data
                $stmt = $db->prepare($query);
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Duplicate email or other DB error
                if ($e->getCode() == 23000) {
                    $error = 'The email address is already in use.';
                } else {
                    $error = 'An error occurred while updating your profile.';
                }
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $confirm = isset($_POST['confirm_delete']) ? trim($_POST['confirm_delete']) : '';
        $current_password = isset($_POST['current_password_delete']) ? $_POST['current_password_delete'] : '';

        if ($confirm !== 'DELETE') {
            $error = 'You must type DELETE in the confirmation field to delete your account.';
        } elseif (empty($current_password) || !password_verify($current_password, $user['password'])) {
            $error = 'Current password is required and must be correct to delete your account.';
        } else {
            // Proceed to delete account
            try {
                if ($user_type === 'farmer') {
                    $delQ = 'DELETE FROM Farmers WHERE farmer_id = ?';
                } else {
                    $delQ = 'DELETE FROM Buyers WHERE buyer_id = ?';
                }
                $dStmt = $db->prepare($delQ);
                $dStmt->execute([$user_id]);
                // Destroy session and redirect to home with message
                session_unset();
                session_destroy();
                header('Location: /farmer_auction/index.php');
                exit();
            } catch (PDOException $e) {
                $error = 'An error occurred while deleting your account.';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title mb-4">My Profile</h3>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="update">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>

                        <?php if ($user_type === 'farmer'): ?>
                            <div class="mb-3">
                                <label class="form-label">Farm Location</label>
                                <input type="text" name="farm_location" class="form-control" value="<?php echo htmlspecialchars($user['farm_location']); ?>">
                            </div>
                        <?php endif; ?>

                        <hr>
                        <h5>Change Password (optional)</h5>
                        <div class="mb-3">
                            <label class="form-label">Current Password (required if changing password or email)</label>
                            <input type="password" name="current_password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-success">Save Changes</button>
                            <a href="/farmer_auction/" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>

                    <hr class="my-4">
                    <h5 class="text-danger">Delete Account</h5>
                    <p>Deleting your account will remove your data and related records. This action cannot be undone.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                        <input type="hidden" name="action" value="delete">
                        <div class="mb-3">
                            <label class="form-label">Type <strong>DELETE</strong> to confirm</label>
                            <input type="text" name="confirm_delete" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password_delete" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-danger">Delete My Account</button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
