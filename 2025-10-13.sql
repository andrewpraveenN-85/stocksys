ALTER TABLE products ADD COLUMN pos_id INT NULL DEFAULT NULL;

ALTER TABLE products MODIFY COLUMN unit_id INT NULL DEFAULT NULL;

-- Create the users table
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert the sample data
INSERT INTO `users` (`id`, `username`, `password`, `role`, `full_name`, `email`, `created_at`, `is_active`) VALUES
(3, 'admin', 'admin123', 'admin', 'System Administrator', 'admin@restaurant.com', '2025-10-09 11:06:20', 1),
(4, 'manager', 'manager123', 'manager', 'Restaurant Manager', 'manager@restaurant.com', '2025-10-09 11:06:20', 1);



CREATE TABLE `stock_fifo` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `grn_id` INT NOT NULL,
  `item_type` VARCHAR(50) NOT NULL,
  `item_id` INT NOT NULL,
  `qty_received` DECIMAL(10,3) NOT NULL,
  `qty_remaining` DECIMAL(10,3) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
