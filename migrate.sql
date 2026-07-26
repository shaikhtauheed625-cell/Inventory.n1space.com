-- Migration: Add new features to n1_shopping
USE n1_shopping;

-- Add cost_price to variations (for profit tracking)
ALTER TABLE variations 
  ADD COLUMN IF NOT EXISTS cost_price DECIMAL(10,2) DEFAULT 0 AFTER price;

-- Add image_path to products
ALTER TABLE products 
  ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) DEFAULT NULL AFTER description;

-- Add discount to sales
ALTER TABLE sales
  ADD COLUMN IF NOT EXISTS discount DECIMAL(10,2) DEFAULT 0 AFTER total_amount;

-- Stock In / Restocking log
CREATE TABLE IF NOT EXISTS stock_in (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variation_id INT NOT NULL,
    quantity INT NOT NULL,
    cost_per_unit DECIMAL(10,2) DEFAULT 0,
    supplier VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (variation_id) REFERENCES variations(id) ON DELETE CASCADE
);

-- Customers table for future CRM
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    total_spent DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
