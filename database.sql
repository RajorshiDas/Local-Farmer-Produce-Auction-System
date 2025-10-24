-- Create Database
CREATE DATABASE IF NOT EXISTS farmer_auction
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE farmer_auction;

-- -----------------------------
-- Farmers Table
-- -----------------------------
CREATE TABLE IF NOT EXISTS Farmers (
    farmer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default_farmer.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------
-- Buyers Table
-- -----------------------------
CREATE TABLE IF NOT EXISTS Buyers (
    buyer_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------
-- Categories Table
-- -----------------------------
CREATE TABLE IF NOT EXISTS Categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT
);

-- -----------------------------
-- Products Table (Auction Items)
-- -----------------------------
CREATE TABLE IF NOT EXISTS Products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    category_id INT,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    base_price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    auction_start DATETIME,
    auction_end DATETIME,
    status ENUM('pending','active','sold','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES Farmers(farmer_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES Categories(category_id) ON DELETE SET NULL
);

-- -----------------------------
-- Bids Table
-- -----------------------------
CREATE TABLE IF NOT EXISTS Bids (
    bid_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    buyer_id INT NOT NULL,
    bid_amount DECIMAL(10,2) NOT NULL,
    bid_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES Buyers(buyer_id) ON DELETE CASCADE
);

-- -----------------------------
-- Payments Table
-- -----------------------------
CREATE TABLE IF NOT EXISTS Payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    bid_id INT NOT NULL,
    buyer_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    payment_method ENUM('bkash','nagad','bank_transfer','cash') DEFAULT 'bkash',
    payment_status ENUM('pending','completed','failed') DEFAULT 'pending',
    FOREIGN KEY (bid_id) REFERENCES Bids(bid_id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES Buyers(buyer_id) ON DELETE CASCADE
);

-- -----------------------------
-- Admins Table
-- -----------------------------
CREATE TABLE IF NOT EXISTS Admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------
-- Messages (Contact / Support)
-- -----------------------------
CREATE TABLE IF NOT EXISTS Messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    subject VARCHAR(150),
    message TEXT,
    reply TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------
-- Notifications (System Log)
-- -----------------------------
CREATE TABLE IF NOT EXISTS Notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('farmer','buyer','admin') NOT NULL,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------
-- Insert Sample Data
-- -----------------------------
INSERT INTO Categories (category_name, description) VALUES
('Vegetables', 'Fresh farm-grown vegetables.'),
('Fruits', 'Organic fruits from local farmers.'),
('Grains', 'Rice, wheat, and other cereals.'),
('Dairy', 'Milk, cheese, and other dairy products.');

INSERT INTO Farmers (name, email, phone, address, password) VALUES
('Abdul Karim', 'abdul@example.com', '01711112222', 'Rajshahi', '123456'),
('Rahima Begum', 'rahima@example.com', '01833334444', 'Bogura', '123456');

INSERT INTO Buyers (name, email, phone, address, password) VALUES
('Rafiq Hasan', 'rafiq@example.com', '01955556666', 'Dhaka', '654321'),
('Mitu Akter', 'mitu@example.com', '01677778888', 'Chattogram', '654321');

INSERT INTO Products (farmer_id, category_id, title, description, base_price, auction_start, auction_end, status)
VALUES
(1, 1, 'Fresh Tomatoes', 'Red and juicy tomatoes, freshly picked.', 150.00, NOW(), DATE_ADD(NOW(), INTERVAL 2 DAY), 'active'),
(2, 2, 'Organic Mangoes', 'Delicious mangoes from northern farms.', 500.00, NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), 'active');
