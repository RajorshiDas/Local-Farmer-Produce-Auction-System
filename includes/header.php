<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Farmer Auction System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/farmer_auction/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="/farmer_auction/index.php">
                <i class="fas fa-seedling me-2"></i>Farmer Auction
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/farmer_auction/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/farmer_auction/auctions.php">Active Auctions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/farmer_auction/upcoming_auctions.php">Upcoming Auctions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/farmer_auction/past_auctions.php">Past Auctions</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isFarmer()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/farmer_auction/farmer/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/farmer_auction/farmer/add_product.php">Add Product</a>
                            </li>
                        <?php elseif (isBuyer()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/farmer_auction/buyer/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/farmer_auction/buyer/wishlist.php">Wishlist</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo $_SESSION['name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/farmer_auction/profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="/farmer_auction/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/farmer_auction/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/farmer_auction/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
