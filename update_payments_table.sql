-- Update Payments table to support buyer payment submissions
-- Run this SQL to add new payment tracking fields

ALTER TABLE Payments 
ADD COLUMN payment_method VARCHAR(50) NULL,
ADD COLUMN transaction_id VARCHAR(100) NULL,
ADD COLUMN payment_notes TEXT NULL,
ADD COLUMN submitted_at TIMESTAMP NULL,
MODIFY COLUMN status ENUM('Pending', 'Submitted', 'Completed', 'Failed') DEFAULT 'Pending';

-- Add indexes for better performance
CREATE INDEX idx_payments_status ON Payments(status);
CREATE INDEX idx_payments_auction ON Payments(auction_id);

-- Update existing pending payments to show proper status
-- (No changes needed as existing payments remain 'Pending')