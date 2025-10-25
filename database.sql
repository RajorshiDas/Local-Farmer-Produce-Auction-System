-- Advanced Farmer Auction System Database
CREATE DATABASE IF NOT EXISTS farmer_auction_system;
USE farmer_auction_system;

-- ========================================
-- Farmers table
-- ========================================
CREATE TABLE IF NOT EXISTS Farmers (
    farmer_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    farm_location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================================
-- Buyers table
-- ========================================
CREATE TABLE IF NOT EXISTS Buyers (
    buyer_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================================
-- Products table
-- ========================================
CREATE TABLE IF NOT EXISTS Products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    farmer_id INT NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    starting_bid DECIMAL(10,2) NOT NULL,
    weight DECIMAL(10,2),
    certification VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES Farmers(farmer_id) ON DELETE CASCADE
);

-- ========================================
-- Product Images table (multiple images)
-- ========================================
CREATE TABLE IF NOT EXISTS ProductImages (
    image_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE
);

-- ========================================
-- Auctions table
-- ========================================
CREATE TABLE IF NOT EXISTS Auctions (
    auction_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('Active', 'Closed', 'Cancelled') DEFAULT 'Active',
    current_highest_bid DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE
);

-- ========================================
-- Bids table
-- ========================================
CREATE TABLE IF NOT EXISTS Bids (
    bid_id INT PRIMARY KEY AUTO_INCREMENT,
    auction_id INT NOT NULL,
    buyer_id INT NOT NULL,
    bid_amount DECIMAL(10,2) NOT NULL,
    bid_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_winning_bid BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (auction_id) REFERENCES Auctions(auction_id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES Buyers(buyer_id) ON DELETE CASCADE
);

-- ========================================
-- Payments table
-- ========================================
CREATE TABLE IF NOT EXISTS Payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    auction_id INT NOT NULL,
    buyer_id INT NOT NULL,
    farmer_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending', 'Submitted', 'Completed', 'Failed') DEFAULT 'Pending',
    payment_method VARCHAR(50) NULL,
    transaction_id VARCHAR(100) NULL,
    payment_notes TEXT NULL,
    submitted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auction_id) REFERENCES Auctions(auction_id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES Buyers(buyer_id) ON DELETE CASCADE,
    FOREIGN KEY (farmer_id) REFERENCES Farmers(farmer_id) ON DELETE CASCADE
);

-- ========================================
-- Reviews table
-- ========================================
CREATE TABLE IF NOT EXISTS Reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    buyer_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES Buyers(buyer_id) ON DELETE CASCADE
);

-- ========================================
-- Wishlist table
-- ========================================
CREATE TABLE IF NOT EXISTS Wishlist (
    wishlist_id INT PRIMARY KEY AUTO_INCREMENT,
    buyer_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES Buyers(buyer_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE
);

-- ========================================
-- Notifications table
-- ========================================
CREATE TABLE IF NOT EXISTS Notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    buyer_id INT NOT NULL,
    auction_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES Buyers(buyer_id) ON DELETE CASCADE,
    FOREIGN KEY (auction_id) REFERENCES Auctions(auction_id) ON DELETE CASCADE
);

-- ========================================
-- Trigger to handle auction close, winning bid, payment, and notifications
-- ========================================
DELIMITER $$

CREATE TRIGGER notify_winner
AFTER UPDATE ON Auctions
FOR EACH ROW
BEGIN
    IF NEW.status = 'Closed' AND OLD.status != 'Closed' THEN
        -- Mark the highest bid as winning
        UPDATE Bids 
        SET is_winning_bid = TRUE 
        WHERE auction_id = NEW.auction_id 
        AND bid_amount = NEW.current_highest_bid 
        ORDER BY bid_time ASC 
        LIMIT 1;
        
        -- Create payment record
        INSERT INTO Payments (auction_id, buyer_id, farmer_id, amount)
        SELECT 
            b.auction_id,
            b.buyer_id,
            p.farmer_id,
            b.bid_amount
        FROM Bids b
        JOIN Auctions a ON b.auction_id = a.auction_id
        JOIN Products p ON a.product_id = p.product_id
        WHERE b.auction_id = NEW.auction_id 
        AND b.is_winning_bid = TRUE;

        -- Insert notification for the winner
        INSERT INTO Notifications (buyer_id, auction_id, message)
        SELECT 
            b.buyer_id,
            b.auction_id,
            CONCAT('Congratulations! You won the auction for ', pr.product_name, '.')
        FROM Bids b
        JOIN Products pr ON pr.product_id = NEW.product_id
        WHERE b.auction_id = NEW.auction_id
        AND b.is_winning_bid = TRUE;
    END IF;
END$$

DELIMITER ;

-- ========================================
-- Sample Data for Testing
-- ========================================
INSERT INTO Farmers (name, email, password, phone, address, farm_location) VALUES 
('John Doe', 'john@example.com', '$2y$10$example_hash_farmer1', '01711111111', 'Dhaka, Bangladesh', 'Dhaka'),
('Jane Smith', 'jane@example.com', '$2y$10$example_hash_farmer2', '01722222222', 'Chittagong, Bangladesh', 'Chittagong');

INSERT INTO Buyers (name, email, password, phone, address) VALUES 
('Alice Johnson', 'alice@example.com', '$2y$10$example_hash_buyer1', '01733333333', 'Sylhet, Bangladesh'),
('Bob Wilson', 'bob@example.com', '$2y$10$example_hash_buyer2', '01744444444', 'Rajshahi, Bangladesh');

INSERT INTO Products (farmer_id, product_name, description, category, starting_bid, weight, certification) VALUES 
(1, 'Fresh Tomatoes', 'Organic tomatoes - 10kg', 'Vegetables', 500.00, 10, 'Organic'),
(1, 'Basmati Rice', 'Premium quality rice - 50kg', 'Grains', 2500.00, 50, 'Premium'),
(2, 'Fresh Milk', 'Pure cow milk - 5 liters', 'Dairy', 300.00, 5, 'Organic');

INSERT INTO ProductImages (product_id, image_path) VALUES
(1, 'uploads/products/tomatoes1.jpg'),
(1, 'uploads/products/tomatoes2.jpg'),
(2, 'uploads/products/basmati_rice.jpg'),
(3, 'uploads/products/fresh_milk.jpg');

INSERT INTO Auctions (product_id, start_time, end_time, current_highest_bid) VALUES 
(1, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 600.00),
(2, NOW(), DATE_ADD(NOW(), INTERVAL 5 DAY), 2700.00),
(3, NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), 350.00);

INSERT INTO Bids (auction_id, buyer_id, bid_amount) VALUES 
(1, 1, 550.00),
(1, 2, 600.00),
(2, 1, 2600.00),
(2, 2, 2700.00),
(3, 1, 320.00),
(3, 2, 350.00);
